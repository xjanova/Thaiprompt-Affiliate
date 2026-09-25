<?php

namespace Tests\Feature\FreshMarket;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOption;
use App\Models\FreshMarketListingOptionGroup;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketCartService;
use App\Services\FreshMarketService;
use App\Services\RiderDispatchService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * หน้าเว็บตลาดสดธีม V4 (ต้องใช้ MySQL)
 *
 * ครอบคลุม:
 * - หน้าสาธารณะ (ผู้ใช้ทั่วไป): หน้าแรก / ค้นหา / หมวด / สินค้า / หน้าร้าน (ร้านประจำ, รถเข็นเปิดอยู่, ร้านปิด) / landing / ติดตามไรเดอร์
 * - หน้าผู้ซื้อ: ตะกร้า / ชำระเงิน / ออเดอร์ / รายละเอียดออเดอร์ (นัดรับ + ไรเดอร์)
 * - หน้าผู้ขาย: สมัคร / แดชบอร์ด / ออเดอร์ / รายละเอียดออเดอร์ / สินค้า / ลงขาย / แก้ไข / รายได้ / ตั้งค่าร้าน
 * - endpoint ใหม่ของหน้าเว็บ: ร้านใกล้ฉัน, สถานะออเดอร์, ตัวเลขออเดอร์ร้าน, login-continue, แก้รูปสินค้า
 * ทุกหน้าต้องตอบ 200 และใช้ธีม V4 (มีคลาส tp-card) — ไม่มี layout เก่า (layouts.taladsod)
 */
class TaladsodWebV4PagesTest extends TestCase
{
    use RefreshDatabase;

    /** ร้านประจำที่ (สีลม) */
    private const SHOP_LAT = 13.7291;

    private const SHOP_LNG = 100.5210;

    /** จุดที่รถเข็นเปิดร้านวันนี้ (ห่างร้านประจำ ~1.5 กม.) */
    private const CART_LAT = 13.7400;

    private const CART_LNG = 100.5300;

    private const BUYER_LAT = 13.7350;

    private const BUYER_LNG = 100.5250;

    protected User $buyer;

    protected User $sellerUser;

    protected User $cartUser;

    protected User $closedUser;

    protected FreshMarketSeller $shop;

    protected FreshMarketSeller $cartShop;

    protected FreshMarketSeller $closedShop;

    protected FreshMarketCategory $category;

    protected FreshMarketListing $krapao;

    protected FreshMarketListing $cartListing;

    protected FreshMarketListing $closedListing;

    /** @var array<string, FreshMarketListingOption> */
    protected array $opt = [];

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
        $this->sellerUser = User::factory()->create(['name' => 'แม่ค้าร้านประจำ']);
        $this->cartUser = User::factory()->create(['name' => 'ลุงรถเข็น']);
        $this->closedUser = User::factory()->create(['name' => 'ป้าร้านปิด']);

        foreach ([$this->buyer, $this->sellerUser, $this->cartUser, $this->closedUser] as $user) {
            Wallet::create(['user_id' => $user->id, 'balance' => $user->is($this->buyer) ? 1000 : 0, 'currency' => 'THB', 'status' => 'active']);
        }

        $this->category = FreshMarketCategory::create(['name' => 'อาหารพร้อมทาน', 'slug' => 'ready-meals-v4', 'icon' => '🍛', 'is_active' => true]);

        // ร้านประจำที่ เปิดอยู่
        $this->shop = $this->makeShop($this->sellerUser, 'ครัวป้าแดง', self::SHOP_LAT, self::SHOP_LNG);

        // รถเข็นที่เปิดร้านอยู่ + ส่งตำแหน่งสด
        $this->cartShop = $this->makeShop($this->cartUser, 'รถเข็นลุงหนวด', 14.3500, 100.5700);
        $this->cartShop->forceFill([
            'is_mobile' => true,
            'is_open' => true,
            'opened_at' => now()->subHour(),
            'closes_at' => now()->addHours(4),
            'current_latitude' => self::CART_LAT,
            'current_longitude' => self::CART_LNG,
            'location_label' => 'ตลาดนัดหน้าโรงเรียน',
            'location_updated_at' => now(),
            'live_location_sharing' => true,
        ])->save();

        // รถเข็นที่ปิดร้านอยู่
        $this->closedShop = $this->makeShop($this->closedUser, 'รถเข็นป้าปิด', 14.3600, 100.5800);
        $this->closedShop->forceFill(['is_mobile' => true, 'is_open' => false])->save();

        // เมนูเปิดตัว: ผัดกะเพรา + ตัวเลือก
        $this->krapao = $this->makeListing($this->shop, 'ผัดกะเพราราดข้าว', 50, 'จาน', false);
        $meat = FreshMarketListingOptionGroup::create([
            'listing_id' => $this->krapao->id, 'name' => 'เลือกเนื้อสัตว์', 'selection_type' => 'single',
            'is_required' => true, 'min_select' => 1, 'max_select' => 1, 'sort_order' => 0,
        ]);
        $extra = FreshMarketListingOptionGroup::create([
            'listing_id' => $this->krapao->id, 'name' => 'เพิ่มเติม', 'selection_type' => 'multi',
            'is_required' => false, 'min_select' => 0, 'max_select' => 1, 'sort_order' => 1,
        ]);
        foreach ([['pork', 'หมูสับ', 0], ['chicken', 'ไก่', 0], ['squid', 'หมึก', 10], ['shrimp', 'กุ้ง', 20]] as $i => [$key, $name, $delta]) {
            $this->opt[$key] = FreshMarketListingOption::create([
                'group_id' => $meat->id, 'listing_id' => $this->krapao->id, 'name' => $name,
                'price_delta' => $delta, 'is_available' => true, 'sort_order' => $i,
            ]);
        }
        $this->opt['egg'] = FreshMarketListingOption::create([
            'group_id' => $extra->id, 'listing_id' => $this->krapao->id, 'name' => 'ไข่ดาว',
            'price_delta' => 10, 'is_available' => true, 'sort_order' => 0,
        ]);

        $this->cartListing = $this->makeListing($this->cartShop, 'ข้าวเหนียวหมูปิ้ง', 30, 'ชุด', false);
        $this->closedListing = $this->makeListing($this->closedShop, 'ขนมครกใบเตย', 25, 'กล่อง', false);
    }

    // =====================================================
    // หน้าสาธารณะ (ยังไม่ login)
    // =====================================================

    public function test_public_market_pages_render_v4_for_guest(): void
    {
        $this->assertV4Page($this->get(route('taladsod.home')), ['เปิดอยู่ใกล้คุณ', 'ครัวป้าแดง', 'รถเข็นลุงหนวด', 'ผัดกะเพราราดข้าว']);
        $this->assertV4Page($this->get(route('taladsod.search')), ['อยากกินอะไร']);
        $this->assertV4Page($this->get(route('taladsod.search', ['q' => 'กะเพรา'])), ['ผัดกะเพราราดข้าว']);
        $this->assertV4Page($this->get(route('taladsod.category', $this->category->slug)), ['อาหารพร้อมทาน', 'ผัดกะเพราราดข้าว']);
        $this->assertV4Page($this->get(route('taladsod.listing', $this->krapao->slug)), ['ผัดกะเพราราดข้าว', 'ครัวป้าแดง', 'ติดตามร้าน']);
    }

    public function test_shop_pages_render_for_fixed_mobile_and_closed_shops(): void
    {
        $this->assertV4Page($this->get(route('taladsod.seller', $this->shop->id)), ['ครัวป้าแดง', 'ผัดกะเพราราดข้าว', 'ร้านประจำที่']);
        $this->assertV4Page($this->get(route('taladsod.seller', $this->cartShop->id)), ['รถเข็นลุงหนวด', 'ตลาดนัดหน้าโรงเรียน', 'รถเข็น/ตลาดนัด']);
        $this->assertV4Page($this->get(route('taladsod.seller', $this->closedShop->id)), ['รถเข็นป้าปิด', 'ร้านปิดอยู่', 'แจ้งเตือนฉันเมื่อร้านเปิด'])
            // รถเข็นที่ปิดอยู่ต้องไม่เปิดเผยตำแหน่งที่ลงทะเบียน (บ้าน)
            ->assertDontSee('14.36', false);
        $this->assertV4Page($this->get(route('taladsod.listing', $this->closedListing->slug)), ['ขนมครกใบเตย', 'ร้านปิดอยู่']);
    }

    public function test_nearby_shops_endpoint_lists_open_shops_with_live_mobile_carts_first_by_distance(): void
    {
        $response = $this->getJson(route('taladsod.api.nearby-shops', ['lat' => self::BUYER_LAT, 'lng' => self::BUYER_LNG, 'radius' => 5]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $shops = collect($response->json('data.shops'));
        $this->assertEqualsCanonicalizing([$this->shop->id, $this->cartShop->id], $shops->pluck('id')->all());
        $this->assertNotContains($this->closedShop->id, $shops->pluck('id')->all());

        $cart = $shops->firstWhere('id', $this->cartShop->id);
        $this->assertTrue($cart['is_mobile']);
        $this->assertTrue($cart['location']['is_live']);
        $this->assertEqualsWithDelta(self::CART_LAT, $cart['location']['latitude'], 0.0001);
        $this->assertSame('ข้าวเหนียวหมูปิ้ง', $cart['top_items'][0]['title']);
        $this->assertIsFloat($cart['distance_km'] + 0.0);

        // ระยะทางเรียงจากใกล้ไปไกล
        $distances = $shops->pluck('distance_km')->all();
        $sorted = $distances;
        sort($sorted);
        $this->assertSame($sorted, $distances);

        // API แอปคืนข้อมูลชุดเดียวกัน
        $this->getJson('/api/v1/fresh-market/shops/nearby?lat='.self::BUYER_LAT.'&lng='.self::BUYER_LNG.'&radius=5')
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        $this->getJson(route('taladsod.api.nearby-shops', ['lat' => 'abc', 'lng' => 1]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_landing_pages_render_v4_with_launch_images(): void
    {
        $this->assertV4Page($this->get(route('taladsod.landing.buyer')), ['ของสด อาหารร้อนๆ', 'banner-market.webp', 'ผัดกะเพราราดข้าว']);
        $this->assertV4Page($this->get(route('taladsod.landing.seller')), ['เปิดร้าน', 'banner-merchant.webp', 'เปิดร้านที่นี่วันนี้']);
        $this->assertV4Page($this->get(route('taladsod.landing.rider')), ['มาเป็นไรเดอร์', 'banner-rider.webp', 'สมัครเป็นไรเดอร์']);

        // โปรฯ GP ฟรีช่วงเปิดตัว → หน้าผู้ขายต้องบอกชัดๆ
        Setting::set('pricing.gp_free', '1', 'boolean', 'pricing');
        Setting::set('pricing.gp_free_until', '', 'string', 'pricing');
        $this->get(route('taladsod.landing.seller'))->assertOk()->assertSee('ฟรี GP ช่วงเปิดตัว');
    }

    // =====================================================
    // หน้าผู้ซื้อ
    // =====================================================

    public function test_buyer_cart_checkout_and_order_pages_render_v4(): void
    {
        $cart = app(FreshMarketCartService::class);
        $cart->addItem($this->buyer, $this->krapao->id, 2, [$this->opt['shrimp']->id, $this->opt['egg']->id], 'เผ็ดน้อย');
        $cart->addItem($this->buyer, $this->cartListing->id, 1, []);

        $this->actingAs($this->buyer);

        $this->assertV4Page($this->get(route('taladsod.cart')), ['ตะกร้าตลาดสด', 'ครัวป้าแดง', 'รถเข็นลุงหนวด', 'ผัดกะเพราราดข้าว', 'กุ้ง, ไข่ดาว', 'เผ็ดน้อย', 'สั่งซื้อจากร้านนี้']);
        $this->assertV4Page($this->get(route('taladsod.checkout', ['seller' => $this->shop->id])), ['ยืนยันสั่งซื้อ', 'รับเองที่ร้าน', 'ไรเดอร์ส่งถึงบ้าน', 'เก็บเงินปลายทาง', 'กุ้ง, ไข่ดาว']);

        // สั่งแบบรับเอง + เก็บเงินปลายทาง
        $this->post(route('taladsod.checkout.store'), [
            'seller_id' => $this->shop->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = FreshMarketOrder::where('buyer_id', $this->buyer->id)->latest('id')->firstOrFail();
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $order->order_status);

        $this->assertV4Page($this->get(route('taladsod.orders')), [$order->order_number, 'ครัวป้าแดง', 'รอร้านยืนยัน']);
        $this->assertV4Page($this->get(route('taladsod.orders', ['status' => 'completed'])), ['ไม่มีออเดอร์ในสถานะนี้']);
        $this->assertV4Page($this->get(route('taladsod.orders.show', $order)), ['#'.$order->order_number, 'ผัดกะเพราราดข้าว', 'กุ้ง, ไข่ดาว', 'ยกเลิกออเดอร์', 'นำทางไปร้าน']);

        $this->getJson(route('taladsod.orders.status-json', $order))
            ->assertOk()
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.allowed_actions', ['cancel']);

        // ออเดอร์ของคนอื่น = ไม่พบ
        $this->actingAs($this->cartUser)
            ->getJson(route('taladsod.orders.status-json', $order))
            ->assertStatus(404)
            ->assertJsonPath('code', 'ORDER_NOT_FOUND');
    }

    public function test_rider_order_detail_shows_live_tracking_and_public_tracking_page_works(): void
    {
        [$order, $job, $rider] = $this->riderOrderAcceptedByRider();

        $this->actingAs($this->buyer);
        $this->assertV4Page($this->get(route('taladsod.orders.show', $order)), ['ติดตามไรเดอร์', 'แชร์ตำแหน่งของฉันให้ไรเดอร์', 'ข้าวเหนียวหมูปิ้ง']);

        // หน้าติดตามแบบ token (ไม่ต้อง login)
        auth()->logout();
        $this->assertV4Page($this->get(route('taladsod.track.show', $job->tracking_token)), [$job->job_number, $rider->full_name, 'โทรหาไรเดอร์']);
        $this->getJson(route('taladsod.track.location', $job->tracking_token))
            ->assertOk()
            ->assertJsonPath('is_active', true);

        // เจ้าของออเดอร์ที่ login อยู่เห็นปุ่มแชร์ตำแหน่งบนหน้าติดตามด้วย
        $this->actingAs($this->buyer)
            ->get(route('taladsod.track.show', $job->tracking_token))
            ->assertOk()
            ->assertSee('แชร์ตำแหน่งของฉันให้ไรเดอร์');
    }

    public function test_rider_active_job_page_renders_v4_with_openstreetmap(): void
    {
        [, $job, $rider] = $this->riderOrderAcceptedByRider();

        $html = $this->assertV4Page(
            $this->actingAs($rider->user)->get(route('taladsod.rider.active-job', $job)),
            ['#'.$job->job_number, 'จุดรับของ', 'จุดส่งของ', 'รถเข็นลุงหนวด']
        )->getContent();

        $this->assertStringContainsString('leaflet', (string) $html);
        $this->assertStringNotContainsString('maps.googleapis.com/maps/api/js', (string) $html);
    }

    // =====================================================
    // หน้าผู้ขาย
    // =====================================================

    public function test_seller_pages_render_v4_for_fixed_mobile_and_closed_shops(): void
    {
        $order = $this->pendingPickupOrder();

        $this->actingAs($this->sellerUser);
        $this->assertV4Page($this->get(route('taladsod.seller.dashboard')), ['ครัวป้าแดง', 'ร้านเปิดอยู่', 'มีออเดอร์รอรับ', 'ค่าธรรมเนียมการขาย (GP)', 'ผัดกะเพราราดข้าว']);
        $this->assertV4Page($this->get(route('taladsod.seller.orders')), [$order->order_number, 'กุ้ง, ไข่ดาว', 'เผ็ดน้อย', 'รับออเดอร์', 'อัปเดตอัตโนมัติ']);
        $this->assertV4Page($this->get(route('taladsod.seller.orders', ['status' => 'pending'])), [$order->order_number]);
        $this->assertV4Page($this->get(route('taladsod.seller.orders.show', $order)), ['รายการที่ต้องเตรียม', 'กุ้ง, ไข่ดาว', 'รับออเดอร์', 'ยกเลิกออเดอร์']);
        $this->assertV4Page($this->get(route('taladsod.seller.listings')), ['ผัดกะเพราราดข้าว', '2 กลุ่มตัวเลือก', 'ลงขายสินค้าใหม่']);
        $this->assertV4Page($this->get(route('taladsod.seller.listings', ['status' => 'sold_out'])), ['ไม่มีสินค้าในหมวดนี้']);
        $this->assertV4Page($this->get(route('taladsod.listing.create')), ['ลงขายสินค้าใหม่', 'ตัวเลือกสินค้า', 'เลือกเนื้อสัตว์']);
        $this->assertV4Page($this->get(route('taladsod.listing.edit', $this->krapao->id)), ['แก้ไขสินค้า', 'ผัดกะเพราราดข้าว', 'ลบสินค้า']);
        $this->assertV4Page($this->get(route('taladsod.seller.earnings')), ['รายได้ร้าน', 'รายได้สุทธิ 14 วันล่าสุด', 'เงินที่กำลังจะได้']);
        $this->assertV4Page($this->get(route('taladsod.seller.profile')), ['ตั้งค่าร้าน', 'ครัวป้าแดง', 'ปักหมุดร้าน']);

        // รถเข็นที่เปิดอยู่ + ส่งตำแหน่งสด
        $this->actingAs($this->cartUser);
        $this->assertV4Page($this->get(route('taladsod.seller.dashboard')), ['รถเข็นลุงหนวด', 'ร้านเปิดอยู่', 'ส่งตำแหน่งสดทุก 30 วินาที', 'ปิดร้าน']);

        // รถเข็นที่ปิดอยู่
        $this->actingAs($this->closedUser);
        $this->assertV4Page($this->get(route('taladsod.seller.dashboard')), ['รถเข็นป้าปิด', 'ร้านปิดอยู่', 'เปิดร้านที่นี่วันนี้']);

        // ยังไม่มีร้าน → หน้าสมัคร (แดชบอร์ดพาไปหน้าสมัคร)
        $newbie = User::factory()->create();
        $this->actingAs($newbie)->get(route('taladsod.seller.dashboard'))->assertRedirect(route('taladsod.register-seller'));
        $this->assertV4Page($this->actingAs($newbie)->get(route('taladsod.register-seller')), ['สมัครเปิดร้านตลาดสด', 'ปักหมุดร้าน', 'สมัครเปิดร้าน']);
    }

    public function test_seller_action_from_list_returns_to_list_and_poll_reports_new_orders(): void
    {
        $order = $this->pendingPickupOrder();
        $this->actingAs($this->sellerUser);

        $this->getJson(route('taladsod.seller.orders.poll'))
            ->assertOk()
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.latest_order_id', $order->id);

        $this->put(route('taladsod.seller.orders.status', $order), [
            'action' => 'accept',
            'return_to' => 'list',
            'return_status' => 'pending',
        ])->assertRedirect(route('taladsod.seller.orders', ['status' => 'pending']));
        $this->assertSame(FreshMarketOrder::STATUS_ACCEPTED, $order->fresh()->order_status);

        // ยกเลิกต้องมีเหตุผล (ข้อความไทย)
        $this->from(route('taladsod.seller.orders'))
            ->put(route('taladsod.seller.orders.status', $order), ['action' => 'cancel', 'return_to' => 'list'])
            ->assertRedirect(route('taladsod.seller.orders'))
            ->assertSessionHasErrors(['reason' => 'กรุณาระบุเหตุผลที่ยกเลิก']);

        // ไม่ใช่ร้าน → 403 JSON
        $this->actingAs($this->buyer)->getJson(route('taladsod.seller.orders.poll'))->assertStatus(403)->assertJsonPath('code', 'NOT_SELLER');
    }

    public function test_login_continue_only_redirects_inside_taladsod(): void
    {
        $this->get(route('taladsod.login-continue', ['to' => '/taladsod/listing/'.$this->krapao->slug]))->assertRedirect(route('login'));

        $this->actingAs($this->buyer);
        $this->get(route('taladsod.login-continue', ['to' => '/taladsod/listing/'.$this->krapao->slug]))
            ->assertRedirect(url('/taladsod/listing/'.$this->krapao->slug));

        foreach (['https://evil.example/x', '//evil.example', '/taladsod/../admin', '/admin', '/taladsodx'] as $bad) {
            $this->get(route('taladsod.login-continue', ['to' => $bad]))->assertRedirect(url('/taladsod'));
        }
    }

    public function test_seller_can_add_and_remove_listing_images_when_editing(): void
    {
        Storage::disk('public')->put('fresh-market/old1.jpg', 'x');
        Storage::disk('public')->put('fresh-market/old2.jpg', 'x');
        $this->krapao->forceFill([
            'images' => ['/storage/fresh-market/old1.jpg', '/storage/fresh-market/old2.jpg'],
            'main_image_url' => '/storage/fresh-market/old1.jpg',
        ])->save();

        $base = [
            'title' => 'ผัดกะเพราราดข้าว',
            'category_id' => $this->category->id,
            'price' => 55,
            'unit' => 'จาน',
            'track_stock' => '0',
            'is_organic' => '0',
            'is_available' => '1',
        ];

        $this->actingAs($this->sellerUser)
            ->put(route('taladsod.listing.update', $this->krapao), $base + [
                'images' => [UploadedFile::fake()->image('new.jpg', 60, 60)],
                'remove_images' => ['/storage/fresh-market/old1.jpg', '/storage/fresh-market/not-mine.jpg'],
                'main_image' => '/storage/fresh-market/old2.jpg',
            ])
            ->assertRedirect(route('taladsod.seller.listings'));

        $fresh = $this->krapao->fresh();
        $this->assertCount(2, $fresh->images);
        $this->assertSame('/storage/fresh-market/old2.jpg', $fresh->images[0]);
        $this->assertStringStartsWith('/storage/fresh-market/', $fresh->images[1]);
        $this->assertSame('/storage/fresh-market/old2.jpg', $fresh->main_image_url);
        $this->assertEquals(55, (float) $fresh->price);
        Storage::disk('public')->assertMissing('fresh-market/old1.jpg');
        Storage::disk('public')->assertExists(substr($fresh->images[1], strlen('/storage/')));
        // ไม่ได้ส่งส่วนตัวเลือกมา → ตัวเลือกเดิมอยู่ครบ
        $this->assertSame(2, $fresh->optionGroups()->count());

        // เกิน 5 รูป → ข้อความไทย ไม่บันทึก
        $this->from(route('taladsod.listing.edit', $this->krapao->id))
            ->put(route('taladsod.listing.update', $this->krapao), $base + [
                'images' => array_map(fn ($i) => UploadedFile::fake()->image("n{$i}.jpg", 20, 20), range(1, 4)),
            ])
            ->assertRedirect(route('taladsod.listing.edit', $this->krapao->id))
            ->assertSessionHasErrors('images');
        $this->assertCount(2, $this->krapao->fresh()->images);

        // ช่องที่ไม่ได้กรอกได้ข้อความไทย
        $this->from(route('taladsod.listing.edit', $this->krapao->id))
            ->put(route('taladsod.listing.update', $this->krapao), ['title' => ''] + $base)
            ->assertSessionHasErrors(['title' => 'กรุณากรอกชื่อสินค้า']);
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * ผู้ซื้อสั่งผัดกะเพรา (กุ้ง + ไข่ดาว) แบบรับเอง เก็บเงินปลายทาง → ออเดอร์รอร้านรับ
     */
    private function pendingPickupOrder(): FreshMarketOrder
    {
        $cart = app(FreshMarketCartService::class);
        $cart->addItem($this->buyer, $this->krapao->id, 2, [$this->opt['shrimp']->id, $this->opt['egg']->id], 'เผ็ดน้อย');

        return $cart->checkout($this->buyer, $this->shop->id, [
            'delivery_type' => 'pickup',
            'payment_method' => 'cod',
            'channel' => 'web',
        ]);
    }

    /**
     * ออเดอร์ส่งด้วยไรเดอร์จากรถเข็น → ร้านรับ → ไรเดอร์รับงาน
     *
     * @return array{0: FreshMarketOrder, 1: \App\Models\RiderJob, 2: Rider}
     */
    private function riderOrderAcceptedByRider(): array
    {
        $service = app(FreshMarketService::class);

        $order = $service->createOrder($this->buyer, $this->cartListing->fresh(), [
            'quantity' => 2,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::BUYER_LAT,
            'buyer_longitude' => self::BUYER_LNG,
            'delivery_address' => '88 ถนนเจริญกรุง แขวงบางรัก',
        ]);
        $service->applyAction($order, 'accept', 'seller', $this->cartUser);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'fresh_market')->fresh();

        $riderUser = User::factory()->create(['name' => 'ไรเดอร์ทดสอบ']);
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $riderUser->id,
            'full_name' => 'สมชาย ส่งไว',
            'phone' => '0899999999',
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'กข 1234',
            'gps_permission_granted' => true,
            'last_latitude' => self::CART_LAT + 0.001,
            'last_longitude' => self::CART_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();
        app(WalletService::class)->getOrCreateWallet($riderUser);

        $job = app(\App\Services\RiderJobService::class)->accept($job, $rider->fresh());

        return [$order->fresh(), $job->fresh(), $rider->fresh()];
    }

    /**
     * @param  array<int, string>  $texts
     */
    private function assertV4Page($response, array $texts = [])
    {
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('tp-card', $html, 'หน้าไม่ได้ใช้ธีม V4');
        $this->assertStringContainsString('ts-scope', $html, 'หน้าไม่มีชุด UI ตลาดสด');
        $this->assertStringNotContainsString('taladsod/app.js', $html, 'ยังใช้ layout ตลาดสดเก่า');

        foreach ($texts as $text) {
            $response->assertSee($text, false);
        }

        return $response;
    }

    private function makeShop(User $user, string $name, float $lat, float $lng): FreshMarketSeller
    {
        return FreshMarketSeller::create([
            'user_id' => $user->id,
            'shop_name' => $name,
            'phone' => '0812345678',
            'address' => '99 ถนนทดสอบ',
            'province' => 'กรุงเทพมหานคร',
            'latitude' => $lat,
            'longitude' => $lng,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
            'total_listings' => 1,
        ]);
    }

    private function makeListing(FreshMarketSeller $shop, string $title, float $price, string $unit, bool $trackStock): FreshMarketListing
    {
        return FreshMarketListing::create([
            'seller_id' => $shop->id,
            'category_id' => $this->category->id,
            'title' => $title,
            'description' => 'ทำสดใหม่ทุกวัน',
            'price' => $price,
            'unit' => $unit,
            'quantity_available' => $trackStock ? 20 : 0,
            'track_stock' => $trackStock,
            'latitude' => $shop->latitude,
            'longitude' => $shop->longitude,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }
}
