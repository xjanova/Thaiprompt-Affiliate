<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneReading Model
 *
 * จัดการบันทึกการทำนายแต่ละครั้ง
 * รองรับทั้งผู้ใช้ที่สมัครสมาชิกและไม่สมัครสมาชิก
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $facebook_user_id
 * @property string|null $facebook_user_name
 * @property string|null $facebook_comment_id
 * @property string|null $facebook_post_id
 * @property array $questions
 * @property array|null $categories
 * @property string $ai_response
 * @property array|null $user_profile
 * @property array|null $user_posts_context
 * @property string $ai_provider
 * @property string|null $ai_model
 * @property int|null $tokens_used
 * @property bool $is_paid
 * @property float $amount_paid
 * @property \Carbon\Carbon|null $paid_at
 * @property string $response_type
 * @property \Carbon\Carbon|null $responded_at
 * @property int $view_count
 * @property string $reading_type ประเภทคำทำนาย: basic = พื้นฐาน, deep = เชิงลึก
 * @property string|null $reading_image_url URL รูปคำทำนายที่สร้างส่งให้ผู้ใช้
 * @property string|null $user_image_url URL รูปที่ผู้ใช้ส่งมาผ่าน Messenger
 * @property int|null $rating
 * @property string|null $feedback
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneReading extends Model
{
    // 🏬 (2026-08-10) ระบบสาขา — ติดป้าย fortune_page_id ให้บิลอัตโนมัติ
    use \App\Models\Concerns\BelongsToFortunePage;
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_readings';

    /**
     * สถานะ conversation ที่เป็นไปได้
     */
    public const STATUS_NEW = 'new';

    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const STATUS_BASIC_DONE = 'basic_done';

    public const STATUS_COLLECTING_BIRTHDATE = 'collecting_birthdate';

    public const STATUS_COLLECTING_QUESTIONS = 'collecting_questions';

    /**
     * 🎯 (2026-04-28) Discovery Chat Mode
     * AI เป็นหมอจิตวิทยา ชวนคุยเนียนๆ เก็บวันเกิด + เรื่องที่กังวล
     * แทน flow แบบ rigid (ขอวันเกิด → ขอคำถาม)
     */
    public const STATUS_DISCOVERY_CHAT = 'discovery_chat';

    /**
     * Confirm หลัง AI สรุปว่ารู้แล้วลูกค้าต้องการอะไร
     * ลูกค้าตอบ "ใช่" → จ่ายเงิน → tarot flow
     */
    public const STATUS_DISCOVERY_CONFIRM = 'discovery_confirm';

    public const STATUS_COLLECTING_TAROT = 'collecting_tarot';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_COMPLETED = 'completed';

    // ───────────────────────────────────────────────────────────
    // 🔮 Celtic Cross Tarot Mode (2026-04-29) — 99฿ ค่าครู
    // ───────────────────────────────────────────────────────────
    /**
     * Tier choice — รอลูกค้าเลือก 39฿ deep หรือ 99฿ Celtic Cross
     * ใช้แทน discovery_chat (เพราะ feedback ว่าไม่เวิร์ค)
     */
    public const STATUS_TIER_CHOICE = 'tier_choice';

    public const STATUS_CELTIC_PENDING_PAYMENT = 'celtic_pending_payment';

    public const STATUS_CELTIC_PICKING = 'celtic_picking'; // เลือกไพ่ 1-10 (track ใน conversation_state.celtic_pick_index)

    public const STATUS_CELTIC_AWAITING_QUESTION = 'celtic_awaiting_question'; // รอ Q1, Q2, หรือ Q3

    public const STATUS_CELTIC_GENERATING = 'celtic_generating'; // AI กำลังทำนาย Q ปัจจุบัน

    public const STATUS_CELTIC_QA_PROMPT = 'celtic_qa_prompt'; // หลังตอบ Q เสร็จ ถามว่าจะถามต่อไหม

    // ───────────────────────────────────────────────────────────
    // 💳 Stripe Payment Method (2026-05-09) — บัตรต่างประเทศ
    // ───────────────────────────────────────────────────────────
    /** รอลูกค้าเลือกวิธีชำระ — QR Thai vs Stripe (บัตรต่างประเทศ) */
    public const STATUS_AWAITING_PAYMENT_METHOD = 'awaiting_payment_method';

    /** Stripe Checkout Session สร้างแล้ว รอลูกค้ากดลิงก์จ่ายผ่านบัตร */
    public const STATUS_PENDING_STRIPE_PAYMENT = 'pending_stripe_payment';

    /** วิธีชำระเงิน */
    public const PAYMENT_METHOD_QR_THAI = 'qr_thai';

    public const PAYMENT_METHOD_STRIPE = 'stripe';

    public const READING_TYPE_BASIC = 'basic';

    public const READING_TYPE_DEEP = 'deep';

    public const READING_TYPE_CELTIC_CROSS = 'celtic_cross';

    /**
     * 🎁 (2026-05-03) ทำนายฟรี 1 ใบ — เฉพาะลูกค้าใหม่ครั้งแรก/platform
     * จั่วไพ่ 1 ใบ + Gemini ทำนายสถานการณ์ปัจจุบัน + ทางออก
     * จบแล้วชวนซื้อ 39/99 เนียน — ปฏิเสธ → คำลา + ปรัชญา (ไม่ฮาร์ดเซล)
     */
    public const READING_TYPE_FREE_CARD = 'free_card';

    // ───────────────────────────────────────────────────────────
    // 🎁 Free Card Reading (2026-05-03) — ฟรี 1 ใบ ครั้งแรกครั้งเดียว
    // ───────────────────────────────────────────────────────────
    /** สถานะหลังจั่วไพ่ + AI ทำนายเสร็จ — รอลูกค้าตอบ Quick Reply (39/99/ไม่สนใจ) */
    public const STATUS_FREE_PREDICTED = 'free_predicted';

    /** สถานะหลังลูกค้าปฏิเสธ upsell — ส่งคำลา + ปรัชญาแล้ว (ปิด conversation) */
    public const STATUS_FREE_DECLINED = 'free_declined';

    // ───────────────────────────────────────────────────────────
    // 🛑 (2026-05-06) Pay-Later (Request-Before-Pay) — ลบทั้งระบบ
    //   user spec: "นำออกให้หมดไม่ต้องเช็คว่ามีบิลค้าง สร้างปัญหามาก"
    //   ทุกคนต้อง pay-first เท่านั้น
    // ───────────────────────────────────────────────────────────
    // STATUS_AWAITING_DELIVERY_CONFIRM constant ลบทิ้ง — ไม่มี Pay-Later flow แล้ว
    // (DB rows เก่าที่ status='awaiting_delivery_confirm' จะถูก close โดย findActiveConversation
    //  เพราะไม่อยู่ใน VALID_STATUSES — fall through เป็น orphan → pay-first ปกติ)

    /**
     * รายการ status ที่หมายถึง "รอชำระเงิน" — ครอบคลุมทั้ง Deep 39฿ และ Celtic 99฿
     *
     * ใช้ใน whereIn() เวลาคิวรี่หา pending bills ทุกประเภท
     */
    public const PENDING_PAYMENT_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CELTIC_PENDING_PAYMENT,
    ];

    /**
     * 🏷️ (2026-07-05) สถานะ "รอชำระ" สำหรับ "แสดงผล/ฟิลเตอร์ในหลังบ้าน" เท่านั้น
     *
     * กว้างกว่า PENDING_PAYMENT_STATUSES — รวมบิลที่ลูกค้ายังไม่จ่ายตั้งแต่
     * เลือกวิธีจ่าย (awaiting_payment_method) → รอโอน (pending_payment/celtic) → รอ Stripe
     *
     * ⚠️ ใช้เฉพาะหน้า admin billing (filter status=pending + KPI รอชำระ + status pill)
     *    ห้ามนำไปใช้ใน flow จับคู่เงิน/หมดอายุ — จุดนั้นต้องใช้ PENDING_PAYMENT_STATUSES (แคบ) เท่านั้น
     */
    public const PENDING_DISPLAY_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CELTIC_PENDING_PAYMENT,
        self::STATUS_AWAITING_PAYMENT_METHOD,
        self::STATUS_PENDING_STRIPE_PAYMENT,
    ];

    /**
     * 🔮 Celtic active states — หลังจ่ายแล้ว แต่ยังไม่จบ session
     *
     * ใช้เป็น "lock guard" — เมื่อ status ใดๆ ในนี้ active:
     *   - ห้าม flow อื่น (Deep 39฿, Free Card, tier menu) แทรก
     *   - รูป/สลิป/sticker จากลูกค้า → ตอบเป็น "นำกลับ pick-card / awaiting-question"
     *   - ลูกค้าหาย/หลุด แล้วกลับ → resume ที่ position/state เดิมเป๊ะๆ
     *
     * ⚠️ ห้ามรวม STATUS_CELTIC_PENDING_PAYMENT (รอจ่าย) — นั่นใช้ PENDING_PAYMENT_STATUSES แทน
     */
    public const CELTIC_ACTIVE_STATUSES = [
        self::STATUS_CELTIC_PICKING,
        self::STATUS_CELTIC_AWAITING_QUESTION,
        self::STATUS_CELTIC_GENERATING,
        self::STATUS_CELTIC_QA_PROMPT,
    ];

    /**
     * 🌙 (2026-07-02) Deep-39 active states — ระหว่างทำนายอยู่ (จ่ายแล้ว/กำลังเก็บ input จากลูกค้า)
     *
     * เคสจริง FTU-260702-F3343 (reading 8448): ลูกค้า Deep-39 จ่ายแล้ว → บอทขอวันเกิด
     *   (status = collecting_birthdate) แต่ pre-handler อื่น (paid-claim ขอสลิป / rebuttal บิลเก่า /
     *   slip-image) เช็ก "ลูกค้ามีบิล active ไหม" ด้วยลิสต์ที่ "ลืมใส่" 3 สถานะนี้ → มองว่าไม่มีบิล
     *   active → แย่งข้อความ (วันเกิด) ไปตอบผิด (ขอสลิป/ตอบโต้บิลเก่า) → ทำนายไม่ได้ ลูกค้าโวยวาย
     *
     * ⚠️ ทุกจุดที่ถามว่า "ลูกค้ากำลังทำนาย Deep อยู่ไหม" ต้องรวมลิสต์นี้ (source-of-truth เดียว)
     *   คู่กับ CELTIC_ACTIVE_STATUSES (Celtic 99) — ครอบทั้งสอง flow
     */
    public const DEEP_ACTIVE_STATUSES = [
        self::STATUS_COLLECTING_BIRTHDATE,
        self::STATUS_COLLECTING_QUESTIONS,
        self::STATUS_COLLECTING_TAROT,
    ];

    /**
     * 🚦 (2026-05-06) Active reading statuses — รวมทั้ง Deep 39 + Celtic 99
     *
     * ใช้ในการ "ปิดปุ่ม Quick Reply ลอย" (default QR) ระหว่างทำนาย
     * เพราะลูกค้าสับสนได้ถ้าเห็นปุ่ม "ดูดวง 39฿ / 99฿" ขณะกำลังทำนายอยู่
     */
    public const ACTIVE_READING_STATUSES = [
        self::STATUS_AWAITING_CONFIRMATION,
        self::STATUS_COLLECTING_BIRTHDATE,
        self::STATUS_COLLECTING_QUESTIONS,
        self::STATUS_DISCOVERY_CHAT,
        self::STATUS_DISCOVERY_CONFIRM,
        self::STATUS_COLLECTING_TAROT,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_TIER_CHOICE,
        self::STATUS_CELTIC_PENDING_PAYMENT,
        self::STATUS_CELTIC_PICKING,
        self::STATUS_CELTIC_AWAITING_QUESTION,
        self::STATUS_CELTIC_GENERATING,
        self::STATUS_CELTIC_QA_PROMPT,
    ];

    /**
     * 🎯 (2026-05-17) "Locked" states — กำลังจ่าย / จ่ายแล้ว / รอผลคำทำนาย
     *
     * ใช้ใน /aistop takeover bypass — admin /aistop ห้ามขัดสถานะเหล่านี้
     * (กันลูกค้าจ่ายเงินแล้วค้าง หรือกรอกข้อมูลค้างกลางคัน)
     *
     * แตกต่างจาก ACTIVE_READING_STATUSES — ไม่รวม "soft" states
     * (AWAITING_CONFIRMATION, TIER_CHOICE, DISCOVERY_*) ที่ลูกค้ายังไม่ตัดสินใจ
     */
    public const LOCKED_FLOW_STATUSES = [
        self::STATUS_COLLECTING_BIRTHDATE,
        self::STATUS_COLLECTING_QUESTIONS,
        self::STATUS_COLLECTING_TAROT,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CELTIC_PENDING_PAYMENT,
        self::STATUS_CELTIC_PICKING,
        self::STATUS_CELTIC_AWAITING_QUESTION,
        self::STATUS_CELTIC_GENERATING,
        self::STATUS_CELTIC_QA_PROMPT,
    ];

    /**
     * 🔒 (2026-05-20) "In Prediction" states — ลูกค้าจ่ายแล้วและกำลังทำนาย
     *
     * นโยบาย: ระหว่างนี้ห้ามมีการสร้างบิลใหม่ หรือออกนอกเรื่องทำนาย เด็ดขาด
     * - PAID = 39฿ AI กำลัง gen คำทำนาย
     * - CELTIC_PICKING = 99฿ ลูกค้ากำลังเปิดไพ่ 10 ใบ
     * - CELTIC_AWAITING_QUESTION = 99฿ รอลูกค้าถามคำถาม
     * - CELTIC_GENERATING = 99฿ AI กำลัง gen คำตอบ
     * - CELTIC_QA_PROMPT = 99฿ รอคำถามถัดไป
     *
     * แตกต่างจาก LOCKED_FLOW_STATUSES — ไม่รวม pending_payment (ยังไม่จ่าย)
     *
     * ⚠️ (2026-07-31 FTU-260731-N0948) **ลิสต์นี้ไม่ครอบ Deep-39 ที่จ่ายแล้ว**
     *   Deep-39 เป็น Pay-First — จ่ายเงิน → state PAID อยู่แค่ ~1 วินาที → ย้ายเข้า
     *   collecting_birthdate/questions/tarot (= DEEP_ACTIVE_STATUSES) ทันที
     *   ⇒ IN-PREDICTION Hard Guard (FortuneConversationService::processMessage) "มองไม่เห็น"
     *      Deep-39 เกือบตลอด flow ทำนาย
     *
     *   จงใจไม่เพิ่ม DEEP_ACTIVE_STATUSES เข้ามาที่นี่ เพราะ Hard Guard จะ short-circuit
     *   ข้าม hook ~1300 บรรทัด (เช็คสถานะ / โอนแล้ว / สลิป / payment handler) ที่ flow Deep
     *   ใช้งานจริงอยู่ — เปลี่ยนแล้วเสี่ยงพังกว้างกว่าที่แก้
     *
     *   ✅ การกัน "อะไรก็ตามแทรกระหว่างทำนาย Deep" ทำที่ปลายทางแทน:
     *      1. paid bypass ของ pre-filter (processMessage) — ลูกค้าจ่ายแล้วห้ามโดน filter บล็อก
     *      2. FortuneChannelManager::buildActiveBillContextMessage() — กันกล่องทักทาย
     *         โผล่ตอน DEEP_ACTIVE_STATUSES / CELTIC_ACTIVE_STATUSES
     *   ถ้าจะย้ายมากันที่ Hard Guard จริง ๆ ต้องยกมาเป็นงานแยก + ทดสอบ flow Deep ครบทุก state
     */
    public const IN_PREDICTION_STATUSES = [
        self::STATUS_PAID,
        self::STATUS_CELTIC_PICKING,
        self::STATUS_CELTIC_AWAITING_QUESTION,
        self::STATUS_CELTIC_GENERATING,
        self::STATUS_CELTIC_QA_PROMPT,
    ];

    /**
     * 🔮 (2026-05-20) "AI Generating" subset — AI กำลังทำงานจริง ๆ ห้ามตอบทุกกรณี
     *
     * ใช้แยกพฤติกรรม guard:
     * - state ใน list นี้ → silent_skip ทุกข้อความ (รอ AI ส่งผลให้)
     * - state อื่นใน IN_PREDICTION_STATUSES → ปล่อย state machine handler (รอ user input)
     */
    public const AI_GENERATING_STATUSES = [
        self::STATUS_PAID,
        self::STATUS_CELTIC_GENERATING,
    ];

    /**
     * 🎂 (2026-05-21) หาวันเกิดล่าสุดของ user (ใช้ใน DM greeting "ดวงประจำวันสั้นๆ")
     *
     * Look up ล่าสุดที่ลูกค้าเคยกรอก birth_date — ไม่จำกัด is_paid (free reading
     * ก็มีวันเกิดได้). คืน Carbon|null
     *
     * @param  string  $userId  facebook_user_id หรือ platform_user_id
     */
    public static function findLatestBirthdate(string $userId, ?string $platform = null): ?\Carbon\Carbon
    {
        // 🎂 (2026-08-21) ย้ายไส้ในไป BirthdateResolver — แหล่งความจริงเดียวของทั้งระบบ
        //   ของเดิมเรียงด้วย latest('updated_at') ซึ่งผิด: updated_at ขยับทุกครั้งที่
        //   setConversationState() ⇒ บิลฟรีที่ยัง active กลบบิลที่จ่ายเงินไปแล้ว
        //   ตอนนี้ resolver เรียง is_paid ก่อนเสมอ (ดูคอมเมนต์ในคลาสนั้น)
        //
        //   $platform = null → ไม่กรอง platform ชั้น credits (คงพฤติกรรมเดิมของ caller เก่า 4 จุด)
        $hit = \App\Services\Fortune\BirthdateResolver::resolve($userId, $userId, $platform);

        return $hit['date'] ?? null;
    }

    /**
     * เช็คว่า user มี reading ที่ active อยู่หรือไม่ (cached 30s ลด DB hit)
     *
     * @param  string  $platform  'facebook' | 'line'
     * @param  string  $userId  Facebook page-scoped ID หรือ LINE user ID
     */
    public static function hasActiveReading(string $platform, string $userId): bool
    {
        $cacheKey = "fortune_active_reading:{$platform}:{$userId}";

        return (bool) \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addSeconds(30),
            function () use ($platform, $userId) {
                // 🩹 (2026-05-08) fortune_readings ไม่มี column 'line_user_id' —
                //   universal field สำหรับ LINE = 'platform_user_id'
                //   เคสจริง: ลูกค้าเลือก 39/99 → query นี้ throw SQLSTATE[42S22]
                //   → bot ตอบ error → ดูดวงไม่ต่อ
                $column = $platform === 'facebook' ? 'facebook_user_id' : 'platform_user_id';

                // 🔒 (2026-05-20) รวม IN_PREDICTION_STATUSES ด้วย — กัน default QR
                //   ปรากฏระหว่าง STATUS_PAID (39฿ AI gen) ที่ ACTIVE list เดิมไม่มี
                $statuses = array_unique(array_merge(
                    self::ACTIVE_READING_STATUSES,
                    self::IN_PREDICTION_STATUSES,
                ));

                return self::where($column, $userId)
                    ->whereIn('conversation_status', $statuses)
                    ->exists();
            }
        );
    }

    /**
     * ล้าง cache hasActiveReading (เรียกเมื่อ status เปลี่ยน)
     */
    public static function clearActiveReadingCache(string $platform, string $userId): void
    {
        \Illuminate\Support\Facades\Cache::forget("fortune_active_reading:{$platform}:{$userId}");
    }

    /**
     * 🌙 (2026-05-25) เช็คว่า user มี reading ที่จ่ายเงินสำเร็จในเดือนนี้ (calendar month) หรือยัง
     *
     * USER RULE: ห้าม DM/pitch ขายซ้ำคนที่ดูดวงสำเร็จในเดือนเดียวกัน
     * - เกณฑ์ "ดูสำเร็จ" = is_paid=true (ครอบทุก reading_type — deep/celtic/free upgrade)
     * - "เดือนนี้" = ตั้งแต่ startOfMonth() ปัจจุบัน (reset อัตโนมัติวันที่ 1 ของเดือนถัดไป)
     *
     * ใช้ที่:
     *   - FortuneMarketingController::getRecipients — exclude ออกจาก outbound campaigns
     *   - FortuneConversationService::shouldSuppressSalesPitch — guard inbound pricing pitch
     *
     * Cache 5 นาที — ลด DB hit (สำคัญสำหรับ marketing scan ลูกค้าหลายพัน) + ทันเวลาพอ
     * Cache key มี Y-m → switch อัตโนมัติเมื่อขึ้นเดือนใหม่
     *
     * @param  string|null  $facebookUserId  FB page-scoped ID
     * @param  string|null  $platformUserId  LINE/Universal user ID (optional)
     */
    public static function hasPaidReadingThisCalendarMonth(?string $facebookUserId, ?string $platformUserId = null): bool
    {
        $cacheRoot = $facebookUserId ?: $platformUserId;
        if (empty($cacheRoot)) {
            return false;
        }

        $monthKey = now()->format('Y-m');
        $cacheKey = "fortune:paid_this_month:{$monthKey}:{$cacheRoot}";

        return (bool) \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($facebookUserId, $platformUserId) {
                return self::query()
                    ->where(function ($q) use ($facebookUserId, $platformUserId) {
                        if (! empty($facebookUserId)) {
                            $q->where('facebook_user_id', $facebookUserId);
                        }
                        if (! empty($platformUserId)) {
                            $q->orWhere('platform_user_id', $platformUserId);
                        }
                    })
                    ->where('is_paid', true)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->exists();
            }
        );
    }

    /**
     * 🚫 (2026-07-31) เพิ่งดูดวงแบบจ่ายเงินภายใน N วันที่ผ่านมาหรือไม่ (rolling)
     *
     * owner: "การ DM ต้องไม่ DM ไปหาลูกค้าที่เพิ่งดูแบบจ่ายเงินไปอย่างน้อย 7 วัน
     *         ตอนนี้ยังไม่เป็นเช่นนั้น"
     *
     * 🐛 ทำไมเลิกใช้ของเดิม (hasPaidReadingThisCalendarMonth):
     *   มันนับ "เดือนปฏิทิน" → ลูกค้าจ่ายวันที่ 31 ก.ค. พอขึ้น 1 ส.ค. เดือนใหม่
     *   guard รีเซ็ตทันที = โดน DM ขายซ้ำหลังดูดวงไปแค่ข้ามคืน
     *   ยิ่งจ่ายปลายเดือนยิ่งโดนเร็ว (จ่าย 30 → พัก 1 วัน / จ่าย 1 → พัก 30 วัน)
     *   ระยะพักไม่แน่นอนแล้วแต่ว่าจ่ายวันไหนของเดือน = ไม่ยุติธรรมกับลูกค้า
     *
     * ⚠️ (2026-08-01) ตัวนี้ **แทนที่** ของเดิมใน DM guard ทั้ง 3 เส้นทางแล้ว
     *   (owner: "อยากเปลี่ยนเป็น 7 วันแทน ไม่เอา 1 เดือนแล้ว ลบกฎเก่าทิ้งไป")
     *   นับวันแบบทบครอบงานของกฎเดือนอยู่แล้ว — เก็บทั้งสองไว้ = โค้ดที่ไม่ทำอะไรเพิ่ม
     *
     *   hasPaidReadingThisCalendarMonth ยังอยู่เพราะใช้ที่อื่นคนละงาน:
     *   FortuneConversationService:19395 ใส่ป้าย PAID_THIS_MONTH ให้ AI ตอนคุย
     *   (บอก AI ว่าลูกค้าจ่ายเดือนนี้แล้ว — ไม่ใช่ด่านบล็อก DM)
     *
     * @param  int  $days  จำนวนวันย้อนหลัง (default 7 ตามที่ owner กำหนด)
     */
    public static function hasPaidReadingWithinDays(?string $facebookUserId, ?string $platformUserId = null, int $days = 7): bool
    {
        $cacheRoot = $facebookUserId ?: $platformUserId;
        if (empty($cacheRoot)) {
            return false;
        }

        $cacheKey = "fortune:paid_within_days:{$days}:".now()->toDateString().":{$cacheRoot}";

        return (bool) \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($facebookUserId, $platformUserId, $days) {
                return self::query()
                    ->where(function ($q) use ($facebookUserId, $platformUserId) {
                        if (! empty($facebookUserId)) {
                            $q->where('facebook_user_id', $facebookUserId);
                        }
                        if (! empty($platformUserId)) {
                            $q->orWhere('platform_user_id', $platformUserId);
                        }
                    })
                    ->where('is_paid', true)
                    // ⚠️ ใช้ paid_at เป็นหลัก (เวลาที่จ่ายจริง) — created_at คือเวลาสร้างบิล
                    //    บิลที่สร้างไว้นานแล้วเพิ่งมาจ่ายวันนี้ ต้องนับจากวันที่จ่าย
                    ->where(function ($q) use ($days) {
                        $q->where('paid_at', '>=', now()->subDays($days))
                            ->orWhere(function ($q2) use ($days) {
                                $q2->whereNull('paid_at')
                                    ->where('created_at', '>=', now()->subDays($days));
                            });
                    })
                    ->exists();
            }
        );
    }

    /**
     * 🧹 ล้าง cache hasPaidReadingThisCalendarMonth — เรียกหลังจ่ายเงินสำเร็จ
     * เพื่อให้ guard ทำงานทันทีในข้อความถัดไป (ไม่ต้องรอ 5 นาที)
     */
    public static function clearPaidThisMonthCache(?string $facebookUserId, ?string $platformUserId = null): void
    {
        $monthKey = now()->format('Y-m');
        $today = now()->toDateString();

        foreach (array_filter([$facebookUserId, $platformUserId]) as $key) {
            \Illuminate\Support\Facades\Cache::forget("fortune:paid_this_month:{$monthKey}:{$key}");
            // 🚫 (2026-07-31) ล้าง rolling-7-day ด้วย ไม่งั้น guard ใหม่ยังปล่อย DM
            //    ออกได้อีก 5 นาทีหลังลูกค้าเพิ่งจ่ายเงิน
            \Illuminate\Support\Facades\Cache::forget("fortune:paid_within_days:7:{$today}:{$key}");
        }
    }

    /**
     * ตำแหน่ง Celtic Cross 10 ตำแหน่งมาตรฐาน
     *
     * Layout (ตามภาพมาตรฐาน):
     *               [10]
     *         [3]   [9]
     *         ↓     [8]
     *  [5]  [1+2]  [6]
     *               [7]
     *         [4]
     */
    public const CELTIC_POSITIONS = [
        1 => ['name' => 'หัวใจของเรื่อง', 'description' => 'สถานการณ์หลักที่เจ้าชะตากำลังเผชิญ', 'short_en' => 'present'],
        2 => ['name' => 'อุปสรรค', 'description' => 'สิ่งที่ขวางหรือท้าทาย ไพ่นี้ต้องอ่านในมุมว่ามันคือสิ่งที่ขวาง', 'short_en' => 'challenge'],
        3 => ['name' => 'จิตสำนึก / เป้าหมาย', 'description' => 'สิ่งที่ตระหนัก สิ่งที่อยากได้ใจกลาง', 'short_en' => 'goal'],
        4 => ['name' => 'จิตใต้สำนึก / รากฐาน', 'description' => 'รากของเรื่อง สิ่งที่ฝังลึกในใจ', 'short_en' => 'foundation'],
        5 => ['name' => 'อดีต', 'description' => 'เหตุการณ์ที่เพิ่งผ่าน หรือสิ่งที่กำลังเลือนหายไป', 'short_en' => 'past'],
        6 => ['name' => 'อนาคตอันใกล้', 'description' => 'สิ่งที่กำลังจะเกิดในอนาคตอันใกล้', 'short_en' => 'near_future'],
        7 => ['name' => 'ตัวเจ้าชะตา', 'description' => 'ทัศนคติ ท่าทาง พลังของเจ้าชะตาที่มีต่อเรื่องนี้', 'short_en' => 'self'],
        8 => ['name' => 'อิทธิพลภายนอก', 'description' => 'คน สิ่งแวดล้อมรอบตัว ที่กำลังส่งผล', 'short_en' => 'external'],
        9 => ['name' => 'ความหวัง & ความกลัว', 'description' => 'ภายในใจที่ซ่อนไว้ ทั้งหวังและกลัว', 'short_en' => 'hopes_fears'],
        10 => ['name' => 'ผลลัพธ์', 'description' => 'จุดจบของเรื่องราวนี้ ตามแนวโน้มปัจจุบัน', 'short_en' => 'outcome'],
    ];

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'bill_reference',
        'fortune_page_id',   // 🏬 สาขา/เพจต้นทางของบิลนี้
        'user_id',
        'facebook_user_id',
        'facebook_user_name',
        'facebook_comment_id',
        'facebook_post_id',
        'platform',
        'platform_user_id',
        'questions',
        'categories',
        'ai_response',
        'basic_response',
        'deep_response',
        'user_profile',
        'user_posts_context',
        'birth_date',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'is_paid',
        'amount_paid',
        'amount_received',
        // 💰 (2026-06-05) โอนขาด → ทยอยเติม (partial payment)
        'partial_paid_total',
        'partial_target_total',
        'partial_rounds',
        'partial_transrefs',
        'partial_hold_at',
        'paid_at',
        'sms_notification_id',
        'unique_payment_amount_id',
        'sender_info',
        'sender_bank',
        'is_floating',
        'response_type',
        'responded_at',
        'reading_type',
        'conversation_status',
        'conversation_state',
        'reading_image_url',
        'user_image_url',
        'view_count',
        'rating',
        'feedback',
        'transfer_reported',
        'transfer_reported_at',
        // Admin Takeover Fields (ระบบเทคโอเวอร์ — แม่หมอ/แอดมินคุยแทน AI)
        'admin_takeover_until',
        'admin_takeover_by',
        'admin_takeover_reason',
        'admin_takeover_started_at',
        // 🔮 Celtic Cross Mode (2026-04-29)
        'celtic_summary_image_path',
        'celtic_questions_used',
        'celtic_first_answered_at',
        // 🎙️ Voice Summary (TTS) — 2026-05-08
        'voice_audio_token',
        'voice_audio_path',
        'voice_audio_disk',  // 🌥️ (2026-05-18) driver ที่เซฟ audio: local/r2/s3/gcs/firebase
        'voice_audio_url',   // 🌥️ (2026-05-18) full URL (สำหรับ Firebase ที่มี token ใน URL)
        'voice_audio_duration_ms',
        'voice_audio_provider',
        'voice_audio_chars',
        'voice_audio_generated_at',
        // 💳 Stripe Checkout — 2026-05-09
        'payment_method',
        'service_fee',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'stripe_paid_at',
        // 💳 Stripe customer region — 2026-05-22 (th=ในไทย no fee / foreign=+15฿)
        'stripe_customer_region',
        // 🔍 Fuzzy Payment Match — 2026-05-15
        'fuzzy_approved_at',
        'fuzzy_approved_delta',
        'fuzzy_approved_sms_id',
        'fuzzy_approved_name_score',
        // 🧾 SlipOK slip verification — 2026-05-31
        'slip_image_path',
        'slip_received_at',
        'slipok_verified_at',
        'slipok_trans_ref',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'questions' => 'array',
        'categories' => 'array',
        'user_profile' => 'array',
        'user_posts_context' => 'array',
        'conversation_state' => 'array',
        'birth_date' => 'date',
        'is_paid' => 'boolean',
        'amount_paid' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'paid_at' => 'datetime',
        'is_floating' => 'boolean',
        'responded_at' => 'datetime',
        'view_count' => 'integer',
        'tokens_used' => 'integer',
        'rating' => 'integer',
        'transfer_reported' => 'boolean',
        'transfer_reported_at' => 'datetime',
        'admin_takeover_until' => 'datetime',
        'admin_takeover_started_at' => 'datetime',
        // 🔮 Celtic Cross
        'celtic_questions_used' => 'integer',
        'celtic_first_answered_at' => 'datetime',
        // 🎙️ Voice Summary (TTS) — 2026-05-08
        'voice_audio_duration_ms' => 'integer',
        'voice_audio_chars' => 'integer',
        'voice_audio_generated_at' => 'datetime',
        // 💳 Stripe Checkout — 2026-05-09
        'service_fee' => 'decimal:2',
        'stripe_paid_at' => 'datetime',
        // 🔍 Fuzzy Payment Match — 2026-05-15
        'fuzzy_approved_at' => 'datetime',
        'fuzzy_approved_delta' => 'decimal:2',
        'fuzzy_approved_sms_id' => 'integer',
        'fuzzy_approved_name_score' => 'integer',
        // 🧾 SlipOK slip verification — 2026-05-31
        'slip_received_at' => 'datetime',
        'slipok_verified_at' => 'datetime',
        // 💰 (2026-06-05) Partial payment (โอนขาด → ทยอยเติม)
        'partial_paid_total' => 'decimal:2',
        'partial_target_total' => 'decimal:2',
        'partial_rounds' => 'integer',
        'partial_transrefs' => 'array',
        'partial_hold_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_paid' => false,
        'amount_paid' => 0,
        'view_count' => 0,
        'response_type' => 'private_message',
        'reading_type' => 'basic',
        'conversation_status' => 'new',
        'platform' => 'facebook',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ SMS Payment Notification
     */
    public function smsNotification(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentNotification::class, 'sms_notification_id');
    }

    /**
     * Scope: เฉพาะบิลลอย (ยังไม่ระบุตัวตนลูกค้า)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFloating($query)
    {
        return $query->where('is_floating', true);
    }

    /**
     * Scope: เฉพาะที่ชำระผ่าน SMS
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeViaSms($query)
    {
        return $query->whereNotNull('sms_notification_id');
    }

    /**
     * Scope: เฉพาะการทำนายที่ชำระเงินแล้ว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope: เฉพาะการทำนายฟรี
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope: เฉพาะของผู้ใช้ Facebook คนใดคนหนึ่ง
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFacebookUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId);
    }

    /**
     * Scope: เฉพาะการทำนายวันนี้
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: เฉพาะการทำนายที่ได้รับการตอบกลับแล้ว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }

    /**
     * Scope: เฉพาะการทำนายเชิงลึก
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeep($query)
    {
        return $query->where('reading_type', 'deep');
    }

    /**
     * Scope: เฉพาะการทำนายพื้นฐาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBasic($query)
    {
        return $query->where('reading_type', 'basic');
    }

    /**
     * 🎁 Scope: เฉพาะการทำนายฟรี 1 ใบ (ระบบใหม่ 2026-05-03)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFreeCard($query)
    {
        return $query->where('reading_type', self::READING_TYPE_FREE_CARD);
    }

    /**
     * 🎁 ตรวจสอบว่าผู้ใช้ใช้สิทธิ์ทำนายฟรี 1 ใบแล้วหรือยัง (ตาม platform + platform_user_id)
     *
     * นโยบาย: ฟรีครั้งเดียวเท่านั้นต่อ platform_user_id
     *   - FB user A กับ LINE user A เป็นคนละสิทธิ์ (link cross-platform ไม่ได้)
     *   - นับว่าใช้สิทธิ์เมื่อ free_card reading มี responded_at != null
     *     (หมายถึง AI ตอบสำเร็จแล้ว) — ถ้าจั่วแล้ว AI fail ก่อนตอบ → ยังไม่นับ ลองใหม่ได้
     *
     * @param  string  $platform  'facebook' หรือ 'line'
     * @param  string  $platformUserId  ID ของผู้ใช้ใน platform นั้น
     * @return bool true = ใช้แล้ว / false = ยังเป็น first-timer
     */
    public static function hasUsedFreeCard(string $platform, string $platformUserId): bool
    {
        // หา free reading ล่าสุดที่ AI ตอบสำเร็จแล้ว
        $latestFree = self::where('platform', $platform)
            ->where(function ($q) use ($platformUserId) {
                // รองรับทั้งคอลัมน์ใหม่ (platform_user_id) และเก่า (facebook_user_id) เผื่อ legacy data
                $q->where('platform_user_id', $platformUserId)
                    ->orWhere('facebook_user_id', $platformUserId);
            })
            ->where('reading_type', self::READING_TYPE_FREE_CARD)
            ->whereNotNull('responded_at')
            ->latest('responded_at')
            ->first();

        // ยังไม่เคยใช้สิทธิ์เลย → eligible
        if (! $latestFree) {
            return false;
        }

        // 🎁 (2026-05-04) Monthly claim reset — รีเซ็ตได้ตามกิจกรรมในกลุ่ม
        //   ถ้ามี FortuneMonthlyFreeClaim ใหม่กว่า responded_at ของ free reading ล่าสุด
        //   → ถือว่าฟรียังไม่ใช้ (ลูกค้าได้สิทธิ์ใหม่ผ่านการ claim รายเดือน)
        try {
            $latestClaim = \App\Models\FortuneMonthlyFreeClaim::where('psid', $platformUserId)
                ->where('platform', $platform)
                ->latest('claimed_at')
                ->first();

            if ($latestClaim
                && $latestClaim->claimed_at
                && $latestFree->responded_at
                && $latestClaim->claimed_at->greaterThan($latestFree->responded_at)) {
                // claim ใหม่กว่า reading ล่าสุด → reset สิทธิ์
                return false;
            }
        } catch (\Throwable $e) {
            // ถ้า table ยังไม่มี (migration ยังไม่รัน) — fall back ใช้ logic เดิม
        }

        return true; // ใช้แล้ว ยังไม่มี claim ใหม่
    }

    /**
     * 🎁 ตรวจสอบว่าควรเสนอ "ทำนายฟรี" ปุ่มให้ลูกค้าหรือไม่
     *
     * เงื่อนไข: settings เปิด + ลูกค้ายังไม่เคยใช้สิทธิ์ฟรี
     */
    public static function shouldOfferFreeCard(string $platform, string $platformUserId): bool
    {
        $settings = FortuneTellingSetting::getSettings();
        if (! $settings->isFreeReadingEnabled()) {
            return false;
        }

        return ! self::hasUsedFreeCard($platform, $platformUserId);
    }

    // 🛑 (2026-05-06) Pay-Later (Request-Before-Pay) — ลบทั้งหมด
    //   เดิมเก็บ: hasUsedRequestBeforePay(), shouldUseRequestBeforePay(),
    //            isPayLaterFlow(), PAY_LATER_ENABLED constant
    //   user spec: "นำออกให้หมด สร้างปัญหามาก"
    //   Migration safety: DB column conversation_state JSON เก็บได้ทุกอย่าง
    //                     existing rows ที่มี is_request_before_pay = true ไม่ break
    //                     (no code reads it anymore — orphan flag, ไม่กระทบ)

    // ============================================================
    // 🔒 Must-Pay-First Lock (2026-05-03) — บล็อกทุก service ถ้ามีบิลค้าง
    // ============================================================

    /**
     * จำนวน revive ครั้งสูงสุดก่อน block admin-only
     *
     * รอบที่ 1 = บิลแรก expire → revive อัตโนมัติเมื่อกลับมา (UPA ใหม่)
     * รอบที่ 2 = บิล revive expire → revive อีก
     * รอบที่ 3 = บิล revive expire → revive อีก (รอบสุดท้าย)
     * รอบที่ 4 = block — ต้องทักแอดมินเท่านั้น
     */
    public const MAX_BILL_REVIVE_COUNT = 3;

    /**
     * 🔒 ค้นหาบิลที่ "blocking" — ยังไม่จ่ายและต้องบังคับให้จ่ายก่อนทำอะไรได้
     *
     * นโยบาย (ตามที่ user spec 2026-05-03):
     *   - ลูกค้ามีบิล deep/celtic ที่ยังไม่จ่าย → lock ทุก service
     *   - ทุกข้อความที่ส่งมา → bot redirect ไปจ่ายเงิน + resend QR
     *   - free_card ไม่นับเป็นบิล (ไม่มีค่าใช้จ่าย)
     *
     * Returns:
     *   - reading object ของบิลล่าสุดที่ยังไม่จ่าย
     *   - null ถ้าไม่มีบิลค้าง
     *
     * Detection criteria:
     *   - reading_type IN [deep, celtic_cross]
     *   - is_paid = false
     *   - bill_reference != null (เคยสร้างบิลจริง — ไม่ใช่ placeholder)
     *   - status = pending_payment (active) OR completed (expired by cron)
     *
     * @param  string  $platform  'facebook' หรือ 'line'
     * @param  string  $platformUserId
     * @return self|null
     */
    /**
     * ระยะเวลา lookback (วัน) สำหรับ blocking unpaid bills
     *
     * บิลที่ค้างเก่ากว่านี้ ไม่ block ลูกค้าอีกต่อไป (เริ่มต้นใหม่ได้)
     * เหตุผล: ลูกค้าอาจเปลี่ยนใจ, เปลี่ยนเครื่อง, หรือลืมไปแล้ว — ห้ามล็อกตลอดชีวิต
     */
    public const BLOCKING_BILL_LOOKBACK_DAYS = 30;

    public static function findBlockingUnpaidBill(string $platform, string $platformUserId): ?self
    {
        // 🛑 (2026-05-06) Pay-Later total removal — return null เสมอ
        //   user spec: "ไม่ต้องเช็คว่ามีบิลค้าง อะไรที่เกี่ยวกับระบบนี้ นำออกให้หมด"
        //   ไม่มีระบบ Request-Before-Pay แล้ว → ไม่มี "บิลค้างต้องทวง"
        //   ลูกค้า pay-first ปกติ ไม่จ่าย = เลิกๆ ปล่อยไป
        return null;
    }

    /**
     * 🔄 นับจำนวน revive ที่ผ่านมาของ reading นี้ (ดูจาก conversation_state)
     */
    public function getReviveCount(): int
    {
        return (int) ($this->getConversationState('revive_count', 0));
    }

    /**
     * 🚫 เช็คว่าบิลนี้ถึง revive cap (ห้าม revive ต่อ — ต้อง admin only)
     */
    public function reachedReviveLimit(): bool
    {
        return $this->getReviveCount() >= self::MAX_BILL_REVIVE_COUNT;
    }

    /**
     * 🔄 Revive บิลที่ expired — สร้าง UPA ใหม่ + reset state กลับไปรอจ่าย
     *
     * ใช้เมื่อลูกค้ากลับมาหลัง bill expire (status=COMPLETED + is_paid=false)
     *
     * Logic:
     *   1. เพิ่ม revive_count
     *   2. ถ้าเกิน MAX_BILL_REVIVE_COUNT → return null (caller ต้อง block + admin)
     *   3. สร้าง UPA ใหม่ (อาจได้ amount ใหม่ที่ unique กว่าเดิม)
     *   4. อัพเดท reading: amount_paid, unique_payment_amount_id, status กลับเป็น pending
     *   5. เก็บ bill_reference เดิม (ลูกค้าจะได้รู้ว่าเป็นบิลเก่า)
     *   6. reset reminder flags ทั้งหมด (จะส่งทวงใหม่ใน cycle ถัดไป)
     *
     * @return self|null reading หลัง revive / null ถ้า reach limit
     */
    public function reviveBillForRepay(): ?self
    {
        // 🔒 (2026-05-03 audit fix #2) Race-safe via DB transaction + row lock
        //    เดิม: 2 messages พร้อมๆ → 2 UPAs สร้างพร้อมกัน → 1 UPA orphan
        //    ใหม่: lockForUpdate() บล็อกอีก message ให้รอ → re-check limit หลัง lock
        try {
            return \Illuminate\Support\Facades\DB::transaction(function () {
                /** @var self|null $locked */
                $locked = self::where('id', $this->id)->lockForUpdate()->first();
                if (! $locked) {
                    return null;
                }

                // Re-check limit หลังจาก lock (กันเคส count เพิ่มขึ้นใน race)
                if ($locked->reachedReviveLimit()) {
                    return null;
                }

                $basePrice = $locked->reading_type === self::READING_TYPE_CELTIC_CROSS
                    ? (float) (FortuneTellingSetting::getSettings()->celtic_cross_price ?? 99)
                    : (float) (FortuneTellingSetting::getSettings()->deep_reading_price ?? 39);

                $newUpa = UniquePaymentAmount::generate(
                    $basePrice,
                    $locked->id,
                    'fortune_reading',
                    self::billTimeoutMinutes() // ⏰ (2026-06-12) เดิม 30 — ตาม setting (default 3 ชม.)
                );

                if (! $newUpa) {
                    \Log::warning('FortuneReading::reviveBillForRepay — UPA generate fail', [
                        'reading_id' => $locked->id,
                    ]);

                    return null;
                }

                $newStatus = $locked->reading_type === self::READING_TYPE_CELTIC_CROSS
                    ? self::STATUS_CELTIC_PENDING_PAYMENT
                    : self::STATUS_PENDING_PAYMENT;

                $reviveCount = $locked->getReviveCount() + 1;
                $locked->setConversationState('revive_count', $reviveCount);
                $locked->setConversationState('revived_at', now()->toIso8601String());
                $locked->setConversationState('reminder_r1_sent_at', null);
                $locked->setConversationState('reminder_r2_sent_at', null);
                $locked->setConversationState('reminder_r3_sent_at', null);
                $locked->setConversationState('expiry_reminder_sent_at', null);
                $locked->setConversationState('cancelled_at', null);
                $locked->setConversationState('cancellation_reason', null);

                $locked->update([
                    'unique_payment_amount_id' => $newUpa->id,
                    'amount_paid' => $newUpa->unique_amount,
                    'conversation_status' => $newStatus,
                    'updated_at' => now(),
                ]);

                \Log::info('FortuneReading: revive bill สำเร็จ', [
                    'reading_id' => $locked->id,
                    'bill_reference' => $locked->bill_reference,
                    'revive_count' => $reviveCount,
                    'new_amount' => $newUpa->unique_amount,
                    'new_upa_id' => $newUpa->id,
                ]);

                return $locked->fresh();
            });
        } catch (\Throwable $e) {
            \Log::error('FortuneReading::reviveBillForRepay — exception', [
                'reading_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🕒 เช็คว่าควรส่ง re-engagement message ตอนลูกค้ากลับมาหรือไม่
     *
     * นโยบาย:
     *   - ส่ง re-engagement ถ้า last engage > 24 ชม. (FB window reset แล้ว)
     *   - หรือยังไม่เคยส่งเลย
     *   - กันส่งซ้ำใน window เดียว
     */
    public function shouldSendReengagement(): bool
    {
        // 🆕 (2026-05-03 audit fix #8) LINE ไม่มี 24hr window แบบ FB
        //    LINE ส่งฟรีได้ทุกเวลา → ไม่ต้องเช็ค 24hr (กันส่งซ้ำใน 1 ชม. แทน)
        $lastEngageIso = $this->getConversationState('last_reengagement_at');
        if (empty($lastEngageIso)) {
            return true;
        }

        try {
            $hours = abs(now()->diffInHours(\Carbon\Carbon::parse($lastEngageIso), true));
            $platform = $this->platform ?? 'facebook';

            // FB: 24hr window — re-engage หลัง window reset
            // LINE: ไม่มี window — กันสแปมโดยส่งทุก 1 ชม. (ลูกค้าทักรัวๆ ใน 1 ชม. ไม่โดน greeting ซ้ำ)
            $threshold = $platform === 'line' ? 1 : 24;

            return $hours >= $threshold;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * นับจำนวนการทำนายเชิงลึกฟรีของผู้ใช้ Facebook ในวันนี้
     */
    public static function countTodayDeepReadings(string $facebookUserId): int
    {
        return self::byFacebookUser($facebookUserId)
            ->today()
            ->deep()
            ->free()
            ->count();
    }

    /**
     * นับจำนวนการทำนายที่สำเร็จของผู้ใช้ Facebook ในวันนี้
     *
     * นับเฉพาะ reading ที่มี AI ตอบกลับแล้ว (responded_at != null)
     * ไม่นับ reading ที่ล้มเหลว (status = 'new') เพื่อไม่ให้หักสิทธิ์ฟรี
     */
    public static function countTodayReadings(string $facebookUserId): int
    {
        return self::byFacebookUser($facebookUserId)
            ->today()
            ->whereNotNull('responded_at')
            ->count();
    }

    /**
     * ⚠️ DEPRECATED (2026-05-03) — แทนด้วย hasUsedFreeCard() (one-per-platform model)
     *
     * เดิม: เช็ค daily quota — ปัจจุบันระบบเป็น 1 ใบ/platform/ตลอดชีวิต
     * เก็บไว้เผื่อ legacy callers ที่ยังไม่ refactor — return based on free_card consumption แทน
     *
     * @param  string  $facebookUserId  ใช้ชื่อเดิมแต่หมายถึง platform_user_id (FB หรือ LINE)
     * @param  int  $maxFreeReadings  ignored — ของเดิม
     */
    public static function hasReachedFreeLimit(string $facebookUserId, int $maxFreeReadings): bool
    {
        // ปัจจุบัน: ใช้สิทธิ์ฟรีหรือยัง? (per platform หา auto)
        $platform = (preg_match('/^U[0-9a-f]{32}$/i', $facebookUserId)) ? 'line' : 'facebook';

        return self::hasUsedFreeCard($platform, $facebookUserId);
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * บันทึกเวลาที่ตอบกลับ
     */
    public function markAsResponded(): void
    {
        $this->update(['responded_at' => now()]);
    }

    /**
     * บันทึกการชำระเงิน
     */
    public function markAsPaid(float $amount = 0): void
    {
        $this->update([
            'is_paid' => true,
            'amount_paid' => $amount,
            'paid_at' => now(),
        ]);
    }

    /**
     * ดึงคำถามทั้งหมดเป็น string
     */
    public function getQuestionsText(): string
    {
        if (empty($this->questions)) {
            return '';
        }

        return implode("\n", $this->questions);
    }

    /**
     * ดึงข้อมูลผู้ใช้จากโปรไฟล์ Facebook
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getProfileData(string $key, $default = null)
    {
        $profile = $this->user_profile;

        return is_array($profile) ? ($profile[$key] ?? $default) : $default;
    }

    /**
     * ตรวจสอบว่ามีคะแนนรีวิวหรือยัง
     */
    public function hasRating(): bool
    {
        return ! is_null($this->rating);
    }

    /**
     * ดึงคะแนนรีวิวเป็นดาว (สำหรับแสดงผล)
     */
    public function getRatingStars(): string
    {
        if (! $this->hasRating()) {
            return '';
        }

        return str_repeat('⭐', $this->rating);
    }

    /**
     * ตรวจสอบว่าเป็นคำทำนายเชิงลึกหรือไม่
     */
    public function isDeep(): bool
    {
        return $this->reading_type === 'deep';
    }

    /**
     * ตรวจสอบว่ามีรูปคำทำนายหรือไม่
     */
    public function hasReadingImage(): bool
    {
        return ! empty($this->reading_image_url);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ส่งรูปมาหรือไม่
     */
    public function hasUserImage(): bool
    {
        return ! empty($this->user_image_url);
    }

    /**
     * ดึงข้อความสรุปประเภทคำทำนาย (สำหรับแสดงผล)
     */
    public function getReadingTypeLabel(): string
    {
        return match ($this->reading_type) {
            self::READING_TYPE_DEEP => '🌟 เชิงลึก',
            self::READING_TYPE_CELTIC_CROSS => '💎 Celtic 99',
            self::READING_TYPE_FREE_CARD => '🎁 ไพ่ฟรี',
            default => '🔮 พื้นฐาน',
        };
    }

    /**
     * 🏷️ (2026-05-25) Map cancellation_reason enum → Thai display label
     *
     * User spec: "บิลที่ถูกยกเลิกโดยระบบ ให้ขึ้นในแอพและในระบบต่างๆ ว่า ยกเลิกโดยระบบ"
     *
     * Source enum (stored in conversation_state.cancellation_reason):
     *   - `auto_expired`        — Phase J cron (30+ นาที pending payment)
     *   - `auto_expired_grace`  — SmsPaymentService cleanup (90+ นาที grace)
     *   - `user_cancelled`      — ลูกค้ากดยกเลิกเอง
     *   - `unknown`             — legacy/missing
     *
     * Routing display:
     *   - auto_expired + auto_expired_grace → "ยกเลิกโดยระบบ"
     *   - user_cancelled                     → "ยกเลิกโดยลูกค้า"
     *   - unknown / null                     → "ยกเลิก (ไม่ทราบสาเหตุ)"
     *
     * ใช้ใน: SmsPaymentController sync API + FcmNotificationService FCM data + admin billing index
     *
     * @param  string|null  $reason  enum key from conversation_state.cancellation_reason
     * @return string Thai label พร้อมแสดง UI
     */
    public static function getCancellationReasonLabel(?string $reason): string
    {
        return match ($reason) {
            'auto_expired', 'auto_expired_grace' => 'ยกเลิกโดยระบบ',
            'user_cancelled' => 'ยกเลิกโดยลูกค้า',
            // 🚫 (2026-07-27) แอดมินกด "ยกเลิกการอนุมัติ" (voidApproval) — อนุมัติผิดบิล/ผิดคน
            'approval_voided' => 'ยกเลิกการอนุมัติโดยแอดมิน',
            // 🛡️ (2026-08-12) ลูกค้าจ่ายบิลอื่นแทน → reconcile ปิดบิลนี้ (ห้ามอนุมัติซ้ำ = เก็บเงินซ้ำ)
            //   เดิมตกไป default "ไม่ทราบสาเหตุ" → แอดมินไม่รู้ว่ากดไม่ได้เพราะอะไร
            'superseded_by_paid' => '⛔ ลูกค้าจ่ายบิลอื่นแทนแล้ว (ห้ามอนุมัติซ้ำ)',
            default => 'ยกเลิก (ไม่ทราบสาเหตุ)',
        };
    }

    /**
     * 🏷️ (2026-05-25) Instance shortcut — ดึง cancellation_reason จาก conversation_state แล้ว map → label
     *
     * คืน null ถ้า reading นี้ยังไม่ถูก cancel (ไม่มี cancellation_reason ใน state)
     * ใช้ใน admin Blade: `{{ $bill->getCancellationReasonLabelOrNull() ?? '-' }}`
     */
    public function getCancellationReasonLabelOrNull(): ?string
    {
        $state = $this->conversation_state ?? [];
        if (! is_array($state) || empty($state['cancellation_reason'])) {
            return null;
        }

        return self::getCancellationReasonLabel((string) $state['cancellation_reason']);
    }

    /**
     * 🏷️ (2026-05-25) เช็คว่า bill นี้ถูก cancel หรือไม่
     *
     * Logic: conversation_status = 'completed' + is_paid = false + มี cancellation_reason ใน state
     *        (cancelled bills ใช้ STATUS_COMPLETED + is_paid=false — ไม่มี STATUS_CANCELLED แยก)
     */
    public function isCancelled(): bool
    {
        if ($this->is_paid) {
            return false;
        }
        if ($this->conversation_status !== self::STATUS_COMPLETED) {
            return false;
        }
        $state = $this->conversation_state ?? [];

        return is_array($state) && ! empty($state['cancellation_reason']);
    }

    // ============================================================
    // Conversation State Management
    // ============================================================

    /**
     * ความสัมพันธ์กับ UniquePaymentAmount
     */
    public function uniquePaymentAmount(): BelongsTo
    {
        return $this->belongsTo(UniquePaymentAmount::class, 'unique_payment_amount_id');
    }

    /**
     * Scope: ค้นหา reading ที่รอชำระเงินของผู้ใช้
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingPaymentByUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', self::PENDING_PAYMENT_STATUSES)
            ->whereNotNull('unique_payment_amount_id');
    }

    /**
     * ระยะเวลา timeout ของ conversation (นาที)
     *
     * conversation ที่เก่ากว่านี้จะถูกปิดอัตโนมัติ
     * pending_payment ก็ใช้ 30 นาทีเท่ากัน (บิลหมดอายุ 30 นาที)
     */
    public const CONVERSATION_TIMEOUT_MINUTES = 30;

    public const PAYMENT_TIMEOUT_MINUTES = 30;

    /**
     * ⏰ (2026-06-12) อายุบิลรอชำระ (นาที) — อ่านจาก admin setting, fallback 180 (3 ชม.)
     *
     * เจ้าของสั่ง: "บิลยกเลิกโดยระบบเร็วไป ให้ปรับใหม่เป็น 3 ชั่วโมง บางคนลืม"
     *
     * ⚠️ ใช้กับ **บิล** (PENDING_PAYMENT / CELTIC_PENDING_PAYMENT + อายุ UPA) เท่านั้น
     *    conversation state อื่นๆ (กรอกวันเกิด/รอยืนยัน/เลือก tier) ยังใช้
     *    CONVERSATION_TIMEOUT_MINUTES = 30 นาทีเหมือนเดิม — กัน orphan ค้างทั้งระบบ
     */
    public static function billTimeoutMinutes(): int
    {
        // static cache ต่อ request — setting ถูกเรียกหลายจุดใน flow เดียว
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $minutes = (int) (FortuneTellingSetting::getSettings()->bill_payment_timeout_minutes ?? 0);
            $cached = $minutes > 0 ? $minutes : 180;
        } catch (\Throwable $e) {
            $cached = 180;
        }

        return $cached;
    }

    /**
     * 💎 (2026-05-04) Request-Before-Pay timeout — ลูกค้าได้คำทำนายไปแล้ว ต้องโอนภายใน 24 ชม
     *
     * ต่างจาก pay-first (30 นาที):
     *   - ลูกค้าได้รับคุณค่าแล้ว (คำทำนาย) → ให้เวลานานพอที่ลูกค้าจริงจะจ่ายทัน
     *   - 24 ชม ก็เพียงพอกัน "ลืม" หรือ "หาเงินสด"
     *   - หลัง 24 ชม → mark fraud_risk + admin alert (cancelExpiredPendingBills)
     */
    public const REQUEST_BEFORE_PAY_TIMEOUT_MINUTES = 1440; // 24 ชม

    /**
     * ระยะเวลา timeout ของ PAID status (นาที)
     *
     * หลังชำระเงินแล้ว AI จะประมวลผลคำทำนาย (~45-90 วินาที + retry)
     * ให้ timeout 10 นาทีเพื่อรอให้ AI ทำงานเสร็จ (รวม retry + ไพ่ยิปซี + throttle delay)
     * ถ้าเกิน 10 นาที → ถือว่า AI ล้มเหลว → ปิด conversation อัตโนมัติ
     */
    public const PAID_PROCESSING_TIMEOUT_MINUTES = 10;

    /**
     * Scope: ค้นหา reading ที่กำลัง conversation อยู่
     *
     * 🛡️ (2026-05-24) USER RULE — ลูกค้าโอนตรงยอด ตัดบิลแล้ว แต่ยังไม่ใช้บริการเสร็จสิ้น
     *   เมื่อกลับมา ก็จะกู้บริการต่อจากจุดที่ค้างไว้ได้เสมอ — ทุกกรณี
     *
     * แบ่งเป็น 2 branches:
     *  1. PAID bills (is_paid=true) → always in scope (no timeout) — รอนานแค่ไหนก็ resume ได้
     *     Discriminator: admin_review_alerted=true → out of scope (cron fortune:expire-stuck-paid
     *     ตั้ง flag นี้หลังบิลค้าง > 24 ชม. → admin จัดการ → ลูกค้าทักใหม่ได้)
     *  2. UNPAID bills (is_paid=false) → existing timeout rules (กัน orphan PENDING block chat)
     *
     * ⚠️ ป้องกัน regression "บอทไม่คุยกับใครเลย" (commit bfa2a8ed0 → revert b601bd68c)
     *   ครั้งนั้นขยาย 24hr ทุก case → orphan paid+incomplete legacy lock ทุกลูกค้า
     *   ครั้งนี้: ใช้ admin_review_alerted flag เป็นทางออก (ไม่ block ตลอดกาล)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveConversation($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', [
                self::STATUS_AWAITING_CONFIRMATION,
                self::STATUS_BASIC_DONE,
                self::STATUS_COLLECTING_BIRTHDATE,
                self::STATUS_COLLECTING_QUESTIONS,
                self::STATUS_COLLECTING_TAROT,
                self::STATUS_DISCOVERY_CHAT,    // 🆕 (2026-04-28) AI psychology chat
                self::STATUS_DISCOVERY_CONFIRM, // 🆕 รอ user ยืนยันสรุปจาก AI
                self::STATUS_TIER_CHOICE,       // 🆕 (2026-04-29) รอเลือก tier 39฿ vs 99฿
                self::STATUS_PENDING_PAYMENT,
                self::STATUS_PAID, // เพิ่ม: ระหว่าง AI กำลังประมวลผลคำทำนาย
                // 🔮 Celtic Cross states (2026-04-29) — ทุก state ของ Celtic ต้องนับว่ายัง active อยู่
                self::STATUS_CELTIC_PENDING_PAYMENT,
                self::STATUS_CELTIC_PICKING,
                self::STATUS_CELTIC_AWAITING_QUESTION,
                self::STATUS_CELTIC_GENERATING,
                self::STATUS_CELTIC_QA_PROMPT,
                // 🎁 Free Card states (2026-05-03) — รอลูกค้าตอบหลังทำนายฟรี
                self::STATUS_FREE_PREDICTED,
                // 💳 Stripe Payment states (2026-05-09, scope-fix 2026-05-22)
                //    เดิมขาดอันนี้ → ลูกค้าทักระหว่างรอจ่าย Stripe → bot ไม่เจอ reading → orphan
                self::STATUS_AWAITING_PAYMENT_METHOD,
                self::STATUS_PENDING_STRIPE_PAYMENT,
            ])
            ->where(function ($q) {
                // ════════════════════════════════════════════════════════════════
                // 🛡️ BRANCH 1: PAID BILLS — always in scope (no timeout)
                //    User rule: "ลูกค้าโอนตรงยอด ตัดบิลแล้ว เมื่อกลับมา resume ได้เสมอ"
                //    ครอบทุก payment path: Pay-First SMS / Pay-Later / Stripe
                //    Out-of-scope กรณีเดียว: admin_review_alerted=true (legacy 24hr+ flagged)
                // ════════════════════════════════════════════════════════════════
                $q->where(function ($paid) {
                    $paid->where('is_paid', true)
                        ->where(function ($flag) {
                            // admin_review_alerted ยังไม่ตั้ง หรือไม่ใช่ true
                            $flag->whereNull('conversation_state')
                                ->orWhereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') IS NULL")
                                ->orWhereRaw("JSON_EXTRACT(conversation_state, '$.admin_review_alerted') != true");
                        });
                })
                // ════════════════════════════════════════════════════════════════
                // 💤 BRANCH 2: UNPAID BILLS — existing timeout rules (กัน orphan block chat)
                // ════════════════════════════════════════════════════════════════
                    ->orWhere(function ($unpaid) {
                        $unpaid->where(function ($p) {
                            $p->where('is_paid', false)->orWhereNull('is_paid');
                        })->where(function ($timeouts) {
                            // awaiting_confirmation + conversation ทั่วไป: timeout 30 นาที
                            $timeouts->where(function ($sub) {
                                $sub->whereIn('conversation_status', [
                                    self::STATUS_AWAITING_CONFIRMATION,
                                    self::STATUS_BASIC_DONE,
                                    self::STATUS_COLLECTING_BIRTHDATE,
                                    self::STATUS_COLLECTING_QUESTIONS,
                                    self::STATUS_COLLECTING_TAROT,
                                    self::STATUS_DISCOVERY_CHAT,
                                    self::STATUS_DISCOVERY_CONFIRM,
                                    self::STATUS_TIER_CHOICE,
                                    // 💳 (2026-05-22) AWAITING_PAYMENT_METHOD = ลูกค้ารอเลือกวิธีชำระ
                                    //   timeout 30 นาที เพียงพอ — ถ้าเกินก็ปล่อย user reset
                                    self::STATUS_AWAITING_PAYMENT_METHOD,
                                ])
                                    ->where('updated_at', '>=', now()->subMinutes(self::CONVERSATION_TIMEOUT_MINUTES));
                            })
                            // 💳 (2026-05-22) PENDING_STRIPE_PAYMENT: timeout = Stripe session expiry + buffer
                            //    Stripe session อายุ 30-60 นาที (อิงจาก stripe_session_expiry_minutes setting)
                            //    ให้ 90 นาทีคงที่เพื่อเผื่อ buffer (Stripe expire เอง → polling จัดการ revert)
                                ->orWhere(function ($sub) {
                                    $sub->where('conversation_status', self::STATUS_PENDING_STRIPE_PAYMENT)
                                        ->where('updated_at', '>=', now()->subMinutes(90));
                                })
                            // pending_payment (Deep + Celtic): timeout ตาม admin setting (default 3 ชม.)
                            // ⏰ (2026-06-12) เดิม 30 นาที — เจ้าของสั่งขยายเป็น 3 ชม. (บางคนลืม)
                                ->orWhere(function ($sub) {
                                    $sub->whereIn('conversation_status', self::PENDING_PAYMENT_STATUSES)
                                        ->where('updated_at', '>=', now()->subMinutes(self::billTimeoutMinutes()));
                                })
                            // 🎁 free_predicted: timeout 15 นาที (ลูกค้ามีโอกาสเลือกซื้อ 39/99 หลังเห็นคำทำนาย)
                                ->orWhere(function ($sub) {
                                    $sub->where('conversation_status', self::STATUS_FREE_PREDICTED)
                                        ->where('updated_at', '>=', now()->subMinutes(15));
                                });
                            // 🛡️ STATUS_PAID + Celtic flow states ย้ายไป BRANCH 1 (paid) แล้ว
                            //    ไม่ต้องมี timeout clause สำหรับ states พวกนี้ใน BRANCH 2
                            //    เพราะ STATUS_PAID/CELTIC_* by definition implies is_paid=true
                        });
                    });
            })
            ->latest();
    }

    /**
     * ค้นหา reading ที่กำลัง conversation อยู่สำหรับผู้ใช้
     *
     * ถ้าพบ conversation ที่หมดเวลาแล้ว จะปิดอัตโนมัติ
     */
    public static function findActiveConversation(string $facebookUserId): ?self
    {
        // ปิด conversation ที่หมดเวลาอัตโนมัติ
        self::expireOldConversations($facebookUserId);

        $active = self::activeConversation($facebookUserId)->first();

        // 🌙 (2026-05-14) Ignore stale PENDING bills — user spec:
        //   "ไม่สนใจบิลที่ไม่ได้จ่าย และสนใจแค่บิลล่าสุด"
        //
        //   เคสจริง: ลูกค้ากด 39/99 → สร้างบิล PENDING → ไม่จ่าย → ทักมาใหม่ภายหลัง
        //   ระหว่างนั้นก็จ่ายอีกบิล (อาจก่อนหน้า) + ทำนายเสร็จ
        //   findActiveConversation ก่อนหน้า: คืน PENDING bill เก่าที่ยังไม่ expire (30 min)
        //   → bot ส่งบิลเก่า "รอโอนนะคะ" → ลูกค้างง "บิลอันไหน?"
        //
        //   Fix: ถ้า active เป็น PENDING + มี reading ใหม่กว่าที่ COMPLETED + is_paid → ignore PENDING
        if ($active !== null && in_array($active->conversation_status, self::PENDING_PAYMENT_STATUSES, true)) {
            $newerCompletedPaid = self::where('facebook_user_id', $facebookUserId)
                ->where('id', '>', $active->id)
                ->where('is_paid', true)
                ->where('conversation_status', self::STATUS_COMPLETED)
                ->exists();

            if ($newerCompletedPaid) {
                // mark stale PENDING as completed — กัน findActiveConversation คืนมาอีก
                //   ใช้ COMPLETED แทน EXPIRED (ไม่มี const) — bills ไม่ active แล้ว
                try {
                    $active->update(['conversation_status' => self::STATUS_COMPLETED]);
                } catch (\Throwable $e) {
                    // ignore — ไม่ block flow หลัก
                }

                // หา active reading ใหม่ (ที่ไม่ใช่ stale PENDING) — ถ้ามี
                return self::activeConversation($facebookUserId)->first();
            }
        }

        return $active;
    }

    /**
     * ปิด conversation ที่หมดเวลาอัตโนมัติ (เฉพาะ user ที่ระบุ)
     *
     * @return int จำนวน conversation ที่ถูกปิด
     */
    public static function expireOldConversations(string $facebookUserId): int
    {
        return self::expireOldConversationsQuery(
            self::where('facebook_user_id', $facebookUserId)
        );
    }

    /**
     * ปิด conversation ที่หมดเวลาทั้งระบบ (global — ใช้จาก scheduled command)
     *
     * @return int จำนวน conversation ที่ถูกปิด
     */
    public static function expireAllOldConversations(): int
    {
        return self::expireOldConversationsQuery(self::query());
    }

    /**
     * 🎯 Phase K — ก่อนยกเลิกบิล ส่ง DM "closing pitch" เพื่อกระตุ้นอีกรอบ
     *
     * ตรรกะ:
     *   - หาบิลที่ค้าง 25 นาที (5 นาทีก่อนหมดอายุ) ที่ยังไม่เคยเตือน
     *   - ส่ง DM message ที่ reframe ราคา (เทียบค่ากาแฟ) + เน้นว่า
     *     การทำนายใช้ดาวเจ้าชนะ + ไพ่ที่พลังจิตลูกค้าเลือกเอง
     *   - mark state `expiry_reminder_sent_at` กันส่งซ้ำ
     *
     * ⚠️ Best-effort: ถ้า platform ส่งไม่สำเร็จ (FB 24hr window หมด) → mark ส่งแล้วอยู่ดี
     *    เพื่อกันวนส่ง
     *
     * @return int จำนวน reminder ที่ส่งสำเร็จ
     */
    public static function sendExpiryReminders(): int
    {
        // 🛑 (2026-05-06) Pay-Later total removal — no-op
        //   user spec: "นำออกให้หมดไม่ต้องเช็คว่ามีบิลค้าง"
        //   เดิม: เตือน R1/R2/R3 สำหรับ Request-Before-Pay bills
        //   ใหม่: ไม่เตือนใครเลย ลูกค้า pay-first ไม่จ่าย = เลิกๆ ปล่อยไป
        return 0;
    }

    /**
     * 🌙 สร้างข้อความ reminder ตาม stage — แม่หมอ persona + Lao locale
     *
     * @param  string  $stage  'r1' | 'r2' | 'r3'
     */
    protected static function buildReminderMessage(self $reading, string $stage): string
    {
        // 🛑 (2026-05-06) Pay-Later removed — สร้าง stub message เผื่อ caller เก่าเรียก
        //   ปัจจุบัน sendExpiryReminders() return 0 — ฟังก์ชันนี้ไม่น่าถูกเรียก
        return '';
    }

    /**
     * 🎯 Closing pitch (R3 — 25-30 นาที) — เดิม Phase K (4 variants)
     *
     * 🌐 (2026-05-03) wrap Lao: ลูกค้าลาวเห็นข้อความลาว variant เดียวที่ปรับเฉพาะ
     */
    protected static function buildClosingPitchMessage(self $reading, int $remainingMinutes): string
    {
        $price = (int) ($reading->amount_paid ?? 0);
        if ($price <= 0) {
            try {
                $settings = \App\Models\FortuneTellingSetting::getSettings();
                $settingPrice = (float) ($settings->deep_reading_price ?? 0);
                if ($settingPrice <= 0) {
                    $settingPrice = (float) ($settings->reading_price ?? 0);
                }
                $price = (int) ($settingPrice > 0 ? $settingPrice : 39);
            } catch (\Throwable $e) {
                $price = 39;
            }
        }

        // 🇱🇦 ลูกค้าลาว — ใช้ closing pitch ลาวเดียว
        if (\App\Services\FortuneLocaleService::current() === \App\Services\FortuneLocaleService::LOCALE_LO) {
            return "⏰ ບິນເບິ່ງດວງເຫຼືອອີກ {$remainingMinutes} ນາທີຈະໝົດອາຍຸ\n\n"
                ."🪙 {$price} ບາດ — ທຽບເທົ່າຄ່າກາເຟ 1 ຈອກ\n"
                ."ແຕ່ໄດ້ຄຳທຳນາຍສະເພາະຕົວຈາກ\n"
                ."   • ດາວເຈົ້າຊະນະຂອງເຈົ້າຊາຕາ\n"
                ."   • ໄພ່ທີ່ສຸ່ມຈາກພະລັງຈິດຂອງເຈົ້າຊາຕາເອງ\n\n"
                ."ເໝືອນຈັບໄພ່ເອງ ເພາະຈິດໝັ້ນແນ່ກໍ່ສື່ເຖິງດາວແລ້ວ ✨\n"
                .'ຖ້າພ້ອມ → ໂອນໄດ້ເລີຍ';
        }

        // 🇹🇭 ลูกค้าไทย — variant ตาม reading_id (รักษาพฤติกรรมเดิม)
        $variants = [
            "🔮 บิลดูดวงยังรออยู่นะคะ — อีก {$remainingMinutes} นาทีจะหมดอายุ\n\n"
                ."☕ ค่าครู {$price} บาท น้อยกว่าค่ากาแฟ 1 แก้วเสียอีก\n"
                ."แต่หมอวิเคราะห์จาก **ดาวเจ้าชนะของเจ้าชะตาเอง**\n"
                ."ไพ่ที่เปิดก็มาจากพลังจิตของเจ้าชะตา\n"
                ."ไม่ต่างจากจับไพ่เอง — จิตตั้งมั่น ดาวก็ส่งสัญญาณมาแล้ว ✨\n\n"
                .'ถ้าพร้อม → โอนมาได้เลย 🙏',

            "💫 คำทำนายของหมอ ไม่ใช่คำตอบทั่วไปที่ใครก็ได้\n\n"
                ."วิเคราะห์จาก **ดาวเจ้าชนะของเจ้าชะตาคนเดียว**\n"
                ."บวกกับ **ไพ่ที่พลังจิตเจ้าชะตาเลือกออกมาเอง**\n"
                ."เหมือนจับไพ่เอง เพราะจิตสื่อถึงดวงดาวไปแล้ว 🌙\n\n"
                ."💎 {$price} บาท แลกคำตอบตรงตัว\n"
                ."⏰ บิลหมดอายุในอีก {$remainingMinutes} นาที",

            "🃏 หมอเตรียมไพ่ + ดาวของเจ้าชะตาไว้พร้อมแล้ว\n\n"
                ."ตอนสุ่มไพ่ พลังจิตของเจ้าชะตาเป็นคนเลือก\n"
                ."ไม่ต่างจากจับไพ่เอง — จิตเชื่อ ใจสื่อ ดาวตอบ 🌟\n\n"
                ."{$price} บาท ไม่ใช่แค่ค่าทำนาย\n"
                ."แต่คือค่าที่ปรึกษาที่ตั้งใจวิเคราะห์ให้เจ้าชะตาคนเดียว\n\n"
                ."อีก {$remainingMinutes} นาทีบิลจะหมด — ถ้าพร้อมโอนมาได้เลยนะคะ",

            "⏰ บิลดูดวงเหลืออีก {$remainingMinutes} นาทีจะหมดอายุ\n\n"
                ."🪙 {$price} บาท — เทียบเท่าค่ากาแฟ 1 แก้ว\n"
                ."แต่ได้คำทำนายเจาะตัวจาก\n"
                ."   • ดาวเจ้าชนะของเจ้าชะตา\n"
                ."   • ไพ่ที่สุ่มจากพลังจิตของเจ้าชะตาเอง\n\n"
                ."เหมือนจับไพ่เอง เพราะจิตตั้งมั่นก็สื่อถึงดาวแล้ว ✨\n"
                .'ถ้าพร้อม → โอนได้เลย',
        ];

        $idx = abs(crc32((string) $reading->id)) % count($variants);

        return $variants[$idx];
    }

    /**
     * 🎯 Phase J — ยกเลิกบิลดูดวงที่ค้างเกิน 30 นาทีพร้อมแจ้ง SMS Checker app
     *
     * ทำ 3 อย่างในคราวเดียว (สำหรับ cron):
     *   1. ดึง reading ที่ conversation_status = pending_payment, is_paid = false,
     *      มี unique_payment_amount_id, และ updated_at เก่ากว่า PAYMENT_TIMEOUT_MINUTES
     *   2. สำหรับแต่ละ reading:
     *      - cancel() UniquePaymentAmount ที่อยู่ 'reserved' → status = cancelled
     *      - ส่ง FCM push "order_cancelled" ให้แอพ SMS Checker (ผ่าน
     *        FcmNotificationService::notifyFortuneReadingCancelled)
     *      - update conversation_status = completed
     *   3. คืนจำนวนบิลที่ expire สำเร็จ
     *
     * ⚠️ ต่างจาก expireAllOldConversations(): ตัวนี้จัดการ **บิล** (UPA + FCM)
     *    ส่วน expireAllOldConversations() จัดการ **conversation status** เฉย ๆ
     *
     * @return int จำนวนบิลที่ถูกยกเลิก
     */
    public static function cancelExpiredPendingBills(): int
    {
        // 🛑 (2026-05-06) Pay-Later removed — pay-first timeout เดียวสำหรับทุกบิล
        // ⏰ (2026-06-12) เดิม 30 นาที → อ่านจาก admin setting (default 180 นาที = 3 ชม.)
        //   เจ้าของสั่ง: "บิลยกเลิกโดยระบบเร็วไป บางคนลืม — 3 ชม. ค่อยยกเลิก"
        //
        // 🩹 (2026-06-12 จับผี #3) ตัดอายุจาก UPA expires_at (fix ตอนสร้างบิล) เป็นหลัก
        //   — ไม่ใช่ updated_at เพราะ reminder 3 จังหวะ + แชทระหว่างรอ bump updated_at
        //   → บิลซอมบี้ลาก ~2 เท่าของอายุจริง ขัดกับที่บอกลูกค้า "เหลือ X นาที"
        //   updated_at เก็บไว้เป็น fallback กรณี UPA record ผิดปกติ
        $payFirstCutoff = now()->subMinutes(self::billTimeoutMinutes());

        $expiredReadings = self::whereIn('conversation_status', self::PENDING_PAYMENT_STATUSES)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->where(function ($q) use ($payFirstCutoff) {
                $q->whereHas('uniquePaymentAmount', function ($uq) {
                    $uq->where('expires_at', '<', now());
                })
                    ->orWhere('updated_at', '<', $payFirstCutoff);
            })
            ->with('uniquePaymentAmount')
            ->get();

        if ($expiredReadings->isEmpty()) {
            return 0;
        }

        $cancelled = 0;
        $fcmService = null;
        $channelManager = null;

        try {
            $fcmService = app(\App\Services\FcmNotificationService::class);
        } catch (\Throwable $e) {
            \Log::warning('FortuneReading::cancelExpiredPendingBills — FCM service unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $channelManager = app(\App\Services\FortuneChannelManager::class);
        } catch (\Throwable $e) {
            \Log::warning('FortuneReading::cancelExpiredPendingBills — channel manager unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($expiredReadings as $reading) {
            try {
                // 1. ยกเลิก UniquePaymentAmount (ถ้ายัง reserved)
                $upa = $reading->uniquePaymentAmount;
                if ($upa && $upa->status === 'reserved') {
                    $upa->cancel();
                }

                // 2. ส่ง DM "คำเตือนสติแบบนักปราชญ์" ให้ผู้ใช้ก่อนปิด conversation
                //    (ส่งก่อน update status เพื่อให้ flow handler ไม่ตีเป็น completed)
                if ($channelManager) {
                    try {
                        $platform = $reading->platform ?? 'facebook';
                        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                        // 🛡️ (2026-06-07) เช็ค cancel_warning_sent ก่อนส่ง — กัน cron รันซ้อน (ทุก 5 นาที)
                        //   ส่งคำเตือน/รูปซ้ำ ก่อน status COMPLETED commit (race double-send)
                        $alreadyWarned = (bool) $reading->getConversationState('cancel_warning_sent');
                        if (! empty($userId) && ! $alreadyWarned) {
                            $platformService = $channelManager->getPlatform($platform);
                            if ($platformService) {
                                // 📜 (2026-06-07) บิลยกเลิกโดยระบบ (หมดเวลา 30 นาที) → เตือนด้วย (โทนนุ่ม)
                                //   user: "ปล่อยให้หมดเวลาเอง → โทนนุ่มกว่า + แยกรูปต่างหาก"
                                //   mode='expire' → รูป scope=expire + ข้อความนุ่ม (อาจแค่ลืม ไม่ใช่เจตนาเบี้ยว)
                                //   helper เช็ค toggle expire เอง → ปิด/ไม่มีรูป = false → fallback wakeup เดิม (20 variants)
                                $sentWarning = \App\Models\FortuneConsentImage::deliverCancelWarning($platformService, (string) $userId, 'expire');
                                if ($sentWarning) {
                                    $reading->setConversationState('cancel_warning_sent', true);
                                } else {
                                    $platformService->sendMessage($userId, self::buildCancelWakeupMessage($reading));
                                    $reading->setConversationState('cancel_warning_sent', true);
                                }
                            }
                        }
                    } catch (\Throwable $dmErr) {
                        \Log::warning('FortuneReading: cancel wake-up DM ล้มเหลว (best-effort)', [
                            'reading_id' => $reading->id,
                            'error' => $dmErr->getMessage(),
                        ]);
                    }
                }

                // 3. mark cancellation timestamp (ใช้สำหรับ AI rebuttal — กันส่งซ้ำ)
                $reading->setConversationState('cancelled_at', now()->toIso8601String());
                $reading->setConversationState('cancellation_reason', 'auto_expired');

                // 🛑 (2026-05-06) Pay-Later removed — ไม่มี fraud detection สำหรับ Request-Before-Pay
                //   เดิม: เช็ค is_request_before_pay flag → mark fraud_risk + alert admin
                //   ใหม่: ทุกบิล = pay-first → ไม่จ่าย = เลิกๆ ไม่ใช่ "โกง"

                // 4. ปิด conversation
                $reading->update(['conversation_status' => self::STATUS_COMPLETED]);

                // 5. แจ้ง SMS Checker app ว่าบิลถูกยกเลิก (สำคัญ — กันแอพเก็บบิลค้าง)
                if ($fcmService) {
                    try {
                        $fcmService->notifyFortuneReadingCancelled($reading);
                    } catch (\Throwable $fcmErr) {
                        \Log::warning('FortuneReading::cancelExpiredPendingBills FCM push failed', [
                            'reading_id' => $reading->id,
                            'bill_reference' => $reading->bill_reference,
                            'error' => $fcmErr->getMessage(),
                        ]);
                    }
                }

                $cancelled++;

                \Log::info('FortuneReading: บิลค้างเกิน timeout → ยกเลิกอัตโนมัติ', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'facebook_user_id' => $reading->facebook_user_id,
                    'amount' => $reading->amount_paid,
                    'timeout_minutes' => self::billTimeoutMinutes(),
                    'age_minutes' => (int) now()->diffInMinutes($reading->updated_at, true),
                ]);

                // 🛡️ (2026-06-12) Bill-Troll Guard — บิลที่ 3 ที่ไม่ชำระ (หลังเตือนแล้ว) → แบนถาวร
                //   best-effort: พังก็ไม่ block การยกเลิกบิลอื่นๆ
                try {
                    app(\App\Services\Fortune\BillTrollGuardService::class)->maybeBanAfterUnpaidCancel($reading);
                } catch (\Throwable $banErr) {
                    \Log::warning('FortuneReading: BillTrollGuard check ล้มเหลว (best-effort)', [
                        'reading_id' => $reading->id,
                        'error' => $banErr->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('FortuneReading::cancelExpiredPendingBills ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return $cancelled;
    }

    /**
     * สร้าง "คำเตือนสติแบบนักปราชญ์" สำหรับส่งให้ผู้ใช้เมื่อบิลถูกยกเลิก
     *
     * โครงสร้าง: [Header แจ้งยกเลิก + เลขบิล + เหตุผล] + [ปรัชญา 20+ variants]
     *
     * @param  string  $reason  'auto_expired' (cron 30 นาที) | 'user_cancelled' (ลูกค้ากดยกเลิกเอง)
     */
    public static function buildCancelWakeupMessage(self $reading, string $reason = 'auto_expired'): string
    {
        // 🚫 / ✋ Header — เปลี่ยนตาม reason
        $billRef = $reading->bill_reference ?? '-';
        // ⏰ (2026-06-12) แสดงเป็น ชม.+นาที ตาม setting จริง (default 3 ชม.)
        $timeoutTotal = self::billTimeoutMinutes();
        $timeoutMin = $timeoutTotal >= 60
            ? (intdiv($timeoutTotal, 60).' ชั่วโมง'.($timeoutTotal % 60 > 0 ? ' '.($timeoutTotal % 60).' นาที' : ''))
            : $timeoutTotal.' นาที';

        if ($reason === 'user_cancelled') {
            // ลูกค้ากดยกเลิกเอง — ใช้โทน "ขอบคุณที่แจ้ง" + เตือนสติเบา ๆ
            $header = "✋ *รับทราบ — ยกเลิกบิลดูดวงตามคำขอแล้วค่ะ*\n"
                ."═══════════════════════\n"
                ."📋 เลขบิล: {$billRef}\n"
                ."═══════════════════════\n\n"
                ."💭 *ก่อนจากกัน แม่หมอขอฝากข้อคิดสักนิด...*\n\n";
        } else {
            // auto_expired (default) / auto_expired_grace — โทน "ระบบยกเลิกให้แล้ว"
            $header = "🚫 *บิลดูดวงของเจ้าชะตาถูกยกเลิกอัตโนมัติแล้ว*\n"
                ."═══════════════════════\n"
                ."📋 เลขบิล: {$billRef}\n"
                ."⏱️ เหตุผล: ไม่ได้รับการชำระเงินภายใน {$timeoutMin}\n"
                ."═══════════════════════\n\n"
                ."💭 *ก่อนปิดท้าย แม่หมอขอฝากข้อคิดสักนิด...*\n\n";
        }

        // ราคาดึงจาก settings (admin ตั้งได้) — fallback 39 ถ้าไม่ตั้ง
        $price = 0;
        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $price = (int) ($settings->deep_reading_price ?? 0);
            if ($price <= 0) {
                $price = (int) ($settings->reading_price ?? 0);
            }
        } catch (\Throwable $e) {
            // ignore — fallback ด้านล่าง
        }
        if ($price <= 0) {
            $price = 39;
        }

        // 20 คำเตือนสตินักปราชญ์ (ใช้คำพูดของคนมีความรู้ เปรียบเทียบเชิงปรัชญา)
        $wisdomMessages = [
            // 1. กาแฟ vs ความรู้
            "📜 *กาแฟ 1 แก้ว เจ้าชะตาจ่าย {$price} บาท ได้ความตื่นเช้าแค่ชั่วโมง*\n\n"
                ."แต่ความรู้เรื่องอนาคตของตัวเอง — กลับไม่ลงทุน\n"
                ."คนสำเร็จคือคนที่ลงทุนกับ \"การรู้ก่อน\" — ไม่ใช่ \"การเดาทีหลัง\" ✨\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเริ่มใหม่",

            // 2. หวย vs ดวงดาว
            "📜 *เจ้าชะตาเสี่ยงซื้อหวย 80 บาท หวังโชค 6 ล้าน*\n\n"
                ."แต่ {$price} บาทรู้ทิศทางชีวิตจากดาวเจ้าชนะ — กลับไม่กล้า\n"
                ."ปราชญ์โบราณว่า: \"คนเก่งซื้อความรู้ คนพนันซื้อความหวัง\" 🎯\n\n"
                ."🔮 ถ้าวันนี้พร้อม พิมพ์ 'ดูดวง' ได้เลย",

            // 3. การลงทุนน้อย แต่ไม่ทำ
            "📜 *ขงจื๊อกล่าว: \"การเดินทางพันลี้ เริ่มจากก้าวแรก\"*\n\n"
                ."แค่ {$price} บาท — ก้าวแรกที่ไม่กล้าเริ่ม\n"
                ."จะหวังเดินถึงปลายทางได้อย่างไร? 🌅\n\n"
                ."ดวงไม่ได้บอกอนาคต — แต่บอก \"ความเป็นไปได้\" ที่จิตเรามองข้าม\n\n"
                ."🔮 ก้าวแรกรออยู่เสมอ พิมพ์ 'ดูดวง' เมื่อพร้อม",

            // 4. ความเสียดาย
            "📜 *พระพุทธเจ้าตรัสว่า: \"ความประมาทเป็นทางแห่งความตาย\"*\n\n"
                ."ความประมาท = คิดว่ารู้แล้วทุกอย่าง\n"
                ."ความฉลาด = ขวนขวายหาความรู้ แม้ราคาเพียง {$price} บาท\n\n"
                ."ที่นี่ไม่ใช่งมงาย — ใช้หลักดาวเจ้าชนะ + ไพ่ที่จิตเจ้าชะตาเลือก\n"
                ."🔮 ลองพิสูจน์ด้วยตัวเอง พิมพ์ 'ดูดวง'",

            // 5. การลงทุนเล็กผลตอบแทนใหญ่
            "📜 *นักปราชญ์ตะวันตกว่า: \"ความรู้คือพลัง ความไม่รู้คือกรง\"*\n\n"
                ."{$price} บาท แลกการเปิดกรงที่ขังจิตใจของเจ้าชะตา\n"
                ."คุ้มกว่าค่ามื้อกลางวันเสียอีก 🍱\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมก้าวออกจากกรง",

            // 6. โซเครติส
            "📜 *โซเครติสว่า: \"รู้จักตนเอง คือจุดเริ่มต้นของปัญญา\"*\n\n"
                ."การรู้จักดวงตัวเอง = รู้จักจุดแข็ง จุดอ่อน จังหวะชีวิต\n"
                ."ราคาเพียง {$price} บาท — ถูกกว่าหนังสือเล่มหนึ่ง 📚\n\n"
                .'🔮 ที่นี่วิเคราะห์จากดาวเจ้าชนะของเจ้าชะตาคนเดียว — ไม่ใช่คำกลางๆ',

            // 7. กระเป๋าเงิน
            "📜 *เงินในกระเป๋าหายไปเฉยๆ ทุกเดือนกี่ {$price} บาท?*\n\n"
                ."ค่าขนม ค่ารถ ค่าแอป — เจ้าชะตาจ่ายโดยไม่คิด\n"
                ."แต่ความรู้เรื่องชะตาตัวเอง — กลับลังเล 🤔\n\n"
                ."ความสำเร็จ = ลำดับความสำคัญถูก\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมจัดลำดับใหม่",

            // 8. กุญแจอนาคต
            "📜 *กุญแจอนาคต ราคา {$price} บาท*\n\n"
                ."ประตูข้างหน้ามีหลายบาน บางบานเปิดสู่โอกาส บางบานเปิดสู่ปัญหา\n"
                ."ดาวเจ้าชนะของเจ้าชะตา = แผนที่บอกว่าควรเปิดบานไหน 🗝️\n\n"
                ."ไม่กล้าซื้อกุญแจ ก็ต้องเดินชนกำแพงไปเรื่อยๆ\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมรับกุญแจ",

            // 9. นักลงทุนกับนักผัดวันประกันพรุ่ง
            "📜 *Warren Buffett ว่า: \"การลงทุนที่ดีที่สุดคือลงทุนในตัวเอง\"*\n\n"
                ."{$price} บาทเรียนรู้ดวงตัวเอง = การลงทุนเล็กที่สุดในชีวิต\n"
                ."แต่คนผัดวันประกันพรุ่ง — ลังเลแม้แค่นี้ 🕰️\n\n"
                .'🔮 พรุ่งนี้ที่ดีกว่า เริ่มจากการตัดสินใจวันนี้',

            // 10. ดาวยิปซีจริง
            "📜 *ที่นี่ไม่ใช่หมอดูที่ใดก็ตาม*\n\n"
                ."✨ ใช้หลัก *ดาวเจ้าชนะ* — คำนวณจริงจากวันเดือนปีเกิด\n"
                ."✨ ไพ่ยิปซีที่ *จิตเจ้าชะตาเลือก* — ไม่ใช่สุ่มมั่ว\n"
                ."✨ คำทำนายเจาะตัวเจ้าชะตาคนเดียว ไม่ใช่คำกลางๆ\n\n"
                ."{$price} บาท — พิสูจน์ด้วยตัวเอง\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อม",

            // 11. ลาว-จื๊อ
            "📜 *ลาว-จื๊อว่า: \"ผู้รู้คนอื่นฉลาด ผู้รู้ตนเองเป็นบัณฑิต\"*\n\n"
                ."การรู้ตนเอง = รู้ดวง รู้จังหวะ รู้สิ่งที่ดาวส่งสัญญาณ\n"
                ."{$price} บาท — น้อยกว่าค่ารถบัสไปทำงาน 1 วัน 🚌\n\n"
                ."🔮 บัณฑิตเริ่มจากความเต็มใจรู้ — พิมพ์ 'ดูดวง'",

            // 12. กลัวเสียเงินกับกลัวพลาดโอกาส
            "📜 *คนแพ้กลัวเสียเงิน คนชนะกลัวพลาดโอกาส*\n\n"
                ."{$price} บาทไม่ใช่เงินที่ทำให้จน\n"
                ."แต่ \"ไม่รู้จังหวะชีวิต\" อาจทำให้พลาดโอกาสล้านบาท 💎\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยนมุมมอง",

            // 13. ค่าโทรศัพท์รายเดือน
            "📜 *ค่าเน็ตมือถือเดือนละกี่ร้อย — เจ้าชะตาจ่ายไม่กระพริบตา*\n\n"
                ."แต่ {$price} บาทรู้อนาคตชีวิต — กลับลังเล 📱\n\n"
                ."สิ่งที่ใช้แล้วหายไป = จ่ายง่าย\n"
                ."สิ่งที่ใช้แล้วเปลี่ยนชีวิต = ลังเล\n\n"
                ."ปราชญ์ว่า: \"ลำดับความสำคัญผิด ชีวิตก็ผิดทาง\"\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมจัดลำดับใหม่",

            // 14. ยุวกาล / นักธุรกิจ
            "📜 *Steve Jobs ว่า: \"คุณเชื่อมจุดได้แค่มองย้อนกลับ\"*\n\n"
                ."แต่ดวงดาว = แผนที่ที่เห็นจุดข้างหน้า ก่อนที่ชีวิตจะเดินผ่าน 🌟\n"
                ."{$price} บาท เห็นแผนที่ก่อนเดิน\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเชื่อมจุดล่วงหน้า",

            // 15. ไม่งมงาย — มีหลักการ
            "📜 *การดูดวงที่นี่ไม่ใช่งมงาย*\n\n"
                ."✓ คำนวณจากตำแหน่งดาวจริง ณ เวลาเกิด\n"
                ."✓ ใช้ไพ่ยิปซี 78 ใบ — ที่จิตเจ้าชะตาเลือกเอง\n"
                ."✓ มีหลักการ มีระบบ ไม่ใช่ยกเมฆ\n\n"
                ."{$price} บาท — ทดสอบด้วยตัวเองว่าเป็นอย่างไร\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมพิสูจน์",

            // 16. ไม่เริ่มต้น = ไม่มีอะไรเปลี่ยน
            "📜 *ไอน์สไตน์ว่า: \"คนบ้าคือคนที่ทำสิ่งเดิมแล้วหวังผลใหม่\"*\n\n"
                ."ถ้าไม่กล้าลงทุน {$price} บาทกับสิ่งใหม่ —\n"
                ."ชีวิตก็จะวนลูปเหมือนเดิมไปเรื่อย ๆ 🔄\n\n"
                ."🔮 ทางออก = กล้าทำสิ่งที่ไม่เคยทำ\n"
                ."พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยน",

            // 17. ค่าหวยเสี่ยงสูง
            "📜 *ซื้อหวย 200 บาท โอกาสถูก 1 ใน ล้าน*\n\n"
                ."{$price} บาทดูดวง โอกาสได้คำตอบเจาะตัว = 100% ✨\n\n"
                ."ปราชญ์ว่า: \"คนฉลาดเลือกความน่าจะเป็นที่สูงเสมอ\"\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเลือกฉลาด",

            // 18. การลงทุนกับการกลัว
            "📜 *Frank Herbert ว่า: \"ความกลัวคือผู้ฆ่าจิตใจ\"*\n\n"
                ."กลัวเสีย {$price} บาท = กลัวสิ่งเล็ก\n"
                ."แต่กลัวอนาคตที่ไม่รู้ = ใช้ชีวิตทั้งชีวิต 🌑\n\n"
                ."🔮 ความกลัวเล็กแลกความกลัวใหญ่\n"
                ."พิมพ์ 'ดูดวง' เมื่อพร้อมเลิกกลัว",

            // 19. รู้แล้วจะรอ — รอแล้วจะเสีย
            "📜 *เวลาคือทรัพย์ที่ซื้อคืนไม่ได้*\n\n"
                ."รู้ดวงตอนนี้ = ตัดสินใจถูกในเดือนหน้า\n"
                ."ไม่รู้ดวง = เดาเองไปทั้งปี ⏰\n\n"
                ."{$price} บาท — ถูกกว่าที่จะปล่อยให้เสียเวลา\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมประหยัดเวลา",

            // 20. คนสำเร็จ vs คนไม่สำเร็จ
            "📜 *ความแตกต่างของคนสำเร็จ ≠ ความฉลาด แต่คือ \"การลงมือ\"*\n\n"
                ."{$price} บาทไม่ใช่เงิน — เป็นการตัดสินใจ\n"
                ."ลงมือ = เห็นผล\n"
                ."ลังเล = อยู่ที่เดิม 🌱\n\n"
                ."ที่นี่ใช้ดาวเจ้าชนะ + ไพ่ยิปซีจริง — ไม่ยกเมฆ\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมตัดสินใจ",

            // 21. กรรมบังตา — น่าเสียดาย
            "📜 *น่าเสียดาย…*\n\n"
                ."ดวงดาวส่งจังหวะมาแล้ว เจ้าชะตาเห็น\n"
                ."กุญแจวางตรงหน้า — แค่ {$price} บาท\n"
                ."แต่จิตไม่กล้าหยิบ\n\n"
                ."นี่ไม่ใช่ความขี้เหนียว — นี่คือ *กรรมเก่ายังบังตา*\n"
                ."ทำให้ลังเลแม้กับสิ่งที่เห็นชัดว่าคุ้ม 🕯️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมตัดกรรม",

            // 22. คนกรรมเบา vs คนกรรมหนัก
            "📜 *คนกรรมเบาลงมือก่อน — คนกรรมหนักลังเลก่อน*\n\n"
                ."{$price} บาทไม่ใช่เรื่องเงิน — เป็นเรื่อง *จิต*\n"
                ."ที่ติดอยู่กับกรรมเก่า จนตัดสินใจไม่ได้แม้สิ่งเล็กที่สุด\n\n"
                ."น่าเสียดาย — เพราะดาวเจ้าชนะของเจ้าชะตาเปิดประตูให้แล้ว\n"
                ."แต่จิตยังถูกกรรมเก่าฉุดอยู่หลังประตูเดิม 🚪\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมก้าว",

            // 23. การไม่ตัดสินใจ = ผลของกรรม
            "📜 *การไม่ตัดสินใจ ก็คือการตัดสินใจอย่างหนึ่ง*\n\n"
                ."พระว่า: \"กรรมที่ทำให้ลังเลซ้ำๆ คือกรรมที่ยังต้องชดใช้\"\n\n"
                ."ไม่ใช่ {$price} บาทที่หยุดเจ้าชะตา\n"
                ."แต่คือกรรมเก่าที่กระซิบในใจว่า \"อย่าเริ่ม อย่าลอง อย่าก้าว\"\n\n"
                ."น่าเสียดายที่จังหวะดาวมาถึง — แต่กรรมขังจิตไว้ที่เดิม 🌙\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมปลด",

            // 24. กุญแจ vs ตัวล็อค
            "📜 *กุญแจอยู่ตรงหน้า — ตัวล็อคอยู่ในจิต*\n\n"
                ."ดูดวง {$price} บาท ไม่ใช่กุญแจของหมอ —\n"
                ."เป็นกุญแจที่ *จิตเจ้าชะตาเลือกหยิบหรือไม่หยิบ*\n\n"
                ."• คนที่หยิบ = คนที่กรรมเริ่มเบา\n"
                ."• คนที่ลังเล = คนที่กรรมยังหนักพอจะกั้นจิต\n\n"
                ."น่าเสียดายเหลือเกิน — จังหวะดาวจริงเปิดมาแล้ว 🗝️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อกรรมเริ่มคลาย",

            // 25. กรรมหนัก = ความกลัวเงินเล็ก
            "📜 *ปราชญ์โบราณว่า: คนที่กลัวเสียเงินเล็ก คือคนที่กรรมยังบังให้มองไม่เห็นโอกาสใหญ่*\n\n"
                ."{$price} บาท — เจ้าชะตามองว่ามาก\n"
                ."เพราะกรรมในจิตปรับมุมมองให้ \"กลัวเสีย\" มากกว่า \"กล้าได้\"\n\n"
                ."น่าเสียดาย…\n"
                ."ดาวเจ้าชนะส่งจังหวะมาแล้ว ไพ่ก็ตั้งรอ\n"
                ."แต่กรรมเก่าทำให้จิตเลือก *อยู่ที่เดิม* ที่คุ้นเคย ⚖️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยนวิบาก",
        ];

        // หมุนตาม reading_id ให้ stable (เจ้าชะตาคนเดิมเห็นเหมือนเดิม)
        $idx = abs(crc32((string) $reading->id)) % count($wisdomMessages);

        // Header (ยกเลิกอัตโนมัติ + เลขบิล + เหตุผล) ถูก compose ที่ต้น method แล้ว
        return $header.$wisdomMessages[$idx];
    }

    /**
     * Helper รวม query logic (DRY ระหว่าง per-user และ global)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     */
    protected static function expireOldConversationsQuery($baseQuery): int
    {
        // ปิด conversation ทั่วไป + pending_payment (Deep + Celtic) ที่ค้างเกิน 30 นาที
        // 🚨 (2026-05-14) CRITICAL FIX: เพิ่ม is_paid=false guard
        //   user report: "บิล 39 บาท โอนแล้ว ไม่ยอมเริ่มกระบวนการทำนาย"
        //   Root cause: COLLECTING_BIRTHDATE หลัง Pay-First Deep (ลูกค้าจ่ายแล้ว — รอวันเกิด)
        //               → 30 min ผ่าน → expire → mark COMPLETED
        //               → auto-recovery filter status=COLLECTING_BIRTHDATE → skip
        //               → ลูกค้าเสียเงิน ไม่ได้ทำนาย
        //   Fix: ห้าม expire reading ที่ลูกค้าจ่ายเงินไปแล้ว (is_paid=true)
        //        — ปล่อยให้ auto-recovery scheduler ผลักดัน flow ต่อ
        $expired = (clone $baseQuery)
            ->whereIn('conversation_status', [
                self::STATUS_AWAITING_CONFIRMATION,
                self::STATUS_BASIC_DONE,
                self::STATUS_COLLECTING_BIRTHDATE,
                self::STATUS_COLLECTING_QUESTIONS,
                self::STATUS_COLLECTING_TAROT,
                self::STATUS_DISCOVERY_CHAT,
                self::STATUS_DISCOVERY_CONFIRM,
                self::STATUS_TIER_CHOICE,
            ])
            ->where('updated_at', '<', now()->subMinutes(self::PAYMENT_TIMEOUT_MINUTES))
            ->where('is_paid', false) // 🛡️ paid bills ห้าม expire — ปล่อย auto-recovery รับช่วง
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        // ⏰ (2026-06-12) บิลรอชำระ (Deep + Celtic) แยก timeout ตาม admin setting (default 3 ชม.)
        //   เดิมรวมอยู่ก้อนเดียวกับ 30 นาทีด้านบน — เจ้าของสั่งขยายเฉพาะ "บิล" เป็น 3 ชม.
        //   หมายเหตุ: บิลที่มี UPA จะถูก cancelExpiredPendingBills จัดการก่อน (cancel UPA + FCM + เตือนสติ)
        //   ก้อนนี้เป็น safety net สำหรับบิลที่ไม่มี UPA / หลุดจาก cron
        // 🩹 (2026-08-07) เพิ่ม awaiting_payment_method + pending_stripe_payment เข้าก้อนนี้
        //   ก่อนหน้านี้ **ไม่มีใครเก็บกวาด 2 สถานะนี้เลย** — ไม่อยู่ทั้งก้อน 30 นาทีด้านบน
        //   และไม่อยู่ใน PENDING_PAYMENT_STATUSES → ค้างถาวรใน DB
        //   หลักฐาน prod: บิล awaiting_payment_method 417 ใบ **เก่ากว่า 30 วันทั้งหมด**
        //   (ไม่มีสักใบในรอบเดือน) ทำให้ KPI "รอชำระ" หลอกตาว่ามีงานต้องตาม
        //
        //   ใช้ billTimeoutMinutes (default 3 ชม.) ไม่ใช่ 30 นาที — สถานะนี้อยู่ในโฟลจ่ายเงิน
        //   เจ้าของเคยสั่งไว้ว่า "บิลยกเลิกเร็วไป บางคนลืม" → ให้เวลาเท่าบิล
        //   ⚠️ ปลอดภัย: 2 สถานะนี้ไม่อยู่ใน scopeActiveConversation อยู่แล้ว
        //      = ไม่เคยบล็อกอะไรของลูกค้า การปิดจึงเป็นการเก็บกวาดล้วน ๆ
        $expiredBills = (clone $baseQuery)
            ->whereIn('conversation_status', array_merge(self::PENDING_PAYMENT_STATUSES, [
                self::STATUS_AWAITING_PAYMENT_METHOD,
                self::STATUS_PENDING_STRIPE_PAYMENT,
            ]))
            ->where('updated_at', '<', now()->subMinutes(self::billTimeoutMinutes()))
            ->where('is_paid', false)
            ->update([
                'conversation_status' => self::STATUS_COMPLETED,
                // 🏷️ (2026-08-07) บันทึกเหตุผลด้วย ไม่งั้นบิลไปกองรวมกับ "ปิดเงียบ" ในหน้าแอดมิน
                //   ทำให้ดูเหมือนลูกค้าหายไปเอง ทั้งที่ระบบเป็นคนยกเลิกตามเวลา
                //   ใช้ JSON_SET เพื่อไม่ทับ state อื่นที่มีอยู่ (COALESCE กันเคส state เป็น NULL)
                'conversation_state' => \DB::raw(
                    "JSON_SET(COALESCE(conversation_state, '{}'), '$.cancellation_reason', 'auto_expired')"
                ),
            ]);

        $expired += $expiredBills;

        // ปิด PAID ที่ค้างเกิน timeout (AI processing ล้มเหลว/timeout)
        // 🛡️ (2026-05-24) USER RULE: paid bills ห้าม expire ทุกกรณี
        //   "ลูกค้าโอนตรงยอด ตัดบิลแล้ว แต่ยังไม่ใช้บริการเสร็จสิ้น
        //    เมื่อกลับมา ก็จะกู้บริการต่อจากจุดที่ค้างไว้ได้เสมอ — ทุกกรณี"
        //   STATUS_PAID by definition implies is_paid=true → branch นี้กลายเป็น no-op
        //   เก็บไว้เพื่อป้องกัน legacy data inconsistency (status=PAID + is_paid=false)
        //   Cleanup งาน admin: fortune:expire-stuck-paid (24hr+) ผ่าน admin_review_alerted flag
        $expiredPaid = (clone $baseQuery)
            ->where('conversation_status', self::STATUS_PAID)
            ->where('is_paid', false)
            ->where('updated_at', '<', now()->subMinutes(self::PAID_PROCESSING_TIMEOUT_MINUTES))
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        // 🔮 Celtic flow states (PICKING / AWAITING / GENERATING / QA_PROMPT)
        // 🛡️ (2026-05-24) USER RULE: Celtic paid bills ห้าม expire ทุกกรณี (เหมือน STATUS_PAID)
        //   Celtic states ตั้งหลังจ่ายเงินทุก state → is_paid=true เป็นปกติ
        //   is_paid=false guard ป้องกัน legacy data inconsistency เท่านั้น
        $expiredCelticGenerating = (clone $baseQuery)
            ->where('conversation_status', self::STATUS_CELTIC_GENERATING)
            ->where('is_paid', false)
            ->where('updated_at', '<', now()->subMinutes(self::PAID_PROCESSING_TIMEOUT_MINUTES))
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        $expiredCelticFlow = (clone $baseQuery)
            ->whereIn('conversation_status', [
                self::STATUS_CELTIC_PICKING,
                self::STATUS_CELTIC_AWAITING_QUESTION,
                self::STATUS_CELTIC_QA_PROMPT,
            ])
            ->where('is_paid', false)
            ->where('updated_at', '<', now()->subMinutes(90))
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        return $expired + $expiredPaid + $expiredCelticGenerating + $expiredCelticFlow;
    }

    /**
     * ค้นหา reading ที่รอชำระเงินโดย unique amount
     *
     * กรองเฉพาะ transaction_type = 'fortune_reading' เพื่อแยกบิลดูดวง
     * ไม่ให้ปะปนกับบิลอีคอมเมิร์ซหรือ seller
     *
     * 🔒 SECURITY (2026-04-28): รับ smsTimestamp เพื่อกัน SMS เก่ามา match บิลใหม่
     *
     * @param  float  $amount  จำนวนเงินที่ได้รับจาก SMS
     * @param  \Carbon\Carbon|null  $smsTimestamp  เวลาที่ SMS เข้า
     */
    public static function findByUniqueAmount(float $amount, ?\Carbon\Carbon $smsTimestamp = null): ?self
    {
        // กรองเฉพาะ fortune_reading เพื่อไม่ให้ match ข้ามระบบ
        $uniquePayment = UniquePaymentAmount::findMatch($amount, 'fortune_reading', $smsTimestamp);

        if (! $uniquePayment) {
            return null;
        }

        // 🔮 รองรับทั้ง Deep 39฿ (STATUS_PENDING_PAYMENT) และ Celtic Cross 99฿ (STATUS_CELTIC_PENDING_PAYMENT)
        // ทั้งสองระบบใช้ UniquePaymentAmount type='fortune_reading' เหมือนกัน — branch ตาม reading_type ใน caller
        //
        // 🌍 (2026-08-23) ต้องมี STATUS_PENDING_STRIPE_PAYMENT ด้วย
        //   เลนบัตรต่างประเทศเปลี่ยนสถานะบิลเป็น pending_stripe_payment แต่ **ยอด QR ไทยยังจองอยู่**
        //   ลูกค้าขอลิงก์บัตร → เปลี่ยนใจ → สแกน QR ใบเดิม → SMS เข้า
        //   ถ้าไม่มีสถานะนี้ในลิสต์ = หาบิลไม่เจอ = เงินเข้าแต่บิลไม่ถูกตัด ลูกค้าไม่ได้คำทำนาย
        return self::where('unique_payment_amount_id', $uniquePayment->id)
            ->whereIn('conversation_status', [
                self::STATUS_PENDING_PAYMENT,
                self::STATUS_CELTIC_PENDING_PAYMENT,
                self::STATUS_PENDING_STRIPE_PAYMENT,
            ])
            ->first();
    }

    /**
     * อัพเดทสถานะ conversation
     */
    public function updateConversationStatus(string $status): void
    {
        $this->update(['conversation_status' => $status]);
    }

    /**
     * เก็บข้อมูลใน conversation state
     *
     * @param  mixed  $value
     */
    public function setConversationState(string $key, $value): void
    {
        $state = $this->conversation_state ?? [];
        $state[$key] = $value;
        $this->update(['conversation_state' => $state]);
    }

    /**
     * 🛡️ (2026-08-12, owner "ห้ามมีบิลซ้อน") บิลนี้ถูกปิดไปแล้วหรือยัง เพราะ "บิลพี่น้อง" ถูกจ่ายไปก่อน?
     *
     * อ่าน marker ที่ FortuneConversationService::cancelCompetingPrePaymentReadings() เขียนไว้
     * แล้วคืนบิลที่จ่ายแล้วตัวจริง — ใช้กัน 2 อย่าง:
     *   1. งานตรวจสลิปที่ค้างคิว ปลุกบิลที่ตายแล้วกลับมาทวงเงิน (VerifySlipFallbackJob / handlePartialPayment)
     *   2. แอดมินกด force-approve บิลที่ตายแล้วจากแอพ SMS (SmsPaymentController)
     *
     * ⚠️ ห้ามใช้แทน "บิลถูกยกเลิก" ทั่วไป — บิลยกเลิก/หมดอายุที่ยังไม่มีใครจ่ายแทน จะคืน null
     *   (โอนช้าแล้วแอดมินตัดบิลย้อนหลัง = เคสจริงที่ต้องทำได้ต่อไป)
     *
     * @return static|null บิลที่จ่ายแล้วซึ่งมาแทนบิลนี้ (null = บิลนี้ไม่ได้ถูกแทน)
     */
    public function supersededByPaidReading(): ?self
    {
        // บิลนี้จ่ายเองแล้ว = ไม่ใช่บิลที่ถูกแทน
        if ($this->is_paid) {
            return null;
        }

        if ($this->getConversationState('cancellation_reason') !== 'superseded_by_paid') {
            return null;
        }

        $keptId = (int) ($this->getConversationState('superseded_by_reading_id') ?? 0);
        if ($keptId <= 0 || $keptId === (int) $this->id) {
            return null;
        }

        $kept = static::find($keptId);

        // ต้องจ่ายจริงเท่านั้น — ถ้าบิลที่มาแทนโดนยกเลิกทีหลัง ปล่อยบิลนี้เดินตาม flow เดิม
        return ($kept && $kept->is_paid) ? $kept : null;
    }

    /**
     * 🃏 (2026-05-17) เช็คว่ายังสับไพ่ใหม่ได้อีกหรือไม่ (Celtic Cross 99฿)
     *
     * ลูกค้าสับไพ่ใหม่ได้แค่ 1 ครั้ง/บิล (anti-fraud — ป้องกันสับจนได้ไพ่ที่ "ชอบ")
     * Counter เก็บใน conversation_state['celtic_shuffle_count']
     */
    public function canShuffleCelticAgain(): bool
    {
        return (int) $this->getConversationState('celtic_shuffle_count', 0) < 1;
    }

    /**
     * ดึงข้อมูลจาก conversation state
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getConversationState(string $key, $default = null)
    {
        // 🛑 (2026-05-06) Pay-Later removed — บังคับ false ทุก read site ของ flag เก่า
        //   ครอบคลุมโค้ดเก่า (Job/Trait) ที่เช็ค is_request_before_pay → fall through pay-first
        //   safety guard: existing DB rows ที่มี flag = true จะถูก clamp เป็น false
        if ($key === 'is_request_before_pay') {
            return false;
        }

        $state = $this->conversation_state;

        return is_array($state) ? ($state[$key] ?? $default) : $default;
    }

    /**
     * 👤 Resolve customer name via fallback chain (เคสชื่อหายระหว่าง flow)
     *
     * Priority:
     *   1. facebook_user_name ของ reading (ถ้าเป็นชื่อคนจริง)
     *   2. user_profile['name'] ของ reading
     *   3. FortuneUserCredit ของ user คนนี้ (cross-conversation persistent)
     *   4. ดึงจาก FortuneReading เก่าๆ ของ user เดียวกัน (latest with valid name)
     *   5. user.name (registered user account)
     *   6. 'คุณ' (default สุดท้าย — ดีกว่าโชว์ code "FACEBOOK-XXXXXX")
     *
     * Side effect: persist กลับ DB เฉพาะกรณีได้ชื่อคนจริง (ไม่ persist 'คุณ' / code-pattern)
     *
     * ⚠️ ห้าม fallback เป็น "PLATFORM-XXXXXX" — เคยมีบั๊ก: persist ลง DB แล้ว historical lookup
     *    เอามาใช้ซ้ำ → ลูกค้าเห็น "FACEBOOK-494919" ตลอด
     */
    public function resolveCustomerName(): string
    {
        // 1. reading.facebook_user_name
        if ($this->isHumanLikeName($this->facebook_user_name)) {
            return $this->facebook_user_name;
        }

        $resolved = null;

        // 2. user_profile.name
        $profile = $this->user_profile ?? [];
        if (is_array($profile) && $this->isHumanLikeName($profile['name'] ?? null)) {
            $resolved = $profile['name'];
        }

        // 3. FortuneUserCredit (persistent across conversations)
        if (! $resolved) {
            try {
                $userId = $this->platform_user_id ?? $this->facebook_user_id;
                if (! empty($userId)) {
                    $credit = \App\Models\FortuneUserCredit::findByUser(
                        $userId,
                        $this->platform ?? 'facebook'
                    );
                    if ($credit && $this->isHumanLikeName($credit->facebook_user_name)) {
                        $resolved = $credit->facebook_user_name;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 4. Historical reading ของ user เดียวกันที่มีชื่อจริง
        if (! $resolved) {
            try {
                $userId = $this->platform_user_id ?? $this->facebook_user_id;
                if (! empty($userId)) {
                    $candidates = self::where(function ($q) use ($userId) {
                        $q->where('facebook_user_id', $userId)
                            ->orWhere('platform_user_id', $userId);
                    })
                        ->whereNotNull('facebook_user_name')
                        ->where('facebook_user_name', '!=', '')
                        ->where('id', '!=', $this->id)
                        ->latest('updated_at')
                        ->limit(10)
                        ->pluck('facebook_user_name');

                    foreach ($candidates as $candidate) {
                        if ($this->isHumanLikeName($candidate)) {
                            $resolved = $candidate;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 5. user.name (registered)
        if (! $resolved) {
            try {
                if ($this->user && $this->isHumanLikeName($this->user->name)) {
                    $resolved = $this->user->name;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 6. Default 'คุณ' — ดีกว่าโชว์ code
        if (! $resolved) {
            return 'คุณ';
        }

        // 💾 Persist กลับ DB เฉพาะกรณีเดิมเป็น empty/'คุณ'/code → resolved ชื่อคนจริง
        try {
            $current = $this->facebook_user_name;
            if ($this->isHumanLikeName($resolved) && ! $this->isHumanLikeName($current)) {
                $this->update(['facebook_user_name' => $resolved]);
                \Log::debug('FortuneReading: persisted resolved customer name', [
                    'reading_id' => $this->id,
                    'resolved_name' => $resolved,
                ]);
            }
        } catch (\Throwable $e) {
            // ignore — return resolved name อย่างน้อย
        }

        return $resolved;
    }

    /**
     * ตรวจว่าค่าที่ได้ "ดูเป็นชื่อคนจริง" หรือเปล่า
     *
     * เกณฑ์:
     *   - ไม่ใช่ null / empty / 'คุณ'
     *   - ไม่ใช่ code pattern PLATFORM-XXXXXX (FACEBOOK-, LINE-, FB-, ...)
     *   - ไม่ใช่ platform user ID เปล่า ๆ (33+ chars hex / numeric long string)
     */
    protected function isHumanLikeName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if ($name === '' || $name === 'คุณ' || $name === 'ลูกค้า' || $name === 'เจ้าชะตา') {
            return false;
        }
        // Code pattern: FACEBOOK-XXXX, LINE-XXXX, FB-XXXX (uppercase prefix + dash + alphanum)
        if (preg_match('/^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$/i', $name)) {
            return false;
        }
        // Platform user ID เปล่า ๆ: LINE userId = U + 32 hex, FB PSID = numeric 15+ chars
        if (preg_match('/^U[0-9a-f]{32}$/i', $name)) {
            return false;
        }
        if (preg_match('/^\d{15,}$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Accessor: $reading->resolved_customer_name
     */
    public function getResolvedCustomerNameAttribute(): string
    {
        return $this->resolveCustomerName();
    }

    /**
     * เพิ่มคำถามเข้าไปใน state
     *
     * @return int จำนวนคำถามปัจจุบัน
     */
    public function addQuestion(string $question): int
    {
        $questions = $this->getConversationState('collected_questions', []);
        $questions[] = $question;
        $this->setConversationState('collected_questions', $questions);

        return count($questions);
    }

    /**
     * ดึงคำถามที่เก็บไว้ทั้งหมด
     */
    public function getCollectedQuestions(): array
    {
        return $this->getConversationState('collected_questions', []);
    }

    /**
     * เพิ่มไพ่ยิปซีที่สุ่มได้เข้าไปใน state (เฉพาะแบบเสียเงิน)
     *
     * @param  int  $questionIndex  ลำดับคำถามที่ไพ่นี้ประกอบ (0-based)
     * @param  int  $cardId  ID ของไพ่จาก TarotCard
     * @param  string  $cardNameTh  ชื่อไพ่ภาษาไทย
     * @param  string  $cardNameEn  ชื่อไพ่ภาษาอังกฤษ
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     * @param  string  $meaning  ความหมายของไพ่ตามตำแหน่ง
     * @return int จำนวนไพ่ที่เก็บไว้
     */
    public function addTarotCard(int $questionIndex, int $cardId, string $cardNameTh, string $cardNameEn, bool $isReversed, string $meaning, ?string $imageUrl = null): int
    {
        $cards = $this->getConversationState('collected_tarot_cards', []);
        $cards[] = [
            'question_index' => $questionIndex,
            'card_id' => $cardId,
            'card_name_th' => $cardNameTh,
            'card_name_en' => $cardNameEn,
            'is_reversed' => $isReversed,
            'meaning' => $meaning,
            'image_url' => $imageUrl,
        ];
        $this->setConversationState('collected_tarot_cards', $cards);

        return count($cards);
    }

    /**
     * ดึงไพ่ยิปซีที่เก็บไว้ทั้งหมด
     */
    public function getCollectedTarotCards(): array
    {
        return $this->getConversationState('collected_tarot_cards', []);
    }

    /**
     * ดึงไพ่ยิปซีสำหรับคำถามข้อที่ระบุ (0-based index)
     */
    public function getTarotCardForQuestion(int $questionIndex): ?array
    {
        $cards = $this->getCollectedTarotCards();
        foreach ($cards as $card) {
            if (($card['question_index'] ?? -1) === $questionIndex) {
                return $card;
            }
        }

        return null;
    }

    /**
     * ตรวจสอบว่ารอชำระเงินอยู่หรือไม่
     */
    public function isPendingPayment(): bool
    {
        return $this->conversation_status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * ตรวจสอบว่าเสร็จสิ้นขั้นตอนพื้นฐานแล้วหรือไม่
     */
    public function isBasicDone(): bool
    {
        return $this->conversation_status === self::STATUS_BASIC_DONE;
    }

    /**
     * บันทึกคำทำนายพื้นฐานและเปลี่ยนสถานะ
     */
    public function saveBasicReading(string $response, string $provider, string $model, int $tokensUsed): void
    {
        $this->update([
            'basic_response' => $response,
            'ai_response' => $response,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => $tokensUsed,
            'conversation_status' => self::STATUS_BASIC_DONE,
            'responded_at' => now(),
        ]);
    }

    /**
     * บันทึกคำทำนายละเอียดหลังชำระเงิน
     *
     * ใช้ DB::table query โดยตรงแทน Eloquent update
     * เพราะหลัง AI generation 45-60 วินาที MySQL connection อาจ stale
     * และ Eloquent $this->update() อาจ return false โดยไม่ throw exception
     */
    public function saveDeepReading(string $response, string $provider, string $model, int $tokensUsed): void
    {
        $updateData = [
            'deep_response' => $response,
            'ai_response' => $response,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => ($this->tokens_used ?? 0) + $tokensUsed,
            'conversation_status' => self::STATUS_COMPLETED,
            'reading_type' => 'deep',
            'updated_at' => now(),
        ];

        // ใช้ DB::table query ตรง — หลีกเลี่ยง Eloquent stale connection
        $affected = \Illuminate\Support\Facades\DB::table($this->table)
            ->where('id', $this->id)
            ->update($updateData);

        if ($affected > 0) {
            // Sync model attributes ให้ตรงกับ DB
            $this->forceFill($updateData)->syncOriginal();
            \Illuminate\Support\Facades\Log::info('Fortune: saveDeepReading สำเร็จ (DB::table)', [
                'reading_id' => $this->id,
                'affected_rows' => $affected,
            ]);
        } else {
            // Fallback: ลอง Eloquent refresh + update
            $this->refresh();
            $result = $this->update($updateData);
            \Illuminate\Support\Facades\Log::warning('Fortune: saveDeepReading fallback to Eloquent', [
                'reading_id' => $this->id,
                'eloquent_result' => $result,
            ]);

            if (! $result) {
                throw new \RuntimeException(
                    "saveDeepReading failed: DB::table affected 0 rows, Eloquent returned false for reading #{$this->id}"
                );
            }
        }
    }

    /**
     * ตั้งค่า unique payment amount และเปลี่ยนสถานะเป็นรอชำระ
     */
    public function setPendingPayment(UniquePaymentAmount $uniqueAmount): void
    {
        $updateData = [
            'unique_payment_amount_id' => $uniqueAmount->id,
            'amount_paid' => $uniqueAmount->unique_amount,
            'conversation_status' => self::STATUS_PENDING_PAYMENT,
        ];

        // Safety net: ถ้ายังไม่มี bill_reference → สร้างให้
        // กรณี reading มาจาก basic→upsell path หรือ boot creating ไม่ได้สร้าง
        if (empty($this->bill_reference)) {
            $updateData['bill_reference'] = self::generateBillReference();
        }

        // ถ้า reading_type ยังเป็น basic → เปลี่ยนเป็น deep (กำลังจะชำระเงิน)
        if ($this->reading_type !== 'deep') {
            $updateData['reading_type'] = 'deep';
        }

        $this->update($updateData);
    }

    /**
     * ⏱️ (2026-06-05) นาทีที่พัก HOLD (โอนขาดครบ 3 รอบ → รอแม่หมอ/แอดมินตรวจ) ก่อนออกจาก ride
     */
    public const PARTIAL_HOLD_MINUTES = 60;

    /**
     * 💰 (2026-06-05) กำลังพัก HOLD รอแม่หมอ/แอดมินตรวจอยู่ไหม (ยังไม่เกิน 60 นาที)
     */
    public function isPartialHoldActive(): bool
    {
        return $this->partial_hold_at !== null
            && $this->partial_hold_at->gt(now()->subMinutes(self::PARTIAL_HOLD_MINUTES));
    }

    /**
     * 💰 (2026-06-05) HOLD หมดเวลาแล้ว (เกิน 60 นาที) — ควรออกจาก ride
     */
    public function isPartialHoldExpired(): bool
    {
        return $this->partial_hold_at !== null
            && $this->partial_hold_at->lte(now()->subMinutes(self::PARTIAL_HOLD_MINUTES));
    }

    /**
     * 💰 (2026-06-05) ยอดที่ยังขาดอยู่ (เป้าหมาย - ที่รับแล้ว) — ปัด 2 ตำแหน่ง ไม่ติดลบ
     */
    public function partialRemaining(): float
    {
        $target = (float) ($this->partial_target_total ?? 0);
        $paid = (float) ($this->partial_paid_total ?? 0);

        return max(0, round($target - $paid, 2));
    }

    /**
     * ยืนยันการชำระเงินและเปลี่ยนสถานะ
     */
    public function confirmPayment(?SmsPaymentNotification $notification = null): void
    {
        // ✅ Idempotent: ถ้าชำระแล้ว ไม่ต้องทำซ้ำ (ป้องกัน paid_at ถูก reset)
        if ($this->is_paid) {
            // อัพเดทเฉพาะ SMS notification info ถ้ายังไม่มี
            if ($notification && empty($this->sms_notification_id)) {
                $this->update([
                    'sms_notification_id' => $notification->id,
                    'sender_info' => $notification->sender_or_receiver,
                    'sender_bank' => $notification->bank,
                ]);
            }

            return;
        }

        $updateData = [
            'is_paid' => true,
            'paid_at' => now(),
            'conversation_status' => self::STATUS_PAID,
        ];

        if ($notification) {
            $updateData['sms_notification_id'] = $notification->id;
            $updateData['sender_info'] = $notification->sender_or_receiver;
            $updateData['sender_bank'] = $notification->bank;
        }

        $this->update($updateData);

        // อัพเดท unique payment amount เป็น used
        if ($this->unique_payment_amount_id) {
            UniquePaymentAmount::where('id', $this->unique_payment_amount_id)
                ->update([
                    'status' => 'used',
                    'matched_at' => now(),
                ]);
        }

        // 👤 (2026-05-25 Patch F) Eager Persona extraction on payment
        //   เคสจริง: ลูกค้าทักเริ่ม → จ่ายภายใน 13 นาที (R3741) → persona ไม่ทันสร้าง
        //   ผลคือ AI ทำนาย Q1 โดยไม่รู้นิสัยลูกค้า → ตอบ generic
        //   Fix: trigger extraction ทันทีหลัง paid โดยใช้ messages 60min ก่อน paid_at
        try {
            app(\App\Services\Fortune\CustomerPersonaService::class)
                ->dispatchEagerExtractionOnPaid($this);
        } catch (\Throwable $e) {
            // Non-blocking — ไม่ให้กระทบ payment flow
            \Illuminate\Support\Facades\Log::warning(
                'FortuneReading: eager persona extraction failed (non-blocking)',
                ['reading_id' => $this->id, 'error' => $e->getMessage()]
            );
        }

        // 🔊 (2026-07-11) ลูกค้าจ่ายเงินแล้ว → ล้างธง "ปิดปาก" (silence) ถ้ามีค้าง
        //   กันเคส FTU-260711-T4317: จ่าย 39฿ แล้วโดน chat_silenced_until ค้าง → พอ Deep session
        //   7 นาทีหมด paid-bypass เลิกทำงาน → บอทเงียบใส่คนจ่ายเงินแล้ว. ครอบทุก path จ่าย
        //   (SMS match / admin force / slip) เพราะรวมศูนย์ที่ confirmPayment. Non-blocking.
        try {
            $silenceUid = $this->platform_user_id ?: $this->facebook_user_id;
            $silencePlatform = $this->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $silenceUid) ? 'line' : 'facebook');
            if (! empty($silenceUid)) {
                app(\App\Services\Fortune\CustomerPersonaService::class)
                    ->clearSilenceOnPaid($silencePlatform, (string) $silenceUid);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'FortuneReading: clear silence on paid failed (non-blocking)',
                ['reading_id' => $this->id, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * 🚫 (2026-06-08) ยกเลิกการอนุมัติบิล (Void Approval) — reverse ของ confirmPayment()
     *
     * Use case: แอดมินกด Force Approve ผิดบิล/ผิดคน (ลูกค้าไม่ได้จ่ายจริง)
     *   → บิลขึ้น "จ่ายแล้ว ✓" ค้างที่ celtic_picking → ต้องถอยกลับเป็น "ยังไม่จ่าย"
     *
     * คืนทุกอย่างที่ confirmPayment + commission distribution ทำไว้:
     *   1. UPA (unique payment amount) used → cancelled  (ปลดล็อกยอดทศนิยม ไม่ให้ค้าง used ถาวร)
     *   2. ปลด SMS notification ที่ผูกผิด → matched_transaction_id = null
     *      (เผื่อเงินจริงของคนอื่นถูก match ผิด — ปล่อยให้ไป match บิลถูกได้)
     *   3. reverse commission ที่จ่ายไปแล้ว (claw back wallet) — ถ้ามี
     *   4. is_paid=false, paid_at=null, amount_received=null, status=COMPLETED (= ปิดบิล)
     *   5. บันทึก audit flag ใน conversation_state
     *
     * Idempotent: ถ้าบิลยัง is_paid=false → คืน ['ok'=>false] เฉยๆ (ไม่ทำซ้ำ)
     *
     * ⚠️ ไม่แจ้งลูกค้า (ตาม spec — เคสนี้ลูกค้าไม่ได้จ่ายจริง การแจ้งจะสร้างความสับสน)
     *
     * @param  string|null  $reason  เหตุผลที่ยกเลิก (เก็บใน audit log)
     * @param  int|null  $adminId  id ของแอดมินที่กดยกเลิก
     * @return array{ok: bool, message?: string, reverted: array, warnings: array}
     */
    public function voidApproval(?string $reason = null, ?int $adminId = null): array
    {
        // Idempotent guard — ยังไม่จ่าย = ไม่มีอะไรให้ถอย
        if (! $this->is_paid) {
            return [
                'ok' => false,
                'message' => 'บิลนี้ยังไม่ได้มาร์คว่าจ่าย — ไม่มีอะไรให้ยกเลิก',
                'reverted' => [],
                'warnings' => [],
            ];
        }

        $reverted = [];
        $warnings = [];

        // 1) Reverse commission ก่อน (แต่ละแถวแยก transaction — กัน 1 แถวพังลาก reading flip ตาม)
        $commissions = FortuneCommission::where('fortune_reading_id', $this->id)
            ->where('status', FortuneCommission::STATUS_PAID)
            ->get();
        foreach ($commissions as $comm) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($comm) {
                    $this->reverseCommissionRow($comm);
                });
                $reverted[] = "commission#{$comm->id} (-{$comm->amount})";
            } catch (\Throwable $e) {
                $warnings[] = "commission#{$comm->id} ดึงคืนไม่สำเร็จ — ต้องแก้มือ";
                \Illuminate\Support\Facades\Log::error('FortuneReading::voidApproval — commission reverse failed', [
                    'reading_id' => $this->id,
                    'commission_id' => $comm->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2) Reading + UPA + SMS notification — 1 transaction เดียว (atomic)
        \Illuminate\Support\Facades\DB::transaction(function () use ($reason, $adminId, &$reverted) {
            // 2.1 UPA used → cancelled (ปลดล็อกยอด)
            if ($this->unique_payment_amount_id) {
                $upaAffected = UniquePaymentAmount::where('id', $this->unique_payment_amount_id)
                    ->where('status', 'used')
                    ->update(['status' => 'cancelled', 'matched_at' => null]);
                if ($upaAffected) {
                    $reverted[] = 'unique_payment_amount → cancelled';
                }
            }

            // 2.2 ปลด SMS notification ที่ผูกกับบิลนี้ (ให้เงินจริงไป match บิลถูกได้)
            $notifAffected = SmsPaymentNotification::where('matched_transaction_id', $this->id)
                ->update(['status' => 'pending', 'matched_transaction_id' => null]);
            if ($notifAffected) {
                $reverted[] = "ปลด SMS notification {$notifAffected} รายการ";
            }

            // 2.3 พลิกบิลกลับเป็น "ยังไม่จ่าย" + ปิด conversation
            //     (ไม่มี STATUS_CANCELLED แยก — ใช้ STATUS_COMPLETED + is_paid=false ตาม convention)
            $state = is_array($this->conversation_state) ? $this->conversation_state : [];
            $state['approval_voided'] = true;
            $state['approval_voided_at'] = now()->toIso8601String();
            $state['approval_void_reason'] = $reason;
            $state['approval_voided_by_admin_id'] = $adminId;
            // 🏷️ (2026-07-27) ระบุสาเหตุยกเลิกให้ชัด — เดิมบิลที่ void แล้วไม่มี cancellation_reason
            //   ทำให้ isCancelled() = false + แอพ/แอดมินขึ้น "ยกเลิก (ไม่ทราบสาเหตุ)"
            $state['cancellation_reason'] = 'approval_voided';
            // เคลียร์ flag ค้างที่ cron expire-stuck-paid ตั้งไว้ (ไม่งั้นโผล่ใน admin review ซ้ำ)
            $state['admin_review_needed'] = false;

            $this->update([
                'is_paid' => false,
                'paid_at' => null,
                'amount_received' => null,
                'sms_notification_id' => null,
                'conversation_status' => self::STATUS_COMPLETED,
                'conversation_state' => $state,
            ]);
            $reverted[] = 'is_paid → false (ปิดบิล)';
        });

        \Illuminate\Support\Facades\Log::warning('FortuneReading: ⛔ approval VOIDED by admin', [
            'reading_id' => $this->id,
            'bill_reference' => $this->bill_reference,
            'reading_type' => $this->reading_type,
            'admin_id' => $adminId,
            'reason' => $reason,
            'reverted' => $reverted,
            'warnings' => $warnings,
        ]);

        return ['ok' => true, 'reverted' => $reverted, 'warnings' => $warnings];
    }

    /**
     * 🔁 (2026-06-08) Reverse 1 commission row + ดึงเงินคืนจาก wallet
     *
     * mirror ของ FortuneCommissionService::depositToWallet (ทำกลับด้าน)
     * ⚠️ เรียกภายใน DB::transaction จาก voidApproval เท่านั้น
     */
    protected function reverseCommissionRow(FortuneCommission $comm): void
    {
        // ดึงเงินคืนจาก wallet (ถ้าเคยจ่ายเข้า wallet จริง)
        $wallet = Wallet::where('user_id', $comm->user_id)->first();
        if ($wallet) {
            $before = (float) ($wallet->balance ?? 0);
            $after = $before - (float) $comm->amount;

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'withdrawal',
                'amount' => (float) $comm->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $wallet->currency,
                'description' => "ดึงคืนค่าแนะนำดูดวง — บิล #{$this->id} ถูกยกเลิกการอนุมัติ",
                'reference_type' => FortuneCommission::class,
                'reference_id' => $comm->id,
                'status' => 'completed',
                'metadata' => [
                    'reading_id' => $this->id,
                    'mode' => 'fortune_commission_reversal',
                ],
                'completed_at' => now(),
            ]);

            if ($after < 0) {
                \Illuminate\Support\Facades\Log::warning('FortuneReading::reverseCommissionRow — wallet ติดลบหลังดึงคืน (ลูกค้าใช้เงินไปแล้ว)', [
                    'reading_id' => $this->id,
                    'wallet_id' => $wallet->id,
                    'balance_after' => $after,
                ]);
            }

            $wallet->update([
                'balance' => $after,
                'total_income' => max(0, (float) ($wallet->total_income ?? 0) - (float) $comm->amount),
                'last_transaction_at' => now(),
            ]);
        }

        // mark commission เป็น rejected (= ยกเลิก) + บันทึกเหตุผล
        $comm->update([
            'status' => FortuneCommission::STATUS_REJECTED,
            'notes' => trim((string) ($comm->notes ?? '')).' | ⛔ REVERSED: void approval บิล #'.$this->id,
        ]);
    }

    // ============================================================
    // Bill Reference Number
    // ============================================================

    /**
     * Boot method สำหรับ auto-generate bill_reference
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reading) {
            // สร้าง bill_reference เฉพาะ reading ที่เสียเงิน (deep + celtic_cross)
            // basic reading (ฟรี) ไม่ต้องมีเลขบิล
            $paidTypes = ['deep', 'celtic_cross'];
            if (empty($reading->bill_reference) && in_array($reading->reading_type, $paidTypes, true)) {
                $reading->bill_reference = self::generateBillReference();
            }
        });

        // 🚦 (2026-05-06) ล้าง active-reading cache เมื่อ status เปลี่ยน
        //   ใช้กัน "ปุ่ม QR ลอยหายช้า" หลัง flow จบ หรือเริ่มใหม่
        static::saved(function ($reading) {
            if (! $reading->isDirty('conversation_status')) {
                return;
            }

            if (! empty($reading->facebook_user_id)) {
                self::clearActiveReadingCache('facebook', $reading->facebook_user_id);
            }
            if (! empty($reading->line_user_id)) {
                self::clearActiveReadingCache('line', $reading->line_user_id);
            }
        });
    }

    /**
     * สร้างเลขที่บิลอ้างอิงที่ไม่ซ้ำกัน
     *
     * รูปแบบ: FTU-YYMMDD-AXXXX
     * - FTU = Fortune Reading
     * - YYMMDD = วันที่ (เช่น 260205)
     * - AXXXX = ตัวอักษร 1 ตัว + ลำดับ random 4 หลัก
     *
     * หมายเหตุ: ใช้ตัวอักษรนำหน้า random part เพื่อป้องกัน Facebook
     * detect ตัวเลข "YYMMDD-XXXXX" เป็นเลขบัญชีธนาคาร
     * (Facebook จะสร้าง Payment Card อัตโนมัติจากเลขบัญชีในข้อความ)
     */
    public static function generateBillReference(): string
    {
        $prefix = 'FTU';
        $datePart = now()->format('ymd');
        $maxAttempts = 10;

        // ตัวอักษรสำหรับนำหน้า random part (ไม่ใช้ I, O, L เพื่อไม่สับสนกับตัวเลข)
        $letters = 'ABCDEFGHJKMNPQRSTUVWXYZ';

        for ($i = 0; $i < $maxAttempts; $i++) {
            // สร้าง random: ตัวอักษร 1 ตัว + ตัวเลข 4 หลัก
            $letter = $letters[random_int(0, strlen($letters) - 1)];
            $numPart = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $reference = "{$prefix}-{$datePart}-{$letter}{$numPart}";

            // ตรวจสอบว่าซ้ำหรือไม่
            if (! self::where('bill_reference', $reference)->exists()) {
                return $reference;
            }
        }

        // Fallback: ใช้ microtime
        $uniquePart = substr(md5(microtime()), 0, 5);

        return "{$prefix}-{$datePart}-{$uniquePart}";
    }

    /**
     * ค้นหา reading จากเลขที่บิล
     */
    public static function findByBillReference(string $billReference): ?self
    {
        return self::where('bill_reference', $billReference)->first();
    }

    // ============================================================
    // Admin Takeover (ระบบเทคโอเวอร์)
    // ============================================================

    /**
     * เหตุผลการเทคโอเวอร์ที่ใช้ได้
     */
    public const TAKEOVER_REASON_MANUAL = 'manual';

    public const TAKEOVER_REASON_AUTO_REPLY = 'auto_reply';

    public const TAKEOVER_REASON_CUSTOMER_REQUEST = 'customer_request';

    /**
     * ความสัมพันธ์กับแอดมินที่เทคโอเวอร์
     */
    public function takeoverAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_takeover_by');
    }

    /**
     * ความสัมพันธ์กับ takeover logs
     */
    public function takeoverLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FortuneTakeoverLog::class, 'fortune_reading_id')
            ->latest();
    }

    /**
     * ตรวจสอบว่ากำลังถูกเทคโอเวอร์อยู่หรือไม่
     *
     * ใช้ DB เป็นแหล่งข้อมูลหลัก — Cache เป็น performance optimization เท่านั้น
     * AI ต้องเช็คผ่าน method นี้ก่อนตอบทุกครั้ง
     */
    public function isAdminTakenOver(): bool
    {
        if (empty($this->admin_takeover_until)) {
            return false;
        }

        return $this->admin_takeover_until->isFuture();
    }

    /**
     * ดึงเวลาที่เหลือของการเทคโอเวอร์ (วินาที)
     */
    public function takeoverRemainingSeconds(): int
    {
        if (! $this->isAdminTakenOver()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->admin_takeover_until, false));
    }

    /**
     * ดึงเวลาที่เหลือของการเทคโอเวอร์ (นาที) — ปัดขึ้น
     */
    public function takeoverRemainingMinutes(): int
    {
        $seconds = $this->takeoverRemainingSeconds();

        return $seconds > 0 ? (int) ceil($seconds / 60) : 0;
    }

    /**
     * Scope: conversations ที่กำลังถูกเทคโอเวอร์อยู่
     */
    public function scopeTakenOver($query)
    {
        return $query->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', now());
    }

    /**
     * Scope: conversations ที่ takeover หมดเวลาแล้ว (รอ cleanup)
     */
    public function scopeTakeoverExpired($query)
    {
        return $query->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '<=', now());
    }

    /**
     * ดึง identifier สำหรับ cache key (รวมทุก platform)
     *
     * ใช้ platform_user_id ถ้ามี, fallback เป็น facebook_user_id
     */
    public function getTakeoverCacheKey(): string
    {
        $platform = $this->platform ?? 'facebook';
        $userId = $this->platform_user_id ?: $this->facebook_user_id;

        return "fortune_admin_active:{$platform}:{$userId}";
    }

    // ════════════════════════════════════════════════════════════════
    // 🔮 Celtic Cross Tarot Mode helpers (2026-04-29)
    // ════════════════════════════════════════════════════════════════

    /**
     * relation: คำถาม-คำตอบทั้งหมดใน Celtic Cross reading
     */
    public function celticQuestions()
    {
        return $this->hasMany(FortuneCelticQuestion::class, 'fortune_reading_id')
            ->orderBy('sequence');
    }

    /**
     * เป็น Celtic Cross reading ไหม
     */
    public function isCelticCrossMode(): bool
    {
        return $this->reading_type === self::READING_TYPE_CELTIC_CROSS;
    }

    /**
     * เพิ่มไพ่ที่เลือกได้ใน Celtic Cross spread (เก็บใน conversation_state.celtic_cards)
     *
     * @param  int  $position  1-10
     * @param  int  $cardId  TarotCard ID
     */
    public function addCelticCard(
        int $position,
        int $cardId,
        string $cardNameTh,
        string $cardNameEn,
        bool $isReversed,
        string $meaning,
        ?string $imageUrl = null
    ): void {
        $cards = $this->getConversationState('celtic_cards', []);
        $cards[$position] = [
            'position' => $position,
            'position_name' => self::CELTIC_POSITIONS[$position]['name'] ?? '?',
            'position_description' => self::CELTIC_POSITIONS[$position]['description'] ?? '',
            'card_id' => $cardId,
            'card_name_th' => $cardNameTh,
            'card_name_en' => $cardNameEn,
            'is_reversed' => $isReversed,
            'meaning' => $meaning,
            'image_url' => $imageUrl,
            'picked_at' => now()->toIso8601String(),
        ];
        $this->setConversationState('celtic_cards', $cards);
    }

    /**
     * ดึงไพ่ Celtic ที่เลือกแล้วทั้งหมด (key = position 1-10)
     *
     * @return array<int, array>
     */
    public function getCelticCards(): array
    {
        $cards = $this->getConversationState('celtic_cards', []);

        // อาจเป็น associative array หรือ indexed array — normalize
        if (! is_array($cards)) {
            return [];
        }

        return $cards;
    }

    /**
     * จำนวนไพ่ที่เลือกแล้ว (0-10)
     */
    public function getCelticPickedCount(): int
    {
        return count($this->getCelticCards());
    }

    /**
     * Position ถัดไปที่ต้องเลือก (1-10) หรือ null ถ้าครบแล้ว
     */
    public function getNextCelticPosition(): ?int
    {
        $cards = $this->getCelticCards();
        for ($i = 1; $i <= 10; $i++) {
            if (! isset($cards[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * ดึง card_ids ของไพ่ที่เลือกแล้ว (ใช้ filter inRandomOrder ไม่ให้ซ้ำ)
     *
     * @return array<int>
     */
    public function getCelticPickedCardIds(): array
    {
        $cards = $this->getCelticCards();

        return array_values(array_map(fn ($c) => (int) ($c['card_id'] ?? 0), $cards));
    }

    /**
     * ลูกค้าสามารถถามคำถาม Celtic Cross ต่อได้ไหม
     *
     * 🌙 (2026-05-23 v3) บังคับ hard cap 5 คำถาม / 15 นาที — user spec
     *   "ถาม 5 คำถาม ภายใน 15 นาที และต้องบอกกติการให้ชัดทุกที่"
     *
     *   • Q1 ยังไม่ตอบ → ถามได้เสมอ
     *   • ครบ max_questions แล้ว → false (จะถูก endCelticSession ส่ง summary)
     *   • เลย window แล้ว → false (FortuneCelticAutoFinalize ส่ง grand_finale)
     */
    public function canAskMoreCeltic(): bool
    {
        $settings = FortuneTellingSetting::getSettings();
        $windowMin = (int) ($settings->celtic_cross_qa_window_minutes ?? 15);
        $maxQuestions = (int) ($settings->celtic_cross_max_questions ?? 0); // 0 = ไม่จำกัด (2026-06-07)

        // ยังไม่ได้ตอบ Q1 → ถามได้
        if (! $this->celtic_first_answered_at) {
            return true;
        }

        // 🛡️ (2026-05-21) Safety valve — ถ้า admin reset (celticQuestions ว่าง / counter=0)
        //   → ignore stale celtic_first_answered_at + allow asking
        if ($this->celtic_questions_used === 0 || $this->celticQuestions()->count() === 0) {
            return true;
        }

        // ⛔ (2026-05-23 v3) Max questions hard cap — เช็คก่อน window
        //   max=0 = unlimited (admin override) → ข้าม check นี้
        if ($maxQuestions > 0 && (int) $this->celtic_questions_used >= $maxQuestions) {
            return false;
        }

        // หลัง Q1 → เช็ค window (default 15 นาที)
        $deadline = $this->celtic_first_answered_at->copy()->addMinutes($windowMin);

        return now()->lessThanOrEqualTo($deadline);
    }

    /**
     * 🌙 (2026-05-23 v3) เหลือคำถามอีกกี่ครั้ง — สำหรับแสดง "เหลือ X คำถาม" ทุกข้อความ
     *
     * @return int|null null = unlimited (admin set 0) — caller ไม่ต้องแสดง
     */
    public function getCelticRemainingQuestions(): ?int
    {
        $settings = FortuneTellingSetting::getSettings();
        $maxQuestions = (int) ($settings->celtic_cross_max_questions ?? 0); // 0 = ไม่จำกัด (2026-06-07)

        if ($maxQuestions <= 0) {
            return null;
        }

        return max(0, $maxQuestions - (int) ($this->celtic_questions_used ?? 0));
    }

    /**
     * บันทึกว่าตอบ Celtic question ไปแล้ว
     * ถ้าเป็น Q1 → set celtic_first_answered_at (start QA window)
     */
    public function markCelticAnswered(int $sequence, bool $startQaWindow = true): void
    {
        // 🆕 (2026-06-23, owner) เริ่มจับเวลา QA window ที่ "คำถามจริงข้อแรกของลูกค้า" (Q2)
        //   ไม่ใช่ตอนพื้นดวงเปิดตัว (Q1 auto) — base chart ส่ง $startQaWindow=false (นับ used แต่ไม่เริ่มเวลา)
        //   เงื่อนไขเปลี่ยนจาก sequence===1 → empty() เพราะคำถามจริงข้อแรกอาจเป็น sequence 2 (หลังพื้นดวง)
        $justStarted = $startQaWindow && empty($this->celtic_first_answered_at);

        $update = [
            'celtic_questions_used' => max($this->celtic_questions_used, $sequence),
        ];

        if ($justStarted) {
            $update['celtic_first_answered_at'] = now();
        }

        $this->update($update);

        // 🆕 (2026-06-23) sync Pro Session avatar timer ให้เริ่มพร้อม QA window (เริ่มที่คำถามจริงข้อแรก)
        //   enterProSession เปิด session ค้างไว้ (awaiting) — ตั้ง started_at ตรงนี้เมื่อ window เริ่มจริง
        if ($justStarted && $this->getConversationState('pro_session_active')) {
            $this->setConversationState('pro_session_started_at', now()->toIso8601String());
            $this->setConversationState('pro_session_awaiting_first_question', false);
        }
    }

    /**
     * ตั้งค่า unique payment amount สำหรับ Celtic Cross + เปลี่ยนสถานะเป็น CELTIC_PENDING_PAYMENT
     *
     * แยกจาก setPendingPayment() เพราะอันนั้นบังคับ reading_type='deep'
     */
    public function setCelticPendingPayment(UniquePaymentAmount $uniqueAmount): void
    {
        $updateData = [
            'unique_payment_amount_id' => $uniqueAmount->id,
            'amount_paid' => $uniqueAmount->unique_amount,
            'conversation_status' => self::STATUS_CELTIC_PENDING_PAYMENT,
            'reading_type' => self::READING_TYPE_CELTIC_CROSS,
        ];

        if (empty($this->bill_reference)) {
            $updateData['bill_reference'] = self::generateBillReference();
        }

        $this->update($updateData);
    }

    /**
     * เวลาเหลือ (นาที) ใน QA window — null ถ้ายังไม่เริ่ม Q1
     */
    public function getCelticQaRemainingMinutes(): ?int
    {
        if (! $this->celtic_first_answered_at) {
            return null;
        }

        $settings = FortuneTellingSetting::getSettings();
        $windowMin = (int) ($settings->celtic_cross_qa_window_minutes ?? 15);
        $deadline = $this->celtic_first_answered_at->copy()->addMinutes($windowMin);

        if (now()->greaterThan($deadline)) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($deadline, false) / 60);
    }
}
