<?php

namespace Tests\Feature\RiderR2;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiderJobService;
use App\Support\Rider\ClientAppBuild;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\RiderR2\Concerns\BuildsRiderSocialFixtures;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ไรเดอร์รอบ 2 — รอบแก้หลังรีวิว (FIXES.md §A1): ตัดสิน "ต้องสแกนส่งมอบ" ตอนไรเดอร์กดรับงาน
 *
 * ต้องครบ: เปิด rider.handover_enabled + ผู้ซื้อสั่งจากแอป build ≥ 43 + ไรเดอร์กดรับจากแอป build ≥ 43
 * ตารางทดสอบ: ผู้ซื้อ (เว็บ/LINE = NULL · แอปเก่า 42 · แอปใหม่ 43) × ไรเดอร์ (เว็บ · LINE · แอปเก่า · ไม่ส่ง header · แอปใหม่)
 * + เก็บ client_app_build ตอนสั่งผ่านแอป (ร้านค้า/ตลาดสด) · รับงานใหม่หลังคืนงาน/แอดมินมอบหมาย = คิดใหม่
 */
class HandoverGatingTest extends TestCase
{
    use BuildsRiderSocialFixtures;
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Cache::flush();

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.handover_enabled', '1', 'boolean', 'rider');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');
        Setting::set('money.distribution_backfill_from', now()->subYear()->toDateTimeString(), 'string', 'money');
        $this->setGpRate(10);

        // เทสต์นี้ตรวจการเก็บ build แอป ไม่ใช่ด่านรูปโปรไฟล์ (มีเทสต์ของมันเองแล้ว)
        config(['profile_photo.required' => false]);
    }

    // =====================================================
    // เก็บ build แอปตอนสั่ง
    // =====================================================

    public function test_shop_app_checkout_records_client_app_build_only_from_header(): void
    {
        foreach (['43' => 43, null => null, 'abc' => null, '0' => null] as $header => $expected) {
            [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
            $product = $this->makeProduct($seller, $store, ['price' => 150, 'stock_quantity' => 5]);
            $buyer = $this->makeBuyer(1000);
            $address = $this->makeAddress($buyer, true);

            Sanctum::actingAs($buyer);
            $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

            $headers = $header === '' ? [] : ['X-App-Build' => (string) $header];
            $this->postJson('/api/v1/cart/checkout', [
                'address_id' => $address->id,
                'payment_method' => 'wallet',
                'delivery_method' => 'rider',
            ], $headers)->assertCreated();

            $order = Order::where('user_id', $buyer->id)->firstOrFail();
            $this->assertSame($expected, $order->client_app_build !== null ? (int) $order->client_app_build : null, 'header '.var_export($header, true));
        }
    }

    public function test_fresh_market_app_order_records_client_app_build(): void
    {
        $listing = $this->makeFreshMarketListing();

        foreach (['43' => 43, '' => null] as $header => $expected) {
            $buyer = User::factory()->create();
            Wallet::create(['user_id' => $buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);

            Sanctum::actingAs($buyer);
            $this->postJson('/api/v1/fresh-market/orders', [
                'items' => [['listing_id' => $listing->id, 'quantity' => 1]],
                'delivery_type' => 'rider',
                'payment_method' => 'wallet',
                'buyer_latitude' => self::BASE_LAT + 0.01,
                'buyer_longitude' => self::BASE_LNG + 0.01,
                'delivery_address' => '99 ถนนทดสอบ แขวงสุริยวงศ์ เขตบางรัก',
            ], $header === '' ? [] : ['X-App-Build' => (string) $header])->assertCreated();

            $order = FreshMarketOrder::where('buyer_id', $buyer->id)->firstOrFail();
            $this->assertSame($expected, $order->client_app_build !== null ? (int) $order->client_app_build : null);
        }
    }

    // =====================================================
    // ตอนรับงาน: ตาราง ผู้ซื้อ × ไรเดอร์
    // =====================================================

    public function test_new_app_buyer_and_new_app_rider_get_handover(): void
    {
        $job = $this->pendingShopJob(43);
        $rider = $this->makeRider();

        $this->apiAccept($rider, $job, '43')->assertOk()
            ->assertJsonPath('data.job.handover.required', true)
            ->assertJsonPath('data.job.handover.status', 'waiting');

        $this->assertTrue((bool) $job->fresh()->handover_required);
    }

    public function test_rider_side_without_supported_app_stays_legacy(): void
    {
        // แอปไรเดอร์รุ่นเก่า (42) / ไม่ส่ง header / ค่าแปลก
        foreach (['42', null, '43abc'] as $build) {
            $job = $this->pendingShopJob(43);
            $this->apiAccept($this->makeRider(), $job, $build)->assertOk()->assertJsonPath('data.job.handover', null);
            $this->assertFalse((bool) $job->fresh()->handover_required, 'rider build '.var_export($build, true));
        }

        // หน้าเว็บ /user/rider (แม้แนบ header มา ก็ไม่ใช่แอป)
        $job = $this->pendingShopJob(43);
        $rider = $this->makeRider();
        $this->actingAs($rider->user)
            ->postJson(route('user.rider.jobs.accept', $job), [], ['X-App-Build' => '43'])
            ->assertOk();
        $this->assertSame('accepted', $job->fresh()->status);
        $this->assertFalse((bool) $job->fresh()->handover_required, 'web');

        // LINE postback (FreshMarketChannelManager เรียก accept โดยไม่มี build)
        $job = $this->pendingShopJob(43);
        app(RiderJobService::class)->accept($job, $this->makeRider());
        $this->assertSame('accepted', $job->fresh()->status);
        $this->assertFalse((bool) $job->fresh()->handover_required, 'line');
    }

    public function test_buyer_side_without_supported_app_stays_legacy(): void
    {
        // ผู้ซื้อสั่งจากหน้าเว็บ/LINE (NULL) หรือแอปเก่า (42) → ไรเดอร์แอปใหม่ก็ยังเป็นงานแบบเดิม
        foreach ([null, 42] as $buyerBuild) {
            $job = $this->pendingShopJob($buyerBuild);
            $this->apiAccept($this->makeRider(), $job, '43')->assertOk()->assertJsonPath('data.job.handover', null);

            $job->refresh();
            $this->assertFalse((bool) $job->handover_required, 'buyer build '.var_export($buyerBuild, true));
            $this->assertContains('release', $job->allowedActionsFor($job->rider));
        }
    }

    public function test_setting_off_keeps_legacy_even_for_new_apps(): void
    {
        Setting::set('rider.handover_enabled', '0', 'boolean', 'rider');

        $job = $this->pendingShopJob(43);
        $this->apiAccept($this->makeRider(), $job, '43')->assertOk();

        $this->assertFalse((bool) $job->fresh()->handover_required);
    }

    public function test_fresh_market_source_follows_the_same_gate(): void
    {
        $buyer = User::factory()->create();
        $order = $this->makeFreshMarketOrder($buyer);
        FreshMarketOrder::whereKey($order->id)->update(['order_status' => 'ready', 'client_app_build' => 43]);
        $job = $this->pendingJobFor($order->fresh(), $buyer);

        $this->apiAccept($this->makeRider(), $job, '43')->assertOk()->assertJsonPath('data.job.handover.required', true);
        $this->assertTrue((bool) $job->fresh()->handover_required);
    }

    public function test_gate_is_recomputed_on_every_accept(): void
    {
        $job = $this->pendingShopJob(43);
        $appRider = $this->makeRider();

        // แอปใหม่รับ → ต้องสแกน → คืนงาน → ไรเดอร์หน้าเว็บรับต่อ → แบบเดิม
        $this->apiAccept($appRider, $job, '43')->assertOk();
        $this->assertTrue((bool) $job->fresh()->handover_required);

        Sanctum::actingAs($appRider->user);
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/release', ['reason' => 'รถเสีย'])->assertOk();
        $this->assertSame('pending', $job->fresh()->status);

        $webRider = $this->makeRider(['full_name' => 'ไรเดอร์ เว็บ']);
        $this->actingAs($webRider->user)->postJson(route('user.rider.jobs.accept', $job))->assertOk();
        $this->assertFalse((bool) $job->fresh()->handover_required);

        // แอดมินมอบหมายก่อนรับของ → ไม่รู้ว่าไรเดอร์ใช้อะไร → แบบเดิม (ส่งได้ทุกช่องทาง)
        $job2 = $this->pendingShopJob(43);
        $this->apiAccept($this->makeRider(), $job2, '43')->assertOk();
        $this->assertTrue((bool) $job2->fresh()->handover_required);
        $admin = User::factory()->create(['role' => 'admin']);
        app(RiderJobService::class)->adminReassign($job2->fresh(), $this->makeRider(['full_name' => 'ไรเดอร์ ใหม่']), $admin);
        $this->assertFalse((bool) $job2->fresh()->handover_required);
    }

    public function test_client_app_build_helper_normalizes_values(): void
    {
        $this->assertSame(43, ClientAppBuild::normalize('43'));
        $this->assertSame(43, ClientAppBuild::normalize(43));
        $this->assertNull(ClientAppBuild::normalize('0'));
        $this->assertNull(ClientAppBuild::normalize('-5'));
        $this->assertNull(ClientAppBuild::normalize('43abc'));
        $this->assertNull(ClientAppBuild::normalize(null));
        $this->assertSame(65535, ClientAppBuild::normalize('999999'));
        $this->assertTrue(ClientAppBuild::supportsHandover(43));
        $this->assertFalse(ClientAppBuild::supportsHandover(42));
        $this->assertFalse(ClientAppBuild::supportsHandover(null));
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ออเดอร์ร้านค้าที่จ่ายแล้วรอไรเดอร์ + งาน pending (สร้างตรงๆ — handover_required ไม่ระบุ = ค่าเริ่มต้นตอนสร้าง)
     */
    private function pendingShopJob(?int $buyerBuild): RiderJob
    {
        $buyer = User::factory()->create();
        $order = $this->makeShopOrder($buyer, ['status' => 'processing', 'settlement_deferred' => true]);
        if ($buyerBuild !== null) {
            Order::whereKey($order->id)->update(['client_app_build' => $buyerBuild]);
        }

        return $this->pendingJobFor($order->fresh(), $buyer);
    }

    private function pendingJobFor(Model $source, User $buyer): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill([
            'job_type' => 'shop_delivery',
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'customer_id' => $buyer->id,
            'title' => 'ส่งสินค้า',
            'pickup_address' => 'ร้านทดสอบ',
            'pickup_latitude' => self::BASE_LAT,
            'pickup_longitude' => self::BASE_LNG,
            'delivery_address' => '99 ถนนทดสอบ เขตบางรัก กรุงเทพมหานคร',
            'delivery_latitude' => self::BASE_LAT + 0.01,
            'delivery_longitude' => self::BASE_LNG + 0.01,
            'total_fee' => 30,
            'rider_earnings' => 25,
            'platform_fee' => 5,
            'status' => 'pending',
            'dispatch_type' => 'broadcast',
        ])->save();

        $this->assertFalse((bool) $job->fresh()->handover_required, 'สร้างงานใหม่ = แบบเดิมเสมอ');

        return $job->fresh();
    }

    private function apiAccept(Rider $rider, RiderJob $job, ?string $build)
    {
        Sanctum::actingAs($rider->user);

        return $this->postJson('/api/v1/rider/jobs/'.$job->id.'/accept', [], $build === null ? [] : ['X-App-Build' => $build]);
    }

    private function makeFreshMarketListing(): FreshMarketListing
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
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

        $sellerUser = User::factory()->create();
        Wallet::create(['user_id' => $sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        $shop = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => self::BASE_LAT,
            'longitude' => self::BASE_LNG,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        return FreshMarketListing::create([
            'seller_id' => $shop->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }
}
