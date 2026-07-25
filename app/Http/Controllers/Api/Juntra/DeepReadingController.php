<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/juntra/fortune/deep
 *
 * "ดูดวงเชิงลึก" แพ็ก 39฿ ให้เว็บ จันทรา.online — ตัวเดียวกับที่บอทแม่หมอ
 * ใน Facebook/LINE ขายอยู่ (READING_TYPE_DEEP) เพื่อให้ลูกค้าที่จ่าย 39 บาท
 * บนเว็บได้ของแบบเดียวกับลูกค้าที่จ่าย 39 บาทในแชท
 *
 * ใช้ generateFortuneTelling(readingType: 'deep') ของบอทตรง ๆ จึงได้ทั้ง
 * prompt template ที่แอดมินแก้ได้จากหลังบ้าน, READING_CONFIG['deep']
 * (max_tokens 6000 / temperature 0.55 ที่จูนมาแล้วว่าไม่ทำให้คำทำนายซ้ำกันหมด)
 * และคีย์พูลเดียวกัน — juntra ไม่ถือคีย์ AI เอง
 *
 * เรื่องเงิน: juntraweb หักเครดิตลูกค้าก่อนเรียกมาแล้ว ที่นี่ไม่คิดเงินซ้ำ
 * และไม่แตะ FortuneReading/บิลของบอท
 */
class DeepReadingController extends Controller
{
    private const MAX_QUESTIONS = 5;

    public function __invoke(Request $request, FortuneAIService $ai): JsonResponse
    {
        $data = $request->validate([
            'questions'   => 'required|array|min:1|max:'.self::MAX_QUESTIONS,
            'questions.*' => 'required|string|max:500',
            'birth_date'  => 'nullable|date_format:Y-m-d|before:today',
            'name'        => 'nullable|string|max:120',
        ]);

        $user = $request->user();

        $profile = array_filter([
            'name' => $data['name'] ?? $user?->name,
        ]);

        try {
            $result = $ai->withCustomerContext(array_filter([
                'user_id'       => $user?->id,
                'customer_name' => $profile['name'] ?? null,
            ]))->generateFortuneTelling(
                questions: array_values($data['questions']),
                userProfile: $profile ?: null,
                userPosts: null,
                promptTemplate: null,     // ใช้ template จากหลังบ้านเหมือนบอท
                readingType: 'deep',
                birthDate: $data['birth_date'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra deep reading failed', ['err' => $e->getMessage()]);

            // 503 เพื่อให้ juntraweb รู้ว่า "ระบบไม่พร้อม" แล้วคืนเครดิตลูกค้า
            // ไม่ใช่ 500 ที่แยกไม่ออกว่าเป็นบั๊กหรือระบบล่ม
            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '') {
            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }

        return response()->json(['data' => [
            'reading'     => $text,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model'    => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? null,
        ]]);
    }
}
