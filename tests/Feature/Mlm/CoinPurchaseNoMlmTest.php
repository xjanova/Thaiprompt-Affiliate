<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\MlmCommissionService;
use App\Services\OrderDistributionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Money\MoneyTestCase;

/**
 * M15: ซื้อด้วย Coins ไม่ได้ PV และไม่เกิดคอม MLM (ขั้น 2f)
 *
 * ออเดอร์ Coins ของ Official Shop เป็นราคา 0 บาท (subtotal/total 0) แต่สินค้ามี pv_value
 * เดิม lineSaleRatio คืน 1 เมื่อยอดก่อนส่วนลดเป็น 0 → ผู้ซื้อได้ PV เต็ม (นับรักษายอด) ทั้งที่ไม่มีเงินบาทเข้า
 *
 * สายงาน: U2 → U1 → buyer
 */
class CoinPurchaseNoMlmTest extends MoneyTestCase
{
    /** @var array<string, MlmMember> */
    protected array $m = [];

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'mlm_enabled' => ['1', 'boolean'],
            'unilevel_enabled' => ['1', 'boolean'],
            'unilevel_levels' => [json_encode([['level' => 1, 'percentage' => 5], ['level' => 2, 'percentage' => 3]]), 'json'],
            'unilevel_max_depth' => ['10', 'integer'],
            'unilevel_max_commission_per_level' => [null, 'decimal'],
            'commission_per_pv' => ['1', 'decimal'],
            'max_commission_percentage' => ['50.00', 'decimal'],
            'rollup_enabled' => ['0', 'boolean'],
            'volume_retention_enabled' => ['0', 'boolean'],
            'binary_enabled' => ['0', 'boolean'],
            'direct_referral_bonus_enabled' => ['1', 'boolean'],
            'direct_referral_bonus_type' => ['fixed', 'string'],
            'direct_referral_bonus_amount' => ['100.00', 'decimal'],
            'direct_referral_bonus_min_order' => ['0', 'decimal'],
            'direct_referral_bonus_first_order_only' => ['1', 'boolean'],
        ] as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        Cache::flush();

        $plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);

        $prev = null;
        foreach (['U2', 'U1', 'buyer'] as $tag) {
            $user = $this->makeUser('สมาชิก '.$tag);
            $this->m[$tag] = MlmMember::create([
                'user_id' => $user->id, 'mlm_plan_id' => $plan->id, 'member_code' => 'CN-'.$tag.'-'.Str::random(4),
                'original_sponsor_id' => $prev?->id, 'unilevel_sponsor_id' => $prev?->id,
                'status' => 'active', 'is_qualified' => true, 'joined_at' => now()->subMonth(),
            ]);
            $prev = $this->m[$tag];
        }
        $this->buyer = User::find($this->m['buyer']->user_id);
    }

    /** ออเดอร์แบบที่ OfficialShopController::purchaseWithCoin สร้าง (ราคา 0 บาท จ่ายด้วย Coins) */
    protected function coinOrder(): Order
    {
        $seller = $this->makeSeller(10, false);
        $product = $this->makeProduct($seller, 1000);
        $product->forceFill(['pv_value' => 1000])->save();

        $order = Order::create([
            'user_id' => $this->buyer->id, 'status' => 'completed', 'payment_method' => 'coins', 'payment_status' => 'paid',
            'paid_at' => now(), 'subtotal' => 0, 'shipping_fee' => 0, 'total_amount' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'seller_id' => $seller->id,
            'product_name' => $product->name, 'product_sku' => $product->sku, 'unit_price' => 0, 'quantity' => 1,
            'subtotal' => 0, 'discount_amount' => 0, 'total' => 0, 'commission_rate' => 0, 'commission_amount' => 0,
            'seller_earning' => 0, 'status' => 'pending',
        ]);

        return $order->fresh();
    }

    public function test_coin_order_distribution_records_no_pv_and_no_commission(): void
    {
        $order = $this->coinOrder();

        app(OrderDistributionService::class)->processOrderDistribution($order);

        $this->assertSame(0, MlmPvTransaction::count(), 'M15: ซื้อด้วย Coins ต้องไม่ได้ PV');
        $this->assertSame(0, MlmCommission::count(), 'M15: ซื้อด้วย Coins ต้องไม่เกิดคอม');
        $this->assertEqualsWithDelta(0.00, (float) $this->m['buyer']->fresh()->total_pv, 0.001);
        $this->assertEqualsWithDelta(0.00, (float) $order->fresh()->mlm_pv_total, 0.001);
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    /** ผู้เรียก engine ตรงๆ ด้วย PV ที่คิดเอง ก็ต้องถูกกันไว้ที่ engine (defense in depth) */
    public function test_engine_refuses_coin_order_even_with_pv_given(): void
    {
        $order = $this->coinOrder();

        $result = app(MlmCommissionService::class)->processOrderCommissions($order, ['total_pv' => 1000, 'pv_amount_thb' => 500]);

        $this->assertNull($result['direct_referral']);
        $this->assertSame([], $result['unilevel']);
        $this->assertSame(0, MlmCommission::count());
    }
}
