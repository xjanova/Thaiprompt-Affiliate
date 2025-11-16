<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Translate Controller - API สำหรับแปลภาษาด้วย Google Translate
 *
 * จัดการ API endpoints สำหรับระบบแปลภาษาแบบเรียลไทม์
 *
 * @example
 * POST /api/v1/translate
 * {
 *   "text": "สวัสดี",
 *   "target": "en",
 *   "source": "th"
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "translatedText": "Hello",
 *   "detectedSourceLanguage": "th"
 * }
 */
class TranslateController extends Controller
{
    /**
     * Translation Service
     *
     * @var TranslationService
     */
    protected TranslationService $translationService;

    /**
     * Constructor - เริ่มต้น Translation Service
     *
     * @param TranslationService $translationService
     */
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * แปลข้อความจากภาษาหนึ่งไปยังอีกภาษาหนึ่ง
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @example
     * POST /api/v1/translate
     * {
     *   "text": "สวัสดีครับ",
     *   "target": "en",
     *   "source": "th"
     * }
     */
    public function translate(Request $request): JsonResponse
    {
        // ตรวจสอบข้อมูลที่รับเข้ามา
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'target' => 'required|string|size:2',
            'source' => 'nullable|string|size:2',
        ], [
            'text.required' => 'กรุณาระบุข้อความที่ต้องการแปล',
            'text.max' => 'ข้อความยาวเกินไป (สูงสุด 5,000 ตัวอักษร)',
            'target.required' => 'กรุณาระบุภาษาปลายทาง',
            'target.size' => 'รหัสภาษาไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'translatedText' => null,
                'detectedSourceLanguage' => null,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $text = $request->input('text');
        $target = $request->input('target');
        $source = $request->input('source', 'th'); // ค่าเริ่มต้นเป็นภาษาไทย

        try {
            // เรียกใช้ TranslationService เพื่อแปลข้อความ
            $translatedText = $this->translationService->translate($text, $target, $source);

            // ตรวจจับภาษาต้นทาง (ถ้าไม่ได้ระบุมา)
            $detectedLanguage = $source;
            if ($request->input('source') === null) {
                $detectedLanguage = $this->translationService->detectLanguage($text) ?? $source;
            }

            // ตรวจสอบว่าการแปลสำเร็จหรือไม่
            if ($translatedText === null || $translatedText === $text) {
                // ถ้าไม่สำเร็จ หรือผลลัพธ์เหมือนเดิม
                Log::warning('Translation failed or returned same text', [
                    'text' => substr($text, 0, 100),
                    'target' => $target,
                    'source' => $source,
                ]);

                return response()->json([
                    'success' => true,
                    'translatedText' => $text, // คืนข้อความเดิม
                    'detectedSourceLanguage' => $detectedLanguage,
                    'message' => 'ไม่สามารถแปลภาษาได้ หรือภาษาเดียวกัน',
                ]);
            }

            return response()->json([
                'success' => true,
                'translatedText' => $translatedText,
                'detectedSourceLanguage' => $detectedLanguage,
                'message' => 'แปลภาษาสำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Translation API error', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100),
                'target' => $target,
            ]);

            return response()->json([
                'success' => false,
                'translatedText' => $text, // fallback เป็นข้อความเดิม
                'detectedSourceLanguage' => $source,
                'error' => 'เกิดข้อผิดพลาดในการแปลภาษา',
            ], 500);
        }
    }

    /**
     * แปลข้อความหลายรายการพร้อมกัน (batch translation)
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @example
     * POST /api/v1/translate/batch
     * {
     *   "texts": ["สวัสดี", "ขอบคุณ", "ลาก่อน"],
     *   "target": "en",
     *   "source": "th"
     * }
     */
    public function translateBatch(Request $request): JsonResponse
    {
        // ตรวจสอบข้อมูลที่รับเข้ามา
        $validator = Validator::make($request->all(), [
            'texts' => 'required|array|min:1|max:50',
            'texts.*' => 'required|string|max:5000',
            'target' => 'required|string|size:2',
            'source' => 'nullable|string|size:2',
        ], [
            'texts.required' => 'กรุณาระบุข้อความที่ต้องการแปล',
            'texts.max' => 'แปลได้สูงสุด 50 ข้อความต่อครั้ง',
            'target.required' => 'กรุณาระบุภาษาปลายทาง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'translations' => [],
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $texts = $request->input('texts');
        $target = $request->input('target');
        $source = $request->input('source', 'th');

        try {
            // แปลทีละข้อความ (TranslationService มี batch แต่เรา loop ง่ายกว่า)
            $translations = [];
            foreach ($texts as $text) {
                $translatedText = $this->translationService->translate($text, $target, $source);
                $translations[] = [
                    'original' => $text,
                    'translated' => $translatedText ?? $text,
                ];
            }

            return response()->json([
                'success' => true,
                'translations' => $translations,
                'count' => count($translations),
                'message' => 'แปลภาษาสำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Batch translation API error', [
                'error' => $e->getMessage(),
                'count' => count($texts),
                'target' => $target,
            ]);

            return response()->json([
                'success' => false,
                'translations' => [],
                'error' => 'เกิดข้อผิดพลาดในการแปลภาษา',
            ], 500);
        }
    }

    /**
     * ตรวจจับภาษาของข้อความ
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @example
     * POST /api/v1/translate/detect
     * {
     *   "text": "Hello World"
     * }
     */
    public function detect(Request $request): JsonResponse
    {
        // ตรวจสอบข้อมูลที่รับเข้ามา
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:1000',
        ], [
            'text.required' => 'กรุณาระบุข้อความที่ต้องการตรวจจับภาษา',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'language' => null,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $text = $request->input('text');

        try {
            // ตรวจจับภาษา
            $language = $this->translationService->detectLanguage($text);

            if ($language === null) {
                return response()->json([
                    'success' => false,
                    'language' => null,
                    'message' => 'ไม่สามารถตรวจจับภาษาได้',
                ]);
            }

            return response()->json([
                'success' => true,
                'language' => $language,
                'message' => 'ตรวจจับภาษาสำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Language detection API error', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100),
            ]);

            return response()->json([
                'success' => false,
                'language' => null,
                'error' => 'เกิดข้อผิดพลาดในการตรวจจับภาษา',
            ], 500);
        }
    }

    /**
     * ดึงรายการภาษาที่รองรับ
     *
     * @return JsonResponse
     *
     * @example
     * GET /api/v1/translate/languages
     */
    public function languages(): JsonResponse
    {
        try {
            $languages = $this->translationService->getAvailableLanguages();

            return response()->json([
                'success' => true,
                'languages' => $languages,
                'count' => count($languages),
                'message' => 'ดึงรายการภาษาสำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Get languages API error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'languages' => [],
                'error' => 'เกิดข้อผิดพลาดในการดึงรายการภาษา',
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะระบบแปลภาษา
     *
     * @return JsonResponse
     *
     * @example
     * GET /api/v1/translate/status
     */
    public function status(): JsonResponse
    {
        $isEnabled = $this->translationService->isEnabled();

        return response()->json([
            'success' => true,
            'enabled' => $isEnabled,
            'message' => $isEnabled ? 'ระบบแปลภาษาพร้อมใช้งาน' : 'ระบบแปลภาษาไม่พร้อมใช้งาน',
        ]);
    }
}
