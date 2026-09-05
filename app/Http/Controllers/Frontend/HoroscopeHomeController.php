<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeZodiacSign;
use App\Services\Fortune\DailyArticleMirror;
use App\Services\FortuneChartService;
use Illuminate\Http\Request;

/**
 * HoroscopeHomeController — หน้าแรกระบบดูดวงสาธารณะ
 *
 * แสดงหน้าแรก /horoscope พร้อมลิงก์ไปทุกหมวด:
 * ดวงรายวัน, ไพ่ทาโรต์, เลขศาสตร์, ทำนายฝัน
 */
class HoroscopeHomeController extends Controller
{
    /**
     * แสดงหน้าแรกดูดวง
     *
     * ดึงข้อมูล: 12 ราศี + ดวงวันนี้ (ถ้ามี) + 7 วันเกิด
     * แสดง 5 หมวดหลักเป็น animated cards
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึง settings
        $settings = FortuneTellingSetting::first();

        // ตรวจสอบว่าเปิดใช้งานระบบหรือไม่
        if ($settings && ! $settings->horoscope_public_enabled) {
            abort(404, 'ระบบดูดวงปิดให้บริการชั่วคราว');
        }

        // ดึง 12 ราศี พร้อมดวงวันนี้
        $zodiacSigns = HoroscopeZodiacSign::active()
            ->ordered()
            ->get();

        // ดึงดวงวันนี้ทั้ง 12 ราศี (ถ้ามี)
        $todayPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'zodiac')
            ->where('status', 'generated')
            ->get()
            ->keyBy('zodiac_sign_id');

        // ดึงข้อมูล 7 วันเกิดจาก FortuneChartService
        $birthDays = $this->getBirthDayData();

        // ดวงวันนี้ของ 7 วันเกิด (ถ้ามี)
        $todayBirthDayPredictions = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'birth_day')
            ->where('status', 'generated')
            ->get()
            ->keyBy('birth_day');

        // หาราศีเด่นวันนี้ (คะแนนสูงสุด)
        $featuredZodiac = null;
        if ($todayPredictions->isNotEmpty()) {
            $bestPrediction = $todayPredictions->sortByDesc('overall_score')->first();
            $featuredZodiac = $zodiacSigns->firstWhere('id', $bestPrediction->zodiac_sign_id);
        }

        // 5 หมวดหลัก
        $categories = $this->getMainCategories();

        // SEO — ตัดคำว่า "ฟรี" ออกถ้า admin ปิดการใช้งานฟรีทั้งหมด
        // ใช้ null-safe operator กันกรณี $settings เป็น null (fresh install ที่ยังไม่มี row)
        $freeEnabled = $settings ? $settings->isHoroscopePublicFreeEnabled() : true;
        $freeWord = $freeEnabled ? ' ฟรี' : '';
        $seoTitle = $settings?->horoscope_seo_title_th
            ?? 'ดูดวงออนไลน์'.$freeWord.' — ดวงรายวัน ไพ่ทาโรต์ ทำนายฝัน เลขศาสตร์';
        $seoDescription = $settings?->horoscope_seo_description_th
            ?? 'ดูดวงออนไลน์'.$freeWord.' ดวงรายวัน 12 ราศี ไพ่ทาโรต์ ทำนายฝัน เลขเด็ด วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ ทำนายด้วย AI แม่นยำ ทันสมัย';

        return view('frontend.horoscope.home', [
            'zodiacSigns' => $zodiacSigns,
            'todayPredictions' => $todayPredictions,
            'birthDays' => $birthDays,
            'todayBirthDayPredictions' => $todayBirthDayPredictions,
            'featuredZodiac' => $featuredZodiac,
            'categories' => $categories,
            'pageTitle' => $seoTitle,
            'pageDescription' => $seoDescription,
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
            $birthDays[$day] = [
                'day' => $day,
                'name_th' => 'วัน'.$dayNames[$day],
                'emoji' => $dayEmojis[$day],
                'color_hex' => $dayColors[$day],
                'planet' => $data['planet'],
                'element' => $data['element'],
                'lucky_color' => $data['lucky_color'],
            ];
        }

        return $birthDays;
    }

    /**
     * 5 หมวดหลักของระบบดูดวง
     */
    protected function getMainCategories(): array
    {
        return [
            [
                'title' => 'ดวงรายวัน',
                'subtitle' => '12 ราศี + 7 วันเกิด',
                'description' => 'ดูดวงรายวัน ประจำ 12 ราศีและ 7 วันเกิด พร้อมคะแนน 5 ด้าน',
                'icon' => '⭐',
                'route' => 'horoscope.daily.index',
                'gradient_from' => '#6366f1',
                'gradient_to' => '#a855f7',
                'features' => ['12 ราศี', '7 วันเกิด', 'AI ทำนาย', 'เลขมงคล'],
            ],
            [
                'title' => 'ไพ่ทาโรต์',
                'subtitle' => 'เปิดไพ่ทำนายชีวิต',
                'description' => 'เปิดไพ่ทาโรต์ 78 ใบ แบบ interactive มี animation สวยงาม',
                'icon' => '🃏',
                'route' => 'horoscope.tarot.index',
                'gradient_from' => '#ec4899',
                'gradient_to' => '#f43f5e',
                'features' => ['78 ใบ', 'เปิดไพ่ฟรี', 'AI ตีความ', 'หลายหมวด'],
            ],
            [
                'title' => 'เลขศาสตร์',
                'subtitle' => 'ทำนายชื่อ เบอร์โทร ทะเบียน',
                'description' => 'วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ เลขบัตรประชาชน ด้วย AI',
                'icon' => '🔢',
                'route' => 'horoscope.numerology.index',
                'gradient_from' => '#f59e0b',
                'gradient_to' => '#ef4444',
                'features' => ['ทำนายชื่อ', 'เบอร์โทร', 'ทะเบียนรถ', 'บัตรประชาชน'],
            ],
            [
                'title' => 'ทำนายฝัน',
                'subtitle' => 'แปลความฝัน + เลขเด็ด',
                'description' => 'ทำนายฝัน 100+ สัญลักษณ์ พร้อมเลขเด็ดจากฝัน ทำนายด้วย AI',
                'icon' => '💭',
                'route' => 'horoscope.dream.index',
                'gradient_from' => '#8b5cf6',
                'gradient_to' => '#3b82f6',
                'features' => ['100+ สัญลักษณ์', 'เลขเด็ด', 'AI ทำนาย', 'หมวดหมู่'],
            ],
            [
                'title' => 'ดูดวง AI',
                'subtitle' => 'ถามอะไรก็ได้',
                'description' => 'สนทนากับ AI หมอดู ถามเรื่องดวงชะตา ความรัก การเงิน การงาน',
                'icon' => '🤖',
                'route' => 'horoscope.home',
                'gradient_from' => '#06b6d4',
                'gradient_to' => '#10b981',
                'features' => ['AI ฉลาด', 'ถามได้ทุกเรื่อง', 'แม่นยำ', 'ทันสมัย'],
            ],
        ];
    }
}
