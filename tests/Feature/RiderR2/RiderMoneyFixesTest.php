<?php

namespace Tests\Feature\RiderR2;

use App\Exceptions\RiderJobException;
use App\Models\FreshMarketConversation;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketService;
use App\Services\Media\ProfilePhotoException;
use App\Services\Media\ProfilePhotoService;
use App\Services\RiderDispatchService;
use App\Services\Routing\Polyline;
use App\Services\Shop\ShopCartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 💸 ไรเดอร์รอบ 2 (fix lane money) — L4 / F5 / M4 / M5 (ต้องใช้ MySQL)
 *
 * - L4: ออเดอร์ที่จ่ายแล้วเรียกไรเดอร์ได้แม้ระยะคำนวณใหม่เกินพื้นที่ (ผู้ให้บริการเส้นทางเปลี่ยน) — ใช้ค่าส่งที่ล็อกตอนสั่ง
 *       ออเดอร์ที่ยังไม่จ่ายยังถูกปฏิเสธเหมือนเดิม
 * - F5: LINE + เว็บตลาดสด ส่งด้วยไรเดอร์ไม่ตกไปเก็บเงินปลายทางเมื่อ rider.allow_cod = false → ใช้ wallet
 *       (ยอดไม่พอ = บอกชัดเป็นภาษาไทยก่อนกดยืนยัน) · หน้าเว็บซ่อนเก็บปลายทางเมื่อเลือกไรเดอร์
 * - M4: รูปโปรไฟล์ความละเอียดเกิน 25 ล้านพิกเซลถูกปฏิเสธก่อนถอดรหัส
 * - M5: รูปเดิม (LINE/Google/FB) คืนให้เจ้าของเท่านั้น (เทสต์ละเอียดอยู่ใน ProfilePhotoTest)
 */
#[Group('rider-r2')]
class RiderMoneyFixesTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Http::fake() แยกในแต่ละเทสต์ — เทสต์ L4 ต้องลงทะเบียนคำตอบ Valhalla ก่อนตัวรับทุก URL
        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->setGpRate(10);
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');

        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // =====================================================
    // L4: เรียกไรเดอร์ให้ออเดอร์ที่จ่ายแล้ว
    // =====================================================

    public function test_paid_order_is_dispatched_with_checkout_fee_when_new_route_exceeds_service_area(): void
    {
        $shape = Polyline::encode([[self::STORE_LAT, self::STORE_LNG], [self::STORE_LAT + 0.01, self::STORE_LNG + 0.01]], 6);
        Http::fake([
            '127.0.0.1:8002/route' => Http::response(['trip' => [
                'summary' => ['length' => 25.0, 'time' => 3000],
                'legs' => [['shape' => $shape]],
            ]]),
            '*' => Http::response(),
        ]);
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 150]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        // ตอนสั่ง: Valhalla ปิด → ระยะเส้นตรง 2.02 กม. ค่าส่ง 31 (อยู่ในพื้นที่)
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet', 'delivery_method' => 'rider'])
            ->assertCreated();
        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(31, (float) $order->shipping_fee);

        // ภายหลัง: เปิด Valhalla และได้เส้นทางอ้อม 25 กม. (เกินพื้นที่ 15 กม.)
        Cache::flush();
        config(['services.valhalla.enabled' => true, 'services.valhalla.url' => 'http://127.0.0.1:8002']);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'shop_delivery')->fresh();

        $this->assertEquals(31, (float) $job->total_fee, 'ค่างาน = ค่าส่งที่ล็อกตอนสั่ง ไม่ใช่สูตรจากระยะใหม่');
        $this->assertEquals(24.8, (float) $job->rider_earnings);
        $this->assertSame('valhalla', $job->distance_source);
        $this->assertEquals(25, (float) $job->distance_km);
        $this->assertSame('pending', $job->status);
    }

    public function test_unpaid_order_beyond_service_area_is_still_rejected(): void
    {
        Http::fake();
        Setting::set('rider.allow_cod', '1', 'boolean', 'rider');
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 120]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer, true, ['latitude' => self::STORE_LAT + 0.2, 'longitude' => self::STORE_LNG]);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'processing',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'delivery_method' => 'rider',
            'shipping_fee' => 45,
            'total_amount' => 165,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
        ]);

        try {
            app(RiderDispatchService::class)->createJobForSource($order, 'shop_delivery');
            $this->fail('ออเดอร์ที่ยังไม่จ่ายและอยู่นอกพื้นที่ ต้องถูกปฏิเสธ');
        } catch (RiderJobException $e) {
            $this->assertSame('OUT_OF_SERVICE_AREA', $e->errorCode);
        }
    }

    // =====================================================
    // F5: ตลาดสด LINE + เว็บ → wallet เมื่อปิด COD กับไรเดอร์
    // =====================================================

    public function test_line_and_web_default_cod_resolves_to_wallet_for_rider_delivery(): void
    {
        Http::fake();
        [$listing] = $this->freshMarketShop();
        $buyer = $this->freshBuyer(1000);

        $order = app(FreshMarketService::class)->createOrder($buyer, $listing, [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'buyer_latitude' => 13.7391,
            'buyer_longitude' => 100.5310,
            'delivery_address' => 'พิกัดที่ส่งผ่าน LINE',
            'default_payment_method' => 'cod',
            'channel' => 'line',
        ]);

        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('held', $order->escrow_status);

        // นัดรับ → ยังเป็นเก็บเงินปลายทางตามเดิม
        $pickup = app(FreshMarketService::class)->createOrder($buyer, $listing, [
            'quantity' => 1, 'delivery_type' => 'pickup', 'default_payment_method' => 'cod', 'channel' => 'line',
        ]);
        $this->assertSame('cod', $pickup->payment_method);

        // แอดมินเปิด COD กับไรเดอร์ → ค่าเริ่มต้นของช่องทางกลับมาใช้ได้
        Setting::set('rider.allow_cod', '1', 'boolean', 'rider');
        $cod = app(FreshMarketService::class)->createOrder($buyer, $listing, [
            'quantity' => 1, 'delivery_type' => 'rider', 'buyer_latitude' => 13.7391, 'buyer_longitude' => 100.5310,
            'delivery_address' => 'บ้าน', 'default_payment_method' => 'cod', 'channel' => 'line',
        ]);
        $this->assertSame('cod', $cod->payment_method);
    }

    public function test_line_rider_order_review_uses_wallet_and_warns_when_balance_is_short(): void
    {
        Http::fake();
        [$listing, $seller] = $this->freshMarketShop();
        $rich = $this->freshBuyer(1000);
        $poor = $this->freshBuyer(10);
        $manager = new FreshMarketChannelManager;
        $quantity = new \ReflectionMethod($manager, 'handleOrderQuantity_Text');
        $confirm = new \ReflectionMethod($manager, 'createOrderFromContext');

        // ยอดพอ → สรุปบอกหัก Wallet แล้วกดยืนยันได้ออเดอร์ที่จ่ายแล้ว
        $conversation = $this->lineConversation($rich, $listing, $seller);
        $reply = $quantity->invoke($manager, $conversation, '1 ส่ง', []);
        $this->assertStringContainsString('หักจาก Wallet', $reply['text']);
        $this->assertStringNotContainsString('ชำระเงินสดตอนรับสินค้า', $reply['text']);
        $this->assertSame('wallet', $conversation->fresh()->getFlowContext('order')['payment_method']);

        $done = $confirm->invoke($manager, $conversation->fresh());
        $this->assertStringContainsString('ชำระผ่าน Wallet แล้ว', $done['text']);
        $order = FreshMarketOrder::where('buyer_id', $rich->id)->latest('id')->firstOrFail();
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('rider', $order->delivery_type);

        // ยอดไม่พอ → บอกชัดตั้งแต่ขั้นสรุป ไม่ไปขั้นยืนยัน ไม่มีออเดอร์ค้าง
        $conversation = $this->lineConversation($poor, $listing, $seller, 'U'.str_repeat('b', 32));
        $reply = $quantity->invoke($manager, $conversation, '1 ส่ง', []);
        $this->assertStringContainsString('ส่งด้วยไรเดอร์ต้องชำระผ่าน Wallet ก่อน', $reply['text']);
        $this->assertStringContainsString('Wallet คงเหลือ ฿10.00', $reply['text']);
        $this->assertStringContainsString('1 นัดรับ', $reply['text']);
        $this->assertNotSame(FreshMarketConversation::STATE_ORDER_REVIEW, $conversation->fresh()->conversation_state);
        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $poor->id)->count());
    }

    public function test_web_taladsod_rider_checkout_resolves_to_wallet_and_hides_cod(): void
    {
        Http::fake();
        [$listing, $seller] = $this->freshMarketShop();
        $buyer = $this->freshBuyer(1000);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $listing->id, 'quantity' => 1])->assertCreated();

        // หน้าเว็บ: ส่งข้อมูลให้ซ่อนเก็บปลายทางเมื่อเลือกไรเดอร์ + ข้อความจ่ายก่อน
        $page = $this->actingAs($buyer)->get(route('taladsod.checkout', ['seller' => $seller->id]))->assertOk();
        $page->assertSee('riderCod'.chr(92).'u0022:false', false); // Js::from เข้ารหัสเครื่องหมายคำพูดเป็นรหัส unicode
        $page->assertSee(ShopCartService::COD_PREPAID_REASON);
        $page->assertSee('x-show="codUsable"', false);

        // ฟอร์มที่ไม่ได้ส่งวิธีจ่าย (ค่าเริ่มต้นเว็บ = cod) + ไรเดอร์ → หัก wallet
        $this->actingAs($buyer)->postJson(route('taladsod.checkout.store'), [
            'seller_id' => $seller->id,
            'delivery_type' => 'rider',
            'buyer_latitude' => 13.7391,
            'buyer_longitude' => 100.5310,
            'delivery_address' => '99 ถนนทดสอบ',
        ])->assertStatus(201);

        $order = FreshMarketOrder::where('buyer_id', $buyer->id)->latest('id')->firstOrFail();
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);

        // ส่งเก็บปลายทางมาเองตรงๆ → ถูกปฏิเสธพร้อมข้อความไทย
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $listing->id, 'quantity' => 1])->assertCreated();
        $this->actingAs($buyer)->postJson(route('taladsod.checkout.store'), [
            'seller_id' => $seller->id,
            'delivery_type' => 'rider',
            'payment_method' => 'cod',
            'buyer_latitude' => 13.7391,
            'buyer_longitude' => 100.5310,
            'delivery_address' => '99 ถนนทดสอบ',
        ])->assertStatus(422)->assertJsonPath('code', 'COD_NOT_AVAILABLE');
    }

    // =====================================================
    // M4 / M5: รูปโปรไฟล์
    // =====================================================

    public function test_profile_photo_pixel_cap_is_25_megapixels(): void
    {
        Http::fake();
        $this->assertSame(25_000_000, ProfilePhotoService::MAX_SOURCE_PIXELS);
        $service = app(ProfilePhotoService::class);

        // 25,000,001 พิกเซล → ปฏิเสธจากหัวไฟล์ (ไม่ถอดรหัส ไม่กินแรม)
        try {
            $service->normalizeUpload(UploadedFile::fake()->createWithContent('huge.png', $this->pngHeaderOnly(5000, 5001)));
            $this->fail('รูปเกิน 25 ล้านพิกเซลต้องถูกปฏิเสธ');
        } catch (ProfilePhotoException $e) {
            $this->assertSame('PHOTO_TOO_LARGE', $e->errorCode);
        }

        // 30 ล้านพิกเซล (เดิมผ่านเพดาน 50 ล้าน) → ปฏิเสธแล้ว
        try {
            $service->normalizeUpload(UploadedFile::fake()->createWithContent('30mp.png', $this->pngHeaderOnly(6000, 5000)));
            $this->fail('รูป 30 ล้านพิกเซลต้องถูกปฏิเสธ');
        } catch (ProfilePhotoException $e) {
            $this->assertSame('PHOTO_TOO_LARGE', $e->errorCode);
        }

        // 25 ล้านพอดี → ผ่านด่านพิกเซล (ไฟล์ทดสอบไม่มีข้อมูลภาพจริง จึงตกที่ถอดรหัสไม่ได้แทน)
        try {
            $service->normalizeUpload(UploadedFile::fake()->createWithContent('edge.png', $this->pngHeaderOnly(5000, 5000)));
            $this->fail('ไฟล์ทดสอบถอดรหัสไม่ได้');
        } catch (ProfilePhotoException $e) {
            $this->assertSame('PHOTO_INVALID', $e->errorCode);
        }
    }

    public function test_legacy_avatar_is_returned_to_everyone_until_a_new_photo_is_set(): void
    {
        Http::fake();
        Storage::fake('local');
        $service = app(ProfilePhotoService::class);
        $subject = User::factory()->create(['line_picture_url' => 'https://profile.line-scdn.net/legacy']);
        $viewer = User::factory()->create();

        // 🪪 (2026-10-04 · AI eKYC) เจ้าของสั่ง: รูปโปรไฟล์ = รูปที่ผู้ใช้เลือก ทุกคนเห็น
        //    ความน่าเชื่อถือมาจากป้าย "ยืนยันตัวตนแล้ว" ไม่ใช่จากรูป (ยกเลิกกติกา M5 เดิมที่ให้เห็นเฉพาะเจ้าของ)
        $this->assertSame('https://profile.line-scdn.net/legacy', $service->urlFor($subject, $subject));
        $this->assertSame('https://profile.line-scdn.net/legacy', $service->urlFor($subject, $viewer));
        $this->assertSame('https://profile.line-scdn.net/legacy', $service->urlFor($subject, null));

        // มีรูปถ่ายสดแล้ว → ทุกคนได้ลิงก์ลายน้ำ (ไม่ใช่รูปเดิม)
        $img = imagecreatetruecolor(400, 400);
        ob_start();
        imagejpeg($img);
        $subject = $service->store($subject, UploadedFile::fake()->createWithContent('live.jpg', (string) ob_get_clean()));
        $url = (string) $service->urlFor($subject, $viewer);
        $this->assertStringContainsString('/api/v1/media/profile-photo/'.$subject->id.'/', $url);
        $this->assertStringNotContainsString('line-scdn', $url);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * PNG ที่มีแค่หัวไฟล์ (IHDR บอกขนาด) — getimagesize อ่านขนาดได้ แต่ถอดรหัสเป็นภาพไม่ได้
     */
    private function pngHeaderOnly(int $width, int $height): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress("\x00"))
            .$chunk('IEND', '');
    }

    /**
     * ร้านตลาดสด (GP 10%) + สินค้า 100 บาท · เปิดไรเดอร์ + wallet + COD (นัดรับ)
     *
     * @return array{0: FreshMarketListing, 1: FreshMarketSeller}
     */
    private function freshMarketShop(): array
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
        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id, 'shop_name' => 'ร้านผักทดสอบ', 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => self::STORE_LAT, 'longitude' => self::STORE_LNG, 'is_active' => true, 'is_suspended' => false,
            'is_verified' => true, 'subscription_type' => 'free',
        ]);
        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักบุ้ง', 'price' => 100, 'unit' => 'กำ', 'quantity_available' => 50,
            'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        return [$listing->fresh(), $seller->fresh()];
    }

    private function freshBuyer(float $balance): User
    {
        $buyer = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $buyer->fresh();
    }

    private function lineConversation(User $buyer, FreshMarketListing $listing, FreshMarketSeller $seller, ?string $lineUserId = null): FreshMarketConversation
    {
        return FreshMarketConversation::create([
            'line_user_id' => $lineUserId ?? 'U'.str_repeat('a', 32),
            'user_id' => $buyer->id,
            'role' => 'buyer',
            'conversation_state' => FreshMarketConversation::STATE_ORDER_QUANTITY,
            'context' => ['order' => [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'listing_price' => $listing->price,
                'listing_unit' => $listing->unit,
                'seller_shop_name' => $seller->shop_name,
                'buyer_latitude' => 13.7391,
                'buyer_longitude' => 100.5310,
            ]],
        ]);
    }
}
