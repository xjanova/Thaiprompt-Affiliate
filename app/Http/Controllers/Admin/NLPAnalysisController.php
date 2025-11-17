<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageEntity;
use App\Models\MessageIntent;
use App\Models\KeywordCluster;
use App\Models\KeywordClusterItem;
use App\Services\NLPEnhancementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * NLP Analysis Controller
 *
 * จัดการ entity extraction, intent recognition, และ keyword clustering
 */
class NLPAnalysisController extends Controller
{
    /**
     * @var NLPEnhancementService
     */
    protected NLPEnhancementService $nlpService;

    /**
     * Constructor
     *
     * @param NLPEnhancementService $nlpService
     */
    public function __construct(NLPEnhancementService $nlpService)
    {
        $this->nlpService = $nlpService;
    }

    /**
     * Display NLP analysis dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Statistics
        $totalEntities = MessageEntity::count();
        $totalIntents = MessageIntent::count();
        $totalClusters = KeywordCluster::active()->count();

        // Entity types distribution
        $entityDistribution = MessageEntity::select('entity_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('entity_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Top intents
        $topIntents = MessageIntent::select('primary_intent')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('primary_intent')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Cluster statistics
        $topClusters = KeywordCluster::active()
            ->orderBy('total_matches', 'desc')
            ->limit(10)
            ->get();

        // High confidence entities
        $highConfidenceEntities = MessageEntity::highConfidence()
            ->latest()
            ->limit(20)
            ->get();

        // Urgent intents
        $urgentIntents = MessageIntent::urgent()
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.line-bot.keywords.nlp-analysis.index', [
            'pageTitle' => 'การวิเคราะห์ NLP',
            'totalEntities' => $totalEntities,
            'totalIntents' => $totalIntents,
            'totalClusters' => $totalClusters,
            'entityDistribution' => $entityDistribution,
            'topIntents' => $topIntents,
            'topClusters' => $topClusters,
            'highConfidenceEntities' => $highConfidenceEntities,
            'urgentIntents' => $urgentIntents,
        ]);
    }

    /**
     * Display entities list
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function entities(Request $request)
    {
        $query = MessageEntity::with('sentiment', 'keyword')
            ->latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('entity_type', $request->get('type'));
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('entity_source', $request->get('source'));
        }

        // Filter by confidence
        if ($request->filled('min_confidence')) {
            $query->where('confidence', '>=', (float) $request->get('min_confidence'));
        }

        $entities = $query->paginate(25);

        // Entity types for dropdown
        $entityTypes = MessageEntity::distinct('entity_type')
            ->pluck('entity_type')
            ->toArray();

        return view('admin.line-bot.keywords.nlp-analysis.entities', [
            'pageTitle' => 'Entities',
            'entities' => $entities,
            'entityTypes' => $entityTypes,
        ]);
    }

    /**
     * Display intents list
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function intents(Request $request)
    {
        $query = MessageIntent::with('sentiment', 'keyword')
            ->latest();

        // Filter by type
        if ($request->filled('intent')) {
            $query->where('primary_intent', $request->get('intent'));
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('suggested_department', $request->get('department'));
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority_level', $request->get('priority'));
        }

        // Only urgent
        if ($request->boolean('urgent')) {
            $query->urgent();
        }

        $intents = $query->paginate(25);

        // Intent types for dropdown
        $intentTypes = MessageIntent::distinct('primary_intent')
            ->pluck('primary_intent')
            ->toArray();

        // Departments
        $departments = ['SALES', 'SUPPORT', 'BILLING', 'DELIVERY', 'QUALITY', 'ACCOUNT', 'OTHER'];

        return view('admin.line-bot.keywords.nlp-analysis.intents', [
            'pageTitle' => 'Intents',
            'intents' => $intents,
            'intentTypes' => $intentTypes,
            'departments' => $departments,
        ]);
    }

    /**
     * Display clusters management
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function clusters(Request $request)
    {
        $query = KeywordCluster::with('keywords')
            ->withCount('keywords as actual_keyword_count');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('cluster_category', $request->get('category'));
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->active();
            } elseif ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sort by popularity
        if ($request->get('sort') === 'popularity') {
            $query->orderBy('total_matches', 'desc');
        } else {
            $query->latest();
        }

        $clusters = $query->paginate(25);

        $categories = [
            'PRODUCT', 'SERVICE', 'ISSUE', 'FEATURE', 'FEEDBACK',
            'PROCESS', 'TECHNICAL', 'BUSINESS', 'OTHER'
        ];

        return view('admin.line-bot.keywords.nlp-analysis.clusters', [
            'pageTitle' => 'Keyword Clusters',
            'clusters' => $clusters,
            'categories' => $categories,
        ]);
    }

    /**
     * Show cluster detail
     *
     * @param KeywordCluster $cluster
     * @return \Illuminate\View\View
     */
    public function showCluster(KeywordCluster $cluster)
    {
        $cluster->load('keywords', 'items');

        // Keywords in cluster with pivot data
        $clusterItems = KeywordClusterItem::where('keyword_cluster_id', $cluster->id)
            ->with('keyword')
            ->orderBy('relevance_score', 'desc')
            ->get();

        return view('admin.line-bot.keywords.nlp-analysis.cluster-detail', [
            'pageTitle' => 'Cluster: ' . $cluster->display_name,
            'cluster' => $cluster,
            'clusterItems' => $clusterItems,
        ]);
    }

    /**
     * Create new cluster
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createCluster(Request $request)
    {
        $validated = $request->validate([
            'cluster_name' => 'required|string|unique:keyword_clusters',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'cluster_category' => 'required|in:PRODUCT,SERVICE,ISSUE,FEATURE,FEEDBACK,PROCESS,TECHNICAL,BUSINESS,OTHER',
            'icon' => 'nullable|string',
            'keywords' => 'nullable|array',
            'keywords.*' => 'integer|exists:line_bot_keywords,id',
        ]);

        $cluster = $this->nlpService->createCluster($validated);

        return redirect()
            ->route('admin.line-bot.keywords.nlp-analysis.show-cluster', $cluster)
            ->with('success', 'สร้าง cluster สำเร็จ');
    }

    /**
     * Update cluster
     *
     * @param Request $request
     * @param KeywordCluster $cluster
     * @return \Illuminate\Http\Response
     */
    public function updateCluster(Request $request, KeywordCluster $cluster)
    {
        $validated = $request->validate([
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'cluster_category' => 'required|in:PRODUCT,SERVICE,ISSUE,FEATURE,FEEDBACK,PROCESS,TECHNICAL,BUSINESS,OTHER',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $cluster->update($validated);

        return redirect()->back()->with('success', 'อัพเดท cluster สำเร็จ');
    }

    /**
     * Delete cluster
     *
     * @param KeywordCluster $cluster
     * @return JsonResponse
     */
    public function deleteCluster(KeywordCluster $cluster): JsonResponse
    {
        $cluster->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ cluster สำเร็จ',
        ]);
    }

    /**
     * Get entity statistics as JSON
     *
     * @return JsonResponse
     */
    public function entityStatistics(): JsonResponse
    {
        $statistics = MessageEntity::select('entity_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(confidence) as avg_confidence')
            ->groupBy('entity_type')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get intent statistics as JSON
     *
     * @return JsonResponse
     */
    public function intentStatistics(): JsonResponse
    {
        $statistics = MessageIntent::select('primary_intent')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(primary_confidence) as avg_confidence')
            ->selectRaw('SUM(CASE WHEN priority_level = "URGENT" THEN 1 ELSE 0 END) as urgent_count')
            ->groupBy('primary_intent')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get cluster usage data for chart
     *
     * @return JsonResponse
     */
    public function clusterUsageData(): JsonResponse
    {
        $data = $this->nlpService->analyzeClusterUsage();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get cluster recommendations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clusterRecommendations(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $recommendations = $this->nlpService->getClusterRecommendations($days);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Get related keywords for entity
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function relatedKeywords(Request $request): JsonResponse
    {
        $entityValue = $request->get('entity');

        if (!$entityValue) {
            return response()->json([
                'success' => false,
                'message' => 'Entity value required',
            ], 400);
        }

        // Find keywords matching entity
        $keywords = MessageEntity::where('entity_value', $entityValue)
            ->with('keyword')
            ->distinct()
            ->pluck('keyword')
            ->unique()
            ->take(10);

        return response()->json([
            'success' => true,
            'data' => $keywords,
        ]);
    }

    /**
     * Get entity co-occurrence data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function entityCoOccurrence(Request $request): JsonResponse
    {
        $days = $request->integer('days', 7);

        // Find co-occurring entities
        $query = MessageEntity::whereDate('created_at', '>=', now()->subDays($days))
            ->select('entity_type', 'entity_value')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('entity_type', 'entity_value')
            ->orderBy('count', 'desc')
            ->limit(20);

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export NLP analysis report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        $report = [
            'generated_at' => now(),
            'period' => "${days} วันที่ผ่านมา",
            'entities' => [
                'total' => MessageEntity::whereDate('created_at', '>=', now()->subDays($days))->count(),
                'by_type' => MessageEntity::whereDate('created_at', '>=', now()->subDays($days))
                    ->select('entity_type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('entity_type')
                    ->pluck('count', 'entity_type'),
            ],
            'intents' => [
                'total' => MessageIntent::whereDate('created_at', '>=', now()->subDays($days))->count(),
                'by_type' => MessageIntent::whereDate('created_at', '>=', now()->subDays($days))
                    ->select('primary_intent')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('primary_intent')
                    ->pluck('count', 'primary_intent'),
                'urgent_count' => MessageIntent::whereDate('created_at', '>=', now()->subDays($days))
                    ->where('priority_level', 'URGENT')
                    ->count(),
            ],
            'clusters' => [
                'total_active' => KeywordCluster::active()->count(),
                'total_keywords' => KeywordCluster::sum('keyword_count'),
                'most_used' => KeywordCluster::active()
                    ->orderBy('total_matches', 'desc')
                    ->limit(5)
                    ->pluck('display_name', 'cluster_name'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Delete entity
     *
     * @param MessageEntity $entity
     * @return JsonResponse
     */
    public function deleteEntity(MessageEntity $entity): JsonResponse
    {
        $entity->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ entity สำเร็จ',
        ]);
    }

    /**
     * Delete intent
     *
     * @param MessageIntent $intent
     * @return JsonResponse
     */
    public function deleteIntent(MessageIntent $intent): JsonResponse
    {
        $intent->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ intent สำเร็จ',
        ]);
    }
}
