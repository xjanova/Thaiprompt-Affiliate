<?php

namespace Tests\Feature;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ทดสอบระบบคอมมิชชั่นดูดวง Level 1 + Level 2
 *
 * ครอบคลุม:
 * - จ่าย Level 1 (สายตรง) สำเร็จ
 * - จ่าย Level 2 (หลาน) สำเร็จ
 * - คำนวณแบบ fixed amount
 * - คำนวณแบบ percent
 * - ไม่จ่าย Level 2 เมื่อปิดระบบ
 * - ป้องกันจ่ายซ้ำ
 * - ไม่มี sponsor ไม่ได้ commission
 * - Wallet balance เพิ่มขึ้นถูกต้อง
 */
class FortuneReferralCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected FortuneCommissionService $service;

    protected FortuneTellingSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FortuneCommissionService;

        // สร้าง settings เริ่มต้น: Level 1 fixed 10 บาท, Level 2 fixed 5 บาท
        $this->settings = FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app',
            'facebook_page_id' => 'test-page',
            'is_enabled' => true,
            'deep_reading_price' => 99,
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 5,
        ]);
    }

    /**
     * ทดสอบ Level 1 commission จ่ายให้ sponsor ตรง
     */
    public function test_level1_commission_created_when_reading_paid(): void
    {
        // Arrange: สร้าง A (sponsor) → B (member)
        [$sponsorUser, $sponsorMember] = $this->createMlmMember();
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        // Act: จ่ายคอมมิชชั่น
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // Assert: มี record Level 1 สำหรับ sponsor
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'from_user_id' => $buyerUser->id,
            'level' => 1,
            'commission_type' => 'fixed',
            'amount' => '10.00',
            'status' => FortuneCommission::STATUS_PAID,
        ]);

        // ต้องมี 1 record เท่านั้น (Level 1 only ถ้า sponsor ไม่มี grandparent)
        $this->assertEquals(1, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    /**
     * ทดสอบ Level 2 commission จ่ายให้ grandparent
     */
    public function test_level2_commission_created_for_grandparent(): void
    {
        // Arrange: สร้าง A (grandparent) → B (sponsor) → C (buyer)
        [$grandparentUser, $grandparentMember] = $this->createMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createMlmMember(sponsorId: $grandparentMember->id);
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        // Act
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // Assert: Level 1 → sponsorUser
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'level' => 1,
            'amount' => '10.00',
        ]);

        // Assert: Level 2 → grandparentUser
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $grandparentUser->id,
            'level' => 2,
            'amount' => '5.00',
        ]);

        // ต้องมี 2 records (Level 1 + Level 2)
        $this->assertEquals(2, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    /**
     * ทดสอบคำนวณแบบ fixed amount
     */
    public function test_fixed_amount_calculation(): void
    {
        // ตั้ง fixed 15 บาท
        $this->settings->update([
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 15,
        ]);

        [$sponsorUser, $sponsorMember] = $this->createMlmMember();
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        // ราคา 99 บาท → fixed 15 → ได้ 15 บาทเสมอ
        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $commission = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $this->assertNotNull($commission);
        $this->assertEquals('15.00', $commission->amount);
        $this->assertEquals('fixed', $commission->commission_type);
    }

    /**
     * ทดสอบคำนวณแบบ percent
     */
    public function test_percent_calculation(): void
    {
        // ตั้ง percent 10% → 99 × 10% = 9.90
        $this->settings->update([
            'fortune_level1_commission_type' => 'percent',
            'fortune_level1_commission_amount' => 10,
        ]);

        [$sponsorUser, $sponsorMember] = $this->createMlmMember();
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $commission = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $this->assertNotNull($commission);
        $this->assertEquals('9.90', $commission->amount);
        $this->assertEquals('percent', $commission->commission_type);
    }

    /**
     * ทดสอบไม่จ่าย Level 2 เมื่อปิดระบบ
     */
    public function test_no_level2_when_disabled(): void
    {
        // ปิด Level 2
        $this->settings->update(['fortune_level2_enabled' => false]);

        [$grandparentUser, $grandparentMember] = $this->createMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createMlmMember(sponsorId: $grandparentMember->id);
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // Level 1 ต้องมี
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 1,
        ]);

        // Level 2 ต้องไม่มี
        $this->assertDatabaseMissing('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 2,
        ]);
    }

    /**
     * ทดสอบป้องกันจ่ายซ้ำ (duplicate commission)
     */
    public function test_prevents_duplicate_commission(): void
    {
        [$sponsorUser, $sponsorMember] = $this->createMlmMember();
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        // จ่ายครั้งแรก
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);
        $countAfterFirst = FortuneCommission::where('fortune_reading_id', $reading->id)->count();

        // จ่ายครั้งที่สอง (ควรข้าม)
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);
        $countAfterSecond = FortuneCommission::where('fortune_reading_id', $reading->id)->count();

        // จำนวน record ต้องเท่ากัน
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    /**
     * ทดสอบ user ไม่มี sponsor ไม่ได้ commission
     */
    public function test_no_commission_for_orphan_user(): void
    {
        // สร้าง member ที่ไม่มี sponsor
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: null);

        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // ต้องไม่มี commission ใดๆ
        $this->assertEquals(0, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    /**
     * ทดสอบ wallet balance เพิ่มขึ้นหลังจ่ายคอมมิชชั่น
     */
    public function test_wallet_balance_increases(): void
    {
        [$sponsorUser, $sponsorMember] = $this->createMlmMember();
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        // สร้าง wallet ให้ sponsor (balance เริ่มต้น 0)
        $wallet = Wallet::create([
            'user_id' => $sponsorUser->id,
            'balance' => 0,
            'currency' => 'THB',
        ]);

        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // Wallet balance ต้องเพิ่มขึ้น 10 บาท (Level 1 fixed)
        $wallet->refresh();
        $this->assertEquals(10, (float) $wallet->balance);
    }

    /**
     * ทดสอบ Level 2 percent คำนวณถูกต้อง
     */
    public function test_level2_percent_calculation(): void
    {
        // ตั้ง Level 2 percent 5% → 99 × 5% = 4.95
        $this->settings->update([
            'fortune_level2_commission_type' => 'percent',
            'fortune_level2_commission_amount' => 5,
        ]);

        [$grandparentUser, $grandparentMember] = $this->createMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createMlmMember(sponsorId: $grandparentMember->id);
        [$buyerUser, $buyerMember] = $this->createMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $l2 = FortuneCommission::where('fortune_reading_id', $reading->id)->level2()->first();
        $this->assertNotNull($l2);
        $this->assertEquals('4.95', $l2->amount);
        $this->assertEquals('percent', $l2->commission_type);
    }

    // ===== Helper Methods =====

    /**
     * สร้าง MlmMember พร้อม User (active, qualified)
     *
     * @return array{0: User, 1: MlmMember}
     */
    private function createMlmMember(?int $sponsorId = null): array
    {
        $user = User::factory()->create();

        $member = MlmMember::create([
            'user_id' => $user->id,
            'member_code' => 'TM' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'unilevel_sponsor_id' => $sponsorId,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now(),
        ]);

        return [$user, $member];
    }

    /**
     * สร้าง FortuneReading ที่ชำระเงินแล้ว
     */
    private function createPaidReading(int $userId, float $amount): FortuneReading
    {
        return FortuneReading::create([
            'fortune_telling_setting_id' => $this->settings->id,
            'user_id' => $userId,
            'reading_type' => 'deep',
            'questions' => 'ทดสอบ',
            'ai_response' => 'ผลลัพธ์ทดสอบ',
            'is_paid' => true,
            'amount_paid' => $amount,
            'paid_at' => now(),
            'status' => 'completed',
        ]);
    }
}
