<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDailyPrediction;
use App\Services\Fortune\DailyArticleMirror;
use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeDailyPredictionController — ดวงรายวัน 7+1 วันเกิด (หลังบ้าน)
 *
 * จัดการ:
 * - ดูรายการดวงรายวันที่ cron 00:01 สร้างไว้ (กรองตามวันที่/สถานะ)
 * - สั่งสร้างซ้ำด้วยมือ เผื่อรอบ 00:01 + preflight 00:20/06:00 ล้มทั้งหมด
 * - ลบใบที่พัง เพื่อให้รอบถัดไปสร้างใหม่
 *
 * 🗑️ (2026-09-09) เดิมชื่อ HoroscopeZodiacController และมีหน้า "จัดการ 12 ราศี"
 *    อยู่ด้วย — ถอดออกแล้วเพราะตาราง horoscope_zodiac_signs บน prod = 0 แถว
 *    (ไม่เคยรัน HoroscopeZodiacSignSeeder) ทำให้ตลอดอายุระบบไม่เคยมี
 *    prediction ชนิด zodiac สักใบ · เหลือเฉพาะเลนวันเกิดที่วิ่งจริง
 *    ถ้าจะเปิดเลนราศีคืน: seed 12 ราศี แล้ว revert คอมมิตนี้
 */
class HoroscopeDailyPredictionController extends Controller
{
    /**
     * @var HoroscopeDailyService
     */
    protected HoroscopeDailyService $dailyService;

    /**
     * Constructor
     */
    public function __construct(HoroscopeDailyService $dailyService)
    {
        $this->dailyService = $dailyService;
    }

    /**
     * แสดงรายการดวงรายวันทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = HoroscopeDailyPrediction::where('prediction_type', 'birth_day')
            ->orderByDesc('target_date')
            ->orderBy('birth_day');

        // กรองตามวันที่
        if ($request->filled('date')) {
            $query->where('target_date', $request->input('date'));
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $predictions = $query->paginate(20);

        // สรุปของวันนี้ — ครบ 8 ใบหรือยัง (คือตัวเลขที่ต้องมองทุกเช้า)
        $expectedToday = count(DailyArticleMirror::allBirthDays());
        $readyToday = HoroscopeDailyPrediction::where('target_date', today())
            ->where('prediction_type', 'birth_day')
            ->where('status', 'generated')
            ->count();

        return view('admin.fortune.horoscope-public.daily.index', [
            'pageTitle' => '📊 ดวงรายวันทั้งหมด',
            'predictions' => $predictions,
            'expectedToday' => $expectedToday,
            'readyToday' => $readyToday,
        ]);
    }

    /**
     * สั่ง generate ดวงรายวันของวันที่ระบุ (ปุ่มกู้มือ)
     *
     * idempotent — ใบที่ status=generated อยู่แล้วจะถูกข้าม ไม่ยิง AI ซ้ำ
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        $request->validate([
            'target_date' => 'nullable|date',
        ]);

        $date = $request->input('target_date')
            ? Carbon::parse($request->input('target_date'))
            : Carbon::today();

        try {
            $result = $this->dailyService->generateDailyForAllBirthDays($date);

            $message = "✅ ดวงวันที่ {$date->format('d/m/Y')} — สร้างใหม่ {$result['success']} ใบ"
                ." · ข้าม (มีอยู่แล้ว) {$result['skipped']} ใบ";

            if ($result['failed'] > 0) {
                $message .= " · ล้มเหลว {$result['failed']} ใบ";
            }

            return redirect()
                ->route('admin.fortune.horoscope-public.daily.index')
                ->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Generate daily predictions ผิดพลาด: '.$e->getMessage());

            return redirect()
                ->route('admin.fortune.horoscope-public.daily.index')
                ->with('error', '❌ เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ลบ prediction (ใบที่พัง — ลบแล้วรอบถัดไปจะสร้างใหม่ให้)
     *
     * @param HoroscopeDailyPrediction $prediction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(HoroscopeDailyPrediction $prediction)
    {
        $prediction->delete();

        return redirect()
            ->back()
            ->with('success', '✅ ลบรายการสำเร็จ');
    }
}
