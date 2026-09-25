<?php

namespace Tests\Feature\FreshMarket;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\FreshMarketShopFollower;
use App\Models\Notification;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketService;
use App\Services\FreshMarketShopPresenceService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * ร้านรถเข็น / ตลาดนัด (ร้านเคลื่อนที่) + เปิด/ปิดร้าน + ติดตามร้าน (ต้องใช้ MySQL)
 *
 * ครอบคลุม:
 * - ร้านเคลื่อนที่ที่ปิดอยู่: ไม่โผล่ในค้นหาใกล้ฉัน (ไม่เปิดเผยที่อยู่ที่ลงทะเบียน) แต่ยังเห็นร้าน/สินค้า และสั่งไม่ได้ (SHOP_CLOSED)
 * - "เปิดร้านที่นี่วันนี้" → ค้นหาใกล้ฉันใช้ตำแหน่งปัจจุบัน + GET /shops/{id}/location ได้ตำแหน่งสด
 * - ร้านประจำที่ยังใช้ที่อยู่ร้าน · ปิดร้านแล้วยังเห็นแต่สั่งไม่ได้
 * - ติดตามร้าน → ร้านเปิดแจ้งเตือน 1 ครั้งต่อร้านต่อคนทุก 3 ชั่วโมง
 * - ร้านย้ายตำแหน่งระหว่างรอไรเดอร์ → ย้ายจุดรับของ + แจ้งไรเดอร์ครั้งเดียว (ไม่ย้ายหลังรับของแล้ว)
 * - คำสั่งกวาดปิดร้านที่เลยเวลาปิด / ตำแหน่งสดเงียบเกิน 30 นาที
 * - เส้นทางเว็บ (session) ใช้ได้เหมือน API
 */
class FreshMarketMobileVendorTest extends TestCase
{
    use RefreshDatabase;

    /** ที่อยู่ที่ลงทะเบียนของร้านรถเข็น (บ้าน — ห่างจากตลาด ~70 กม.) */
    private const BASE_LAT = 14.3500;

    private const BASE_LNG = 100.5700;

    /** ตลาดนัดที่ไปขายวันนี้ */
    private const MARKET_LAT = 13.7291;

    private const MARKET_LNG = 100.5210;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketSeller $seller;

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

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อทดสอบ']);
        $this->sellerUser = User::factory()->create(['name' => 'ร้านรถเข็นทดสอบ']);

        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'รถเข็นกะเพราป้าแดง',
            'phone' => '0812345678',
            'address' => '9 หมู่บ้านทดสอบ (บ้านเจ้าของร้าน)',
            'latitude' => self::BASE_LAT,
            'longitude' => self::BASE_LNG,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $this->seller->forceFill(['is_mobile' => true, 'is_open' => false])->save();

        // สินค้าถูกคัดลอกพิกัดบ้านมาตอนลงขาย (พฤติกรรม createListing เดิม)
        $this->listing = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ผัดกะเพราหมูสับ',
            'price' => 50,
            'unit' => 'จาน',
            'quantity_available' => 0,
            'track_stock' => false,
            'latitude' => self::BASE_LAT,
            'longitude' => self::BASE_LNG,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    public function test_closed_mobile_shop_is_hidden_from_nearby_but_visible_and_not_orderable(): void
    {
        // ไม่โผล่ทั้งที่ตลาดและที่บ้าน (ไม่เปิดเผยที่อยู่ที่ลงทะเบียน)
        $this->getJson('/api/v1/fresh-market/nearby?lat='.self::MARKET_LAT.'&lng='.self::MARKET_LNG.'&radius=5')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/fresh-market/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG.'&radius=5')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // ค้นหาแบบไม่ใช้พิกัดยังเห็นสินค้า — ป้ายร้านปิด + ไม่มีพิกัด
        $item = collect($this->getJson('/api/v1/fresh-market/listings')->assertOk()->json('data'))
            ->firstWhere('id', $this->listing->id);
        $this->assertNotNull($item, 'ร้านปิดยังต้องเห็นสินค้าในรายการ');
        $this->assertFalse($item['shop_is_open']);
        $this->assertFalse($item['can_order']);
        $this->assertNull($item['latitude'], 'ร้านเคลื่อนที่ที่ปิดอยู่ต้องไม่ส่งพิกัดบ้าน');
        $this->assertSame(FreshMarketSeller::CLOSED_MESSAGE, $item['seller']['closed_message']);

        $this->getJson("/api/v1/fresh-market/shops/{$this->seller->id}")
            ->assertOk()
            ->assertJsonPath('data.is_open', false)
            ->assertJsonPath('data.is_mobile', true)
            ->assertJsonPath('data.can_order', false)
            ->assertJsonPath('data.closed_message', FreshMarketSeller::CLOSED_MESSAGE)
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.address', null)
            ->assertJsonPath('data.listings.0.can_order', false);

        $this->getJson("/api/v1/fresh-market/shops/{$this->seller->id}/location")
            ->assertOk()
            ->assertJsonPath('data.is_open', false)
            ->assertJsonPath('data.location', null)
            ->assertJsonPath('data.closed_message', FreshMarketSeller::CLOSED_MESSAGE);

        Sanctum::actingAs($this->buyer);

        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertStatus(409)->assertJsonPath('code', 'SHOP_CLOSED');

        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertEquals(1000.0, (float) Wallet::where('user_id', $this->buyer->id)->value('balance'), 'สั่งไม่สำเร็จต้องไม่หักเงิน');
    }

    public function test_opening_mobile_shop_uses_current_location_for_nearby_and_live_location(): void
    {
        Sanctum::actingAs($this->sellerUser);

        $this->postJson('/api/v1/fresh-market/seller/open', [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'location_label' => 'ตลาดนัดหน้าโรงเรียน',
            'live_location_sharing' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.just_opened', true)
            ->assertJsonPath('data.location_label', 'ตลาดนัดหน้าโรงเรียน')
            ->assertJsonPath('data.location.source', 'live');

        $seller = $this->seller->fresh();
        $this->assertTrue($seller->isOpenNow());
        $this->assertNotNull($seller->closes_at, 'ร้านเคลื่อนที่ที่ไม่ตั้งเวลาปิด ระบบต้องตั้งเวลาปิดให้');
        $this->assertEqualsWithDelta(self::MARKET_LAT, (float) $seller->current_latitude, 0.00001);

        $data = $this->getJson('/api/v1/fresh-market/nearby?lat='.(self::MARKET_LAT + 0.001).'&lng='.self::MARKET_LNG.'&radius=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0');
        $this->assertSame($this->listing->id, $data['id']);
        $this->assertTrue($data['shop_is_open']);
        $this->assertLessThan(0.5, $data['distance_km']);
        $this->assertEqualsWithDelta(self::MARKET_LAT, $data['latitude'], 0.00001, 'พิกัดต้องเป็นตำแหน่งตลาดวันนี้ ไม่ใช่บ้าน');

        // ค้นหาใกล้บ้าน (ที่อยู่ที่ลงทะเบียน) ไม่เจอ
        $this->getJson('/api/v1/fresh-market/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG.'&radius=5')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // รายการแบบแบ่งหน้า + พิกัด (join + having + นับหน้า) ทุกการเรียง
        foreach (['newest', 'price_asc', 'popular', 'distance'] as $sort) {
            $page = $this->getJson('/api/v1/fresh-market/listings?lat='.self::MARKET_LAT.'&lng='.self::MARKET_LNG.'&radius=2&sort='.$sort)
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->json('data.0');
            $this->assertTrue($page['shop_is_open'], "sort={$sort}");
            $this->assertEqualsWithDelta(self::MARKET_LAT, $page['latitude'], 0.00001, "sort={$sort}");
        }
        $this->getJson('/api/v1/fresh-market/listings?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG.'&radius=5')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
        $this->getJson(route('taladsod.api.listings', ['lat' => self::MARKET_LAT, 'lng' => self::MARKET_LNG, 'radius' => 2]))
            ->assertOk()
            ->assertJsonPath('data.0.shop_is_open', true);

        // หน้าเว็บค้นหาใกล้ฉันก็ใช้ตำแหน่งเดียวกัน
        $web = $this->getJson(route('taladsod.api.nearby', ['lat' => self::MARKET_LAT, 'lng' => self::MARKET_LNG, 'radius' => 2]))
            ->assertOk()
            ->json('data.0');
        $this->assertTrue($web['shop_is_open']);
        $this->assertEqualsWithDelta(self::MARKET_LAT, $web['latitude'], 0.00001);

        $this->getJson("/api/v1/fresh-market/shops/{$this->seller->id}/location")
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.location.is_live', true)
            ->assertJsonPath('data.location.label', 'ตลาดนัดหน้าโรงเรียน')
            ->assertJsonPath('data.poll_interval_seconds', 30);

        // เปิดซ้ำตอนเปิดอยู่ = ไม่ใช่การเปิดร้านใหม่
        $this->postJson('/api/v1/fresh-market/seller/open', ['latitude' => self::MARKET_LAT, 'longitude' => self::MARKET_LNG])
            ->assertOk()
            ->assertJsonPath('data.just_opened', false);

        Sanctum::actingAs($this->buyer);

        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertCreated()
            ->assertJsonPath('data.seller.is_mobile', true)
            ->assertJsonPath('data.seller.latitude', fn ($lat) => abs($lat - self::MARKET_LAT) < 0.00001);
    }

    public function test_fixed_shop_keeps_its_address_and_closing_it_blocks_orders(): void
    {
        $owner = User::factory()->create();
        $fixed = FreshMarketSeller::create([
            'user_id' => $owner->id,
            'shop_name' => 'แผงผักประจำตลาด',
            'phone' => '0898765432',
            'address' => 'ตลาดบางรัก แผง 12',
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $veg = FreshMarketListing::create([
            'seller_id' => $fixed->id,
            'title' => 'ผักบุ้ง',
            'price' => 20,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $this->assertTrue($fixed->fresh()->isOpenNow(), 'ร้านประจำเปิดเป็นค่าเริ่มต้น (พฤติกรรมเดิม)');

        $nearby = $this->getJson('/api/v1/fresh-market/nearby?lat='.self::MARKET_LAT.'&lng='.self::MARKET_LNG.'&radius=2')
            ->assertOk()
            ->json('data');
        $this->assertSame([$veg->id], array_column($nearby, 'id'));
        $this->assertTrue($nearby[0]['shop_is_open']);

        Sanctum::actingAs($owner);

        // ร้านประจำไม่ต้องส่งตำแหน่งสด
        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => 13.70, 'longitude' => 100.50])
            ->assertStatus(422)
            ->assertJsonPath('code', 'NOT_MOBILE_SHOP');

        $this->postJson('/api/v1/fresh-market/seller/close')
            ->assertOk()
            ->assertJsonPath('data.is_open', false)
            ->assertJsonPath('data.was_open', true);

        // ปิดแล้วยังเห็นตามที่อยู่ร้าน แต่สั่งไม่ได้
        $closed = $this->getJson('/api/v1/fresh-market/nearby?lat='.self::MARKET_LAT.'&lng='.self::MARKET_LNG.'&radius=2')
            ->assertOk()
            ->json('data.0');
        $this->assertSame($veg->id, $closed['id']);
        $this->assertFalse($closed['shop_is_open']);

        Sanctum::actingAs($this->buyer);

        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $veg->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertStatus(409)->assertJsonPath('code', 'SHOP_CLOSED');

        // เปิดร้านประจำไม่ต้องส่งพิกัด
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/fresh-market/seller/open')->assertOk()->assertJsonPath('data.is_open', true);
    }

    public function test_follow_notifications_are_sent_at_most_once_per_three_hours(): void
    {
        $presence = app(FreshMarketShopPresenceService::class);
        $countNotified = fn () => Notification::where('user_id', $this->buyer->id)->where('type', 'fresh_market_shop_open')->count();

        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/v1/fresh-market/shops/{$this->seller->id}/follow")
            ->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);
        $this->postJson("/api/v1/fresh-market/shops/{$this->seller->id}/follow")->assertOk();
        $this->assertSame(1, FreshMarketShopFollower::where('seller_id', $this->seller->id)->count(), 'กดซ้ำต้องไม่เกิดแถวซ้ำ');

        $this->getJson('/api/v1/fresh-market/me/followed-shops')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->seller->id)
            ->assertJsonPath('data.0.is_open', false);

        $this->getJson("/api/v1/fresh-market/shops/{$this->seller->id}")
            ->assertOk()
            ->assertJsonPath('data.is_following', true);

        $open = fn () => $presence->open($this->seller->fresh(), ['latitude' => self::MARKET_LAT, 'longitude' => self::MARKET_LNG]);

        $open();
        $this->assertSame(1, $countNotified(), 'ร้านเปิด → แจ้งผู้ติดตาม');

        $notification = Notification::where('user_id', $this->buyer->id)->where('type', 'fresh_market_shop_open')->first();
        $this->assertSame($this->seller->id, $notification->data['shop_id']);
        $this->assertSame('taladsod-shop', $notification->data['screen']);

        // ปิด-เปิดใหม่ภายใน 3 ชั่วโมง → ไม่แจ้งซ้ำ
        $presence->close($this->seller->fresh());
        $open();
        $this->assertSame(1, $countNotified(), 'ภายใน 3 ชม. ต้องไม่แจ้งซ้ำ');

        $this->travel(3)->hours();
        $this->travel(1)->minutes();

        $presence->close($this->seller->fresh());
        $open();
        $this->assertSame(2, $countNotified(), 'เกิน 3 ชม. แจ้งได้อีกครั้ง');

        // เลิกติดตาม → ไม่ได้รับแจ้งอีก
        Sanctum::actingAs($this->buyer);
        $this->deleteJson("/api/v1/fresh-market/shops/{$this->seller->id}/follow")
            ->assertOk()
            ->assertJsonPath('data.is_following', false);

        $this->travel(4)->hours();
        $presence->close($this->seller->fresh());
        $open();
        $this->assertSame(2, $countNotified());

        // ติดตามร้านตัวเองไม่ได้
        Sanctum::actingAs($this->sellerUser);
        $this->postJson("/api/v1/fresh-market/shops/{$this->seller->id}/follow")
            ->assertStatus(422)
            ->assertJsonPath('code', 'CANNOT_FOLLOW_OWN_SHOP');
    }

    public function test_live_location_moves_rider_pickup_before_pickup_and_notifies_rider_once(): void
    {
        $presence = app(FreshMarketShopPresenceService::class);
        $presence->open($this->seller->fresh(), [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'live_location_sharing' => true,
        ]);

        $order = app(FreshMarketService::class)->createOrder($this->buyer, $this->listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => 13.7400,
            'buyer_longitude' => 100.5300,
            'delivery_address' => '88 ถนนเจริญกรุง แขวงบางรัก',
        ]);
        app(FreshMarketService::class)->applyAction($order, 'accept', 'seller', $this->sellerUser);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'fresh_market')->fresh();
        $this->assertEqualsWithDelta(self::MARKET_LAT, (float) $job->pickup_latitude, 0.00001, 'จุดรับของ = ตำแหน่งร้านวันนี้');

        $rider = $this->makeRider();
        app(RiderJobService::class)->accept($job, $rider);

        $pickupNotices = fn () => Notification::where('user_id', $rider->user_id)
            ->where('type', 'rider_job_update')
            ->get()
            ->filter(fn ($n) => ($n->data['event'] ?? null) === 'pickup_moved')
            ->count();

        Sanctum::actingAs($this->sellerUser);

        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => self::MARKET_LAT + 0.009, 'longitude' => self::MARKET_LNG])
            ->assertOk()
            ->assertJsonPath('data.pickups_updated', 1);
        $this->assertEqualsWithDelta(self::MARKET_LAT + 0.009, (float) $job->fresh()->pickup_latitude, 0.00001);
        $this->assertSame(1, $pickupNotices());

        // ขยับนิดเดียว (GPS แกว่ง < 30 ม.) → ไม่ย้าย
        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => self::MARKET_LAT + 0.0091, 'longitude' => self::MARKET_LNG])
            ->assertOk()
            ->assertJsonPath('data.pickups_updated', 0);

        // ย้ายอีกภายใน 5 นาที → ย้ายจุดรับ แต่ไม่แจ้งไรเดอร์ซ้ำ
        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => self::MARKET_LAT + 0.018, 'longitude' => self::MARKET_LNG])
            ->assertOk()
            ->assertJsonPath('data.pickups_updated', 1);
        $this->assertSame(1, $pickupNotices(), 'แจ้งไรเดอร์ไม่เกิน 1 ครั้งต่อ 5 นาที');

        // ไรเดอร์รับของแล้ว → ไม่ย้ายจุดรับอีก
        app(RiderJobService::class)->markPickedUp($job->fresh(), $rider);
        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => self::MARKET_LAT + 0.027, 'longitude' => self::MARKET_LNG])
            ->assertOk()
            ->assertJsonPath('data.pickups_updated', 0);
        $this->assertEqualsWithDelta(self::MARKET_LAT + 0.018, (float) $job->fresh()->pickup_latitude, 0.00001);
    }

    public function test_sweep_closes_shops_after_closing_time_or_stale_live_location(): void
    {
        $presence = app(FreshMarketShopPresenceService::class);

        // ร้าน A: ส่งตำแหน่งสด แล้วเงียบไป
        $presence->open($this->seller->fresh(), [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'live_location_sharing' => true,
        ]);

        // ร้าน B: ปักหมุดครั้งเดียว (ไม่ส่งสด) ตั้งปิด 1 ชม.
        $ownerB = User::factory()->create();
        $shopB = FreshMarketSeller::create([
            'user_id' => $ownerB->id,
            'shop_name' => 'รถเข็นผลไม้',
            'phone' => '0811112222',
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $presence->open($shopB->fresh(), [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'closes_at' => now()->addHour()->toIso8601String(),
        ]);
        $this->assertTrue($shopB->fresh()->isMobileShop(), 'ร้านไม่มีที่อยู่ประจำ + ส่งพิกัด = ร้านเคลื่อนที่');

        $this->travel(31)->minutes();
        $this->artisan('fresh-market:close-stale-shops')->assertSuccessful();

        $this->assertFalse((bool) $this->seller->fresh()->is_open, 'ตำแหน่งสดเงียบเกิน 30 นาที → ปิด');
        $this->assertTrue((bool) $shopB->fresh()->is_open, 'ไม่ได้ส่งสด + ยังไม่ถึงเวลาปิด → เปิดต่อ');
        $this->assertTrue(Notification::where('user_id', $this->sellerUser->id)->where('type', 'fresh_market_shop')->exists());

        $this->travel(30)->minutes();
        $this->artisan('fresh-market:close-stale-shops')->assertSuccessful();
        $this->assertFalse((bool) $shopB->fresh()->is_open, 'เลยเวลาปิดที่ตั้งไว้ → ปิด');
    }

    public function test_open_validation_errors(): void
    {
        Sanctum::actingAs($this->sellerUser);

        $this->postJson('/api/v1/fresh-market/seller/open', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'LOCATION_REQUIRED');

        $this->postJson('/api/v1/fresh-market/seller/open', [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'closes_at' => now()->subHour()->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_CLOSING_TIME');

        $this->postJson('/api/v1/fresh-market/seller/open', ['latitude' => 0, 'longitude' => 0])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_LOCATION');

        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => self::MARKET_LAT, 'longitude' => self::MARKET_LNG])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SHOP_CLOSED');

        Sanctum::actingAs($this->buyer);
        $this->postJson('/api/v1/fresh-market/seller/open', ['latitude' => self::MARKET_LAT, 'longitude' => self::MARKET_LNG])
            ->assertStatus(403)
            ->assertJsonPath('code', 'NOT_SELLER');

        $this->seller->forceFill(['is_suspended' => true])->save();
        Sanctum::actingAs($this->sellerUser);
        $this->postJson('/api/v1/fresh-market/seller/open', ['latitude' => self::MARKET_LAT, 'longitude' => self::MARKET_LNG])
            ->assertStatus(403)
            ->assertJsonPath('code', 'SHOP_UNAVAILABLE');
    }

    public function test_web_session_routes_open_shop_and_follow(): void
    {
        $this->actingAs($this->sellerUser);

        $this->postJson(route('taladsod.seller.open'), [
            'latitude' => self::MARKET_LAT,
            'longitude' => self::MARKET_LNG,
            'location_label' => 'หน้าตลาดนัด',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.is_open', true);

        $this->getJson(route('taladsod.seller.presence'))
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.location_label', 'หน้าตลาดนัด');

        $this->actingAs($this->buyer);

        $this->postJson(route('taladsod.shop.follow', $this->seller->id))
            ->assertOk()
            ->assertJsonPath('data.is_following', true);

        $this->getJson(route('taladsod.shop.location', $this->seller->id))
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.location.label', 'หน้าตลาดนัด');

        $this->getJson(route('taladsod.followed-shops'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->seller->id);

        $this->get(route('taladsod.seller', $this->seller->id))
            ->assertOk()
            ->assertViewHas('isFollowing', true)
            ->assertViewHas('presence', fn ($p) => $p['is_open'] === true && $p['location']['label'] === 'หน้าตลาดนัด')
            ->assertViewHas('shopEndpoints', fn ($e) => str_ends_with($e['location'], '/taladsod/shop/'.$this->seller->id.'/location'));

        $this->actingAs($this->sellerUser);

        $this->get(route('taladsod.seller.dashboard'))
            ->assertOk()
            ->assertViewHas('presence', fn ($p) => $p['is_open'] === true && $p['followers_count'] === 1)
            ->assertViewHas('presenceEndpoints', fn ($e) => isset($e['open'], $e['location'], $e['close'], $e['mobile_mode']));

        $this->postJson(route('taladsod.seller.close'))->assertOk()->assertJsonPath('data.is_open', false);

        // สลับเป็นร้านประจำที่ (มีที่อยู่ร้าน) → เปิดตามที่อยู่ร้าน ตำแหน่งปัจจุบันถูกลบ
        $this->postJson(route('taladsod.seller.mobile-mode'), ['is_mobile' => 0])
            ->assertOk()
            ->assertJsonPath('data.is_mobile', false)
            ->assertJsonPath('data.is_open', true);
        $this->assertNull($this->seller->fresh()->current_latitude);
    }

    // ===== Helpers =====

    private function makeRider(): Rider
    {
        $user = User::factory()->create();

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => self::MARKET_LAT + 0.001,
            'last_longitude' => self::MARKET_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
