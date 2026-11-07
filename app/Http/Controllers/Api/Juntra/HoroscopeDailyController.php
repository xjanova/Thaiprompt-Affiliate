<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\JuntraZodiacDaily;
use App\Services\Fortune\ZodiacAstroBrief;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 🌠 (2026-11-07) ดวงรายวันของราศีให้เว็บจันทรา — POST fortune/horoscope/daily
 *
 * juntraweb เรียกเส้นนี้มาตั้งแต่ 2026-07-26 แต่ไม่เคยมีอยู่จริง (404) ⇒ หน้าเว็บเขียนดวงเองจากข้างขึ้นข้างแรม
 * ตอนนี้ทำนายจากตำแหน่งดาวจริงด้วยพูลเดียวกับบอท (ดู JuntraZodiacDaily) · ของฟรี ไม่คิดเงิน
 */
class HoroscopeDailyController extends Controller
{
    public function __invoke(Request $request, JuntraZodiacDaily $daily): JsonResponse
    {
        $data = $request->validate([
            'zodiac' => ['required', 'string', Rule::in(array_keys(ZodiacAstroBrief::SLUGS))],
            'zodiac_th' => 'nullable|string|max:40',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        // รับเฉพาะเมื่อวาน-พรุ่งนี้ (เขตเวลาต่างกันได้ 1 วัน) — กันยิงวันสุ่มให้เปลือง AI
        $today = Carbon::today('Asia/Bangkok');
        $date = isset($data['date']) ? Carbon::parse($data['date'], 'Asia/Bangkok')->startOfDay() : $today;
        if (abs($today->diffInDays($date, false)) > 1) {
            return response()->json(['message' => 'รับเฉพาะดวงของเมื่อวานถึงพรุ่งนี้'], 422);
        }

        $out = $daily->get($data['zodiac'], $date);
        if ($out === null) {
            return response()->json(['message' => 'ไม่รู้จักราศีนี้'], 422);
        }

        return response()->json(['data' => $out + ['date' => $date->format('Y-m-d'), 'zodiac' => $data['zodiac']]]);
    }
}
