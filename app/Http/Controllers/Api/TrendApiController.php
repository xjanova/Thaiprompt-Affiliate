<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViralTrend;
use App\Models\TrendKeyword;
use App\Models\TrendSource;
use App\Services\TrendDetection\ViralTrendDetectionService;
use App\Services\TrendDetection\KeywordAnalyzerService;
use App\Services\TrendDetection\TrendContentGeneratorService;
use Illuminate\Http\Request;

class TrendApiController extends Controller
{
    public function __construct(
        protected ViralTrendDetectionService $detectionService,
        protected KeywordAnalyzerService $analyzerService,
        protected TrendContentGeneratorService $contentGenerator
    ) {}

    /**
     * Get trending dashboard data
     */
    public function dashboard()
    {
        $data = $this->detectionService->getDashboardData();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * List viral trends
     */
    public function index(Request $request)
    {
        $query = ViralTrend::with('keyword');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('started_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('started_at', '<=', $request->end_date);
        }

        // Order
        $orderBy = $request->get('order_by', 'viral_score');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = min($request->get('per_page', 20), 100);
        $trends = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get specific viral trend
     */
    public function show(ViralTrend $trend)
    {
        $trend->load('keyword', 'analyticsSnapshots');

        $chartData = $this->detectionService->getChartData($trend);

        return response()->json([
            'success' => true,
            'data' => [
                'trend' => $trend,
                'chart_data' => $chartData,
            ],
        ]);
    }

    /**
     * Get trending keywords
     */
    public function trendingKeywords(Request $request)
    {
        $limit = min($request->get('limit', 50), 100);
        $minScore = $request->get('min_score', 10);

        $keywords = $this->analyzerService->getTrendingKeywords($limit, $minScore);

        return response()->json([
            'success' => true,
            'data' => $keywords,
        ]);
    }

    /**
     * Get emerging keywords
     */
    public function emergingKeywords(Request $request)
    {
        $hoursBack = min($request->get('hours', 24), 168); // Max 1 week
        $limit = min($request->get('limit', 20), 50);

        $keywords = $this->analyzerService->detectEmergingKeywords($hoursBack, $limit);

        return response()->json([
            'success' => true,
            'data' => $keywords,
        ]);
    }

    /**
     * Get related keywords
     */
    public function relatedKeywords(TrendKeyword $keyword, Request $request)
    {
        $limit = min($request->get('limit', 10), 50);

        $related = $this->analyzerService->findRelatedKeywords($keyword, $limit);

        return response()->json([
            'success' => true,
            'data' => $related,
        ]);
    }

    /**
     * Generate content for trend
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
     * Get trend sources
     */
    public function sources(Request $request)
    {
        $query = TrendSource::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $sources = $query->byPriority()->get();

        return response()->json([
            'success' => true,
            'data' => $sources,
        ]);
    }

    /**
     * Create trend source
     */
    public function createSource(Request $request)
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

        return response()->json([
            'success' => true,
            'data' => $source,
            'message' => 'Source created successfully',
        ], 201);
    }

    /**
     * Update trend source
     */
    public function updateSource(Request $request, TrendSource $source)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:web,api,rss,social_media',
            'url' => 'sometimes|required|url',
            'scraping_method' => 'sometimes|required|in:html,json,xml,rss',
            'check_interval' => 'sometimes|required|integer|min:1',
            'priority' => 'sometimes|required|integer|min:1|max:10',
            'is_active' => 'sometimes|boolean',
            'api_config' => 'nullable|array',
            'selectors' => 'nullable|array',
        ]);

        $source->update($validated);

        return response()->json([
            'success' => true,
            'data' => $source,
            'message' => 'Source updated successfully',
        ]);
    }

    /**
     * Delete trend source
     */
    public function deleteSource(TrendSource $source)
    {
        $source->delete();

        return response()->json([
            'success' => true,
            'message' => 'Source deleted successfully',
        ]);
    }

    /**
     * Get analytics for time range
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'interval' => 'in:hour,day,week',
        ]);

        $interval = $validated['interval'] ?? 'day';

        // This would need more sophisticated aggregation
        // For now, return basic stats

        $stats = [
            'total_trends' => ViralTrend::whereBetween('started_at', [
                $validated['start_date'],
                $validated['end_date']
            ])->count(),
            'total_keywords' => TrendKeyword::whereBetween('first_seen_at', [
                $validated['start_date'],
                $validated['end_date']
            ])->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
