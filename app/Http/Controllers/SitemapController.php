<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate sitemap.xml
     */
    public function index(): Response
    {
        $urls = $this->getSitemapUrls();

        $xml = view('sitemap.index', compact('urls'))->render();

        return response($xml, 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * สร้าง robots.txt แบบ dynamic
     *
     * ⚠️ ต้องเป็น route (ไม่ใช่ไฟล์ static ใน public/) เพื่อให้บรรทัด Sitemap
     * แสดง URL จริงตาม config('app.url') และเพิ่ม directive สำหรับ AI crawler ได้
     *
     * นโยบาย: อนุญาต AI crawler ทั้งหมด (Google-Extended, GPTBot, ClaudeBot ฯลฯ)
     * เพื่อเพิ่มการมองเห็นใน AI Overviews / Gemini / ChatGPT / Perplexity
     * แต่ยังกันไม่ให้เข้าโซน admin/user/api เหมือน bot ทั่วไป
     *
     * @return Response
     */
    public function robots(): Response
    {
        // โซนที่ห้าม crawl (ทั้ง bot ทั่วไปและ AI)
        $disallow = ['/admin', '/user', '/login', '/register', '/api/'];

        // AI crawler หลักที่เราต้องการ "อนุญาต" ให้เข้าถึง/อ้างอิงเนื้อหา
        $aiAgents = [
            'Google-Extended',   // Gemini / Vertex AI / AI Overviews grounding ของ Google
            'GPTBot',            // OpenAI (ChatGPT training)
            'OAI-SearchBot',     // OpenAI (ChatGPT Search)
            'ChatGPT-User',      // OpenAI (เปิดลิงก์ตามคำขอผู้ใช้)
            'ClaudeBot',         // Anthropic (Claude)
            'anthropic-ai',      // Anthropic (เดิม)
            'PerplexityBot',     // Perplexity
            'CCBot',             // Common Crawl (ป้อนหลาย AI)
            'Applebot-Extended', // Apple Intelligence
        ];

        $lines = [];

        // กลุ่มค่าเริ่มต้นสำหรับ bot ทุกตัว
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        foreach ($disallow as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';

        // กลุ่ม AI crawler — ระบุ Allow ชัดเจน (ต้อง repeat disallow เพราะ robots.txt
        // ใช้กลุ่มที่ specific ที่สุดกลุ่มเดียว ไม่ merge กับกลุ่ม *)
        $lines[] = '# AI crawlers — อนุญาตให้เข้าถึง/อ้างอิงเนื้อหา (เพิ่ม visibility ใน AI Overviews / Gemini / ChatGPT / Perplexity)';
        foreach ($aiAgents as $agent) {
            $lines[] = 'User-agent: '.$agent;
            $lines[] = 'Allow: /';
            foreach ($disallow as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = '';
        }

        // ประกาศ Sitemap ด้วย URL จริง
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Get all URLs for sitemap
     */
    private function getSitemapUrls(): array
    {
        $urls = [];
        $now = now()->toAtomString();

        // Homepage
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => $now,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Static pages
        $staticPages = [
            ['url' => '/about', 'priority' => '0.8'],
            ['url' => '/contact', 'priority' => '0.8'],
            ['url' => '/register', 'priority' => '0.9'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => url($page['url']),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => $page['priority'],
            ];
        }

        // Dynamic pages from CMS
        $cmsPages = Page::where('is_active', true)->get();
        foreach ($cmsPages as $page) {
            $urls[] = [
                'loc' => url('/page/'.$page->slug),
                'lastmod' => $page->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        return $urls;
    }
}
