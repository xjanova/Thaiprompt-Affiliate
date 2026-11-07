<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🕊️ Pro Session Grace — ลูกค้า Deep 39฿ ถามต่อ "หลัง" ช่วงคุยต่อ 7 นาทีปิดไปแล้ว
 *
 * 🧾 เคสต้นเรื่อง (prod 2026-11-07 บิล #14643 FTU-261105-Y7178, FB):
 *   16:35 ส่งคำทำนาย + เปิด Pro Session → 16:40 sweep ล้างธง → 16:48 ลูกค้าถามเรื่องคดีความด้วยความกังวล
 *   → has_active=false → ไหลลงแชทฟรี → เข้า buffer debounce 30 วิ → deploy รัน cache:clear ล้าง buffer
 *   → job ตื่นมาเจอ buffer ว่าง → **ไม่มีใครตอบเลย** แอดมินต้องตอบเองมือ 7 นาทีต่อมา
 *
 * 📏 กฎ (ใช้กับ Deep 39 ที่ปิด session ไปแล้วเท่านั้น — Celtic 99 มีทางของตัวเอง):
 *   1. คำถามจริงภายใน {@see PRO_SESSION_GRACE_MINUTES} นาทีหลังปิด → เปิด session ต่อให้สั้น ๆ
 *      {@see PRO_SESSION_GRACE_REOPEN_MINUTES} นาที (ครั้งเดียวต่อบิล) แล้วตอบผ่านเส้น Pro Session ปกติ
 *      — เส้นนั้นจดคำถามลง conversation_state (MySQL) ก่อน buffer ⇒ deploy ล้าง cache ไม่ทำให้คำถามหาย
 *        และฝั่ง LINE job ยืม replyToken ที่เทิร์นนี้ฝากไว้ (ReplyTokenVault) ⇒ ตอบฟรี ไม่กินโควต้า push
 *   2. เลยช่วงผ่อนผัน (หรือใช้สิทธิ์ไปแล้ว) แต่ยังไม่เกิน {@see PRO_SESSION_POLITE_HOURS} ชม. หลังเปิด session
 *      → ตอบกลับทันทีในบทแม่หมอว่า "ช่วงถามต่อปิดแล้ว + อ่านคำทำนายเดิมได้" (สคริปต์ล้วน ไม่ผ่าน AI ไม่ผ่าน buffer)
 *      — เป็นคำตอบของเทิร์นนี้เอง ⇒ LINE ใช้ replyToken ของ webhook ตรง ๆ / FB ตอบในกรอบ 24 ชม.
 *      ส่งได้ 1 ครั้งต่อ {@see PRO_SESSION_POLITE_COOLDOWN_MINUTES} นาที — ข้อความถัดไปไหลตาม flow เดิม (ไม่วนตอบซ้ำ)
 *   3. ขอบคุณ / ลา / ฟิลเลอร์ ("ค่ะ", "👍") → ไม่แตะ (คืน null) = พฤติกรรมเดิมทุกอย่าง
 *   4. ข้อความที่มีเส้นของตัวเองอยู่แล้ว (ดูดวงใหม่ / ถามราคา / ไพ่ฟรี / ดวงรายวัน / แก้วันเกิด) → ไม่แตะ
 *
 * ⚠️ ห้าม push บน LINE จากเส้นนี้เด็ดขาด (.claude/LINE_MESSAGING_RULES.md — push มีแค่ 300 ครั้ง/เดือน)
 *   ⇒ ตอนเปิด session ต่อ ตั้ง pro_session_timeout_notified = true ไว้เลย
 *     ไม่งั้น cron fortune:deep-auto-finalize จะ push "หมดเวลาทำนายแล้วค่ะ" ซ้ำอีกรอบตอนช่วงต่อเวลาหมด
 */
trait ProSessionGraceTrait
{
    /** หลังปิด session กี่นาทีที่คำถามจริงยังได้ "เปิดต่อให้ตอบ" */
    public const PRO_SESSION_GRACE_MINUTES = 15;

    /** เปิดต่อให้นานกี่นาที (นับจากคำถามที่เข้ามา) — สั้นพอให้ตอบข้อนี้ + ถามต่อได้อีกนิด */
    public const PRO_SESSION_GRACE_REOPEN_MINUTES = 5;

    /** หลังเปิด session กี่ชั่วโมงที่ยังตอบสุภาพแทนการเงียบ (= กรอบตอบกลับ 24 ชม. ของ FB) */
    public const PRO_SESSION_POLITE_HOURS = 24;

    /** ข้อความสุภาพส่งซ้ำได้ทุกกี่นาที — กันลูกค้าที่ถามรัวได้กล่องเดิมซ้ำทุกข้อความ */
    public const PRO_SESSION_POLITE_COOLDOWN_MINUTES = 180;

    /**
     * จุดเข้าเดียว — เรียกจาก processMessage ตอน "ไม่มี conversation ค้าง" ก่อนด่านแชทฟรีทั้งหมด
     *
     * @return array|null null = ไม่ใช่เคสนี้ ปล่อย flow เดิมทำงานต่อ
     */
    protected function maybeHandlePostProSessionFollowup(string $userId, string $messageText, ?array $userProfile = null): ?array
    {
        try {
            if (! $this->looksLikePostSessionQuestion($messageText)) {
                return null; // ขอบคุณ/ลา/ฟิลเลอร์/ข้อความที่มีเส้นของตัวเอง → พฤติกรรมเดิม
            }

            $reading = $this->findRecentClosedDeepProSessionReading($userId);
            if ($reading === null) {
                return null;
            }

            $phase = $this->postProSessionPhase($reading);

            if ($phase === 'grace') {
                $this->reopenProSessionForGrace($reading, $userId);

                Log::info('Fortune ProSession grace: คำถามหลังปิด session → เปิดต่อให้ตอบ', [
                    'reading_id' => $reading->id,
                    'user_id' => $userId,
                    'reopen_minutes' => self::PRO_SESSION_GRACE_REOPEN_MINUTES,
                    'q_preview' => mb_substr($messageText, 0, 40),
                ]);

                return $this->handleProSession($reading, $messageText, $userProfile);
            }

            if ($phase === 'polite') {
                // แก้วันเกิดมีเส้นของตัวเองหลังด่านนี้ (resolveBirthdateCorrectionTarget) — ห้ามแย่ง
                //   (ช่วง grace ไม่ต้องเช็ค เพราะ handleProSession ดักแก้วันเกิดให้เองอยู่แล้ว)
                if (method_exists($this, 'resolveBirthdateCorrectionTarget')
                    && $this->resolveBirthdateCorrectionTarget($userId, $messageText) !== null) {
                    return null;
                }

                $reading->setConversationState('pro_session_post_polite_at', now()->toIso8601String());

                Log::info('Fortune ProSession grace: เลยช่วงผ่อนผัน → ตอบสุภาพแทนการเงียบ', [
                    'reading_id' => $reading->id,
                    'user_id' => $userId,
                    'q_preview' => mb_substr($messageText, 0, 40),
                ]);

                return $this->buildPostProSessionPoliteReply($reading);
            }
        } catch (\Throwable $e) {
            // ด่านเสริม — พังแล้วต้องไม่ทำให้ flow หลักพัง (ตกไปทางเดิม)
            Log::warning('Fortune ProSession grace: ล้ม (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * ข้อความนี้เป็น "คำถาม/เรื่องที่อยากให้แม่หมอช่วยดู" จริงไหม
     *
     * ฝั่งที่พลาดได้ปลอดภัยกว่าคือ "ไม่จับ" (ไหลไป flow เดิม) ⇒ ต้องมีสัญญาณคำถามหรือความกังวลชัด ๆ
     * ข้อความยาวเฉย ๆ ไม่นับ — คำชม "แม่นมากค่ะ ตรงทุกข้อเลย" ไม่ควรได้ "ช่วงถามต่อปิดแล้วนะคะ" กลับไป
     */
    protected function looksLikePostSessionQuestion(string $text): bool
    {
        $raw = trim($text);
        if (mb_strlen($raw) < 4) {
            return false;
        }

        // ขอบคุณ / พอแล้ว / ฟิลเลอร์ = ไม่มีอะไรต้องตอบ (มี question guard ในตัวแล้ว:
        //   "ขอบคุณค่ะ แล้วเรื่องงานล่ะ" ไม่นับเป็นคำลา)
        if ($this->looksLikeContentFreeFiller($raw) || $this->looksLikeProSessionExitIntent($raw)) {
            return false;
        }

        $lower = mb_strtolower($raw);
        $noSpace = str_replace(' ', '', $lower);

        // คำลาสั้น ๆ ที่ exit-intent ไม่ครอบ
        if (mb_strlen($noSpace) <= 20 && ! str_contains($noSpace, '?')) {
            foreach (['บายบาย', 'บาย', 'ลาก่อน', 'ราตรีสวัสดิ์', 'ฝันดี', 'ไปก่อนนะ', 'แล้วพบกันใหม่', 'byebye', 'bye', 'goodnight'] as $bye) {
                if (str_contains($noSpace, $bye)) {
                    return false;
                }
            }
        }

        // มีเส้นของตัวเองอยู่แล้ว — ห้ามแย่ง (ดูดวงใหม่ / ถามราคา / ไพ่ฟรี / ดวงรายวัน)
        if ($this->isGenericFortuneRequest($raw)
            || $this->isExplicitDeepReadingRequest($raw)
            || $this->looksLikePricingQuestion($raw)
            || (method_exists($this, 'matchesFreeCardKeyword') && $this->matchesFreeCardKeyword($raw))
            || (method_exists($this, 'looksLikeDailyFreeRequest') && $this->looksLikeDailyFreeRequest($raw))
            || (method_exists($this, 'looksLikePaymentInfoRequest') && $this->looksLikePaymentInfoRequest($raw))) {
            return false;
        }

        // เรื่องเงิน/ราคา/โอน — looksLikePricingQuestion ต้องมีบริบทดวงประกอบ ("ราคาเท่าไหร่คะ" เดี่ยว ๆ ไม่ผ่าน)
        //   แต่ไม่ใช่ "คำถามต่อจากดวง" แน่นอน ⇒ ปล่อยด่านราคา/สลิป/แชทเดิมดูแล (ห้ามเปิด session ให้ AI ตอบเรื่องเงิน)
        foreach (['ราคา', 'กี่บาท', 'ค่าครู', 'จ่ายเท่า', 'โอนแล้ว', 'โอนเงิน', 'สลิป'] as $money) {
            if (str_contains($noSpace, $money)) {
                return false;
            }
        }

        if ($this->isQuestionSentence($raw) || $this->looksLikeFortuneFollowupQuestion($raw)) {
            return true;
        }

        // ไม่มีเครื่องหมายคำถาม แต่บอกความกังวล/ขอคำชี้แนะชัด ๆ ("กังวลมากค่ะ ขอให้แม่หมอชี้แนะ")
        //   รวม "รึป่าว/ป่าว" — คนไทยพิมพ์แทน "หรือเปล่า" บ่อย (เคสต้นเรื่องพิมพ์ "ขึ้นศาลตัดสินรึป่าวคะ")
        foreach (['กังวล', 'กลัวว่า', 'ไม่สบายใจ', 'ชี้แนะ', 'แนะนำ', 'ช่วยดู', 'ช่วยบอก', 'ควรทำ', 'ควรจะ', 'จะเป็นไป', 'รึป่าว', 'หรือป่าว', 'ป่าวคะ', 'ป่าวครับ'] as $kw) {
            if (str_contains($noSpace, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * บิล Deep 39 ล่าสุดของลูกค้าที่ "เปิด session ภายใน 24 ชม. และปิดไปแล้ว"
     *
     * ใช้ proSessionRecentSince (จ่าย **หรือ** เปิด session ในช่วงนี้) — บิล #14643 จ่ายมา 2 วันก่อนถูกทำนายใหม่
     */
    protected function findRecentClosedDeepProSessionReading(string $userId): ?FortuneReading
    {
        $candidates = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', true)
            ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->proSessionRecentSince(now()->subHours(self::PRO_SESSION_POLITE_HOURS))
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->postProSessionPhase($candidate) !== 'none') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * ตอนนี้บิลนี้อยู่ช่วงไหนหลังปิด session (ไม่มี side-effect)
     *
     * @return string 'grace' = เปิดต่อให้ตอบได้ | 'polite' = ตอบสุภาพ | 'none' = ไม่ใช่เคสนี้
     */
    protected function postProSessionPhase(FortuneReading $reading): string
    {
        if ($reading->reading_type !== FortuneReading::READING_TYPE_DEEP
            || (string) $reading->getConversationState('pro_session_type', '') !== 'deep') {
            return 'none';
        }

        // ยังเปิดอยู่ = เส้น Pro Session ปกติดูแล (ด่านบนสุดของ processMessage) — ไม่ใช่งานของเรา
        if ($reading->getConversationState('pro_session_active', false)) {
            return 'none';
        }

        $openedAt = $this->parseStateTime($reading->getConversationState('pro_session_opened_at'));
        if ($openedAt === null || $openedAt->diffInMinutes(now(), true) > self::PRO_SESSION_POLITE_HOURS * 60) {
            return 'none';
        }

        $closedAt = $this->proSessionClosedAt($reading);
        if ($closedAt === null) {
            return 'none';
        }

        $sinceClose = (int) $closedAt->diffInMinutes(now(), true);
        if ($sinceClose <= self::PRO_SESSION_GRACE_MINUTES
            && ! $reading->getConversationState('pro_session_grace_used', false)) {
            return 'grace';
        }

        $politeAt = $this->parseStateTime($reading->getConversationState('pro_session_post_polite_at'));
        if ($politeAt !== null && $politeAt->diffInMinutes(now(), true) < self::PRO_SESSION_POLITE_COOLDOWN_MINUTES) {
            return 'none'; // เพิ่งบอกไปแล้ว — ข้อความถัดไปไหลตาม flow เดิม ไม่วนกล่องเดิม
        }

        return 'polite';
    }

    /**
     * เวลาที่ session ปิดจริง — เรียงจากแหล่งที่แม่นที่สุด
     *
     * บิลก่อน deploy นี้ไม่มี pro_session_closed_at ⇒ ต้องคำนวณย้อนจากนาฬิกาเดิมให้ได้
     */
    protected function proSessionClosedAt(FortuneReading $reading): ?Carbon
    {
        foreach (['pro_session_closed_at', 'pro_session_timeout_notified_at'] as $key) {
            $at = $this->parseStateTime($reading->getConversationState($key));
            if ($at !== null) {
                return $at;
            }
        }

        // ลูกค้าถามแล้ว → ปิดตอน started_at + window
        $startedAt = $this->parseStateTime($reading->getConversationState('pro_session_started_at'));
        if ($startedAt !== null) {
            $window = max(1, (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES));

            return $startedAt->copy()->addMinutes($window);
        }

        // ไม่เคยถาม → ปิดตอนครบช่วงสแตนบาย (นับจากพร้อมให้ถาม)
        $readyAt = $this->parseStateTime($reading->getConversationState('pro_session_ready_at'));
        if ($readyAt !== null) {
            return $readyAt->copy()->addMinutes($this->proSessionStandbyMinutes());
        }

        return null;
    }

    /**
     * เปิด session ต่อให้สั้น ๆ เพื่อให้คำถามนี้วิ่งเส้น Pro Session (จด DB ก่อน buffer + ยืม replyToken)
     */
    protected function reopenProSessionForGrace(FortuneReading $reading, string $userId): void
    {
        $reading->setConversationState('pro_session_active', true);
        $reading->setConversationState('pro_session_pending_exit', false);
        $reading->setConversationState('pro_session_awaiting_first_question', false);
        $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
        $reading->setConversationState('pro_session_window_minutes', self::PRO_SESSION_GRACE_REOPEN_MINUTES);
        $reading->setConversationState('pro_session_grace_used', true);
        $reading->setConversationState('pro_session_closed_at', null);
        // ⚠️ กัน cron ส่งข้อความปิดท้ายซ้ำตอนช่วงต่อเวลาหมด (LINE = push = กินโควต้า 300/เดือน)
        $reading->setConversationState('pro_session_timeout_notified', true);
        // ช่วงต่อเวลาสั้นมาก — ไม่ต้องมีกล่อง "เหลือเวลาอีก 3 นาที" / กล่องตามถาม
        $reading->setConversationState('time_notice_sent', true);
        $reading->setConversationState('pro_session_nudge_sent', true);

        // ธง pro_session_active เปลี่ยน → cache "จ่ายแล้วกำลังทำนาย" ต้องเห็นค่าใหม่ทันที
        if (method_exists($this, 'clearPaidActiveCache')) {
            $this->clearPaidActiveCache($userId);
        }
    }

    /**
     * กล่องตอบสุภาพหลังเลยช่วงผ่อนผัน — สคริปต์ล้วน ไม่ผ่าน AI (ฟรี + ไม่มีทางล่ม + ตอบในเทิร์นนี้ได้ทันที)
     *
     * อยู่ในบทแม่หมอ: ห้ามพูดถึงระบบ/คิว/AI · ไม่ตื้อขาย (closingInviteLine ดูแล "เพิ่งจ่ายวันนี้" ให้)
     */
    protected function buildPostProSessionPoliteReply(FortuneReading $reading): array
    {
        $name = $reading->resolveCustomerName();

        return [
            'action' => 'pro_session_after_window',
            'message' => "🌙 แม่หมอได้ยินคำถามของเจ้าชะตาแล้วนะคะ คุณ{$name} 🙏\n\n"
                .'ช่วงถามต่อของดวงรอบนี้ปิดไปแล้วค่ะ — เรื่องใหม่ต้องตั้งจิตเปิดไพ่ใหม่ '
                ."แม่หมอไม่อยากตอบให้คลาดจากพลังดวงเดิมนะคะ\n\n"
                ."📖 อ่านคำทำนายเดิมอีกครั้ง — พิมพ์ *\"อ่านคำทำนายล่าสุด\"* ได้ตลอดเลยค่ะ\n"
                .$this->closingInviteLine($reading, true),
            'reading' => $reading,
        ];
    }

    /** แปลงเวลาใน conversation_state — ค่าพัง/ว่าง/ไม่ใช่สตริง = null (fail-closed) */
    protected function parseStateTime($value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
