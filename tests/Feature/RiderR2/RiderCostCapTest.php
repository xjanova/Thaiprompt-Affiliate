<?php

namespace Tests\Feature\RiderR2;

use App\Models\Coupon;
use App\Models\EarningsLedger;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\DeliveryFeeCalculator;
use App\Services\FreshMarketService;
use App\Services\OrderDistributionService;
use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 💸 ไรเดอร์รอบ 2 (fix lane money — C1) — เพดานต้นทุนไรเดอร์ที่ร้านเลือกจ่าย (ต้องใช้ MySQL)
 *
 * กติกา: ค่าส่งที่ร้านออกให้ (ส่งฟรี) + โบนัสไรเดอร์ ≤ รายได้สุทธิของร้านจากออเดอร์นั้น (ยอดหลังส่วนลดร้าน − GP)
 *        ลดค่าส่งที่ออกให้ก่อน (ผู้ซื้อจ่ายส่วนที่เหลือ → subsidy_capped) แล้วลดโบนัส (bonus_capped)
 *
 * ครอบคลุม: สินค้า ฿1 + ส่งฟรี + โบนัส 100 ที่ 15 กม. (ร้านค้า + ตลาดสด) → แพลตฟอร์มไม่จ่ายเกินที่เก็บได้ทุกฝ่าย
 *         · สินค้า ฿20 ไกล (ส่งฟรี+โบนัส / โบนัสอย่างเดียว) · คูปองร้านลดรายได้ → จำกัดใหม่ทั้งตะกร้าและตอนสั่ง
 *         · ร้านที่รายได้พอ → ไม่ถูกจำกัด · ฟังก์ชันเพดานเรียกซ้ำได้ผลเดิม
 */
#[Group('rider-r2')]
class RiderCostCapTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    /** ~14.9 กม. ตามถนน (เส้นตรง 11.45 กม. × 1.3) → ค่าส่ง 30 + 12.89 × 10 ปัดขึ้น = ฿159 */
    private const FAR_15KM_LAT_DELTA = 0.103;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->setGpRate(10);
        Setting::set('pricing.referral_pool_percent', '0', 'float', 'pricing');
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('money.distribution_backfill_from', now()->subYear()->toDateTimeString(), 'string', 'money');

        // 09:00 เวลาไทย — ไม่ใช่กลางคืน/ชั่วโมงเร่งด่วน (โบนัสใช้ค่าปกติ ไม่มีส่วนเพิ่ม)
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // =====================================================
    // ฟังก์ชันเพดาน (บริสุทธิ์)
    // =====================================================

    public function test_cap_reduces_subsidy_first_then_bonus_and_is_idempotent(): void
    {
        $quote = ['total_fee' => 159.0, 'rider_earnings' => 127.2, 'shop_subsidy' => 159.0, 'shop_bonus' => 100.0, 'free_delivery' => true];

        // งบ 200: ค่าส่ง 159 เต็ม + โบนัสเหลือ 41
        $a = DeliveryFeeCalculator::capShopCosts($quote, 200.0);
        $this->assertSame(159.0, $a['shop_subsidy']);
        $this->assertSame(41.0, $a['shop_bonus']);
        $this->assertSame(0.0, $a['buyer_fee']);
        $this->assertFalse($a['subsidy_capped']);
        $this->assertTrue($a['bonus_capped']);
        $this->assertTrue($a['free_delivery'], 'ร้านยังออกค่าส่งเต็ม = ส่งฟรีจริง');
        $this->assertSame(168.2, $a['rider_total']);

        // งบ 50: ค่าส่งได้แค่ 50 ผู้ซื้อจ่าย 109 · ไม่มีโบนัส
        $b = DeliveryFeeCalculator::capShopCosts($quote, 50.0);
        $this->assertSame(50.0, $b['shop_subsidy']);
        $this->assertSame(0.0, $b['shop_bonus']);
        $this->assertSame(109.0, $b['buyer_fee']);
        $this->assertTrue($b['subsidy_capped']);
        $this->assertTrue($b['bonus_capped']);
        $this->assertFalse($b['free_delivery'], 'ผู้ซื้อต้องจ่ายส่วนที่เหลือ ไม่ใช่ส่งฟรีแล้ว');

        // จำกัดซ้ำด้วยงบที่น้อยลง = จำกัดครั้งเดียวด้วยงบนั้น · ธงจำกัดไม่หายเมื่อเรียกซ้ำ
        $this->assertEquals(DeliveryFeeCalculator::capShopCosts($quote, 20.0), DeliveryFeeCalculator::capShopCosts($a, 20.0));
        $again = DeliveryFeeCalculator::capShopCosts($b, 50.0);
        $this->assertTrue($again['subsidy_capped']);
        $this->assertTrue($again['bonus_capped']);

        // ต้นทุนอื่นของร้าน (ส่วนลดค่าส่งจากคูปองร้าน) กินงบก่อน · งบติดลบ = 0
        $c = DeliveryFeeCalculator::capShopCosts($quote, 30.0, 40.0);
        $this->assertSame(0.0, $c['shop_subsidy']);
        $this->assertSame(159.0, $c['buyer_fee']);

        // ไม่มีต้นทุนร้าน → ไม่ถูกจำกัด
        $plain = DeliveryFeeCalculator::capShopCosts(['total_fee' => 40.0, 'rider_earnings' => 32.0, 'shop_subsidy' => 0, 'shop_bonus' => 0, 'free_delivery' => false], 0.0);
        $this->assertSame(40.0, $plain['buyer_fee']);
        $this->assertFalse($plain['subsidy_capped']);
        $this->assertFalse($plain['bonus_capped']);

        // quote ปกติมีคีย์เพดานเสมอ (ยังไม่จำกัด)
        $q = (new DeliveryFeeCalculator)->quoteForDistance(3.0);
        $this->assertFalse($q['subsidy_capped']);
        $this->assertFalse($q['bonus_capped']);
    }

    // =====================================================
    // ร้านค้า: สินค้า ฿1 + ส่งฟรี + โบนัส 100 ที่ 15 กม.
    // =====================================================

    public function test_one_baht_item_with_free_delivery_and_max_bonus_never_costs_the_platform(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true, 'rider_bonus' => 100]);
        $product = $this->makeProduct($seller, $store, ['price' => 1]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true, ['latitude' => self::STORE_LAT + self::FAR_15KM_LAT_DELTA, 'longitude' => self::STORE_LNG]);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $data = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data');
        $rider = $data['stores'][0]['rider'];

        $this->assertTrue($rider['available']);
        $this->assertEqualsWithDelta(14.89, $rider['distance_km'], 0.01);
        $this->assertEquals(159, $rider['fee_full']);
        // รายได้ร้าน = 1 − GP 0.10 = 0.90 → ออกค่าส่งได้ 0.90 · โบนัสเหลือ 0
        $this->assertEquals(0.9, $rider['shop_subsidy']);
        $this->assertEquals(0, $rider['shop_bonus']);
        $this->assertEquals(158.1, $rider['fee'], 'ผู้ซื้อจ่ายค่าส่งส่วนที่ร้านออกไม่ไหว');
        $this->assertTrue($rider['subsidy_capped']);
        $this->assertTrue($rider['bonus_capped']);
        $this->assertFalse($rider['free_delivery']);
        $this->assertEquals(127.2, $rider['rider_total'], 'ไรเดอร์ยังได้ส่วนแบ่งจากค่าส่งเต็ม');
        $this->assertEquals(158.1, $data['stores'][0]['shipping_fee']);
        $this->assertEquals(159.1, $data['summary']['grand_total']);

        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet', 'delivery_method' => 'rider'])
            ->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(158.1, (float) $order->shipping_fee);
        $this->assertEquals(159.1, (float) $order->total_amount, 'ยอดที่เก็บ = ยอดที่แสดงในตะกร้า');
        $this->assertEquals(0.9, (float) $order->delivery_subsidy_amount, 'ล็อกค่าที่จำกัดแล้ว');
        $this->assertEquals(0, (float) $order->rider_bonus_amount);

        [$job] = $this->completeShopOrder($order);
        $this->assertEquals(159, (float) $job->total_fee, 'งานไรเดอร์ = ค่าส่งเต็ม (ผู้ซื้อ 158.10 + ร้าน 0.90)');
        $this->assertEquals(0, (float) $job->shop_bonus);

        $this->assertPlatformNeverPaysMore($order->fresh(), $job->fresh(), $buyer);
    }

    // =====================================================
    // ร้านค้า: สินค้า ฿20 ส่งไกล
    // =====================================================

    public function test_twenty_baht_item_far_away_caps_subsidy_then_bonus(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true, 'rider_bonus' => 30]);
        $product = $this->makeProduct($seller, $store, ['price' => 20]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true, ['latitude' => self::STORE_LAT + 0.07, 'longitude' => self::STORE_LNG]);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $rider = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data.stores.0.rider');

        // รายได้ร้าน 20 − 2 = 18 → ออกค่าส่งได้ 18 ผู้ซื้อจ่ายที่เหลือ · โบนัส 30 ถูกตัดเหลือ 0
        $this->assertGreaterThan(18, $rider['fee_full']);
        $this->assertEquals(18, $rider['shop_subsidy']);
        $this->assertEquals(round($rider['fee_full'] - 18, 2), $rider['fee']);
        $this->assertEquals(0, $rider['shop_bonus']);
        $this->assertTrue($rider['subsidy_capped']);
        $this->assertTrue($rider['bonus_capped']);

        // ร้านไม่ได้เลือกส่งฟรี (โบนัสอย่างเดียว) → ผู้ซื้อจ่ายค่าส่งเต็ม โบนัสเหลือ 18
        $store->forceFill(['rider_free_delivery' => false])->save();
        $rider = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data.stores.0.rider');
        $this->assertEquals($rider['fee_full'], $rider['fee']);
        $this->assertEquals(0, $rider['shop_subsidy']);
        $this->assertEquals(18, $rider['shop_bonus']);
        $this->assertFalse($rider['subsidy_capped']);
        $this->assertTrue($rider['bonus_capped']);
        $this->assertEquals(round($rider['rider_earnings'] + 18, 2), $rider['rider_total']);

        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet', 'delivery_method' => 'rider'])
            ->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(18, (float) $order->rider_bonus_amount);
        $this->assertEquals(0, (float) $order->delivery_subsidy_amount);

        [$job] = $this->completeShopOrder($order);
        $this->assertEquals(18, (float) $job->shop_bonus, 'ไรเดอร์ได้โบนัส = ค่าที่จำกัดแล้วบนออเดอร์');
        $this->assertPlatformNeverPaysMore($order->fresh(), $job->fresh(), $buyer);
    }

    public function test_shop_with_enough_earnings_is_not_capped(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true, 'rider_bonus' => 15]);
        $product = $this->makeProduct($seller, $store, ['price' => 300]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $rider = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data.stores.0.rider');

        $this->assertEquals(0, $rider['fee']);
        $this->assertEquals($rider['fee_full'], $rider['shop_subsidy']);
        $this->assertEquals(15, $rider['shop_bonus']);
        $this->assertFalse($rider['subsidy_capped']);
        $this->assertFalse($rider['bonus_capped']);
        $this->assertTrue($rider['free_delivery']);
    }

    // =====================================================
    // ร้านค้า: คูปองร้านลดรายได้ → จำกัดใหม่
    // =====================================================

    public function test_store_coupon_lowers_shop_earnings_and_recaps_in_cart_and_checkout(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true, 'rider_bonus' => 10]);
        $product = $this->makeProduct($seller, $store, ['price' => 100]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);
        Coupon::create([
            'code' => 'RIDERCAP70',
            'store_id' => $store->id,
            'discount_type' => 'fixed',
            'discount_value' => 70,
            'min_purchase' => 0,
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        // ไม่มีคูปอง: รายได้ร้าน 90 พอจ่ายค่าส่ง 31 + โบนัส 10 (ระยะ 2.02 กม. → 30 + 0.2 ปัดขึ้น = 31)
        $plain = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data.stores.0');
        $this->assertEquals(31, $plain['rider']['fee_full']);
        $this->assertEquals(0, $plain['shipping_fee']);
        $this->assertEquals(10, $plain['rider']['shop_bonus']);
        $this->assertFalse($plain['rider']['subsidy_capped']);

        // คูปองร้าน ฿70: รายได้ร้าน = (100 − 70) − GP 3 = 27 → ค่าส่งออกได้ 27 ผู้ซื้อจ่าย 4 · โบนัส 0
        $withCoupon = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id.'&coupon_code=RIDERCAP70')
            ->assertOk()->json('data');
        $group = $withCoupon['stores'][0];
        $this->assertNull($withCoupon['coupon_error']);
        $this->assertEquals(27, $group['rider']['shop_subsidy']);
        $this->assertEquals(4, $group['rider']['fee']);
        $this->assertEquals(0, $group['rider']['shop_bonus']);
        $this->assertTrue($group['rider']['subsidy_capped']);
        $this->assertTrue($group['rider']['bonus_capped']);
        $this->assertEquals(4, $group['shipping_fee']);
        $this->assertEquals(34, $group['total'], '100 − 70 + ค่าส่ง 4');

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id, 'payment_method' => 'wallet', 'delivery_method' => 'rider', 'coupon_code' => 'RIDERCAP70',
        ])->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(34, (float) $order->total_amount, 'ยอดที่เก็บ = ยอดในตะกร้า');
        $this->assertEquals(4, (float) $order->shipping_fee);
        $this->assertEquals(27, (float) $order->delivery_subsidy_amount);
        $this->assertEquals(0, (float) $order->rider_bonus_amount);
        $this->assertEquals(70, (float) $order->product_discount);

        [$job] = $this->completeShopOrder($order);
        $this->assertEquals(31, (float) $job->total_fee);
        $this->assertPlatformNeverPaysMore($order->fresh(), $job->fresh(), $buyer);
    }

    // =====================================================
    // ตลาดสด
    // =====================================================

    public function test_fresh_market_one_baht_item_is_capped_in_quote_and_order_and_platform_never_pays_more(): void
    {
        [$listing, $seller, $sellerUser] = $this->freshMarketShop(price: 1, sellerOverrides: ['rider_free_delivery' => true, 'rider_bonus' => 100]);
        $buyer = $this->makeBuyer(1000);
        $dropLat = 13.7291 + self::FAR_15KM_LAT_DELTA;

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $listing->id, 'quantity' => 1])->assertCreated();
        $quote = $this->getJson('/api/v1/fresh-market/cart/quote?seller_id='.$seller->id.'&latitude='.$dropLat.'&longitude=100.5210')
            ->assertOk()->json('data');

        $this->assertTrue($quote['available']);
        $this->assertEquals(159, $quote['fee_full']);
        $this->assertEquals(0.9, $quote['shop_subsidy']);
        $this->assertEquals(0, $quote['shop_bonus']);
        $this->assertEquals(158.1, $quote['fee']);
        $this->assertTrue($quote['subsidy_capped']);
        $this->assertTrue($quote['bonus_capped']);
        $this->assertFalse($quote['free_delivery']);
        $this->assertEquals(159.1, $quote['grand_total']);

        // ทางเดิมของแอป (ไม่ส่งยอดสินค้า) ก็ได้ค่าที่จำกัดแล้ว
        $single = $this->getJson('/api/v1/fresh-market/delivery-quote?listing_id='.$listing->id.'&latitude='.$dropLat.'&longitude=100.5210&quantity=1')
            ->assertOk()->json('data');
        $this->assertEquals(158.1, $single['fee']);
        $this->assertTrue($single['subsidy_capped']);

        $order = app(FreshMarketService::class)->createOrderFromItems($buyer, $seller->id, [
            ['listing_id' => $listing->id, 'quantity' => 1],
        ], [
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => $dropLat,
            'buyer_longitude' => 100.5210,
            'delivery_address' => '99 ถนนทดสอบ',
            'channel' => 'api',
        ]);
        $order = FreshMarketOrder::findOrFail($order->id);

        $this->assertEquals(158.1, (float) $order->delivery_fee);
        $this->assertEquals(0.9, (float) $order->delivery_subsidy_amount);
        $this->assertEquals(0, (float) $order->rider_bonus_amount);
        $this->assertEquals(159.1, $order->walletPaidAmount(), 'ยอดที่หัก = grand_total ในใบเสนอราคา');
        $this->assertEquals(159, $order->riderDeliveryFeeCharged());

        // ไรเดอร์ส่งสำเร็จ + ปิดออเดอร์ → ร้านได้ 0 (ไม่ติดลบ) ไรเดอร์ได้ค่าส่ง แพลตฟอร์มเหลือ GP + ส่วนแบ่งค่าส่ง
        $job = $this->completedFreshMarketJob($order);
        FreshMarketOrder::whereKey($order->id)->update(['order_status' => FreshMarketOrder::STATUS_DELIVERED, 'delivered_at' => now()]);
        app(FreshMarketService::class)->completeOrder($order->fresh(), 'admin', User::factory()->create(['role' => 'admin']));

        $order = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $order->order_status);
        $sellerGot = round((float) WalletTransaction::where('reference_type', FreshMarketService::REF_PAYOUT)->where('reference_id', $order->id)->sum('amount'), 2);
        $riderGot = $this->riderCredits($job);
        $buyerPaid = $order->walletPaidAmount();

        $this->assertEquals(0.0, $sellerGot, '0.90 − ค่าส่งที่ออกให้ 0.90 = 0 (หักได้ครบ ไม่มีส่วนเกินให้แพลตฟอร์มรับ)');
        $this->assertEquals(0.0, (float) $order->seller_earning);
        $this->assertEquals(127.2, $riderGot);
        $this->assertGreaterThanOrEqual(0.0, $sellerGot);
        $this->assertLessThanOrEqual($buyerPaid, round($sellerGot + $riderGot, 2), 'แพลตฟอร์มจ่ายออกไม่เกินที่ผู้ซื้อจ่าย');
        $this->assertEqualsWithDelta(31.9, round($buyerPaid - $sellerGot - $riderGot, 2), 0.001, 'แพลตฟอร์มเหลือ GP 0.10 + ส่วนแบ่งค่าส่ง 31.80');
    }

    public function test_fresh_market_bonus_only_is_capped_to_shop_earnings(): void
    {
        [$listing, $seller] = $this->freshMarketShop(price: 20, sellerOverrides: ['rider_bonus' => 30]);
        $service = app(FreshMarketService::class);
        $listing->setRelation('seller', $seller);

        $uncapped = $service->quoteDelivery($listing, 13.7291 + 0.07, 100.5210);
        $this->assertEquals(30, $uncapped['shop_bonus'], 'ไม่รู้ยอดสินค้า = ยังไม่จำกัด');
        $this->assertFalse($uncapped['bonus_capped']);

        $capped = $service->quoteDelivery($listing, 13.7291 + 0.07, 100.5210, 20.0);
        $this->assertEquals(18, $capped['shop_bonus'], 'รายได้ร้าน 20 − GP 2 = 18');
        $this->assertTrue($capped['bonus_capped']);
        $this->assertFalse($capped['subsidy_capped']);
        $this->assertEquals($capped['fee_full'], $capped['fee']);
        $this->assertEquals(round($capped['rider_earnings'] + 18, 2), $capped['rider_total']);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ไรเดอร์ส่งสำเร็จ (สร้างงานจากออเดอร์จริง → เสร็จ → เคลียร์เงินไรเดอร์) + แบ่งเงินออเดอร์
     *
     * @return array{0: RiderJob, 1: Rider}
     */
    private function completeShopOrder(Order $order): array
    {
        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'shop_delivery')->fresh();
        $rider = $this->makeRider();

        $job->forceFill(['rider_id' => $rider->id, 'status' => 'completed', 'accepted_at' => now(), 'picked_up_at' => now(), 'completed_at' => now()])->save();
        app(RiderEarningService::class)->settle($job->fresh());

        Order::whereKey($order->id)->update(['status' => 'delivered', 'delivered_at' => now()]);
        app(OrderDistributionService::class)->processOrderDistribution($order->fresh());

        return [$job->fresh(), $rider];
    }

    /**
     * แพลตฟอร์มไม่จ่ายเกินที่เก็บได้: ร้าน ≥ 0 · ร้าน + ไรเดอร์ ≤ ผู้ซื้อจ่าย · ต้นทุนไรเดอร์หักจากร้านได้ครบตามที่ล็อก
     */
    private function assertPlatformNeverPaysMore(Order $order, RiderJob $job, User $buyer): void
    {
        $buyerPaid = round((float) WalletTransaction::where('reference_type', 'order')->where('reference_id', $order->id)
            ->where('user_id', $buyer->id)->where('type', 'fee')->sum('amount'), 2);
        $this->assertEqualsWithDelta((float) $order->total_amount, $buyerPaid, 0.001);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $sellerGets = round((float) $ledger->net_amount, 2);
        $riderGot = $this->riderCredits($job);
        $snapshotCosts = round((float) $order->rider_bonus_amount + (float) $order->delivery_subsidy_amount, 2);

        $this->assertGreaterThanOrEqual(0.0, $sellerGets);
        $this->assertEqualsWithDelta($snapshotCosts, (float) data_get($ledger->breakdown, 'calculations.rider_cost_deduction'), 0.001,
            'หักต้นทุนไรเดอร์จากร้านได้ครบ — แพลตฟอร์มไม่ต้องรับส่วนเกิน');
        $this->assertEqualsWithDelta(round((float) $job->rider_earnings + (float) $job->shop_bonus, 2), $riderGot, 0.001);

        $platformKeeps = round($buyerPaid - $sellerGets - $riderGot, 2);
        $platformBooked = round((float) $ledger->platform_fee + (float) PlatformTransaction::where('source_type', 'rider_job')
            ->where('source_id', $job->id)->where('sub_type', 'rider_delivery_fee')->sum('amount'), 2);

        $this->assertGreaterThanOrEqual(0.0, $platformKeeps, 'แพลตฟอร์มไม่ขาดทุนจากออเดอร์นี้');
        $this->assertEqualsWithDelta($platformBooked, $platformKeeps, 0.001, 'เงินที่เหลือ = GP + ส่วนแบ่งค่าส่ง (ไม่มีเงินงอก/หาย)');
    }

    private function riderCredits(RiderJob $job): float
    {
        return round((float) WalletTransaction::where('reference_type', 'rider_job')
            ->where('reference_id', $job->id)
            ->where('type', 'deposit')
            ->sum('amount'), 2);
    }

    private function makeRider(): Rider
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id, 'full_name' => 'ไรเดอร์ ทดสอบ', 'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved', 'availability' => 'busy', 'rider_type' => 'delivery', 'vehicle_type' => 'motorcycle', 'approved_at' => now(),
        ])->save();

        return $rider->fresh();
    }

    /**
     * ร้านตลาดสด (GP 10%) ที่ (13.7291, 100.5210) + สินค้า 1 รายการ · เปิดไรเดอร์
     *
     * @param  array<string, mixed>  $sellerOverrides
     * @return array{0: FreshMarketListing, 1: FreshMarketSeller, 2: User}
     */
    private function freshMarketShop(float $price, array $sellerOverrides = []): array
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ', 'ai_provider' => 'groq', 'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10, 'fee_mode' => 'percentage', 'escrow_enabled' => true, 'cod_enabled' => true,
            'rider_enabled' => true, 'cashback_enabled' => false, 'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        $sellerUser = User::factory()->create();
        Wallet::create(['user_id' => $sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $seller = FreshMarketSeller::create(array_merge([
            'user_id' => $sellerUser->id, 'shop_name' => 'ร้านผัก '.Str::random(4), 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7291, 'longitude' => 100.5210, 'is_active' => true, 'is_suspended' => false, 'is_verified' => true,
            'subscription_type' => 'free',
        ], $sellerOverrides));

        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักชีลาว', 'price' => $price, 'unit' => 'กำ', 'quantity_available' => 50,
            'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        return [$listing->fresh(), $seller->fresh(), $sellerUser];
    }

    /**
     * งานไรเดอร์ของออเดอร์ตลาดสดที่ส่งสำเร็จแล้ว + เคลียร์เงินไรเดอร์ (ค่างาน = ค่าส่งเต็มที่ล็อกไว้)
     */
    private function completedFreshMarketJob(FreshMarketOrder $order): RiderJob
    {
        $rider = $this->makeRider();
        $fee = (float) $order->riderDeliveryFeeCharged();
        $split = (new DeliveryFeeCalculator)->split($fee);

        $job = new RiderJob;
        $job->forceFill([
            'rider_id' => $rider->id, 'job_type' => 'fresh_market', 'source_type' => $order->getMorphClass(), 'source_id' => $order->id,
            'customer_id' => $order->buyer_id, 'title' => 'ส่งของตลาดสด '.$order->order_number,
            'pickup_address' => 'ร้านผัก', 'pickup_latitude' => 13.7291, 'pickup_longitude' => 100.5210,
            'delivery_address' => '99 ถนนทดสอบ', 'delivery_latitude' => (float) $order->buyer_latitude, 'delivery_longitude' => (float) $order->buyer_longitude,
            'total_fee' => $fee, 'rider_earnings' => $split['rider_earnings'], 'platform_fee' => $split['platform_fee'],
            'shop_bonus' => (float) $order->rider_bonus_amount, 'cod_amount' => 0, 'status' => 'completed',
            'accepted_at' => now(), 'picked_up_at' => now(), 'completed_at' => now(),
            'tracking_token' => Str::random(48), 'tracking_expires_at' => now()->addDay(),
        ])->save();

        FreshMarketOrder::whereKey($order->id)->update(['rider_job_id' => $job->id, 'rider_id' => $rider->id]);
        app(RiderEarningService::class)->settle($job->fresh());

        return $job->fresh();
    }
}
