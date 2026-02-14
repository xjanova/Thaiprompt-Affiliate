<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Page View Analytics Controller
 *
 * จัดการหน้า Analytics สำหรับวิเคราะห์สถิติการเข้าชมเว็บ
 * รวมถึง SEO, Traffic Sources, User Behavior
 */
class PageViewAnalyticsController extends Controller
{
    /**
     * แสดงหน้า Dashboard Analytics หลัก
     */
    public function index(Request $request): View
    {
        $period = $request->get('period', '7days');

        // สถิติภาพรวม
        $summary = PageView::getSummary($period);

        // หน้าที่มีคนเข้าชมมากที่สุด
        $topPages = PageView::getTopPages(10, $period);

        // แหล่งที่มาของ traffic
        $topReferrers = PageView::getTopReferrers(10, $period);

        // สถิติตาม device
        $deviceStats = PageView::getDeviceStats($period);

        // สถิติตาม browser
        $browserStats = PageView::getBrowserStats(10, $period);

        // สถิติรายวัน
        $dailyStats = PageView::getDailyStats(30);

        return view('admin.analytics.page-views.index', compact(
            'period',
            'summary',
            'topPages',
            'topReferrers',
            'deviceStats',
            'browserStats',
            'dailyStats'
        ));
    }

    /**
     * แสดงหน้า Real-time Analytics
     */
    public function realtime(): View
    {
        return view('admin.analytics.page-views.realtime');
    }

    /**
     * ดึงข้อมูล Real-time (AJAX)
     */
    public function realtimeData(): JsonResponse
    {
        // ผู้ใช้ที่ active ใน 5 นาทีล่าสุด
        $activeNow = PageView::human()
            ->where('viewed_at', '>=', now()->subMinutes(5))
            ->distinct('session_id')
            ->count('session_id');

        // Page views ใน 1 ชั่วโมงล่าสุด (รายนาที)
        $lastHour = PageView::human()
            ->where('viewed_at', '>=', now()->subHour())
            ->select(
                DB::raw('MINUTE(viewed_at) as minute'),
                DB::raw('COUNT(*) as views')
            )
            ->groupBy('minute')
            ->orderBy('minute')
            ->get();

        // หน้าที่กำลังดูตอนนี้
        $currentPages = PageView::human()
            ->where('viewed_at', '>=', now()->subMinutes(5))
            ->select('path', DB::raw('COUNT(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'active_now' => $activeNow,
                'last_hour' => $lastHour,
                'current_pages' => $currentPages,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * แสดงหน้ารายละเอียดหน้าที่เข้าชม
     */
    public function pages(Request $request): View
    {
        $period = $request->get('period', '7days');

        $pages = PageView::human()
            ->period($period)
            ->select(
                'path',
                'route_name',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as unique_visitors'),
                DB::raw('AVG(response_time_ms) as avg_response_time')
            )
            ->groupBy('path', 'route_name')
            ->orderByDesc('views')
            ->paginate(50);

        return view('admin.analytics.page-views.pages', compact('period', 'pages'));
    }

    /**
     * แสดงหน้า Traffic Sources
     */
    public function sources(Request $request): View
    {
        $period = $request->get('period', '7days');

        // Referrer domains
        $referrers = PageView::getTopReferrers(50, $period);

        // UTM campaigns
        $campaigns = PageView::getUtmStats($period);

        // Direct vs Referral
        $directVsReferral = [
            'direct' => PageView::human()
                ->period($period)
                ->whereNull('referrer_domain')
                ->count(),
            'referral' => PageView::human()
                ->period($period)
                ->whereNotNull('referrer_domain')
                ->count(),
        ];

        return view('admin.analytics.page-views.sources', compact(
            'period',
            'referrers',
            'campaigns',
            'directVsReferral'
        ));
    }

    /**
     * แสดงหน้า Device & Browser Stats
     */
    public function devices(Request $request): View
    {
        $period = $request->get('period', '7days');

        $deviceStats = PageView::getDeviceStats($period);
        $browserStats = PageView::getBrowserStats(20, $period);

        // Platform stats
        $platformStats = PageView::human()
            ->period($period)
            ->whereNotNull('platform')
            ->select('platform', DB::raw('COUNT(*) as views'))
            ->groupBy('platform')
            ->orderByDesc('views')
            ->get();

        return view('admin.analytics.page-views.devices', compact(
            'period',
            'deviceStats',
            'browserStats',
            'platformStats'
        ));
    }

    /**
     * แสดงหน้า Geographic Stats
     */
    public function geography(Request $request): View
    {
        $period = $request->get('period', '7days');

        $countryStats = PageView::getCountryStats(50, $period);

        return view('admin.analytics.page-views.geography', compact('period', 'countryStats'));
    }

    /**
     * Export ข้อมูลเป็น CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $period = $request->get('period', '7days');

        $pages = PageView::human()
            ->period($period)
            ->select(
                'path',
                'route_name',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as unique_visitors'),
                DB::raw('AVG(response_time_ms) as avg_response_time')
            )
            ->groupBy('path', 'route_name')
            ->orderByDesc('views')
            ->get();

        $filename = 'page-views-'.$period.'-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($pages) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Path',
                'Route Name',
                'Total Views',
                'Unique Visitors',
                'Avg Response Time (ms)',
            ]);

            // Data rows
            foreach ($pages as $page) {
                fputcsv($file, [
                    $page->path,
                    $page->route_name ?? '-',
                    $page->views,
                    $page->unique_visitors,
                    round($page->avg_response_time, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: ดึงสถิติสำหรับ Charts
     */
    public function chartData(Request $request): JsonResponse
    {
        $period = $request->get('period', '7days');
        $type = $request->get('type', 'daily');

        $data = match ($type) {
            'daily' => PageView::getDailyStats(30),
            'hourly' => PageView::getHourlyStats(),
            'devices' => PageView::getDeviceStats($period),
            'browsers' => PageView::getBrowserStats(10, $period),
            'countries' => PageView::getCountryStats(10, $period),
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
