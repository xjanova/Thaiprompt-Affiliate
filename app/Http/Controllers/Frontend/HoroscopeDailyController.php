<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDailyPrediction;
use App\Services\Fortune\DailyArticleMirror;
use App\Services\FortuneChartService;
use App\Services\HoroscopeDailyService;
use Illuminate\Http\Request;

/**
 * HoroscopeDailyController — ดวงรายวัน 7+1 วันเกิด
 *
 * หน้าต่างๆ:
 * - index: แสดง grid วันเกิด (7 วัน + พุธกลางคืน)
 * - showBirthDay: รายละเอียดดวงตามวันเกิด
 *
 * 🗑️ (2026-09-09) ถอดเลน 12 ราศี ออก — horoscope_zodiac_signs บน prod
 *    = 0 แถว หน้านี้จึงเปิดมาด้วยแท็บ "12 ราศี" ที่ว่างเปล่ามาตลอด
 */
class HoroscopeDailyController extends Controller
{
    protected HoroscopeDailyService $dailyService;

    /**
     * Constructor
     */
    public function __construct(HoroscopeDailyService $dailyService)
    {
        $this->dailyService = $dailyService;
    }

    /**
     * หน้าหลักดวงรายวัน — grid 7+1 วันเกิด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดวงวันนี้ — 7+1 วันเกิด
        $birthDayPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'birth_day')
            ->generated()
            ->get()
            ->keyBy('birth_day');

        // ข้อมูล 7+1 วันเกิด
        $birthDays = $this->getBirthDayData();

        // SEO
        $pageTitle = 'ดวงรายวันตามวันเกิด — '.today()->locale('th')->translatedFormat('j F Y');

        return view('frontend.horoscope.daily.index', [
            'birthDayPredictions' => $birthDayPredictions,
            'birthDays' => $birthDays,
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * หน้ารายละเอียดดวงตามวันเกิด
     *
     * @param  int  $day  วันเกิด 0-6
     * @return \Illuminate\View\View
     */
    public function showBirthDay(int $day)
    {
        // ตรวจสอบค่า day — 7 = พุธกลางคืน (ราหู) วันเกิดที่ 8 ตามตำราไทย
        if ($day < 0 || $day > FortuneChartService::WEDNESDAY_NIGHT) {
            abort(404, 'ไม่พบข้อมูลวันเกิดนี้');
        }

        // ดวงวันนี้
        $prediction = $this->dailyService->getBirthDayPrediction($day);

        // เพิ่ม view count
        if ($prediction) {
            $prediction->incrementView();
        }

        // ประวัติ 7 วัน
        $history = $this->dailyService->getBirthDayHistory($day, 7);

        // ข้อมูล Chaochana — chaochanaFor() รองรับ index 7 (พุธกลางคืน = ราหู)
        $chaochana = FortuneChartService::chaochanaFor($day) ?? [];
        $planetKey = $chaochana['planet'] ?? 'sun';
        $planet = FortuneChartService::PLANETS[$planetKey] ?? [];

        // ข้อมูลวันเกิด — index 7 = พุธกลางคืน (วันเกิดที่ 8 ตามตำราไทย)
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];
        $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣', '🌘'];
        $dayColors = ['#ef4444', '#eab308', '#ec4899', '#22c55e', '#f97316', '#06b6d4', '#7c3aed', '#34495e'];

        $birthDayInfo = [
            'day' => $day,
            'name_th' => 'วัน'.$dayNames[$day],
            'emoji' => $dayEmojis[$day],
            'color_hex' => $dayColors[$day],
            'planet_name' => $planet['name'] ?? 'อาทิตย์',
            'planet_symbol' => $planet['symbol'] ?? '☉',
            'planet_color' => $planet['color'] ?? '#FF6B35',
            'element' => $chaochana['element'] ?? 'ไฟ',
            'lucky_color' => $chaochana['lucky_color'] ?? 'แดง',
            'friends' => collect($chaochana['friends'] ?? [])
                ->map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p)
                ->toArray(),
            'enemies' => collect($chaochana['enemies'] ?? [])
                ->map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p)
                ->toArray(),
        ];

        // SEO
        $pageTitle = "ดวง{$birthDayInfo['name_th']}วันนี้ — ".today()->locale('th')->translatedFormat('j F Y');

        return view('frontend.horoscope.daily.birth-day-detail', [
            'birthDayInfo' => $birthDayInfo,
            'prediction' => $prediction,
            'history' => $history,
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * ดึงข้อมูลวันเกิดทั้งหมด (7 วัน + พุธกลางคืน) จาก FortuneChartService
     */
    protected function getBirthDayData(): array
    {
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];
        $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣', '🌘'];
        $dayColors = ['#ef4444', '#eab308', '#ec4899', '#22c55e', '#f97316', '#06b6d4', '#7c3aed', '#34495e'];

        $birthDays = [];
        foreach (DailyArticleMirror::allBirthDays() as $day) {
            $data = FortuneChartService::chaochanaFor($day);
            if ($data === null) {
                continue;
            }
            $planet = FortuneChartService::PLANETS[$data['planet']] ?? [];
            $birthDays[$day] = [
                'day' => $day,
                'name_th' => 'วัน'.$dayNames[$day],
                'emoji' => $dayEmojis[$day],
                'color_hex' => $dayColors[$day],
                'planet' => $planet['name'] ?? $data['planet'],
                'element' => $data['element'],
                'lucky_color' => $data['lucky_color'],
            ];
        }

        return $birthDays;
    }
}
