<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🖼️ (2026-05-20 Phase 3b) Image Intent Classifier
 *
 * แยกประเภทรูปที่ลูกค้าส่งมาในระบบ Fortune Bot
 *
 * Use case (user spec 2026-05-20):
 *   "ในระบบการแชทปกติ หรือตอนที่สั่งซื้อเสร็จ ลูกค้าจะมีการส่งรูป หรือสลิป
 *    บอทจะได้แยกได้ถูกว่า ลูกค้าส่งรูปอะไร หรือเป็นเพียงอีโมจิ
 *    (แต่ถ้าส่งรูปรัวๆ จะถือว่าสแปม)"
 *
 * Output enum:
 *   • payment_slip       — สลิปการโอนเงิน → routing ไป SMS payment verification
 *   • fortune_subject    — รูปบุคคล/สิ่งของสำหรับดูดวง (เช่น รูปคนรัก/บ้าน/รถ)
 *   • general_photo      — รูปทั่วไป (วิว/อาหาร/animal) — chat ตอบได้แต่ไม่เกี่ยวดวง
 *   • emoji_sticker      — อีโมจิ/สติ๊กเกอร์ (ไม่ใช่รูปจริง)
 *   • nonsense           — รูปที่ไม่มีความหมาย/junk
 *
 * Provider: Gemini Flash (ราคาถูก/ฟรี — เหมาะ first-pass classification)
 *   ไม่ใช้ OpenAI 5.5 เพราะแพงเกินไปสำหรับ classify
 *
 * Cache: ผลลัพธ์ cache ไว้ 10 นาที (key = sha1(image first 1KB)) — กันเรียก AI ซ้ำๆ
 */
class ImageIntentClassifier
{
    /** Intent enum values */
    public const INTENT_PAYMENT_SLIP = 'payment_slip';

    public const INTENT_FORTUNE_SUBJECT = 'fortune_subject';

    public const INTENT_GENERAL_PHOTO = 'general_photo';

    public const INTENT_EMOJI_STICKER = 'emoji_sticker';

    public const INTENT_NONSENSE = 'nonsense';

    public const INTENT_UNKNOWN = 'unknown';

    /** Default fallback intent ถ้า AI fail */
    public const DEFAULT_INTENT_ON_FAIL = self::INTENT_GENERAL_PHOTO;

    protected FortuneTellingSetting $settings;

    protected FortuneAIService $ai;

    public function __construct(
        ?FortuneTellingSetting $settings = null,
        ?FortuneAIService $ai = null,
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        // ใช้ purpose='image_classify' — admin มี option สร้าง key เฉพาะได้ (fall to any/null ถ้าไม่มี)
        $this->ai = $ai ?? new FortuneAIService($this->settings, 'image_classify');
    }

    /**
     * Classify รูป → intent enum + confidence + reason
     *
     * @param  string  $imageData  URL (http/https) หรือ data:URL
     * @param  string|null  $contextHint  context ของ flow (เช่น 'celtic_active', 'payment_pending', 'chat_normal')
     * @param  bool  $reliableMode  (2026-06-05) true = slip pre-check → ใช้ OpenAI vision (paid, ล่มยาก) ก่อน
     *                              + bypass master image-vision gate (ด่านประหยัดโควต้า SlipOK ต้องทำงานแม้ปิด vision)
     *                              + fallback Gemini ฟรีถ้า OpenAI ล่ม. false = เดิม (Gemini ฟรี, เคารพ gate)
     * @return array{intent: string, confidence: float, reason: string, raw_response: ?string}
     */
    public function classify(string $imageData, ?string $contextHint = null, bool $reliableMode = false): array
    {
        // 🗄️ Cache check — ถ้า classify รูปนี้แล้ว 10 นาทีที่ผ่านมา → return cached
        $cacheKey = $this->buildCacheKey($imageData);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::debug('ImageIntentClassifier: cache hit', [
                'intent' => $cached['intent'] ?? null,
                'cache_key' => $cacheKey,
            ]);

            return $cached;
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($contextHint);

        try {
            $result = null;
            $via = null;

            if ($reliableMode) {
                // 🌟 (2026-06-05, user) slip pre-check ใช้ API เสียเงินที่ล่มยาก — OpenAI gpt-5.x vision ก่อน
                //   + bypass_vision_gate (master toggle enable_image_vision=false ปิด classifier → fail-open
                //   ทุกภาพทะลุไป SlipOK เปลืองโควต้า). cache 10 นาที กันเรียกซ้ำ
                $cfg = ['temperature' => 0.2, 'max_tokens' => 200, 'bypass_vision_gate' => true];
                try {
                    $result = $this->ai->chatWithImage($imageData, $systemPrompt, $userPrompt, $cfg);
                    if ($result !== null && ! empty($result['response'])) {
                        $via = 'openai';
                    }
                } catch (\Throwable $e) {
                    Log::debug('ImageIntentClassifier: OpenAI vision ล้มเหลว → fallback Gemini', [
                        'error' => $e->getMessage(),
                    ]);
                }

                // OpenAI ไม่พร้อม/ตอบว่าง → fallback Gemini ฟรี (ยัง bypass gate)
                if ($result === null || empty($result['response'])) {
                    $result = $this->ai->chatWithImageGemini($imageData, $systemPrompt, $userPrompt, null, null, $cfg);
                    $via = ($result !== null && ! empty($result['response'])) ? 'gemini_fallback' : null;
                }
            } else {
                // เดิม: Gemini Flash ฟรี (เคารพ master image-vision gate) — ใช้กับ webhook image-intent routing
                $result = $this->ai->chatWithImageGemini(
                    $imageData,
                    $systemPrompt,
                    $userPrompt,
                    null,           // apiKey — Pool resolve
                    null,           // model — Pool resolve
                    [
                        'temperature' => 0.2,   // ต่ำ — ต้องการ output structured JSON
                        'max_tokens' => 200,
                    ]
                );
                $via = ($result !== null && ! empty($result['response'])) ? 'gemini' : null;
            }

            if ($result === null || empty($result['response'])) {
                Log::warning('ImageIntentClassifier: vision returned null/empty', [
                    'context_hint' => $contextHint,
                    'reliable_mode' => $reliableMode,
                ]);

                return $this->failResult('AI vision unavailable');
            }

            $parsed = $this->parseAiResponse((string) $result['response']);

            // Cache 10 นาที
            Cache::put($cacheKey, $parsed, now()->addMinutes(10));

            Log::info('ImageIntentClassifier: สำเร็จ', [
                'intent' => $parsed['intent'],
                'confidence' => $parsed['confidence'],
                'context_hint' => $contextHint,
                'via' => $via,
                'tokens_used' => $result['tokens_used'] ?? 0,
                'model' => $result['model'] ?? null,
            ]);

            return $parsed;
        } catch (\Throwable $e) {
            Log::warning('ImageIntentClassifier: exception', [
                'error' => $e->getMessage(),
                'context_hint' => $contextHint,
            ]);

            return $this->failResult($e->getMessage());
        }
    }

    /**
     * Build system prompt — สอน AI ให้ตอบ JSON enum
     */
    protected function buildSystemPrompt(): string
    {
        return "คุณเป็น AI Image Classifier สำหรับ Fortune Bot (แม่หมอจันทรา)\n"
            ."หน้าที่: ดูรูปแล้วจัดประเภท 1 ใน 5 ค่า ตอบเป็น JSON เท่านั้น\n\n"
            ."ประเภทที่เป็นไปได้:\n"
            ."• payment_slip — สลิปการโอนเงิน/โอนแบงค์/PromptPay (มีเลขจำนวนเงิน, เวลา, ชื่อบัญชี)\n"
            ."• fortune_subject — รูปบุคคล/สถานที่/สิ่งของที่ใช้ดูดวง (รูปคน, คู่รัก, บ้าน, รถ, ลายมือ)\n"
            ."• general_photo — รูปทั่วไป (วิว, อาหาร, สัตว์, หน้าจอ, มีม) — ไม่เกี่ยวดวง\n"
            ."• emoji_sticker — อีโมจิ/สติ๊กเกอร์ LINE/Facebook + รูปนิ้วโป้ง/ยกนิ้ว/ไลค์/รูปรีแอคการ์ตูน (ไม่ใช่ภาพถ่ายจริง/ไม่ใช่สลิป)\n"
            ."• nonsense — รูปดำ/ขาว/ไม่ชัด/ไม่มีเนื้อหา/junk\n\n"
            ."กฎ:\n"
            ."1. ตอบเป็น JSON เท่านั้น — ห้ามมี markdown, ห้ามมีคำอธิบายภาษาธรรมชาตินอก JSON\n"
            ."2. confidence = 0.0-1.0 (ความมั่นใจ)\n"
            ."3. reason = ภาษาไทยสั้น 1 ประโยค บอกเหตุผลที่จัดประเภทนี้\n\n"
            ."Schema:\n"
            ."{\"intent\": \"<enum>\", \"confidence\": <float>, \"reason\": \"<string>\"}\n\n"
            ."ตัวอย่าง:\n"
            ."{\"intent\": \"payment_slip\", \"confidence\": 0.95, \"reason\": \"เห็นจำนวนเงิน 99 บาท + เวลา + ชื่อบัญชี SCB\"}\n"
            ."{\"intent\": \"fortune_subject\", \"confidence\": 0.85, \"reason\": \"รูปผู้ชาย น่าจะส่งให้แม่หมอดูเรื่องคู่\"}\n"
            ."{\"intent\": \"general_photo\", \"confidence\": 0.70, \"reason\": \"รูปอาหาร ไม่เกี่ยวกับการดูดวง\"}\n"
            ."{\"intent\": \"emoji_sticker\", \"confidence\": 0.90, \"reason\": \"สติ๊กเกอร์ LINE การ์ตูนน่ารัก\"}\n";
    }

    /**
     * Build user prompt — context ของ session
     */
    protected function buildUserPrompt(?string $contextHint): string
    {
        $base = 'ดูรูปนี้แล้วจัดประเภท ตอบ JSON ตาม schema';

        if ($contextHint === null || $contextHint === '') {
            return $base;
        }

        $hintMap = [
            'celtic_active' => '(บริบท: ลูกค้าอยู่ใน Celtic 99 active session — รูปอาจเป็นรูปคน/สถานที่ที่ดูดวง)',
            'payment_pending' => '(บริบท: ลูกค้ารอจ่ายเงิน — รูปอาจเป็นสลิปการโอน)',
            'chat_normal' => '(บริบท: ลูกค้าคุยปกติ — รูปอาจเป็นอะไรก็ได้)',
            'free_card' => '(บริบท: ลูกค้าทำนายฟรี — รูปอาจเป็นรูปคน/สิ่งของที่ดูดวง)',
        ];

        $hint = $hintMap[$contextHint] ?? "(บริบท: {$contextHint})";

        return $base."\n".$hint;
    }

    /**
     * Parse AI response → structured array
     *
     * AI ควรตอบ JSON แต่ก็เผื่อ markdown/extra text → strip ก่อน
     */
    protected function parseAiResponse(string $response): array
    {
        // Strip markdown code fence ถ้ามี
        $cleaned = preg_replace('/```(?:json)?\s*|\s*```/u', '', $response);
        $cleaned = trim($cleaned);

        // Extract JSON block (เผื่อมี text นอก JSON)
        if (preg_match('/\{[^{}]*"intent"[^{}]*\}/s', $cleaned, $m)) {
            $cleaned = $m[0];
        }

        $data = json_decode($cleaned, true);
        if (! is_array($data) || ! isset($data['intent'])) {
            Log::warning('ImageIntentClassifier: parse JSON failed', [
                'response_preview' => mb_substr($response, 0, 200),
            ]);

            return $this->failResult('parse JSON failed', $response);
        }

        $intent = (string) $data['intent'];
        // Validate enum
        $validIntents = [
            self::INTENT_PAYMENT_SLIP,
            self::INTENT_FORTUNE_SUBJECT,
            self::INTENT_GENERAL_PHOTO,
            self::INTENT_EMOJI_STICKER,
            self::INTENT_NONSENSE,
        ];

        if (! in_array($intent, $validIntents, true)) {
            Log::warning('ImageIntentClassifier: invalid intent enum', [
                'returned_intent' => $intent,
                'valid' => $validIntents,
            ]);
            // Fallback to general_photo (safest)
            $intent = self::DEFAULT_INTENT_ON_FAIL;
        }

        return [
            'intent' => $intent,
            'confidence' => (float) ($data['confidence'] ?? 0.5),
            'reason' => (string) ($data['reason'] ?? ''),
            'raw_response' => $response,
        ];
    }

    /**
     * Build cache key — ใช้ sha1 ของ 1KB แรกของรูป (กัน hash ทั้งรูป = slow)
     */
    protected function buildCacheKey(string $imageData): string
    {
        // ถ้าเป็น data URL — hash 1KB แรก
        // ถ้าเป็น URL — hash URL ตรง ๆ (FB CDN URL จะ unique ต่อรูป)
        $sample = mb_substr($imageData, 0, 1024);

        return 'img_classify:'.sha1($sample);
    }

    /**
     * Fail result — fallback เป็น general_photo (ปลอดภัยสุด — chat path จะตอบทั่วไป)
     */
    protected function failResult(string $reason, ?string $rawResponse = null): array
    {
        return [
            'intent' => self::DEFAULT_INTENT_ON_FAIL,
            'confidence' => 0.0,
            'reason' => "fallback: {$reason}",
            'raw_response' => $rawResponse,
        ];
    }
}
