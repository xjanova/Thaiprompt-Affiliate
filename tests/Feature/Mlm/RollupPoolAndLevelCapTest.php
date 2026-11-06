<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MlmCalculationService;
use App\Services\MlmCommissionClawbackService;
use App\Services\MlmCommissionService;
use App\Services\OrderDistributionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Money\MoneyTestCase;

/**
 * ขั้น 2c + 2d ของ audit MLM (ต้องออกพร้อมกัน)
 *
 * - F8 / M4: เงินโรลอัพที่ไม่มีผู้รับ อยู่ใน mlm_pool เป็นแถว "pooled" ที่ไม่มีเจ้าของ
 *   ห้ามเป็นแถวที่อนุมัติ/จ่ายเข้ากระเป๋าคน (เดิมเป็นของ member #1) และห้าม fallback ไปผู้ซื้อ
 *   ตัวกันคำนวณซ้ำต้องนับแถว pool_bonus ด้วย
 * - F6: ค่าตั้ง unilevel_max_commission_per_level = NULL (ค่าบน prod) ต้องแปลว่า "ไม่จำกัด" ไม่ใช่ 0
 *   ผลข้างเคียงที่ตั้งใจ: ค่าแนะนำตรงถูกจำกัดด้วย budget ที่เหลือหลัง unilevel (UiParity 100 → 76)
 *
 * ค่าตั้งเหมือน prod: unilevel 5/3/2/1/1, commission_per_pv 1, max 50%, ค่าแนะนำ 100 บาทออเดอร์แรก,
 * rollup เปิด + rollup_to_pool_enabled เปิด และ **คงแถวเพดานต่อชั้นที่เป็น NULL ไว้** แบบ prod
 *
 * สายงาน: U4 → U3 → U2 → U1 → S (ผู้แนะนำ) → buyer
 */
class RollupPoolAndLevelCapTest extends MoneyTestCase
{
    protected MlmPlan $plan;

    /** @var array<string, MlmMember> */
    protected array $m = [];

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings([
            'mlm_enabled' => ['1', 'boolean'],
            'unilevel_enabled' => ['1', 'boolean'],
            'unilevel_levels' => [json_encode([
                ['level' => 1, 'percentage' => 5], ['level' => 2, 'percentage' => 3], ['level' => 3, 'percentage' => 2],
                ['level' => 4, 'percentage' => 1], ['level' => 5, 'percentage' => 1],
            ]), 'json'],
            'unilevel_max_depth' => ['10', 'integer'],
            // 🔑 ค่าบน prod: แถวมีอยู่ แต่ค่าเป็น NULL (F6)
            'unilevel_max_commission_per_level' => [null, 'decimal'],
            'commission_per_pv' => ['1', 'decimal'],
            'max_commission_percentage' => ['50.00', 'decimal'],
            'rollup_enabled' => ['1', 'boolean'],
            'rollup_prevent_duplicate' => ['1', 'boolean'],
            'rollup_max_levels' => ['10', 'integer'],
            'rollup_max_per_member' => ['1', 'integer'],
            'rollup_to_pool_enabled' => ['1', 'boolean'],
            // ปิดเกณฑ์รักษายอด → active/inactive ตัดสินจาก status ของสมาชิกตรงๆ
            'volume_retention_enabled' => ['0', 'boolean'],
            'binary_enabled' => ['0', 'boolean'],
            'genealogy_enabled' => ['1', 'boolean'],
            'direct_referral_bonus_enabled' => ['1', 'boolean'],
            'direct_referral_bonus_type' => ['fixed', 'string'],
            'direct_referral_bonus_amount' => ['100.00', 'decimal'],
            'direct_referral_bonus_min_order' => ['0', 'decimal'],
            'direct_referral_bonus_max_per_order' => [null, 'decimal'],
            'direct_referral_bonus_first_order_only' => ['1', 'boolean'],
        ]);

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);

        $prev = null;
        foreach (['U4', 'U3', 'U2', 'U1', 'S', 'buyer'] as $tag) {
            $user = $this->makeUser('สมาชิก '.$tag);
            $this->m[$tag] = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $this->plan->id,
                'member_code' => 'RP-'.$tag.'-'.Str::random(4),
                'original_sponsor_id' => $prev?->id,
                'unilevel_sponsor_id' => $prev?->id,
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now()->subMonth(),
            ]);
            $prev = $this->m[$tag];
        }
        $this->buyer = User::find($this->m['buyer']->user_id);
    }

    /** ตั้งค่า MLM ตรงๆ ใน DB (รองรับค่า NULL แบบ prod) แล้วล้าง cache */
    protected function settings(array $map): void
    {
        foreach ($map as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        Cache::flush();
    }

    /** ทำให้สมาชิกในรายการไม่ active (เกณฑ์รักษายอดปิด → ดูจาก status) */
    protected function deactivate(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->m[$tag]->forceFill(['status' => 'inactive'])->save();
        }
    }

    protected function product(float $price, float $pv): Product
    {
        $product = $this->makeProduct($this->makeSeller(10, true), $price);
        $product->forceFill(['pv_value' => $pv])->save();

        return $product->fresh();
    }

    protected function paidOrder(float $price, float $pv): Order
    {
        $order = $this->makePaidOrder($this->buyer, [[$this->product($price, $pv), 1]]);
        app(OrderDistributionService::class)->processOrderDistribution($order->fresh());

        return $order->fresh();
    }

    protected function rows(Order $order)
    {
        return MlmCommission::where('source_type', Order::class)->where('source_id', $order->id)->get();
    }

    protected function amountTo(Order $order, string $tag, ?string $type = null): float
    {
        return round((float) $this->rows($order)
            ->where('mlm_member_id', $this->m[$tag]->id)
            ->when($type, fn ($c) => $c->where('type', $type))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('commission_amount'), 2);
    }

    protected function pooled(Order $order)
    {
        return $this->rows($order)->where('type', 'pool_bonus');
    }

    protected function approveAndPayAll(): int
    {
        $service = new MlmCalculationService;
        $service->approvePendingCommissions();

        return $service->payApprovedCommissions();
    }

    // ================================================================== F6

    /** E2E ShopUnilevel S1 / PaymentPaths 13: แถวเพดาน NULL → จ่ายตามสูตรเต็ม 53.5/32.1/21.4/10.7/10.7 */
    public function test_null_level_cap_means_no_cap(): void
    {
        $this->assertNull(MlmGlobalSetting::positiveCap('unilevel_max_commission_per_level'));

        $order = $this->paidOrder(1070, 1070);

        $this->assertEqualsWithDelta(535.00, (float) $order->mlm_commission_total, 0.001);
        $this->assertEqualsWithDelta(53.50, $this->amountTo($order, 'S', 'unilevel_direct'), 0.001);
        $this->assertEqualsWithDelta(32.10, $this->amountTo($order, 'U1'), 0.001);
        $this->assertEqualsWithDelta(21.40, $this->amountTo($order, 'U2'), 0.001);
        $this->assertEqualsWithDelta(10.70, $this->amountTo($order, 'U3'), 0.001);
        $this->assertEqualsWithDelta(10.70, $this->amountTo($order, 'U4'), 0.001);
        $this->assertEqualsWithDelta(100.00, $this->amountTo($order, 'S', 'direct_referral'), 0.001);
        $this->assertSame(0, $this->pooled($order)->count());
    }

    /** ค่า 0 / ค่าว่าง = ไม่จำกัด เหมือน NULL · ค่า > 0 ยังจำกัดได้ตามเดิม */
    public function test_positive_level_cap_still_limits_each_level(): void
    {
        $this->settings(['unilevel_max_commission_per_level' => ['20', 'decimal']]);
        $this->assertSame(20.0, MlmGlobalSetting::positiveCap('unilevel_max_commission_per_level'));

        $order = $this->paidOrder(1070, 1070);

        $this->assertEqualsWithDelta(20.00, $this->amountTo($order, 'S', 'unilevel_direct'), 0.001, 'L1 53.5 ถูกจำกัดที่ 20');
        $this->assertEqualsWithDelta(20.00, $this->amountTo($order, 'U1'), 0.001, 'L2 32.1 ถูกจำกัดที่ 20');
        $this->assertEqualsWithDelta(20.00, $this->amountTo($order, 'U2'), 0.001, 'L3 21.4 ถูกจำกัดที่ 20');
        $this->assertEqualsWithDelta(10.70, $this->amountTo($order, 'U3'), 0.001);

        $this->settings(['unilevel_max_commission_per_level' => ['0', 'decimal']]);
        $this->assertNull(MlmGlobalSetting::positiveCap('unilevel_max_commission_per_level'));
        $this->settings(['unilevel_max_commission_per_level' => ['', 'decimal']]);
        $this->assertNull(MlmGlobalSetting::positiveCap('unilevel_max_commission_per_level'));
    }

    /**
     * E2E UiParity S1: ออเดอร์ PV 200 → budget 100, unilevel 24 → ค่าแนะนำ 100 เหลือ 76
     * (ผลข้างเคียงที่ตั้งใจของการแก้ F6 — ก่อนแก้ unilevel เป็น 0 ค่าแนะนำจึงได้เต็ม 100)
     */
    public function test_referral_bonus_shrinks_to_what_the_budget_has_left_after_unilevel(): void
    {
        $order = $this->paidOrder(200, 200);

        $this->assertEqualsWithDelta(100.00, (float) $order->mlm_commission_total, 0.001);
        $this->assertEqualsWithDelta(24.00, round((float) $this->rows($order)
            ->filter(fn ($c) => str_starts_with((string) $c->type, 'unilevel'))->sum('commission_amount'), 2), 0.001);
        $this->assertEqualsWithDelta(76.00, $this->amountTo($order, 'S', 'direct_referral'), 0.001);
    }

    /** หน้าจำลองฝั่งสมาชิก (MlmDashboardController) ต้องอ่านเพดาน NULL แบบเดียวกับ engine */
    public function test_member_preview_reports_no_level_cap_for_null_setting(): void
    {
        $this->actingAs(User::find($this->m['S']->user_id))
            ->postJson(route('user.mlm.preview-calculation'), ['sales_amount' => 1000])
            ->assertOk()
            ->assertJsonPath('constraints.max_per_level', null);
    }

    // ================================================================== F8

    /**
     * E2E ShopUnilevel S3: ทุก upline ไม่ active → เงิน unilevel 128.40 อยู่ในกองเป็นแถว pooled ที่ไม่มีเจ้าของ
     * พออนุมัติทั้งหมด + จ่ายทั้งหมด ไม่มีใครได้เงินนี้ (เดิมเข้ากระเป๋า member #1)
     */
    public function test_rollup_without_recipient_stays_in_the_pool_and_is_never_paid(): void
    {
        $this->deactivate(['U4', 'U3', 'U2', 'U1', 'S']);
        $order = $this->paidOrder(1070, 1070);

        $pool = $this->pooled($order);
        $this->assertSame(5, $pool->count());
        $this->assertEqualsWithDelta(128.40, round((float) $pool->sum('commission_amount'), 2), 0.001);
        foreach ($pool as $row) {
            $this->assertSame(MlmCommission::STATUS_POOLED, $row->status);
            $this->assertNull($row->mlm_member_id, 'เงินกองต้องไม่เป็นของสมาชิกคนใด');
            $this->assertNull($row->user_id);
            $this->assertSame('pool', $row->tree_type);
        }
        // ค่าแนะนำตรงยังเป็นของ S (ไม่มีเงื่อนไข active)
        $this->assertEqualsWithDelta(100.00, $this->amountTo($order, 'S', 'direct_referral'), 0.001);

        $poolBefore = $this->platformBalance('mlm_pool');
        $this->assertSame(1, $this->approveAndPayAll(), 'จ่ายได้เฉพาะค่าแนะนำตรง');

        $this->assertSame(5, MlmCommission::where('status', MlmCommission::STATUS_POOLED)->count(), 'แถว pooled ห้ามถูกอนุมัติ/จ่าย');
        $this->assertEqualsWithDelta($poolBefore - 100.00, $this->platformBalance('mlm_pool'), 0.001);
        foreach (['U4', 'U3', 'U2', 'U1'] as $tag) {
            $this->assertEqualsWithDelta(0.00, $this->walletBalance(User::find($this->m[$tag]->user_id)), 0.001);
        }
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($this->buyer), 0.001, 'ห้าม fallback ไปผู้ซื้อ');
    }

    /** แม้ปิด rollup_to_pool_enabled ก็ห้ามส่งเงินที่ไม่มีผู้รับให้ member #1 เป็นค่าคอมที่จ่ายได้ (M4) */
    public function test_rollup_without_recipient_never_goes_to_member_one_when_pool_switch_is_off(): void
    {
        $this->settings(['rollup_to_pool_enabled' => ['0', 'boolean']]);
        $this->deactivate(['U4', 'U3', 'U2', 'U1', 'S']);
        $memberOne = MlmMember::find(1);

        $order = $this->paidOrder(1070, 1070);

        $payable = $this->rows($order)->whereNotIn('status', [MlmCommission::STATUS_POOLED, 'cancelled', 'rejected']);
        $this->assertSame(['direct_referral'], $payable->pluck('type')->unique()->values()->all());
        $this->assertSame(0, $this->rows($order)->where('type', 'unilevel_rollup')->count());
        if ($memberOne) {
            $this->assertSame(0, $payable->where('mlm_member_id', 1)->where('type', '!=', 'direct_referral')->count());
        }
        $this->assertEqualsWithDelta(128.40, round((float) $this->pooled($order)->sum('commission_amount'), 2), 0.001);
    }

    /**
     * rollup ที่มีผู้รับยังทำงานตามเดิม: L2 + L4 ไม่ active → ไปคน active ถัดไป ไม่มีแถว pooled
     * F29 / M5: แม้สวิตช์ compression เปิดอยู่ (ค่าบน prod) engine ต้องจ่ายแบบ rollup — สวิตช์ถูกซ่อนจากหน้าตั้งค่า
     */
    public function test_rollup_with_an_active_recipient_still_pays_that_member(): void
    {
        $this->settings(['unilevel_compression_enabled' => ['1', 'boolean']]);
        $this->deactivate(['U1', 'U3']);
        $order = $this->paidOrder(1070, 1070);

        // U1 (L2 32.1) ม้วนขึ้นไป U2 · U3 (L4 10.7) ม้วนขึ้นไป U4
        $this->assertEqualsWithDelta(32.10, $this->amountTo($order, 'U2', 'unilevel_rollup'), 0.001);
        $this->assertEqualsWithDelta(21.40, $this->amountTo($order, 'U2', 'unilevel_indirect'), 0.001);
        $this->assertEqualsWithDelta(10.70, $this->amountTo($order, 'U4', 'unilevel_rollup'), 0.001);
        $this->assertEqualsWithDelta(0.00, $this->amountTo($order, 'U1'), 0.001);
        $this->assertEqualsWithDelta(0.00, $this->amountTo($order, 'U3'), 0.001);
        $this->assertSame(0, $this->pooled($order)->count());
    }

    /** E2E ShopUnilevel S6b: เรียกคำนวณซ้ำเมื่อทุกแถวเป็น pool_bonus → ต้องไม่สร้างแถวใหม่ */
    public function test_rerun_is_blocked_when_the_order_only_has_pool_rows(): void
    {
        $this->settings(['direct_referral_bonus_enabled' => ['0', 'boolean']]);
        $this->deactivate(['U4', 'U3', 'U2', 'U1', 'S']);
        $order = $this->paidOrder(1070, 1070);
        $countBefore = MlmCommission::count();
        $this->assertSame(5, $this->pooled($order)->count());

        (new MlmCommissionService)->processOrderCommissions($order, ['total_pv' => 1070, 'pv_amount_thb' => 535]);

        $this->assertSame($countBefore, MlmCommission::count());
    }

    /** คืนเงินออเดอร์ที่มีแถว pooled (ไม่มีเจ้าของ) ต้องไม่พัง และแถว pooled ถูกยกเลิกโดยไม่สร้างหนี้ให้ใคร */
    public function test_refund_cancels_pooled_rows_without_touching_any_wallet(): void
    {
        $this->deactivate(['U4', 'U3', 'U2', 'U1']);
        $order = $this->paidOrder(1070, 1070);
        $this->assertSame(4, $this->pooled($order)->count(), 'L2-L5 ไม่มีผู้รับ');

        $result = (new MlmCommissionClawbackService)->clawbackOrderCommissions($order);

        $this->assertSame(0, $this->rows($order)->where('status', MlmCommission::STATUS_POOLED)->count());
        $this->assertSame(4, $this->pooled($order)->where('status', 'cancelled')->count());
        $this->assertSame([], $result['debts_created']);
        $this->assertSame('cancelled', $this->rows($order)->firstWhere('type', 'unilevel_direct')->status);
    }

    /** แถว pooled อนุมัติทีละแถวไม่ได้ (ปุ่มอนุมัติในหน้าแอดมิน) */
    public function test_pooled_row_cannot_be_approved_individually(): void
    {
        $this->deactivate(['U4', 'U3', 'U2', 'U1', 'S']);
        $order = $this->paidOrder(1070, 1070);
        $row = $this->pooled($order)->first();

        $this->assertFalse($row->approve());
        $this->assertSame(0, (new MlmCalculationService)->approvePendingCommissions([$row->id]));
        $this->assertSame(MlmCommission::STATUS_POOLED, $row->fresh()->status);
    }

    /**
     * migration แปลงแถวโรลอัพเข้ากองเดิม (pending/approved ของ member #1) เป็น pooled — แถว paid ไม่แตะ
     * และซ่อนสวิตช์ compression ที่ไม่มีผล (F29 / M5)
     */
    public function test_migration_converts_legacy_pool_rows_to_pooled(): void
    {
        $base = [
            'mlm_member_id' => $this->m['U4']->id, 'mlm_plan_id' => $this->plan->id, 'user_id' => $this->m['U4']->user_id,
            'from_member_id' => $this->m['buyer']->id, 'type' => 'pool_bonus', 'level' => 2, 'pv_amount' => 1070,
            'commission_amount' => 32.10, 'is_rollup' => true, 'tree_type' => 'pool',
        ];
        $pending = MlmCommission::create($base + ['status' => 'pending']);
        $approved = MlmCommission::create($base + ['status' => 'approved']);
        $paid = MlmCommission::create($base + ['status' => 'paid']);
        // แถว pool_bonus ของรอบแจกกองรายสัปดาห์ (ไม่ใช่โรลอัพ) ต้องไม่ถูกแตะ
        $share = MlmCommission::create(array_merge($base, ['status' => 'pending', 'is_rollup' => false]));

        // F29 / M5: สวิตช์ compression ที่มองเห็นได้ (แบบ prod) ต้องถูกซ่อน ค่าเดิมไม่ถูกแก้
        DB::table('mlm_global_settings')->updateOrInsert(['key' => 'unilevel_compression_enabled'], ['value' => '1', 'type' => 'boolean', 'is_visible' => true]);

        $migration = require database_path('migrations/2026_11_03_140000_add_pooled_status_to_mlm_commissions_table.php');
        $migration->up();

        $switch = DB::table('mlm_global_settings')->where('key', 'unilevel_compression_enabled')->first();
        $this->assertSame(0, (int) $switch->is_visible, 'สวิตช์ compression ไม่มีผลกับเงินจริง ต้องถูกซ่อน');
        $this->assertSame('1', (string) $switch->value);

        $this->assertSame(MlmCommission::STATUS_POOLED, $pending->fresh()->status);
        $this->assertSame(MlmCommission::STATUS_POOLED, $approved->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('pending', $share->fresh()->status);
    }
}
