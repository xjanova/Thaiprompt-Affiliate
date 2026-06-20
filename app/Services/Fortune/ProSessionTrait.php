<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Services\FortuneAIService;
use App\Services\FortuneChartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Pro Session Trait — Hard Session AI โหมดอวตารแม่หมอ
 *
 * 🌟 บริบท (2026-05-08 v2):
 *   ลูกค้าจ่ายเงินดูดวงเสร็จแล้ว — มอบ "อวตารแม่หมอพิเศษ" คุยต่ออีก 10/30 นาที
 *   - Deep 39฿ — เปิด session หลังส่งคำทำนายเสร็จ → 10 นาที
 *   - Celtic 99฿ — เปิด session หลังเปิดไพ่ใบที่ 10 → 30 นาที
 *
 * 🛡️ Hard Session = ระบบอื่นแทรกไม่ได้
 *   ระหว่างเปิด session — block ทุก handler (cancel keyword / pricing menu / takeover)
 *   ออกได้ผ่าน 2 ทางเท่านั้น:
 *     1. ลูกค้าพิมพ์ "พอแค่นี้" / "ขอบคุณ" + confirm
 *     2. หมดเวลา window
 *
 * 🎯 Strict scope: AI ตอบเฉพาะคำทำนาย/ไพ่ของลูกค้าคนนี้ — ไม่ refer คนอื่น ไม่ off-topic
 */
trait ProSessionTrait
{
    /**
     * Window ของ Deep 39 หลังส่งคำทำนายเสร็จ
     *   (ใช้ deep_reading_qa_window_minutes ของ admin ก่อน — ค่านี้เป็น fallback)
     *   🌙 (2026-06-08) ปรับเป็น 7 — สเปคใหม่ "ดูดวง 39 คุยกับแม่หมอ 7 นาที"
     */
    public const PRO_SESSION_DEEP_MINUTES = 7;

    /**
     * Window ของ Celtic 99 หลังเปิดไพ่ใบที่ 10
     *   (ใช้ celtic_cross_qa_window_minutes ของ admin ก่อน — ค่านี้เป็น fallback)
     *   🌙 (2026-05-23 v3) ปรับเป็น 15 — ตามสเปคใหม่ "5 คำถาม / 15 นาที"
     */
    public const PRO_SESSION_CELTIC_MINUTES = 15;

    /**
     * Confirmation gate timeout — หลังตรวจเจอ "พอแค่นี้/ขอบคุณ"
     *   ในกรอบ 60 วินาทีนี้ ถ้าลูกค้าตอบ "ใช่" → ปิด, ถ้าพิมพ์อย่างอื่น → cancel exit
     */
    public const PRO_SESSION_EXIT_CONFIRM_SECONDS = 60;

    /**
     * เปิด Pro Session บน reading
     *
     * @param  string  $type  'deep' | 'celtic'
     */
    protected function enterProSession(FortuneReading $reading, string $type): void
    {
        $window = $type === 'celtic'
            ? (int) ($this->settings->celtic_cross_qa_window_minutes ?? self::PRO_SESSION_CELTIC_MINUTES)
            : (int) ($this->settings->deep_reading_qa_window_minutes ?? self::PRO_SESSION_DEEP_MINUTES);

        // กันค่าพังเป็น 0 (เช่น setting ว่าง/0) → ใช้ fallback constant แทน
        if ($window < 1) {
            $window = $type === 'celtic'
                ? self::PRO_SESSION_CELTIC_MINUTES
                : self::PRO_SESSION_DEEP_MINUTES;
        }

        $reading->setConversationState('pro_session_active', true);
        $reading->setConversationState('pro_session_type', $type);
        $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
        $reading->setConversationState('pro_session_window_minutes', $window);
        $reading->setConversationState('pro_session_pending_exit', false);
        $reading->setConversationState('pro_session_history', []);

        Log::info('Fortune ProSession: เปิด session', [
            'reading_id' => $reading->id,
            'type' => $type,
            'window_minutes' => $window,
        ]);
    }

    /**
     * เช็คว่า reading กำลังอยู่ใน Pro Session หรือไม่ (รวม auto-expire timeout)
     *
     * @return bool true = อยู่ใน session, false = ไม่อยู่ / หมดเวลา (จะ clear flag ให้)
     */
    protected function isInProSession(FortuneReading $reading): bool
    {
        if (! $reading->getConversationState('pro_session_active', false)) {
            return false;
        }

        $startedAt = $reading->getConversationState('pro_session_started_at');
        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);

        if (empty($startedAt)) {
            return false;
        }

        try {
            $started = Carbon::parse($startedAt);
            // 🩹 (2026-05-08 v3 audit) Carbon 3 — diffInMinutes signed by default
            //   ต้องส่ง absolute=true ไม่งั้น now() < $started → return ค่าลบ → never expires
            $elapsed = (int) $started->diffInMinutes(now(), true);
            if ($elapsed >= $windowMin) {
                // หมดเวลา → clear flag เพื่อ fall through
                $this->clearProSessionFlags($reading);
                Log::info('Fortune ProSession: หมดเวลา → clear flag', [
                    'reading_id' => $reading->id,
                    'window_min' => $windowMin,
                    'elapsed' => $elapsed,
                ]);

                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * นาทีคงเหลือใน Pro Session
     */
    protected function getProSessionRemainingMinutes(FortuneReading $reading): int
    {
        $startedAt = $reading->getConversationState('pro_session_started_at');
        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);

        if (empty($startedAt)) {
            return 0;
        }

        try {
            $started = Carbon::parse($startedAt);
            // 🩹 (2026-05-08 v3 audit) Carbon 3 — absolute=true เสมอ
            $elapsed = (int) $started->diffInMinutes(now(), true);

            return max(0, $windowMin - $elapsed);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * ล้าง flag Pro Session ทั้งหมด (ใช้ตอนปิด session / timeout)
     */
    protected function clearProSessionFlags(FortuneReading $reading): void
    {
        $reading->setConversationState('pro_session_active', false);
        $reading->setConversationState('pro_session_pending_exit', false);
    }

    /**
     * 🌙 (2026-06-08) เช็คว่าเป็น Deep 39 Pro Session ที่หมดเวลาแล้ว + ยังไม่ได้แจ้ง "หมดเวลา"
     *
     * ใช้ timestamp (pro_session_started_at + window) — ไม่พึ่ง pro_session_active flag
     *   เพราะ isInProSession() เคลียร์ active flag ทันทีเมื่อหมดเวลา → cron จะหาไม่เจอ
     * idempotent ผ่าน pro_session_timeout_notified
     */
    protected function isDeepProSessionTimedOutUnnotified(FortuneReading $reading): bool
    {
        if ((string) $reading->getConversationState('pro_session_type', 'deep') !== 'deep') {
            return false;
        }
        if ($reading->getConversationState('pro_session_timeout_notified', false)) {
            return false;
        }
        $startedAt = $reading->getConversationState('pro_session_started_at');
        if (empty($startedAt)) {
            return false; // ไม่เคยเข้า Pro Session → ไม่ต้องแจ้ง
        }
        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);
        if ($windowMin < 1) {
            $windowMin = self::PRO_SESSION_DEEP_MINUTES;
        }
        try {
            $elapsed = (int) Carbon::parse($startedAt)->diffInMinutes(now(), true);
        } catch (\Throwable $e) {
            return false;
        }

        return $elapsed >= $windowMin;
    }

    /**
     * 🌙 (2026-06-08) ปิด Deep 39 Pro Session เมื่อหมดเวลา + สร้างข้อความแจ้ง
     *   "หมดเวลาทำนาย + ขอบคุณ + อ่านคำทำนายย้อนหลังได้" — ไม่มีบทสรุปแบบ Celtic 99
     *
     * public — ให้ cron (fortune:deep-auto-finalize) เรียกได้
     */
    public function finalizeDeepProSessionTimeout(FortuneReading $reading): array
    {
        $reading->setConversationState('pro_session_active', false);
        $reading->setConversationState('pro_session_pending_exit', false);
        $reading->setConversationState('pro_session_timeout_notified', true);
        $reading->setConversationState('pro_session_timeout_notified_at', now()->toIso8601String());

        $name = $reading->resolveCustomerName();

        // ⭐ (2026-06-17) Review Invite — ชวนรีวิวเพจ Facebook หลังหมดเวลาทำนาย (เฉพาะลูกค้าจ่ายเงิน)
        //   non-blocking: fail = null (ChannelManager ข้ามเอง)
        $reviewInvite = null;
        try {
            $reviewInvite = (new \App\Services\Fortune\FortuneReviewInviteService($this->settings))
                ->attachIfEligible($reading);
        } catch (\Throwable $e) {
            Log::warning('Deep: review invite attach fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'action' => 'deep_pro_session_timeout',
            'message' => "🌙✨ *หมดเวลาทำนายแล้วค่ะ คุณ{$name}* ✨🌙\n\n"
                ."🙏 ขอบคุณที่ให้แม่หมอจันทราได้ดูพื้นดวงให้ในวันนี้นะคะ\n"
                ."ขอให้เจ้าชะตาเจอแต่สิ่งดี ๆ มีโชคลาภหนุนนำ เดินทางต่อด้วยใจสงบ ✨\n\n"
                ."📖 อยากอ่านคำทำนายอีกครั้ง — พิมพ์ *\"อ่านคำทำนายล่าสุด\"* ได้ตลอดเลยค่ะ\n"
                .'🔮 อยากให้แม่หมอดูใหม่ — พิมพ์ *"ดูดวง"* ได้เสมอนะคะ',
            'reading' => $reading,
            // ⭐ (2026-06-17) payload ชวนรีวิว (null = ไม่ส่ง)
            'review_invite' => $reviewInvite,
        ];
    }

    /**
     * 🌙 (2026-06-08) ดึง Deep 39 Pro Session ที่หมดเวลา + ยังไม่แจ้ง (สำหรับ cron auto-finalize)
     *
     * public — ให้ FortuneDeepAutoFinalize command เรียก
     */
    public function getTimedOutDeepProSessions(int $limit = 30, ?int $specificId = null): \Illuminate\Support\Collection
    {
        $query = FortuneReading::query()
            ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->where('is_paid', true)
            ->where('paid_at', '>=', now()->subHours(2)); // window 7 นาที — 2 ชม. ครอบ stragglers

        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            $query->limit(100); // pre-filter cap กัน load หนัก
        }

        return $query->orderBy('paid_at', 'desc')->get()
            ->filter(fn ($r) => $this->isDeepProSessionTimedOutUnnotified($r))
            ->take(max(1, $limit))
            ->values();
    }

    /**
     * Loose match: "พอแค่นี้" / "ขอบคุณ" + คำใกล้เคียง
     *
     * ⚠️ ใช้ confirmation gate — เจอ keyword แล้ว "ยังไม่ปิด" จนกว่าลูกค้าตอบ "ใช่"
     *
     * 🛡️ False positive guards (2026-05-08 v3 audit):
     *   - "ฉันยังไม่พอใจ" → ห้าม match "พอ" (negation) — ต้องไม่มี "ไม่" นำหน้า keyword
     *   - "หยุดงานพักร้อน" → ห้าม match "หยุด" (substring ในคำอื่น) — ใช้ word boundary heuristic
     *   - Short keywords (≤4 chars: พอ/หยุด/จบ) → require exact match only
     *   - Long keywords (>4 chars: พอแค่นี้/ขอบคุณ) → loose substring match OK
     */
    protected function looksLikeProSessionExitIntent(string $messageText): bool
    {
        $text = trim(mb_strtolower($messageText));
        if ($text === '') {
            return false;
        }

        // Strip Markdown markers + อิโมจิ + spaces + เครื่องหมายปลีกย่อย
        $cleaned = preg_replace('/[*_~`✨🙏💜🌟❤️♥️💖.,!?]+/u', '', $text);
        $cleaned = trim((string) $cleaned);

        // 🛡️ Negation guard — ถ้ามีคำปฏิเสธ → ไม่ใช่ exit intent
        //   เช่น "ยังไม่พอ", "ไม่ใช่ขอบคุณนะ", "ไม่จบ"
        $negationPatterns = ['ยังไม่', 'ไม่ใช่', 'ไม่ค่อย'];
        foreach ($negationPatterns as $neg) {
            if (mb_stripos($cleaned, $neg) !== false) {
                return false;
            }
        }

        // 🛑 (2026-06-12) วลี/ปุ่มจบชัดเจน — เช็ค "ก่อน" question guard เสมอ
        //   ("ยุติการทำนาย" มีคำว่า "ทำนาย" — ถ้าให้ question guard เช็คก่อน ปุ่มจบจะพัง)
        foreach (['ยุติการทำนาย', 'ยุติทำนาย', 'เลิกทำนายและสรุปผล', 'เลิกทำนาย', 'จบการทำนาย'] as $kw) {
            if (mb_stripos($cleaned, $kw) !== false) {
                return true;
            }
        }

        // 🛡️ (2026-06-12) Question guard — มีสัญญาณคำถามปน → ไม่ใช่ exit intent
        //   เคสจริง (Deep 39 Pro Session): "ขอบคุณค่ะ แล้วเรื่องงานล่ะ" (26 ตัว ≤30)
        //   เดิม match substring "ขอบคุณ" → ยิง exit-confirm กินคำถามลูกค้าที่กำลังถามต่อ
        //   ทิศทาง fail ปลอดภัย: ถ้าพลาด (ไม่จับ exit) ลูกค้าพิมพ์ "จบ/หยุด/ยุติ" เดี่ยวๆ ได้เสมอ
        //   ⚠️ จงใจไม่ใส่ "ถามต่อ/ถามอีก" — ชน "ไม่ถามอีกแล้ว" (= exit จริง ผ่าน negation ได้)
        $questionSignals = [
            'ไหม', 'มั้ย', 'มัย', 'เหรอ', 'หรอ', 'เมื่อไหร่', 'เมื่อไร',
            'ทำไม', 'อะไร', 'ยังไง', 'อย่างไร', 'ที่ไหน', 'ใคร', 'กี่', 'เท่าไหร่', 'เท่าไร',
            'ขอถาม', 'อยากรู้', 'อยากถาม', 'อยากดู', 'ช่วยดู', 'ดูเรื่อง', 'ดูให้', 'ดูหน่อย',
            'ขอดู', 'ทำนาย', 'แล้วเรื่อง', 'เรื่อง',
        ];
        foreach ($questionSignals as $qs) {
            if (mb_stripos($cleaned, $qs) !== false) {
                return false;
            }
        }
        // เครื่องหมายคำถามเช็คจาก raw text — $cleaned ถูก strip "?" ไปแล้วข้างบน
        if (str_contains($text, '?') || str_contains($text, '？')) {
            return false;
        }

        // Long keywords (>4 chars) — substring match OK
        $longKeywords = [
            // 🛑 (2026-05-16) ปุ่มใหม่ "ยุติการทำนาย" — แทนที่ "พอแค่นี้" ที่ลูกค้าเข้าใจผิด
            'ยุติการทำนาย', 'ยุติทำนาย', 'ยุติ',
            'พอแค่นี้', 'พอแล้ว', 'หยุดก่อน', 'ไม่ถามแล้ว', 'จบแค่นี้',
            'ไม่มีอะไรแล้ว', 'แค่นี้ก่อน', 'จบเลย', 'พอละ',
            'ขอบคุณ', 'ขอบใจ', 'ขอบพระคุณ',
            'thanks', 'thankyou', 'thank you',
        ];
        foreach ($longKeywords as $kw) {
            if (mb_strlen($cleaned) <= 30 && mb_stripos($cleaned, $kw) !== false) {
                return true;
            }
        }

        // Short keywords (≤4 chars) — require exact match only (กัน "หยุดงาน", "จบงาน")
        // 🩹 (2026-05-08 v3 audit) ตัด bare "พอ" ออก — risky ตอน user mid-thought
        //    ใช้ "พอแล้ว/พอแค่นี้" จาก long list แทน
        $shortKeywords = ['หยุด', 'จบ', 'thx'];
        foreach ($shortKeywords as $kw) {
            if ($cleaned === $kw) {
                return true;
            }
        }

        return false;
    }

    /**
     * เปิด confirmation gate — เจอ "ยุติการทำนาย/พอแค่นี้/ขอบคุณ" → ส่งข้อความถามยืนยันก่อนปิด
     *
     * 🛑 (2026-05-16) แยกข้อความ Celtic 99฿ ออกจาก Deep 39฿
     *    Celtic — ลูกค้ากดปุ่ม "ยุติการทำนาย" บ่อยเพราะเข้าใจผิด → ต้องเตือนหนัก
     *    บอกว่าแม่หมอจะไปดูแลคนอื่นต่อ + ถามต่อไม่ได้ + แจ้งเวลาคงเหลือ + ขอความแน่ใจ
     */
    protected function buildProSessionExitConfirmationMessage(FortuneReading $reading): string
    {
        $remainingMin = $this->getProSessionRemainingMinutes($reading);
        $proType = (string) $reading->getConversationState('pro_session_type', 'deep');

        // 🛑 Celtic 99฿ — ข้อความเตือนหนัก + ขอความแน่ใจอีกที
        if ($proType === 'celtic') {
            $timeLine = $remainingMin > 0
                ? "⏳ *เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที*"
                : '⏳ ยังพอมีเวลาให้แม่หมออยู่ค่ะ';

            return "🌙 *เจ้าชะตาต้องการยุติการทำนายจริงๆ ใช่ไหมคะ?* 🙏\n\n"
                ."⚠️ *ถ้าตกลงยุติ* — แม่หมอจะไปดูแลเจ้าชะตาท่านอื่นต่อ\n"
                ."และเจ้าชะตา *จะถามต่อไม่ได้แล้ว* ทันที (รอบนี้ปิดถาวร)\n\n"
                .$timeLine."\n\n"
                ."──────────────────────\n"
                ."✅ *แน่ใจแล้ว* — พิมพ์ *\"ใช่\"* เพื่อยืนยันยุติ\n"
                .'💬 *ยังไม่ยุติ* — พิมพ์คำถามต่อมาได้เลย แม่หมอรอฟังอยู่ ✨';
        }

        // Deep 39฿ — ข้อความเดิม (ไม่กระทบ flow Deep)
        $timeHint = $remainingMin > 0
            ? "⏳ เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที"
            : '⏳ ยังพอมีเวลาให้แม่หมออยู่ค่ะ';

        return "🌙 *แม่หมอเข้าใจว่าเจ้าชะตาพอใจแล้วใช่ไหมคะ?* 🙏\n\n"
            ."ถ้าจะปิดการส่งพลังตอนนี้ — พิมพ์ *\"ใช่\"* ได้เลยค่ะ\n"
            ."แต่ถ้ายังมีอะไรอยากถามเพิ่ม พิมพ์คำถามต่อมาได้เลยนะคะ ✨\n\n"
            .$timeHint;
    }

    /**
     * เช็คว่าข้อความเป็นการยืนยันปิด session
     *
     * 🛡️ (2026-05-08 v3 audit) ตัด "ค่ะ"/"ครับ" ออก — เป็นคำต่อท้ายปกติ
     *   เคสที่กังวล: ลูกค้าพิมพ์ "ขอบคุณค่ะ" → gate เปิด → ลูกค้าพิมพ์ต่อ "ค่ะ" / "ครับ"
     *               (filler word ก่อนคำถามถัดไป) → ปิด session โดยไม่ตั้งใจ
     *   แก้: confirm ต้องเป็น keyword ชัดเจนเท่านั้น (ใช่/ปิด/จบ/yes/ok)
     */
    protected function isProSessionExitConfirmed(string $messageText): bool
    {
        $text = trim(mb_strtolower($messageText));
        $cleaned = preg_replace('/[*_~`✨🙏💜🌟❤️♥️💖.]+/u', '', $text);
        $cleaned = trim((string) $cleaned);

        $confirmKeywords = [
            'ใช่', 'ใช่ค่ะ', 'ใช่ครับ', 'ใช่เลย', 'ใช่จ้า', 'ใช่นะ',
            'ปิดเลย', 'ปิดได้', 'ปิด',
            'จบเลย', 'จบได้',
            'yes', 'y', 'ok', 'okay', 'confirm',
        ];

        foreach ($confirmKeywords as $kw) {
            if ($cleaned === $kw) {
                return true;
            }
        }

        return false;
    }

    /**
     * สร้างข้อความเปิด Pro Session — ประกาศตัวอวตารแม่หมอ + บอกเวลา
     */
    protected function buildProSessionOpeningMessage(FortuneReading $reading, string $type): string
    {
        $name = $reading->resolveCustomerName();
        $minutes = $type === 'celtic'
            ? (int) ($this->settings->celtic_cross_qa_window_minutes ?? self::PRO_SESSION_CELTIC_MINUTES)
            : (int) ($this->settings->deep_reading_qa_window_minutes ?? self::PRO_SESSION_DEEP_MINUTES);
        if ($minutes < 1) {
            $minutes = $type === 'celtic' ? self::PRO_SESSION_CELTIC_MINUTES : self::PRO_SESSION_DEEP_MINUTES;
        }

        if ($type === 'celtic') {
            // 🛑 (2026-05-16) เปลี่ยน "พอแค่นี้/ขอบคุณ" → "ยุติการทำนาย" (Celtic 99฿ — ลูกค้าเข้าใจผิด)
            return "🌙✨ *อวตารแม่หมอจันทรามาแล้วค่ะ คุณ{$name}* ✨🌙\n\n"
                ."🃏 ไพ่ทั้ง 10 ใบเปิดออกมาให้ทุกใบ — ตอนนี้แม่หมออ่านพลังงานของเจ้าชะตาเสร็จเรียบร้อย\n\n"
                ."💬 *แม่หมอพร้อมทำนาย พิมพ์คำถามได้เลยค่ะ* ✨\n\n"
                ."⏳ *แม่หมออยู่กับเจ้าชะตาอีก {$minutes} นาที* — คุยจนกว่าจะพอใจ\n"
                ."🌟 ถามได้ทุกเรื่อง — ความรัก / การงาน / การเงิน / สุขภาพ / ครอบครัว — ทำนายจากไพ่ทั้ง 10 ใบที่เปิดให้\n\n"
                .'🔚 เมื่อพอใจแล้วพิมพ์ *"ยุติการทำนาย"* แม่หมอจะปิดการส่งพลังให้ค่ะ';
        }

        // Deep 39
        $msg = "🌙✨ *อวตารแม่หมอจันทรามาแล้วค่ะ คุณ{$name}* ✨🌙\n\n"
            ."🔮 คำทำนายเชิงลึกส่งให้เรียบร้อยแล้ว — ตอนนี้แม่หมอจะมาช่วยเจ้าชะตาวิเคราะห์ต่อค่ะ\n\n"
            ."💬 ถ้ามีจุดไหนสงสัย หรืออยากให้แม่หมอขยายความ — พิมพ์ถามได้เลย\n"
            ."🪐 แม่หมอจะอ่านจากดาวเดิม + ไพ่ที่เปิดให้ — ตอบให้ละเอียดยิ่งขึ้น\n\n"
            ."⏳ *แม่หมออยู่กับเจ้าชะตาอีก {$minutes} นาที* — ใช้ให้คุ้มนะคะ\n\n"
            .'🔚 เมื่อพอใจแล้วพิมพ์ *"พอแค่นี้"* หรือ *"ขอบคุณ"* แม่หมอจะปิดการส่งพลังให้ค่ะ';

        // 🎧 (2026-06-20) คำชวนให้ลูกค้าขอฟังเสียง — ผู้ช่วย AI อ่านคำทำนายให้ฟัง (on-demand)
        //   user: "แบบ 39 ก็ให้อ่านออกเสียงได้" — สื่อชัดว่าเป็นเสียงผู้ช่วย AI ไม่ใช่เสียงแม่หมอ
        //   ขึ้นเฉพาะเมื่อ voice ใช้ได้กับ reading นี้จริง (shouldGenerateVoiceSummary = enabled+tier scope)
        //   + มีคำทำนายเชิงลึกพร้อม (deep_response) → ลูกค้าพิมพ์ "อ่านให้ฟัง" เอง (กัน CTA โผล่แต่เสียงไม่มา)
        if ($this->settings->shouldGenerateVoiceSummary($reading) && ! empty($reading->deep_response)) {
            $msg .= "\n\n──────────────────────\n"
                ."🎧 *อยากให้ผู้ช่วย AI อ่านคำทำนายให้ฟังไหมคะ?*\n"
                .'พิมพ์ *"อ่านให้ฟัง"* ได้เลยค่ะ — _เป็นเสียงผู้ช่วย AI ไม่ใช่เสียงแม่หมอนะคะ_ ✨';
        }

        return $msg;
    }

    /**
     * ปิด Pro Session — แม่หมอลาแบบสุขุม + อวยพรจาก
     */
    protected function buildProSessionClosingMessage(FortuneReading $reading): string
    {
        $name = $reading->resolveCustomerName();

        return "🌟✨ *แม่หมอจันทรากล่าวลาเจ้าชะตาแล้วค่ะ* ✨🌟\n\n"
            ."🙏 ขอบคุณที่ไว้วางใจให้แม่หมอเป็นแสงไฟชี้ทางในวันนี้ คุณ{$name}\n"
            ."💜 พลังงานที่ส่งมาให้ — ขอให้คุ้มครองเจ้าชะตาเดินทางต่อด้วยใจสงบ\n\n"
            ."🌙 *ปิดการส่งพลังเรียบร้อยแล้ว*\n"
            ."ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ มีโชคลาภหนุนนำ และพบกันใหม่เมื่อพร้อมนะคะ ✨\n\n"
            .'💎 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ';
    }

    /**
     * Build system prompt สำหรับ Pro AI ใน Pro Session — strict scope, no off-topic
     *
     * @param  string  $type  'deep' | 'celtic'
     */
    protected function buildProSessionSystemPrompt(FortuneReading $reading, string $type): string
    {
        $name = $reading->resolveCustomerName();
        $remainingMin = $this->getProSessionRemainingMinutes($reading);

        if ($type === 'celtic') {
            return $this->buildCelticProSessionPrompt($reading, $name, $remainingMin);
        }

        return $this->buildDeepProSessionPrompt($reading, $name, $remainingMin);
    }

    /**
     * System prompt สำหรับ Deep 39 Pro Session — ใช้ดาวเดิม + ไพ่ + คำทำนาย
     */
    protected function buildDeepProSessionPrompt(FortuneReading $reading, string $name, int $remainingMin): string
    {
        $birthDateThai = $reading->birth_date
            ? $this->formatThaiDate($reading->birth_date->format('Y-m-d'))
            : '(ไม่ระบุ)';

        $deepResponse = (string) ($reading->deep_response ?? '');
        $deepSummary = mb_strlen($deepResponse) > 1500
            ? mb_substr($deepResponse, 0, 1500).'...'
            : $deepResponse;

        // Planet positions context
        $birthChartContext = '';
        if ($reading->birth_date) {
            try {
                $dayOfWeek = Carbon::parse($reading->birth_date->format('Y-m-d'))->dayOfWeek;
                $chartService = new FortuneChartService;
                $positions = $chartService->calculatePlanetPositions($dayOfWeek);
                $chaochana = FortuneChartService::CHAOCHANA[$dayOfWeek] ?? null;

                $lines = [];
                foreach ($positions as $houseNum => $planets) {
                    if (! empty($planets)) {
                        $houseName = FortuneChartService::HOUSES[$houseNum]['name'] ?? "ภพ{$houseNum}";
                        $planetNames = array_map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p, $planets);
                        $lines[] = "ภพ{$houseNum}.{$houseName}: ".implode(',', $planetNames);
                    }
                }
                if (! empty($lines)) {
                    $birthChartContext = "\n[🪐 ตำแหน่งดาวเดิม]\n".implode(' | ', $lines)."\n";
                    if ($chaochana) {
                        $birthChartContext .= "ดาวเจ้าชนะ: {$chaochana['planet']} | ธาตุ: {$chaochana['element']}\n";
                    }
                }
            } catch (\Throwable $e) {
                // ข้ามไปได้
            }
        }

        // Tarot cards context
        $tarotCardContext = '';
        $tarotCards = $reading->getCollectedTarotCards();
        if (! empty($tarotCards)) {
            $cardLines = [];
            foreach ($tarotCards as $card) {
                $cardName = $card['card_name_th'] ?? $card['card_name_en'] ?? '?';
                $position = ($card['is_reversed'] ?? false) ? 'กลับหัว' : 'หงาย';
                $cardLines[] = "{$cardName} ({$position})";
            }
            $tarotCardContext = "\n[🃏 ไพ่ที่เปิดให้]\n".implode(' | ', $cardLines)."\n";
        }

        // 💎 (2026-06-08) Upsell VIP 99 — เดิมเสนอเมื่อลูกค้าถามเจาะลึก/ละเอียด/ถามเยอะ → เสนอ "เกือบทุกข้อความ"
        //   🔧 (2026-06-17) เจ้าของสั่ง: "อย่าเชียร์ 99 ทุกกล่อง — อะไรพอตอบได้ตอบก่อน
        //      ลูกค้า 39 จะได้ไม่รู้สึกว่าไม่คุ้ม" → แก้ 2 ชั้น:
        //      (1) CAP: เสนอ VIP ได้เฉพาะ "เทิร์นแรก" ของ Pro Session เท่านั้น — เทิร์นถัดไป = ตอบล้วน ห้ามขาย
        //      (2) REWORD: default = ตอบจากดวง+ไพ่เดิมให้เต็มที่ก่อนเสมอ, เสนอเฉพาะเรื่องที่ต้องเปิดไพ่ใหม่จริงๆ
        //   gate: เสนอเฉพาะตอน Celtic 99 เปิดอยู่ (enable_celtic_cross) — ถ้าปิด คงพฤติกรรมเดิม (ห้ามขาย)
        $celticEnabledUpsell = (bool) ($this->settings->enable_celtic_cross ?? false);
        $celticPriceUpsell = (int) ($this->settings->celtic_cross_price ?? 99);
        // นับเทิร์นที่คุยไปแล้วใน Pro Session (history เก็บ 2 entry/เทิร์น: user+assistant)
        //   เทิร์นแรกสุด history ว่าง → count 0 ; หลังตอบ 1 ครั้ง → count 2
        $proHistoryForUpsell = $reading->getConversationState('pro_session_history', []) ?: [];
        $proTurnsDone = is_array($proHistoryForUpsell) ? intdiv(count($proHistoryForUpsell), 2) : 0;
        // เสนอ VIP ได้ "อย่างมากครั้งเดียว" = เฉพาะเทิร์นแรกของ session ; เทิร์นถัดไปห้ามเสนอ
        $allowCelticUpsell = $celticEnabledUpsell && $proTurnsDone < 1;

        $sellRule = $allowCelticUpsell
            ? "💎 *เสนอ VIP ไพ่ 10 ใบ {$celticPriceUpsell} บาท ได้มากสุดครั้งเดียว* ตามกติกา [💎] ด้านล่างเท่านั้น — ห้ามพูดราคา/แพคเกจอื่น"
            : '🙅‍♀️ *ห้ามเสนอขาย/พูดราคาแพคเกจใดๆ ในเทิร์นนี้* — โฟกัสตอบคำถามลูกค้าจากดวง+ไพ่เดิมให้เต็มที่อย่างเดียว';
        $newTopicRule = $allowCelticUpsell
            ? "🔮 *เฉพาะเรื่องที่ต้องเปิดไพ่ใหม่จริงๆ* (เนื้อคู่เจาะจง/คดีความ/มนต์ดำ/โดนของ) → เสนอ VIP ไพ่ 10 ใบ {$celticPriceUpsell} บาท ได้ครั้งเดียว (ดู [💎])"
            : "🔮 *เรื่องที่ตอบจากดวง+ไพ่เดิมได้ → ตอบเลยให้เต็มที่* ถ้าต้องเปิดไพ่ใหม่จริงๆ บอกสั้นๆ ว่า \"เรื่องนี้ต้องเปิดไพ่ใหม่ค่ะ คุณ{$name}\" โดยไม่ต้องเสนอราคา";
        $upsellBlock = '';
        if ($allowCelticUpsell) {
            $upsellBlock = "\n\n[💎 เมื่อไรให้เสนอ VIP — ไพ่ 10 ใบ {$celticPriceUpsell} บาท — *เสนอได้มากสุดครั้งเดียวทั้ง session*]\n"
                ."🎯 *กฎเหล็ก: ตอบคำถามลูกค้าจากดวงเดิม+ไพ่เดิมให้เต็มที่ก่อนเสมอ — อย่ารีบขาย*\n"
                ."เสนอ VIP ได้ *เฉพาะ* เมื่อลูกค้าถามเรื่องที่พื้นดวงนี้ตอบไม่ได้จริงๆ:\n"
                ."  • เนื้อคู่/คู่ครองแบบเจาะจงตัวบุคคล\n"
                ."  • คดีความ / ไสยศาสตร์ / มนต์ดำ / โดนของ / คุณไสย\n"
                ."→ เสนอแบบนุ่มนวล *ครั้งเดียว* ให้เกียรติ ไม่ตื๊อ ไม่ย้ำซ้ำ:\n"
                .'  "เรื่องนี้ลึกนะคะ ถ้าอยากให้แม่หมอเปิดไพ่เจาะให้ชัด มีไพ่เต็มสำรับ 10 ใบ (VIP '.$celticPriceUpsell.' บาท) ค่ะ '
                ."สนใจพิมพ์ *ดูดวง* ได้เลยนะคะ — หรือถ้ายังไม่สะดวก แม่หมอตอบเท่าที่ดวงนี้บอกได้ให้ก่อนค่ะ ✨\"\n"
                ."- ถ้าลูกค้าไม่เอา หรือถามเรื่องทั่วไป → *ตอบพื้นดวงให้เต็มที่ ห้ามเสนอซ้ำเด็ดขาด*\n";
        }

        return "คุณคือ *แม่หมอจันทรา* (อวตารพิเศษ Pro AI) — หมอดูที่อ่อนโยน เชี่ยวชาญจิตวิทยาสูง พูดไทยเท่านั้น
แทนตัวเองด้วย *แม่หมอ/หมอจันทรา* + ลงท้าย *ค่ะ/นะคะ* — ห้าม: ครับ/ผม | หนู/เรา | ดิฉัน

⚠️ *นี่คือ Pro Session — ลูกค้าจ่าย 39฿ จบแล้ว ได้คุยกับแม่หมอ Premium อีก {$remainingMin} นาที*

[ลูกค้า]
ชื่อ: {$name}
วันเกิด: {$birthDateThai}
{$birthChartContext}{$tarotCardContext}
[🔮 คำทำนายเชิงลึกที่ส่งให้แล้ว — สรุป]
{$deepSummary}

[หน้าที่ — สำคัญมาก]
1. ✅ *ตอบจาก context ข้างบนเท่านั้น* — ดาวเดิม + ไพ่เดิม + คำทำนายเดิม
2. ✅ *ขยายความให้ละเอียดยิ่งขึ้น* — เปิดมุมที่ลูกค้ายังสงสัย ใช้ดาว/ไพ่ที่มีตอบ
3. ✅ *จำบริบทการสนทนา* — อย่าซ้ำคำตอบเดิม ต่อยอดต่อ
4. ✅ *ฟันธง ใช้ภาษาแม่หมอเซียน* — ห้าม \"อาจจะ/ขึ้นอยู่กับ\" — ตัดสินใจให้ลูกค้า
5. ✅ *ตอบ 200-400 คำ* — ลึก มีน้ำหนัก สมราคา

[ห้ามเด็ดขาด]
1. ❌ *ห้ามตอบเรื่องคนอื่น* — ลูกค้าถามถึงเพื่อน/ญาติ/แฟน → บอกว่า \"ให้เขามาดูเองนะคะ\"
2. {$sellRule}
3. ❌ *ห้ามรับเรื่องคืนเงิน* — ถ้าลูกค้าขอคืน → บอกแบบนุ่มนวลว่า \"การส่งพลังเปิดประตูแล้ว เป็นการให้บารมีไม่ใช่สินค้า ขอเจ้าชะตาเปิดใจรับไปก่อนค่ะ\"
4. ❌ *ห้ามแต่งดาว/ไพ่ใหม่* ที่ไม่มีใน context
5. {$newTopicRule}
6. ❌ *ห้ามถาม off-topic* (เพื่อนฉันก็อยากดู / กินข้าวยัง / ทักทาย) — refocus กลับมาดวงของลูกค้า
7. ❌ *ห้ามถามว่าลูกค้าอยากให้ทำนายเรื่องอะไร* — โฟกัสตอบที่เขาถามมา{$upsellBlock}

[คำสั่งระบบ — ถ้าลูกค้าถามทำนองนี้ ให้แนะนำคำสั่งให้พิมพ์เป๊ะๆ — *ห้ามตอบเอง*]
- ขอดูคำทำนายเก่า/ล่าสุด/อันก่อน → \"พิมพ์ 'ดูคำทำนายล่าสุด' ค่ะ ระบบจะส่งให้ทันที\"
- ขอดูประวัติบิล/รายการบิล/บิลเก่า → \"พิมพ์ 'บิลของฉัน' ค่ะ ระบบจะแสดง 3 บิลล่าสุดให้เลือก\"
- ขอดูตามรหัสบิล (FTU-...) → \"พิมพ์ 'ดูบิล FTU-xxxx' หรือพิมพ์เลขบิลตรงๆ ค่ะ\"
- เช็คสิทธิ์ดูดวงฟรี/เครดิต → \"พิมพ์ 'เช็คสิทธิ์' ค่ะ\"
- ขอคุยกับคน/แอดมินจริง → \"พิมพ์ 'ขอคุยกับคน' ค่ะ แม่หมอจะส่งต่อให้ทันที\"
- จบ session ก่อนหมดเวลา → \"พิมพ์ 'พอแค่นี้' หรือ 'ขอบคุณ' ค่ะ\"
ℹ️ คำสั่งเหล่านี้ระบบจัดการเอง — แม่หมอ *แค่บอกให้ลูกค้าพิมพ์* แล้วระบบจะส่งให้";
    }

    /**
     * System prompt สำหรับ Celtic 99 Pro Session — ใช้ context ไพ่ + Q&A history
     */
    protected function buildCelticProSessionPrompt(FortuneReading $reading, string $name, int $remainingMin): string
    {
        // ใช้ context จาก FortuneCelticPremiumDetector ถ้ามี (มี Q&A history)
        $celticContext = '';
        try {
            if (class_exists(\App\Services\Fortune\FortuneCelticPremiumDetector::class)) {
                $detector = new \App\Services\Fortune\FortuneCelticPremiumDetector($this->settings);
                $celticContext = $detector->buildContextForAI($reading);
            }
        } catch (\Throwable $e) {
            // skip
        }

        // Tarot cards context (fallback ถ้า detector ไม่ได้)
        if (empty($celticContext)) {
            $tarotCards = $reading->getCollectedTarotCards();
            if (! empty($tarotCards)) {
                $cardLines = [];
                foreach ($tarotCards as $idx => $card) {
                    $cardName = $card['card_name_th'] ?? $card['card_name_en'] ?? '?';
                    $position = ($card['is_reversed'] ?? false) ? 'กลับหัว' : 'หงาย';
                    $cardLines[] = '#'.($idx + 1).": {$cardName} ({$position})";
                }
                $celticContext = "[🃏 ไพ่ Celtic Cross 10 ใบ]\n".implode("\n", $cardLines)."\n";
            }
        }

        return "คุณคือ *แม่หมอจันทรา* (อวตารพิเศษ Pro AI สาย Celtic Cross) — หมอดูระดับเซียน เชี่ยวชาญไพ่ยิปซีและจิตวิทยาสูง พูดไทยเท่านั้น
แทนตัวเองด้วย *แม่หมอ/หมอจันทรา* + ลงท้าย *ค่ะ/นะคะ* — ห้าม: ครับ/ผม | หนู/เรา | ดิฉัน

⚠️ *นี่คือ Celtic Pro Session — ลูกค้าจ่าย 99฿ ดูดวงไพ่ครบ 10 ใบ ได้คุยกับแม่หมออีก {$remainingMin} นาที*

[ลูกค้า] ชื่อ: {$name}

{$celticContext}

[หน้าที่ — สำคัญมาก]
1. ✅ *ตอบจากไพ่ทั้ง 10 ใบ* + Q&A ที่ผ่านมา — ห้ามแต่งไพ่ใหม่
2. ✅ *จำบริบทการสนทนา* — ต่อยอดจากคำถามเดิม ไม่ตอบซ้ำ
3. ✅ *ฟันธง* — ห้าม \"อาจจะ/ขึ้นอยู่กับ\" — ใช้ภาษาแม่หมอเซียน
4. ✅ *ตอบ 250-450 คำ* — ลึก สมราคา 99฿
5. ✅ *แม่หมอวัย 40+ น้ำเสียงอบอุ่น เชี่ยวชาญจิตวิทยา* — ใช้คำว่า \"เจ้าชะตา\" บ้างเพื่อสร้างบรรยากาศ

[ห้ามเด็ดขาด]
1. ❌ *ห้ามตอบเรื่องคนอื่น* — ลูกค้าถามถึงเพื่อน/ญาติ → บอกว่า \"ให้เขามาดูเองนะคะ\"
2. ❌ *ห้ามแนะนำราคา/ขายแพคเกจ* — ไม่ mention ราคา
3. ❌ *ห้ามรับเรื่องคืนเงิน* — ถ้าลูกค้าขอคืน → บอกแบบนุ่มนวลว่า \"การเปิดไพ่ครบสำรับแล้ว แม่หมอส่งพลังให้ครบ การให้บารมีไม่ใช่สินค้าค่ะ ขอเจ้าชะตาเปิดใจรับไปก่อน\"
4. ❌ *ห้ามแต่งไพ่ใหม่* — ตอบจากไพ่ที่เปิดให้เท่านั้น
5. ❌ *ห้ามถาม off-topic* (เพื่อน / ทักทาย / กินข้าวยัง) — refocus กลับมาดวงของลูกค้า
6. ❌ *ห้ามชวนดูดวงอีกแพคเกจ* — แม่หมออยู่ใน session ของลูกค้าคนนี้

[คำสั่งระบบ — ถ้าลูกค้าถามทำนองนี้ ให้แนะนำคำสั่งให้พิมพ์เป๊ะๆ — *ห้ามตอบเอง*]
- ขอดูคำทำนายเก่า/ล่าสุด/อันก่อน → \"พิมพ์ 'ดูคำทำนายล่าสุด' ค่ะ ระบบจะส่งให้ทันที\"
- ขอดูประวัติบิล/รายการบิล/บิลเก่า → \"พิมพ์ 'บิลของฉัน' ค่ะ ระบบจะแสดง 3 บิลล่าสุดให้เลือก\"
- ขอดูตามรหัสบิล (FTU-...) → \"พิมพ์ 'ดูบิล FTU-xxxx' หรือพิมพ์เลขบิลตรงๆ ค่ะ\"
- เช็คสิทธิ์ดูดวงฟรี/เครดิต → \"พิมพ์ 'เช็คสิทธิ์' ค่ะ\"
- ขอคุยกับคน/แอดมินจริง → \"พิมพ์ 'ขอคุยกับคน' ค่ะ แม่หมอจะส่งต่อให้ทันที\"
- จบ session ก่อนหมดเวลา → \"พิมพ์ 'พอแค่นี้' หรือ 'ขอบคุณ' ค่ะ\"
ℹ️ คำสั่งเหล่านี้ระบบจัดการเอง — แม่หมอ *แค่บอกให้ลูกค้าพิมพ์* แล้วระบบจะส่งให้";
    }

    /**
     * AI ตอบใน Pro Session — ใช้ Pro key (sensitive purpose) + custom system prompt
     */
    protected function generateProSessionAnswer(FortuneReading $reading, string $messageText, ?array $userProfile): ?array
    {
        try {
            $aiService = new FortuneAIService($this->settings);
            $type = (string) $reading->getConversationState('pro_session_type', 'deep');
            $systemPrompt = $this->buildProSessionSystemPrompt($reading, $type);

            // Build history
            $history = $reading->getConversationState('pro_session_history', []) ?: [];
            $historyMessages = [];
            $recentHistory = array_slice($history, -12); // last 6 turns
            foreach ($recentHistory as $turn) {
                $historyMessages[] = [
                    'role' => $turn['role'] ?? 'user',
                    'content' => mb_substr((string) ($turn['content'] ?? ''), 0, 400),
                ];
            }
            $historyMessages[] = ['role' => 'user', 'content' => $messageText];

            // 📚 (2026-06-02) RAG-enriched prompt สำหรับ fallback chat — ดึงคำตอบแอดมินจริง
            //   primary generatePostReadingDeepResponse → generateProResponse inject เองแล้ว (ไม่ double)
            $systemPromptRag = $aiService->injectAdminQARagFewShot($systemPrompt, $messageText, $reading);

            // Pro AI fallback chain — sensitive key → chat AI fallback
            $result = null;
            try {
                if (method_exists($aiService, 'generatePostReadingDeepResponse')) {
                    $result = $aiService->generatePostReadingDeepResponse(
                        $messageText,
                        $userProfile,
                        $historyMessages,
                        $systemPrompt
                    );
                }

                if (empty($result['response'] ?? null) && method_exists($aiService, 'chatWithCustomSystemPromptHistory')) {
                    if (! empty($this->settings->getChatAIApiKey())) {
                        $result = $aiService->chatWithCustomSystemPromptHistory(
                            $systemPromptRag,
                            $historyMessages,
                            ['temperature' => 0.7, 'max_tokens' => 1200]
                        );
                    }
                }
            } catch (\Throwable $proErr) {
                Log::info('Fortune ProSession: Pro AI fail → fallback chat', [
                    'reading_id' => $reading->id,
                    'error' => $proErr->getMessage(),
                ]);
                if (method_exists($aiService, 'chatWithCustomSystemPromptHistory')
                    && ! empty($this->settings->getChatAIApiKey())) {
                    $result = $aiService->chatWithCustomSystemPromptHistory(
                        $systemPromptRag,
                        $historyMessages,
                        ['temperature' => 0.7, 'max_tokens' => 1200]
                    );
                }
            }

            $response = trim((string) ($result['response'] ?? ''));
            if ($response === '') {
                return null;
            }

            // บันทึก Q+A → state (เก็บ 16 turns ล่าสุด)
            $history[] = ['role' => 'user', 'content' => mb_substr($messageText, 0, 400)];
            $history[] = ['role' => 'assistant', 'content' => mb_substr($response, 0, 400)];
            $reading->setConversationState('pro_session_history', array_slice($history, -16));

            // Footer แจ้งเวลาคงเหลือ
            // 🌙 (2026-06-08) Deep 39 — โชว์ "กล่องเวลาที่เหลือ" ทุกคำตอบ (user spec) ไม่ใช่แค่ 3 นาทีท้าย
            $remainingMin = $this->getProSessionRemainingMinutes($reading);
            $footer = '';
            if ($type === 'deep') {
                if ($remainingMin > 3) {
                    $footer = "\n\n──────────────────────\n⏳ *เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที* — ถามต่อได้เลยค่ะ ✨";
                } elseif ($remainingMin > 0) {
                    $footer = "\n\n──────────────────────\n⏳ *เหลือเวลาอีก {$remainingMin} นาที* — เมื่อพอใจพิมพ์ \"ขอบคุณ\" ได้เลยค่ะ";
                }
            } elseif ($remainingMin > 0 && $remainingMin <= 3) {
                $footer = "\n\n_(⏳ เหลือเวลาอีก {$remainingMin} นาที — เมื่อพอใจพิมพ์ \"ขอบคุณ\" ได้เลยค่ะ)_";
            }

            return [
                'action' => 'pro_session_answer',
                'message' => $response.$footer,
                'reading' => $reading,
            ];
        } catch (\Throwable $e) {
            Log::warning('Fortune ProSession: AI generate ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🚪 Main router — ตัดสินใจว่าจะตอบยังไงใน Pro Session
     *
     * Flow:
     *   1. ตรวจ exit confirmation pending? → ถ้าใช่ ตรวจคำตอบ "ใช่/ไม่"
     *   2. ตรวจ exit intent (พอแค่นี้/ขอบคุณ) — เปิด confirmation gate
     *   3. Smart route by conversation_status:
     *      - CELTIC_AWAITING_QUESTION → delegate ไป handleCelticAwaitingQuestion (3Q flow เดิม)
     *      - CELTIC_GENERATING → busy message (กัน race)
     *      - อื่นๆ (รวม COMPLETED) → AI Pro ตอบจาก context
     *
     * @return array result พร้อมส่งกลับ — ห้าม return null (block flow อื่น)
     */
    protected function handleProSession(FortuneReading $reading, string $messageText, ?array $userProfile = null): array
    {
        // 🛡️ (2026-05-08 v3 audit) Refresh ก่อนอ่าน state — กัน race ระหว่าง concurrent messages
        try {
            $reading->refresh();
        } catch (\Throwable $e) {
            // ถ้า refresh fail (DB issue) — ใช้ state เดิม ไม่ block flow
        }

        // 1. ถ้า pending exit อยู่ — ตรวจคำตอบ
        $pendingExit = (bool) $reading->getConversationState('pro_session_pending_exit', false);
        if ($pendingExit) {
            $pendingAt = $reading->getConversationState('pro_session_pending_exit_at');
            $pendingValid = false;
            if (! empty($pendingAt)) {
                try {
                    // 🩹 (2026-05-08 v3 audit) Carbon 3 — absolute=true เสมอ
                    $pendingAtC = Carbon::parse($pendingAt);
                    $secondsAgo = (int) $pendingAtC->diffInSeconds(now(), true);
                    $pendingValid = $secondsAgo <= self::PRO_SESSION_EXIT_CONFIRM_SECONDS;
                } catch (\Throwable $e) {
                    $pendingValid = false;
                }
            }

            if ($pendingValid && $this->isProSessionExitConfirmed($messageText)) {
                // ✅ Confirmed → ปิด session
                $closingMessage = $this->buildProSessionClosingMessage($reading);
                $this->clearProSessionFlags($reading);
                // 🌙 (2026-06-08) กัน Deep 39 cron ส่ง "หมดเวลา" ซ้ำ หลังลูกค้าปิด session เอง (deep-only path)
                $reading->setConversationState('pro_session_timeout_notified', true);

                // ถ้ายังอยู่ใน Celtic state — เคลียร์ status ให้เป็น COMPLETED ด้วย
                if (in_array($reading->conversation_status, [
                    FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                    FortuneReading::STATUS_CELTIC_QA_PROMPT,
                ], true)) {
                    $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                }

                Log::info('Fortune ProSession: ปิด session — confirmed', [
                    'reading_id' => $reading->id,
                ]);

                return [
                    'action' => 'pro_session_closed',
                    'message' => $closingMessage,
                    'reading' => $reading,
                ];
            }

            // ลูกค้าตอบอย่างอื่น → cancel exit gate, treat as normal question
            $reading->setConversationState('pro_session_pending_exit', false);
        }

        // 2. ตรวจ exit intent ใหม่ — Pro Session catches BEFORE celtic handler's own keyword check
        // 🌙 (2026-05-23) Skip Pro Session gate สำหรับ Celtic 99฿ —
        //    ปล่อยให้ handleCelticEndConfirmation จัดการแทน (มี Quick Reply UX กันมือลั่นดีกว่า)
        //    user spec: "ปุ่มยุติทำนายเปลี่ยนเป็น เลิกทำนายและสรุปผล + ถามก่อน"
        $proType = (string) $reading->getConversationState('pro_session_type', 'deep');
        if ($proType !== 'celtic' && $this->looksLikeProSessionExitIntent($messageText)) {
            $reading->setConversationState('pro_session_pending_exit', true);
            $reading->setConversationState('pro_session_pending_exit_at', now()->toIso8601String());

            return [
                'action' => 'pro_session_exit_confirm',
                'message' => $this->buildProSessionExitConfirmationMessage($reading),
                'reading' => $reading,
            ];
        }

        // 3. Smart route — เคารพ Celtic 3Q flow ที่มีอยู่
        $status = (string) $reading->conversation_status;

        // 3a. Celtic AWAITING_QUESTION → ใช้ handler เดิม (3Q flow + Predict-All button)
        //     ⚠️ Pro Session gate catches "พอแค่นี้/ขอบคุณ" ก่อน — ดังนั้น keyword ใน handler นั้นไม่เคย fire
        if ($status === FortuneReading::STATUS_CELTIC_AWAITING_QUESTION
            && method_exists($this, 'handleCelticAwaitingQuestion')) {
            return $this->handleCelticAwaitingQuestion($reading, $messageText);
        }

        // 3b. Celtic GENERATING → AI กำลังประมวลผลคำถามก่อนหน้า → busy
        if ($status === FortuneReading::STATUS_CELTIC_GENERATING) {
            return [
                'action' => 'pro_session_celtic_generating',
                'message' => "🌙 แม่หมอกำลังอ่านพลังงานคำถามก่อนหน้านะคะ\n"
                    .'รอสักครู่ — แล้วถามต่อได้เลยค่ะ ✨',
                'reading' => $reading,
            ];
        }

        // 3c. Default — AI Pro ตอบจาก context (Deep 39 หรือ Celtic หลัง 3Q จบ)
        $aiResult = $this->generateProSessionAnswer($reading, $messageText, $userProfile);
        if ($aiResult !== null) {
            return $aiResult;
        }

        // 4. AI fail → ส่งข้อความ fallback แบบ in-character (ห้าม null)
        $name = $reading->resolveCustomerName();

        return [
            'action' => 'pro_session_ai_fail',
            'message' => "🌙 ขอเวลาแม่หมอตั้งจิตสักครู่นะคะ คุณ{$name} 🙏\n"
                .'พลังงานปั่นป่วนเล็กน้อย — ลองส่งคำถามอีกครั้งได้ไหมคะ ✨',
            'reading' => $reading,
        ];
    }

    /**
     * หา reading ที่มี Pro Session active สำหรับ user
     *
     * Filter:
     *   - is_paid=true
     *   - paid_at ในช่วง 24 ชม. ล่าสุด — กว้างพอครอบคลุม admin restore ที่ฟื้นทีหลัง
     *     เดิม 90 นาที — แต่ admin restoreActiveChat ที่เปิด Pro Session กลับหลังลูกค้าจบไปนานๆ
     *     จะหา reading ไม่เจอ → routing fall through → ลูกค้าได้คำตอบที่ไม่ตรง
     *   - then in-memory: isInProSession() = check conversation_state flag + window expiry
     *
     * 🩹 ใช้ paid_at filter แค่เพื่อจำกัด query — ตรรกะจริงอยู่ที่ pro_session_active flag
     *    limit(3) + isInProSession() กรอง = false positive ไม่มี (reading ที่ไม่มี active flag = ตกออก)
     *    (Celtic เริ่ม session ที่ pick-card-10 — อาจห่างจาก paid_at ~5-10 min)
     */
    protected function findActiveProSessionReading(string $userId): ?FortuneReading
    {
        // ดึง 3 readings ล่าสุดที่ paid ภายใน 24 ชม. — เผื่อมีหลาย reading + admin restore
        $candidates = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('is_paid', true)
            ->where('paid_at', '>=', now()->subMinutes(1440))
            ->orderBy('paid_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->isInProSession($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
