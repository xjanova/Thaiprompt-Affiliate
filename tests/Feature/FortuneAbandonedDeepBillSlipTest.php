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
 * 🧾 (2026-09-13) ลูกค้าจ่ายช้ากว่าเวลาบิล แล้วแจ้งโอนด้วยสำนวน "โอนให้แล้ว"
 *
 * เคสจริง Pantaree Donpar (FB 26273302092329161):
 *   12 ก.ย. 20:58 เปิดบิลดูดวง 39 (39.16) → "หนูไม่มีระบบโอนในโทรศัพท์ ต้องรอเช้าไปโอนร้านค้า"
 *   → บิลหมดเวลา 3 ชม. ถูกปิด (completed + is_paid=0)
 *   13 ก.ย. 12:35 ส่งรูปสลิป (เก็บเงียบ) + พิมพ์ "หนูโอนให้แล้วนะคะ" → ตัวจับไม่ติด → แอดมินตัดมือ
 *
 * สำนวนหลวมรับเฉพาะเมื่อมี "ร่องรอยการจ่าย" (hasRecentPaymentContext) — เรื่องเล่า
 * "พ่อจ่ายให้แล้วค่ะ" จากคนที่ไม่มีบิลเลย ต้องไม่โดนทวงสลิป
 *
 * เทสต์นี้ตรึง:
 *   1. findAbandonedUnpaidDeepBill() — ตัวบอก "มีบิล 39 ออก QR แล้วยังไม่จ่าย"
 *   2. tryReturningPaidSlipCheck() — สำนวนหลวม + ร่องรอย = แจ้งโอน · ไม่มีร่องรอย = ปล่อยคุยปกติ
 *   3. cache "มีบิลค้างไหม" ต้องล้างทันทีเมื่อสถานะบิลเปลี่ยน
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

    /** เปิด SlipOK (ทาง cold path คืน null ทันทีถ้าปิด) */
    private function enableSlipOk(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->enable_slipok_verify = true;
        $settings->slipok_branch_id = 'test-branch';
        $settings->slipok_api_key = 'test-key';
        $settings->save();
        Cache::flush();

        $this->service = new FortuneConversationService($settings->fresh());
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

    // ── 1. ตัวบอกบิลค้างจ่าย ─────────────────────────────────────────────

    public function test_expired_unpaid_deep_bill_is_found(): void
    {
        $bill = $this->makeDeepBill();

        $found = $this->service->findAbandonedUnpaidDeepBill(self::PSID);

        $this->assertNotNull($found, 'บิล 39 ออก QR แล้วยังไม่จ่าย (ปิดไปแล้ว) ต้องถูกนับว่า "อาจโอนตามมา"');
        $this->assertSame($bill->id, $found->id);
    }

    /** จ่ายบิลใหม่ไปแล้ว → บิลเก่าหมดความหมาย */
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
     * บิลซ้อน: จ่ายใบแรก (id ต่ำกว่า) แล้วใบที่สองถูกยกเลิก
     * → ใบที่สองต้องไม่ถูกนับว่าค้าง (นับการจ่ายจากเวลา ไม่ใช่ลำดับ id)
     */
    public function test_earlier_bill_paid_after_this_one_was_opened_cancels_the_signal(): void
    {
        $this->makeDeepBill(['is_paid' => true, 'paid_at' => now(), 'amount_paid' => 39.30]);
        $second = $this->makeDeepBill();
        DB::table('fortune_readings')->where('id', $second->id)->update(['created_at' => now()->subMinutes(10)]);

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

    /** เกิน 3 วัน = สลิปเก่าเกินที่ SlipOK รับอยู่แล้ว (MAX_SLIP_AGE_DAYS) → ไม่นับ */
    public function test_bill_older_than_three_days_is_ignored(): void
    {
        $bill = $this->makeDeepBill();
        DB::table('fortune_readings')->where('id', $bill->id)->update(['created_at' => now()->subDays(4)]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    /** Celtic มีตัวของตัวเอง (findRecoverableCelticReading) — ตัวนี้ดูเฉพาะดูดวง 39 */
    public function test_celtic_bill_is_left_to_its_own_helper(): void
    {
        $this->makeDeepBill(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS]);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    public function test_other_customers_bills_do_not_count(): void
    {
        $this->makeDeepBill(['facebook_user_id' => '99999999999999999', 'platform_user_id' => '99999999999999999']);

        $this->assertNull($this->service->findAbandonedUnpaidDeepBill(self::PSID));
    }

    // ── 2. แจ้งโอนสำนวนหลวม ต้องมีร่องรอยการจ่าย ──────────────────────────

    /** เคสจริง: บิล 39 เมื่อวานหมดเวลา + "หนูโอนให้แล้วนะคะ" → ต้องเข้าเส้นตรวจสลิป ไม่ไหลไป AI แชท */
    public function test_loose_claim_with_expired_bill_reaches_slip_check(): void
    {
        $this->enableSlipOk();
        $this->makeDeepBill();

        $resp = $this->service->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ');

        $this->assertNotNull($resp, 'มีบิลค้างจ่าย + บอกว่าโอนให้แล้ว ต้องไม่ถูกปล่อยไป AI แชท');
        $this->assertSame('slipok_ask_slip', $resp['action'] ?? null);
    }

    /** รูปที่เพิ่งส่งมาแล้วถูกเก็บไว้ = ร่องรอยการจ่ายด้วย (ลำดับเดียวกับเคสจริง: รูปก่อน ข้อความทีหลัง) */
    public function test_loose_claim_with_stashed_image_reaches_slip_check(): void
    {
        $this->enableSlipOk();
        Cache::put('fortune:pending_slip:facebook:'.self::PSID, 'fortune/slips/pend_test_missing.jpg', now()->addMinutes(30));

        $this->assertNotNull(
            $this->service->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ'),
            'เพิ่งส่งรูปมา + บอกว่าโอนให้แล้ว ต้องเข้าเส้นตรวจสลิป'
        );
    }

    /** ไม่มีบิล ไม่มีรูป ไม่มีธง → สำนวนหลวมอย่างเดียวไม่พอ ปล่อยคุยปกติ */
    public function test_loose_claim_without_any_payment_trace_is_left_alone(): void
    {
        $this->enableSlipOk();

        $this->assertNull($this->service->tryReturningPaidSlipCheck('facebook', self::PSID, 'หนูโอนให้แล้วนะคะ'));
        $this->assertNull(
            $this->service->tryReturningPaidSlipCheck('facebook', self::PSID, 'พ่อจ่ายให้แล้วค่ะ'),
            'เรื่องเล่า (พ่อจ่ายค่าเทอมให้) จากคนที่ไม่มีบิลเลย ต้องไม่โดนทวงสลิป'
        );
    }

    /** ตัวควบคุม: สำนวนเข้มเดิม ("โอนแล้ว") ยังทำงานเหมือนเดิมแม้ไม่มีร่องรอย */
    public function test_strict_claim_still_works_without_trace(): void
    {
        $this->enableSlipOk();

        $this->assertNotNull($this->service->tryReturningPaidSlipCheck('facebook', self::PSID, 'โอนแล้วค่ะ'));
    }

    // ── 3. cache "มีบิลค้างไหม" ─────────────────────────────────────────

    /**
     * 💳 cache (30 วิ) ต้องล้างทันทีที่บิลเข้าสถานะรอจ่าย
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
