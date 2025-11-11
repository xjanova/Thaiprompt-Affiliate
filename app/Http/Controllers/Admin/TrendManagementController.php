<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrendSource;
use App\Models\ViralTrend;
use App\Models\TrendKeyword;
use App\Services\TrendDetection\ViralTrendDetectionService;
use App\Services\TrendDetection\TrendScraperService;
use App\Services\TrendDetection\TrendContentGeneratorService;
use Illuminate\Http\Request;

class TrendManagementController extends Controller
{
    public function __construct(
        protected ViralTrendDetectionService $detectionService,
        protected TrendScraperService $scraperService,
        protected TrendContentGeneratorService $contentGenerator
    ) {}

    /**
     * Display trend dashboard
     */
    public function index()
    {
        $dashboardData = $this->detectionService->getDashboardData();

        return view('admin.trends.index', [
            'rising_trends' => $dashboardData['rising_trends'],
            'top_keywords' => $dashboardData['top_keywords'],
            'trending_now' => $dashboardData['trending_now'],
            'stats' => $dashboardData['analytics_summary'],
        ]);
    }

    /**
     * Show viral trend details
     */
    public function show(ViralTrend $trend)
    {
        $trend->load('keyword', 'analyticsSnapshots');

        $chartData = $this->detectionService->getChartData($trend);

        return view('admin.trends.show', [
            'trend' => $trend,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Manage trend sources
     */
    public function sources()
    {
        $sources = TrendSource::withCount('trendData')
            ->orderBy('priority', 'desc')
            ->paginate(20);

        return view('admin.trends.sources', compact('sources'));
    }

    /**
     * Create trend source
     */
    public function createSource()
    {
        return view('admin.trends.create-source');
    }

    /**
     * Store trend source
     */
    public function storeSource(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:web,api,rss,social_media',
            'url' => 'required|url',
            'scraping_method' => 'required|in:html,json,xml,rss',
            'check_interval' => 'required|integer|min:1',
            'priority' => 'required|integer|min:1|max:10',
            'api_config' => 'nullable|array',
            'selectors' => 'nullable|array',
        ]);

        $source = TrendSource::create($validated);

        return redirect()
            ->route('admin.trends.sources')
            ->with('success', 'Trend source created successfully');
    }

    /**
     * Edit trend source
     */
    public function editSource(TrendSource $source)
    {
        return view('admin.trends.edit-source', compact('source'));
    }

    /**
     * Update trend source
     */
    public function updateSource(Request $request, TrendSource $source)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:web,api,rss,social_media',
            'url' => 'required|url',
            'scraping_method' => 'required|in:html,json,xml,rss',
            'check_interval' => 'required|integer|min:1',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'api_config' => 'nullable|array',
            'selectors' => 'nullable|array',
        ]);

        $source->update($validated);

        return redirect()
            ->route('admin.trends.sources')
            ->with('success', 'Trend source updated successfully');
    }

    /**
     * Delete trend source
     */
    public function deleteSource(TrendSource $source)
    {
        $source->delete();

        return redirect()
            ->route('admin.trends.sources')
            ->with('success', 'Trend source deleted successfully');
    }

    /**
     * Test scrape a source
     */
    public function testScrape(TrendSource $source)
    {
        $result = $this->scraperService->scrape($source);

        return response()->json($result);
    }

    /**
     * Generate content for a trend
     */
    public function generateContent(Request $request, ViralTrend $trend)
    {
        $validated = $request->validate([
            'content_type' => 'required|in:blog,social,summary,newsletter',
        ]);

        $result = $this->contentGenerator->generateContent(
            $trend,
            $validated['content_type']
        );

        return response()->json($result);
    }

    /**
     * Browse keywords
     */
    public function keywords()
    {
        $keywords = TrendKeyword::trending()
            ->paginate(50);

        return view('admin.trends.keywords', compact('keywords'));
    }
}
