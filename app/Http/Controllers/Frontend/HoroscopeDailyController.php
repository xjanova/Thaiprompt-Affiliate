<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeZodiacSign;
use App\Services\Fortune\DailyArticleMirror;
use App\Services\FortuneChartService;
use App\Services\HoroscopeDailyService;
use Illuminate\Http\Request;

/**
 * HoroscopeDailyController — ดวงรายวัน 12 ราศี + 7 วันเกิด
 *
 * หน้าต่างๆ:
 * - index: แสดง grid 12 ราศี + tabs 7 วันเกิด
 * - showZodiac: รายละเอียดดวงราศี 5 ด้าน + ประวัติ 7 วัน
 * - showBirthDay: รายละเอียดดวงตามวันเกิด
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
     * หน้าหลักดวงรายวัน — แสดง 12 ราศี + 7 วันเกิด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึง 12 ราศี
        $zodiacSigns = HoroscopeZodiacSign::active()
            ->ordered()
            ->get();

        // ดวงวันนี้ — 12 ราศี
        $zodiacPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'zodiac')
            ->generated()
            ->get()
            ->keyBy('zodiac_sign_id');

        // ดวงวันนี้ — 7 วันเกิด
        $birthDayPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'birth_day')
            ->generated()
            ->get()
            ->keyBy('birth_day');

        // ข้อมูล 7 วันเกิด
        $birthDays = $this->getBirthDayData();

        // Tab ที่ active
        $activeTab = $request->get('tab', 'zodiac');

        // SEO
        $pageTitle = 'ดวงรายวัน 12 ราศี + 7 วันเกิด — '.today()->locale('th')->translatedFormat('j F Y');

        return view('frontend.horoscope.daily.index', [
            'zodiacSigns' => $zodiacSigns,
            'zodiacPredictions' => $zodiacPredictions,
            'birthDayPredictions' => $birthDayPredictions,
            'birthDays' => $birthDays,
            'activeTab' => $activeTab,
            'pageTitle' => $pageTitle,
        ]);
    }

    /**
     * หน้ารายละเอียดดวงราศี
     *
     * @param  string  $slug  slug ของราศี เช่น 'aries'
     * @return \Illuminate\View\View
     */
    public function showZodiac(string $slug)
    {
        // ดึงราศี
        $zodiac = HoroscopeZodiacSign::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // ดวงวันนี้
        $prediction = $this->dailyService->getZodiacPrediction($slug);

        // เพิ่ม view count
        if ($prediction) {
            $prediction->incrementView();
        }

        // ประวัติ 7 วัน
        $history = $this->dailyService->getZodiacHistory($zodiac->id, 7);

        // ราศีอื่นๆ สำหรับ navigation
        $allZodiacs = HoroscopeZodiacSign::active()->ordered()->get();

        // SEO
        $pageTitle = "ดวงราศี{$zodiac->name_th}วันนี้ — ".today()->locale('th')->translatedFormat('j F Y');

        return view('frontend.horoscope.daily.zodiac-detail', [
            'zodiac' => $zodiac,
            'prediction' => $prediction,
            'history' => $history,
            'allZodiacs' => $allZodiacs,
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
