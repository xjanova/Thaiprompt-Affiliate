<?php

namespace App\Services\Fortune;

use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Log;

/**
 * 🎚️ FortuneModelRouter (2026-08-17)
 *
 * แหล่งเดียวที่ตัดสินว่า "เทิร์นนี้ควรใช้โมเดลไหน" สำหรับบริการที่ลูกค้าจ่ายเงินแล้ว
 *
 * นโยบายที่ owner กำหนด:
 *   🪬 โหมดดูคุณไสย์      → sol เสมอ ทุกเทิร์น (ต้องการเหตุและผลที่ถูกต้อง)
 *   🧠 คำถามยาก/หนัก      → sol เฉพาะเทิร์นนั้น
 *   🌙 อื่นๆ               → luna (ถูกกว่า 25 เท่า และพอสำหรับคำถามทั่วไป)
 *
 * ทำไมต้องรวมไว้ที่เดียว: กฎ "คำถามยาก" มีกับดักซ่อนอยู่ (ดู isHardQuestion)
 * ถ้าปล่อยให้ Celtic กับ Deep 39 เขียนกันคนละชุด จะเพี้ยนคนละทางแน่นอน
 *
 * 🎛️ (2026-10-30, owner) ชื่อโมเดลตามที่ตั้งบน key ในหน้าแอดมินแล้ว ไม่ยึดชื่อในโค้ดอีก
 *   เดิมเปลี่ยน key ไป gen 6 แล้ว เทิร์นคุณไสย์/คำถามยาก/คุยต่อ 39 ยังวิ่ง gpt-5.6 เงียบๆ
 *   ตัวแรง = โมเดลของ key OpenAI purpose=sensitive · ตัวหลัก = โมเดลของ key OpenAI เลนทำนาย Deep
 */
class FortuneModelRouter
{
    /** ค่าสำรองของโมเดลตัวแรง (คุณไสย์ + คำถามยาก) — ใช้เมื่อไม่มี key ให้อ่าน ปกติเรียกผ่าน strongModel() */
    public const MODEL_STRONG = 'gpt-5.6-sol';

    /** ค่าสำรองของโมเดลหลัก — ใช้เมื่อไม่มี key ให้อ่าน ปกติเรียกผ่าน defaultModel() */
    public const MODEL_DEFAULT = 'gpt-5.6-luna';

    /** สั้นกว่านี้ = ทัก/ตอบรับ ไม่ใช่คำถามยาก → ข้าม detector ไม่ต้องเสียค่าตรวจ */
    protected const MIN_QUESTION_LEN = 8;

    /** เกณฑ์ mood/complexity ที่ถือว่า "ยาก" (ตรงกับสเปกเดิมของกลไก escalate) */
    protected const HARD_THRESHOLD = 4;

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * 🃏 Celtic 99฿ — คืน modelOverrides ที่ส่งเข้า generateWithRetryAndFallback ได้ตรงๆ
     *
     * ⚠️ ต้องส่งเป็นพารามิเตอร์ `modelOverrides` เท่านั้น — ห้ามตั้ง $model บน service ก่อนเรียก
     *    เพราะ generateWithRetryAndFallbackInner สร้าง $allKeys ใหม่จาก getAllAvailableKeys()
     *    ซึ่งแต่ละ key พก model ของตัวเองมา → ค่าที่ตั้งไว้ก่อนหน้าถูกเขียนทับเงียบๆ
     *
     * @return array<string, string>|null null = ใช้โมเดลปกติของ key
     */
    public function celticOverrides(FortuneReading $reading, ?string $userQuestion = null): ?array
    {
        return $this->isStrongTurn($reading, $userQuestion, 'celtic')
            ? ['openai' => self::strongModel()]
            : null;
    }

    /**
     * 🌟 Deep 39฿ Pro Session — คืน "ชื่อโมเดล" ตรงๆ (path นี้ระบุโมเดลเอง ไม่ได้ผ่าน pool overrides)
     */
    public function proSessionModel(FortuneReading $reading, ?string $userQuestion = null): string
    {
        return $this->isStrongTurn($reading, $userQuestion, 'deep')
            ? self::strongModel()
            : self::defaultModel();
    }

    /**
     * 💪 โมเดลตัวแรง — ตามที่ตั้งบน key OpenAI purpose=sensitive
     */
    public static function strongModel(): string
    {
        return self::modelFromKey('sensitive', self::MODEL_STRONG);
    }

    /**
     * 🌙 โมเดลหลัก — ตามที่ตั้งบน key OpenAI เลนทำนาย Deep
     */
    public static function defaultModel(): string
    {
        return self::modelFromKey('prediction_deep', self::MODEL_DEFAULT);
    }

    /**
     * อ่านโมเดลจาก key ที่ priority สูงสุดของเลนนั้น
     *
     * ⚠️ นับเฉพาะ provider openai เพราะปลายทางส่งเข้าเลน OpenAI (`['openai' => ...]`)
     *    และไม่ใช้ resolveModel() — key ที่ไม่ได้ตั้งโมเดลจะได้ตัวแรกของค่าย ซึ่งไม่ใช่ "ตัวแรง" ที่ตั้งใจ
     * ⚠️ ดู key ที่ pool เลือกได้จริง (available) ก่อน — key ที่ critical/ยังไม่ผ่านเทส/ถูกพักชั่วคราว pool จะข้ามไป
     *    ถ้าเอาโมเดลของตัวนั้นมาส่งผ่าน key อื่น อาจคนละบัญชีแล้วเจอ model_not_found ทั้งที่ลูกค้าจ่ายเงินแล้ว
     *    ไม่มี key พร้อมเลยค่อยอ่านจาก key ที่เปิดอยู่ เพราะยังตรงกับที่แอดมินตั้งมากกว่าชื่อในโค้ด
     */
    protected static function modelFromKey(string $purpose, string $fallback): string
    {
        try {
            $keys = AiApiKey::query()
                ->forProvider('openai')
                ->forPurpose($purpose)
                ->orderByPriority();

            $key = (clone $keys)->available()->first() ?? $keys->active()->first();

            return $key?->model ?: $fallback;
        } catch (\Throwable $e) {
            // non-blocking — อ่าน key ไม่ได้ ก็ใช้ค่าสำรอง (ลูกค้าต้องได้คำตอบเสมอ)
            Log::warning('FortuneModelRouter: อ่านโมเดลจาก key ไม่สำเร็จ (ใช้ค่าสำรอง)', [
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * ตรรกะกลาง — เทิร์นนี้ต้องใช้โมเดลตัวแรงไหม
     */
    protected function isStrongTurn(FortuneReading $reading, ?string $userQuestion, string $context): bool
    {
        try {
            // 1) 🪬 โหมดดูคุณไสย์ → sol เสมอ (เฉพาะ Celtic — Deep ไม่มีโหมดนี้)
            if ($context === 'celtic' && app(\App\Services\CelticCrossService::class)->isBlackMagicModeForced($reading)) {
                return true;
            }

            // 2) 🧠 คำถามยาก → sol เฉพาะเทิร์นนี้
            if ($this->isHardQuestion($reading, $userQuestion, $context)) {
                return true;
            }
        } catch (\Throwable $e) {
            // non-blocking — ตัดสินใจไม่ได้ ก็ใช้โมเดลปกติ (ลูกค้าต้องได้คำตอบเสมอ)
            Log::warning('FortuneModelRouter: เลือกโมเดลไม่สำเร็จ (ใช้ค่าปกติ)', [
                'reading_id' => $reading->id,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * คำถามนี้ "ยาก/หนัก" พอที่จะยกระดับโมเดลไหม
     *
     * ⚠️ กับดักที่เจอจริง (2026-08-17) — **ห้ามใช้ธง is_sensitive เดี่ยวๆ เป็นตัวตัดสิน**
     *    FortuneSensitivityDetector เป็น hybrid = heuristic + classifier(Groq)
     *    แต่ prod **ไม่มี key Groq ใน pool เลย** → classifier ยิงไม่ได้ ตกกลับมา heuristic ล้วน
     *    ซึ่งให้ confidence สูงสุด 40 ขณะที่ธง is_sensitive ต้องการ ≥ 80
     *    → หัวข้อหนักไม่มีทางติดธง ทั้งที่ detector จับ topic ได้แล้ว
     *    (2026-10-06: classifier ยืมเส้นแชทได้แล้ว แต่ล้มเมื่อไรก็เหลือ heuristic ล้วน — กฎนี้ยังต้องใช้)
     *    วัดจริง: "แม่ป่วยหนัก หมอบอกอาจไม่รอด เครียดจนไม่อยากอยู่" และ
     *             "ถ้าหนูฆ่าตัวตายจะได้ไปเจอพ่อไหม" → is_sensitive=false ทั้งคู่ (complexity=4)
     *    ดังนั้นใช้ mood_level / complexity ที่ heuristic ให้มาโดยตรง เกณฑ์ ≥ 4
     */
    protected function isHardQuestion(FortuneReading $reading, ?string $userQuestion, string $context): bool
    {
        $q = trim((string) $userQuestion);
        if ($q === '' || mb_strlen($q) < self::MIN_QUESTION_LEN) {
            return false;
        }

        $uid = (string) ($reading->facebook_user_id ?? $reading->line_user_id ?? '');

        $detection = (new FortuneSensitivityDetector($this->settings))->detect($q, [
            'user_id' => $uid,
            'has_active_paid_reading' => true, // ถึงตรงนี้ = จ่ายแล้วเสมอ
            'channel_context' => $context === 'celtic' ? 'celtic' : 'paid_prediction',
        ]);

        $mood = (int) ($detection['mood_level'] ?? 1);
        $complexity = (int) ($detection['complexity'] ?? 1);

        if (empty($detection['is_sensitive']) && $mood < self::HARD_THRESHOLD && $complexity < self::HARD_THRESHOLD) {
            return false;
        }

        // 🛡️ เพดานกันบานปลาย — ใช้ตัวนับเดิมของ FortuneSensitiveBudgetGuard (5 ครั้ง/คน/วัน)
        //   หมายเหตุ: บันทึกเป็น "จำนวนครั้ง" ไม่ได้บวกยอดบาท เพราะรู้ต้นทุนหลังยิงเสร็จเท่านั้น
        //   ขอบเขตค่าใช้จ่ายถูกคุมด้วยโควต้าคำถามของบิลอยู่แล้ว
        if ($uid !== '') {
            $platform = $reading->platform ?? (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
            $guard = new FortuneSensitiveBudgetGuard($this->settings);

            if (! ($guard->canUse($platform, $uid)['allowed'] ?? false)) {
                Log::info('FortuneModelRouter: คำถามยากแต่ชนเพดาน → ใช้โมเดลปกติต่อ', [
                    'reading_id' => $reading->id,
                    'context' => $context,
                ]);

                return false;
            }

            $guard->recordUse($platform, $uid, 0);
        }

        Log::info('FortuneModelRouter: คำถามยาก → ยกระดับเป็นโมเดลตัวแรง', [
            'model' => self::strongModel(),
            'reading_id' => $reading->id,
            'context' => $context,
            'mood_level' => $mood,
            'complexity' => $complexity,
            'is_sensitive' => ! empty($detection['is_sensitive']),
            'reasons' => $detection['reasons'] ?? [],
            'detection_used' => $detection['detection_used'] ?? null,
        ]);

        return true;
    }
}
