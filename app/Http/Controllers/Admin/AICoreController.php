<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreFeature;
use App\Models\AICoreTenant;
use App\Models\AICoreUsageLog;
use App\Models\AICoreAlert;
use App\Services\AICoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AI Core Dashboard Controller
 *
 * จัดการหน้า Dashboard หลักของ AI Core
 * แสดงภาพรวมทั้งหมดของระบบ AI
 */
class AICoreController extends Controller
{
    /**
     * @var AICoreService
     */
    protected $aiCoreService;

    /**
     * Constructor
     *
     * @param AICoreService $aiCoreService
     */
    public function __construct(AICoreService $aiCoreService)
    {
        $this->aiCoreService = $aiCoreService;
    }

    /**
     * แสดงหน้า AI Core Dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // ดึงสถิติรวม
        $stats = $this->getDashboardStats();

        // ดึงการใช้งานล่าสุด
        $recentUsage = AICoreUsageLog::with(['feature', 'tenant', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // ดึง alerts ที่ยังไม่อ่าน
        $alerts = AICoreAlert::unread()
            ->with(['feature', 'tenant'])
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ดึงข้อมูลการใช้งานรายวัน (7 วันล่าสุด)
        $usageChart = $this->getUsageChartData();

        // ดึง Features ที่ใช้งานมากที่สุด
        $topFeatures = $this->getTopFeatures();

        return view('admin.ai-core.dashboard', compact(
            'stats',
            'recentUsage',
            'alerts',
            'usageChart',
            'topFeatures'
        ));
    }

    /**
     * ดึงสถิติรวมสำหรับ Dashboard
     *
     * @return array
     */
    private function getDashboardStats(): array
    {
        return [
            'total_features' => AICoreFeature::count(),
            'enabled_features' => AICoreFeature::where('is_enabled', true)->count(),
            'total_tenants' => AICoreTenant::count(),
            'active_tenants' => AICoreTenant::where('status', 'active')->where('is_enabled', true)->count(),
            'total_usage_today' => AICoreUsageLog::whereDate('created_at', today())->count(),
            'total_usage_month' => AICoreUsageLog::whereMonth('created_at', now()->month)->count(),
            'unread_alerts' => AICoreAlert::unread()->count(),
            'critical_alerts' => AICoreAlert::unread()->where('severity', 'critical')->count(),
        ];
    }

    /**
     * ดึงข้อมูลสำหรับ Usage Chart (7 วันล่าสุด)
     *
     * @return array
     */
    private function getUsageChartData(): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $totalData = [];
        $successData = [];
        $failedData = [];

        foreach ($data as $item) {
            $labels[] = $item->date;
            $totalData[] = $item->total;
            $successData[] = $item->success;
            $failedData[] = $item->failed;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'ทั้งหมด',
                    'data' => $totalData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'สำเร็จ',
                    'data' => $successData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'ล้มเหลว',
                    'data' => $failedData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
        ];
    }

    /**
     * ดึง Features ที่ใช้งานมากที่สุด (Top 5)
     *
     * @return \Illuminate\Support\Collection
     */
    private function getTopFeatures()
    {
        return AICoreUsageLog::select('feature_id', DB::raw('COUNT(*) as usage_count'))
            ->with('feature')
            ->groupBy('feature_id')
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'feature' => $item->feature,
                    'usage_count' => $item->usage_count,
                ];
            });
    }

    /**
     * แสดงหน้าตั้งค่า Global Settings
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        return view('admin.ai-core.settings');
    }

    /**
     * บันทึกการตั้งค่า
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enable_global_quota' => 'boolean',
            'enable_auto_reset' => 'boolean',
            'enable_alerts' => 'boolean',
            'alert_channels' => 'array',
        ]);

        // TODO: บันทึกการตั้งค่าลง AICoreSetting

        return redirect()->route('admin.ai-core.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
