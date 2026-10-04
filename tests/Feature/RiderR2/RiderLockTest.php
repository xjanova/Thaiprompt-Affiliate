<?php

namespace Tests\Feature\RiderR2;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\Feature\RiderR2\Concerns\BuildsRiderSocialFixtures;
use Tests\Feature\RiderR2\Fixtures\LockableFakeSource;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ล็อกเรียกไรเดอร์คนโปรด (ไรเดอร์รอบ 2 — เลน social) — ต้องใช้ MySQL
 *
 * - ตอนสั่ง: preferred_rider_id ใช้ได้เฉพาะส่งด้วยไรเดอร์ + ผู้ซื้อให้หัวใจไรเดอร์คนนั้น ≥ 11 ดวง (10 ดวง → 422 RIDER_LOCK_NOT_ALLOWED)
 * - ตอนเรียกไรเดอร์: ข้อเสนอเฉพาะไรเดอร์คนนั้น (dispatch_type locked, push rider_job_offer locked = true)
 *   ไรเดอร์คนอื่นไม่เห็น/เปิด/รับไม่ได้จนหมด rider.lock_offer_seconds → rider:sweep-pending กระจายปกติ
 * - ไรเดอร์ที่ถูกล็อกรับได้ผ่านปุ่มรับงานเดิม · ปฏิเสธ → กระจายปกติทันที · ไม่พร้อม/หัวใจไม่ครบ → ไม่ล็อก
 */
#[Group('rider')]
class RiderLockTest extends TestCase
{
    use BuildsRiderSocialFixtures;
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Cache::flush();
        FakeDeliverableSource::reset();

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');
        $this->setGpRate(10);
    }

    // =====================================================
    // กระจายงาน
    // =====================================================

    public function test_locked_job_is_exclusive_to_preferred_rider_then_falls_back_after_expiry(): void
    {
        $buyer = User::factory()->create(['name' => 'มาลี ศรีสุข']);
        $favorite = $this->makeRider();
        $other = $this->makeRider(['full_name' => 'ไรเดอร์ อื่น']);
        $this->giveHearts($favorite, $buyer, 11);

        $job = $this->createLockedSourceJob($buyer, $favorite->id);

        $this->assertSame('locked', $job->dispatch_type);
        $this->assertSame($favorite->id, (int) $job->preferred_rider_id);
        $this->assertSame($buyer->id, (int) $job->preferred_by_user_id);
        $this->assertSame($favorite->id, (int) $job->current_offer_rider_id);
        $this->assertTrue($job->preferred_until->isFuture());
        $this->assertEqualsWithDelta(60, now()->diffInSeconds($job->preferred_until, false), 3);

        // push ข้อเสนอเฉพาะตัว (locked = true) ไปที่ไรเดอร์คนโปรดคนเดียว
        $offer = Notification::where('user_id', $favorite->user_id)->where('type', 'rider_job_offer')->first();
        $this->assertNotNull($offer);
        $this->assertTrue((bool) ($offer->data['locked'] ?? false));
        $this->assertSame($job->id, (int) $offer->data['job_id']);
        $this->assertStringContainsString('มาลี ศ.', $offer->title);
        $this->assertSame(0, Notification::where('user_id', $other->user_id)->where('type', 'rider_job_offer')->count());

        // ไรเดอร์คนอื่น: ไม่เห็นในรายการ เปิดดูไม่ได้ กดรับไม่ได้
        Sanctum::actingAs($other->user);
        $this->getJson('/api/v1/rider/jobs/available')->assertOk()->assertJsonCount(0, 'data.jobs');
        $this->getJson("/api/v1/rider/jobs/{$job->id}")->assertForbidden();
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")->assertStatus(409)->assertJsonPath('code', 'JOB_TAKEN');
        $this->assertNull($job->fresh()->rider_id);

        // ไรเดอร์คนโปรด: เห็นงาน + รู้ว่าลูกค้าประจำเรียก + การ์ดผู้ซื้อ
        Sanctum::actingAs($favorite->user);
        $this->getJson('/api/v1/rider/jobs/available')
            ->assertOk()
            ->assertJsonPath('data.jobs.0.id', $job->id)
            ->assertJsonPath('data.jobs.0.dispatch_type', 'locked')
            ->assertJsonPath('data.jobs.0.locked_by_buyer', true)
            ->assertJsonPath('data.jobs.0.allowed_actions', ['accept'])
            ->assertJsonPath('data.jobs.0.buyer.display_name', 'มาลี ศ.')
            ->assertJsonPath('data.jobs.0.buyer.hearts_given', 11);

        // หมดเวลาสิทธิ์ → รอบกวาดทุกนาทีกระจายปกติ
        $this->travel(61)->seconds();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $after = $job->fresh();
        $this->assertSame('broadcast', $after->dispatch_type);
        $this->assertNull($after->current_offer_rider_id);
        $this->assertFalse($after->preferred_until->isFuture());
        $this->assertSame($favorite->id, (int) $after->preferred_rider_id, 'เก็บไว้เป็นประวัติว่าผู้ซื้อเคยล็อก');
        $this->assertContains('expired', array_column(array_filter($after->dispatch_attempts, fn ($a) => (int) $a['rider_id'] === $favorite->id), 'status'));
        $this->assertSame(1, Notification::where('user_id', $other->user_id)->where('type', 'rider_job_offer')->count(), 'ไรเดอร์คนอื่นได้แจ้งเตือนหลังหมดสิทธิ์');
        $this->assertSame(1, Notification::where('user_id', $favorite->user_id)->where('type', 'rider_job_offer')->count(), 'ไรเดอร์คนโปรดไม่ถูกแจ้งซ้ำ');

        Sanctum::actingAs($other->user);
        $this->getJson('/api/v1/rider/jobs/available')
            ->assertOk()
            ->assertJsonPath('data.jobs.0.id', $job->id)
            ->assertJsonPath('data.jobs.0.locked_by_buyer', false)
            ->assertJsonPath('data.jobs.0.buyer', null);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")->assertOk();
        $this->assertSame($other->id, (int) $job->fresh()->rider_id);
    }

    public function test_preferred_rider_accepts_through_normal_endpoint(): void
    {
        $buyer = User::factory()->create(['name' => 'มาลี ศรีสุข']);
        $favorite = $this->makeRider();
        $this->giveHearts($favorite, $buyer, 11);

        $job = $this->createLockedSourceJob($buyer, $favorite->id);

        Sanctum::actingAs($favorite->user);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.job.status', 'accepted')
            ->assertJsonPath('data.job.locked_by_buyer', true)
            ->assertJsonPath('data.job.buyer.display_name', 'มาลี ศ.')
            ->assertJsonPath('data.job.buyer.hearts_given', 11);

        $this->assertSame($favorite->id, (int) $job->fresh()->rider_id);

        // หมดเวลาแล้ว sweep ต้องไม่แตะงานที่รับไปแล้ว
        $this->travel(61)->seconds();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame('accepted', $job->fresh()->status);
        $this->assertSame($favorite->id, (int) $job->fresh()->rider_id);
    }

    public function test_preferred_rider_rejecting_releases_lock_immediately(): void
    {
        $buyer = User::factory()->create();
        $favorite = $this->makeRider();
        $other = $this->makeRider();
        $this->giveHearts($favorite, $buyer, 11);

        $job = $this->createLockedSourceJob($buyer, $favorite->id);

        Sanctum::actingAs($favorite->user);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/reject")->assertOk();

        $after = $job->fresh();
        $this->assertSame('broadcast', $after->dispatch_type);
        $this->assertFalse($after->preferred_until->isFuture());
        $this->assertSame(1, Notification::where('user_id', $other->user_id)->where('type', 'rider_job_offer')->count());

        Sanctum::actingAs($other->user);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")->assertOk();
    }

    public function test_ten_hearts_or_unavailable_rider_does_not_lock(): void
    {
        $buyer = User::factory()->create();
        $tenHearts = $this->makeRider();
        $this->giveHearts($tenHearts, $buyer, 10);

        $job = $this->createLockedSourceJob($buyer, $tenHearts->id);
        $this->assertSame('broadcast', $job->dispatch_type);
        $this->assertNull($job->preferred_rider_id);
        $this->assertNull($job->preferred_until);

        // หัวใจครบแต่ไรเดอร์ออฟไลน์ → ไม่เสียเวลารอ 60 วินาที กระจายปกติทันที
        $offline = $this->makeRider(['availability' => 'offline']);
        $this->giveHearts($offline, $buyer, 11);
        $job2 = $this->createLockedSourceJob($buyer, $offline->id);
        $this->assertSame('broadcast', $job2->dispatch_type);
        $this->assertNull($job2->preferred_rider_id);

        // หัวใจครบแต่ไรเดอร์มีงานค้าง
        $busy = $this->makeRider();
        $this->giveHearts($busy, $buyer, 11);
        $this->makeJobFor($this->makeShopOrder(User::factory()->create()), $busy, User::factory()->create(), 'picked_up');
        $job3 = $this->createLockedSourceJob($buyer, $busy->id);
        $this->assertSame('broadcast', $job3->dispatch_type);
    }

    // =====================================================
    // ตอนสั่ง (ร้านค้า)
    // =====================================================

    public function test_shop_checkout_lock_requires_eleven_hearts_and_rider_delivery(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);
        $favorite = $this->makeRider();
        $this->giveHearts($favorite, $buyer, 10);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $payload = [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
            'preferred_rider_id' => $favorite->id,
        ];

        // 10 ดวง → 422 (ยังไม่สร้างออเดอร์ ไม่ตัดเงิน)
        $this->postJson('/api/v1/cart/checkout', $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'RIDER_LOCK_NOT_ALLOWED')
            ->assertJsonPath('data.hearts_from_me', 10)
            ->assertJsonPath('data.lock_min_hearts', 11);
        $this->assertSame(0, Order::where('store_id', $store->id)->count());
        $this->assertEquals(1000.0, $this->walletBalance($buyer));

        // ไรเดอร์ที่ไม่มีอยู่จริง → 422 เหมือนกัน
        $this->postJson('/api/v1/cart/checkout', array_merge($payload, ['preferred_rider_id' => 999999]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'RIDER_LOCK_NOT_ALLOWED');

        // ดวงที่ 11 แต่เลือกส่งพัสดุ → 422
        $this->giveHearts($favorite, $buyer, 1);
        $this->postJson('/api/v1/cart/checkout', array_merge($payload, ['delivery_method' => 'parcel']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'RIDER_LOCK_NOT_ALLOWED');

        // 11 ดวง + ส่งด้วยไรเดอร์ → สั่งได้ และจำไรเดอร์ที่ล็อกไว้ในออเดอร์
        $this->postJson('/api/v1/cart/checkout', $payload)->assertCreated();
        $order = Order::where('store_id', $store->id)->firstOrFail();
        $this->assertSame($favorite->id, (int) $order->preferred_rider_id);
        $this->assertSame('paid', $order->payment_status);

        // ร้านเรียกไรเดอร์ → ข้อเสนอเฉพาะไรเดอร์คนโปรด
        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'shop_delivery')->fresh();
        $this->assertSame('locked', $job->dispatch_type);
        $this->assertSame($favorite->id, (int) $job->current_offer_rider_id);
        $this->assertSame($buyer->id, (int) $job->preferred_by_user_id);
    }

    public function test_shop_checkout_without_preferred_rider_is_unchanged(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
        ])->assertCreated();

        $this->assertNull(Order::where('user_id', $buyer->id)->value('preferred_rider_id'));

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
            'preferred_rider_id' => 'abc',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // =====================================================
    // ตอนสั่ง (ตลาดสด)
    // =====================================================

    public function test_fresh_market_order_lock_requires_eleven_hearts(): void
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

        $buyer = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
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
        $listing = FreshMarketListing::create([
            'seller_id' => $shop->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $favorite = $this->makeRider();
        $this->giveHearts($favorite, $buyer, 10);

        $payload = [
            'items' => [['listing_id' => $listing->id, 'quantity' => 1]],
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::BASE_LAT + 0.01,
            'buyer_longitude' => self::BASE_LNG + 0.01,
            'delivery_address' => '99 ถนนทดสอบ แขวงสุริยวงศ์ เขตบางรัก',
            'preferred_rider_id' => $favorite->id,
        ];

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/fresh-market/orders', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'RIDER_LOCK_NOT_ALLOWED');
        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $buyer->id)->count());
        $this->assertSame(10, (int) $listing->fresh()->quantity_available, 'ถูกปฏิเสธต้องไม่ตัดสต็อก');

        $this->postJson('/api/v1/fresh-market/orders', array_merge($payload, ['delivery_type' => 'pickup']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'RIDER_LOCK_NOT_ALLOWED');

        $this->giveHearts($favorite, $buyer, 1);
        $this->postJson('/api/v1/fresh-market/orders', $payload)->assertCreated();

        $order = FreshMarketOrder::where('buyer_id', $buyer->id)->firstOrFail();
        $this->assertSame($favorite->id, (int) $order->preferred_rider_id);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * งานจากออเดอร์ปลอมที่ผู้ซื้อล็อกเรียกไรเดอร์ $riderId (จุดรับของ = จุดอ้างอิง)
     */
    private function createLockedSourceJob(User $buyer, int $riderId): RiderJob
    {
        $holder = User::factory()->create();
        $seller = User::factory()->create();

        LockableFakeSource::configure((int) $holder->id, [
            'pickup' => [
                'name' => 'ร้านป้าแดง',
                'address' => 'ตลาดบางรัก เขตบางรัก กรุงเทพมหานคร',
                'latitude' => self::BASE_LAT,
                'longitude' => self::BASE_LNG,
                'phone' => '0811111111',
                'notes' => null,
            ],
            'dropoff' => [
                'name' => 'คุณลูกค้า',
                'address' => '88/8 ซ.เจริญกรุง 30 แขวงบางรัก เขตบางรัก กรุงเทพมหานคร 10500',
                'latitude' => 13.7400,
                'longitude' => 100.5300,
                'phone' => '0822222222',
                'notes' => null,
            ],
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
            'preferred_rider_id' => $riderId,
        ]);

        return app(RiderDispatchService::class)
            ->createJobForSource(LockableFakeSource::findOrFail($holder->id), 'fresh_market')
            ->fresh();
    }
}
