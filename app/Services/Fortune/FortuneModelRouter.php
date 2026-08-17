<?php

namespace App\Services\Fortune;

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
 */
class FortuneModelRouter
{
    /** โมเดลตัวแรง — ใช้กับคุณไสย์ + คำถามยาก */
    public const MODEL_STRONG = 'gpt-5.6-sol';

    /** โมเดลหลัก — ถูกและพอสำหรับคำถามทั่วไป */
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
        $model = $this->pickModel($reading, $userQuestion, 'celtic');

        return $model === self::MODEL_STRONG ? ['openai' => self::MODEL_STRONG] : null;
    }

    /**
     * 🌟 Deep 39฿ Pro Session — คืน "ชื่อโมเดล" ตรงๆ (path นี้ระบุโมเดลเอง ไม่ได้ผ่าน pool overrides)
     */
    public function proSessionModel(FortuneReading $reading, ?string $userQuestion = null): string
    {
        return $this->pickModel($reading, $userQuestion, 'deep');
    }

    /**
     * ตรรกะกลาง — เลือกโมเดลของเทิร์นนี้
     */
    protected function pickModel(FortuneReading $reading, ?string $userQuestion, string $context): string
    {
        try {
            // 1) 🪬 โหมดดูคุณไสย์ → sol เสมอ (เฉพาะ Celtic — Deep ไม่มีโหมดนี้)
            if ($context === 'celtic' && app(\App\Services\CelticCrossService::class)->isBlackMagicModeForced($reading)) {
                return self::MODEL_STRONG;
            }

            // 2) 🧠 คำถามยาก → sol เฉพาะเทิร์นนี้
            if ($this->isHardQuestion($reading, $userQuestion, $context)) {
                return self::MODEL_STRONG;
            }
        } catch (\Throwable $e) {
            // non-blocking — ตัดสินใจไม่ได้ ก็ใช้โมเดลปกติ (ลูกค้าต้องได้คำตอบเสมอ)
            Log::warning('FortuneModelRouter: เลือกโมเดลไม่สำเร็จ (ใช้ค่าปกติ)', [
                'reading_id' => $reading->id,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }

        return self::MODEL_DEFAULT;
    }

    /**
     * คำถามนี้ "ยาก/หนัก" พอที่จะยกระดับโมเดลไหม
     *
     * ⚠️ กับดักที่เจอจริง (2026-08-17) — **ห้ามใช้ธง is_sensitive เดี่ยวๆ เป็นตัวตัดสิน**
     *    FortuneSensitivityDetector เป็น hybrid = heuristic + classifier(Groq)
     *    แต่ prod **ไม่มี key Groq ใน pool เลย** → classifier ยิงไม่ได้ ตกกลับมา heuristic ล้วน
     *    ซึ่งให้ confidence สูงสุด 40 ขณะที่ธง is_sensitive ต้องการ ≥ 80
     *    → หัวข้อหนักไม่มีทางติดธง ทั้งที่ detector จับ topic ได้แล้ว
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

        Log::info('FortuneModelRouter: คำถามยาก → ยกระดับเป็น '.self::MODEL_STRONG, [
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
