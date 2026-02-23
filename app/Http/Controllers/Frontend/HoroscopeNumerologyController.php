<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeNumerologyReading;
use App\Services\HoroscopeNumerologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeNumerologyController — เลขศาสตร์บน Horoscope Public
 *
 * จัดการ:
 * - หน้าหลักเลขศาสตร์ (5 ฟอร์มวิเคราะห์)
 * - วิเคราะห์ชื่อ / เบอร์โทร / ทะเบียนรถ / วันเกิด
 * - แสดงผลลัพธ์
 */
class HoroscopeNumerologyController extends Controller
{
    /**
     * @var HoroscopeNumerologyService
     */
    protected HoroscopeNumerologyService $numerologyService;

    /**
     * Constructor
     */
    public function __construct(HoroscopeNumerologyService $numerologyService)
    {
        $this->numerologyService = $numerologyService;
    }

    /**
     * หน้าหลักเลขศาสตร์ — 5 ฟอร์มวิเคราะห์
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // สถิติ
        $totalReadings = HoroscopeNumerologyReading::count();
        $numberMeanings = HoroscopeNumerologyService::getAllNumberMeanings();

        return view('frontend.horoscope.numerology.index', [
            'pageTitle' => 'เลขศาสตร์ — วิเคราะห์ตัวเลขดวงชะตา',
            'totalReadings' => $totalReadings,
            'numberMeanings' => $numberMeanings,
        ]);
    }

    /**
     * วิเคราะห์ชื่อ
     */
    public function analyzeName(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $reading = $this->numerologyService->analyzeName(
                $request->name,
                $request->question
            );

            return $this->buildSuccessResponse($reading);
        } catch (\Exception $e) {
            Log::error('วิเคราะห์ชื่อผิดพลาด: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
        }
    }

    /**
     * วิเคราะห์เบอร์โทร
     */
    public function analyzePhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:15',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $reading = $this->numerologyService->analyzePhone(
                $request->phone,
                $request->question
            );

            return $this->buildSuccessResponse($reading);
        } catch (\Exception $e) {
            Log::error('วิเคราะห์เบอร์โทรผิดพลาด: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
        }
    }

    /**
     * วิเคราะห์ทะเบียนรถ
     */
    public function analyzeLicensePlate(Request $request): JsonResponse
    {
        $request->validate([
            'license_plate' => 'required|string|min:2|max:20',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $reading = $this->numerologyService->analyzeLicensePlate(
                $request->license_plate,
                $request->question
            );

            return $this->buildSuccessResponse($reading);
        } catch (\Exception $e) {
            Log::error('วิเคราะห์ทะเบียนรถผิดพลาด: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
        }
    }

    /**
     * วิเคราะห์วันเกิด
     */
    public function analyzeBirthday(Request $request): JsonResponse
    {
        $request->validate([
            'birthday' => 'required|date|before:today',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $reading = $this->numerologyService->analyzeBirthday(
                $request->birthday,
                $request->question
            );

            return $this->buildSuccessResponse($reading);
        } catch (\Exception $e) {
            Log::error('วิเคราะห์วันเกิดผิดพลาด: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
        }
    }

    /**
     * วิเคราะห์เลขบัตรประชาชน
     */
    public function analyzeIdCard(Request $request): JsonResponse
    {
        $request->validate([
            'id_card' => 'required|string|size:13',
            'question' => 'nullable|string|max:500',
        ]);

        // ⚠️ ไม่เก็บเลขบัตรจริง — ใช้แค่ตัวเลขท้าย 4 ตัว + root
        $idCard = preg_replace('/[^0-9]/', '', $request->id_card);

        if (strlen($idCard) !== 13) {
            return response()->json(['success' => false, 'message' => 'เลขบัตรประชาชนไม่ถูกต้อง'], 422);
        }

        try {
            // วิเคราะห์เฉพาะตัวเลข (ไม่เก็บเลขจริง)
            $digits = array_map('intval', str_split($idCard));
            $rootNumber = $this->numerologyService->reduceToSingleDigit(array_sum($digits));
            $numberMeaning = $this->numerologyService->getNumberMeaning($rootNumber);
            $maskedId = str_repeat('*', 9).substr($idCard, -4);

            $reading = HoroscopeNumerologyReading::create([
                'user_id' => auth()->id(),
                'reading_type' => HoroscopeNumerologyReading::TYPE_ID_CARD,
                'input_value' => $maskedId, // เก็บแค่ masked version
                'numerology_number' => $rootNumber,
                'digit_breakdown' => json_encode([
                    'last_4' => substr($idCard, -4),
                    'root' => $rootNumber,
                    'sum' => array_sum($digits),
                ]),
                'ai_analysis_th' => "เลขชะตาจากบัตรประชาชนคือ {$rootNumber} ({$numberMeaning['name']}) — {$numberMeaning['meaning']}",
                'lucky_numbers' => [
                    str_pad(($rootNumber * 7) % 100, 2, '0', STR_PAD_LEFT),
                    str_pad(($rootNumber * 11 + rand(1, 9)) % 100, 2, '0', STR_PAD_LEFT),
                ],
                'lucky_colors' => [$numberMeaning['color']],
                'session_id' => session()->getId(),
                'ip_address' => request()->ip(),
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            return $this->buildSuccessResponse($reading);
        } catch (\Exception $e) {
            Log::error('วิเคราะห์เลขบัตรผิดพลาด: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'], 500);
        }
    }

    /**
     * แสดงผลลัพธ์ (GET — สำหรับ share)
     */
    public function showResult(int $id)
    {
        $reading = HoroscopeNumerologyReading::findOrFail($id);

        // เพิ่ม view count
        $reading->increment('view_count');

        $numberMeaning = $this->numerologyService->getNumberMeaning($reading->numerology_number);

        return view('frontend.horoscope.numerology.result', [
            'pageTitle' => HoroscopeNumerologyReading::TYPE_LABELS_TH[$reading->reading_type] ?? 'เลขศาสตร์',
            'reading' => $reading,
            'numberMeaning' => $numberMeaning,
            'breakdown' => json_decode($reading->digit_breakdown, true),
        ]);
    }

    /**
     * สร้าง response สำเร็จ (ใช้ร่วมกัน)
     */
    protected function buildSuccessResponse(HoroscopeNumerologyReading $reading): JsonResponse
    {
        $numberMeaning = $this->numerologyService->getNumberMeaning($reading->numerology_number);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $reading->id,
                'type' => $reading->reading_type,
                'type_label' => HoroscopeNumerologyReading::TYPE_LABELS_TH[$reading->reading_type] ?? '',
                'type_icon' => HoroscopeNumerologyReading::TYPE_ICONS[$reading->reading_type] ?? '🔢',
                'input_value' => $reading->input_value,
                'numerology_number' => $reading->numerology_number,
                'number_meaning' => $numberMeaning,
                'breakdown' => json_decode($reading->digit_breakdown, true),
                'analysis' => $reading->ai_analysis_th,
                'advice' => $reading->ai_advice_th,
                'lucky_numbers' => $reading->lucky_numbers,
                'lucky_colors' => $reading->lucky_colors,
                'result_url' => route('horoscope.numerology.result', $reading->id),
            ],
            'message' => 'วิเคราะห์สำเร็จ',
        ]);
    }
}
