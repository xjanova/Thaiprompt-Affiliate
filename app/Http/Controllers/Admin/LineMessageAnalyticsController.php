<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LineMessageAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LINE Message Analytics Controller
 *
 * จัดการ dashboard และ API สำหรับ LINE message analytics
 *
 * Routes:
 * - GET /admin/line-analytics/dashboard - Dashboard หลัก
 * - GET /admin/line-analytics/api/overview - API: Overview stats
 * - GET /admin/line-analytics/api/trending - API: Trending data
 * - GET /admin/line-analytics/api/errors - API: Error analysis
 */
class LineMessageAnalyticsController extends Controller
{
    /**
     * Analytics Service
     */
    protected LineMessageAnalyticsService $analyticsService;

    /**
     * Constructor
     */
    public function __construct(LineMessageAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * แสดง dashboard หลัก
     *
     * @return \Illuminate\View\View
     */
    public function dashboard(Request $request)
    {
        // ดึง period จาก request (default: week)
        $period = $request->get('period', 'week');

        // Validate period
        if (! in_array($period, ['today', 'week', 'month', 'all'])) {
            $period = 'week';
        }

        // ดึงข้อมูลสำหรับ dashboard
        $data = $this->analyticsService->getDashboardSummary($period);

        return view('admin.line-analytics.dashboard', [
            'pageTitle' => 'LINE Message Analytics',
            'period' => $period,
            'data' => $data,
        ]);
    }

    /**
     * API: ดึงสถิติภาพรวม
     */
    public function apiOverview(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');

        $data = $this->analyticsService->getOverviewStatistics($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: ดึงข้อมูล trending
     */
    public function apiTrending(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $interval = $request->get('interval', 'day');

        // Validate interval
        if (! in_array($interval, ['hour', 'day'])) {
            $interval = 'day';
        }

        $data = $this->analyticsService->getTrendingData($period, $interval);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: วิเคราะห์ error patterns
     */
    public function apiErrors(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');

        $errorPatterns = $this->analyticsService->getErrorPatternAnalysis($period);
        $errorSeverity = $this->analyticsService->getErrorSeverityBreakdown($period);

        return response()->json([
            'success' => true,
            'data' => [
                'patterns' => $errorPatterns,
                'severity' => $errorSeverity,
            ],
        ]);
    }

    /**
     * API: ดึง recovery metrics
     */
    public function apiRecovery(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');

        $data = $this->analyticsService->getRecoveryMetrics($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: ดึงสถิติ message types
     */
    public function apiMessageTypes(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');

        $data = $this->analyticsService->getMessageTypeStatistics($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: ดึงสถิติ user engagement
     */
    public function apiUserEngagement(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');

        $data = $this->analyticsService->getUserEngagementMetrics($period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * ล้าง cache analytics
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->analyticsService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'ล้าง cache สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ล้าง cache ล้มเหลว: '.$e->getMessage(),
            ], 500);
        }
    }
}
