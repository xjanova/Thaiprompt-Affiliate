<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * ⏳ (2026-09-02 FTU-260902-V9628) รอลูกค้า "เล่าให้จบ" ก่อนรวบตอบ
 *
 * ต้นตอ: ลูกค้าเล่าเรื่องยาวเป็นชิ้นๆ ห่างกัน 15-47 วินาที (ทั้งหมดเป็นการเล่า/ระบาย
 * ไม่ใช่คำถามสักชิ้น) แต่ settle window ตั้งไว้ 10 วิ → ทุกชิ้นถูกนับเป็นคำถามใหม่
 * บอทตอบคำทำนายเต็ม 500-1,300 ตัวอักษร 9 ครั้งใน 25 นาที และเธอพิมพ์ต่อ **15 วินาที**
 * หลังบอทตอบ (อ่านไม่ทันแน่นอน) → owner: "ไม่ใช่ ไม่มืออาชีพ"
 *
 * แนวคิด — แยก "คนถามข้อเดียว" ออกจาก "คนกำลังเล่ายาว" ด้วยเวลาที่ใช้อ่านคำตอบ:
 *   • ตอบไปยาว 800 ตัวอักษร แล้วลูกค้าพิมพ์กลับใน 15 วิ = อ่านไม่ทัน = ยังเล่าไม่จบ
 *   • ตอบไป 200 ตัวอักษร แล้วลูกค้าพิมพ์กลับใน 2 นาที = อ่านแล้ว = ถามจริง
 * นับเป็น "สตรีค" (เล่าต่อ +1 / อ่านแล้ว −1) ครบ 2 = เข้าโหมดเล่ายาว
 *   → ขยายหน้าต่างรอ + ตอบสั้นแบบรับฟัง (คำวิเคราะห์เต็มรอตอนเธอหยุดจริง)
 *
 * ⚠️ ห้ามใช้ "ช่องว่างระหว่างข้อความ" เฉยๆ เป็นตัวตัดสิน — ลูกค้าที่ตั้งใจถามก็ตอบเร็วได้
 *    ตัวแยกที่แม่นคือ "เร็วเกินกว่าจะอ่านคำตอบที่เพิ่งส่งไปจบ"
 */
trait QaSettleTrait
{
    /**
     * ค่าคงที่ของกลไกนี้
     *
     * ⚠️ ห้ามเปลี่ยนเป็น `const` ใน trait — **constant ใน trait ต้องใช้ PHP 8.2+**
     *    แต่ composer.json ประกาศ `"php": "^8.1"` (สินค้าถูกติดตั้งบนเครื่องลูกค้าด้วย)
     *    ใส่เป็น const เมื่อไร เครื่องที่รัน 8.1 จะ fatal ตั้งแต่ parse
     *
     *   • windowAt     สตรีคที่เริ่ม "ขยายหน้าต่างรอ" — ผู้ใช้ไม่เห็นความต่าง แค่รอนานขึ้น
     *                  ⇒ ตั้งไว 1 ได้ ความเสี่ยงต่ำ
     *   • briefAt      สตรีคที่เริ่ม "ตอบสั้นแบบรับฟัง" — ลูกค้าเห็นชัด ต้องมั่นใจกว่า
     *                  ⇒ ต้องเร็วเกินอ่าน 2 ครั้งติด กันคนที่ถามต่อจริงๆ โดนตอบสั้นทั้งที่จ่าย 99฿
     *   • charsPerSec  ความเร็วอ่านไทยบนมือถือ — ผู้สูงวัยอ่านคำทำนายหนาแน่นช้ากว่านี้อีก
     *   • minSec/maxSec ขอบเวลาอ่านที่ยอมรับ (วินาที)
     *   • decayFactor  จะคลายสตรีคต่อเมื่อเว้นนานกว่า readSec × ตัวนี้ = "หยุดจริง" ไม่ใช่แค่ช้าลงนิดหน่อย
     *                  (ตั้ง 1 เมื่อไร สตรีคจะคลายทุกครั้งที่ลูกค้าคิดนาน แล้วโหมดรับฟังจะไม่เคยติด)
     *
     * @return array{windowAt:int, briefAt:int, charsPerSec:int, minSec:int, maxSec:int, decayFactor:int}
     */
    protected function qaRambleTuning(): array
    {
        return [
            'windowAt' => 1,
            'briefAt' => 2,
            'charsPerSec' => 4,
            'minSec' => 10,
            'maxSec' => 120,
            'decayFactor' => 2,
        ];
    }

    /**
     * 📝 จดว่าเพิ่งส่งคำตอบไปยาวเท่าไร — ใช้คำนวณ "อ่านทันไหม" ในเทิร์นถัดไป
     */
    public function qaNoteAnswerSent(FortuneReading $reading, string $answerText): void
    {
        try {
            $reading->setConversationState('qa_answer_at', now()->toIso8601String());
            $reading->setConversationState('qa_answer_len', mb_strlen(strip_tags($answerText)));
        } catch (\Throwable $e) {
            // non-blocking — สถิติเพื่อจับจังหวะเท่านั้น ห้ามทำให้ flow ตอบคำถามล้ม
        }
    }

    /**
     * 🔎 อัปเดตสตรีค "เล่ายาว" ตอนลูกค้าพิมพ์เข้ามาในเส้น Q&A
     *
     * @return int สตรีคล่าสุด (0 = ปกติ)
     */
    public function qaTrackRamble(FortuneReading $reading): int
    {
        try {
            $answerAt = $reading->getConversationState('qa_answer_at');
            $streak = (int) ($reading->getConversationState('qa_ramble_streak', 0) ?: 0);

            // ยังไม่เคยตอบอะไรไป = ไม่มีอะไรให้อ่าน → ไม่ตัดสิน
            if (! $answerAt) {
                return $streak;
            }

            $tune = $this->qaRambleTuning();
            $gap = now()->diffInSeconds(\Carbon\Carbon::parse($answerAt), true);
            $len = (int) ($reading->getConversationState('qa_answer_len', 0) ?: 0);

            $readSec = (int) round($len / $tune['charsPerSec']);
            $readSec = max($tune['minSec'], min($tune['maxSec'], $readSec));

            // เร็วกว่าเวลาที่ควรใช้อ่าน = ยังเล่าไม่จบ → +1
            // ช้ากว่ามาก (readSec × decayFactor) = หยุดจริงแล้ว → คลายลงทีละขั้น
            // ระหว่างกลาง = คงไว้ (อ่านผ่านๆ แล้วเล่าต่อ ยังนับว่าอยู่ในโหมดเล่า)
            //   ⚠️ ถ้าคลายทุกครั้งที่ gap >= readSec สตรีคจะไม่มีวันถึง 2 กับคนที่เล่าสลับหยุด
            //      (ทดสอบกับไทม์ไลน์จริง FTU-260902-V9628 แล้ว — แบบคลายไวลดคำตอบได้แค่ 1 ครั้ง)
            if ($gap < $readSec) {
                $streak = min(5, $streak + 1);
            } elseif ($gap >= $readSec * $tune['decayFactor']) {
                $streak = max(0, $streak - 1);
            }

            $reading->setConversationState('qa_ramble_streak', $streak);

            return $streak;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * ลูกค้ารายนี้ "กำลังเล่ายาว" อยู่ไหม (ระดับที่พอจะขยายหน้าต่างรอ)
     */
    public function qaIsRambling(FortuneReading $reading): bool
    {
        return (int) ($reading->getConversationState('qa_ramble_streak', 0) ?: 0)
            >= $this->qaRambleTuning()['windowAt'];
    }

    /**
     * ⏱️ หน้าต่างรอที่ควรใช้กับลูกค้ารายนี้
     *
     * @param  int  $baseSec  ค่าพื้นฐานของเส้นนั้น (Celtic / ProSession)
     * @return int วินาทีที่จะรอ — **0 = ห้าม buffer ให้ตอบทันที** (caller ต้องเช็ค > 0)
     */
    public function qaSettleWindow(FortuneReading $reading, int $baseSec): int
    {
        $window = $baseSec;

        if ($this->qaIsRambling($reading)) {
            $ramble = (int) ($this->settings->qa_settle_ramble_seconds ?? 50);

            // ตั้ง 0 = ไม่อยากให้ขยาย → คงพฤติกรรมเดิม
            $window = $ramble > 0 ? max($baseSec, $ramble) : $baseSec;
        }

        return $this->qaClampToRemainingWindow($reading, $window);
    }

    /**
     * 🛟 (2026-09-05) หน้าต่างรอต้อง "ยิงทันหน้าต่างคุย" — ไม่งั้นคำถามหายเงียบ
     *
     * เคสจริง FTU-260905-N3337 (reading 12386, ปราณี):
     *   21:37:29  ตอบคำถามที่ 7 · log remaining_min = 2   ⇒ หน้าต่างหมด ~21:39:29
     *   21:38:37  ลูกค้ากดปุ่มคำถามแนะนำข้อ 2 → settle-buffer 50 วิ (rambling)
     *   21:39:30  ProcessBufferedCelticMessageJob: session expired → skip   ⇐ ช้าไป 1 วินาที
     *   ⇒ ลูกค้าจ่าย 99 กดปุ่มที่ *ระบบเสนอเอง* แล้วได้ความเงียบ ไม่มีข้อความบอกว่าทำไม
     *
     * ต้นเหตุคือ **นาฬิกาสองตัวไม่คุยกัน** — `settle_sec` (debounce) กับหน้าต่างคุย (`canAskMoreCeltic`
     * / `isProSessionActive`) · ด่าน "หมดเวลา" อยู่ที่ job ตอน flush ซึ่งสายเกินจะตัดสินใจอะไรได้แล้ว
     * ⇒ ต้องเช็คตอน **เข้าคิว** ไม่ใช่ตอน flush
     *
     * กฎ: เหลือเวลาน้อยกว่าที่จะรอ → **หดหน้าต่าง** ; หดจนไม่เหลือ → คืน 0 = ยิงทันที
     *   ยอมเสีย debounce ดีกว่าเสียคำถามของคนที่จ่ายเงินมาแล้ว
     *
     * ⚠️ ห้ามแก้ด้วยการยืดหน้าต่างคุยเฉย ๆ — คนละปัญหา
     */
    protected function qaClampToRemainingWindow(FortuneReading $reading, int $window): int
    {
        try {
            $remaining = $reading->qaRemainingSeconds();
        } catch (\Throwable $e) {
            return $window; // อ่านเวลาที่เหลือไม่ได้ → คงพฤติกรรมเดิม (non-blocking)
        }

        // null = ไม่มีเส้นตายที่รู้จัก (ยังไม่เริ่มจับเวลา / ไม่จำกัด) → ไม่ต้องหด
        if ($remaining === null) {
            return $window;
        }

        // เผื่อเวลาให้ job ตื่น + queue หน่วง — flush ต้องเกิด *ก่อน* เส้นตาย ไม่ใช่พอดีเป๊ะ
        $usable = $remaining - $this->qaSettleDeadlineGuardSeconds();

        if ($usable >= $window) {
            return $window; // เวลาเหลือเฟือ → ไม่แตะ
        }

        $clamped = max(0, $usable);

        \Log::info('QaSettle: หดหน้าต่างรอให้ทันเส้นตายหน้าต่างคุย', [
            'reading_id' => $reading->id,
            'window_was' => $window,
            'window_now' => $clamped,
            'remaining_sec' => $remaining,
            'immediate' => $clamped === 0,
        ]);

        return $clamped;
    }

    /**
     * กันชนก่อนเส้นตาย (วินาที) — flush ต้องเกิดก่อนเส้นตาย ไม่ใช่พอดีเป๊ะ
     *
     * job dispatch ด้วย delay = window+1 แล้วยังต้องรอ queue ตื่น + job เช็ค isSettled ก่อน flush
     * 20 วินาทีเผื่อพอสำหรับ worker ที่ยุ่ง โดยไม่กิน debounce ตอนเวลายังเหลือเยอะ
     * (เวลาเหลือเยอะ = ไม่ถูกหดเลย ดู qaClampToRemainingWindow)
     *
     * ⚠️ ต้องเป็นเมธอด **ห้ามเป็น const ใน trait** — const ใน trait ใช้ได้ตั้งแต่ PHP 8.2
     *    แต่โปรเจกต์นี้รองรับ PHP 8.1 ขึ้นไป
     */
    protected function qaSettleDeadlineGuardSeconds(): int
    {
        return 20;
    }

    /**
     * เพดานแข็ง — นับจากข้อความแรกในชุด ครบแล้วต้องตอบแม้ลูกค้ายังพิมพ์อยู่
     */
    public function qaSettleMaxSeconds(): int
    {
        $max = (int) ($this->settings->qa_settle_max_seconds ?? 180);

        return $max > 0 ? $max : 180;
    }

    /**
     * 💬 โชว์ "จุดสามจุดกำลังพิมพ์" ระหว่างที่บอทนิ่งรอ
     *
     * owner 2026-09-02: ระหว่างรอให้เห็นแค่จุดสามจุด **ห้ามเพิ่มข้อความในแชท**
     * (กำลังแก้ปัญหา "บอทพูดเยอะเกิน" อยู่ — เพิ่มกล่องอีกก็ผิดโจทย์)
     *
     * 💸 LINE ใช้ showLoadingAnimation — ฟรี ไม่กินโควต้า push และไม่กิน replyToken
     *    ⛔ ห้ามเปลี่ยนเป็น push เด็ดขาด (.claude/LINE_MESSAGING_RULES.md ข้อ 1)
     */
    public function qaSendTypingHint(FortuneReading $reading, int $seconds = 30): void
    {
        try {
            $platform = $reading->platform;
            $candidate = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
            if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
                $platform = preg_match('/^U[a-f0-9]{32}$/i', $candidate) ? 'line' : 'facebook';
            }

            if ($platform === 'line') {
                $userId = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
                if ($userId === '') {
                    return;
                }
                // LINE รับ 5-60 วินาที (ปัดเป็นช่วงละ 5 วิ ตาม API)
                app(\App\Services\LineFortuneService::class)
                    ->showLoadingAnimation($userId, max(5, min(60, $seconds)));

                return;
            }

            $psid = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
            if ($psid === '') {
                return;
            }
            app(\App\Services\FacebookWebhookService::class)->sendTypingIndicator($psid, true);
        } catch (\Throwable $e) {
            // non-blocking — จุดสามจุดส่งไม่ออกต้องไม่ทำให้คำตอบลูกค้าหาย
            Log::debug('QA settle: typing hint ส่งไม่สำเร็จ (ไม่บล็อก)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🤫 คำสั่งเสริมสำหรับ AI ตอนลูกค้ายังเล่าไม่จบ — ตอบสั้นแบบรับฟัง ไม่ใช่คำทำนายเต็ม
     *
     * @return string '' = ไม่เข้าเกณฑ์ (ตอบเต็มตามปกติ)
     */
    public function qaBriefReplyDirective(FortuneReading $reading): string
    {
        if (! (bool) ($this->settings->qa_ramble_brief_reply ?? true)) {
            return '';
        }

        // ⚠️ ใช้เกณฑ์ที่เข้มกว่าการขยายหน้าต่าง — ตอบสั้นคือสิ่งที่ลูกค้าเห็น
        //   ต้องเร็วเกินอ่าน 2 ครั้งติด ไม่ใช่ครั้งเดียว (คนจ่าย 99฿ ที่ถามต่อจริงต้องได้คำตอบเต็ม)
        if ((int) ($reading->getConversationState('qa_ramble_streak', 0) ?: 0)
            < $this->qaRambleTuning()['briefAt']) {
            return '';
        }

        return "\n\n=== 🤫 โหมดรับฟัง (เจ้าชะตากำลังเล่าเรื่องยังไม่จบ) ===\n"
            ."สังเกตได้ว่าเจ้าชะตากำลังเล่าเรื่องต่อเนื่อง พิมพ์กลับมาเร็วมากโดยยังไม่ได้อ่านคำตอบก่อนหน้า\n"
            ."รอบนี้ให้ตอบ **สั้นๆ 2-3 บรรทัดเท่านั้น** แบบรับฟังและสรุปใจความที่เธอเพิ่งเล่า\n"
            ."- ห้ามวิเคราะห์ไพ่ยาว ห้ามใส่หัวข้อ ห้ามใส่ช่วงเวลา/สี/เลข/คำแนะนำเป็นข้อๆ\n"
            ."- ให้แสดงว่าแม่หมอฟังอยู่และเข้าใจ แล้วชวนให้เล่าต่อจนจบ\n"
            ."- ถ้าในข้อความมีคำถามตรงๆ ให้ตอบคำถามนั้นสั้นๆ ก่อน แล้วค่อยชวนเล่าต่อ\n"
            ."- คำวิเคราะห์เต็มจะให้ตอนเธอเล่าจบหรือในบทสรุปท้าย — รอบนี้ยังไม่ต้อง\n"
            .'=== จบโหมดรับฟัง ==='."\n";
    }
}
