<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LineSignupAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * LINE Analytics Dashboard Controller
 *
 * Provides analytics endpoints for admin and sponsors
 */
class LineAnalyticsController extends Controller
{
    public function __construct(
        private LineSignupAnalyticsService $analytics
    ) {}

    /**
     * Show analytics dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');

        $data = $this->analytics->getDashboardData($period);

        return view('admin.line-analytics.index', compact('data', 'period'));
    }

    /**
     * Get dashboard data (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');

        $data = $this->analytics->getDashboardData($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get overall statistics (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOverallStats(Request $request): JsonResponse
    {
        $period = $request->get('period', 'all');

        $stats = $this->analytics->getOverallStats($period);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get conversion funnel (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversionFunnel(Request $request): JsonResponse
    {
        $period = $request->get('period', 'all');

        $funnel = $this->analytics->getConversionFunnel($period);

        return response()->json([
            'success' => true,
            'data' => $funnel,
        ]);
    }

    /**
     * Get dropout analysis (API)
     *
     * @return JsonResponse
     */
    public function getDropoutAnalysis(): JsonResponse
    {
        $dropout = $this->analytics->getDropoutAnalysis();

        return response()->json([
            'success' => true,
            'data' => $dropout,
        ]);
    }

    /**
     * Get signup trends (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSignupTrends(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $groupBy = $request->get('group_by', 'day');

        $trends = $this->analytics->getSignupTrends($period, $groupBy);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get sponsor leaderboard (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $limit = $request->get('limit', 10);

        $leaderboard = $this->analytics->getSponsorLeaderboard($period, $limit);

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    /**
     * Get active conversations (API)
     *
     * @return JsonResponse
     */
    public function getActiveConversations(): JsonResponse
    {
        $active = $this->analytics->getActiveConversations();

        return response()->json([
            'success' => true,
            'data' => $active,
        ]);
    }

    /**
     * Get sponsor-specific analytics
     *
     * @param Request $request
     * @param int $sponsorId
     * @return JsonResponse
     */
    public function getSponsorAnalytics(Request $request, int $sponsorId): JsonResponse
    {
        // Check authorization - sponsor can only view their own analytics
        $user = $request->user();

        if (!$user->isSuperAdmin() && $user->id !== $sponsorId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $period = $request->get('period', 'month');

        $data = $this->analytics->getSponsorAnalytics($sponsorId, $period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Clear analytics cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $this->analytics->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully',
        ]);
    }

    /**
     * Export analytics data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        $format = $request->get('format', 'json');

        $data = $this->analytics->getDashboardData($period);

        if ($format === 'csv') {
            return $this->exportCsv($data);
        }

        // Default: JSON
        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="line-analytics-' . now()->format('Y-m-d') . '.json"');
    }

    /**
     * Export data as CSV
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    private function exportCsv(array $data)
    {
        $csv = fopen('php://temp', 'r+');

        // Header
        fputcsv($csv, ['Metric', 'Value']);

        // Overview stats
        foreach ($data['overview'] as $key => $value) {
            fputcsv($csv, [ucwords(str_replace('_', ' ', $key)), $value]);
        }

        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);

        return response($output)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="line-analytics-' . now()->format('Y-m-d') . '.csv"');
    }
}
