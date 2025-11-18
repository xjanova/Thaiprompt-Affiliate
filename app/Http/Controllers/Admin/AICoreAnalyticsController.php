<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreFeature;
use App\Models\AICoreTenant;
use App\Models\AICoreUsageLog;
use App\Models\AICoreQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AI Core Analytics Controller
 *
 * จัดการการวิเคราะห์และรายงานการใช้งาน AI Core
 */
class AICoreAnalyticsController extends Controller
{
    /**
     * แสดงหน้า Analytics Dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // กำหนดช่วงเวลา (default: 30 วันล่าสุด)
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // สถิติรวม
        $stats = $this->getOverallStats($startDate, $endDate);

        // กราฟการใช้งานรายวัน
        $usageChart = $this->getDailyUsageChart($startDate, $endDate);

        // Features ที่ได้รับความนิยมสูงสุด
        $topFeatures = $this->getTopFeatures($startDate, $endDate, 10);

        // Tenants ที่ใช้งานมากที่สุด
        $topTenants = $this->getTopTenants($startDate, $endDate, 10);

        // อัตราความสำเร็จ
        $successRateChart = $this->getSuccessRateChart($startDate, $endDate);

        // การใช้งานตามหมวดหมู่
        $categoryUsage = $this->getCategoryUsage($startDate, $endDate);

        // Quota utilization
        $quotaUtilization = $this->getQuotaUtilization();

        return view('admin.ai-core.analytics.index', compact(
            'stats',
            'usageChart',
            'topFeatures',
            'topTenants',
            'successRateChart',
            'categoryUsage',
            'quotaUtilization',
            'startDate',
            'endDate'
        ));
    }

    /**
     * แสดง Analytics สำหรับ Feature เฉพาะ
     *
     * @param Request $request
     * @param AICoreFeature $feature
     * @return \Illuminate\View\View
     */
    public function feature(Request $request, AICoreFeature $feature)
    {
        // กำหนดช่วงเวลา
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // สถิติสำหรับ Feature นี้
        $stats = [
            'total_usage' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'success_count' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->success()
                ->count(),
            'failed_count' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->failed()
                ->count(),
            'unique_users' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')
                ->count('user_id'),
            'unique_tenants' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('tenant_id')
                ->count('tenant_id'),
            'avg_response_time' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('response_time')
                ->avg('response_time'),
            'total_tokens' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('tokens_used'),
            'total_cost' => AICoreUsageLog::where('feature_id', $feature->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('cost'),
        ];

        // กราฟการใช้งานรายวัน
        $usageChart = $this->getFeatureDailyUsage($feature->id, $startDate, $endDate);

        // Tenants ที่ใช้งาน Feature นี้มากที่สุด
        $topTenants = AICoreUsageLog::select('tenant_id', DB::raw('COUNT(*) as usage_count'))
            ->where('feature_id', $feature->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('tenant')
            ->groupBy('tenant_id')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        // การใช้งานแบ่งตามช่วงเวลา (ชั่วโมง)
        $hourlyUsage = $this->getHourlyUsage($feature->id, $startDate, $endDate);

        return view('admin.ai-core.analytics.feature', compact(
            'feature',
            'stats',
            'usageChart',
            'topTenants',
            'hourlyUsage',
            'startDate',
            'endDate'
        ));
    }

    /**
     * แสดง Analytics สำหรับ Tenant เฉพาะ
     *
     * @param Request $request
     * @param AICoreTenant $tenant
     * @return \Illuminate\View\View
     */
    public function tenant(Request $request, AICoreTenant $tenant)
    {
        // กำหนดช่วงเวลา
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // สถิติสำหรับ Tenant นี้
        $stats = [
            'total_usage' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'success_count' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->success()
                ->count(),
            'failed_count' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->failed()
                ->count(),
            'features_used' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('feature_id')
                ->count('feature_id'),
            'avg_response_time' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('response_time')
                ->avg('response_time'),
            'total_tokens' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('tokens_used'),
            'total_cost' => AICoreUsageLog::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('cost'),
        ];

        // กราฟการใช้งานรายวัน
        $usageChart = $this->getTenantDailyUsage($tenant->id, $startDate, $endDate);

        // Features ที่ Tenant นี้ใช้มากที่สุด
        $topFeatures = AICoreUsageLog::select('feature_id', DB::raw('COUNT(*) as usage_count'))
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('feature')
            ->groupBy('feature_id')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        // สถานะ Quota
        $quotas = AICoreQuota::where('tenant_id', $tenant->id)
            ->with('feature')
            ->get()
            ->map(function ($quota) {
                return [
                    'feature' => $quota->feature,
                    'quota_limit' => $quota->quota_limit,
                    'quota_used' => $quota->quota_used,
                    'quota_remaining' => $quota->quota_remaining,
                    'percentage_used' => $quota->quota_limit > 0
                        ? round(($quota->quota_used / $quota->quota_limit) * 100, 2)
                        : 0,
                ];
            });

        return view('admin.ai-core.analytics.tenant', compact(
            'tenant',
            'stats',
            'usageChart',
            'topFeatures',
            'quotas',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Analytics Data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:overall,feature,tenant',
            'feature_id' => 'required_if:type,feature|exists:ai_core_features,id',
            'tenant_id' => 'required_if:type,tenant|exists:ai_core_tenants,id',
            'format' => 'required|in:csv,xlsx',
        ]);

        // TODO: Implement export logic
        // จะต้องใช้ package เช่น maatwebsite/excel

        return back()->with('info', 'ฟีเจอร์ Export กำลังพัฒนา');
    }

    /**
     * ดึงสถิติรวมทั้งหมด
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getOverallStats(string $startDate, string $endDate): array
    {
        $totalUsage = AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])->count();
        $successCount = AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
            ->success()->count();

        return [
            'total_usage' => $totalUsage,
            'success_count' => $successCount,
            'failed_count' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->failed()->count(),
            'success_rate' => $totalUsage > 0 ? round(($successCount / $totalUsage) * 100, 2) : 0,
            'unique_users' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')->count('user_id'),
            'unique_tenants' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->distinct('tenant_id')->count('tenant_id'),
            'total_tokens' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->sum('tokens_used'),
            'total_cost' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->sum('cost'),
            'avg_response_time' => AICoreUsageLog::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('response_time')
                ->avg('response_time'),
        ];
    }

    /**
     * ดึงข้อมูลกราฟการใช้งานรายวัน
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getDailyUsageChart(string $startDate, string $endDate): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => 'ทั้งหมด',
                    'data' => $data->pluck('total')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'สำเร็จ',
                    'data' => $data->pluck('success')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'ล้มเหลว',
                    'data' => $data->pluck('failed')->toArray(),
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
        ];
    }

    /**
     * ดึง Features ที่ได้รับความนิยมสูงสุด
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function getTopFeatures(string $startDate, string $endDate, int $limit = 10)
    {
        return AICoreUsageLog::select('feature_id', DB::raw('COUNT(*) as usage_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('feature')
            ->groupBy('feature_id')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึง Tenants ที่ใช้งานมากที่สุด
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function getTopTenants(string $startDate, string $endDate, int $limit = 10)
    {
        return AICoreUsageLog::select('tenant_id', DB::raw('COUNT(*) as usage_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('tenant_id')
            ->with('tenant')
            ->groupBy('tenant_id')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงข้อมูลอัตราความสำเร็จ
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getSuccessRateChart(string $startDate, string $endDate): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'rate' => $item->total > 0 ? round(($item->success / $item->total) * 100, 2) : 0,
                ];
            });

        return [
            'labels' => $data->pluck('date')->toArray(),
            'data' => $data->pluck('rate')->toArray(),
        ];
    }

    /**
     * ดึงการใช้งานแบ่งตามหมวดหมู่
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getCategoryUsage(string $startDate, string $endDate)
    {
        return AICoreUsageLog::join('ai_core_features', 'ai_core_usage_logs.feature_id', '=', 'ai_core_features.id')
            ->select('ai_core_features.category', DB::raw('COUNT(*) as usage_count'))
            ->whereBetween('ai_core_usage_logs.created_at', [$startDate, $endDate])
            ->groupBy('ai_core_features.category')
            ->orderBy('usage_count', 'desc')
            ->get();
    }

    /**
     * ดึงข้อมูล Quota Utilization
     *
     * @return \Illuminate\Support\Collection
     */
    private function getQuotaUtilization()
    {
        return AICoreQuota::with(['tenant', 'feature'])
            ->where('status', 'active')
            ->get()
            ->map(function ($quota) {
                return [
                    'tenant' => $quota->tenant,
                    'feature' => $quota->feature,
                    'quota_limit' => $quota->quota_limit,
                    'quota_used' => $quota->quota_used,
                    'percentage_used' => $quota->quota_limit > 0
                        ? round(($quota->quota_used / $quota->quota_limit) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('percentage_used')
            ->take(10);
    }

    /**
     * ดึงการใช้งานรายวันสำหรับ Feature
     *
     * @param int $featureId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getFeatureDailyUsage(int $featureId, string $startDate, string $endDate): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->where('feature_id', $featureId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->toArray(),
            'data' => $data->pluck('total')->toArray(),
        ];
    }

    /**
     * ดึงการใช้งานรายวันสำหรับ Tenant
     *
     * @param int $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getTenantDailyUsage(int $tenantId, string $startDate, string $endDate): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->toArray(),
            'data' => $data->pluck('total')->toArray(),
        ];
    }

    /**
     * ดึงการใช้งานแบ่งตามชั่วโมง
     *
     * @param int $featureId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getHourlyUsage(int $featureId, string $startDate, string $endDate): array
    {
        $data = AICoreUsageLog::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as total')
        )
            ->where('feature_id', $featureId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // สร้าง labels 0-23 ชั่วโมง
        $labels = range(0, 23);
        $usageByHour = array_fill(0, 24, 0);

        foreach ($data as $item) {
            $usageByHour[$item->hour] = $item->total;
        }

        return [
            'labels' => array_map(fn($h) => sprintf('%02d:00', $h), $labels),
            'data' => $usageByHour,
        ];
    }
}
