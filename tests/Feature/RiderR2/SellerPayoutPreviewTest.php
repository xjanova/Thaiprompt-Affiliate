<?php

namespace Tests\Feature\RiderR2;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Services\OrderDistributionService;
use Laravel\Sanctum\Sanctum;

/**
 * ยอดที่ร้าน "ได้รับจริง" (OrderDistributionService::sellerPayoutFor) — 2026-11-13
 *
 * แอปร้านเคยโชว์ "รายรับสุทธิ" = ค่าสินค้าหลังหัก GP ซึ่งไม่ใช่เงินที่เข้าร้าน
 * (ไม่หักโบนัสไรเดอร์/ค่าส่งที่ร้านออกแทน และไม่รวมค่าส่งพัสดุที่ร้านได้)
 *
 * ครอบคลุม:
 *   - ยอดประมาณการก่อนแบ่งเงิน ต้องเท่ากับยอดที่จ่ายจริงหลังแบ่งเงิน (กันสูตรสองที่เพี้ยนจากกัน)
 *   - ออเดอร์ไรเดอร์เงินพัก: หักโบนัส + ค่าส่งที่ร้านออก · ค่าส่งของผู้ซื้อไม่เข้าร้าน
 *   - ออเดอร์พัสดุ: ค่าส่งที่ลูกค้าจ่ายเข้าร้าน
 *   - API ออเดอร์ของร้าน (รายละเอียด + รายการ) ส่งยอดนี้ให้แอป
 */
class SellerPayoutPreviewTest extends HandoverTestCase
{
    public function test_rider_order_preview_deducts_bonus_and_subsidy_and_matches_the_real_payout(): void
    {
        // ร้านออกค่าส่งให้ทั้งหมด 40 + เติมโบนัสไรเดอร์ 15 · สินค้า 100 · GP 10%
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        $service = app(OrderDistributionService::class);

        $preview = $service->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertNotNull($preview);
        $this->assertFalse($preview['final']);
        $this->assertEqualsWithDelta(100.0, $preview['items_gross'], 0.001);
        $this->assertEqualsWithDelta(10.0, $preview['platform_fee'], 0.001);
        $this->assertEqualsWithDelta(0.0, $preview['shipping_income'], 0.001);
        $this->assertEqualsWithDelta(15.0, $preview['rider_bonus'], 0.001);
        $this->assertEqualsWithDelta(40.0, $preview['delivery_subsidy'], 0.001);
        $this->assertEqualsWithDelta(55.0, $preview['rider_cost_deduction'], 0.001);
        // 100 − GP 10 − ค่าส่งที่ออกให้ 40 − โบนัส 15 = 35 (ไม่ใช่ 90 ที่แอปเคยโชว์)
        $this->assertEqualsWithDelta(35.0, $preview['net_payout'], 0.001);

        // ส่งมอบสำเร็จ → แบ่งเงินจริง
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['shop_bonus' => 15]);
        $this->completeByScans($buyer, $order, $rider, $job);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $actual = $service->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertTrue($actual['final']);
        $this->assertEqualsWithDelta((float) $ledger->net_amount, $actual['net_payout'], 0.001);
        $this->assertEqualsWithDelta($this->walletOf($seller), $actual['net_payout'], 0.001);
        // ยอดประมาณการ = ยอดจริง ทุกบรรทัด
        foreach (['items_gross', 'platform_fee', 'vat_amount', 'referral_amount', 'shipping_income', 'store_discount_deduction', 'rider_bonus', 'delivery_subsidy', 'store_shipping_discount', 'rider_cost_deduction', 'net_payout'] as $key) {
            $this->assertEqualsWithDelta($preview[$key], $actual[$key], 0.001, "บรรทัด {$key} ของยอดประมาณการต้องเท่ายอดจริง");
        }
    }

    public function test_buyer_paid_rider_fee_is_not_seller_income(): void
    {
        // ผู้ซื้อจ่ายค่าส่งไรเดอร์ 40 เอง ร้านไม่เติมอะไร
        [$order, , $seller] = $this->makeDeferredShopOrder(shipping: 40);

        $preview = app(OrderDistributionService::class)->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertEqualsWithDelta(0.0, $preview['shipping_income'], 0.001);
        $this->assertEqualsWithDelta(0.0, $preview['rider_cost_deduction'], 0.001);
        $this->assertEqualsWithDelta(90.0, $preview['net_payout'], 0.001);
    }

    public function test_rider_costs_never_push_the_payout_below_zero_and_rows_add_up(): void
    {
        // สินค้า 20 (ร้านได้ 18 หลัง GP) แต่ล็อกค่าส่งที่ร้านออก 40 + โบนัส 15 ไว้ → หักได้แค่ 18
        [$order, , $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0, price: 20);

        $preview = app(OrderDistributionService::class)->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertEqualsWithDelta(0.0, $preview['net_payout'], 0.001);
        $this->assertEqualsWithDelta(18.0, $preview['rider_cost_deduction'], 0.001);
        // แถวย่อยรวมกันต้องเท่ายอดที่หักจริง (ไม่โชว์ −55 บนรายได้ 18)
        $this->assertEqualsWithDelta(
            $preview['rider_cost_deduction'],
            $preview['rider_bonus'] + $preview['delivery_subsidy'] + $preview['store_shipping_discount'],
            0.001
        );
        // สมการทั้งใบ: สินค้า − GP − VAT − ค่าแนะนำ + ค่าส่งที่ได้ − ส่วนลดร้าน − ต้นทุนไรเดอร์ = ยอดที่ได้รับ
        $this->assertEqualsWithDelta(
            $preview['net_payout'],
            $preview['items_gross'] - $preview['platform_fee'] - $preview['vat_amount'] - $preview['referral_amount']
                + $preview['shipping_income'] - $preview['store_discount_deduction'] - $preview['rider_cost_deduction'],
            0.001
        );
    }

    public function test_parcel_order_preview_includes_shipping_income_and_matches_the_ledger(): void
    {
        $seller = $this->makeSeller(12.0);
        $buyer = $this->makeUser('ผู้ซื้อพัสดุ');
        $product = $this->makeProduct($seller, 500);
        $order = $this->makePaidOrder($buyer, [[$product, 2]], 50);
        $service = app(OrderDistributionService::class);

        $preview = $service->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertFalse($preview['final']);
        $this->assertEqualsWithDelta(1000.0, $preview['items_gross'], 0.001);
        $this->assertEqualsWithDelta(120.0, $preview['platform_fee'], 0.001);
        $this->assertEqualsWithDelta(50.0, $preview['shipping_income'], 0.001);
        // 1000 − GP 120 + ค่าส่งพัสดุ 50 = 930 (แอปเคยโชว์ 880)
        $this->assertEqualsWithDelta(930.0, $preview['net_payout'], 0.001);

        $service->processOrderDistribution($order->fresh());
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $actual = $service->sellerPayoutFor($order->fresh(), (int) $seller->id);

        $this->assertTrue($actual['final']);
        $this->assertEqualsWithDelta((float) $ledger->net_amount, $actual['net_payout'], 0.001);
        $this->assertEqualsWithDelta($preview['net_payout'], $actual['net_payout'], 0.001);
        $this->assertEqualsWithDelta($preview['shipping_income'], $actual['shipping_income'], 0.001);
        $this->assertEqualsWithDelta($preview['platform_fee'], $actual['platform_fee'], 0.001);
    }

    public function test_cancelled_order_and_other_sellers_get_no_payout(): void
    {
        [$order, , $seller] = $this->makeDeferredShopOrder(shipping: 40);
        $service = app(OrderDistributionService::class);
        $stranger = $this->makeSeller(10.0);

        // ร้านอื่นที่ไม่มีสินค้าในออเดอร์นี้
        $this->assertNull($service->sellerPayoutFor($order->fresh(), (int) $stranger->id));

        Order::whereKey($order->id)->update(['status' => 'cancelled']);
        $this->assertNull($service->sellerPayoutFor($order->fresh(), (int) $seller->id));
    }

    public function test_seller_order_api_exposes_the_real_payout(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        Sanctum::actingAs($seller);

        // ก่อนส่งมอบ: รายละเอียดมียอดประมาณการ · รายการยังไม่มียอดที่จ่ายจริง
        $detail = $this->getJson("/api/v1/seller/orders/{$order->id}")->assertOk()->json('data.totals');
        $this->assertArrayHasKey('seller_earning', $detail); // ตัวเลขเดิม (ค่าสินค้าหลัง GP) ยังส่งอยู่ให้แอปรุ่นเก่า
        $this->assertFalse($detail['payout']['final']);
        $this->assertEqualsWithDelta(35.0, $detail['payout']['net_payout'], 0.001);
        $this->assertEqualsWithDelta(15.0, $detail['payout']['rider_bonus'], 0.001);
        $this->assertEqualsWithDelta(40.0, $detail['payout']['delivery_subsidy'], 0.001);

        $row = collect($this->getJson('/api/v1/seller/orders')->assertOk()->json('data.orders'))->firstWhere('id', $order->id);
        $this->assertNotNull($row);
        $this->assertNull($row['net_payout']);

        // หลังส่งมอบ: ทั้งสองที่เป็นยอดที่จ่ายจริง
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['shop_bonus' => 15]);
        $this->completeByScans($buyer, $order, $rider, $job);
        Sanctum::actingAs($seller);

        $detail = $this->getJson("/api/v1/seller/orders/{$order->id}")->assertOk()->json('data.totals');
        $this->assertTrue($detail['payout']['final']);
        $this->assertEqualsWithDelta(35.0, $detail['payout']['net_payout'], 0.001);

        $row = collect($this->getJson('/api/v1/seller/orders')->assertOk()->json('data.orders'))->firstWhere('id', $order->id);
        $this->assertEqualsWithDelta(35.0, $row['net_payout'], 0.001);
    }
}
