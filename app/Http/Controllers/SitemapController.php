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
                'loc' => url('/page/' . $page->slug),
                'lastmod' => $page->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        return $urls;
    }
}
