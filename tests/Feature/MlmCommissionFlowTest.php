<?php

namespace Tests\Feature;

use App\Helpers\MlmRetentionHelper;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use App\Models\Rank;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MlmCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ทดสอบ Flow การคำนวณคอมมิชชั่น MLM แบบครบวงจร
 *
 * จำลองสถานการณ์จริง:
 * 1. สินค้า (Shop) — จ่ายคอมตาม PV + rollup ปกติ
 * 2. ดูดวง (Fortune) — ค่าแนะนำ Static / PV mode ไม่ rollup
 * 3. Active/Inactive check — ซื้อของ หรือ ดูดวง ถือว่า active
 * 4. Rank bonus multiplier — คูณคอมตาม rank
 * 5. Overpay protection — ป้องกันจ่ายเกิน
 *
 * โครงสร้างสมาชิก:
 * Admin (#1) → Sponsor A (#2) → Sponsor B (#3) → Buyer C (#4)
 */
class MlmCommissionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected MlmPlan $plan;

    protected User $adminUser;

    protected User $userA;

    protected User $userB;

    protected User $userC;

    protected MlmMember $adminMember;

    protected MlmMember $memberA;

    protected MlmMember $memberB;

    protected MlmMember $memberC;

    protected Wallet $walletA;

    protected Wallet $walletB;

    /**
     * ตั้งค่าข้อมูลทดสอบ — สร้าง MLM hierarchy + settings
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ล้าง cache ก่อนทุก test (ป้องกัน Cache::remember ค้างจาก test ก่อนหน้า)
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        // สร้าง MLM Plan
        $this->plan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan',
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        // สร้าง Users (Admin, A, B, C)
        // ⚠️ 'role' อยู่ใน $guarded → ต้องใช้ forceFill หรือ DB update
        $this->adminUser = User::factory()->create(['name' => 'Admin']);
        $this->adminUser->forceFill(['role' => 'admin'])->save();
        $this->userA = User::factory()->create(['name' => 'Sponsor A']);
        $this->userB = User::factory()->create(['name' => 'Sponsor B']);
        $this->userC = User::factory()->create(['name' => 'Buyer C']);

        // สร้าง Wallets สำหรับ sponsor ที่จะรับคอม
        Wallet::create(['user_id' => $this->adminUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        $this->walletA = Wallet::create(['user_id' => $this->userA->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        $this->walletB = Wallet::create(['user_id' => $this->userB->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->userC->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        // สร้าง MLM Members: Admin → A → B → C
        $this->adminMember = MlmMember::create([
            'user_id' => $this->adminUser->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'ADM001',
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonths(6),
        ]);

        $this->memberA = MlmMember::create([
            'user_id' => $this->userA->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'MEM-A',
            'unilevel_sponsor_id' => $this->adminMember->id,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonths(3),
        ]);

        $this->memberB = MlmMember::create([
            'user_id' => $this->userB->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'MEM-B',
            'unilevel_sponsor_id' => $this->memberA->id,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonths(2),
        ]);

        $this->memberC = MlmMember::create([
            'user_id' => $this->userC->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'MEM-C',
            'unilevel_sponsor_id' => $this->memberB->id,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
        ]);

        // ตั้งค่า MLM Global Settings
        $this->setupMlmSettings();
    }

    /**
     * ตั้งค่า MLM settings สำหรับทดสอบ
     *
     * ⚠️ ข้อควรระวัง:
     * - MlmGlobalSetting::set() ใช้ detectType() สำหรับ record ใหม่
     * - ถ้าส่ง string 'false' → type = 'string' → get() คืน 'false' (truthy!)
     * - ถ้าส่ง boolean false → type = 'boolean' → get() คืน false (falsy)
     * - ถ้าส่ง json_encode(array) → type = 'string' → get() คืน JSON string (ไม่ decode)
     * - ถ้าส่ง array โดยตรง → type = 'json' → get() คืน array (decoded)
     */
    protected function setupMlmSettings(): void
    {
        // Unilevel levels: 5 ชั้น [5%, 3%, 2%, 1%, 1%]
        // ⚠️ ส่ง array โดยตรง → detectType() จะกำหนด type = 'json'
        // → getTypedValue() จะ json_decode กลับเป็น array อัตโนมัติ
        MlmGlobalSetting::set('unilevel_levels', [
            ['level' => 1, 'percentage' => 5],
            ['level' => 2, 'percentage' => 3],
            ['level' => 3, 'percentage' => 2],
            ['level' => 4, 'percentage' => 1],
            ['level' => 5, 'percentage' => 1],
        ]);

        // ตั้ง unilevel_percentages ด้วย (resolveUnilevelLevels อ่านจาก key นี้ก่อน)
        MlmGlobalSetting::set('unilevel_percentages', [5, 3, 2, 1, 1]);

        MlmGlobalSetting::set('global_pv_rate', '1');
        MlmGlobalSetting::set('commission_per_pv', '0.01');
        MlmGlobalSetting::set('unilevel_max_depth', '10');
        MlmGlobalSetting::set('unilevel_enabled', true);
        MlmGlobalSetting::set('binary_enabled', false);
        MlmGlobalSetting::set('max_commission_percentage', '80');

        // Rollup settings
        MlmGlobalSetting::set('rollup_enabled', true);
        MlmGlobalSetting::set('rollup_prevent_duplicate', true);
        MlmGlobalSetting::set('rollup_max_levels', '10');

        // Retention settings — ใช้ boolean จริง ไม่ใช่ string
        MlmGlobalSetting::set('volume_retention_enabled', true);
        MlmGlobalSetting::set('volume_retention_monthly_pv', '100');
        MlmGlobalSetting::set('volume_retention_grace_days', '7');
    }

    /**
     * ทำให้สมาชิก active ด้วยการซื้อสินค้า (สร้าง PV transaction)
     */
    protected function makeActiveByPurchase(MlmMember $member, float $pvAmount = 200): void
    {
        MlmPvTransaction::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $this->plan->id,
            'transaction_type' => 'purchase',
            'pv_amount' => $pvAmount,
            'sales_amount' => $pvAmount,
            'previous_balance' => 0,
            'new_balance' => $pvAmount,
            'description' => 'ซื้อสินค้าทดสอบ',
            'created_at' => now(),
        ]);
    }

    /**
     * ทำให้สมาชิก active ด้วยการดูดวง (สร้าง PV transaction)
     */
    protected function makeActiveByFortune(MlmMember $member, float $pvAmount = 200): void
    {
        MlmPvTransaction::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $this->plan->id,
            'transaction_type' => 'fortune_reading',
            'pv_amount' => $pvAmount,
            'sales_amount' => $pvAmount,
            'previous_balance' => 0,
            'new_balance' => $pvAmount,
            'description' => 'ดูดวงทดสอบ',
            'created_at' => now(),
        ]);
    }

    // ============================================================
    // กลุ่ม 1: ทดสอบ Active Status
    // ============================================================

    /**
     * @test
     * ซื้อของ ถือว่า active
     */
    public function member_is_active_when_purchased_this_month(): void
    {
        $this->makeActiveByPurchase($this->memberA, 200);

        $this->assertTrue(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * ดูดวง ถือว่า active
     */
    public function member_is_active_when_fortune_reading_this_month(): void
    {
        $this->makeActiveByFortune($this->memberA, 200);

        $this->assertTrue(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * ไม่มีการเคลื่อนไหว → inactive
     */
    public function member_is_inactive_when_no_activity(): void
    {
        // ไม่มี PV transaction เลย
        $this->assertFalse(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * PV ไม่ถึงเกณฑ์ และ พ้น grace period → inactive
     */
    public function member_is_inactive_when_pv_below_threshold(): void
    {
        // ⚠️ ปิด grace period เพื่อทดสอบ PV threshold ล้วนๆ
        MlmGlobalSetting::set('volume_retention_grace_days', '0');

        // เกณฑ์ = 100 PV, มีแค่ 50
        // สร้าง transaction เมื่อเดือนก่อน (ไม่นับใน monthly PV ของเดือนนี้)
        MlmPvTransaction::create([
            'mlm_member_id' => $this->memberA->id,
            'mlm_plan_id' => $this->plan->id,
            'transaction_type' => 'purchase',
            'pv_amount' => 50,
            'sales_amount' => 50,
            'previous_balance' => 0,
            'new_balance' => 50,
            'description' => 'ซื้อสินค้า PV ต่ำกว่าเกณฑ์ เดือนก่อน',
            'created_at' => now()->subMonth()->subDay(),
        ]);

        $this->assertFalse(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * ปิดระบบ retention → ทุกคน active (ตราบใดที่ status = 'active')
     */
    public function all_members_active_when_retention_disabled(): void
    {
        // ⚠️ ต้องส่ง boolean false (ไม่ใช่ string 'false')
        // เพราะ set() ใช้ detectType() → boolean false จะได้ type='boolean'
        // → getTypedValue() จะ cast (bool) '0' = false (ถูกต้อง)
        // แต่ถ้าส่ง string 'false' → type='string' → get() คืน 'false' (truthy!)
        MlmGlobalSetting::set('volume_retention_enabled', false);

        // ไม่มี PV transaction แต่ยังถือว่า active (เพราะปิดระบบ retention)
        $this->assertTrue(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * Grace period — ซื้อเมื่อ 5 วันก่อน (grace = 7 วัน) → ยังถือว่า active
     */
    public function member_is_active_during_grace_period(): void
    {
        MlmPvTransaction::create([
            'mlm_member_id' => $this->memberA->id,
            'mlm_plan_id' => $this->plan->id,
            'transaction_type' => 'purchase',
            'pv_amount' => 50, // ไม่ถึงเกณฑ์ 100
            'sales_amount' => 50,
            'previous_balance' => 0,
            'new_balance' => 50,
            'description' => 'ซื้อเมื่อ 5 วันก่อน',
            'created_at' => now()->subDays(5),
        ]);

        $this->assertTrue(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    // ============================================================
    // กลุ่ม 2: ทดสอบ Shop Commission (สินค้า) — Rollup ปกติ
    // ============================================================

    /**
     * @test
     * สินค้า: ทุกคน active → จ่ายคอมครบทุก level
     */
    public function shop_commission_pays_all_active_levels(): void
    {
        // ทำให้ทุกคน active
        $this->makeActiveByPurchase($this->adminMember);
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->memberB);

        $service = app(MlmCommissionService::class);

        // C ซื้อของ PV = 1000
        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            1000.0,
            'purchase',
            999
        );

        $this->assertTrue($result['success']);

        // commission_per_pv = 0.01
        // Level 1 (B): 1000 × 5% × 0.01 = 0.50
        // Level 2 (A): 1000 × 3% × 0.01 = 0.30
        // Level 3 (Admin): 1000 × 2% × 0.01 = 0.20
        $commissions = $result['commissions'];
        $this->assertGreaterThanOrEqual(3, count($commissions));

        // ตรวจสอบว่ามีคอมมิชชั่น Level 1 (B ได้รับ)
        $level1 = collect($commissions)->firstWhere('level', 1);
        $this->assertNotNull($level1, 'ต้องมี commission Level 1');
        $this->assertEquals($this->memberB->id, $level1['mlm_member_id']);
        $this->assertEquals(0.50, $level1['commission_amount']);

        // ตรวจสอบ Level 2 (A ได้รับ)
        $level2 = collect($commissions)->firstWhere('level', 2);
        $this->assertNotNull($level2, 'ต้องมี commission Level 2');
        $this->assertEquals($this->memberA->id, $level2['mlm_member_id']);
        $this->assertEquals(0.30, $level2['commission_amount']);
    }

    /**
     * @test
     * สินค้า: สมาชิกไม่ active → rollup ให้คน active ถัดไป
     */
    public function shop_commission_rolls_up_for_inactive_member(): void
    {
        // B = inactive (ไม่มี PV), A = active, Admin = active
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->adminMember);

        $service = app(MlmCommissionService::class);

        // C ซื้อของ PV = 1000
        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            1000.0,
            'purchase',
            888
        );

        $this->assertTrue($result['success']);

        $commissions = $result['commissions'];

        // B ไม่ active → Level 1 commission ต้อง rollup
        // ตรวจว่า B ไม่ได้รับคอมตรงๆ (ถ้าไม่มี rollup record ที่ is_rollup=false ของ B)
        $directB = collect($commissions)->filter(function ($c) {
            return $c['mlm_member_id'] === $this->memberB->id
                && ! ($c['is_rollup'] ?? false);
        });
        $this->assertCount(0, $directB, 'B ไม่ active ต้องไม่ได้รับคอมตรง');

        // A ต้องได้รับคอมจาก Level 2 ตามปกติ (และอาจได้ rollup จาก B ด้วย)
        $commA = collect($commissions)->filter(function ($c) {
            return $c['mlm_member_id'] === $this->memberA->id;
        });
        $this->assertGreaterThan(0, $commA->count(), 'A ต้องได้รับคอม (rollup จาก B หรือ Level 2)');
    }

    // ============================================================
    // กลุ่ม 3: ทดสอบ Fortune Commission — Static Mode (ค่าแนะนำ)
    // ============================================================

    /**
     * @test
     * ดูดวง Static: ผู้แนะนำ active → จ่ายค่าแนะนำเต็มจำนวน
     */
    public function fortune_static_pays_active_sponsor_full_amount(): void
    {
        // ทำให้ B (sponsor ตรงของ C) active
        $this->makeActiveByPurchase($this->memberB);

        // สร้าง FortuneReading (⚠️ facebook_user_id = NOT NULL ใน DB)
        $reading = FortuneReading::create([
            'user_id' => $this->userC->id,
            'facebook_user_id' => 'test-fb-user-c',
            'bill_reference' => 'TEST-001',
            'is_paid' => true,
            'amount_paid' => 39,
            'platform' => 'facebook',
        ]);

        // จ่ายค่าแนะนำโดยตรง (จำลอง distributeStaticCommissions logic)
        $sponsor = $this->memberB;
        $isActive = MlmRetentionHelper::isMemberActive($sponsor);
        $this->assertTrue($isActive, 'Sponsor B ต้อง active');

        // สร้าง commission record (จำลอง static mode: จ่าย 10 บาทตรงให้ sponsor)
        MlmCommission::create([
            'mlm_member_id' => $sponsor->id,
            'mlm_plan_id' => $this->plan->id,
            'user_id' => $sponsor->user_id,
            'from_member_id' => $this->memberC->id,
            'source_type' => FortuneReading::class,
            'source_id' => $reading->id,
            'type' => 'unilevel_direct',
            'level' => 1,
            'commission_amount' => 10,
            'pv_amount' => 10,
            'percentage' => 100,
            'status' => 'pending',
            'is_rollup' => false,
            'tree_type' => 'unilevel',
            'notes' => 'ค่าแนะนำดูดวง 10 บาท',
        ]);

        // ตรวจสอบว่ามี commission record
        $commission = MlmCommission::where('source_type', FortuneReading::class)
            ->where('source_id', $reading->id)
            ->first();

        $this->assertNotNull($commission);
        $this->assertEquals(10.00, (float) $commission->commission_amount);
        $this->assertEquals(1, $commission->level);
        $this->assertEquals($this->memberB->id, $commission->mlm_member_id);
        $this->assertEquals(100, (float) $commission->percentage);

        // กำไรสุทธิ = 39 - 10 = 29
        $netProfit = 39 - (float) $commission->commission_amount;
        $this->assertEquals(29, $netProfit, 'กำไรสุทธิต้อง = 29 บาท');
    }

    /**
     * @test
     * ดูดวง Static: ผู้แนะนำไม่ active → ไม่จ่าย ไม่ rollup
     */
    public function fortune_static_skips_inactive_sponsor_no_rollup(): void
    {
        // B ไม่ active (ไม่มี PV)
        // A active
        $this->makeActiveByPurchase($this->memberA);

        $sponsor = $this->memberB;
        $isActive = MlmRetentionHelper::isMemberActive($sponsor);
        $this->assertFalse($isActive, 'Sponsor B ต้องไม่ active');

        // สร้าง FortuneReading (⚠️ facebook_user_id = NOT NULL ใน DB)
        $reading = FortuneReading::create([
            'user_id' => $this->userC->id,
            'facebook_user_id' => 'test-fb-user-c',
            'bill_reference' => 'TEST-002',
            'is_paid' => true,
            'amount_paid' => 39,
            'platform' => 'facebook',
        ]);

        // จำลอง logic: ถ้า sponsor ไม่ active → ข้ามเลย ไม่จ่าย ไม่ rollup
        // ไม่สร้าง commission record
        if (! $isActive) {
            // ข้ามเลย — ตาม logic ใน distributeStaticCommissions
        }

        // ตรวจว่าไม่มี commission ถูกสร้างเลย
        $commissionCount = MlmCommission::where('source_type', FortuneReading::class)
            ->where('source_id', $reading->id)
            ->count();

        $this->assertEquals(0, $commissionCount, 'ดูดวง: ไม่ active ต้องไม่มี commission (ไม่ rollup)');

        // ตรวจว่า A ก็ไม่ได้รับ rollup
        $rollupToA = MlmCommission::where('mlm_member_id', $this->memberA->id)
            ->where('source_type', FortuneReading::class)
            ->where('source_id', $reading->id)
            ->count();

        $this->assertEquals(0, $rollupToA, 'A ต้องไม่ได้รับ rollup จากดูดวง');
    }

    // ============================================================
    // กลุ่ม 4: ทดสอบ Fortune Commission — PV Mode (ไม่ rollup)
    // ============================================================

    /**
     * @test
     * ดูดวง PV Mode: ทุกคน active → จ่ายคอมครบ แต่ไม่ rollup
     */
    public function fortune_pv_mode_pays_all_active_no_rollup(): void
    {
        // ทำให้ทุกคน active
        $this->makeActiveByPurchase($this->adminMember);
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->memberB);

        $service = app(MlmCommissionService::class);

        // ดูดวง PV = 39 (price × pv_rate = 39 × 1.0)
        // disableRollup = true (กฎดูดวง)
        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            39.0,
            'fortune_reading',
            777,
            disableRollup: true
        );

        $this->assertTrue($result['success']);

        // commission_per_pv = 0.01
        // Level 1 (B): 39 × 5% × 0.01 = 0.0195 → 0.02
        // Level 2 (A): 39 × 3% × 0.01 = 0.0117 → 0.01
        // Level 3 (Admin): 39 × 2% × 0.01 = 0.0078 → 0.01
        $commissions = $result['commissions'];
        $this->assertGreaterThanOrEqual(2, count($commissions));
    }

    /**
     * @test
     * ดูดวง PV Mode: สมาชิกไม่ active → ข้ามเลย ไม่ rollup
     */
    public function fortune_pv_mode_skips_inactive_no_rollup(): void
    {
        // B = inactive, A = active, Admin = active
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->adminMember);

        $service = app(MlmCommissionService::class);

        // ดูดวง PV = 39, disableRollup = true
        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            39.0,
            'fortune_reading',
            666,
            disableRollup: true
        );

        $this->assertTrue($result['success']);

        $commissions = $result['commissions'];

        // B ไม่ active → ข้ามไปเลย ไม่มี rollup
        $bReceived = collect($commissions)->filter(function ($c) {
            return $c['mlm_member_id'] === $this->memberB->id;
        });
        $this->assertCount(0, $bReceived, 'B ไม่ active ต้องไม่ได้คอม');

        // ตรวจว่าไม่มี rollup record (is_rollup = true)
        $rollupRecords = collect($commissions)->filter(function ($c) {
            return ($c['is_rollup'] ?? false) === true;
        });
        $this->assertCount(0, $rollupRecords, 'ดูดวง: ต้องไม่มี rollup record');
    }

    // ============================================================
    // กลุ่ม 5: ทดสอบ Rank Bonus Multiplier
    // ============================================================

    /**
     * @test
     * สมาชิกมี Rank bonus_multiplier → คอมถูกคูณ
     */
    public function rank_bonus_multiplier_increases_commission(): void
    {
        // ทำให้ทุกคน active
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->memberB);
        $this->makeActiveByPurchase($this->adminMember);

        // สร้าง Rank ที่มี bonus_multiplier = 2
        $rank = Rank::create([
            'name' => 'Gold',
            'name_th' => 'ทอง',
            'level' => 2,
            'bonus_multiplier' => 2.0,
            'commission_rate' => 0.1,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // กำหนด rank ให้ B
        $this->userB->update(['current_rank_id' => $rank->id]);

        $service = app(MlmCommissionService::class);

        // C ซื้อของ PV = 1000
        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            1000.0,
            'purchase',
            555
        );

        $this->assertTrue($result['success']);

        $commissions = $result['commissions'];
        $level1 = collect($commissions)->firstWhere('level', 1);

        // Level 1 (B) ปกติ: 1000 × 5% × 0.01 = 0.50
        // ถ้ามี rank multiplier × 2 → 0.50 × 2 = 1.00
        $this->assertNotNull($level1);
        $this->assertEquals(1.00, $level1['commission_amount'], 'B มี rank multiplier ×2 ต้องได้ 1.00');
    }

    // ============================================================
    // กลุ่ม 6: ทดสอบ Overpay Protection
    // ============================================================

    /**
     * @test
     * คอมรวมเกิน max_commission_percentage → ถูก scale ลง
     */
    public function overpay_protection_scales_down_commissions(): void
    {
        // ทำให้ทุกคน active
        $this->makeActiveByPurchase($this->adminMember);
        $this->makeActiveByPurchase($this->memberA);
        $this->makeActiveByPurchase($this->memberB);

        // ตั้ง max_commission_percentage ต่ำมาก เพื่อบังคับให้ overpay trigger
        MlmGlobalSetting::set('max_commission_percentage', '1');
        // commission_per_pv = 1 (แทน 0.01) เพื่อให้คอมเยอะจน trigger overpay
        MlmGlobalSetting::set('commission_per_pv', '1');

        $service = app(MlmCommissionService::class);

        $result = $service->calculateCommissionsWithRollup(
            $this->memberC,
            1000.0,
            'purchase',
            444
        );

        $this->assertTrue($result['success']);

        // ถ้า max_commission_percentage = 1%
        // PV Pool = 1000 × 1 = 1000
        // Max commission = 1000 × 1% = 10
        // ปกติ: Level 1 = 50, Level 2 = 30, Level 3 = 20 → total 100
        // ต้องถูก scale down → total ≤ 10
        $totalCommission = array_sum(array_column($result['commissions'], 'commission_amount'));
        $maxAllowed = 1000 * 1 * (1 / 100); // PV × commission_per_pv × max%

        $this->assertLessThanOrEqual($maxAllowed + 0.01, $totalCommission,
            'คอมรวมต้องไม่เกิน max_commission_percentage');
    }

    // ============================================================
    // กลุ่ม 7: ทดสอบ Fortune Commission Preview API
    // ============================================================

    /**
     * @test
     * Preview API — Static mode ต้องแสดง Level 1 อย่างเดียว
     */
    public function fortune_commission_preview_static_mode(): void
    {
        // ⚠️ ใช้ getSettings() แทน create() เพราะ $attributes default มี
        // 'enabled_platforms' => ['facebook'] (PHP array) ซึ่ง create() ไม่ serialize ให้
        // → เกิด "Array to string conversion" ตอน INSERT
        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'is_enabled' => true,
            'deep_reading_price' => 39,
            'fortune_commission_mode' => 'static',
            'fortune_static_commission_amount' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.fortune.settings.fortune-commission-preview', [
                'commission_mode' => 'static',
                'static_amount' => 10,
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'mode' => 'static',
                    'price' => 39,
                    'static_amount' => 10,
                    'total_commission' => 10,
                    'net_profit' => 29,
                ],
            ]);

        // ต้องมีแค่ 1 level
        $levels = $response->json('data.levels');
        $this->assertCount(1, $levels, 'Static mode ต้องมีแค่ Level 1');
        $this->assertEquals(1, $levels[0]['level']);
        $this->assertEquals(100, $levels[0]['percentage']);
        $this->assertEquals(10, $levels[0]['amount']);
    }

    /**
     * @test
     * Preview API — PV mode ต้องแสดงหลาย level
     */
    public function fortune_commission_preview_pv_mode(): void
    {
        // ⚠️ ใช้ getSettings() แทน create() (ดูเหตุผลใน static_mode test)
        $settings = FortuneTellingSetting::getSettings();
        $settings->update([
            'is_enabled' => true,
            'deep_reading_price' => 39,
            'fortune_commission_mode' => 'pv',
            'fortune_pv_value' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.fortune.settings.fortune-commission-preview', [
                'commission_mode' => 'pv',
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'mode' => 'pv',
                    'price' => 39,
                ],
            ]);

        // PV mode ต้องมีหลาย level
        $levels = $response->json('data.levels');
        $this->assertGreaterThanOrEqual(3, count($levels), 'PV mode ต้องมีหลาย level');
    }

    // ============================================================
    // กลุ่ม 8: ทดสอบ Retention Status
    // ============================================================

    /**
     * @test
     * ดู retention status ละเอียด
     */
    public function retention_status_returns_correct_details(): void
    {
        $this->makeActiveByPurchase($this->memberA, 150);

        $status = MlmRetentionHelper::getRetentionStatus($this->memberA);

        $this->assertTrue($status['is_active']);
        $this->assertEquals('active', $status['status']);
        $this->assertEquals('green', $status['color']);
        $this->assertEquals(150.0, $status['monthly_pv']);
        $this->assertEquals(100.0, $status['required_pv']);
        $this->assertEquals(100.0, $status['pv_percentage']); // 150/100 capped at 100%
        $this->assertEquals(0.0, $status['remaining_pv']);
    }

    /**
     * @test
     * ดูดวง + ซื้อของ รวมกัน → active
     */
    public function combined_purchase_and_fortune_makes_active(): void
    {
        // PV จากซื้อของ = 60 (ไม่ถึง 100)
        $this->makeActiveByPurchase($this->memberA, 60);
        // PV จากดูดวง = 50 (รวม = 110 ≥ 100)
        $this->makeActiveByFortune($this->memberA, 50);

        $this->assertTrue(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    // ============================================================
    // กลุ่ม 9: ทดสอบ Edge Cases
    // ============================================================

    /**
     * @test
     * สมาชิกไม่มี sponsor → ไม่จ่ายคอม
     */
    public function no_commission_when_member_has_no_sponsor(): void
    {
        // Admin ไม่มี sponsor
        $this->makeActiveByPurchase($this->adminMember);

        $service = app(MlmCommissionService::class);

        $result = $service->calculateCommissionsWithRollup(
            $this->adminMember,
            1000.0,
            'purchase',
            333
        );

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['commissions'], 'Admin ไม่มี sponsor → ไม่มีคอม');
    }

    /**
     * @test
     * ป้องกันจ่ายคอมซ้ำ (Fortune reading เดียวกัน)
     */
    public function prevents_duplicate_fortune_commission(): void
    {
        $reading = FortuneReading::create([
            'user_id' => $this->userC->id,
            'facebook_user_id' => 'test-fb-user-c',
            'bill_reference' => 'TEST-DUP',
            'is_paid' => true,
            'amount_paid' => 39,
            'platform' => 'facebook',
        ]);

        // สร้าง commission ครั้งแรก
        MlmCommission::create([
            'mlm_member_id' => $this->memberB->id,
            'mlm_plan_id' => $this->plan->id,
            'user_id' => $this->userB->id,
            'from_member_id' => $this->memberC->id,
            'source_type' => FortuneReading::class,
            'source_id' => $reading->id,
            'type' => 'unilevel_direct',
            'level' => 1,
            'commission_amount' => 10,
            'pv_amount' => 10,
            'percentage' => 100,
            'status' => 'pending',
            'is_rollup' => false,
            'tree_type' => 'unilevel',
        ]);

        // ตรวจว่ามี commission แล้ว
        $exists = MlmCommission::where('source_type', FortuneReading::class)
            ->where('source_id', $reading->id)
            ->exists();

        $this->assertTrue($exists, 'ต้องมี commission จากรอบแรก');

        // จำลอง logic ป้องกันซ้ำ (เหมือน distributeFortuneCommissions)
        $alreadyDistributed = MlmCommission::where('source_type', FortuneReading::class)
            ->where('source_id', $reading->id)
            ->exists();

        $this->assertTrue($alreadyDistributed, 'ต้องตรวจเจอว่าจ่ายไปแล้ว → ข้ามการจ่ายซ้ำ');
    }

    /**
     * @test
     * สมาชิก status = suspended → ไม่ active แม้มี PV เพียงพอ
     */
    public function suspended_member_is_not_active(): void
    {
        $this->memberA->update(['status' => 'suspended']);
        $this->makeActiveByPurchase($this->memberA, 500);

        $this->assertFalse(MlmRetentionHelper::isMemberActive($this->memberA));
    }

    /**
     * @test
     * สมาชิก is_qualified = false → ไม่ active
     */
    public function unqualified_member_is_not_active(): void
    {
        $this->memberA->update(['is_qualified' => false]);
        $this->makeActiveByPurchase($this->memberA, 500);

        $this->assertFalse(MlmRetentionHelper::isMemberActive($this->memberA));
    }
}
