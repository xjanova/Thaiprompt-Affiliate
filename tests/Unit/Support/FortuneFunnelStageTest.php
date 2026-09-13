<?php

namespace Tests\Unit\Support;

use App\Models\FortuneReading;
use App\Support\FortuneFunnelStage;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

/**
 * 🔲 ขั้นในกรวยของหลังบ้าน ต้องตรงกับ Warroom (2026-09-13)
 *
 * เจ้าของสั่ง: "สถานะ Conversation ของรายละเอียดคำทำนายขั้นตอน ควรตรงกันกับ warroom"
 * ต้นฉบับฝั่ง Warroom: WarroomJuntra src/lib/adapters/chat.ts (STAGE_META · CS_STAGE · stageOfReading · stageDetailOf)
 *
 * ไม่แตะ DB — สร้าง FortuneReading ในหน่วยความจำอย่างเดียว
 */
class FortuneFunnelStageTest extends TestCase
{
    /** สร้างบิลในหน่วยความจำ (ไม่บันทึก) */
    private function reading(array $attributes): FortuneReading
    {
        $reading = new FortuneReading;
        $reading->forceFill(array_merge([
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_NEW,
            'is_paid' => false,
            'amount_paid' => 0,
            'conversation_state' => [],
        ], $attributes));

        return $reading;
    }

    /**
     * สถานะทุกตัวในโมเดลต้องมีขั้นของตัวเอง — เพิ่มสถานะใหม่แล้วลืมแปลง = ตกไป "อื่น ๆ" เงียบ ๆ ทั้งสองระบบ
     * ⇒ เทสต์นี้บังคับให้เพิ่มลงตาราง (และไปเพิ่มใน Warroom chat.ts ด้วย)
     */
    #[Test]
    public function every_model_status_constant_has_a_stage(): void
    {
        $constants = (new ReflectionClass(FortuneReading::class))->getConstants();
        $statuses = array_filter($constants, fn ($v, $k) => str_starts_with($k, 'STATUS_') && is_string($v), ARRAY_FILTER_USE_BOTH);

        $this->assertNotEmpty($statuses);
        foreach ($statuses as $name => $status) {
            $this->assertArrayHasKey($status, FortuneFunnelStage::STATUS_TO_STAGE, "FortuneReading::{$name} ('{$status}') ยังไม่มีขั้นใน FortuneFunnelStage");
            $this->assertArrayHasKey(FortuneFunnelStage::STATUS_TO_STAGE[$status], FortuneFunnelStage::META);
        }
    }

    /**
     * ป้าย 13 ขั้นต้องเหมือน STAGE_META ของ Warroom ทุกตัวอักษร — คนดูสองจอต้องเห็นคำเดียวกัน
     */
    #[Test]
    public function stage_labels_match_warroom(): void
    {
        $warroom = [
            'predicting' => '🔮 AI กำลังทำนาย',
            'cancelled_user' => '🚨 ลูกค้ายกเลิกบิลเอง',
            'deciding' => '💰 รอชำระเงิน',
            'celtic' => '🃏 เลือกไพ่ · Celtic',
            'waiting' => '⏳ จ่ายแล้ว · รอคำทำนาย',
            'upsell' => '🎁 ฟรีแล้ว · รอซื้อ 39/99',
            'choosing' => '🧭 เลือกแพ็กเกจ/คุยเก็บข้อมูล',
            'collecting' => '📝 กำลังเก็บข้อมูล',
            'intake' => '💬 เริ่มสนทนาใหม่',
            'delivered' => '✅ ส่งคำทำนายแล้ว',
            'declined' => '🙅 ปฏิเสธ upsell',
            'cancelled_system' => '❌ ยกเลิกโดยระบบ',
            'idle' => '💤 อื่น ๆ',
        ];

        $ours = array_map(fn ($m) => $m['icon'].' '.$m['label'], FortuneFunnelStage::META);
        $this->assertSame($warroom, $ours);
    }

    /**
     * 🚨 บิลยกเลิก = completed + ไม่จ่าย — ต้องขึ้นว่ายกเลิก ไม่ใช่ "ส่งคำทำนายแล้ว"
     *    และ timeline ต้องจบที่จุดยกเลิก ไม่มีขั้น "จ่ายแล้ว" ผ่านมาก่อน (timeline เดิมติ๊กชำระแล้ว ✓)
     */
    #[Test]
    public function cancelled_bill_is_cancelled_not_delivered(): void
    {
        $byCustomer = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'amount_paid' => 99,
            'conversation_state' => ['cancellation_reason' => 'user_cancelled'],
        ]);
        $this->assertSame('cancelled_user', FortuneFunnelStage::of($byCustomer));
        $this->assertNull(FortuneFunnelStage::detail($byCustomer), 'ป้ายขั้นบอกแล้วว่าลูกค้ายกเลิกเอง ไม่ต้องพูดซ้ำ');
        $this->assertSame(['intake', 'choosing', 'deciding', 'cancelled_user'], FortuneFunnelStage::path($byCustomer));

        $bySystem = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'amount_paid' => 39,
            'conversation_state' => ['cancellation_reason' => 'auto_expired'],
        ]);
        $this->assertSame('cancelled_system', FortuneFunnelStage::of($bySystem));
        $this->assertNull(FortuneFunnelStage::detail($bySystem), 'หมดเวลา = "ยกเลิกโดยระบบ" ซ้ำกับป้ายขั้น');
        $this->assertNotContains('waiting', FortuneFunnelStage::path($bySystem));

        // เหตุผลที่ป้ายขั้นไม่ได้บอก ต้องขึ้นเป็นรายละเอียด — แอดมินต้องรู้ว่าทำไมกดอนุมัติไม่ได้
        $voided = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'amount_paid' => 99,
            'conversation_state' => ['cancellation_reason' => 'approval_voided'],
        ]);
        $this->assertSame('cancelled_system', FortuneFunnelStage::of($voided));
        $this->assertSame('ยกเลิกการอนุมัติโดยแอดมิน', FortuneFunnelStage::detail($voided));

        // จ่ายแล้วมีเหตุผลยกเลิกค้างใน state = ไม่ใช่บิลยกเลิก (isCancelled เช็คการจ่ายก่อน)
        $paid = $this->reading([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true,
            'amount_paid' => 99,
            'conversation_state' => ['cancellation_reason' => 'user_cancelled'],
        ]);
        $this->assertSame('delivered', FortuneFunnelStage::of($paid));
    }

    /**
     * 🔎 ตัวกรองหน้ารายการ: ทุกสถานะต้องมีให้เลือกครั้งเดียว มีชื่อไทย + มีตัวเลือกบิลยกเลิก
     *    (เดิมเป็นลิสต์เขียนมือ ขาด tier_choice / discovery_* / awaiting_confirmation)
     */
    #[Test]
    public function filter_options_cover_every_status_once_with_thai_names(): void
    {
        $values = [];
        foreach (FortuneFunnelStage::filterOptions() as $group) {
            $this->assertNotEmpty($group['options'], $group['label']);
            foreach ($group['options'] as $value => $name) {
                $values[] = $value;
                $this->assertNotSame($value, $name, "สถานะ {$value} ยังไม่มีชื่อไทย");
            }
        }

        $this->assertSame(count($values), count(array_unique($values)), 'ตัวเลือกซ้ำ');
        $expected = [...array_keys(FortuneFunnelStage::STATUS_TO_STAGE), ...array_keys(FortuneFunnelStage::CANCELLED_FILTERS)];
        sort($expected);
        sort($values);
        $this->assertSame($expected, $values);
        $this->assertSame([], array_diff(array_keys(FortuneFunnelStage::STATUS_NAMES), array_keys(FortuneFunnelStage::STATUS_TO_STAGE)));
    }

    /**
     * เหตุผลยกเลิกที่ระบบเขียนจริง ต้องมีป้ายของตัวเอง ไม่ตกไป "ไม่ทราบสาเหตุ"
     */
    #[Test]
    public function real_cancellation_reasons_have_labels(): void
    {
        foreach (['user_rejected_question', 'package_switch', 'provisional_replaced_by_deep'] as $reason) {
            $this->assertNotSame('ยกเลิก (ไม่ทราบสาเหตุ)', FortuneReading::getCancellationReasonLabel($reason), $reason);

            $r = $this->reading([
                'conversation_status' => FortuneReading::STATUS_COMPLETED,
                'amount_paid' => 39,
                'conversation_state' => ['cancellation_reason' => $reason],
            ]);
            $this->assertSame(FortuneReading::getCancellationReasonLabel($reason), FortuneFunnelStage::detail($r));
        }
    }

    /**
     * 🃏 สถานะ Celtic ทุกตัวต้องติดขั้นปัจจุบันบน timeline + บรรทัดรายละเอียดเหมือน Warroom
     */
    #[Test]
    public function celtic_statuses_show_warroom_details(): void
    {
        $picking = $this->reading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            'is_paid' => true,
            'conversation_state' => ['celtic_cards' => [1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd']],
        ]);
        $this->assertSame('celtic', FortuneFunnelStage::of($picking));
        $this->assertSame('เปิดไพ่ 4/10', FortuneFunnelStage::detail($picking));
        $this->assertSame(['intake', 'choosing', 'deciding', 'celtic', 'predicting', 'delivered'], FortuneFunnelStage::path($picking));

        $birthdate = $this->reading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'is_paid' => true,
            'conversation_state' => ['celtic_birthdate_pending' => true],
        ]);
        $this->assertSame('เปิดไพ่ครบ · รอวันเกิด 🎂', FortuneFunnelStage::detail($birthdate));

        $asked = $this->reading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'is_paid' => true,
            'celtic_questions_used' => 2,
        ]);
        $this->assertSame('ถามไป 2 · รอคำถามต่อ', FortuneFunnelStage::detail($asked));

        $generating = $this->reading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING,
            'is_paid' => true,
            'celtic_questions_used' => 2,
        ]);
        $this->assertSame('predicting', FortuneFunnelStage::of($generating));
        $this->assertSame('กำลังตอบคำถามที่ 3', FortuneFunnelStage::detail($generating));

        $unpaid = $this->reading([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            'amount_paid' => 99,
        ]);
        $this->assertSame('deciding', FortuneFunnelStage::of($unpaid));
        $this->assertSame('รอจ่าย 99 (Celtic)', FortuneFunnelStage::detail($unpaid));
    }

    /**
     * สถานะที่ไม่อยู่ในตาราง ('expired' ของโค้ดเก่า / ค่าแปลกจากฟอร์มแก้ไข) → เดาจากรูปบิลเหมือน Warroom
     */
    #[Test]
    public function unknown_status_falls_back_to_bill_shape(): void
    {
        $this->assertSame('deciding', FortuneFunnelStage::of($this->reading(['conversation_status' => 'expired', 'amount_paid' => 39])));
        $this->assertSame('upsell', FortuneFunnelStage::of($this->reading(['conversation_status' => 'mystery', 'ai_response' => 'ไพ่ฟรี'])));
        $this->assertSame('waiting', FortuneFunnelStage::of($this->reading(['conversation_status' => 'expired', 'is_paid' => true, 'amount_paid' => 39])));
        $this->assertSame('celtic', FortuneFunnelStage::of($this->reading(['conversation_status' => 'mystery', 'is_paid' => true, 'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS])));
        $this->assertSame('delivered', FortuneFunnelStage::of($this->reading(['conversation_status' => 'expired', 'is_paid' => true, 'ai_response' => 'คำทำนาย'])));
        $this->assertSame('intake', FortuneFunnelStage::of($this->reading(['conversation_status' => 'mystery'])));
    }

    /**
     * 🚫 แอป SMS Checker กดปฏิเสธบิล → เขียนสถานะดิบ 'cancelled' (ยังเขียนอยู่ prod มี 45 ใบ)
     *    เดิมตกไปเดาจากรูปบิลได้ "รอชำระเงิน" ทั้งที่แอดมินปฏิเสธไปแล้ว
     */
    #[Test]
    public function bill_rejected_from_sms_checker_is_cancelled(): void
    {
        $rejected = $this->reading(['conversation_status' => FortuneFunnelStage::REJECTED_STATUS, 'amount_paid' => 39]);

        $this->assertSame('cancelled_system', FortuneFunnelStage::of($rejected));
        $this->assertSame('ปฏิเสธบิลจากแอป SMS Checker', FortuneFunnelStage::detail($rejected));
        $this->assertSame(['intake', 'choosing', 'deciding', 'cancelled_system'], FortuneFunnelStage::path($rejected));
    }

    /**
     * ขั้นปัจจุบันต้องอยู่บน timeline เสมอ — ไม่งั้นหน้ารายละเอียดไม่มีขั้นไหนติด "ปัจจุบัน" (อาการเดิม)
     */
    #[Test]
    public function current_stage_is_always_on_the_path(): void
    {
        $types = [FortuneReading::READING_TYPE_DEEP, FortuneReading::READING_TYPE_CELTIC_CROSS, FortuneReading::READING_TYPE_FREE_CARD, FortuneReading::READING_TYPE_BASIC];

        foreach (array_keys(FortuneFunnelStage::STATUS_TO_STAGE) as $status) {
            foreach ($types as $type) {
                foreach ([false, true] as $paid) {
                    $r = $this->reading(['conversation_status' => $status, 'reading_type' => $type, 'is_paid' => $paid]);
                    $this->assertContains(
                        FortuneFunnelStage::of($r),
                        FortuneFunnelStage::path($r),
                        "{$type} · {$status} · ".($paid ? 'จ่ายแล้ว' : 'ยังไม่จ่าย')
                    );
                }
            }
        }

        // เชิงลึกจ่ายก่อน: เก็บวันเกิดอยู่หลังรอชำระ · ยังไม่จ่าย: เก็บข้อมูลก่อนออกบิล
        $paidCollecting = $this->reading(['conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE, 'is_paid' => true]);
        $path = FortuneFunnelStage::path($paidCollecting);
        $this->assertLessThan(array_search('collecting', $path, true), array_search('deciding', $path, true));

        $free = $this->reading(['conversation_status' => FortuneReading::STATUS_FREE_DECLINED, 'reading_type' => FortuneReading::READING_TYPE_FREE_CARD]);
        $this->assertSame(['intake', 'upsell', 'declined'], FortuneFunnelStage::path($free));
    }
}
