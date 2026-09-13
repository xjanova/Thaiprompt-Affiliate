<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🧾 (2026-09-13) ลูกค้าจ่ายช้ากว่าเวลาบิล — สลิปต้องไม่ถูกเก็บเงียบ
 *
 * เคสจริง Pantaree Donpar (FB 26273302092329161):
 *   12 ก.ย. 20:58 เปิดบิลดูดวง 39 (39.16) → "หนูไม่มีระบบโอนในโทรศัพท์ ต้องรอเช้าไปโอนร้านค้า"
 *   → บิลหมดเวลา 3 ชม. ถูกปิด (completed + is_paid=0)
 *   13 ก.ย. 12:35 ส่งรูปสลิป → ไม่มีบิลค้าง + ไม่มีธงรอสลิป → รูปถูกเก็บเงียบ ไม่มีใครตรวจ
 *
 * ฝั่ง Celtic มีด่านนี้มาตั้งแต่ 2026-06-01 — ดูดวง 39 ตกหล่นมาตลอด
 * เทสต์นี้ตรึงตัวตัดสิน findAbandonedUnpaidDeepBill() ที่ประตูรับรูปของ FB/LINE ใช้
 * + cache "มีบิลค้างไหม" ที่ต้องล้างทันทีเมื่อสถานะบิลเปลี่ยน
 *
 * @group fortune-slip
 */
class FortuneAbandonedDeepBillSlipTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '26273302092329161';

    private FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new FortuneConversationService(FortuneTellingSetting::getSettings());
    }

    /**
     * บิลดูดวง 39 ทรงเดียวกับ FTU-260912-Q1090 หลังถูกปิดเพราะหมดเวลา
     */
    private function makeDeepBill(array $overrides = []): FortuneReading
    {
        static $seq = 0;
        $seq++;

        return FortuneReading::create(array_merge([
            'facebook_user_id' => self::PSID,
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            // ⚠️ คอลัมน์ NOT NULL ไม่มี default — ไม่ใส่ = insert ตายที่ MySQL (error 1364)
            'questions' => ['ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'is_paid' => false,
            'amount_paid' => 39.16,
            'unique_payment_amount_id' => 3000 + $seq,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'bill_reference' => 'FTU-TEST-Q10'.$seq,
        ], $overrides));
    }

    public function test_expired_unpaid_deep_bill_is_found(): void
    {
        $bill = $this->makeDeepBill();

        $found = $this->service->findAbandonedUnpaidDeepBill(self::PSID);

        $this->assertNotNull($found, 'บิล 39 ออก QR แล้วยังไม่จ่าย (ปิดไปแล้ว) ต้องถูกนับว่า "อาจโอนตามมา"');
        $this->assertSame($bill->id, $found->id);
    }

    /**
     * จ่ายบิลใหม่ไปแล้ว → บิลเก่าหมดความหมาย
     * ไม่งั้นรูปทุกใบหลังได้ดวงแล้วจะถูกส่งเข้าตัวตรวจสลิปโดยใช่เหตุ
     */
    public function test_bill_paid_afterwards_cancels_the_signal(): void
    {
        $this->makeDeepBill();
        $this->makeDeepBill([
            'is_paid' => true,
            'paid_at' => now(),
            'amount_paid' => 39.60,
        ]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    /**
     * นั่งหน้าเมนูเลือกแพคเกจ (ยังไม่ออก QR) = ยังไม่เคยมียอดให้โอน → ไม่นับ
     * (เคสเดียวกัน บิล 13028 ของลูกค้าคนนี้)
     */
    public function test_bill_without_qr_amount_is_ignored(): void
    {
        $this->makeDeepBill(['unique_payment_amount_id' => null, 'amount_paid' => 0]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    /**
     * เกิน 3 วัน = สลิปเก่าเกินที่ SlipOK รับอยู่แล้ว (MAX_SLIP_AGE_DAYS) → ไม่นับ
     */
    public function test_bill_older_than_three_days_is_ignored(): void
    {
        $bill = $this->makeDeepBill();
        DB::table('fortune_readings')->where('id', $bill->id)->update(['created_at' => now()->subDays(4)]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    /**
     * Celtic มีทางของตัวเองอยู่แล้ว (findRecoverableCelticReading) — ตัวนี้ดูเฉพาะดูดวง 39
     */
    public function test_celtic_bill_is_left_to_its_own_path(): void
    {
        $this->makeDeepBill(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    public function test_other_customers_bills_do_not_count(): void
    {
        $this->makeDeepBill(['facebook_user_id' => '99999999999999999', 'platform_user_id' => '99999999999999999']);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    /**
     * 💳 cache "มีบิลค้างจ่ายไหม" (30 วิ) ต้องล้างทันทีที่บิลเข้าสถานะรอจ่าย
     *
     * เคสเดียวกัน 12:40:03 → 12:40:20: ค่าถูกคำนวณเป็น false ตอนข้อความ "เปิดบิล 39" เข้ามา
     * (ก่อนสร้างบิล) → 17 วิต่อมา "สาธุๆๆ" ยังอ่าน false ค้าง → ได้คำอวยพรลาก่อนทั้งที่มีบิลรอจ่าย
     */
    public function test_pending_bill_cache_is_cleared_when_bill_is_opened(): void
    {
        $reading = $this->makeDeepBill([
            'unique_payment_amount_id' => null,
            'amount_paid' => 0,
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
        ]);

        // ข้อความก่อนหน้าคำนวณ + จำค่า "ยังไม่มีบิล" ไว้
        $this->assertFalse($this->service->hasPendingUnpaidBill(self::PSID));

        // สร้างบิลจริง (ทางเดียวกับ createPaymentBill → setPendingPayment)
        $reading->update([
            'unique_payment_amount_id' => 3157,
            'amount_paid' => 39.60,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
        ]);

        $this->assertTrue(
            $this->service->hasPendingUnpaidBill(self::PSID),
            'สร้างบิลแล้ว ต้องไม่อ่านค่า "ไม่มีบิลค้าง" ที่จำไว้ก่อนหน้า'
        );
    }
}
