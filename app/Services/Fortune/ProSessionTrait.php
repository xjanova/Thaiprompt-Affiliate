<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Services\FortuneAIService;
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
     * 🛟 (2026-08-21) เพดานจำนวนคำถามค้างที่จดไว้ใน conversation_state
     *   ลูกค้ารัวเกินนี้ = เอาก้อนท้ายสุด (คำถามล่าสุดคือสิ่งที่เขาอยากรู้จริง)
     */
    public const PRO_SESSION_PENDING_MAX = 8;

    /**
     * 🛟 (2026-08-21) คำถามค้างหยุดนาฬิกา session ได้นานสุดกี่นาที
     *
     * ต้นตอ FTU-260821-K9664: ลูกค้าถาม 3 ข้อ → bot เงียบ (deploy กิน buffer) →
     *   **นาฬิกา 7 นาทีเดินต่อทั้งที่ยังไม่ได้ตอบ** → หมดเวลาโดยไม่ได้คำตอบสักคำ
     * → ถ้ายังมีคำถามค้างที่ยังไม่ตอบ ห้ามให้ session หมดเวลา (แต่ต้องมีเพดาน กัน session อมตะ)
     *
     * ⚠️ (2026-08-22) ค่านี้ต้อง **เท่ากับ** หน้าต่างมองย้อนของ FortuneProSessionAnswerRecover
     *   (`updated_at >= now()-15 นาที`) — เกราะสองชั้นนี้ต้องหมดอายุพร้อมกัน
     *
     *   เดิม 10 vs 15 = มีช่องว่าง 5 นาทีที่ cron ยังไล่ re-dispatch อยู่ (เห็น pending_count=1)
     *   แต่ has...() เลิกนับคำถามนั้นแล้ว → isInProSession() ตัดสินว่าหมดเวลา แล้วปิดบิลทิ้ง
     *   เคสจริง FTU-260822-P2391: คำถามค้าง 21:23:15 · เกราะหลุด 21:33:15 · cron ยังยิงถึง 21:35:02
     *   · บิลถูกปิด 21:36:03 — ตัวปิดชนะตัวที่พยายามตอบ
     *
     *   ห้ามตั้งเกินหน้าต่างของ cron ด้วย — จะกลายเป็น session ที่ค้างเปิดโดยไม่มีใคร retry
     */
    public const PRO_SESSION_PENDING_GRACE_MINUTES = 15;

    /**
     * 🛟 (2026-08-22) คำถามค้างเก่าสุดกี่นาทีที่ยังยอม "ตอบย้อนหลัง" หลัง session ปิดไปแล้ว
     *
     * ใช้โดย ProcessBufferedProSessionMessageJob — กว้างกว่า grace ข้างบนเผื่อคิวตัน
     * แต่ยังแคบพอที่จะไม่ไปตอบคำถามของเมื่อคืน
     */
    public const PRO_SESSION_LATE_ANSWER_MAX_MINUTES = 30;

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

        // 🤝 (2026-08-29 FTU-260829-M9469) Celtic 99 — ยืดอายุ "เซสชัน" ถึงเพดานรวม (30 นาที)
        //   ⚠️ ไม่ได้เลื่อนเวลาบทสรุป — บทสรุปยิงตาม celtic_cross_qa_window_minutes (15) เหมือนเดิม
        //      เพราะนาฬิกาบทสรุปอยู่คนละตัว: FortuneReading::canAskMoreCeltic() อ่าน qa_window ตรง ๆ
        //   ที่ยืดคือ pro_session_window_minutes = อายุของ "แม่หมอยังอยู่ในสาย"
        //      → พอบทสรุปยิงนาทีที่ 15 getProSessionRemainingMinutes() ยังเหลือ 15
        //      → endCelticSession เข้าเส้น linger ได้ (เดิม remaining=0 เลยตกไป clearProSessionFlags)
        //   ถ้าเจ้าของปิดสวิตช์ → total = qa_window พอดี = พฤติกรรมเดิมเป๊ะ
        if ($type === 'celtic'
            && method_exists($this->settings, 'isCelticAftercareEnabled')
            && $this->settings->isCelticAftercareEnabled()) {
            $window = max($window, $this->settings->getCelticAftercareTotalMinutes());
        }

        $reading->setConversationState('pro_session_active', true);
        $reading->setConversationState('pro_session_type', $type);
        $reading->setConversationState('pro_session_window_minutes', $window);
        $reading->setConversationState('pro_session_pending_exit', false);
        $reading->setConversationState('pro_session_history', []);
        // 🆕 (2026-06-23) เวลาที่เปิด session (สำหรับ nudge ตามถาม 1 นาที — Part C)
        $reading->setConversationState('pro_session_opened_at', now()->toIso8601String());
        $reading->setConversationState('pro_session_nudge_sent', false);

        // 🆕 (2026-06-23, owner) "เริ่มจับเวลาหลังคำถามแรก" ทั้ง Deep และ Celtic
        //   เปิด session ค้างไว้ (awaiting) — ตั้ง pro_session_started_at ตอนคำถามจริงข้อแรก:
        //     Deep   → handleProSession (คำถามแรกใน Pro Session)
        //     Celtic → markCelticAnswered (คำถามจริงข้อแรก = Q2 หลังพื้นดวง Q1 auto)
        //   isInProSession รองรับ awaiting (timer ยังไม่นับ จนกว่าจะถามจริง)
        $reading->setConversationState('pro_session_awaiting_first_question', true);

        // 🆕 (2026-06-23) pro_session_ready_at = เวลาที่ "ลูกค้าพร้อมพิมพ์คำถามได้แล้ว" (อ้างอิงสำหรับ nudge 1 นาที)
        //   Deep — ส่งคำทำนายเสร็จ = พร้อมถามทันที → ตั้งที่นี่
        //   Celtic — พร้อมหลังพื้นดวง/opening ส่งจริง → ตั้งใน onCelticAllCardsPicked / base chart (ไม่ใช่ที่ card-10)
        if ($type === 'deep') {
            $reading->setConversationState('pro_session_ready_at', now()->toIso8601String());
        }

        Log::info('Fortune ProSession: เปิด session', [
            'reading_id' => $reading->id,
            'type' => $type,
            'window_minutes' => $window,
            'deferred_timer' => $type === 'deep',
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
            // 🆕 (2026-06-23) deferred timer — session เปิดแล้วแต่ยังไม่เริ่มนับ (รอคำถามแรก)
            //   awaiting = ยังอยู่ใน session (ให้ router จับคำถามแรกได้) ; ไม่ awaiting = malformed → ไม่อยู่
            return (bool) $reading->getConversationState('pro_session_awaiting_first_question', false);
        }

        try {
            $started = Carbon::parse($startedAt);
            // 🩹 (2026-05-08 v3 audit) Carbon 3 — diffInMinutes signed by default
            //   ต้องส่ง absolute=true ไม่งั้น now() < $started → return ค่าลบ → never expires
            $elapsed = (int) $started->diffInMinutes(now(), true);
            if ($elapsed >= $windowMin) {
                // 🛟 (2026-08-21) หยุดนาฬิกาถ้ายังมีคำถามที่ "รับแล้วแต่ยังไม่ได้ตอบ"
                //   ต้นตอ FTU-260821-K9664: ลูกค้าถาม → bot เงียบเพราะ buffer โดน deploy กิน →
                //   นาฬิกาเดินต่อจนหมดเวลา → ลูกค้าจ่ายเงินแล้วแต่ได้ "หมดเวลาทำนายแล้วค่ะ" แทนคำตอบ
                //   มีเพดาน PRO_SESSION_PENDING_GRACE_MINUTES อยู่ใน has... แล้ว = ไม่กลายเป็น session อมตะ
                if ($this->hasPendingProSessionQuestion($reading)) {
                    Log::info('Fortune ProSession: หมดเวลาแต่ยังมีคำถามค้าง → ยืดให้ตอบก่อน', [
                        'reading_id' => $reading->id,
                        'window_min' => $windowMin,
                        'elapsed' => $elapsed,
                    ]);

                    return true;
                }

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
     * ⏳ (2026-08-28) กล่องแจ้งเวลาใกล้หมด — โผล่ **ครั้งเดียว** ต่อ 1 เซสชัน
     *
     * เจ้าของสั่ง: เอากล่องรายงานเวลาออกจากทุกคำตอบ ให้โผล่ทีเดียวตอน 3 นาทีสุดท้าย
     *
     * ทำไมต้องมีธง: ถ้าเช็คแค่ `remainingMin <= 3` ลูกค้าที่ถามรัว ๆ ช่วงท้าย
     * จะเจอกล่องเดิมทุกข้อความ = กลับไปเป็นแบบเดิมเป๊ะในช่วงที่กวนใจที่สุด
     *
     * ธงอยู่ใน conversation_state (DB) ไม่ใช่ Cache — deploy รัน cache:clear
     * (= flushdb) 3 หนต่อรอบ เก็บบน Cache แปลว่าเตือนซ้ำได้เรื่อย ๆ
     *
     * @param  int  $remainingMin  นาทีคงเหลือ
     * @return string ท่อนต่อท้ายข้อความ ('' = ไม่ต้องแจ้ง)
     */
    protected function buildProSessionTimeNotice(FortuneReading $reading, int $remainingMin): string
    {
        // ยังเหลือเวลาเยอะ / หมดเวลาแล้ว → เงียบ (หมดเวลามีข้อความปิดของตัวเองอยู่แล้ว)
        if ($remainingMin <= 0 || $remainingMin > 3) {
            return '';
        }

        try {
            if (! empty($reading->getConversationState('time_notice_sent'))) {
                return '';
            }

            $reading->setConversationState('time_notice_sent', true);
        } catch (\Throwable $e) {
            // อ่าน/เขียน state ไม่ได้ → ยอมแจ้งซ้ำ ดีกว่าไม่แจ้งเลยตอนใกล้หมดเวลา
            Log::debug('ProSession: อ่านธงแจ้งเวลาไม่ได้ (ปล่อยแจ้ง)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return "\n\n──────────────────────\n"
            ."⏳ *เหลือเวลาอีก {$remainingMin} นาที* — ถามต่อได้เลยค่ะ หรือเมื่อพอใจพิมพ์ \"ขอบคุณ\" ได้เลยนะคะ";
    }

    /**
     * นาทีคงเหลือใน Pro Session
     */
    protected function getProSessionRemainingMinutes(FortuneReading $reading): int
    {
        $startedAt = $reading->getConversationState('pro_session_started_at');
        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);

        if (empty($startedAt)) {
            // 🆕 (2026-06-23) ยังไม่เริ่มนับ (รอคำถามแรก) → คืนเต็ม window ; malformed → 0
            return $reading->getConversationState('pro_session_awaiting_first_question', false) ? $windowMin : 0;
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
        // 🛟 (2026-08-21) ปิด session = ไม่มีคำถามค้างให้กู้แล้ว — กันคำถามเก่าโผล่มาตอบข้ามวัน
        $this->clearPendingProSessionQuestion($reading);
    }

    /**
     * 🛟 (2026-08-21) จดคำถามที่ "รับแล้วแต่ยังไม่ได้ตอบ" ลง conversation_state (MySQL)
     *
     * ทำไมต้องมี: MessageBuffer เก็บบน Laravel Cache = redis DB 1 และ `php artisan cache:clear`
     *   เรียก `RedisStore::flush()` → `flushdb()` → **ล้างทั้ง database ไม่ใช่ลบตาม prefix**
     *   deploy รัน cache:clear 3 จุด → ถ้าลูกค้าถามพอดีตอนนั้น buffer หายทั้งก้อน
     *   job ตื่นมาเจอ `empty(peek())` → `return;` เงียบ ไม่มี log ไม่มี failed_jobs
     *   (ต้นตอจริง FTU-260821-K9664 — ลูกค้าจ่าย 39฿ ถาม 3 ข้อ ได้คำตอบ 0 ข้อ)
     *
     * conversation_state เป็นคอลัมน์ JSON บน MySQL → deploy ล้างไม่ได้ = แหล่งความจริงสำรอง
     *
     * ⚠️ Deep กับ Celtic ต้องใช้ "คนละคีย์" — ตาข่ายกู้คนละตัวและ dispatch job คนละคลาส
     *   ถ้าใช้คีย์เดียวกัน cron ของ Deep จะไปหยิบคำถาม Celtic แล้วส่งเข้า handler ผิดตัว
     *
     * @param  string  $text  ข้อความดิบที่ลูกค้าพิมพ์
     * @param  string  $scope  'deep' | 'celtic'
     */
    protected function rememberPendingProSessionQuestion(FortuneReading $reading, string $text, string $scope = 'deep'): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        [$qKey, $atKey] = $this->pendingQuestionStateKeys($scope);

        $pending = $reading->getConversationState($qKey, []);
        if (! is_array($pending)) {
            $pending = [];
        }

        // กันบวมถ้าลูกค้ารัวยาว — เก็บพอให้ตอบครบ ไม่ต้องเก็บทั้งชีวิต
        $pending[] = $text;
        if (count($pending) > self::PRO_SESSION_PENDING_MAX) {
            $pending = array_slice($pending, -self::PRO_SESSION_PENDING_MAX);
        }

        $reading->setConversationState($qKey, array_values($pending));
        // ⚠️ เขียน "เวลาข้อความแรกที่ยังค้าง" เท่านั้น — ห้าม reset ทุกครั้งที่พิมพ์
        //   ไม่งั้นลูกค้าที่พิมพ์เรื่อย ๆ จะไม่มีวันแก่พอให้ตาข่ายกู้มองเห็น
        if (empty($reading->getConversationState($atKey))) {
            $reading->setConversationState($atKey, now()->toIso8601String());
        }
    }

    /**
     * 🤝 (2026-08-21, owner) เพิ่งดูดวงแบบจ่ายเงินไปวันนี้ — ห้ามขายซ้ำ
     *
     * owner: "เมื่อเพิ่งดูดวงไป ก็ไม่ควรขายซ้ำอีกภายในวันนั้น ๆ นอกจากเจ้าดวงมีเจตนาจะดูเพิ่ม"
     *
     * เช็คจากใบที่กำลังปิดเอง — ไม่ต้อง query ใหม่ เพราะข้อความปิดท้ายคือจุดจบของใบนี้พอดี
     * นับเฉพาะบิลที่ **จ่ายเงิน** (owner ยืนยัน) — ดวงฟรีรายวันยังชวนต่อได้ตามปกติ
     * เพราะกล่องฟรีมีหางคำชวน + ปุ่ม VIP เป็นสเปกเดิมที่ owner สั่งไว้
     */
    protected function justHadPaidReadingToday(FortuneReading $reading): bool
    {
        if (! $reading->is_paid) {
            return false;
        }

        $at = $reading->paid_at ?: $reading->created_at;
        if (empty($at)) {
            return false;
        }

        try {
            return Carbon::parse($at)->isSameDay(now());
        } catch (\Throwable $e) {
            return false; // parse ไม่ได้ = ใช้ข้อความปกติ (fail-open ฝั่งไม่พัง UX)
        }
    }

    /**
     * 🤝 (2026-08-21) บรรทัดปิดท้ายหลังดูดวงจบ — สลับเป็นแบบ "ไม่ขาย" ถ้าเพิ่งจ่ายวันนี้
     *
     * เดิมทุกทางจบลงท้ายด้วย 'หากต้องการดูใหม่ พิมพ์ "ดูดวง"' = ยื่นขายรอบใหม่ทันที
     * ที่ลูกค้าเพิ่งจ่ายเสร็จ (เคสจริง FTU-260821-K9664 — แถมรอบนั้นยังไม่ได้คำตอบด้วย)
     *
     * ⚠️ ไม่ได้ปิดทาง "เจตนาจะดูเพิ่ม" — ลูกค้าพิมพ์ "ดูดวง" เองยังเข้าโฟลว์ปกติทุกเมื่อ
     *   ตรงนี้แค่เลิก **ยื่นให้ก่อน** เท่านั้น (จับเจตนา ≠ ตื้อขาย)
     *
     * @param  bool  $recallLineShown  ผู้เรียกพิมพ์บรรทัด "อ่านคำทำนายล่าสุด" ไปแล้วหรือยัง
     *                                 (กันบรรทัดซ้ำกันเองในกล่องเดียว)
     */
    protected function closingInviteLine(FortuneReading $reading, bool $recallLineShown = false): string
    {
        if (! $this->justHadPaidReadingToday($reading)) {
            return '💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ ✨';
        }

        return $recallLineShown
            ? '💜 วันนี้พักใจสักหน่อยนะคะ — แม่หมออยู่ตรงนี้เสมอค่ะ ✨'
            : '📖 อยากอ่านคำทำนายอีกครั้ง — พิมพ์ *"อ่านคำทำนายล่าสุด"* ได้ตลอดเลยค่ะ ✨';
    }

    /**
     * 🛟 (2026-08-21) คีย์ conversation_state ของคำถามค้าง แยกตามชนิดเซสชัน
     *
     * @return array{0: string, 1: string} [คีย์รายการคำถาม, คีย์เวลาข้อความแรกที่ค้าง]
     */
    protected function pendingQuestionStateKeys(string $scope): array
    {
        return $scope === 'celtic'
            ? ['celtic_pending_q', 'celtic_pending_q_at']
            : ['pro_session_pending_q', 'pro_session_pending_q_at'];
    }

    /**
     * 🛟 (2026-08-21) ล้างคำถามค้าง — เรียกหลัง "ตอบไปแล้วจริง" หรือ session ปิด
     *
     * @param  string|null  $scope  null = ล้างทั้ง deep และ celtic (ใช้ตอนปิด session)
     */
    protected function clearPendingProSessionQuestion(FortuneReading $reading, ?string $scope = null): void
    {
        foreach ($scope === null ? ['deep', 'celtic'] : [$scope] as $s) {
            [$qKey, $atKey] = $this->pendingQuestionStateKeys($s);

            if (empty($reading->getConversationState($atKey))
                && empty($reading->getConversationState($qKey))) {
                continue; // ไม่มีอะไรให้ล้าง — กันเขียน DB ฟรี ๆ ทุกข้อความ
            }

            $reading->setConversationState($qKey, []);
            $reading->setConversationState($atKey, null);
        }
    }

    /**
     * 🛟 (2026-08-21) มีคำถามค้างที่ยังไม่ได้ตอบอยู่ไหม (ใช้หยุดนาฬิกา + ให้ตาข่ายกู้มองเห็น)
     *
     * @param  int|null  $olderThanSeconds  ถ้าระบุ = ต้องค้างนานกว่านี้ถึงจะนับ (ใช้ในตาข่ายกู้
     *                                      กันไปแย่ง job ปกติที่ยัง debounce อยู่)
     * @param  string|null  $scope  null = นับว่ามีถ้าค้างฝั่งไหนก็ได้ (ใช้ตอนหยุดนาฬิกา)
     */
    protected function hasPendingProSessionQuestion(FortuneReading $reading, ?int $olderThanSeconds = null, ?string $scope = null): bool
    {
        foreach ($scope === null ? ['deep', 'celtic'] : [$scope] as $s) {
            [$qKey, $atKey] = $this->pendingQuestionStateKeys($s);

            $at = $reading->getConversationState($atKey);
            if (empty($at)) {
                continue;
            }

            $pending = $reading->getConversationState($qKey, []);
            if (! is_array($pending) || $pending === []) {
                continue;
            }

            try {
                $since = (int) Carbon::parse($at)->diffInSeconds(now(), true);
            } catch (\Throwable $e) {
                continue; // parse ไม่ได้ = ถือว่าไม่มี ปล่อยให้ flow ปกติเดิน
            }

            // 🛡️ เพดาน — คำถามค้างเก่าเกินไปไม่ควรหยุดนาฬิกาต่อ (กัน session อมตะ)
            if ($since > self::PRO_SESSION_PENDING_GRACE_MINUTES * 60) {
                continue;
            }

            if ($olderThanSeconds === null || $since >= $olderThanSeconds) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🧹 (2026-07-08) เช็คว่า Pro Session window "หมดเวลา" แล้วหรือยัง — non-mutating (ไม่ clear flag)
     *
     * ต่างจาก isInProSession():
     *   - ตัวนี้ไม่มี side-effect (isInProSession clear flag ทันทีเมื่อหมดเวลา — ใช้ dry-run ไม่ได้)
     *   - คืน true = "หมดเวลาแล้ว ควรกวาด flag ทิ้ง" ; false = ยัง linger จริง / ยังไม่เริ่มนับ
     *
     * ใช้โดย sweep cron (clearStaleLingeringProSessions) — ต้นตอ incident 82-customer 2026-07-08:
     *   Celtic finale ผ่าน max_questions_reached/ai_signal คง pro_session_active ไว้ให้ linger
     *   แต่ถ้าลูกค้าเงียบหลัง finale ไม่มี cron ไหนกวาด (celtic/deep-auto-finalize จับเฉพาะ status
     *   ที่ยังไม่ completed) → flag ค้างถาวร → isInPrediction บล็อก "ดูดวง" ครั้งถัดไป
     */
    protected function proSessionWindowExpired(FortuneReading $reading): bool
    {
        if (! $reading->getConversationState('pro_session_active', false)) {
            return false; // ไม่มี flag = ไม่มีอะไรต้องกวาด
        }

        $startedAt = $reading->getConversationState('pro_session_started_at');
        if (empty($startedAt)) {
            // ยังไม่เริ่มนับ (รอคำถามแรก): awaiting=true → ยังไม่หมด ; ไม่ awaiting → malformed ควรกวาด
            return ! (bool) $reading->getConversationState('pro_session_awaiting_first_question', false);
        }

        // 🛟 (2026-08-21) ยังมีคำถามค้างไม่ได้ตอบ = ยังไม่ถือว่าหมดเวลา (sweep cron ห้ามกวาด)
        if ($this->hasPendingProSessionQuestion($reading)) {
            return false;
        }

        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);
        try {
            // 🩹 Carbon 3 — absolute=true เสมอ (กัน now() < started → ค่าลบ → never expires)
            return (int) Carbon::parse($startedAt)->diffInMinutes(now(), true) >= $windowMin;
        } catch (\Throwable $e) {
            return true; // parse ไม่ได้ = malformed → กวาดทิ้ง
        }
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

        // 🛟 (2026-08-21) ห้ามส่ง "หมดเวลาทำนายแล้วค่ะ" ทับหน้าคำถามที่ยังไม่ได้ตอบ
        //   ต้นตอ FTU-260821-K9664: ลูกค้าถาม 19:42-19:43 → เงียบ → 19:51 cron ยิง "หมดเวลา"
        //   = ลูกค้าจ่าย 39฿ ถาม 3 ข้อ ได้คำตอบ 0 ข้อ แถมโดนไล่. ให้ตาข่ายกู้ตอบให้ก่อน
        if ($this->hasPendingProSessionQuestion($reading)) {
            return false;
        }

        $windowMin = (int) $reading->getConversationState('pro_session_window_minutes', self::PRO_SESSION_DEEP_MINUTES);
        if ($windowMin < 1) {
            $windowMin = self::PRO_SESSION_DEEP_MINUTES;
        }

        $startedAt = $reading->getConversationState('pro_session_started_at');
        if (empty($startedAt)) {
            // 🆕 (2026-06-23 bug-hunt) ลูกค้าไม่เคยถาม (timer ยังไม่เริ่ม — defer ตาม Part B) →
            //   ปิดด้วย "abandon window" อ้างอิง pro_session_ready_at เพื่อยังส่งข้อความปิด/ชวนรีวิว
            //   (กัน regression: ลูกค้าจ่ายแล้วเงียบ จะไม่ได้รับข้อความปิดเลยถ้าไม่มี safety net นี้)
            if (! $reading->getConversationState('pro_session_active', false)) {
                return false; // session ปิดไปแล้ว
            }
            $readyAt = $reading->getConversationState('pro_session_ready_at');
            if (empty($readyAt)) {
                return false; // ยังไม่พร้อมให้ถาม (เช่น ยังไม่ส่งคำทำนาย)
            }
            // 🕰️ (2026-06-30) ลูกค้าไม่ถามเลย → สแตนบายรอเต็ม standby window (default 30 นาที)
            //   แล้วค่อยปิด (ระหว่างนั้น cron nudge ตามทุก interval) — owner spec
            $abandonMin = $this->proSessionStandbyMinutes();
            try {
                $elapsedReady = (int) Carbon::parse($readyAt)->diffInMinutes(now(), true);
            } catch (\Throwable $e) {
                return false;
            }

            return $elapsedReady >= $abandonMin;
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
                // 🤝 (2026-08-21) เพิ่งจ่ายวันนี้ = ไม่ยื่นขายรอบใหม่ต่อหน้า (ลูกค้าพิมพ์ "ดูดวง" เองยังได้ปกติ)
                .$this->closingInviteLine($reading, true),
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
        //   ใช้ helper เดียวกับ FB push follow-up (DRY) — gate: enabled+tier scope + มี deep_response
        //   ⚠️ path นี้ส่งเฉพาะ streaming; Deep prod ส่วนใหญ่ delivery แบบ push → CTA ถูกแนบที่
        //      FortuneChannelManager::sendFacebookProSessionFollowUp อีกจุด (กล่องที่ push จริง)
        $msg .= $this->settings->buildVoiceCtaSnippet($reading);

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

        // 🤖 (2026-09-02) แอดมินถามแทนลูกค้าหลัง session ปิด → remaining = 0 → พรอมต์บอก AI ว่า
        //    "ได้คุยอีก 0 นาที" แล้ว AI จะตอบว่าหมดเวลา ⇒ ตอนแอดมินถาม ให้เห็นเป็นอย่างน้อย 10 นาทีเสมอ
        //    ธง one-shot — ล้างทันทีที่อ่าน ไม่กระทบเทิร์นของลูกค้าเอง
        if ((bool) $reading->getConversationState('pro_session_admin_asking', false)) {
            $reading->setConversationState('pro_session_admin_asking', false);
            $remainingMin = max((int) ($remainingMin ?? 0), 10);
        }

        $prompt = $type === 'celtic'
            ? $this->buildCelticProSessionPrompt($reading, $name, $remainingMin)
            : $this->buildDeepProSessionPrompt($reading, $name, $remainingMin);

        // 🏬 (2026-08-21) ตัวตนของเพจสาขา — แปะที่นี่จุดเดียวครอบทั้งเส้น Pro AI และเส้น fallback
        //   (เส้น fallback ใช้ $systemPrompt ก้อนเดียวกันนี้ต่อไปยัง chatWithCustomSystemPromptHistory)
        return \App\Services\Fortune\FortunePageIdentity::appendTo($prompt);
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

        // 🪐 ดวงพื้นของลูกค้า
        //
        // ⚠️ (2026-09-01) เดิมใช้ FortuneChartService::calculatePlanetPositions($dayOfWeek)
        //   ซึ่ง **ไม่ใช่การผูกดวง** — เป็นตารางจัดวางตายตัว (เจ้าชนะ→ภพ1 · มิตร→9/11/5 ·
        //   ศัตรู→6/12/8 · ที่เหลือวน 2/3/4/7) รับพารามิเตอร์แค่ "วันในสัปดาห์"
        //   ⇒ ผลลัพธ์มีแค่ 7 แบบทั้งระบบ คนเกิดวันอังคารได้ดวงเหมือนกันหมดไม่ว่าเกิดปีไหน
        //   AI จึงไม่มีข้อเท็จจริงเฉพาะตัวให้ฟันธง → ตอบกว้างๆ เซฟๆ
        //
        //   ใหม่: ใช้ ThaiAstrologyService::formatPersonBlock() ตัวเดียวกับ Celtic 99
        //   ซึ่งคำนวณดาวจริง 9 ดวงด้วย PlanetEphemeris (Keplerian JPL) + ราศี + ภพ +
        //   เกษตร/อุจ/นิจ + พักร + ทักษา + ดาวเสวยอายุ + นักษัตร/ชง + Life Path + Personal Year
        $birthChartContext = '';
        if ($reading->birth_date) {
            try {
                // 🕛 birthDateTimeForChart() = "Y-m-d" หรือ "Y-m-d H:i" ถ้ารู้เวลา (ลูกค้าบอก/แอดมินกรอก)
                $block = trim((new ThaiAstrologyService)
                    ->formatPersonBlock((string) $reading->birthDateTimeForChart()));

                // ลูกค้าเพิ่งบอกเวลาเกิดเทิร์นนี้ → ให้แม่หมอบอกสั้นๆ ว่าปรับผังแล้ว (ครั้งเดียว)
                $justUpdated = $reading->pullBirthTimeJustUpdated();
                if ($justUpdated !== null && $block !== '') {
                    $block .= "\n🕛 เจ้าชะตาเพิ่งบอกเวลาเกิด {$justUpdated} น. — ผังข้างบนคำนวณใหม่ด้วยเวลานี้แล้ว"
                        ."\n   → เปิดคำตอบด้วยประโยคสั้นๆ ว่ารับเวลาเกิดแล้วและปรับดวงให้ใหม่ (1 ประโยคพอ) แล้วตอบต่อตามปกติ";
                }

                $block .= $this->birthTimeUnparsedDirective($reading);

                if ($block !== '') {
                    // ⚠️ ต้องบอกว่า "ตัวนี้ชนะ" — บิลที่ทำนายไปก่อน 2026-09-01 ใช้ผังดาวชุดเก่า
                    //   ถ้าคำทำนายเดิมในสรุปด้านล่างอ้างดาวไม่ตรงกับผังนี้ AI จะได้ไม่สับสน
                    $birthChartContext = "\n[🪐 ดวงพื้น — คำนวณจริงจากวันเกิด: ใช้ผังนี้เป็นหลัก ห้ามเดาเอง"
                        ." และถ้าคำทำนายเดิมด้านล่างอ้างดาวไม่ตรงกับผังนี้ ให้ยึดผังนี้]\n".$block."\n";
                }
            } catch (\Throwable $e) {
                // ephemeris ล้ม → ปล่อยว่าง ดีกว่าป้อนดวงผิดให้ลูกค้าที่จ่ายเงินแล้ว
                Log::warning('ProSession: ผูกดวง Deep 39 ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
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
3. ❌ *ห้ามรับเรื่องคืนเงิน* — ถ้าลูกค้าขอคืน → บอกแบบนุ่มนวลว่า \"การส่งพลังเปิดประตูแล้ว เป็นการให้บารมีไม่ใช่สินค้า ขอลูกเปิดใจรับไปก่อนค่ะ\"
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
ℹ️ คำสั่งเหล่านี้ระบบจัดการเอง — แม่หมอ *แค่บอกให้ลูกค้าพิมพ์* แล้วระบบจะส่งให้

".FortuneAIService::NO_HEDGE_DIRECTIVE;
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

        // 🌟 (2026-09-02) ผังดวงของลูกค้า — เดิม "คุยต่อหลังบทสรุป" เป็นพรอมต์เดียวของเลน 99 ที่ไม่มีดวงเลย
        //   (พื้นดวงเปิดตัว / Q1 / Q2+ / บทสรุปใหญ่ มีครบ แต่พอมาคุยต่อ 30 นาที AI เหลือแค่ไพ่+ประวัติ)
        //   ลูกค้าถาม "แล้วดวงเรื่องงานล่ะ" หลังบทสรุป → ไม่มีข้อเท็จจริงให้ยึด → มโน
        $celticAstroBlock = '';
        try {
            $astroSource = (string) $reading->getConversationState('celtic_birthdate_text', '');
            if ($astroSource === '' && $reading->birth_date) {
                $astroSource = 'เจ้าชะตาเกิด '.$reading->birth_date->format('d/m/Y');
            }
            // 🕛 ต่อท้ายเวลาเฉพาะตอน "รู้จริง" — ค่ามาตรฐาน 12:00 ห้ามเอาไปบอกว่าลูกค้าบอกมา
            if ($astroSource !== '' && $reading->birthTimeIsKnown()) {
                $astroSource .= ' เวลาเกิด '.FortuneReading::hourToTimeString((float) $reading->birthHourFloat(), false).' น.';
            }
            if ($astroSource !== '') {
                $celticAstroBlock = (new ThaiAstrologyService)->buildCelticBirthAstrologyBlock($astroSource);
                $justUpdated = $reading->pullBirthTimeJustUpdated();
                if ($justUpdated !== null && $celticAstroBlock !== '') {
                    $celticAstroBlock .= "🕛 เจ้าชะตาเพิ่งบอกเวลาเกิด {$justUpdated} น. — ผังข้างบนคำนวณใหม่แล้ว "
                        ."→ เปิดคำตอบด้วยประโยคสั้นๆ ว่ารับเวลาเกิดแล้ว (1 ประโยค) แล้วตอบต่อ\n\n";
                }
                $celticAstroBlock .= $this->birthTimeUnparsedDirective($reading);
            }
        } catch (\Throwable $e) {
            // ผูกดวงไม่ได้ → ทำนายจากไพ่ล้วนเหมือนเดิม
        }

        return "คุณคือ *แม่หมอจันทรา* (อวตารพิเศษ Pro AI สาย Celtic Cross) — หมอดูระดับเซียน เชี่ยวชาญไพ่ยิปซีและจิตวิทยาสูง พูดไทยเท่านั้น
แทนตัวเองด้วย *แม่หมอ/หมอจันทรา* + ลงท้าย *ค่ะ/นะคะ* — ห้าม: ครับ/ผม | หนู/เรา | ดิฉัน

⚠️ *นี่คือ Celtic Pro Session — ลูกค้าจ่าย 99฿ ดูดวงไพ่ครบ 10 ใบ ได้คุยกับแม่หมออีก {$remainingMin} นาที*

[ลูกค้า] ชื่อ: {$name}

{$celticContext}
{$celticAstroBlock}

[หน้าที่ — สำคัญมาก]
1. ✅ *ตอบจากไพ่ทั้ง 10 ใบ* + Q&A ที่ผ่านมา — ห้ามแต่งไพ่ใหม่
2. ✅ *จำบริบทการสนทนา* — ต่อยอดจากคำถามเดิม ไม่ตอบซ้ำ
3. ✅ *ฟันธง* — ห้าม \"อาจจะ/ขึ้นอยู่กับ\" — ใช้ภาษาแม่หมอเซียน
4. ✅ *ตอบ 250-450 คำ* — ลึก สมราคา 99฿
5. ✅ *แม่หมอวัย 40+ น้ำเสียงอบอุ่น เชี่ยวชาญจิตวิทยา* — เรียกลูกค้าว่า \"ลูก\" เสมอ (สลับ \"ลูกดวง\" ได้) — โทนดุแต่รัก ไม่ปลอบ ไม่อ้อม

[ห้ามเด็ดขาด]
1. ❌ *ห้ามตอบเรื่องคนอื่น* — ลูกค้าถามถึงเพื่อน/ญาติ → บอกว่า \"ให้เขามาดูเองนะคะ\"
2. ❌ *ห้ามแนะนำราคา/ขายแพคเกจ* — ไม่ mention ราคา
3. ❌ *ห้ามรับเรื่องคืนเงิน* — ถ้าลูกค้าขอคืน → บอกแบบนุ่มนวลว่า \"การเปิดไพ่ครบสำรับแล้ว แม่หมอส่งพลังให้ครบ การให้บารมีไม่ใช่สินค้าค่ะ ขอลูกเปิดใจรับไปก่อน\"
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
ℹ️ คำสั่งเหล่านี้ระบบจัดการเอง — แม่หมอ *แค่บอกให้ลูกค้าพิมพ์* แล้วระบบจะส่งให้

".FortuneAIService::NO_HEDGE_DIRECTIVE;
    }

    /**
     * 🕛 เจ้าชะตา "พยายามบอกเวลาเกิด" แต่ระบบอ่านไม่ออก → ใช่ไหม
     *
     * ต้องเห็นหน่วยเวลาชัดๆ (ตี/ทุ่ม/โมง/นาฬิกา/HH:MM) หรือวลี "เวลาเกิด/เกิดตอน/เกิดเวลา"
     * ⚠️ ห้ามหลวมกว่านี้ — "เกิดวันที่ 17 ตุลาคม 2508" คือ *วัน* ไม่ใช่เวลา
     *    ถ้าดักกว้างไป แม่หมอจะทวงเวลาเกิดทั้งที่ลูกค้าไม่ได้ตั้งใจบอก
     */
    protected function mentionsBirthTimeIntent(string $text): bool
    {
        if (preg_match('/(เวลาเกิด|เกิดเวลา|เกิดตอน|คลอดตอน|เกิดช่วง)/u', $text)) {
            return true;
        }

        // มีคำบ่งชี้การเกิด + หน่วยเวลาที่ติดกับตัวเลข
        //   ⚠️ ต้องตัด "เกิด" ที่ไม่ได้แปลว่าคลอด ด้วยรายการเดียวกับ extractStatedBirthHour
        //      ไม่งั้น *"หนี้ 2.30 แสน จะเกิดอะไรขึ้น"* ติดธง แล้วแม่หมอทวงเวลาเกิดกลางคำถามหนี้
        return (bool) preg_match('/(เกิด(?!อะไร|เรื่อง|ปัญหา|ขึ้น|ผล|เหตุ|ความ)|คลอด|ลืมตา)/u', $text)
            && (bool) preg_match('/(ตี\s*\d|\d\s*ทุ่ม|\d\s*โมง|\d\s*นาฬิกา|\d{1,2}[:.]\d{2}|เที่ยงคืน)/u', $text);
    }

    /**
     * 🚨 (2026-09-03 — เคสจริง FTU-260903-X0866) กันแม่หมอ "รับปากลอยๆ" เรื่องเวลาเกิด
     *
     * ลูกค้าพิมพ์ *"เวลาเกิดตี5"* → ตัวดึงอ่านไม่ออก → `birth_time` ยังเป็น NULL
     * แต่ AI เห็นข้อความลูกค้าในประวัติแล้วตอบเองว่า
     *   *"รับข้อมูลเวลาเกิดแล้วค่ะ ... ละเอียดขึ้นกว่าการใช้เวลาเที่ยงมาตรฐาน"*
     * ⇒ ผังยังคำนวณจาก 12:00 อยู่ดี = **โกหกลูกค้าที่จ่ายเงินแล้ว**
     *
     * บล็อกในผังบอกแค่ "ยังไม่ได้บอกเวลาเกิด" ซึ่ง AI เลือกเชื่อลูกค้าแทนระบบ
     * → ต้องสั่งตรงๆ ว่า *ห้ามอ้างว่ารับแล้ว* และให้ขอเป็นตัวเลข
     *
     * one-shot: อ่านแล้วล้างธง ไม่งั้นทวงซ้ำทุกเทิร์น
     */
    protected function birthTimeUnparsedDirective(FortuneReading $reading): string
    {
        $raw = $reading->getConversationState('birth_time_unparsed');
        if (empty($raw)) {
            return '';
        }
        $reading->setConversationState('birth_time_unparsed', null);

        // 🎯 (2026-09-03 — เคสจริง FTU-260903-A2742) เรากำลังจะ *ถาม* เวลาเกิดเป็นตัวเลข
        //    ⇒ ข้อความถัดไปของลูกค้าคือ **คำตอบ** ของคำถามนี้ ต้องจำไว้
        //    ไม่งั้นลูกค้าตอบ "19.00" เฉย ๆ (ไม่มีคำว่า "เกิด") แล้วด่านเข้มงวดทิ้งทันที
        //    = บอทถามเอง ลูกค้าตอบให้ แต่ระบบไม่เก็บ แล้ว AI ก็ประกาศว่ารับแล้วอีกรอบ
        $reading->setConversationState('awaiting_birth_time', now()->toIso8601String());

        return "\n🚨 เจ้าชะตาเพิ่งพยายามบอกเวลาเกิด (\"{$raw}\") แต่ *ระบบอ่านค่าไม่ออก* — ผังข้างบนยังคำนวณจาก 12:00 น. เหมือนเดิม"
            ."\n   ❌ ห้ามพูดว่า \"รับเวลาเกิดแล้ว\" / \"ปรับผังให้ใหม่แล้ว\" / \"แม่นขึ้นกว่าเวลามาตรฐาน\" — ยังไม่ได้ปรับอะไรทั้งนั้น"
            ."\n   ✅ ให้ขอใหม่สั้นๆ 1 ประโยค เป็น *ตัวเลข* เช่น \"รบกวนพิมพ์เป็นตัวเลขให้แม่หมออีกครั้งนะคะ เช่น 05:00\" แล้วตอบคำถามต่อตามปกติ";
    }

    /**
     * AI ตอบใน Pro Session — ใช้ Pro key (sensitive purpose) + custom system prompt
     */
    protected function generateProSessionAnswer(FortuneReading $reading, string $messageText, ?array $userProfile): ?array
    {
        // 🕛 (2026-09-02) ลูกค้าบอกเวลาเกิดกลางวง ("เกิดตอน 6 โมงเช้าค่ะ") → เก็บลง DB ก่อนสร้างพรอมต์
        //    ผังในพรอมต์เทิร์นนี้จะคำนวณด้วยเวลาใหม่ทันที (ทั้ง Deep 39 และ Celtic คุยต่อ)
        //    จุดนี้เป็นคอขวดเดียวของทั้งทางตรงและทาง settle-buffer → ไม่ต้องดัก 2 ที่
        //    🎯 ถ้าเทิร์นที่แล้วแม่หมอเพิ่งถาม "เวลาเกิดกี่โมง" → ข้อความนี้คือคำตอบ
        //       ใช้ตัวอ่าน **ผ่อนกฎ** ได้ เพราะรู้บริบทว่าทั้งข้อความคือคำตอบเรื่องเวลา
        //       (ลูกค้าตอบ "19.00" / "19.00 -20.30" เฉย ๆ ไม่มีคำว่า "เกิด" ให้ด่านเข้มงวดยึด)
        $awaitingAt = $reading->getConversationState('awaiting_birth_time');
        $isAnswer = false;
        if (! empty($awaitingAt)) {
            try {
                // มีอายุ 30 นาที — เลยกว่านั้นถือว่าคุยเรื่องอื่นไปแล้ว กลับไปใช้ด่านเข้มงวด
                $isAnswer = \Carbon\Carbon::parse($awaitingAt)->gt(now()->subMinutes(30));
            } catch (\Throwable $e) {
                $isAnswer = false;
            }
            $reading->setConversationState('awaiting_birth_time', null);
        }

        $reading->captureStatedBirthTime(
            $messageText,
            $isAnswer ? FortuneReading::BIRTH_TIME_SOURCE_TIME_ANSWER : 'pro_session'
        );

        // 🚨 บอกเวลาเกิดมาแล้วแต่ยังไม่มีค่าใน DB = ตัวดึงอ่านไม่ออก → ติดธงกันแม่หมอรับปากลอยๆ
        //    (เช็ค birthHourFloat() ไม่ใช่ค่า return ของ capture — capture คืน null ตอน "ค่าเดิมอยู่แล้ว" ด้วย)
        if ($reading->birthTimeIsKnown()) {
            // รู้เวลาแล้ว → ล้างธงค้างจากเทิร์นก่อน ไม่งั้นพรอมต์จะได้ทั้ง "รับแล้ว" และ "อ่านไม่ออก" พร้อมกัน
            if (! empty($reading->getConversationState('birth_time_unparsed'))) {
                $reading->setConversationState('birth_time_unparsed', null);
            }
        } elseif ($this->mentionsBirthTimeIntent($messageText)) {
            // เก็บเฉพาะบรรทัดเดียว ตัดอัญประกาศ — สตริงนี้ถูกยัดลงพรอมต์ในเครื่องหมายคำพูด
            $snippet = preg_replace('/\s+/u', ' ', str_replace(['"', '"', '"'], '', trim($messageText)));
            $reading->setConversationState('birth_time_unparsed', mb_substr((string) $snippet, 0, 40));
        }

        try {
            $aiService = new FortuneAIService($this->settings);
            // 🪪 (2026-09-01) ผูก usage log กับบิล — เดิมเลน Q&A ไม่เรียก forReading เลย
            //   ⇒ ai_api_key_usage_logs.reading_id = NULL ทั้งเลน post_reading_deep/celtic_premium
            //   คิดต้นทุน AI ต่อบิลขาดฝั่งคุยต่อทั้งก้อน (จุด gen หลักผูกครบแล้ว)
            $aiService->forReading($reading);
            $type = (string) $reading->getConversationState('pro_session_type', 'deep');
            $systemPrompt = $this->buildProSessionSystemPrompt($reading, $type);

            // 🤫 (2026-09-02) เจ้าชะตากำลังเล่ายาว ยังไม่จบ → ตอบสั้นแบบรับฟัง ('' = ไม่เข้าเกณฑ์)
            $systemPrompt .= $this->qaBriefReplyDirective($reading);

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
            // 🎚️ (2026-08-17) เลือกโมเดลตามบริบทเหมือน Celtic — luna ปกติ / sol เมื่อคำถามยาก
            //   ก่อนหน้านี้เส้นนี้ไม่เคยแตะโมเดล Pro เลย: getSensitivePoolKey()=NULL +
            //   sensitive_provider='gemini' + ไม่มี key gemini/sensitive → คืน null ทุกครั้ง
            //   → ตกไป chatWithCustomSystemPromptHistory = key แชทฟรี
            //   วัดจริง 30 วัน: post_reading_deep 466 คอล อยู่บน Gemini ฟรีทั้งหมด (มี key TTS ตอบ 99 ครั้ง)
            //   บังคับ provider='openai' เพื่อให้ชื่อโมเดลกับ key เป็นค่ายเดียวกัน
            $proModel = app(\App\Services\Fortune\FortuneModelRouter::class)
                ->proSessionModel($reading, $messageText);

            $result = null;
            try {
                if (method_exists($aiService, 'generatePostReadingDeepResponse')) {
                    $result = $aiService->generatePostReadingDeepResponse(
                        $messageText,
                        $userProfile,
                        $historyMessages,
                        $systemPrompt,
                        'openai',
                        $proModel
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
            //
            // 🔇 (2026-08-28, owner) "นำส่วนที่รายงานเวลา ถามแม่หมอ พิมพ์คำถามต่อไปได้เลย
            //    นั้นออกไปเลย โผล่ทีเดียวตอนใกล้หมดเวลา 3 นาทีสุดท้าย พอ"
            //
            //    ของเดิม (2026-06-08) โชว์ทุกคำตอบสำหรับ deep — ทับสเปกนั้นแล้ว
            //    เหตุผลของเจ้าของ: กล่องนับถอยหลังต่อท้ายทุกข้อความ = เร่งลูกค้ากลาย ๆ
            //    และทำให้คำทำนายดูเหมือนใบเสร็จมากกว่าบทสนทนา
            //
            // ⚠️ "โผล่ทีเดียว" = ครั้งเดียวจริง ๆ ต่อ 1 เซสชัน ไม่ใช่ทุกข้อความในช่วง 3 นาทีท้าย
            //    ธงเก็บใน conversation_state (DB) **ห้ามเก็บบน Cache** — deploy รัน cache:clear
            //    3 หนต่อรอบ ธงจะหาย แล้วลูกค้าที่จ่ายเงินมาจะโดนเตือนซ้ำ
            //    (rule_never_cache_only_for_paid_customer_state)
            $remainingMin = $this->getProSessionRemainingMinutes($reading);
            $footer = $this->buildProSessionTimeNotice($reading, $remainingMin);

            // ⏳ (2026-09-02) จดความยาวคำตอบ — เทิร์นหน้าใช้ตัดสินว่าลูกค้า "อ่านทันไหม" (ดู QaSettleTrait)
            $this->qaNoteAnswerSent($reading, (string) $response);

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
    /**
     * @param  bool  $skipSettle  true = มาจาก ProcessBufferedProSessionMessageJob (ผ่าน settle window มาแล้ว)
     *                            ห้ามลืม — ไม่งั้น job จะ buffer ซ้ำเป็นวงวนไม่รู้จบ
     */
    protected function handleProSession(FortuneReading $reading, string $messageText, ?array $userProfile = null, bool $skipSettle = false): array
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
        // 🤝 (2026-08-29 FTU-260829-M9469) ช่วง "คุยต่อหลังบทสรุป" ของ Celtic 99
        //   ⚠️ ต้องอยู่ **ก่อน** settle-buffer (3c-0) — ไม่งั้น "ขอบคุณ" จะถูกอมเข้า buffer
        //      แล้วโผล่เป็นคำถามให้ AI ตอบอีก 10 วินาทีถัดมา แทนที่จะเป็นคำอวยพรส่งท้าย
        //   ครอบ 3 ทางออกตามสเปกเจ้าของ: ลูกค้าลาเอง / ขอเปิดรอบใหม่ / (เงียบ+เพดานเวลา = cron)
        if ($proType === 'celtic' && $this->isInCelticAftercare($reading)) {
            $aftercare = $this->handleCelticAftercareMessage($reading, $messageText);
            if ($aftercare !== null) {
                return $aftercare;
            }
        }

        if ($proType !== 'celtic' && $this->looksLikeProSessionExitIntent($messageText)) {
            $reading->setConversationState('pro_session_pending_exit', true);
            $reading->setConversationState('pro_session_pending_exit_at', now()->toIso8601String());

            return [
                'action' => 'pro_session_exit_confirm',
                'message' => $this->buildProSessionExitConfirmationMessage($reading),
                'reading' => $reading,
            ];
        }

        // 🎂 (2026-07-25, owner) "เจ้าชะตาแย้งว่าวันเกิดผิด ควรทำนายให้ใหม่ (1 ครั้ง/บิล)"
        //   ต้องดักก่อน AI Pro chat — ไม่งั้น AI ตอบคุยเฉยๆ โดยยังทำนายจากดวงเดิม (ลูกค้าเสียเงินฟรี)
        //   เฉพาะ Deep 39 (Celtic ทำนายจากไพ่เป็นหลัก + มีระบบสับไพ่ใหม่ของตัวเองอยู่แล้ว)
        if ($proType !== 'celtic'
            && $reading->reading_type === FortuneReading::READING_TYPE_DEEP
            && method_exists($this, 'handleBirthdateCorrection')) {
            $correction = $this->handleBirthdateCorrection($reading, $messageText);
            if ($correction !== null) {
                return $correction;
            }
        }

        // 🆕 (2026-06-23, owner) Deep 39 — เริ่มจับเวลา "หลังคำถามแรก" (ข้อความนี้ผ่าน exit-check แล้ว = คำถามจริง)
        //   เปิด session ค้างไว้ตั้งแต่ส่งคำทำนาย → ตอนนี้ลูกค้าถามจริง → เริ่มนับ 7 นาที
        if ((string) $reading->getConversationState('pro_session_type', 'deep') === 'deep'
            && $reading->getConversationState('pro_session_awaiting_first_question', false)) {
            $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
            $reading->setConversationState('pro_session_awaiting_first_question', false);
            $reading->setConversationState('pro_session_nudge_sent', true); // ถามแล้ว = ไม่ต้อง nudge
            Log::info('Fortune ProSession: เริ่มจับเวลา Deep หลังคำถามแรก', [
                'reading_id' => $reading->id,
            ]);
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

        // 3c-0. 📦 (2026-08-17) Settle window — นิ่งรอลูกค้า "รัวคำ" ให้จบก่อนตอบทีเดียว
        //   ยกกลไกเดียวกับ Celtic (FIX D 2026-06-22) มาใช้กับ Deep 39 — เดิม scope 'deep_qa'
        //   ถูกจดไว้ใน MessageBuffer ว่า "Phase 4b — future" แต่ไม่เคยทำ
        //   → ถามรัว 3 ข้อ = ยิง AI 3 ครั้ง ตอบ 3 ครั้งแยกกัน (เปลือง + อ่านยาก + ตอบทับกันเอง)
        //
        //   ⚠️ วางไว้ตรงนี้ (3c) ไม่ใช่หัวเมธอด — ทุกอย่างก่อนหน้านี้ต้องตอบทันที:
        //     ยืนยันปิด session / exit intent / แก้วันเกิด / Celtic 3Q flow
        //     ถ้าไป buffer ที่หัวเมธอด ลูกค้าพิมพ์ "พอแค่นี้" จะค้าง 10 วิ = พัง
        //   ปิดได้ด้วย setting pro_session_settle_seconds = 0
        //   ⏳ (2026-09-02 FTU-260902-V9628) หน้าต่างปรับตามพฤติกรรม — ดู QaSettleTrait
        //     แก้ทั้ง 2 เส้นพร้อมกัน (Celtic + ProSession) — แก้ฝั่งเดียวอีกฝั่งเป็นระเบิดเวลา
        $settleSec = (int) ($this->settings->pro_session_settle_seconds ?? 10);
        if (! $skipSettle && $settleSec > 0) {
            $this->qaTrackRamble($reading);
            $settleSec = $this->qaSettleWindow($reading, $settleSec);
            $dUserId = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
            $dPlatform = $reading->platform;
            if (! $dPlatform || ! in_array($dPlatform, ['facebook', 'line'], true)) {
                $dPlatform = preg_match('/^U[a-f0-9]{32}$/i', $dUserId) ? 'line' : 'facebook';
            }

            if ($dUserId !== '') {
                app(\App\Services\Fortune\MessageBuffer::class)->append('deep_qa', $dUserId, $messageText);

                // 🛟 (2026-08-21) จดคำถามลง conversation_state ด้วย — buffer อยู่บน Cache (redis DB 1)
                //   ซึ่ง `php artisan cache:clear` = `flushdb()` ล้างทั้ง DB ไม่ใช่ลบตาม prefix
                //   ต้นตอ FTU-260821-K9664: ลูกค้าถาม 3 ข้อตอน deploy กำลังรัน → cache:clear กิน buffer
                //   → job ตื่นมาเจอ peek() ว่าง → `return;` เงียบ → คำถามระเหย ไม่มี error ที่ไหนเลย
                //   conversation_state อยู่บน MySQL = deploy ล้างไม่ได้ → กู้คืนได้เสมอ
                $this->rememberPendingProSessionQuestion($reading, $messageText);

                \App\Jobs\ProcessBufferedProSessionMessageJob::dispatch($reading->id, $dPlatform, $dUserId, $settleSec)
                    ->delay(now()->addSeconds($settleSec + 1));

                // ลูกค้าพิมพ์อยู่ = engaged → กัน nudge ตามถามยิงระหว่างรอ window
                $reading->setConversationState('pro_session_nudge_sent', true);
                $reading->setConversationState('pro_session_last_nudge_at', now()->toIso8601String());

                // 💬 ระหว่างนิ่งรอ ให้เห็น "จุดสามจุดกำลังพิมพ์" — ห้ามเพิ่มกล่องข้อความ
                $this->qaSendTypingHint($reading, $settleSec);

                Log::info('Fortune ProSession: settle-buffer คำถาม (นิ่งรอรัว)', [
                    'reading_id' => $reading->id,
                    'settle_sec' => $settleSec,
                    'rambling' => $this->qaIsRambling($reading),
                    'q_preview' => mb_substr($messageText, 0, 40),
                ]);

                return [
                    'action' => 'silent_skip',
                    'message' => null,
                    'reading' => $reading,
                ];
            }
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
     * 🤖 (2026-09-02) แอดมินถามแทนลูกค้า — เลน Deep 39 (คู่แฝดของ CelticCrossService::askQuestionAsAdmin)
     *
     * owner: "แบบ 39 แอดมินควรตั้งคำถามแทนลูกค้าได้จากหลังบ้าน ได้เหมือนแบบ 99 ด้วย"
     *
     * Bypass: ไม่เช็ค window / exit intent / settle-buffer / โควตา — แอดมิน sovereign
     * Side effects (เหมือนลูกค้าถามเอง): เก็บ pro_session_history · push ให้ลูกค้า · takeover log
     * ไม่แตะสถานะ session (ไม่เปิด/ไม่ต่อเวลา) — แค่ตอบ 1 ข้อแล้วส่ง
     *
     * @return array{success:bool,reading_id:int,pushed:bool,response_len:int,response_preview:?string,response_full:?string,elapsed_ms:int,message:?string,platform:?string}
     */
    public function answerProSessionAsAdmin(FortuneReading $reading, string $question, ?int $adminId = null): array
    {
        $start = microtime(true);
        $question = trim($question);
        $result = [
            'success' => false,
            'reading_id' => $reading->id,
            'pushed' => false,
            'response_len' => 0,
            'response_preview' => null,
            'response_full' => null,
            'elapsed_ms' => 0,
            'message' => null,
            'platform' => null,
        ];

        if ($question === '') {
            $result['message'] = 'กรุณาพิมพ์คำถาม';

            return $result;
        }

        $userId = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
        $platform = $reading->platform;
        if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
            $platform = preg_match('/^U[a-f0-9]{32}$/i', $userId) ? 'line' : 'facebook';
        }
        $result['platform'] = $platform;

        // ให้พรอมต์รู้ว่าเป็นเลนไหน (deep = ค่าเริ่มต้นเมื่อไม่เคยเปิด session)
        if (empty($reading->getConversationState('pro_session_type'))) {
            $reading->setConversationState('pro_session_type', 'deep');
        }

        try {
            \App\Models\FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => $adminId,
                'platform' => $platform,
                'action' => 'message',
                'reason' => 'admin_ai_assist',
                'message' => '[ADMIN ASK AI · DEEP] '.mb_substr($question, 0, 500),
            ]);
        } catch (\Throwable $e) {
            // non-critical
        }

        // ธง one-shot ให้พรอมต์ไม่บอก AI ว่า "เหลือ 0 นาที" ตอน session ปิดไปแล้ว (แอดมินถามได้ตลอด)
        $reading->setConversationState('pro_session_admin_asking', true);

        $aiResult = $this->generateProSessionAnswer($reading, $question, null);
        $response = is_array($aiResult) ? trim((string) ($aiResult['message'] ?? '')) : '';
        if ($response === '') {
            $result['message'] = 'AI ไม่ตอบกลับ — ลองใหม่อีกครั้ง';
            $result['elapsed_ms'] = (int) ((microtime(true) - $start) * 1000);

            return $result;
        }

        if ($userId !== '') {
            try {
                $channel = new \App\Services\FortuneChannelManager($this->settings);
                $result['pushed'] = (bool) $channel->sendResponse($platform, $userId, [
                    'action' => 'pro_session_answer',
                    'message' => $response,
                    'reading' => $reading,
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Deep answerProSessionAsAdmin: push ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $result['message'] = 'ไม่มี user_id — ตอบแล้วแต่ push ไม่ได้';
        }

        $result['success'] = true;
        $result['response_len'] = mb_strlen($response);
        $result['response_preview'] = mb_substr($response, 0, 300);
        $result['response_full'] = $response;
        $result['elapsed_ms'] = (int) ((microtime(true) - $start) * 1000);

        Log::info('Deep answerProSessionAsAdmin สำเร็จ', [
            'reading_id' => $reading->id,
            'admin_id' => $adminId,
            'pushed' => $result['pushed'],
            'response_len' => $result['response_len'],
            'elapsed_ms' => $result['elapsed_ms'],
        ]);

        return $result;
    }

    /**
     * 📦 (2026-08-17) ทางเข้าสาธารณะสำหรับ ProcessBufferedProSessionMessageJob
     *
     * เรียก handleProSession ด้วย skipSettle=true — ข้อความที่ส่งมาผ่าน settle window มาแล้ว
     * (ถ้าไม่ข้าม จะ append กลับเข้า buffer แล้ว dispatch job ใหม่ = วนไม่รู้จบ)
     */
    public function handleProSessionBuffered(FortuneReading $reading, string $combined, ?array $userProfile = null): array
    {
        return $this->handleProSession($reading, $combined, $userProfile, true);
    }

    /**
     * 📦 (2026-08-17) เปิด isInProSession ให้ job เช็คได้ว่า session ยังเปิดอยู่ไหม
     *   (session อาจหมดเวลาระหว่างรอ settle window → ไม่ต้องตอบ ปล่อย cron แจ้งหมดเวลา)
     */
    public function isInProSessionPublic(FortuneReading $reading): bool
    {
        return $this->isInProSession($reading);
    }

    /**
     * 🛟 (2026-08-21) หยิบคำถามค้างจาก conversation_state (สำเนาที่ deploy ล้างไม่ได้) แบบ atomic
     *
     * ใช้เมื่อ MessageBuffer บน Cache หายไป (deploy `cache:clear` = `flushdb` ทั้ง redis DB 1)
     *
     * ⚠️ ต้อง atomic จริง — ในเคสต้นตอ FTU-260821-K9664 มี job ถูก dispatch ไว้ **3 ตัว**
     *   (ลูกค้าพิมพ์ 3 ข้อความ = dispatch 3 ครั้ง) ถ้าทั้ง 3 ตัวมาหยิบสำเนาเดียวกัน = ตอบซ้ำ 3 รอบ
     *   lock + refresh ในนี้ทำให้มีตัวเดียวที่ได้ของ ตัวที่เหลือได้ '' แล้วเงียบไป
     *
     * @param  string  $scope  'deep' | 'celtic'
     * @return string ข้อความรวม ('' = ไม่มีของ / job อื่นหยิบไปแล้ว)
     */
    public function takePendingProSessionQuestionPublic(FortuneReading $reading, string $scope = 'deep'): string
    {
        $lock = \Illuminate\Support\Facades\Cache::lock('fortune-prosession-pending:'.$scope.':'.$reading->id, 30);

        // non-blocking: ไม่ได้ lock → คืน false → แปลว่ามี job อื่นกำลังหยิบอยู่
        $result = $lock->get(function () use ($reading, $scope) {
            $reading->refresh();

            [$qKey] = $this->pendingQuestionStateKeys($scope);

            $pending = $reading->getConversationState($qKey, []);
            if (! is_array($pending) || $pending === []) {
                return '';
            }

            $this->clearPendingProSessionQuestion($reading, $scope);

            $texts = array_filter(array_map(fn ($t) => trim((string) $t), $pending), fn ($t) => $t !== '');

            return implode("\n", $texts);
        });

        return is_string($result) ? $result : '';
    }

    /**
     * 🛟 (2026-08-22) อ่านคำถามค้าง "แบบไม่หยิบออก" — คู่กับ take...() ข้างบน
     *
     * ทำไมต้องมี: `isInProSession()` **ไม่ใช่ read-only** — หมดเวลาเมื่อไหร่มันเรียก
     *   `clearProSessionFlags()` ซึ่งเรียก `clearPendingProSessionQuestion()` ต่อในคอลเดียวกัน
     *   ⇒ พอ job เช็ค session แล้วได้ false คำถามค้างก็ถูกล้างไปเรียบร้อยแล้ว
     *      บรรทัดถัดไปจะไม่เหลืออะไรให้กู้เลย (ต้นตอ FTU-260822-P2391)
     *
     * ⚠️ ต้อง peek **ก่อน** เช็ค session และห้าม take ตรงนั้น —
     *    ถ้า take ก่อน ธงจะหายไป → isInProSession() มองไม่เห็นคำถามค้าง → ตัดสินว่าหมดเวลาทันที
     *    (นี่คือเหตุผลที่ลำดับเดิมเช็ค session ก่อนหยิบ — ลำดับถูก แต่ขาดสำเนากันไว้)
     *
     * @param  string  $scope  'deep' | 'celtic'
     * @return array{text: string, at: string|null} text='' = ไม่มีคำถามค้าง
     */
    public function peekPendingProSessionQuestionPublic(FortuneReading $reading, string $scope = 'deep'): array
    {
        [$qKey, $atKey] = $this->pendingQuestionStateKeys($scope);

        $pending = $reading->getConversationState($qKey, []);
        if (! is_array($pending) || $pending === []) {
            return ['text' => '', 'at' => null];
        }

        $texts = array_filter(array_map(fn ($t) => trim((string) $t), $pending), fn ($t) => $t !== '');

        return [
            'text' => implode("\n", $texts),
            'at' => $reading->getConversationState($atKey),
        ];
    }

    /**
     * 🛟 (2026-08-22) จอง "สิทธิ์ตอบย้อนหลัง" แบบ atomic — ผู้ชนะรายเดียวเท่านั้นที่ได้ตอบ
     *
     * จำเป็นเพราะทางตอบย้อนหลังเกิดขึ้น **หลัง** pending_q ถูกล้างไปแล้ว = ไม่มี token
     * ให้แย่งกันเหมือนทางปกติอีก ถ้าไม่กันตรงนี้ job ที่ค้างคิวจะตอบซ้ำกันทุกตัว
     *   เคสจริง FTU-260822-P2391: หลังคิวคลาย มี job รันไล่กัน **4 ตัว** ภายใน 3 นาที
     *   ⇒ ลูกค้าจะได้คำตอบเดียวกัน 4 กล่องรวด
     *
     * @param  string  $scope  'deep' | 'celtic'
     * @return bool true = คุณคือผู้ชนะ ตอบได้ / false = มีคนตอบไปแล้ว เงียบไว้
     */
    public function claimLateProSessionAnswerPublic(FortuneReading $reading, string $scope = 'deep'): bool
    {
        $stateKey = $scope === 'celtic' ? 'celtic_late_answered_at' : 'pro_session_late_answered_at';

        $lock = \Illuminate\Support\Facades\Cache::lock('fortune-prosession-late:'.$scope.':'.$reading->id, 30);

        // non-blocking — ไม่ได้ lock แปลว่ามี job อื่นกำลังจองอยู่พอดี
        $result = $lock->get(function () use ($reading, $stateKey) {
            $reading->refresh();

            if (! empty($reading->getConversationState($stateKey))) {
                return false; // ตอบย้อนหลังไปแล้วรอบหนึ่ง
            }

            $reading->setConversationState($stateKey, now()->toIso8601String());

            return true;
        });

        return $result === true;
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

    /**
     * 🔔 (2026-06-23, owner) จำนวนวินาทีที่รอ "ลูกค้าถามคำถามแรก" ก่อนส่งกล่องตามถาม
     *   default 60 (1 นาที) — owner: "ไม่มาถามภายใน 1 นาทีหลังระบบพร้อมให้ถาม ก็ส่งกล่องตาม"
     */
    protected function proSessionNudgeAfterSeconds(): int
    {
        $s = (int) ($this->settings->pro_session_nudge_after_seconds ?? 60);

        return $s > 0 ? $s : 60;
    }

    /**
     * 🕰️ (2026-06-30) สแตนบายรอลูกค้าที่ "ยังไม่ถามเลย" กี่นาที ก่อน auto-finalize (default 30)
     *   owner spec: ลูกค้าเงียบหลังพื้นดวง/คำทำนาย → รอ 30 นาที (ตามระหว่างนั้น) แล้วสรุปให้เลย
     */
    protected function proSessionStandbyMinutes(): int
    {
        $m = (int) ($this->settings->pro_session_standby_minutes ?? 30);

        return $m > 0 ? $m : 30;
    }

    /**
     * 🔔 (2026-06-30) ตามลูกค้าให้เริ่มถามทุกกี่นาที ระหว่างสแตนบาย (default 10)
     */
    protected function proSessionNudgeIntervalMinutes(): int
    {
        $m = (int) ($this->settings->pro_session_nudge_interval_minutes ?? 10);

        return $m > 0 ? $m : 10;
    }

    /**
     * 🔔 (2026-06-30) reading นี้ควร "ตามให้เริ่มถาม" ตอนนี้ไหม (nudge ทุก interval ระหว่างสแตนบาย)
     *   ใช้ pro_session_ready_at (ตั้งตอนพร้อมให้ถามจริง — Deep: ส่งคำทำนาย / Celtic: ส่งพื้นดวง)
     *
     *   owner spec 2026-06-30: ลูกค้ายังไม่ถามเลย → ตามทุก 10 นาที ระหว่างสแตนบาย 30 นาที
     *     (เดิม: ตามครั้งเดียวแล้วค้าง). เลย standby แล้ว → หยุดตาม (ปล่อย auto-finalize จัดการ)
     *   idempotent ผ่าน pro_session_last_nudge_at (เว้นระยะ interval ต่อครั้ง)
     */
    protected function isProSessionAwaitingNudge(FortuneReading $reading): bool
    {
        if (! $reading->getConversationState('pro_session_active', false)) {
            return false;
        }
        if (! $reading->getConversationState('pro_session_awaiting_first_question', false)) {
            return false; // ลูกค้าถามแล้ว (timer เริ่มแล้ว) → ไม่ต้องตาม
        }
        // 🛡️ (2026-06-23 bug-hunt) ลูกค้ากำลังถูกถามยืนยันออกจาก session (พิมพ์ขอบคุณ/พอแล้ว) → ห้ามตาม (ขัดเจตนา)
        if ($reading->getConversationState('pro_session_pending_exit', false)) {
            return false;
        }
        // Celtic ยังรอวันเกิดอยู่ = ยังไม่ถึงขั้น "ถามคำถาม" → ไม่ตาม
        if ($reading->getConversationState('celtic_birthdate_pending', false)) {
            return false;
        }
        $readyAt = $reading->getConversationState('pro_session_ready_at');
        if (empty($readyAt)) {
            return false; // ยังไม่พร้อมให้ถาม (เช่น Celtic ยังไม่ส่งพื้นดวง)
        }
        try {
            $elapsedMin = Carbon::parse($readyAt)->diffInMinutes(now(), true);
        } catch (\Throwable $e) {
            return false;
        }

        $interval = $this->proSessionNudgeIntervalMinutes();
        $standby = $this->proSessionStandbyMinutes();

        // เลยสแตนบายแล้ว → หยุดตาม (auto-finalize จะสรุปให้เอง)
        if ($elapsedMin >= $standby) {
            return false;
        }

        // 🔔 (2026-07-25) ตามครั้งเดียวพอ — ลดกล่องซ้ำ (เจ้าของ: "ส่งกล่องข้อความซ้ำหลายกล่าย")
        //   เดิมตามทุก 10 นาทีตลอดสแตนบาย 30 นาที = ลูกค้าที่อ่านจบแล้วไม่อยากถามต่อ โดนตาม 3 รอบ
        //   ⚠️ ห้ามใช้ pro_session_window_minutes มาตัด — window นับจาก pro_session_started_at
        //      ซึ่งตั้งตอน "ถามคำถามแรก" เท่านั้น แต่ nudge มีไว้สำหรับคนที่ยังไม่ถามเลย
        //      (started_at ยังว่าง = เวลายังไม่เริ่มเดิน ลูกค้ายังถามได้เต็มเวลา) → ถ้าเอา window
        //      มาเทียบกับ elapsed (ซึ่งนับจาก ready_at) = คนละเรือนนาฬิกา และ Deep (7 < interval 10)
        //      จะไม่ถูกตามเลยแม้แต่ครั้งเดียว
        if ((int) $reading->getConversationState('pro_session_nudge_count', 0) >= 1) {
            return false;
        }
        // ยังไม่ถึงรอบตามครั้งแรก (นับจากพร้อมให้ถาม)
        if ($elapsedMin < $interval) {
            return false;
        }
        // เว้นระยะจากการตามครั้งล่าสุด ≥ interval
        $lastNudge = $reading->getConversationState('pro_session_last_nudge_at');
        if (! empty($lastNudge)) {
            try {
                if (Carbon::parse($lastNudge)->diffInMinutes(now(), true) < $interval) {
                    return false;
                }
            } catch (\Throwable $e) {
                // parse ไม่ได้ → ถือว่าตามได้ (ปลอดภัยกว่าเงียบ)
            }
        }

        return true;
    }

    /**
     * 🔔 (2026-06-23) ดึง reading ที่พร้อมให้ถามแต่ลูกค้ายังเงียบ (สำหรับ cron nudge)
     *   public — ให้ FortuneProSessionNudge command เรียก
     */
    public function getProSessionsAwaitingNudge(int $limit = 30, ?int $specificId = null): \Illuminate\Support\Collection
    {
        $query = FortuneReading::query()
            ->whereIn('reading_type', [
                FortuneReading::READING_TYPE_DEEP,
                FortuneReading::READING_TYPE_CELTIC_CROSS,
            ])
            ->where('is_paid', true)
            ->where('paid_at', '>=', now()->subHours(2)); // ready ภายใน ~2 ชม. หลังจ่าย

        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            $query->limit(120); // pre-filter cap กัน load หนัก
        }

        return $query->orderBy('paid_at', 'desc')->get()
            ->filter(fn ($r) => $this->isProSessionAwaitingNudge($r))
            ->take(max(1, $limit))
            ->values();
    }

    /**
     * 🔔 (2026-06-23) สร้างกล่องข้อความ "ตามให้เริ่มถามคำถาม" (per type)
     *   ย้ำว่าเวลาเริ่มนับหลังถามคำถามแรก (ไม่กดดันลูกค้า)
     */
    public function buildProSessionNudgeMessage(FortuneReading $reading): array
    {
        $name = $reading->resolveCustomerName();
        $type = (string) $reading->getConversationState('pro_session_type', 'deep');

        if ($type === 'celtic') {
            // 🪬 (2026-06-30) โหมดดูคุณไสย์ → ตามด้วยหัวข้อของ/คุณไสย์ ไม่ใช่ รัก/งาน/เงินทั่วไป
            $bmForced = false;
            try {
                $bmForced = app(\App\Services\CelticCrossService::class)->isBlackMagicModeForced($reading);
            } catch (\Throwable $e) {
                // non-blocking → ใช้ข้อความปกติ
            }

            if ($bmForced) {
                $msg = "🪬 คุณ{$name}คะ — แม่หมอล็อกพลังไพ่ทั้ง 10 ใบทะลุเรื่องของ/คุณไสย์รออยู่แล้วนะคะ ✨\n\n"
                    ."💬 *อยากให้แม่หมอเจาะเรื่องไหนก่อนดีคะ?* พิมพ์ถามมาได้เลย\n"
                    ."🌟 โดนของ/คุณไสย์ไหม / ใครทำ / จะแก้-ถอน-ป้องกันยังไง — แม่หมอฟันธงจากไพ่ให้\n\n"
                    .'⏳ ไม่ต้องรีบนะคะ — เวลาคุยจะเริ่มนับ *หลังจากเจ้าชะตาถามคำถามแรก* ค่ะ';
            } else {
                $msg = "🌙 คุณ{$name}คะ — แม่หมอเปิดไพ่ทั้ง 10 ใบรออยู่แล้วนะคะ ✨\n\n"
                    ."💬 *อยากให้แม่หมอดูเรื่องไหนก่อนดีคะ?* พิมพ์คำถามมาได้เลย\n"
                    ."🌟 ความรัก / การงาน / การเงิน / สุขภาพ / ครอบครัว — แม่หมอทำนายจากไพ่ให้\n\n"
                    .'⏳ ไม่ต้องรีบนะคะ — เวลาคุยจะเริ่มนับ *หลังจากเจ้าชะตาถามคำถามแรก* ค่ะ';
            }
        } else {
            $msg = "🌙 คุณ{$name}คะ — แม่หมอยังรอเจ้าชะตาอยู่นะคะ ✨\n\n"
                ."💬 *มีเรื่องไหนอยากให้แม่หมอช่วยดูต่อไหมคะ?* พิมพ์ถามได้เลย\n"
                ."🪐 แม่หมอจะอ่านจากดวงเดิม + ไพ่ที่เปิดให้ — ตอบให้ละเอียดขึ้น\n\n"
                .'⏳ ไม่ต้องรีบนะคะ — เวลาคุยจะเริ่มนับ *หลังจากเจ้าชะตาถามคำถามแรก* ค่ะ';
        }

        return [
            'action' => 'pro_session_nudge',
            'message' => $msg,
            'reading' => $reading,
        ];
    }

    /**
     * 🔔 (2026-06-30) บันทึกว่าตามให้ถามไปแล้ว 1 ครั้ง (นับรอบ + เวลาล่าสุด สำหรับเว้นระยะ interval)
     *   เดิม: set pro_session_nudge_sent=true (ครั้งเดียว) → เปลี่ยนเป็นนับรอบ ตามได้ทุก interval
     */
    public function markProSessionNudgeSent(FortuneReading $reading): void
    {
        $count = (int) $reading->getConversationState('pro_session_nudge_count', 0);
        $reading->setConversationState('pro_session_nudge_count', $count + 1);
        $reading->setConversationState('pro_session_last_nudge_at', now()->toIso8601String());
        // เก็บ flag เดิมไว้ backward-compat (reset ตอนลูกค้าถามจริงยังใช้อยู่)
        $reading->setConversationState('pro_session_nudge_sent', true);
        $reading->setConversationState('pro_session_nudge_sent_at', now()->toIso8601String());
    }
}
