<?php

namespace Tests\Feature\RiderR2;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DeliveryFeeCalculator;
use App\Services\FreshMarketService;
use App\Services\RiderDispatchService;
use App\Services\Routing\Polyline;
use App\Services\Shop\ShopCartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🛵 ไรเดอร์รอบ 2 — ค่าส่งในตะกร้า (ร้านค้า + ตลาดสด) + คีย์ใหม่ตามสัญญากลาง
 *
 * ตรวจ: ส่วนเพิ่มกลางคืน/เร่งด่วน (เวลาไทย), โบนัสร้านช่วงเร่งด่วน, ส่งฟรี (ผู้ซื้อ 0 ร้านออกเต็ม),
 *       COD ปิดพร้อมเหตุผลเมื่อ rider.allow_cod = false, ออเดอร์ล็อกโบนัส/ค่าส่งที่ร้านออก,
 *       งานไรเดอร์คัดลอก shop_bonus + distance_source + route_polyline และ toApiSummary แยกรายได้
 */
class CartRiderQuoteTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->setGpRate(10);
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');

        // 09:00 เวลาไทย — ไม่ใช่กลางคืน ไม่ใช่ชั่วโมงเร่งด่วน (ผลคาดเดาได้)
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function bkk(string $time): Carbon
    {
        return Carbon::parse('2026-10-05 '.$time, 'Asia/Bangkok');
    }

    // =====================================================
    // สูตร: ส่วนเพิ่มตามเวลา + โบนัสร้าน + ส่งฟรี
    // =====================================================

    public function test_night_and_peak_surcharges_follow_bangkok_hours(): void
    {
        Setting::set('rider.night_surcharge', '15', 'float', 'rider');
        Setting::set('rider.peak_surcharge', '10', 'float', 'rider');
        $calc = new DeliveryFeeCalculator;

        $this->assertSame(15.0, $calc->surchargeAt($this->bkk('23:30')));
        $this->assertSame(15.0, $calc->surchargeAt($this->bkk('05:59')));
        $this->assertSame(0.0, $calc->surchargeAt($this->bkk('06:00')), 'สิ้นสุดกลางคืน 6 โมงตรง (ช่วงปลายเปิด)');
        $this->assertSame(10.0, $calc->surchargeAt($this->bkk('12:15')));
        $this->assertSame(0.0, $calc->surchargeAt($this->bkk('13:00')));
        $this->assertSame(10.0, $calc->surchargeAt($this->bkk('18:59')));
        $this->assertSame(0.0, $calc->surchargeAt($this->bkk('09:00')));
        // เวลาที่ส่งมาเป็น UTC ก็ต้องตัดสินตามเวลาไทย (16:30 UTC = 23:30 ไทย)
        $this->assertSame(15.0, $calc->surchargeAt(Carbon::parse('2026-10-05 16:30:00', 'UTC')));

        $night = $calc->quoteForDistance(3.0, null, $this->bkk('23:30'));
        $this->assertSame(55.0, $night['total_fee'], 'ตามระยะ 40 + กลางคืน 15');
        $this->assertSame(15.0, $night['surcharge']);
        $this->assertSame(10.0, $night['distance_fee'], 'base + distance + surcharge = total');
        $this->assertSame(44.0, $night['rider_earnings'], 'ไรเดอร์ได้ 80% ของค่าส่งเต็ม (รวมส่วนเพิ่ม)');
        $this->assertSame(55.0, $night['buyer_fee']);

        // ไม่ส่งเวลา = ตารางค่าส่งไม่ขึ้นกับเวลา
        $this->assertSame(0.0, $calc->quoteForDistance(3.0)['surcharge']);

        // เริ่ม = สิ้นสุด → ปิดกลางคืน
        Setting::set('rider.night_start_hour', '22', 'integer', 'rider');
        Setting::set('rider.night_end_hour', '22', 'integer', 'rider');
        $this->assertSame(0.0, (new DeliveryFeeCalculator)->surchargeAt($this->bkk('23:30')));
    }

    public function test_shop_bonus_uses_peak_value_only_in_peak_hours_and_only_when_higher(): void
    {
        [, $store] = $this->makeSellerWithStore(['rider_bonus' => 10, 'rider_bonus_peak' => 25]);
        $calc = new DeliveryFeeCalculator;

        $this->assertSame(25.0, $calc->shopBonusFor($store, $this->bkk('12:00')));
        $this->assertSame(25.0, $calc->shopBonusFor($store, $this->bkk('17:30')));
        $this->assertSame(10.0, $calc->shopBonusFor($store, $this->bkk('15:00')));
        $this->assertSame(10.0, $calc->shopBonusFor($store), 'ไม่ส่งเวลา = โบนัสปกติ');

        $quote = $calc->quoteForDistance(5.0, $store, $this->bkk('12:00'));
        $this->assertSame(48.0, $quote['rider_earnings']);
        $this->assertSame(25.0, $quote['shop_bonus']);
        $this->assertSame(73.0, $quote['rider_total']);
        $this->assertSame(60.0, $quote['buyer_fee'], 'โบนัสร้านไม่บวกให้ผู้ซื้อ');

        // โบนัสช่วงเร่งด่วนต่ำกว่าปกติ → ใช้โบนัสปกติ
        $store->forceFill(['rider_bonus_peak' => 5])->save();
        $this->assertSame(10.0, $calc->shopBonusFor($store->fresh(), $this->bkk('12:00')));
    }

    public function test_free_delivery_buyer_pays_zero_and_shop_covers_full_fee(): void
    {
        [, $store] = $this->makeSellerWithStore(['rider_free_delivery' => true, 'rider_bonus' => 5]);

        $quote = (new DeliveryFeeCalculator)->quoteForDistance(5.0, $store, $this->bkk('09:00'));

        $this->assertTrue($quote['free_delivery']);
        $this->assertSame(60.0, $quote['total_fee'], 'total_fee ยังเป็นค่าส่งเต็ม');
        $this->assertSame(0.0, $quote['buyer_fee']);
        $this->assertSame(60.0, $quote['shop_subsidy']);
        $this->assertSame(48.0, $quote['rider_earnings'], 'ไรเดอร์ได้ส่วนแบ่งจากค่าส่งเต็มเหมือนเดิม');
        $this->assertSame(53.0, $quote['rider_total']);
    }

    // =====================================================
    // ตะกร้าร้านค้า (GET /api/v1/cart)
    // =====================================================

    public function test_shop_cart_rider_object_has_contract_keys_and_cod_is_prepaid_only(): void
    {
        Http::fake();
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_bonus' => 10]);
        $product = $this->makeProduct($seller, $store, ['price' => 150]);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $data = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)
            ->assertOk()
            ->json('data');
        $rider = $data['stores'][0]['rider'];

        foreach (['available', 'reason', 'fee', 'distance_km', 'estimated_minutes', 'fee_full', 'distance_source', 'route_polyline',
            'rider_earnings', 'shop_bonus', 'shop_subsidy', 'rider_total', 'surcharge', 'free_delivery'] as $key) {
            $this->assertArrayHasKey($key, $rider, "rider.{$key}");
        }

        $this->assertTrue($rider['available']);
        $this->assertEquals($rider['fee_full'], $rider['fee'], 'ไม่ได้ส่งฟรี → ผู้ซื้อจ่ายเต็ม');
        $this->assertSame('haversine', $rider['distance_source'], 'Valhalla ปิดในเทสต์ → ตกไปคิดเส้นตรง');
        $this->assertNull($rider['route_polyline']);
        $this->assertEquals(10, $rider['shop_bonus']);
        $this->assertEquals(0, $rider['shop_subsidy']);
        $this->assertEquals(round($rider['rider_earnings'] + 10, 2), $rider['rider_total']);
        $this->assertEquals(0, $rider['surcharge']);
        $this->assertFalse($rider['free_delivery']);
        $this->assertEquals($rider['fee'], $data['stores'][0]['shipping_fee']);

        // งานไรเดอร์ต้องจ่ายก่อน (ค่าเริ่มต้น rider.allow_cod = false)
        $this->assertFalse($data['stores'][0]['cod']['available']);
        $this->assertSame(ShopCartService::COD_PREPAID_REASON, $data['stores'][0]['cod']['reason']);
        $this->assertSame('ส่งด้วยไรเดอร์ต้องชำระก่อน เงินพักไว้ปลอดภัยจนคุณได้รับของ', $data['stores'][0]['cod']['reason']);
        $this->assertFalse($data['summary']['cod_available']);

        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'cod', 'delivery_method' => 'rider'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COD_NOT_AVAILABLE');
        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    public function test_cod_returns_when_admin_allows_it(): void
    {
        Http::fake();
        // แอดมินเปิด COD เอง → ใช้ได้ตามวงเงินเดิม (ตั้งก่อนคำขอแรก — ค่าตั้งโหลดครั้งเดียวต่อ instance)
        Setting::set('rider.allow_cod', '1', 'boolean', 'rider');
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 150]);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)
            ->assertOk()
            ->assertJsonPath('data.stores.0.cod.available', true)
            ->assertJsonPath('data.stores.0.cod.reason', null);
    }

    public function test_shop_cart_free_delivery_charges_buyer_zero_and_uses_valhalla_route(): void
    {
        config(['services.valhalla.enabled' => true, 'services.valhalla.url' => 'http://127.0.0.1:8002']);
        $shape = Polyline::encode([[self::STORE_LAT, self::STORE_LNG], [self::STORE_LAT + 0.01, self::STORE_LNG + 0.01]], 6);
        Http::fake(['127.0.0.1:8002/route' => Http::response(['trip' => [
            'summary' => ['length' => 3.4, 'time' => 600],
            'legs' => [['shape' => $shape]],
        ]])]);

        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 200]);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $data = $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)->assertOk()->json('data');
        $rider = $data['stores'][0]['rider'];

        $this->assertSame('valhalla', $rider['distance_source']);
        $this->assertSame($shape, $rider['route_polyline']);
        $this->assertEquals(3.4, $rider['distance_km']);
        $this->assertEquals(15, $rider['estimated_minutes'], '10 นาทีตามถนน + รับของ 5 นาที');
        $this->assertEquals(44, $rider['fee_full'], '30 + (3.4 − 2) × 10 = 44');
        $this->assertEquals(0, $rider['fee']);
        $this->assertEquals(44, $rider['shop_subsidy']);
        $this->assertTrue($rider['free_delivery']);
        $this->assertEquals(0, $data['stores'][0]['shipping_fee']);
        $this->assertEquals(200, $data['summary']['grand_total']);
    }

    // =====================================================
    // ออเดอร์ → งานไรเดอร์
    // =====================================================

    public function test_checkout_locks_bonus_and_subsidy_and_job_copies_pricing(): void
    {
        config(['services.valhalla.enabled' => true, 'services.valhalla.url' => 'http://127.0.0.1:8002']);
        $shape = Polyline::encode([[self::STORE_LAT, self::STORE_LNG], [self::STORE_LAT + 0.01, self::STORE_LNG + 0.01]], 6);
        Http::fake(['127.0.0.1:8002/route' => Http::response(['trip' => [
            'summary' => ['length' => 5.0, 'time' => 780],
            'legs' => [['shape' => $shape]],
        ]])]);

        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'rider_free_delivery' => true, 'rider_bonus' => 15]);
        $product = $this->makeProduct($seller, $store, ['price' => 300]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet', 'delivery_method' => 'rider'])
            ->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(0, (float) $order->shipping_fee, 'ส่งฟรี → ผู้ซื้อไม่จ่ายค่าส่ง');
        $this->assertEquals(300, (float) $order->total_amount);
        $this->assertEquals(15, (float) $order->rider_bonus_amount);
        $this->assertEquals(60, (float) $order->delivery_subsidy_amount, 'ร้านออกค่าส่งเต็ม 60 บาท');

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'shop_delivery')->fresh();

        $this->assertEquals(60, (float) $job->total_fee, 'ค่าส่งเต็มของงาน (ผู้ซื้อจ่าย 0 → คิดจากสูตร)');
        $this->assertEquals(48, (float) $job->rider_earnings);
        $this->assertEquals(15, (float) $job->shop_bonus);
        $this->assertSame('valhalla', $job->distance_source);
        $this->assertSame($shape, $job->route_polyline);

        $summary = $job->toApiSummary();
        $this->assertSame(['delivery_fee' => 60.0, 'rider_earnings' => 48.0, 'shop_bonus' => 15.0, 'rider_total' => 63.0], $summary['earnings_breakdown']);
        $this->assertSame('valhalla', $summary['distance_source']);
        $this->assertSame($shape, $summary['route_polyline']);

        // ไรเดอร์ที่ยังไม่ได้รับงาน → ไม่เห็นเส้นทาง (ชี้บ้านผู้ซื้อ) แต่เห็นรายได้แยกส่วน
        $riderUser = User::factory()->create();
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $riderUser->id, 'full_name' => 'ไรเดอร์ทดสอบ', 'phone' => '0811112222', 'status' => 'approved',
            'availability' => 'online', 'rider_type' => 'delivery', 'vehicle_type' => 'motorcycle', 'approved_at' => now(),
        ])->save();
        $asOffer = $job->toApiSummary($rider->fresh());
        $this->assertNull($asOffer['route_polyline']);
        $this->assertSame(63.0, $asOffer['earnings_breakdown']['rider_total']);
    }

    public function test_job_without_order_bonus_defaults_to_zero(): void
    {
        Http::fake();
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 120]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer, true);

        // ออเดอร์เก่าก่อนรอบนี้ (ไม่มีโบนัส)
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
            'shipping_fee' => 45,
            'total_amount' => 165,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
        ]);

        $job = app(RiderDispatchService::class)->createJobForSource($order, 'shop_delivery')->fresh();

        $this->assertEquals(0, (float) $job->shop_bonus);
        $this->assertEquals(45, (float) $job->total_fee, 'ค่าส่งที่ลูกค้าจ่ายจริงยังเป็นค่างาน');
        $this->assertSame('haversine', $job->distance_source);
        $this->assertNull($job->route_polyline);
        $this->assertSame(36.0, $job->toApiSummary()['earnings_breakdown']['rider_total']);
    }

    // =====================================================
    // ตลาดสด
    // =====================================================

    public function test_fresh_market_quote_has_rider_keys_and_order_charges_buyer_fee(): void
    {
        Http::fake();
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');

        $buyer = User::factory()->create();
        $sellerUser = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id, 'shop_name' => 'ร้านส่งฟรี', 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563, 'longitude' => 100.5018, 'is_active' => true, 'is_suspended' => false, 'is_verified' => true,
            'subscription_type' => 'free', 'rider_free_delivery' => true, 'rider_bonus' => 12,
        ]);
        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักบุ้ง', 'price' => 100, 'unit' => 'กำ', 'quantity_available' => 10,
            'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $listing->id, 'quantity' => 1])->assertCreated();

        $data = $this->getJson('/api/v1/fresh-market/cart/quote?seller_id='.$seller->id.'&latitude=13.7663&longitude=100.5118')
            ->assertOk()
            ->json('data');

        foreach (['fee', 'fee_full', 'buyer_fee', 'distance_source', 'route_polyline', 'rider_earnings', 'shop_bonus', 'shop_subsidy', 'rider_total', 'surcharge', 'free_delivery', 'cod'] as $key) {
            $this->assertArrayHasKey($key, $data, $key);
        }
        $this->assertTrue($data['free_delivery']);
        $this->assertEquals(0, $data['fee']);
        $this->assertGreaterThan(0, $data['fee_full']);
        $this->assertEquals($data['fee_full'], $data['total_fee'], 'total_fee = ค่าส่งเต็ม');
        $this->assertEquals($data['fee_full'], $data['shop_subsidy']);
        $this->assertEquals(12, $data['shop_bonus']);
        $this->assertEquals(100, $data['grand_total'], 'ผู้ซื้อจ่ายแค่ค่าสินค้า');
        $this->assertFalse($data['cod']['available']);
        $this->assertSame(ShopCartService::COD_PREPAID_REASON, $data['cod']['reason']);

        $order = app(FreshMarketService::class)->createOrderFromItems($buyer, $seller->id, [
            ['listing_id' => $listing->id, 'quantity' => 1],
        ], [
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => 13.7663,
            'buyer_longitude' => 100.5118,
            'delivery_address' => '99 ถนนทดสอบ',
            'channel' => 'api',
        ]);

        $order = FreshMarketOrder::findOrFail($order->id);
        $this->assertEquals(0, (float) $order->delivery_fee);
        $this->assertEquals((float) $data['fee_full'], (float) $order->delivery_subsidy_amount);
        $this->assertEquals(12, (float) $order->rider_bonus_amount);
    }
}
