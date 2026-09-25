<?php

namespace Tests\Feature\Money;

use App\Models\EarningsLedger;
use App\Models\MlmGlobalSetting;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Services\OrderDistributionService;
use App\Services\SellerPayoutService;
use Illuminate\Support\Facades\Cache;

/**
 * แบ่งเงินออเดอร์ e-commerce ด้วย PricingEngine
 *
 * - GP มาจากแพลตฟอร์ม (ร้าน/แพ็กเกจ/ค่ากลาง) ไม่ใช่ products.commission_rate ที่ผู้ขายตั้ง
 * - แบ่งซ้ำ = จ่ายครั้งเดียว
 * - ออเดอร์หลายผู้ขาย = ledger ครบทุกคน (unique key รวม user_id)
 * - สินค้าไม่มี PV = ไม่หักค่าแนะนำเลย แม้เปิด MLM (เดิมหัก 100%)
 * - COD ส่งถึงแต่ยังไม่บันทึกรับเงิน = ยังไม่แบ่ง
 */
class OrderDistributionTest extends MoneyTestCase
{
    public function test_single_seller_uses_platform_gp_and_holds_net_in_escrow(): void
    {
        $seller = $this->makeSeller(12.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 500);
        $order = $this->makePaidOrder($buyer, [[$product, 2]], 50);

        $result = app(OrderDistributionService::class)->processOrderDistribution($order);

        $this->assertArrayNotHasKey('skipped', $result);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertSame((int) $seller->id, (int) $ledger->user_id);
        $this->assertEqualsWithDelta(1050.00, (float) $ledger->gross_amount, 0.001); // 1000 + ค่าส่ง 50
        $this->assertEqualsWithDelta(120.00, (float) $ledger->platform_fee, 0.001);  // GP 12% ของร้าน ไม่ใช่ 0% ที่ผู้ขายตั้ง
        $this->assertEqualsWithDelta(0.00, (float) $ledger->vat_amount, 0.001);      // ร้านไม่จด VAT
        $this->assertEqualsWithDelta(0.00, (float) $ledger->mlm_commission, 0.001);  // ปิด MLM
        $this->assertEqualsWithDelta(930.00, (float) $ledger->net_amount, 0.001);
        $this->assertSame(EarningsLedger::STATUS_PENDING, $ledger->status);
        $this->assertNull($ledger->available_at, 'ยังไม่ส่งของ ต้องยังไม่เริ่มนับเวลาพักเงิน');

        $this->assertEqualsWithDelta(120.00, $this->platformBalance('fee'), 0.001);
        $this->assertEqualsWithDelta(930.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);

        $order->refresh();
        $this->assertEqualsWithDelta(930.00, (float) $order->seller_net_earnings, 0.001);
        $this->assertEqualsWithDelta(0.00, (float) $order->mlm_commission_total, 0.001);
    }

    public function test_running_distribution_twice_pays_once(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 300);
        $order = $this->makePaidOrder($buyer, [[$product, 1]]);

        $service = app(OrderDistributionService::class);
        $service->processOrderDistribution($order);
        $second = $service->processOrderDistribution($order->fresh());

        $this->assertTrue($second['skipped'] ?? false);
        $this->assertSame('already_distributed', $second['reason'] ?? null);
        $this->assertSame(1, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertSame(1, PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'order_fee')
            ->count());
        $this->assertEqualsWithDelta(30.00, $this->platformBalance('fee'), 0.001);
        $this->assertEqualsWithDelta(270.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);

        // cron ต้องไม่หยิบออเดอร์นี้อีก
        $this->assertFalse($service->getPendingOrders(50)->contains('id', $order->id));
    }

    public function test_multi_seller_order_creates_one_ledger_per_seller(): void
    {
        $sellerA = $this->makeSeller(10.0);
        $sellerB = $this->makeSeller(20.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $productA = $this->makeProduct($sellerA, 100);
        $productB = $this->makeProduct($sellerB, 300);
        $order = $this->makePaidOrder($buyer, [[$productA, 1], [$productB, 1]], 60);

        $result = app(OrderDistributionService::class)->processOrderDistribution($order);

        $ledgers = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->get()->keyBy('user_id');
        $this->assertCount(2, $ledgers, 'ต้องมี ledger ของผู้ขายทั้ง 2 ราย (เดิมรายที่ 2 ชน unique key)');

        $a = $ledgers[$sellerA->id];
        $b = $ledgers[$sellerB->id];
        $this->assertEqualsWithDelta(10.00, (float) $a->platform_fee, 0.001);
        $this->assertEqualsWithDelta(60.00, (float) $b->platform_fee, 0.001);

        // ค่าส่งถูกแบ่งให้ครบพอดี 60 บาท
        $shipA = (float) data_get($a->breakdown, 'shipping_share');
        $shipB = (float) data_get($b->breakdown, 'shipping_share');
        $this->assertEqualsWithDelta(60.00, $shipA + $shipB, 0.001);

        $this->assertEqualsWithDelta(90.00 + $shipA, (float) $a->net_amount, 0.001);
        $this->assertEqualsWithDelta(240.00 + $shipB, (float) $b->net_amount, 0.001);

        // เงินเข้า = เงินที่ลูกค้าจ่าย (GP + เงินพักผู้ขาย)
        $this->assertEqualsWithDelta(460.00, $this->platformBalance('fee') + $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);
        $this->assertCount(2, $result['seller_earnings']);
    }

    public function test_product_without_pv_puts_nothing_into_mlm_pool_even_when_mlm_enabled(): void
    {
        MlmGlobalSetting::set('mlm_enabled', true);
        MlmGlobalSetting::set('commission_per_pv', 1);
        Cache::flush();

        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 1000); // ไม่มีแถว mlm_product_pv และ pv_value = 0
        $order = $this->makePaidOrder($buyer, [[$product, 1]]);

        app(OrderDistributionService::class)->processOrderDistribution($order);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertEqualsWithDelta(0.00, (float) $ledger->mlm_commission, 0.001, 'ไม่มี PV = ค่าแนะนำ 0 บาท (เดิมหัก 100% ของยอดขาย)');
        $this->assertEqualsWithDelta(900.00, (float) $ledger->net_amount, 0.001);
        $this->assertFalse(PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'mlm_commission_pool')
            ->exists());
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    public function test_vat_is_deducted_only_for_vat_registered_store(): void
    {
        $vatSeller = $this->makeSeller(10.0, true);
        $plainSeller = $this->makeSeller(10.0, false);
        $buyer = $this->makeUser('ผู้ซื้อ');

        $vatOrder = $this->makePaidOrder($buyer, [[$this->makeProduct($vatSeller, 1070), 1]]);
        $plainOrder = $this->makePaidOrder($buyer, [[$this->makeProduct($plainSeller, 1070), 1]]);

        $service = app(OrderDistributionService::class);
        $service->processOrderDistribution($vatOrder);
        $service->processOrderDistribution($plainOrder);

        $vatLedger = EarningsLedger::where('source_id', $vatOrder->id)->where('source_type', 'Order')->sole();
        $plainLedger = EarningsLedger::where('source_id', $plainOrder->id)->where('source_type', 'Order')->sole();

        $this->assertEqualsWithDelta(70.00, (float) $vatLedger->vat_amount, 0.001);
        $this->assertEqualsWithDelta(0.00, (float) $plainLedger->vat_amount, 0.001);
        $this->assertEqualsWithDelta(70.00, $this->platformBalance('vat'), 0.001);
    }

    public function test_cod_order_is_distributed_only_after_payment_is_recorded(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 200);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 0, [
            'status' => 'shipped',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);

        // ส่งถึงแล้วแต่ยังไม่บันทึกว่าเก็บเงินได้ → ยังไม่แบ่ง
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // ผู้เก็บเงินบันทึกรับเงินแล้ว → observer แบ่งเงิน + ตั้งเวลาพักเงินทันที (ส่งถึงแล้ว)
        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertEqualsWithDelta(180.00, (float) $ledger->net_amount, 0.001);
        $this->assertNotNull($ledger->available_at);
        $this->assertTrue($ledger->available_at->greaterThan(now()->addDays(2)));
    }

    public function test_order_level_discount_is_recorded_as_platform_expense(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 500);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 0, [
            'discount_amount' => 50,
            'total_amount' => 450,
        ]);

        app(OrderDistributionService::class)->processOrderDistribution($order);

        // ผู้ขายยังได้ราคาเต็ม (ส่วนลดคูปองเป็นของแพลตฟอร์ม)
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertEqualsWithDelta(450.00, (float) $ledger->net_amount, 0.001);

        // GP 50 บาทพอจ่ายส่วนลด 50 บาท → หักจริงจากกระเป๋า fee
        $expense = PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('type', PlatformTransaction::TYPE_EXPENSE)
            ->where('sub_type', 'order_discount')
            ->sole();
        $this->assertEqualsWithDelta(50.00, (float) $expense->amount, 0.001);
        $this->assertEqualsWithDelta(0.00, $this->platformBalance('fee'), 0.001);
        $this->assertNotNull(PlatformWallet::where('slug', 'fee')->first());
    }

    /**
     * คูปองส่งฟรีของร้าน: ร้านได้เฉพาะค่าส่งที่ผู้ซื้อจ่ายจริง (0) และแพลตฟอร์มไม่ลงรายจ่าย
     * (เดิมร้านได้ค่าส่งเต็ม 40 + แพลตฟอร์มลงรายจ่าย 40 → ตั้งค่าส่งสูงๆ + คูปองส่งฟรี = ดึงเงินออกจากระบบ)
     */
    public function test_store_free_shipping_coupon_does_not_pay_seller_unpaid_shipping(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 100);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 40, [
            'discount_amount' => 40,
            'product_discount' => 0,
            'shipping_discount' => 40,
            'discount_funded_by' => 'store',
            'total_amount' => 100,
        ]);

        $result = app(OrderDistributionService::class)->processOrderDistribution($order);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertEqualsWithDelta(90.00, (float) $ledger->net_amount, 0.001, '100 − GP 10 + ค่าส่งที่ผู้ซื้อจ่าย 0');
        $this->assertEqualsWithDelta(0.00, (float) data_get($ledger->breakdown, 'shipping_share'), 0.001);
        $this->assertSame('store', $result['discount']['funded_by']);

        $this->assertSame(0, PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'like', 'order_discount%')
            ->count(), 'คูปองร้านไม่ใช่รายจ่ายแพลตฟอร์ม');

        // ผู้ซื้อจ่าย 100 = GP 10 (fee) + เงินพักร้าน 90 (escrow)
        $this->assertEqualsWithDelta(10.00, $this->platformBalance('fee'), 0.001);
        $this->assertEqualsWithDelta(90.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);
    }

    /**
     * ออเดอร์ที่ไม่มีคอลัมน์แยกส่วนลด (ก่อน migration) แต่ใช้คูปองของร้าน → ดูจาก coupon_usages
     * ส่วนลดสินค้าถูกหักใน order_items.total แล้ว → ไม่ลงรายจ่ายซ้ำ และคืนเงินแล้วไม่ "คืนรายจ่าย" ที่ไม่เคยมี
     */
    public function test_legacy_store_coupon_is_detected_from_coupon_usage_and_refund_creates_no_money(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 100);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 0, [
            'discount_amount' => 20,
            'total_amount' => 80,
        ]);
        \App\Models\OrderItem::where('order_id', $order->id)->update(['discount_amount' => 20, 'total' => 80]);

        $storeId = (int) \App\Models\VendorStore::where('user_id', $seller->id)->value('id');
        $couponId = \Illuminate\Support\Facades\DB::table('coupons')->insertGetId([
            'code' => 'LEGACY20',
            'store_id' => $storeId,
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('coupon_usages')->insert([
            'coupon_id' => $couponId,
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'discount_amount' => 20,
            'order_total' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(OrderDistributionService::class)->processOrderDistribution($order->fresh());

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertEqualsWithDelta(72.00, (float) $ledger->net_amount, 0.001, 'ยอดหลังลด 80 − GP 8');
        $this->assertSame(0, PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'like', 'order_discount%')
            ->count());
        $this->assertEqualsWithDelta(8.00, $this->platformBalance('fee'), 0.001);

        // คืนเงิน: ย้อน GP ออกจาก fee แล้ว fee ต้องเหลือ 0 (ไม่มีการเติม "คืนรายจ่ายส่วนลด" จากอากาศ)
        app(\App\Services\RefundService::class)->processFullRefund($order->fresh(), null, 'ทดสอบคืนเงิน');

        $this->assertEqualsWithDelta(0.00, $this->platformBalance('fee'), 0.001);
        $this->assertSame(0, PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'order_discount_reversal')
            ->count());
        $this->assertEqualsWithDelta(80.00, $this->walletBalance($buyer), 0.001, 'ผู้ซื้อได้คืนเท่าที่จ่ายจริง');
    }
}
