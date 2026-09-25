<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\StoreLayoutSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * หน้าร้านค้า/ตะกร้า/ชำระเงิน/คำสั่งซื้อ ธีม V4 (ต้องใช้ MySQL)
 *
 * ทุกหน้าต้องตอบ 200 และเป็นธีม V4 (มีคลาส tp-root / tp-card) ทั้งผู้เยี่ยมชมและผู้ซื้อที่ล็อกอิน
 * + ส่วนหัว/ส่วนท้ายกลาง (public header/footer) มีเมนู ตลาดสด / ร้านค้า / เป็นไรเดอร์ / เปิดร้าน
 */
#[Group('shop')]
class StorefrontV4PagesTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        // หน้าแรก (/) ต้องมี super admin ไม่งั้นเด้งไปหน้าติดตั้ง
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true, 'role' => 'admin'])->save();
    }

    /**
     * ตรวจว่าเป็นหน้า V4 ที่มีส่วนหัวสาธารณะ
     */
    private function assertV4Public($response): void
    {
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('tp-root', $html, 'ไม่ใช่ layout V4');
        $this->assertStringContainsString('ตลาดสด', $html, 'ส่วนหัวสาธารณะไม่มีเมนูตลาดสด');
        $this->assertStringContainsString('เป็นไรเดอร์', $html);
        $this->assertStringContainsString('เปิดร้าน', $html);
        $this->assertStringNotContainsString('storefront-aurora', $html, 'ยังโหลด CSS ธีมเก่า');
    }

    /**
     * @return array{0: User, 1: \App\Models\VendorStore, 2: Product}
     */
    private function catalog(): array
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true, 'is_verified' => true]);
        $product = $this->makeProduct($seller, $store, [
            'price' => 250,
            'compare_at_price' => 300,
            'slug' => 'v4-test-product-'.$seller->id,
            'description' => '<p>รายละเอียด</p><img src="x" onerror="alert(1)"><a href="javascript:alert(2)">ลิงก์</a>',
            'is_featured' => true,
        ]);

        return [$seller, $store, $product];
    }

    public function test_public_storefront_pages_render_v4_for_guest(): void
    {
        [, $store, $product] = $this->catalog();

        $this->assertV4Public($this->get('/'));
        $this->assertV4Public($this->get(route('storefront.index')));
        $this->assertV4Public($this->get(route('storefront.index', ['search' => 'ทดสอบ', 'sort_by' => 'price_low'])));
        $this->assertV4Public($this->get(route('storefront.index', ['category' => $product->category->slug])));
        $this->assertV4Public($this->get(route('storefront.stores')));
        $this->assertV4Public($this->get(route('about')));

        $show = $this->get(route('shop.show', $product->slug));
        $this->assertV4Public($show);
        // รายละเอียดสินค้าที่ผู้ขายกรอก ต้องถูกล้าง XSS (onerror=alert / javascript: ในลิงก์)
        $this->assertStringNotContainsString('alert(1)', $show->getContent());
        $this->assertStringNotContainsString('javascript:alert', $show->getContent());
        $show->assertSee('รายละเอียด');

        $this->assertV4Public($this->get(route('store.show', $store->store_slug)));
        $this->assertV4Public($this->get(route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $product->slug])));
    }

    public function test_storefront_json_endpoints_still_work(): void
    {
        [, , $product] = $this->catalog();

        $this->getJson(route('storefront.search', ['q' => mb_substr($product->name, 0, 6)]))->assertOk();
        $this->getJson(route('storefront.products', ['page' => 1]))
            ->assertOk()
            ->assertJsonStructure(['products', 'has_more', 'current_page', 'last_page', 'total']);
    }

    public function test_official_shop_pages_render_v4(): void
    {
        // getOfficialSellerId() จำ id ไว้ใน static ข้ามเทสต์ (แถวผู้ใช้ถูก rollback ไปแล้ว) → สร้างผู้ใช้ด้วย id นั้นถ้ายังไม่มี
        $officialId = Product::getOfficialSellerId();
        $official = User::find($officialId) ?? User::factory()->create([
            'id' => $officialId,
            'email' => 'official-shop-'.$officialId.'@thaiprompt.test',
        ]);
        $product = $this->makeProduct($official, null, [
            'slug' => 'official-v4-'.$officialId,
            'is_featured' => true,
            'price' => 199,
        ]);

        $this->assertV4Public($this->get(route('official-shop.index')));
        $this->assertV4Public($this->get(route('official-shop.featured')));
        $this->assertV4Public($this->get(route('official-shop.category', $product->category->slug)));
        $this->assertV4Public($this->get(route('official-shop.show', $product->slug)));
    }

    public function test_logged_in_buyer_pages_render_v4(): void
    {
        [, , $product] = $this->catalog();
        // มีคะแนนสะสม → กล่องคะแนนบนหน้าชำระเงินต้องใช้ถ้อยคำกลาง (ไม่มีคำว่า MLM) — GAP-25
        $product->forceFill(['pv_value' => 10])->save();
        $buyer = $this->makeBuyer(1000);
        $this->makeAddress($buyer);

        $this->actingAs($buyer);

        // ตะกร้าว่าง
        $this->assertV4Public($this->get(route('cart.index')));

        ShoppingCart::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);

        $cart = $this->get(route('cart.index'));
        $this->assertV4Public($cart);
        $cart->assertSee($product->name);

        $checkout = $this->get(route('checkout.index'));
        $this->assertV4Public($checkout);
        $checkout->assertSee('คะแนนสะสม', false)
            ->assertDontSee('PV สำหรับระบบ MLM')
            ->assertDontSee('ค่าคอมมิชชั่น');
        $checkout->assertSee('ส่งด้วยไรเดอร์');

        // หน้าร้าน/สินค้า เมื่อล็อกอิน
        $this->assertV4Public($this->get(route('storefront.index')));
        $this->assertV4Public($this->get(route('shop.show', $product->slug)));
    }

    public function test_order_pages_render_v4_for_owner(): void
    {
        [, , $product] = $this->catalog();
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
            'payment_method' => 'promptpay',
        ]);

        $this->actingAs($buyer);

        $index = $this->get(route('orders.index'));
        $index->assertOk()->assertSee('tp-card', false)->assertSee($order->order_number);

        $show = $this->get(route('orders.show', $order->id));
        $show->assertOk()->assertSee('tp-card', false)->assertSee($order->order_number);

        // โพลสถานะจากหน้า QR ยังได้ JSON
        $this->getJson(route('orders.show', $order->id))
            ->assertOk()
            ->assertJsonPath('order.id', $order->id);

        $this->assertV4Public($this->get(route('checkout.success', $order->id)));

        // ออเดอร์ส่งแล้ว → หน้าเขียนรีวิว
        $order->forceFill(['status' => 'delivered', 'delivered_at' => now()])->save();
        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $review = $this->get(route('orders.review.form', [$order->id, $item->id]));
        $review->assertOk()->assertSee('tp-card', false)->assertSee('name="rating"', false);
    }

    public function test_rider_order_page_shows_live_map_and_tracking_link(): void
    {
        \App\Models\Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        [, , $product] = $this->catalog();
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $product->store_id,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => now(),
            'delivery_method' => 'rider',
        ]);

        // ยังไม่เรียกไรเดอร์ → การ์ดบอกสถานะ ไม่มีแผนที่
        $this->actingAs($buyer)->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee('รอร้านเรียกไรเดอร์')
            ->assertDontSee('x-data="tpRiderLive(', false);

        $riderUser = User::factory()->create();
        $rider = \App\Models\Rider::create([
            'user_id' => $riderUser->id,
            'full_name' => 'ไรเดอร์ทดสอบ',
            'phone' => '0811111111',
            'status' => 'approved',
            'availability' => 'busy',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'กข 1234',
            'last_latitude' => self::STORE_LAT,
            'last_longitude' => self::STORE_LNG,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ]);

        $job = app(\App\Services\RiderDispatchService::class)->createJobForSource($order, 'shop_delivery');
        $job->forceFill(['rider_id' => $rider->id, 'status' => 'delivering', 'accepted_at' => now(), 'gps_active' => true])->save();

        $html = $this->actingAs($buyer)->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee('x-data="tpRiderLive(', false)
            ->assertSee('หน้าติดตามไรเดอร์')
            ->getContent();

        $this->assertStringContainsString('leaflet', $html);
        $this->assertStringContainsString('tile.openstreetmap.org', $html);
    }

    public function test_other_users_cannot_open_order_pages(): void
    {
        [, , $product] = $this->catalog();
        $buyer = $this->makeBuyer(0);
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]]);

        $this->actingAs($this->makeBuyer(0));

        $this->get(route('orders.show', $order->id))->assertNotFound();
        $this->get(route('checkout.success', $order->id))->assertNotFound();
    }

    public function test_vendor_store_custom_js_is_not_rendered_publicly(): void
    {
        [$seller, $store] = $this->catalog();
        $layout = StoreLayoutSetting::getOrCreateForUser($seller->id);
        $layout->forceFill([
            'custom_js' => 'window.__stolen = document.cookie;',
            'custom_css' => '.x{color:red}</style><script>alert(1)</script>',
            'footer_content' => '<p>ร้านเรา</p><img src=x onerror=alert(3)>',
            'show_footer' => true,
        ])->save();

        $html = $this->get(route('store.show', $store->store_slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('window.__stolen', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onerror=alert(3)', $html);
    }

    public function test_seller_layout_preview_renders_v4(): void
    {
        [$seller] = $this->catalog();
        $seller->forceFill(['kyc_status' => 'approved'])->save();

        $this->actingAs($seller->fresh());
        $this->get(route('seller.store.layout.preview'))
            ->assertOk()
            ->assertSee('tp-root', false)
            ->assertSee('โหมดตัวอย่างหน้าร้าน')
            ->assertDontSee('storefront-aurora', false);
    }

    public function test_order_list_shows_status_filter_counts(): void
    {
        [, , $product] = $this->catalog();
        $buyer = $this->makeBuyer(0);
        $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]]);
        $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], ['status' => 'completed', 'payment_status' => 'paid']);

        $this->actingAs($buyer);

        $this->get(route('orders.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('ทั้งหมด');

        $this->assertSame(2, Order::where('user_id', $buyer->id)->count());
    }
}
