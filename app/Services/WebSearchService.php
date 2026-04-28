<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Web Search Service — รองรับหลาย provider แบบ fallback chain
 *
 * Provider order:
 * 1. Tavily (1000 free/เดือน) — return clean LLM-ready content
 * 2. Brave Search API (2000 free/เดือน) — JSON results
 * 3. DuckDuckGo HTML scraping (ไม่จำกัด, ไม่ต้อง key) — last resort
 *
 * ใช้กับ MysticContentAutoPostService เพื่อหา reference articles
 * แล้วส่งให้ Groq Llama เขียนเป็นบทความใหม่
 */
class WebSearchService
{
    /**
     * ค้นเว็บ — เลือก provider ที่ใช้ได้
     *
     * @param  string  $query  คำค้น (ภาษาไทยหรืออังกฤษ)
     * @param  int  $maxResults  จำนวนผลลัพธ์ที่ต้องการ
     * @return array  ['provider' => string, 'results' => array<{url, title, snippet, content?}>]
     */
    public function search(string $query, int $maxResults = 5): array
    {
        // 🥇 Tavily (LLM-friendly)
        $tavilyKey = $this->getTavilyKey();
        if (! empty($tavilyKey)) {
            try {
                $results = $this->searchWithTavily($query, $maxResults, $tavilyKey);
                if (! empty($results)) {
                    return ['provider' => 'tavily', 'results' => $results];
                }
            } catch (Exception $e) {
                Log::warning('WebSearch: Tavily ล้มเหลว → ลอง Brave', ['error' => $e->getMessage()]);
            }
        }

        // 🥈 Brave Search
        $braveKey = $this->getBraveKey();
        if (! empty($braveKey)) {
            try {
                $results = $this->searchWithBrave($query, $maxResults, $braveKey);
                if (! empty($results)) {
                    return ['provider' => 'brave', 'results' => $results];
                }
            } catch (Exception $e) {
                Log::warning('WebSearch: Brave ล้มเหลว → ลอง DuckDuckGo', ['error' => $e->getMessage()]);
            }
        }

        // 🥉 DuckDuckGo HTML scraping (no key)
        try {
            $results = $this->searchWithDuckDuckGo($query, $maxResults);
            if (! empty($results)) {
                return ['provider' => 'duckduckgo', 'results' => $results];
            }
        } catch (Exception $e) {
            Log::warning('WebSearch: DuckDuckGo ล้มเหลว', ['error' => $e->getMessage()]);
        }

        return ['provider' => 'none', 'results' => []];
    }

    /**
     * 🥇 Tavily Search — designed for LLM, returns clean extracted content
     *
     * Free tier: 1000 calls/เดือน
     * Doc: https://docs.tavily.com/docs/python-sdk/tavily-search/api-reference
     */
    protected function searchWithTavily(string $query, int $maxResults, string $apiKey): array
    {
        $response = Http::timeout(20)
            ->asJson()
            ->post('https://api.tavily.com/search', [
                'api_key' => $apiKey,
                'query' => $query,
                'search_depth' => 'basic', // basic หรือ advanced (advanced ใช้ credit เพิ่ม)
                'include_answer' => false,
                'include_raw_content' => true, // เอา content ตัวเต็ม → ส่งให้ LLM rewrite
                'max_results' => $maxResults,
            ]);

        if (! $response->successful()) {
            throw new Exception('Tavily HTTP ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json('results', []);
        $results = [];

        foreach ($data as $item) {
            $results[] = [
                'url' => $item['url'] ?? '',
                'title' => $item['title'] ?? '',
                'snippet' => mb_substr($item['content'] ?? '', 0, 500),
                'content' => mb_substr($item['raw_content'] ?? $item['content'] ?? '', 0, 3000),
            ];
        }

        return $results;
    }

    /**
     * 🥈 Brave Search API — fallback
     *
     * Free tier: 2000 calls/เดือน, 1 query/วินาที
     * Doc: https://api.search.brave.com/app/documentation/web-search/get-started
     */
    protected function searchWithBrave(string $query, int $maxResults, string $apiKey): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Subscription-Token' => $apiKey,
            ])
            ->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $query,
                'count' => min($maxResults, 20),
                'country' => 'TH',
                'search_lang' => 'th',
            ]);

        if (! $response->successful()) {
            throw new Exception('Brave HTTP ' . $response->status() . ': ' . $response->body());
        }

        $items = $response->json('web.results', []);
        $results = [];

        foreach (array_slice($items, 0, $maxResults) as $item) {
            $results[] = [
                'url' => $item['url'] ?? '',
                'title' => $item['title'] ?? '',
                'snippet' => mb_substr($item['description'] ?? '', 0, 500),
                'content' => mb_substr($item['description'] ?? '', 0, 1500),
            ];
        }

        return $results;
    }

    /**
     * 🥉 DuckDuckGo HTML scraping — last resort (no key needed)
     *
     * ใช้ HTML interface เพราะ DDG ไม่มี free JSON API
     * Pattern: <a class="result__a" href="...">Title</a> + <a class="result__snippet">Snippet</a>
     */
    protected function searchWithDuckDuckGo(string $query, int $maxResults): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                    . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])
            ->get('https://html.duckduckgo.com/html/', [
                'q' => $query,
                'kl' => 'th-th',
            ]);

        if (! $response->successful()) {
            throw new Exception('DuckDuckGo HTTP ' . $response->status());
        }

        $html = $response->body();
        $results = [];

        // หา <h2 class="result__title"><a class="result__a" href="...">Title</a></h2>
        if (preg_match_all(
            '/<a[^>]*class="[^"]*result__a[^"]*"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is',
            $html,
            $linkMatches
        )) {
            // หา <a class="result__snippet">Snippet</a>
            preg_match_all(
                '/<a[^>]*class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/a>/is',
                $html,
                $snippetMatches
            );

            $count = min(count($linkMatches[1]), $maxResults);

            for ($i = 0; $i < $count; $i++) {
                $rawUrl = html_entity_decode($linkMatches[1][$i]);

                // DDG redirects ผ่าน /l/?uddg=... — extract URL จริง
                if (preg_match('/uddg=([^&]+)/', $rawUrl, $m)) {
                    $rawUrl = urldecode($m[1]);
                }

                $title = trim(strip_tags($linkMatches[2][$i]));
                $snippet = isset($snippetMatches[1][$i])
                    ? trim(strip_tags($snippetMatches[1][$i]))
                    : '';

                if ($title !== '' && $rawUrl !== '') {
                    $results[] = [
                        'url' => $rawUrl,
                        'title' => mb_substr($title, 0, 200),
                        'snippet' => mb_substr($snippet, 0, 500),
                        'content' => mb_substr($snippet, 0, 800),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Tavily key — ดึงจาก settings ก่อน → fallback config
     */
    protected function getTavilyKey(): ?string
    {
        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $key = $settings->tavily_api_key ?? null;
            if (! empty($key)) {
                return $key;
            }
        } catch (Exception $e) {
            // settings ไม่พร้อม — ลอง config ต่อ
        }

        return config('services.tavily.api_key', env('TAVILY_API_KEY')) ?: null;
    }

    /**
     * Brave key — ดึงจาก settings ก่อน → fallback config
     */
    protected function getBraveKey(): ?string
    {
        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $key = $settings->brave_search_api_key ?? null;
            if (! empty($key)) {
                return $key;
            }
        } catch (Exception $e) {
            //
        }

        return config('services.brave_search.api_key', env('BRAVE_SEARCH_API_KEY')) ?: null;
    }
}
