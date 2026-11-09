<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\JuntraSpreadProfiles;
use App\Services\Fortune\PersonaBreakDetector;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Services\LineGatekeeperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/juntra/fortune/deep (+ /server/fortune/deep)
 *
 * "ดูดวงเชิงลึก" แพ็ก 39฿ ให้เว็บ จันทรา.online — ตัวเดียวกับที่บอทแม่หมอใน Facebook/LINE ขาย
 *
 * 2 โหมด:
 *   1) ทีละข้อ (ส่ง question_index มา) — 🔮 (2026-11-07) เลนเดียวกับบิลในแชททุกอย่าง
 *      FortuneConversationService::answerDeepQuestionForWeb → generateDeepAnswer (ตัวเดียวกับลูปของบอท)
 *      = พรอมต์ต่อข้อ (ผังดวงจริง ดาวจร กราฟชีวิต ฤกษ์ บริบทไทย) · prediction_deep/sensitive (กู้ได้แค่ OpenAI)
 *      · ด่านหลุดบท · ด่านความครบ + retry · เว็บเรียกจากงานเบื้องหลังทีละข้อ (ข้อละ ≤ ~55 วิ)
 *   2) ทั้งบิลในคำขอเดียว (ไม่ส่ง question_index — เว็บรุ่นเก่า/แอพมือถือ) — พรอมต์รวมแบบเดิม
 *      แต่ 🛡️ (2026-11-07) ย้ายจาก generateFortuneTelling (ยิงครั้งเดียว ไม่มี purpose → key แชท Haiku
 *      ตอบคำปฏิเสธแทนคำทำนาย) มาเป็นคีย์พูลบิลจ่ายเงิน prediction_deep + ด่านหลุดบท
 *
 * ทั้งสองโหมด: ได้คำปฏิเสธ/คำตอบไม่ครบ = 503 (reason_code) ให้เว็บคืนเงิน — ห้ามส่งข้อความปัดไปขาย
 *
 * เรื่องเงิน: juntraweb หักเครดิตลูกค้าก่อนเรียกมาแล้ว ที่นี่ไม่คิดเงินซ้ำ และไม่แตะ FortuneReading/บิลของบอท
 */
class DeepReadingController extends Controller
{
    private const MAX_QUESTIONS = 5;

    /** เพดานเวลาของคำขอหนึ่ง (วิ) — เว็บรอคำขอหนึ่งได้ ~55-60 วิ (Apache) */
    private const REQUEST_BUDGET_SEC = 50;

    public function __invoke(Request $request, FortuneAIService $ai): JsonResponse
    {
        $data = $request->validate([
            'questions' => 'required|array|min:1|max:'.self::MAX_QUESTIONS,
            'questions.*' => 'required|string|max:500',
            'birth_date' => 'nullable|date_format:Y-m-d|before:today',
            'name' => 'nullable|string|max:120',
            'question_index' => 'nullable|integer|min:0|max:'.(self::MAX_QUESTIONS - 1),
            'previous' => 'nullable|array|max:'.(self::MAX_QUESTIONS - 1),
            'previous.*.question' => 'required_with:previous|string|max:500',
            'previous.*.answer' => 'required_with:previous|string|max:8000',
            'user_ref' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $questions = array_values($data['questions']);
        $profile = array_filter(['name' => $data['name'] ?? $user?->name]);

        if (array_key_exists('question_index', $data) && $data['question_index'] !== null) {
            return $this->oneQuestion($data, $questions, $profile, $user?->id);
        }

        return $this->wholeBill($ai, $data, $questions, $profile, $user?->id);
    }

    /**
     * โหมดทีละข้อ — เลนเดียวกับบิลดูดวง 39฿ ในแชท
     *
     * @param  array<int,string>  $questions
     * @param  array<string,string>  $profile
     */
    private function oneQuestion(array $data, array $questions, array $profile, ?int $userId): JsonResponse
    {
        $index = (int) $data['question_index'];
        if (! isset($questions[$index])) {
            return response()->json(['message' => 'ไม่มีคำถามข้อนี้', 'reason_code' => 'bad_index'], 422);
        }

        $userRef = trim((string) ($data['user_ref'] ?? ''));
        if ($userRef === '' && $userId !== null) {
            $userRef = 'tp:'.$userId;
        }

        if (($rejected = $this->rejectQuestion($questions[$index])) !== null) {
            return $rejected;
        }

        // ทราฟฟิค AI รวมของระบบเต็ม — ไม่ยิงเพิ่ม ให้เว็บคืนเงิน (บอทเองก็หยุดถามข้อต่อไปเหมือนกัน)
        if (! LineGatekeeperService::canCallAI('fortune')) {
            return response()->json(['message' => 'ระบบคำทำนายคิวเต็มชั่วคราว', 'reason_code' => 'busy'], 503);
        }

        try {
            $gen = app(FortuneConversationService::class)->answerDeepQuestionForWeb(
                $questions,
                $index,
                (array) ($data['previous'] ?? []),
                $profile ?: null,
                $data['birth_date'] ?? null,
                $userRef,
                self::REQUEST_BUDGET_SEC,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra deep reading (per question) failed', ['index' => $index, 'err' => $e->getMessage()]);

            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        $text = trim($gen['text']);
        if (($bad = $this->badAnswer($text, $gen['complete'], $gen['ai'], $index)) !== null) {
            return $bad;
        }

        return response()->json(['data' => [
            'reading' => $text,
            'question_index' => $index,
            'ai_provider' => $gen['ai']['provider'] ?? null,
            'ai_model' => $gen['ai']['model'] ?? null,
            'tokens_used' => $gen['tokens'],
            'purpose' => $gen['purpose'],
        ]]);
    }

    /**
     * โหมดทั้งบิลในคำขอเดียว (เว็บรุ่นเก่า/แอพ) — พรอมต์รวมเดิม บนคีย์พูลบิลจ่ายเงิน
     *
     * @param  array<int,string>  $questions
     * @param  array<string,string>  $profile
     */
    private function wholeBill(FortuneAIService $ai, array $data, array $questions, array $profile, ?int $userId): JsonResponse
    {
        foreach ($questions as $q) {
            if (($rejected = $this->rejectQuestion($q)) !== null) {
                return $rejected;
            }
        }

        try {
            $result = $ai->withCustomerContext(array_filter([
                'user_id' => $userId,
                'customer_name' => $profile['name'] ?? null,
            ]))
                ->withConfigOverride(['budget_sec' => self::REQUEST_BUDGET_SEC])
                ->generateWithRetryAndFallback(
                    questions: $questions,
                    userProfile: $profile ?: null,
                    userPosts: null,
                    promptTemplate: null,     // ใช้ template จากหลังบ้านเหมือนบอท
                    readingType: 'deep',
                    birthDate: $data['birth_date'] ?? null,
                    userContext: 'jw_deep',
                    purpose: 'prediction_deep',
                );
        } catch (\Throwable $e) {
            Log::warning('Juntra deep reading failed', ['err' => $e->getMessage()]);

            // 503 เพื่อให้ juntraweb รู้ว่า "ระบบไม่พร้อม" แล้วคืนเครดิตลูกค้า
            return response()->json(['message' => 'ระบบคำทำนายไม่พร้อมชั่วคราว'], 503);
        }

        $text = trim(FortuneAIService::stripInternalContextTags((string) ($result['response'] ?? '')));
        if ($text === '') {
            return response()->json(['message' => 'คำทำนายว่างเปล่า'], 503);
        }
        if (($bad = $this->badAnswer($text, true, $result, null)) !== null) {
            return $bad;
        }

        return response()->json(['data' => [
            'reading' => $text,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? null,
        ]]);
    }

    /**
     * ด่านกันเจลเบรคบนคำถามดิบของลูกค้า (ทางนี้ไม่ผ่าน chatWithCustomSystemPrompt) — 422 = เว็บคืนเงิน
     *
     * 🤍 (2026-11-09 owner) เลิกตรวจจับ self-harm/วิกฤตแล้ว — คำถามแบบนั้นทำนายตามปกติ
     *   คงคีย์ crisis ไว้เป็น false เสมอ ให้เว็บรุ่นเก่าที่ยังอ่านคีย์นี้ไม่พัง
     */
    private function rejectQuestion(string $question): ?JsonResponse
    {
        $attack = FortuneAIService::detectAdversarialInput($question);
        if ($attack === null) {
            return null;
        }

        Log::warning('Juntra deep reading: question rejected by adversarial guard', ['attack' => $attack]);

        return response()->json(['message' => 'คำถามนี้แม่หมอทำนายให้ไม่ได้', 'reason_code' => 'question_rejected', 'crisis' => false], 422);
    }

    /**
     * คำตอบที่ห้ามส่งไปขาย — คำปฏิเสธ/หลุดบท/ไม่ครบ → 503 + reason_code (เว็บคืนเงิน)
     *
     * @param  array<string,mixed>  $ai
     */
    private function badAnswer(string $text, bool $complete, array $ai, ?int $index): ?JsonResponse
    {
        $reason = PersonaBreakDetector::reason($text)
            ?? (JuntraSpreadProfiles::looksLikeRefusal($text) ? 'คำปฏิเสธ' : null);

        if ($reason !== null) {
            Log::warning('Juntra deep reading: model answer is not a reading', [
                'index' => $index, 'reason' => $reason,
                'provider' => $ai['provider'] ?? null, 'model' => $ai['model'] ?? null,
                'text' => mb_substr($text, 0, 160),
            ]);

            return response()->json(['message' => 'แม่หมอทำนายไม่สำเร็จ', 'reason_code' => 'model_refused'], 503);
        }

        if (! $complete) {
            Log::warning('Juntra deep reading: answer incomplete after retry', [
                'index' => $index, 'length' => mb_strlen($text), 'model' => $ai['model'] ?? null,
            ]);

            return response()->json(['message' => 'คำทำนายไม่ครบ', 'reason_code' => 'incomplete'], 503);
        }

        return null;
    }
}
