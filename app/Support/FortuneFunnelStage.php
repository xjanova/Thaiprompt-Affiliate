<?php

namespace App\Support;

use App\Models\FortuneReading;

/**
 * 🔲 ขั้นของลูกค้าในกรวยขายดูดวง — ชุดเดียวกับ Warroom (แหล่งเดียวของหลังบ้าน)
 *
 * เจ้าของสั่ง (2026-09-13): "สถานะ Conversation ของรายละเอียดคำทำนายขั้นตอน ควรตรงกันกับ warroom"
 *
 * ปัญหาเดิม:
 *   - หน้ารายละเอียดคำทำนายมี timeline 8 ขั้นเขียนตายตัว (new → … → paid → completed)
 *     สถานะ Celtic / เลือกแพ็กเกจ / วิธีจ่าย / ไพ่ฟรี ไม่อยู่ในรายการ ⇒ ไม่มีขั้นไหนติด "ปัจจุบัน" เลย
 *   - บิลยกเลิก = completed + is_paid=0 (ไม่มีสถานะ cancelled แยก) ⇒ timeline เดิมติ๊ก "ชำระแล้ว" ✓
 *     ให้บิลที่ลูกค้าไม่ได้จ่าย
 *   - หน้ารายการ/หน้ารับช่วงแชทโชว์รหัสดิบ เช่น celtic_awaiting_question
 *     ขณะที่ Warroom เขียน "เลือกไพ่ · Celtic — เปิดไพ่ครบ · รอคำถามแรก"
 *
 * ต้นฉบับ: WarroomJuntra `src/lib/adapters/chat.ts` — STAGE_META · CS_STAGE · stageOfReading() · stageDetailOf()
 * ⚠️ แก้ฝั่งใดฝั่งหนึ่ง ต้องแก้อีกฝั่งให้ตรงกันเสมอ (ป้าย ไอคอน สี และตารางสถานะ)
 *    ข้อต่างเดียวที่ตั้งใจ: Warroom ยก "จ่ายแล้ว · รอคำทำนาย" ขึ้นเป็น "AI กำลังทำนาย" เมื่อเห็นงาน AI
 *    วิ่งอยู่ในคิวสด — หลังบ้านไม่มีข้อมูลคิวสด จึงแสดงตามสถานะในฐานข้อมูลอย่างเดียว
 */
final class FortuneFunnelStage
{
    /**
     * ป้าย / ไอคอน / สี ต่อขั้น — เรียงตามลำดับความสำคัญ (prio) ของ Warroom
     *
     * @var array<string, array{label: string, icon: string, color: string}>
     */
    public const META = [
        'predicting' => ['label' => 'AI กำลังทำนาย', 'icon' => '🔮', 'color' => '#8b5cf6'],
        'cancelled_user' => ['label' => 'ลูกค้ายกเลิกบิลเอง', 'icon' => '🚨', 'color' => '#ef4444'],
        'deciding' => ['label' => 'รอชำระเงิน', 'icon' => '💰', 'color' => '#f59e0b'],
        'celtic' => ['label' => 'เลือกไพ่ · Celtic', 'icon' => '🃏', 'color' => '#22d3ee'],
        'waiting' => ['label' => 'จ่ายแล้ว · รอคำทำนาย', 'icon' => '⏳', 'color' => '#f43f5e'],
        'upsell' => ['label' => 'ฟรีแล้ว · รอซื้อ 39/99', 'icon' => '🎁', 'color' => '#10b981'],
        'choosing' => ['label' => 'เลือกแพ็กเกจ/คุยเก็บข้อมูล', 'icon' => '🧭', 'color' => '#eab308'],
        'collecting' => ['label' => 'กำลังเก็บข้อมูล', 'icon' => '📝', 'color' => '#84cc16'],
        'intake' => ['label' => 'เริ่มสนทนาใหม่', 'icon' => '💬', 'color' => '#38bdf8'],
        'delivered' => ['label' => 'ส่งคำทำนายแล้ว', 'icon' => '✅', 'color' => '#64748b'],
        'declined' => ['label' => 'ปฏิเสธ upsell', 'icon' => '🙅', 'color' => '#94a3b8'],
        'cancelled_system' => ['label' => 'ยกเลิกโดยระบบ', 'icon' => '❌', 'color' => '#9ca3af'],
        'idle' => ['label' => 'อื่น ๆ', 'icon' => '💤', 'color' => '#6b7280'],
    ];

    /**
     * conversation_status → ขั้น (ตาราง CS_STAGE ของ Warroom ตัวต่อตัว)
     *
     * สถานะที่ไม่อยู่ในตาราง (เช่น 'cancelled' / 'expired' ที่ฟอร์มแก้ไขยอมให้ตั้ง แต่ระบบไม่ได้ใช้จริง)
     * ตกไปเดาจากรูปบิลใน of() — เหมือน Warroom
     *
     * @var array<string, string>
     */
    public const STATUS_TO_STAGE = [
        // 💬 ต้นกรวย — คุยอยู่ ยังไม่มีบิล
        FortuneReading::STATUS_NEW => 'intake',
        FortuneReading::STATUS_AWAITING_CONFIRMATION => 'intake',
        // 🧭 เลือกแพ็กเกจ / คุยเก็บเรื่อง (ยังไม่มีบิล — ไม่ใช่ "รอชำระ")
        FortuneReading::STATUS_TIER_CHOICE => 'choosing',
        FortuneReading::STATUS_DISCOVERY_CHAT => 'choosing',
        FortuneReading::STATUS_DISCOVERY_CONFIRM => 'choosing',
        // 📝 เก็บข้อมูล (collecting_tarot = จั่วไพ่ก่อนจ่ายแบบเก่า ไม่ใช่เลือกไพ่ Celtic ที่จ่ายแล้ว)
        FortuneReading::STATUS_COLLECTING_BIRTHDATE => 'collecting',
        FortuneReading::STATUS_COLLECTING_QUESTIONS => 'collecting',
        FortuneReading::STATUS_COLLECTING_TAROT => 'collecting',
        // 💰 ออกบิลแล้ว / เลือกวิธีจ่าย — ยังไม่จ่าย เงินค้างอยู่
        FortuneReading::STATUS_AWAITING_PAYMENT_METHOD => 'deciding',
        FortuneReading::STATUS_PENDING_PAYMENT => 'deciding',
        FortuneReading::STATUS_CELTIC_PENDING_PAYMENT => 'deciding',
        FortuneReading::STATUS_PENDING_STRIPE_PAYMENT => 'deciding',
        // ⏳ จ่ายแล้ว รอส่งคำทำนาย
        FortuneReading::STATUS_PAID => 'waiting',
        // 🃏 Celtic ที่จ่ายแล้ว
        FortuneReading::STATUS_CELTIC_PICKING => 'celtic',
        FortuneReading::STATUS_CELTIC_AWAITING_QUESTION => 'celtic',
        FortuneReading::STATUS_CELTIC_QA_PROMPT => 'celtic',
        FortuneReading::STATUS_CELTIC_GENERATING => 'predicting',
        // 🎁 ดูไพ่ฟรีแล้ว รอตัดสินใจซื้อ 39/99
        FortuneReading::STATUS_FREE_PREDICTED => 'upsell',
        // ✅/🙅 จบแล้ว (บิลยกเลิกก็เป็น completed — of() เช็คการยกเลิกก่อนตารางนี้)
        FortuneReading::STATUS_BASIC_DONE => 'delivered',
        FortuneReading::STATUS_COMPLETED => 'delivered',
        FortuneReading::STATUS_FREE_DECLINED => 'declined',
    ];

    /** เส้นทางบิล Celtic 99 — ใช้วาด timeline หน้ารายละเอียด */
    private const PATH_CELTIC = ['intake', 'choosing', 'deciding', 'celtic', 'predicting', 'delivered'];

    /** เส้นทางบิลเชิงลึก 39 แบบจ่ายก่อน (ปัจจุบัน) — จ่ายแล้วค่อยเก็บวันเกิด */
    private const PATH_DEEP_PAID = ['intake', 'choosing', 'deciding', 'collecting', 'waiting', 'predicting', 'delivered'];

    /** เส้นทางบิลเชิงลึกที่ยังไม่จ่าย — เก็บข้อมูลก่อนออกบิล (flow เก่า) */
    private const PATH_DEEP_UNPAID = ['intake', 'choosing', 'collecting', 'deciding', 'waiting', 'predicting', 'delivered'];

    /** เส้นทางไพ่ฟรี 1 ใบ */
    private const PATH_FREE = ['intake', 'upsell', 'delivered'];

    /** เส้นทางคำทำนายพื้นฐาน (แบบเก่า) */
    private const PATH_BASIC = ['intake', 'delivered'];

    /** ขั้นก่อนถึงจุดยกเลิก — บิลที่ถูกยกเลิกเดินมาถึง "รอชำระ" เสมอ */
    private const PATH_BEFORE_CANCEL = ['intake', 'choosing', 'deciding'];

    /**
     * ขั้นของบิลนี้ — ลำดับการตัดสินเหมือน stageOfReading() ของ Warroom
     */
    public static function of(FortuneReading $reading): string
    {
        // 1. การยกเลิกชนะทุกอย่าง — บิลยกเลิกคือ completed + is_paid=0 + มีเหตุผลยกเลิก
        //    ต้องเช็คก่อนตาราง ไม่งั้น completed จะกลายเป็น "ส่งคำทำนายแล้ว"
        if ($reading->isCancelled()) {
            return $reading->getConversationState('cancellation_reason') === 'user_cancelled'
                ? 'cancelled_user'
                : 'cancelled_system';
        }

        // 2. สถานะจริงจากฐานข้อมูล
        $status = strtolower((string) $reading->conversation_status);
        if ($status !== '' && isset(self::STATUS_TO_STAGE[$status])) {
            return self::STATUS_TO_STAGE[$status];
        }

        // 3. สถานะแปลก/ว่าง → เดาจากรูปบิล (ลำดับสำคัญ เหมือน Warroom)
        $paid = (bool) $reading->is_paid;
        $billed = (float) $reading->amount_paid > 0;
        $answered = ! empty($reading->ai_response);

        if (! $paid && $billed) {
            return 'deciding';
        }
        if (! $paid && $answered) {
            return 'upsell';
        }
        if ($paid && ! $answered) {
            return self::looksCeltic($reading) ? 'celtic' : 'waiting';
        }
        if ($paid && $answered) {
            return 'delivered';
        }
        if (! $paid && ! $billed && ! $answered) {
            return 'intake';
        }

        return 'idle';
    }

    /**
     * บรรทัดรายละเอียด "ลูกค้ากำลังทำอะไรอยู่" — เหมือน stageDetailOf() ของ Warroom
     *
     * คืน null เมื่อป้ายขั้นอย่างเดียวพอแล้ว
     */
    public static function detail(FortuneReading $reading): ?string
    {
        // บิลยกเลิก — เหตุผลยกเลิกบอกอยู่แล้ว (Warroom ขึ้นเป็นป้ายหมุด)
        if ($reading->isCancelled()) {
            return $reading->getCancellationReasonLabelOrNull();
        }

        $usedQ = (int) ($reading->celtic_questions_used ?? 0);

        return match (strtolower((string) $reading->conversation_status)) {
            FortuneReading::STATUS_CELTIC_PICKING => 'เปิดไพ่ '.$reading->getCelticPickedCount().'/10',
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION => $reading->getConversationState('celtic_birthdate_pending')
                ? 'เปิดไพ่ครบ · รอวันเกิด 🎂'
                : ($usedQ > 0 ? "ถามไป {$usedQ} · รอคำถามต่อ" : 'เปิดไพ่ครบ · รอคำถามแรก'),
            FortuneReading::STATUS_CELTIC_GENERATING => 'กำลังตอบคำถามที่ '.($usedQ + 1),
            FortuneReading::STATUS_CELTIC_QA_PROMPT => $usedQ > 0 ? "ตอบไป {$usedQ} คำถาม · ถามต่อไหม" : 'ถามต่อไหม',
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT => 'รอจ่าย 99 (Celtic)',
            FortuneReading::STATUS_PENDING_PAYMENT => 'รอจ่าย 39 (Deep)',
            FortuneReading::STATUS_AWAITING_PAYMENT_METHOD => 'เลือกวิธีจ่าย (QR/บัตร)',
            FortuneReading::STATUS_PENDING_STRIPE_PAYMENT => 'รอจ่ายผ่านบัตร (Stripe)',
            FortuneReading::STATUS_COLLECTING_BIRTHDATE => 'กำลังขอวันเกิด',
            FortuneReading::STATUS_COLLECTING_QUESTIONS => 'กำลังขอคำถาม',
            FortuneReading::STATUS_COLLECTING_TAROT => 'จั่วไพ่ก่อนจ่าย',
            FortuneReading::STATUS_TIER_CHOICE => 'กำลังเลือก 39/99',
            FortuneReading::STATUS_FREE_PREDICTED => 'ดูฟรีแล้ว · รอตัดสินใจซื้อ',
            default => null,
        };
    }

    /**
     * ป้าย/ไอคอน/สี ของขั้น — ขั้นที่ไม่รู้จักได้ของ "อื่น ๆ"
     *
     * @return array{label: string, icon: string, color: string}
     */
    public static function meta(string $stage): array
    {
        return self::META[$stage] ?? self::META['idle'];
    }

    /**
     * ป้ายพร้อมไอคอน เช่น "🃏 เลือกไพ่ · Celtic" — ใช้ในตาราง/ป้ายสั้น
     */
    public static function label(FortuneReading $reading): string
    {
        $meta = self::meta(self::of($reading));

        return $meta['icon'].' '.$meta['label'];
    }

    /**
     * ลำดับขั้นของบิลแบบนี้ — ใช้วาด timeline หน้ารายละเอียด (ขั้นปัจจุบันอยู่ในลิสต์เสมอ)
     *
     * ⚠️ ขั้นก่อนหน้าขั้นปัจจุบันคือ "ลำดับของกรวย" ไม่ใช่ประวัติจริงทีละก้าว
     *    เช่น ลูกค้าพิมพ์ราคาเองจะข้ามขั้นเลือกแพ็กเกจไปออกบิลเลย
     *
     * @return array<int, string>
     */
    public static function path(FortuneReading $reading): array
    {
        $stage = self::of($reading);

        if ($stage === 'cancelled_user' || $stage === 'cancelled_system') {
            return [...self::PATH_BEFORE_CANCEL, $stage];
        }

        $path = match (true) {
            $reading->reading_type === FortuneReading::READING_TYPE_FREE_CARD,
            $stage === 'upsell',
            $stage === 'declined' => $stage === 'declined'
                ? ['intake', 'upsell', 'declined']
                : self::PATH_FREE,
            self::looksCeltic($reading),
            $stage === 'celtic' => self::PATH_CELTIC,
            $reading->reading_type === FortuneReading::READING_TYPE_BASIC => self::PATH_BASIC,
            (bool) $reading->is_paid => self::PATH_DEEP_PAID,
            default => self::PATH_DEEP_UNPAID,
        };

        // ขั้นที่ไม่อยู่ในเส้นทางของบิลแบบนี้ (เช่น สถานะแปลก → อื่น ๆ) ต่อท้ายไว้ ห้ามหายจากจอ
        if (! in_array($stage, $path, true)) {
            $path[] = $stage;
        }

        return $path;
    }

    /**
     * บิล Celtic หรือไม่ — ดูทั้งประเภทบิลและสถานะ (บิลเก่าบางใบ reading_type ไม่ตรง)
     */
    private static function looksCeltic(FortuneReading $reading): bool
    {
        return str_contains(strtolower((string) $reading->reading_type), 'celtic')
            || str_contains(strtolower((string) $reading->response_type), 'celtic')
            || str_starts_with(strtolower((string) $reading->conversation_status), 'celtic_');
    }
}
