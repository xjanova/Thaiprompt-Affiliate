<?php

namespace Tests\Feature\Rider;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ยินยอมแชร์ตำแหน่งทั้งสองฝ่าย (ไรเดอร์ ↔ ผู้ซื้อ) — ต้องใช้ MySQL
 *
 * ครอบคลุม:
 * - ไรเดอร์ต้องยินยอม 1 ครั้งก่อนรับงานแรก (POST /rider/consent) · ถอนระหว่างมีงานไม่ได้
 * - ผู้ซื้อเห็นตำแหน่งไรเดอร์เฉพาะออเดอร์ของตัวเอง + เฉพาะตอนงานวิ่งอยู่ (คนอื่น 404 · ส่งเสร็จแล้วไม่มีพิกัด)
 * - ไรเดอร์เห็นตำแหน่งผู้ซื้อเฉพาะเมื่อผู้ซื้อเปิดแชร์ และตำแหน่งยังสด (≤ 2 นาที)
 * - ส่งเสร็จ / ยกเลิก / ไรเดอร์คืนงาน → หยุดแชร์ + ลบพิกัดผู้ซื้ออัตโนมัติ
 * - ออเดอร์ร้านค้า (source=shop) ใช้กติกาเดียวกัน + เส้นทางเว็บ (session)
 */
#[Group('rider')]
class DeliveryLocationConsentTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private const SHOP_LAT = 13.7291;

    private const SHOP_LNG = 100.5210;

    private const BUYER_LAT = 13.7400;

    private const BUYER_LNG = 100.5300;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        FreshMarketSetting::clearCache();

        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 0,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '0', 'float', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อ']);
        $this->sellerUser = User::factory()->create(['name' => 'แม่ค้า']);

        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'แผงผักป้าแดง',
            'phone' => '0812345678',
            'address' => 'ตลาดบางรัก',
            'latitude' => self::SHOP_LAT,
            'longitude' => self::SHOP_LNG,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $this->listing = FreshMarketListing::create([
            'seller_id' => $seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 40,
            'unit' => 'กำ',
            'quantity_available' => 20,
            'latitude' => self::SHOP_LAT,
            'longitude' => self::SHOP_LNG,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    public function test_rider_must_consent_once_before_first_accept(): void
    {
        [, $job] = $this->riderOrderWithJob();
        $rider = $this->makeRider(['share_location_consent_at' => null]);

        Sanctum::actingAs($rider->user);

        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")
            ->assertStatus(403)
            ->assertJsonPath('code', 'NOT_ELIGIBLE')
            ->assertJsonPath('data.block_code', 'CONSENT_REQUIRED');

        $this->postJson('/api/v1/rider/consent')
            ->assertOk()
            ->assertJsonPath('data.location_consent', true)
            ->assertJsonPath('data.can_accept_jobs', true);
        $this->assertNotNull($rider->fresh()->share_location_consent_at);

        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.job.status', 'accepted');

        // ถอนความยินยอมระหว่างมีงานไม่ได้
        $this->postJson('/api/v1/rider/consent', ['location_consent' => false])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HAS_ACTIVE_JOB');
        $this->assertNotNull($rider->fresh()->share_location_consent_at);
    }

    public function test_buyer_sees_rider_location_only_for_own_active_order(): void
    {
        [$order, $job] = $this->riderOrderWithJob();
        $url = "/api/v1/orders/fresh-market/{$order->id}/rider-location";

        Sanctum::actingAs($this->buyer);

        // ยังไม่มีไรเดอร์รับงาน
        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.has_rider_job', true)
            ->assertJsonPath('data.reason', 'waiting_for_rider')
            ->assertJsonPath('data.rider_location', null)
            ->assertJsonPath('data.can_share_location', false);

        $rider = $this->makeRider();
        app(RiderJobService::class)->accept($job, $rider);

        $data = $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.job.is_active', true)
            ->assertJsonPath('data.location_available', true)
            ->assertJsonPath('data.rider.name', $rider->full_name)
            ->assertJsonPath('data.can_share_location', true)
            ->json('data');
        $this->assertEqualsWithDelta((float) $rider->last_latitude, $data['rider_location']['latitude'], 0.00001);
        $this->assertIsFloat($data['rider_location']['longitude']);

        // หน้าเว็บติดตาม: ผู้ซื้อที่ login = ได้ลิงก์แชร์ตำแหน่ง (session) · คนอื่นที่ถือลิงก์ = ไม่ได้
        $this->actingAs($this->buyer)
            ->get(route('taladsod.track.show', $job->fresh()->tracking_token))
            ->assertOk()
            ->assertViewHas('deliveryEndpoints', fn ($e) => $e !== null && $e['source'] === 'fresh-market' && $e['order_id'] === $order->id);
        $this->actingAs(User::factory()->create())
            ->get(route('taladsod.track.show', $job->fresh()->tracking_token))
            ->assertOk()
            ->assertViewHas('deliveryEndpoints', null);

        // ผู้ใช้อื่น / ร้านค้า (ไม่ใช่ผู้ซื้อ) → 404 เหมือนไม่มีออเดอร์นี้
        Sanctum::actingAs(User::factory()->create());
        $this->getJson($url)->assertStatus(404)->assertJsonPath('code', 'ORDER_NOT_FOUND');
        Sanctum::actingAs($this->sellerUser);
        $this->getJson($url)->assertStatus(404);

        // source ผิดประเภท → ไม่พบ
        Sanctum::actingAs($this->buyer);
        $this->getJson("/api/v1/orders/shop/{$order->id}/rider-location")->assertStatus(404);

        // ส่งของเสร็จ → ไม่มีพิกัดไรเดอร์อีก
        $service = app(RiderJobService::class);
        $service->markPickedUp($job->fresh(), $rider);
        $service->deliver($job->fresh(), $rider, UploadedFile::fake()->image('proof.jpg'), null, null, false);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.job.status', 'completed')
            ->assertJsonPath('data.job.is_active', false)
            ->assertJsonPath('data.reason', 'job_not_active')
            ->assertJsonPath('data.rider_location', null)
            ->assertJsonPath('data.rider', null)
            ->assertJsonPath('data.pickup', null)
            ->assertJsonPath('data.can_share_location', false);
    }

    public function test_rider_sees_customer_location_only_when_opted_in_and_fresh(): void
    {
        [$order, $job] = $this->riderOrderWithJob();
        $rider = $this->makeRider();
        app(RiderJobService::class)->accept($job, $rider);

        $share = "/api/v1/orders/fresh-market/{$order->id}/share-location";
        $riderView = fn () => $this->actingAsRider($rider)->getJson("/api/v1/rider/jobs/{$job->id}")->assertOk()->json('data.job.customer_live_location');

        $this->assertNull($riderView(), 'ผู้ซื้อยังไม่เปิดแชร์ → ไรเดอร์ไม่เห็น');

        // คนอื่นเปิดแชร์แทนผู้ซื้อไม่ได้
        Sanctum::actingAs(User::factory()->create());
        $this->postJson($share, ['share' => true, 'latitude' => 13.75, 'longitude' => 100.53])->assertStatus(404);

        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => true, 'latitude' => 13.7405, 'longitude' => 100.5305])
            ->assertOk()
            ->assertJsonPath('data.sharing', true)
            ->assertJsonPath('data.location_saved', true)
            ->assertJsonPath('data.customer_sharing.is_fresh', true);

        $live = $riderView();
        $this->assertNotNull($live);
        $this->assertEqualsWithDelta(13.7405, $live['latitude'], 0.00001);

        // เก่ากว่า 2 นาที → ไรเดอร์ไม่เห็น
        $this->travel(121)->seconds();
        $this->assertNull($riderView(), 'ตำแหน่งผู้ซื้อเก่ากว่า 2 นาทีต้องไม่แสดง');

        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => true, 'latitude' => 13.7410, 'longitude' => 100.5310])->assertOk();
        $this->assertEqualsWithDelta(13.7410, $riderView()['latitude'], 0.00001);

        // ปิดแชร์ → ลบพิกัดทิ้ง
        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => false])
            ->assertOk()
            ->assertJsonPath('data.sharing', false);
        $this->assertNull($riderView());
        $fresh = $job->fresh();
        $this->assertFalse((bool) $fresh->customer_share_location);
        $this->assertNull($fresh->customer_last_latitude);

        // พิกัดผิด → 422
        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => true, 'latitude' => 0, 'longitude' => 0])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_LOCATION');
    }

    public function test_sharing_stops_automatically_when_delivery_ends(): void
    {
        // ส่งเสร็จ
        [$order, $job] = $this->riderOrderWithJob();
        $rider = $this->makeRider();
        app(RiderJobService::class)->accept($job, $rider);

        Sanctum::actingAs($this->buyer);
        $share = "/api/v1/orders/fresh-market/{$order->id}/share-location";
        $this->postJson($share, ['share' => true, 'latitude' => 13.7405, 'longitude' => 100.5305])->assertOk();

        $service = app(RiderJobService::class);
        $service->markPickedUp($job->fresh(), $rider);
        $service->deliver($job->fresh(), $rider, UploadedFile::fake()->image('proof.jpg'), null, null, false);

        $done = $job->fresh();
        $this->assertSame('completed', $done->status);
        $this->assertFalse((bool) $done->customer_share_location);
        $this->assertNull($done->customer_last_latitude);
        $this->assertNull($done->customer_location_at);

        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => true, 'latitude' => 13.7405, 'longitude' => 100.5305])
            ->assertStatus(409)
            ->assertJsonPath('code', 'JOB_NOT_ACTIVE');
        $this->assertNull($job->fresh()->customer_last_latitude, 'งานจบแล้วห้ามมีพิกัดผู้ซื้อค้าง');
    }

    public function test_release_and_cancel_clear_customer_location(): void
    {
        [$order, $job] = $this->riderOrderWithJob();
        $rider = $this->makeRider();
        $service = app(RiderJobService::class);
        $service->accept($job, $rider);

        Sanctum::actingAs($this->buyer);
        $share = "/api/v1/orders/fresh-market/{$order->id}/share-location";
        $this->postJson($share, ['share' => true, 'latitude' => 13.7405, 'longitude' => 100.5305])->assertOk();

        // ไรเดอร์คืนงาน → ไม่มีไรเดอร์แล้ว หยุดแชร์ (ต้องเปิดใหม่กับไรเดอร์คนถัดไป)
        $service->release($job->fresh(), $rider, 'รถเสีย');
        $released = $job->fresh();
        $this->assertSame('pending', $released->status);
        $this->assertFalse((bool) $released->customer_share_location);
        $this->assertNull($released->customer_last_latitude);

        $this->postJson($share, ['share' => true])->assertStatus(409)->assertJsonPath('code', 'JOB_NOT_ACTIVE');

        // ไรเดอร์คนใหม่รับ → ผู้ซื้อเปิดแชร์ใหม่ → ยกเลิกงาน → ลบพิกัด
        $rider2 = $this->makeRider();
        $service->accept($released, $rider2);
        Sanctum::actingAs($this->buyer);
        $this->postJson($share, ['share' => true, 'latitude' => 13.7405, 'longitude' => 100.5305])->assertOk();

        $service->cancel($job->fresh(), 'admin', 'ทดสอบยกเลิก');
        $cancelled = $job->fresh();
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->customer_last_latitude);

        $this->getJson("/api/v1/orders/fresh-market/{$order->id}/rider-location")
            ->assertOk()
            ->assertJsonPath('data.rider_location', null)
            ->assertJsonPath('data.customer_sharing.enabled', false);
    }

    public function test_shop_order_rider_location_uses_the_same_rules(): void
    {
        [$shopSeller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($shopSeller, $store, ['price' => 120]);
        $buyer = $this->makeBuyer(500);
        $address = $this->makeAddress($buyer, true);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
            'shipping_fee' => 40,
            'total_amount' => 160,
            'paid_at' => now(),
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
        ]);

        $rider = $this->makeRider();
        $job = new RiderJob;
        $job->forceFill([
            'job_type' => 'shop_delivery',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'title' => 'ส่งสินค้าร้านค้า '.$order->order_number,
            'pickup_address' => '1 ถนนสีลม',
            'pickup_latitude' => self::STORE_LAT,
            'pickup_longitude' => self::STORE_LNG,
            'pickup_contact_name' => $store->store_name,
            'delivery_address' => '99 ถนนพระราม 4',
            'delivery_latitude' => self::STORE_LAT + 0.01,
            'delivery_longitude' => self::STORE_LNG + 0.01,
            'delivery_contact_name' => 'ผู้รับ ทดสอบ',
            'distance_km' => 2,
            'estimated_duration_minutes' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'customer_id' => $buyer->id,
            'rider_id' => $rider->id,
            'status' => 'delivering',
            'accepted_at' => now(),
            'picked_up_at' => now(),
            'tracking_token' => Str::random(48),
            'tracking_expires_at' => now()->addDay(),
            'gps_active' => true,
        ])->save();

        Sanctum::actingAs($buyer);

        $this->getJson("/api/v1/orders/shop/{$order->id}/rider-location")
            ->assertOk()
            ->assertJsonPath('data.source', 'shop')
            ->assertJsonPath('data.job.status', 'delivering')
            ->assertJsonPath('data.location_available', true);

        $this->postJson("/api/v1/orders/shop/{$order->id}/share-location", ['share' => true, 'latitude' => 13.7390, 'longitude' => 100.5310])
            ->assertOk()
            ->assertJsonPath('data.sharing', true);
        $this->assertNotNull($job->fresh()->customerLiveLocation());

        // ผู้ซื้อคนอื่น
        Sanctum::actingAs($this->buyer);
        $this->getJson("/api/v1/orders/shop/{$order->id}/rider-location")->assertStatus(404);

        // เส้นทางเว็บ (session) — เจ้าของออเดอร์เท่านั้น
        $this->actingAs($buyer);
        $this->getJson(route('taladsod.delivery.rider-location', ['shop', $order->id]))
            ->assertOk()
            ->assertJsonPath('data.location_available', true);
        $this->postJson(route('taladsod.delivery.share-location', ['shop', $order->id]), ['share' => false])
            ->assertOk()
            ->assertJsonPath('data.sharing', false);

        $this->actingAs($this->buyer);
        $this->getJson(route('taladsod.delivery.rider-location', ['shop', $order->id]))->assertStatus(404);
    }

    // ===== Helpers =====

    /**
     * ออเดอร์ตลาดสดส่งด้วยไรเดอร์ (จ่าย wallet) ที่ร้านรับแล้ว + งานไรเดอร์ที่รอคนรับ
     *
     * @return array{0: FreshMarketOrder, 1: RiderJob}
     */
    private function riderOrderWithJob(): array
    {
        $service = app(FreshMarketService::class);

        $order = $service->createOrder($this->buyer, $this->listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::BUYER_LAT,
            'buyer_longitude' => self::BUYER_LNG,
            'delivery_address' => '88 ถนนเจริญกรุง แขวงบางรัก',
        ]);
        $service->applyAction($order, 'accept', 'seller', $this->sellerUser);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'fresh_market')->fresh();

        return [$order->fresh(), $job];
    }

    private function actingAsRider(Rider $rider): static
    {
        Sanctum::actingAs($rider->user);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides = []): Rider
    {
        $user = User::factory()->create();

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => self::SHOP_LAT + 0.001,
            'last_longitude' => self::SHOP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides))->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
