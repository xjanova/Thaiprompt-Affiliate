<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\UniquePaymentAmount;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 💎 (2026-09-16) ลูกค้าเปลี่ยนใจกลางคัน — เปิดบิล 39 แต่โอนมาเต็มราคา 99
 *
 * เคสจริง ศศิธร ศรีวงษา (FTU-260916-F3828 / reading 13277):
 *   เปิดบิล Deep ฿39.92 → ถามเรื่องแพคเกจ 99 สองครั้ง (เป็น "คำถาม" ตัวจับเปลี่ยนแพคเกจจึงไม่ยิง —
 *   ถูกแล้ว) → ส่งสลิป ฿99.00 → ด่านยอดของ SlipOK เช็คแค่ "≥ ขั้นต่ำ" → ตัดบิล 39 เป็นจ่ายแล้ว
 *   ผลคือจ่าย 99 ได้ของ 39 (amount_received=99.00 / amount_paid=39.92 / reading_type=deep)
 *
 * สิ่งที่ห้ามหลุด:
 *   1. บิล 39 + สลิปเต็ม 99 → ต้องกลายเป็น Celtic, amount_paid = เงินจริง, UPA ยอด 39 ถูกคืน
 *   2. โอนเกินนิดหน่อย (ทิป/ปัดเศษ) ยังไม่ถึง 99 → ห้ามอัปเกรด ต้องได้ของ 39 ตามที่สั่ง
 *   3. ลูกค้าเลือก 39 เอง "หลัง" เงินถึงมือแล้ว (prepay_package_locked) → คำพูดชนะ ห้ามอัปเกรดทับ
 *   4. บิลที่จ่ายแล้ว / มีคำทำนายออกไปแล้ว / Celtic ปิดขาย / มียอดสะสมค้าง → ห้ามแตะ
 *
 * @group fortune-slip
 */
class FortuneOverpaidSlipTierUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '28677665745184812';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // ⚠️ getSettings() มี static memo ข้ามเทสต์ใน process เดียวกัน (เทสต์ก่อนหน้า rollback แถวไปแล้ว)
        FortuneTellingSetting::clearSettingsCache();
    }

    /**
     * เปิดตัวบริการพร้อมตั้งราคา — schema default ของ deep_reading_price = 99 (prod ตั้ง 39)
     * ถ้าไม่ตั้งเอง เกณฑ์ "Celtic ต้องแพงกว่า Deep" จะเพี้ยนทั้งชุด
     */
    private function service(bool $celticEnabled = true): object
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->deep_reading_price = 39;
        $settings->celtic_cross_price = 99;
        $settings->enable_celtic_cross = $celticEnabled;
        $settings->save();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        return new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            /** เปิดเมธอด protected ให้เทสต์เรียกตรง — ทดสอบ "การตัดสิน" ไม่ใช่ทั้งเส้นตัดบิล */
            public function callUpgrade(FortuneReading $reading, array $verify): bool
            {
                return $this->tryUpgradeDeepToCelticFromOverpaidSlip($reading, $verify);
            }
        };
    }

    /**
     * สร้างบิล Deep รอจ่าย พร้อม UPA ยอดทศนิยม (39.92 แบบเคสจริง)
     */
    private function deepBill(array $overrides = []): FortuneReading
    {
        $reading = FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ศศิธร ศรีวงษา',
            'questions' => [],
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
        ], $overrides));

        $upa = UniquePaymentAmount::generate(39, $reading->id, 'fortune_reading', 180);
        $this->assertNotNull($upa, 'สร้าง UPA ยอด 39 ไม่สำเร็จ');
        $reading->forceFill([
            'unique_payment_amount_id' => $upa->id,
            'amount_paid' => $upa->unique_amount,
        ])->save();

        return $reading->fresh();
    }

    /** @return array<string, mixed> */
    private function verify(float $amount): array
    {
        return [
            'ok' => true,
            'amount' => $amount,
            'transRef' => '260916093304012948',
        ];
    }

    /** 1️⃣ เคสจริง: บิล 39.92 + สลิป 99.00 → อัปเกรดเป็น Celtic */
    public function test_บิล39_โอนมา99_อัปเกรดเป็นceltic(): void
    {
        $reading = $this->deepBill();
        $upaId = $reading->unique_payment_amount_id;

        $this->assertTrue($this->service()->callUpgrade($reading, $this->verify(99.00)));

        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::READING_TYPE_CELTIC_CROSS, $fresh->reading_type);
        // amount_paid ต้องเป็น "เงินจริงที่รับ" ไม่ใช่ยอดบิลเดิม 39.92 (ไม่งั้นรายงานยอดขายผิด)
        $this->assertEquals(99.00, (float) $fresh->amount_paid);
        // UPA ยอด 39 ต้องถูกคืน + ปลด FK (ไม่มีใครจ่ายยอดนั้นแล้ว)
        $this->assertNull($fresh->unique_payment_amount_id);
        $this->assertSame('cancelled', UniquePaymentAmount::find($upaId)?->status);
        // ร่องรอยให้แอดมินรู้ว่าแถวนี้ถูกอัปเกรดอัตโนมัติ ไม่ใช่บิล Celtic แต่แรก
        $this->assertSame('deep', $fresh->getConversationState('tier_upgraded_from'));
        $this->assertSame('overpaid_slip', $fresh->getConversationState('tier_upgraded_reason'));
        // ยังไม่ตัดบิลตรงนี้ — finalizeSlipOkApproved เป็นคนเรียก confirmPayment ต่อ
        $this->assertFalse((bool) $fresh->is_paid);
    }

    /** 2️⃣ โอนเกินแบบทิป/ปัดเศษ ยังไม่ถึงราคา Celtic → ห้ามอัปเกรด */
    public function test_โอนเกินเล็กน้อยไม่ถึง99_ไม่อัปเกรด(): void
    {
        foreach ([40.00, 50.00, 98.99] as $amount) {
            $reading = $this->deepBill();

            $this->assertFalse(
                $this->service()->callUpgrade($reading, $this->verify($amount)),
                "ยอด {$amount} ยังไม่ถึงราคา Celtic — ต้องได้ของ 39 ตามที่สั่ง"
            );
            $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
        }
    }

    /** 3️⃣ ลูกค้าเลือก 39 เองหลังเงินถึงมือแล้ว → คำพูดชนะ ห้ามอัปเกรดทับ */
    public function test_ลูกค้าเลือกdeepเองหลังจ่าย_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill();
        $reading->setConversationState('prepay_package_locked', 'deep');

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), $this->verify(99.00)));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }

    /** 4️⃣ บิลที่ตัดไปแล้ว → ห้ามแปลงทับของที่ส่งไปแล้ว (finalize ถูกเรียกซ้ำ/สลิปใบที่สอง) */
    public function test_บิลจ่ายแล้ว_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill();
        $reading->forceFill(['is_paid' => true, 'paid_at' => now()])->save();

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), $this->verify(99.00)));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }

    /** 5️⃣ มีคำทำนาย Deep ออกไปแล้ว → ห้ามแปลง (mirror guard ของ adminSwitchPackage) */
    public function test_มีคำทำนายแล้ว_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill(['ai_response' => 'คำทำนายที่ส่งให้ลูกค้าไปแล้ว']);

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), $this->verify(99.00)));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }

    /** 6️⃣ Celtic ปิดขาย → ไม่มีของให้อัปเกรด */
    public function test_celticปิดขาย_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill();

        $this->assertFalse($this->service(celticEnabled: false)->callUpgrade($reading, $this->verify(99.00)));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }

    /** 7️⃣ มียอดโอนสะสมค้างอยู่ → ปล่อยเลน top-up เดิมจัดการ (มี state machine ของตัวเอง) */
    public function test_มียอดสะสมค้าง_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill();
        $reading->forceFill(['partial_paid_total' => 20.00])->save();

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), $this->verify(99.00)));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }

    /** 8️⃣ บิล Celtic อยู่แล้ว → ไม่ต้องทำอะไร (กันวนอัปเกรดซ้ำ) */
    public function test_บิลcelticอยู่แล้ว_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]);

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), $this->verify(99.00)));
    }

    /** 9️⃣ สลิปไม่มียอด (SlipOK คืนมาไม่ครบ) → ห้ามเดา ต้องปล่อยเส้นเดิม */
    public function test_สลิปไม่มียอด_ไม่อัปเกรด(): void
    {
        $reading = $this->deepBill();

        $this->assertFalse($this->service()->callUpgrade($reading->fresh(), ['ok' => true, 'transRef' => 'X']));
        $this->assertSame(FortuneReading::READING_TYPE_DEEP, $reading->fresh()->reading_type);
    }
}
