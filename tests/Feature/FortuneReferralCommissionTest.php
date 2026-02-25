<?php

namespace Tests\Feature;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ทดสอบระบบคอมมิชชั่นดูดวง Level 1 + Level 2 อย่างครบถ้วน
 *
 * ครอบคลุม:
 * - จ่าย Level 1 (สายตรง) สำเร็จ
 * - จ่าย Level 2 (หลาน) สำเร็จ
 * - คำนวณแบบ fixed amount + percent
 * - ไม่จ่าย Level 2 เมื่อปิด
 * - ป้องกันจ่ายซ้ำ (duplicate protection)
 * - ไม่มี sponsor ไม่ได้ commission
 * - Wallet balance เพิ่มขึ้นถูกต้อง
 * - ต่อสายงาน 3 ชั้น → จ่ายแค่ 2 ชั้น
 * - Sponsor inactive → ไม่จ่าย
 * - Model scopes + accessors
 * - API endpoint ดูคอมมิชชั่น
 *
 * @group fortune-commission
 */
class FortuneReferralCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected FortuneCommissionService $service;

    protected FortuneTellingSetting $settings;

    protected MlmPlan $mlmPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FortuneCommissionService;

        // ปิดระบบรักษายอด เพื่อให้ทุก member ที่ status=active ถือว่า active
        // ⚠️ ใช้ '0' ไม่ใช่ 'false' เพราะ PHP: (bool)'false' = true
        MlmGlobalSetting::updateOrCreate(
            ['key' => 'volume_retention_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'retention']
        );

        // ล้าง cache (MlmGlobalSetting::get ใช้ Cache::remember)
        \Illuminate\Support\Facades\Cache::flush();

        // สร้าง MLM Plan (จำเป็นสำหรับ mlm_members FK)
        $this->mlmPlan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        // สร้าง settings เริ่มต้น: Level 1 fixed 10 บาท, Level 2 fixed 5 บาท
        $this->settings = FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app-'.uniqid(),
            'facebook_page_id' => 'test-page-'.uniqid(),
            'is_enabled' => true,
            'reading_price' => 29,
            'deep_reading_price' => 99,
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 5,
        ]);
    }

    // ===========================================================
    // 1. Core: Level 1 Commission
    // ===========================================================

    /**
     * ทดสอบ Level 1 commission จ่ายให้ sponsor ตรงสำเร็จ
     */
    public function test_level1_commission_paid_to_sponsor(): void
    {
        // A (sponsor) → B (buyer)
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember();
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // ยืนยัน record ถูกสร้าง
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'from_user_id' => $buyerUser->id,
            'level' => 1,
            'commission_type' => 'fixed',
            'amount' => '10.00',
            'reading_price' => '99.00',
            'status' => FortuneCommission::STATUS_PAID,
        ]);
    }

    // ===========================================================
    // 2. Core: Level 2 Commission (Grandparent)
    // ===========================================================

    /**
     * ทดสอบ Level 2 commission จ่ายให้ grandparent
     */
    public function test_level2_commission_paid_to_grandparent(): void
    {
        // A (grandparent) → B (sponsor) → C (buyer)
        [$grandUser, $grandMember] = $this->createActiveMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember(sponsorId: $grandMember->id);
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // Level 1 → sponsor
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'level' => 1,
            'amount' => '10.00',
        ]);

        // Level 2 → grandparent
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $grandUser->id,
            'level' => 2,
            'amount' => '5.00',
        ]);

        // ต้องมี 2 records (L1 + L2)
        $this->assertEquals(2, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    /**
     * ทดสอบต่อสายงาน 4 ชั้น → จ่ายแค่ 2 ชั้น (ไม่เกิน grandparent)
     */
    public function test_only_2_levels_paid_even_with_deep_tree(): void
    {
        // A → B → C → D (buyer)
        [$greatGrand, $ggMember] = $this->createActiveMlmMember();
        [$grandUser, $grandMember] = $this->createActiveMlmMember(sponsorId: $ggMember->id);
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember(sponsorId: $grandMember->id);
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99);

        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // L1 → sponsor (C), L2 → grandparent (B), great-grandparent (A) ไม่ได้
        $commissions = FortuneCommission::where('fortune_reading_id', $reading->id)->get();
        $this->assertEquals(2, $commissions->count());
        $this->assertTrue($commissions->contains('user_id', $sponsorUser->id));
        $this->assertTrue($commissions->contains('user_id', $grandUser->id));
        $this->assertFalse($commissions->contains('user_id', $greatGrand->id));
    }

    // ===========================================================
    // 3. Commission Types: Fixed & Percent
    // ===========================================================

    /**
     * ทดสอบ Level 1 fixed amount
     */
    public function test_level1_fixed_amount(): void
    {
        $this->settings->update([
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 25,
        ]);

        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 199);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $c = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $this->assertNotNull($c);
        $this->assertEquals('25.00', $c->amount); // fixed ไม่ขึ้นกับราคา
        $this->assertEquals('fixed', $c->commission_type);
        $this->assertEquals('25.00', $c->commission_rate);
    }

    /**
     * ทดสอบ Level 1 percent
     */
    public function test_level1_percent_calculation(): void
    {
        $this->settings->update([
            'fortune_level1_commission_type' => 'percent',
            'fortune_level1_commission_amount' => 10, // 10%
        ]);

        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $c = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $this->assertNotNull($c);
        $this->assertEquals('9.90', $c->amount); // 99 × 10% = 9.90
        $this->assertEquals('percent', $c->commission_type);
    }

    /**
     * ทดสอบ Level 2 percent calculation
     */
    public function test_level2_percent_calculation(): void
    {
        $this->settings->update([
            'fortune_level2_commission_type' => 'percent',
            'fortune_level2_commission_amount' => 5, // 5%
        ]);

        [$grand, $gMember] = $this->createActiveMlmMember();
        [$sponsor, $sMember] = $this->createActiveMlmMember(sponsorId: $gMember->id);
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 200);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $c = FortuneCommission::where('fortune_reading_id', $reading->id)->level2()->first();
        $this->assertNotNull($c);
        $this->assertEquals('10.00', $c->amount); // 200 × 5% = 10.00
    }

    /**
     * ทดสอบ Mixed: L1 percent + L2 fixed
     */
    public function test_mixed_l1_percent_l2_fixed(): void
    {
        $this->settings->update([
            'fortune_level1_commission_type' => 'percent',
            'fortune_level1_commission_amount' => 15, // 15%
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 8, // 8 บาท
        ]);

        [$grand, $gMember] = $this->createActiveMlmMember();
        [$sponsor, $sMember] = $this->createActiveMlmMember(sponsorId: $gMember->id);
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 100);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $l1 = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $l2 = FortuneCommission::where('fortune_reading_id', $reading->id)->level2()->first();

        $this->assertEquals('15.00', $l1->amount); // 100 × 15%
        $this->assertEquals('8.00', $l2->amount);  // fixed 8 บาท
    }

    // ===========================================================
    // 4. Level 2 Toggle
    // ===========================================================

    /**
     * ทดสอบปิด Level 2 → ไม่จ่าย grandparent
     */
    public function test_no_level2_when_disabled(): void
    {
        $this->settings->update(['fortune_level2_enabled' => false]);

        [$grand, $gMember] = $this->createActiveMlmMember();
        [$sponsor, $sMember] = $this->createActiveMlmMember(sponsorId: $gMember->id);
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $this->assertDatabaseHas('fortune_commissions', ['fortune_reading_id' => $reading->id, 'level' => 1]);
        $this->assertDatabaseMissing('fortune_commissions', ['fortune_reading_id' => $reading->id, 'level' => 2]);
    }

    // ===========================================================
    // 5. Safety: Duplicate Prevention
    // ===========================================================

    /**
     * ทดสอบจ่ายซ้ำ → ถูกป้องกัน
     */
    public function test_prevents_duplicate_commission(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);

        // จ่ายครั้งแรก
        $this->service->distributeCommissions($reading, $bMember, $this->settings);
        $count1 = FortuneCommission::where('fortune_reading_id', $reading->id)->count();

        // จ่ายอีก 2 ครั้ง → ต้องไม่เพิ่ม
        $this->service->distributeCommissions($reading, $bMember, $this->settings);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);
        $count3 = FortuneCommission::where('fortune_reading_id', $reading->id)->count();

        $this->assertEquals($count1, $count3, 'ต้องไม่มี duplicate commission');
    }

    // ===========================================================
    // 6. Edge Cases
    // ===========================================================

    /**
     * ทดสอบไม่มี sponsor → ไม่จ่าย
     */
    public function test_no_commission_for_orphan(): void
    {
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: null);
        $reading = $this->createPaidReading($buyer->id, 99);

        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $this->assertEquals(0, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    /**
     * ทดสอบ sponsor ไม่ active → ไม่จ่าย (ไม่ roll up)
     */
    public function test_inactive_sponsor_gets_no_commission(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        $sMember->update(['status' => 'inactive']); // ปิด active

        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);
        $reading = $this->createPaidReading($buyer->id, 99);

        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        // Sponsor inactive → L1 ไม่จ่าย (ไม่ roll up)
        $this->assertDatabaseMissing('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsor->id,
            'level' => 1,
        ]);
    }

    /**
     * ทดสอบ reading ไม่ได้จ่ายเงิน (amount = 0) → ไม่จ่ายคอมมิชชั่น
     */
    public function test_no_commission_for_free_reading(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        // บิลฟรี amount = 0
        $reading = FortuneReading::create([
            'user_id' => $buyer->id,
            'facebook_user_id' => 'test_fb_free_' . uniqid(),
            'reading_type' => 'basic',
            'questions' => json_encode(['ทดสอบฟรี']),
            'ai_response' => 'ผลลัพธ์',
            'ai_provider' => 'test',
            'is_paid' => false,
            'amount_paid' => 0,
            'conversation_status' => 'completed',
            'platform' => 'test',
        ]);

        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $this->assertEquals(0, FortuneCommission::where('fortune_reading_id', $reading->id)->count());
    }

    // ===========================================================
    // 7. Wallet Integration
    // ===========================================================

    /**
     * ทดสอบ Wallet balance เพิ่มหลังจ่ายคอมมิชชั่น
     */
    public function test_wallet_balance_increases_after_commission(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        // สร้าง wallet ให้ sponsor (wallet_address auto-generated ใน boot)
        Wallet::create([
            'user_id' => $sponsor->id,
            'balance' => 100,
            'currency' => 'THB',
        ]);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        // Wallet ต้องเพิ่ม 10 บาท (L1 fixed) → 100 + 10 = 110
        $sponsor->refresh();
        $wallet = Wallet::where('user_id', $sponsor->id)->first();
        $this->assertEquals(110, (float) $wallet->balance);
    }

    /**
     * ทดสอบ Auto-create wallet ถ้า user ยังไม่มี wallet
     */
    public function test_auto_creates_wallet_if_not_exists(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        // ไม่สร้าง wallet → service ต้องสร้างให้
        $this->assertNull(Wallet::where('user_id', $sponsor->id)->first());

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        // Wallet ถูกสร้างอัตโนมัติ + มีเงิน 10 บาท
        $wallet = Wallet::where('user_id', $sponsor->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(10, (float) $wallet->balance);
    }

    // ===========================================================
    // 8. MLM Tree: ใช้ผังเดียวกับ MLM (ไม่แยก)
    // ===========================================================

    /**
     * ทดสอบว่า fortune commission ใช้ mlm_members.unilevel_sponsor_id
     */
    public function test_uses_mlm_tree_for_sponsor_chain(): void
    {
        [$grand, $gMember] = $this->createActiveMlmMember();
        [$sponsor, $sMember] = $this->createActiveMlmMember(sponsorId: $gMember->id);
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        // ยืนยัน mlm_member_id ตรงกับ sponsor/grandparent ใน mlm_members
        $l1 = FortuneCommission::where('fortune_reading_id', $reading->id)->level1()->first();
        $l2 = FortuneCommission::where('fortune_reading_id', $reading->id)->level2()->first();

        $this->assertEquals($sMember->id, $l1->mlm_member_id);
        $this->assertEquals($gMember->id, $l2->mlm_member_id);
        $this->assertEquals($bMember->id, $l1->from_mlm_member_id);
    }

    // ===========================================================
    // 9. Model Scopes & Accessors
    // ===========================================================

    /**
     * ทดสอบ Model scopes (level1, level2, forUser, paid)
     */
    public function test_model_scopes(): void
    {
        [$grand, $gMember] = $this->createActiveMlmMember();
        [$sponsor, $sMember] = $this->createActiveMlmMember(sponsorId: $gMember->id);
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $this->assertEquals(1, FortuneCommission::level1()->count());
        $this->assertEquals(1, FortuneCommission::level2()->count());
        $this->assertEquals(1, FortuneCommission::forUser($sponsor->id)->count());
        $this->assertEquals(1, FortuneCommission::forUser($grand->id)->count());
        $this->assertEquals(2, FortuneCommission::paid()->count());
    }

    /**
     * ทดสอบ Model accessors (level_name, status_name)
     */
    public function test_model_accessors(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $reading = $this->createPaidReading($buyer->id, 99);
        $this->service->distributeCommissions($reading, $bMember, $this->settings);

        $commission = FortuneCommission::first();
        $this->assertEquals('สายตรง', $commission->level_name);
        $this->assertEquals('จ่ายแล้ว', $commission->status_name);
        $this->assertTrue($commission->isLevel1());
        $this->assertFalse($commission->isLevel2());
    }

    // ===========================================================
    // 10. Multiple Readings → Multiple Commission Batches
    // ===========================================================

    /**
     * ทดสอบหลายบิล → หลาย commission (ไม่ปนกัน)
     */
    public function test_multiple_readings_create_separate_commissions(): void
    {
        [$sponsor, $sMember] = $this->createActiveMlmMember();
        [$buyer, $bMember] = $this->createActiveMlmMember(sponsorId: $sMember->id);

        $r1 = $this->createPaidReading($buyer->id, 99);
        $r2 = $this->createPaidReading($buyer->id, 199);

        $this->service->distributeCommissions($r1, $bMember, $this->settings);
        $this->service->distributeCommissions($r2, $bMember, $this->settings);

        // 2 บิล → 2 commissions (แต่ละบิล 1 L1)
        $this->assertEquals(1, FortuneCommission::where('fortune_reading_id', $r1->id)->count());
        $this->assertEquals(1, FortuneCommission::where('fortune_reading_id', $r2->id)->count());
        $this->assertEquals(2, FortuneCommission::forUser($sponsor->id)->count());
    }

    // ===========================================================
    // Helpers
    // ===========================================================

    /**
     * สร้าง MlmMember + User ที่ active
     *
     * @return array{0: User, 1: MlmMember}
     */
    private function createActiveMlmMember(?int $sponsorId = null): array
    {
        $user = User::factory()->create();

        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->mlmPlan->id,
            'member_code' => 'TM'.str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT),
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
            'user_id' => $userId,
            'facebook_user_id' => 'test_fb_' . uniqid(),
            'reading_type' => 'deep',
            'questions' => json_encode(['ทดสอบ #' . uniqid()]),
            'ai_response' => 'ผลลัพธ์ทดสอบ',
            'ai_provider' => 'test',
            'is_paid' => true,
            'amount_paid' => $amount,
            'paid_at' => now(),
            'conversation_status' => 'paid',
            'platform' => 'test',
        ]);
    }
}
