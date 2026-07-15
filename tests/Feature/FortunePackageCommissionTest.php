<?php

namespace Tests\Feature;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ทดสอบค่าแนะนำ "แยกอัตราตามแพคเกจ" (2026-07-15)
 *
 * แผนที่เจ้าของสั่ง:
 *   - ดูพื้นดวง 39฿ (reading_type = deep)         → สายตรง 5฿, ไม่มีชั้นหลาน
 *   - Celtic Cross 99฿ (reading_type = celtic_cross) → สายตรง 10฿, ชั้นหลาน 2฿
 *
 * ครอบคลุม:
 * - ปิดสวิตช์ = พฤติกรรมเดิมทุกประการ (กันงานนี้ไปกระทบของเดิม)
 * - เปิดสวิตช์ → deep จ่าย 5฿ ชั้นเดียว
 * - เปิดสวิตช์ → celtic จ่าย 10฿ + 2฿
 * - reading_type ที่ไม่รู้จัก (basic) → ตกไปใช้อัตราเดิม ไม่เดาเอง
 * - ยอดโอนมีเศษสตางค์ (99.47) ต้องไม่กระทบค่าแนะนำ (จ่ายบาทคงที่)
 *
 * @group fortune-commission
 */
class FortunePackageCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected FortuneCommissionService $service;

    protected FortuneTellingSetting $settings;

    protected MlmPlan $mlmPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FortuneCommissionService;

        // ปิดระบบรักษายอด → member ที่ status=active ถือว่า active
        // ⚠️ ใช้ '0' ไม่ใช่ 'false' เพราะ PHP: (bool)'false' = true
        MlmGlobalSetting::updateOrCreate(
            ['key' => 'volume_retention_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'retention']
        );
        \Illuminate\Support\Facades\Cache::flush();

        $this->mlmPlan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        // อัตราเดิม (ใช้เมื่อปิดโหมดแยกแพคเกจ): L1 = 10฿, L2 = 1฿ — ตรงกับ prod ตอนนี้
        $this->settings = FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app-'.uniqid(),
            'facebook_page_id' => 'test-page-'.uniqid(),
            'is_enabled' => true,
            'deep_reading_price' => 39,
            'celtic_cross_price' => 99,
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 1,
            // โหมดแยกแพคเกจ — ปิดไว้ก่อน แต่ละเทสต์เปิดเอง
            'fortune_pkg_rates_enabled' => false,
            'fortune_deep_l1_amount' => 5,
            'fortune_deep_l2_enabled' => false,
            'fortune_deep_l2_amount' => 0,
            'fortune_celtic_l1_amount' => 10,
            'fortune_celtic_l2_enabled' => true,
            'fortune_celtic_l2_amount' => 2,
        ]);
    }

    // ===========================================================
    // 1. ปิดสวิตช์ = ต้องไม่กระทบของเดิมเลย
    // ===========================================================

    /**
     * ปิดโหมดแยกแพคเกจ → deep ยังได้อัตราเดิม 10฿ (ไม่ใช่ 5฿ ของแพคเกจ)
     */
    public function test_package_rates_disabled_keeps_legacy_flat_rate(): void
    {
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember();
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 39, FortuneReading::READING_TYPE_DEEP);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // อัตราเดิม = 10฿ ไม่ใช่ 5฿
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'level' => 1,
            'amount' => '10.00',
        ]);
    }

    // ===========================================================
    // 2. ดูพื้นดวง 39฿ → สายตรง 5฿ ไม่มีชั้นหลาน
    // ===========================================================

    public function test_deep_package_pays_5_baht_level1_only(): void
    {
        $this->settings->update(['fortune_pkg_rates_enabled' => true]);

        // A → B → C : C ซื้อ ดังนั้น B = สายตรง, A = ชั้นหลาน
        [$grandUser, $grandMember] = $this->createActiveMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember(sponsorId: $grandMember->id);
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 39, FortuneReading::READING_TYPE_DEEP);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        // สายตรงได้ 5 บาท
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'level' => 1,
            'commission_type' => 'fixed',
            'amount' => '5.00',
        ]);

        // ชั้นหลาน — แพคเกจนี้ต้องไม่มีเลย
        $this->assertDatabaseMissing('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 2,
        ]);

        $this->assertSame(
            1,
            FortuneCommission::where('fortune_reading_id', $reading->id)->count(),
            'ดูพื้นดวงต้องจ่ายค่าแนะนำแค่รายการเดียว (สายตรง)'
        );
    }

    // ===========================================================
    // 3. Celtic 99฿ → สายตรง 10฿ + ชั้นหลาน 2฿
    // ===========================================================

    public function test_celtic_package_pays_10_and_2_baht(): void
    {
        $this->settings->update(['fortune_pkg_rates_enabled' => true]);

        [$grandUser, $grandMember] = $this->createActiveMlmMember();
        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember(sponsorId: $grandMember->id);
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99, FortuneReading::READING_TYPE_CELTIC_CROSS);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $sponsorUser->id,
            'level' => 1,
            'amount' => '10.00',
        ]);

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'user_id' => $grandUser->id,
            'level' => 2,
            'amount' => '2.00',
        ]);
    }

    // ===========================================================
    // 4. เศษสตางค์ในยอดโอนต้องไม่กระทบค่าแนะนำ
    // ===========================================================

    /**
     * ยอดจริงบน prod มีเศษสตางค์สุ่มไว้แยกบิลตอนแมตช์ SMS (เช่น 99.47)
     * โหมดแยกแพคเกจจ่ายบาทคงที่ → ต้องได้ 10฿ เท่าเดิมไม่ว่าเศษเป็นเท่าไหร่
     */
    public function test_random_satang_in_amount_paid_does_not_change_commission(): void
    {
        $this->settings->update(['fortune_pkg_rates_enabled' => true]);

        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember();
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 99.47, FortuneReading::READING_TYPE_CELTIC_CROSS);
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 1,
            'amount' => '10.00',
        ]);
    }

    // ===========================================================
    // 5. reading_type ที่ไม่รู้จัก → ตกไปใช้อัตราเดิม
    // ===========================================================

    /**
     * บิล basic (ไม่ใช่ deep/celtic) → ห้ามเดาอัตรา ต้องใช้อัตราเดิม 10฿
     */
    public function test_unknown_reading_type_falls_back_to_legacy_rate(): void
    {
        $this->settings->update(['fortune_pkg_rates_enabled' => true]);

        [$sponsorUser, $sponsorMember] = $this->createActiveMlmMember();
        [$buyerUser, $buyerMember] = $this->createActiveMlmMember(sponsorId: $sponsorMember->id);

        $reading = $this->createPaidReading($buyerUser->id, 29, 'basic');
        $this->service->distributeCommissions($reading, $buyerMember, $this->settings);

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 1,
            'amount' => '10.00',
        ]);
    }

    // ===========================================================
    // 6. Getter ระดับ model — ตรวจตรงๆ ไม่ผ่าน service
    // ===========================================================

    public function test_setting_getters_branch_by_reading_type(): void
    {
        $this->settings->update(['fortune_pkg_rates_enabled' => true]);
        $s = $this->settings->fresh();

        $this->assertSame(5.0, $s->getFortuneLevel1Amount(39, FortuneReading::READING_TYPE_DEEP));
        $this->assertSame(0.0, $s->getFortuneLevel2Amount(39, FortuneReading::READING_TYPE_DEEP));
        $this->assertFalse($s->isFortuneLevel2Enabled(FortuneReading::READING_TYPE_DEEP));

        $this->assertSame(10.0, $s->getFortuneLevel1Amount(99, FortuneReading::READING_TYPE_CELTIC_CROSS));
        $this->assertSame(2.0, $s->getFortuneLevel2Amount(99, FortuneReading::READING_TYPE_CELTIC_CROSS));
        $this->assertTrue($s->isFortuneLevel2Enabled(FortuneReading::READING_TYPE_CELTIC_CROSS));

        // ไม่ส่ง reading_type → อัตราเดิม
        $this->assertSame(10.0, $s->getFortuneLevel1Amount(99));
        $this->assertSame(1.0, $s->getFortuneLevel2Amount(99));
    }

    /**
     * ปิดสวิตช์ → getter ต้องคืนอัตราเดิมแม้ส่ง reading_type มา
     */
    public function test_setting_getters_ignore_reading_type_when_disabled(): void
    {
        $s = $this->settings->fresh();

        $this->assertFalse($s->isFortunePackageRatesEnabled());
        $this->assertSame(10.0, $s->getFortuneLevel1Amount(39, FortuneReading::READING_TYPE_DEEP));
        $this->assertTrue($s->isFortuneLevel2Enabled(FortuneReading::READING_TYPE_DEEP));
    }

    // ===========================================================
    // Helpers
    // ===========================================================

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

    private function createPaidReading(int $userId, float $amount, string $readingType): FortuneReading
    {
        return FortuneReading::create([
            'user_id' => $userId,
            'facebook_user_id' => 'test_fb_'.uniqid(),
            'reading_type' => $readingType,
            'questions' => json_encode(['ทดสอบ #'.uniqid()]),
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
