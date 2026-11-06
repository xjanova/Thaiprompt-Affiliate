<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformWallet;
use App\Models\Product;
use App\Models\User;
use App\Services\MlmCalculationService;
use App\Services\OrderDistributionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Money\MoneyTestCase;

/**
 * Budget ค่าคอมต่อออเดอร์ ขั้น 2b (audit F5, F7, F9 · เจ้าของตัดสิน M3)
 *
 * - F7: budget = min(PV × อัตรา × 50%, เงินที่ออเดอร์เก็บเข้า mlm_pool จริง) และ PV เฉลี่ยตามส่วนลดของบรรทัด
 * - F5: ค่าแนะนำตรงจ่ายเฉพาะออเดอร์ที่มี PV > 0 และไม่เกิน budget ของออเดอร์
 * - F9: แถวที่จ่ายไม่ได้ต้องมีเหตุผลบนแถว และจ่ายไม่เกินเงินที่ออเดอร์ต้นทางเก็บเข้ากอง
 *
 * ค่าตั้งเหมือน prod (unilevel 5/3/2/1/1, commission_per_pv 1, max 50%, ค่าแนะนำ 100 บาทออเดอร์แรก)
 * ยกเว้น: ลบแถว unilevel_max_commission_per_level (บน prod เป็น NULL → อ่านเป็น 0 = F6 แก้ในขั้น 2d),
 * ปิด rollup (F8 = ขั้น 2c) และปิดเกณฑ์รักษายอด (ทุกคน active) เพื่อให้เห็นตัวเลข budget ล้วนๆ
 * เรียกเส้นทางเงินจริง OrderDistributionService::processOrderDistribution
 *
 * สายงาน: U4 → U3 → U2 → U1 → S (ผู้แนะนำ) → buyer
 */
class OrderBudgetTest extends MoneyTestCase
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
            'commission_per_pv' => ['1', 'decimal'],
            'max_commission_percentage' => ['50.00', 'decimal'],
            'rollup_enabled' => ['0', 'boolean'],
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
        DB::table('mlm_global_settings')->where('key', 'unilevel_max_commission_per_level')->delete();
        Cache::flush();

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
                'member_code' => 'OB-'.$tag.'-'.Str::random(4),
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

    protected function product(User $seller, float $price, float $pv): Product
    {
        $product = $this->makeProduct($seller, $price);
        $product->forceFill(['pv_value' => $pv])->save();

        return $product->fresh();
    }

    /** ออเดอร์จ่ายแล้ว + คูปองร้านที่หักในบรรทัดสินค้า (แบบที่ checkout ทำ) */
    protected function paidOrderWithStoreCoupon(Product $product, float $discount): Order
    {
        $order = $this->makePaidOrder($this->buyer, [[$product, 1]]);

        if ($discount > 0) {
            $line = (float) $product->price;
            OrderItem::where('order_id', $order->id)->update([
                'discount_amount' => $discount,
                'total' => $line - $discount,
                'seller_earning' => $line - $discount,
            ]);
            DB::table('orders')->where('id', $order->id)->update([
                'discount_amount' => $discount,
                'product_discount' => $discount,
                'shipping_discount' => 0,
                'discount_funded_by' => 'store',
                'total_amount' => $line - $discount,
            ]);
        }

        return $order->fresh();
    }

    protected function distribute(Order $order): array
    {
        return app(OrderDistributionService::class)->processOrderDistribution($order->fresh());
    }

    protected function rows(Order $order)
    {
        return MlmCommission::where('source_type', Order::class)->where('source_id', $order->id)->get();
    }

    protected function liveTotal(Order $order): float
    {
        return round((float) $this->rows($order)->whereNotIn('status', ['cancelled', 'rejected'])->sum('commission_amount'), 2);
    }

    protected function unilevelTotal(Order $order): float
    {
        return round((float) $this->rows($order)->filter(fn ($c) => str_starts_with((string) $c->type, 'unilevel'))->sum('commission_amount'), 2);
    }

    protected function referral(Order $order): ?MlmCommission
    {
        return $this->rows($order)->firstWhere('type', 'direct_referral');
    }

    // ------------------------------------------------------------------ F7

    /** E2E ShopUnilevel S5: คูปองร้าน 100 จาก 1,070 → PV 970, unilevel 116.40, กอง 485 */
    public function test_store_coupon_spreads_pv_proportionally(): void
    {
        $this->settings(['direct_referral_bonus_enabled' => ['0', 'boolean']]);
        $seller = $this->makeSeller(10, true);
        $order = $this->paidOrderWithStoreCoupon($this->product($seller, 1070, 1070), 100);

        $this->distribute($order);

        $order->refresh();
        $this->assertEqualsWithDelta(970.00, (float) $order->mlm_pv_total, 0.001, 'PV ต้องลดตามส่วนลด 970/1070');
        $this->assertEqualsWithDelta(485.00, (float) $order->mlm_commission_total, 0.001);
        $this->assertEqualsWithDelta(485.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertEqualsWithDelta(116.40, $this->unilevelTotal($order), 0.001, 'unilevel = 970 × 12%');
        $this->assertEqualsWithDelta(970.00, (float) $this->m['buyer']->fresh()->total_pv, 0.001);
    }

    /**
     * E2E ShopUnilevel S5b: คูปองร้าน 970 (ผู้ซื้อจ่าย 100) → กองได้ 50
     * คอมทั้งหมดต้องไม่เกิน 50 และพออนุมัติ+จ่ายทั้งหมดแล้วต้องจ่ายครบ ไม่ค้าง approved
     */
    public function test_heavy_coupon_commissions_never_exceed_pool_collected_and_all_get_paid(): void
    {
        $seller = $this->makeSeller(10, true);
        $order = $this->paidOrderWithStoreCoupon($this->product($seller, 1070, 1070), 970);

        $this->distribute($order);

        $this->assertEqualsWithDelta(50.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertEqualsWithDelta(100.00, (float) $order->fresh()->mlm_pv_total, 0.001);
        $this->assertEqualsWithDelta(12.00, $this->unilevelTotal($order), 0.001, 'unilevel = 100 PV × 12%');
        $this->assertEqualsWithDelta(38.00, (float) $this->referral($order)->commission_amount, 0.001, 'ค่าแนะนำได้เท่าที่เหลือใน budget 50');
        $this->assertEqualsWithDelta(50.00, $this->liveTotal($order), 0.001);

        MlmCommission::where('status', 'pending')->get()->each->approve();
        $paid = (new MlmCalculationService)->payApprovedCommissions();

        $this->assertSame($this->rows($order)->count(), $paid);
        $this->assertSame(0, MlmCommission::where('status', 'approved')->count(), 'ไม่มีแถวค้าง approved');
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    /** GP สูงทำให้กองเก็บได้ 334.58 ต่ำกว่า budget จาก PV (500) → budget ต้องเป็นเงินที่เก็บได้จริง */
    public function test_budget_is_the_money_the_order_put_into_the_pool_not_pv(): void
    {
        $this->settings(['direct_referral_bonus_amount' => ['300.00', 'decimal']]);
        $seller = $this->makeSeller(60, true);
        $order = $this->paidOrderWithStoreCoupon($this->product($seller, 1000, 1000), 0);

        $this->distribute($order);

        // กอง = min(PV 1000, 50% × 1000, 1000 − GP 600 − VAT 65.42) = 334.58
        $this->assertEqualsWithDelta(334.58, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertEqualsWithDelta(120.00, $this->unilevelTotal($order), 0.001);
        $this->assertEqualsWithDelta(214.58, (float) $this->referral($order)->commission_amount, 0.001);
        $this->assertEqualsWithDelta(334.58, $this->liveTotal($order), 0.001);
    }

    // ------------------------------------------------------------------ F5

    /**
     * E2E ShopUnilevel S3b / BinaryReferral S4d / PaymentPaths 11: สินค้า PV 0 → กองได้ 0 → ห้ามมีค่าแนะนำ
     * และสิทธิ์ "ออเดอร์แรก" ต้องยังอยู่ ออเดอร์ถัดไปที่มี PV จึงได้ค่าแนะนำ
     */
    public function test_zero_pv_order_creates_no_referral_bonus_and_keeps_first_order_right(): void
    {
        $seller = $this->makeSeller(10, true);

        $first = $this->paidOrderWithStoreCoupon($this->product($seller, 50, 0), 0);
        $this->distribute($first);

        $this->assertNull($this->referral($first), 'ออเดอร์ PV 0 ต้องไม่สร้างค่าแนะนำ');
        $this->assertSame(0, $this->rows($first)->count());
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('mlm_pool'), 0.001);

        $second = $this->paidOrderWithStoreCoupon($this->product($seller, 1000, 1000), 0);
        $this->distribute($second);

        $bonus = $this->referral($second);
        $this->assertNotNull($bonus, 'ออเดอร์แรกที่มี PV ต้องได้ค่าแนะนำ');
        $this->assertSame($this->m['S']->id, (int) $bonus->mlm_member_id);
        $this->assertEqualsWithDelta(100.00, (float) $bonus->commission_amount, 0.001);
        $this->assertLessThanOrEqual($this->platformBalance('mlm_pool') + 0.001, $this->liveTotal($second));
    }

    /** budget ถูก unilevel ใช้หมด → ค่าแนะนำถูกยกเลิกพร้อมเหตุผล (ไม่ค้างเป็นแถวรอจ่าย) และคืนสิทธิ์ออเดอร์แรก */
    public function test_referral_is_cancelled_with_reason_when_unilevel_uses_the_whole_budget(): void
    {
        // unilevel 50% ของ PV = budget ทั้งก้อน
        $this->settings(['unilevel_levels' => [json_encode([['level' => 1, 'percentage' => 50]]), 'json']]);
        $seller = $this->makeSeller(10, true);
        $order = $this->paidOrderWithStoreCoupon($this->product($seller, 200, 200), 0);

        $this->distribute($order);

        $bonus = $this->referral($order);
        $this->assertNotNull($bonus);
        $this->assertSame('cancelled', $bonus->status);
        $this->assertStringContainsString('budget', (string) $bonus->notes);
        $this->assertEqualsWithDelta(100.00, $this->liveTotal($order), 0.001, 'คอมรวม = กอง 100');
        $this->assertEqualsWithDelta(100.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    // ------------------------------------------------------------------ F7 + binary (2a)

    /** binary ใช้ได้เฉพาะเงินที่ออเดอร์เก็บเข้ากองจริง: กอง 33.46 < ค่าคู่ 40 → ไม่จ่ายคู่จากออเดอร์นี้ */
    public function test_binary_pair_is_limited_by_the_pool_collected(): void
    {
        $this->settings([
            'unilevel_enabled' => ['0', 'boolean'],
            'direct_referral_bonus_enabled' => ['0', 'boolean'],
            'binary_enabled' => ['1', 'boolean'],
            'binary_pair_commission' => ['40.00', 'decimal'],
            'binary_pair_unit_pv' => ['100', 'decimal'],
            'binary_pairing_type' => ['1:1', 'string'],
            'binary_max_pairs_per_day' => ['10', 'integer'],
            'binary_max_commission_per_day' => [null, 'decimal'],
            'binary_flush_mode' => ['percentage', 'string'],
            'binary_flush_percentage' => ['100.00', 'decimal'],
            'binary_carry_forward_enabled' => ['0', 'boolean'],
        ]);
        $g = $this->m['S'];
        $left = $this->binaryChild($g, 'left');
        $right = $this->binaryChild($g, 'right');
        $seller = $this->makeSeller(60, true);

        $this->buyAs($left, $this->product($seller, 100, 100));
        $orderR = $this->buyAs($right, $this->product($seller, 100, 100));

        // กองของออเดอร์ขวา = min(100, 50, 100 − 60 − 6.54) = 33.46
        $this->assertEqualsWithDelta(33.46, (float) $orderR->fresh()->mlm_commission_total, 0.001);
        $this->assertSame(0, $this->rows($orderR)->where('type', 'binary_pair')->count(), 'ค่าคู่ 40 เกินกอง 33.46');
        $this->assertLessThanOrEqual(33.461, $this->liveTotal($orderR));
    }

    protected function binaryChild(MlmMember $parent, string $side): MlmMember
    {
        $user = $this->makeUser('ขา '.$side);

        return MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'OB-B'.$side.'-'.Str::random(4),
            'original_sponsor_id' => $parent->id,
            'unilevel_sponsor_id' => $parent->id,
            'binary_parent_id' => $parent->id,
            'binary_position' => $side,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
        ]);
    }

    protected function buyAs(MlmMember $member, Product $product): Order
    {
        $order = $this->makePaidOrder(User::find($member->user_id), [[$product, 1]]);
        $this->distribute($order);

        return $order;
    }

    // ------------------------------------------------------------------ F9

    /** กองไม่พอ → แถวยังเป็น approved แต่ต้องมีเหตุผล · เติมกองแล้วจ่ายได้ และเหตุผลถูกล้าง */
    public function test_unpayable_row_records_reason_and_clears_it_once_paid(): void
    {
        $row = $this->approvedRow(['commission_amount' => 120]);

        $service = new MlmCalculationService;
        $this->assertSame(0, $service->payApprovedCommissions([$row->id]));

        $row->refresh();
        $this->assertSame('approved', $row->status);
        $this->assertSame('pool_insufficient', $row->payout_blocked_reason);
        $this->assertNotNull($row->payout_blocked_at);
        $this->assertSame([$row->id => 'pool_insufficient'], $service->getLastPayoutBlocked());

        PlatformWallet::getMlmPoolWallet()->addFunds(500, 'test_funding');
        $this->assertSame(1, $service->payApprovedCommissions([$row->id]));

        $row->refresh();
        $this->assertSame('paid', $row->status);
        $this->assertNull($row->payout_blocked_reason);
        $this->assertNull($row->payout_blocked_at);
    }

    /**
     * E2E BinaryReferral S4e / S1c: แถวเก่าของออเดอร์ที่เก็บเข้ากอง 0 ห้ามดูดเงินกองของออเดอร์อื่น
     * แม้กองรวมจะมีเงินพอ
     */
    public function test_payout_never_exceeds_the_source_order_pool(): void
    {
        $seller = $this->makeSeller(10, true);
        $order = $this->paidOrderWithStoreCoupon($this->product($seller, 50, 0), 0);
        $this->distribute($order);
        $this->assertEqualsWithDelta(0.00, (float) $order->fresh()->mlm_commission_total, 0.001);

        // แถวค่าแนะนำแบบที่โค้ดเดิมสร้างไว้แล้ว (ก่อน F5) + กองมีเงินจากออเดอร์อื่น 1,000
        $row = $this->approvedRow(['source_type' => Order::class, 'source_id' => $order->id, 'commission_amount' => 100]);
        PlatformWallet::getMlmPoolWallet()->addFunds(1000, 'other_orders');

        $service = new MlmCalculationService;
        $this->assertSame(0, $service->payApprovedCommissions([$row->id]));

        $row->refresh();
        $this->assertSame('approved', $row->status);
        $this->assertSame('order_budget_exceeded', $row->payout_blocked_reason);
        $this->assertEqualsWithDelta(1000.00, $this->platformBalance('mlm_pool'), 0.001);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance(User::find($row->user_id)), 0.001);
    }

    /** ปุ่ม "จ่ายทั้งหมด" ต้องบอกว่าจ่ายไม่ได้กี่รายการ เพราะอะไร (เดิมตอบสำเร็จเงียบๆ) */
    public function test_pay_all_button_reports_blocked_rows(): void
    {
        $this->approvedRow(['commission_amount' => 80]);
        $admin = $this->makeUser('แอดมิน');
        $admin->forceFill(['role' => 'admin', 'is_super_admin' => true])->save();

        $this->actingAs($admin)
            ->post(route('admin.mlm.commissions.pay-all'))
            ->assertRedirect(route('admin.mlm.commissions.index'))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'จ่ายไม่ได้ 1 รายการ')
                && str_contains($msg, 'กองทุน MLM มีเงินไม่พอจ่าย'));
    }

    protected function approvedRow(array $attrs): MlmCommission
    {
        return MlmCommission::create(array_merge([
            'mlm_member_id' => $this->m['S']->id,
            'mlm_plan_id' => $this->plan->id,
            'user_id' => $this->m['S']->user_id,
            'from_member_id' => $this->m['buyer']->id,
            'type' => 'direct_referral',
            'level' => 1,
            'pv_amount' => 0,
            'sales_amount' => 100,
            'commission_amount' => 100,
            'status' => 'approved',
            'approved_at' => now(),
        ], $attrs));
    }
}
