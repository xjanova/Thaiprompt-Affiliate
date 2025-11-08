<?php

namespace App\Services\TrendDetection;

use App\Models\TrendSource;
use App\Models\TrendData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class TrendScraperService
{
    /**
     * Scrape data from a trend source
     */
    public function scrape(TrendSource $source): array
    {
        try {
            $data = match($source->type) {
                'web' => $this->scrapeWeb($source),
                'api' => $this->scrapeApi($source),
                'rss' => $this->scrapeRss($source),
                'social_media' => $this->scrapeSocialMedia($source),
                default => throw new \Exception("Unsupported source type: {$source->type}")
            };

            $source->markAsChecked();

            return [
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to scrape source {$source->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'count' => 0,
            ];
        }
    }

    /**
     * Scrape HTML website
     */
    protected function scrapeWeb(TrendSource $source): array
    {
        $response = Http::timeout(30)
            ->withHeaders($this->getHeaders($source))
            ->get($source->url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
        }

        $html = $response->body();
        $crawler = new Crawler($html);
        $selectors = $source->selectors ?? [];

        $items = [];
        $containerSelector = $selectors['container'] ?? 'article, .post, .item';

        $crawler->filter($containerSelector)->each(function (Crawler $node) use ($selectors, $source, &$items) {
            try {
                $item = [
                    'title' => $this->extractText($node, $selectors['title'] ?? 'h1, h2, h3, .title'),
                    'content' => $this->extractText($node, $selectors['content'] ?? 'p, .content, .description'),
                    'url' => $this->extractAttribute($node, $selectors['url'] ?? 'a', 'href'),
                    'author' => $this->extractText($node, $selectors['author'] ?? '.author, .by'),
                    'engagement_count' => $this->extractNumber($node, $selectors['engagement'] ?? '.likes, .reactions'),
                    'comment_count' => $this->extractNumber($node, $selectors['comments'] ?? '.comments'),
                    'share_count' => $this->extractNumber($node, $selectors['shares'] ?? '.shares'),
                    'view_count' => $this->extractNumber($node, $selectors['views'] ?? '.views'),
                ];

                // Make URL absolute
                if ($item['url'] && !str_starts_with($item['url'], 'http')) {
                    $baseUrl = parse_url($source->url, PHP_URL_SCHEME) . '://' .
                               parse_url($source->url, PHP_URL_HOST);
                    $item['url'] = $baseUrl . $item['url'];
                }

                $items[] = $this->createTrendData($source, $item);
            } catch (\Exception $e) {
                Log::warning("Failed to parse item: " . $e->getMessage());
            }
        });

        return $items;
    }

    /**
     * Scrape API endpoint
     */
    protected function scrapeApi(TrendSource $source): array
    {
        $config = $source->api_config ?? [];
        $headers = $config['headers'] ?? [];
        $params = $config['params'] ?? [];
        $method = $config['method'] ?? 'GET';

        $request = Http::timeout(30)->withHeaders($headers);

        if (isset($config['bearer_token'])) {
            $request->withToken($config['bearer_token']);
        }

        $response = match(strtoupper($method)) {
            'POST' => $request->post($source->url, $params),
            'PUT' => $request->put($source->url, $params),
            default => $request->get($source->url, $params),
        };

        if (!$response->successful()) {
            throw new \Exception("API request failed: HTTP {$response->status()}");
        }

        $data = $response->json();
        $dataPath = $config['data_path'] ?? null;

        // Extract data from nested path if specified
        if ($dataPath) {
            $data = data_get($data, $dataPath, []);
        }

        $items = [];
        foreach ((array) $data as $item) {
            $items[] = $this->createTrendData($source, $this->mapApiData($item, $config));
        }

        return $items;
    }

    /**
     * Scrape RSS feed
     */
    protected function scrapeRss(TrendSource $source): array
    {
        $response = Http::timeout(30)->get($source->url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch RSS feed: HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        $items = [];

        if ($xml === false) {
            throw new \Exception("Invalid RSS feed");
        }

        foreach ($xml->channel->item as $item) {
            $items[] = $this->createTrendData($source, [
                'title' => (string) $item->title,
                'content' => strip_tags((string) $item->description),
                'url' => (string) $item->link,
                'author' => (string) ($item->author ?? $item->creator ?? ''),
                'published_at' => isset($item->pubDate) ?
                    date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null,
            ]);
        }

        return $items;
    }

    /**
     * Scrape social media (placeholder for platform-specific APIs)
     */
    protected function scrapeSocialMedia(TrendSource $source): array
    {
        // This would integrate with specific social media APIs
        // Twitter API, Facebook Graph API, Instagram API, etc.

        return $this->scrapeApi($source);
    }

    /**
     * Create TrendData record
     */
    protected function createTrendData(TrendSource $source, array $data): TrendData
    {
        $trendData = TrendData::create([
            'trend_source_id' => $source->id,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'url' => $data['url'] ?? null,
            'author' => $data['author'] ?? null,
            'engagement_count' => $data['engagement_count'] ?? 0,
            'comment_count' => $data['comment_count'] ?? 0,
            'share_count' => $data['share_count'] ?? 0,
            'view_count' => $data['view_count'] ?? 0,
            'raw_data' => $data,
            'published_at' => $data['published_at'] ?? now(),
            'scraped_at' => now(),
        ]);

        $trendData->updateViralScore();

        return $trendData;
    }

    /**
     * Extract text from node
     */
    protected function extractText(Crawler $node, string $selector): ?string
    {
        try {
            $element = $node->filter($selector);
            return $element->count() > 0 ? trim($element->text()) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract attribute from node
     */
    protected function extractAttribute(Crawler $node, string $selector, string $attribute): ?string
    {
        try {
            $element = $node->filter($selector);
            return $element->count() > 0 ? $element->attr($attribute) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract number from text
     */
    protected function extractNumber(Crawler $node, string $selector): int
    {
        $text = $this->extractText($node, $selector);
        if (!$text) {
            return 0;
        }

        // Remove non-numeric characters except decimal point
        $number = preg_replace('/[^0-9.]/', '', $text);

        // Handle K, M, B suffixes
        if (str_contains(strtoupper($text), 'K')) {
            $number = (float) $number * 1000;
        } elseif (str_contains(strtoupper($text), 'M')) {
            $number = (float) $number * 1000000;
        } elseif (str_contains(strtoupper($text), 'B')) {
            $number = (float) $number * 1000000000;
        }

        return (int) $number;
    }

    /**
     * Map API data to standard format
     */
    protected function mapApiData(array $item, array $config): array
    {
        $mapping = $config['field_mapping'] ?? [];

        return [
            'title' => data_get($item, $mapping['title'] ?? 'title'),
            'content' => data_get($item, $mapping['content'] ?? 'content'),
            'url' => data_get($item, $mapping['url'] ?? 'url'),
            'author' => data_get($item, $mapping['author'] ?? 'author'),
            'engagement_count' => data_get($item, $mapping['engagement'] ?? 'engagement', 0),
            'comment_count' => data_get($item, $mapping['comments'] ?? 'comments', 0),
            'share_count' => data_get($item, $mapping['shares'] ?? 'shares', 0),
            'view_count' => data_get($item, $mapping['views'] ?? 'views', 0),
            'published_at' => data_get($item, $mapping['published_at'] ?? 'published_at'),
        ];
    }

    /**
     * Get HTTP headers for request
     */
    protected function getHeaders(TrendSource $source): array
    {
        $config = $source->api_config ?? [];

        return array_merge([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
        ], $config['headers'] ?? []);
    }

    /**
     * Scrape multiple sources
     */
    public function scrapeMultiple(array $sources): array
    {
        $results = [];

        foreach ($sources as $source) {
            $results[$source->id] = $this->scrape($source);
        }

        return $results;
    }

    /**
     * Scrape all ready sources
     */
    public function scrapeAllReady(): array
    {
        $sources = TrendSource::readyToScrape()
            ->byPriority()
            ->get();

        return $this->scrapeMultiple($sources->all());
    }
}
