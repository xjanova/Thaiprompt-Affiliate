<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeDreamDictionary;
use App\Models\HoroscopeDreamReading;
use App\Models\HoroscopeNumerologyReading;
use App\Models\HoroscopeZodiacSign;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HoroscopeAnalyticsController — สถิติระบบดูดวงสาธารณะ
 *
 * แสดง:
 * - ภาพรวมสถิติทุกหมวด (daily, dream, numerology)
 * - กราฟ 7 วัน / 30 วัน
 * - สัญลักษณ์ฝันยอดนิยม
 * - ราศีที่คนดูมากที่สุด
 */
class HoroscopeAnalyticsController extends Controller
{
    /**
     * หน้า Analytics หลัก
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $today = Carbon::today();
        $weekAgo = Carbon::now()->subDays(7);
        $monthAgo = Carbon::now()->subDays(30);

        // === สถิติภาพรวม ===

        // ดวงรายวัน
        $dailyStats = [
            'total_predictions' => HoroscopeDailyPrediction::count(),
            'generated_today' => HoroscopeDailyPrediction::where('target_date', $today)->count(),
            'total_views' => HoroscopeDailyPrediction::sum('view_count'),
            'zodiacs_active' => HoroscopeZodiacSign::where('is_active', true)->count(),
        ];

        // ทำนายฝัน
        $dreamStats = [
            'total_readings' => HoroscopeDreamReading::count(),
            'today_readings' => HoroscopeDreamReading::whereDate('created_at', $today)->count(),
            'week_readings' => HoroscopeDreamReading::where('created_at', '>=', $weekAgo)->count(),
            'total_views' => HoroscopeDreamReading::sum('view_count'),
            'total_symbols' => HoroscopeDreamDictionary::where('is_active', true)->count(),
        ];

        // เลขศาสตร์
        $numerologyStats = [
            'total_readings' => HoroscopeNumerologyReading::count(),
            'today_readings' => HoroscopeNumerologyReading::whereDate('created_at', $today)->count(),
            'week_readings' => HoroscopeNumerologyReading::where('created_at', '>=', $weekAgo)->count(),
            'total_views' => HoroscopeNumerologyReading::sum('view_count'),
        ];

        // === Top Items ===

        // ราศียอดนิยม (วิวมากสุด 7 วัน)
        $topZodiacs = HoroscopeDailyPrediction::select('zodiac_sign_id', DB::raw('SUM(view_count) as total_views'))
            ->where('target_date', '>=', $weekAgo)
            ->where('prediction_type', 'zodiac')
            ->groupBy('zodiac_sign_id')
            ->orderByDesc('total_views')
            ->limit(5)
            ->with('zodiacSign')
            ->get();

        // สัญลักษณ์ฝันยอดนิยม (ค้นหามากสุด)
        $topDreamSymbols = HoroscopeDreamDictionary::where('is_active', true)
            ->orderByDesc('search_count')
            ->limit(10)
            ->with('category')
            ->get();

        // ประเภทเลขศาสตร์ที่ใช้มากสุด
        $numerologyTypes = HoroscopeNumerologyReading::select('reading_type', DB::raw('COUNT(*) as count'))
            ->groupBy('reading_type')
            ->orderByDesc('count')
            ->get();

        // === กราฟ 7 วัน ===
        $chartData = $this->buildChartData($weekAgo, $today);

        return view('admin.fortune.horoscope-public.analytics', [
            'pageTitle' => '📊 สถิติระบบดูดวงสาธารณะ',
            'dailyStats' => $dailyStats,
            'dreamStats' => $dreamStats,
            'numerologyStats' => $numerologyStats,
            'topZodiacs' => $topZodiacs,
            'topDreamSymbols' => $topDreamSymbols,
            'numerologyTypes' => $numerologyTypes,
            'chartData' => $chartData,
        ]);
    }

    /**
     * ดึงข้อมูลกราฟ (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        $days = $request->input('days', 7);
        $days = min($days, 90); // จำกัดไม่เกิน 90 วัน

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::today();

        $chartData = $this->buildChartData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }

    /**
     * สร้างข้อมูลกราฟ
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function buildChartData(Carbon $startDate, Carbon $endDate): array
    {
        // ดึงข้อมูลรายวัน — ทำนายฝัน
        $dreamDaily = HoroscopeDreamReading::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date');

        // ดึงข้อมูลรายวัน — เลขศาสตร์
        $numerologyDaily = HoroscopeNumerologyReading::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date');

        // ดึงข้อมูลรายวัน — ดูดวงรายวัน (views)
        $dailyViews = HoroscopeDailyPrediction::select(
            'target_date as date',
            DB::raw('SUM(view_count) as total_views')
        )
            ->where('target_date', '>=', $startDate)
            ->groupBy('target_date')
            ->pluck('total_views', 'date');

        // สร้าง labels + datasets
        $labels = [];
        $dreamCounts = [];
        $numerologyCounts = [];
        $dailyViewCounts = [];

        $period = new \DatePeriod(
            $startDate->copy()->startOfDay(),
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()->startOfDay()
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $labels[] = Carbon::parse($dateStr)->locale('th')->isoFormat('D MMM');
            $dreamCounts[] = (int) ($dreamDaily[$dateStr] ?? 0);
            $numerologyCounts[] = (int) ($numerologyDaily[$dateStr] ?? 0);
            $dailyViewCounts[] = (int) ($dailyViews[$dateStr] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '💭 ทำนายฝัน',
                    'data' => $dreamCounts,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                ],
                [
                    'label' => '🔢 เลขศาสตร์',
                    'data' => $numerologyCounts,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
                [
                    'label' => '⭐ ดวงรายวัน (views)',
                    'data' => $dailyViewCounts,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.1)',
                ],
            ],
        ];
    }
}
