<?php

namespace Tests\Feature\V4;

use App\Models\AccountingActivityLog;
use App\Models\EarningsLedger;
use App\Models\MlmCommission;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMessage;
use App\Models\OrderTrackingHistory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\ShippingProvider;
use App\Models\StoreBanner;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * หน้าแอดมินอีคอมเมิร์ซ / ร้านค้า / หน้าร้าน / Official Shop / คอมมิชชั่น — ย้ายเป็น layouts.admin-v4 (Wave-2 Wa2)
 *
 * ตรวจ: ทุกหน้าเปิดได้ (200) และใช้ธีม V4 (มี tp-card) ทั้งแบบว่างและมีข้อมูล
 * + action ที่แก้ในรอบนี้: ระงับ/เปิดร้าน, ตั้ง VAT/แพ็กเกจ, เรียงร้านแนะนำ (SortableJS/JSON),
 *   อัตรา GP รายสินค้า, ประวัติการจัดส่ง (เดิม TypeError 500), เลขพัสดุไม่ถูกเขียนทับด้วย id แอดมิน,
 *   หน้า AI Selection ที่ไม่มี view → หน้า "ยังไม่เปิดให้บริการ" แทน 500
 * ต้องใช้ MySQL (RefreshDatabase)
 */
#[Group('v4')]
class AdminShopPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Storage::fake('public');
        Cache::flush();

        // OfficialShopAdminController จำผู้ขายทางการไว้ใน static — ล้างทุกเทสต์ (DB ถูก rollback ระหว่างเทสต์)
        $cache = new \ReflectionProperty(\App\Http\Controllers\Admin\OfficialShopAdminController::class, 'officialSeller');
        $cache->setValue(null, null);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_pages_render_empty_state_on_v4(): void
    {
        foreach ([
            'admin.ecommerce.dashboard', 'admin.ecommerce.products.index', 'admin.ecommerce.products.blocked',
            'admin.ecommerce.orders.index', 'admin.ecommerce.orders.unread-messages', 'admin.ecommerce.categories.index',
            'admin.ecommerce.reviews.index', 'admin.ecommerce.reports',
            'admin.storefront.index', 'admin.storefront.banners.index', 'admin.storefront.banners.create',
            'admin.storefront.vendor-stores.index', 'admin.featured-stores.index', 'admin.seller-applications.index',
            'admin.official-shop.dashboard', 'admin.official-shop.products.index', 'admin.official-shop.products.create',
            'admin.mlm.commissions.index',
        ] as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('tp-card', false);
        }
    }

    public function test_every_page_renders_with_data_on_v4(): void
    {
        $data = $this->seedShop();

        $pages = [
            route('admin.ecommerce.dashboard'),
            route('admin.ecommerce.products.index'),
            route('admin.ecommerce.products.index', ['store_id' => $data['store']->id, 'sort_by' => 'evil;drop', 'sort_order' => 'sideways']),
            route('admin.ecommerce.products.show', $data['product']),
            route('admin.ecommerce.products.edit', $data['product']),
            route('admin.ecommerce.products.blocked'),
            route('admin.ecommerce.orders.index'),
            route('admin.ecommerce.orders.index', ['status' => 'paid', 'payment_status' => 'paid', 'delivery_method' => 'parcel', 'sort_by' => 'nope']),
            route('admin.ecommerce.orders.show', $data['paidOrder']),
            route('admin.ecommerce.orders.show', $data['pendingOrder']),
            route('admin.ecommerce.orders.tracking', $data['paidOrder']),
            route('admin.ecommerce.orders.tracking', ['order' => $data['paidOrder'], 'tab' => 'chat']),
            route('admin.ecommerce.orders.unread-messages'),
            route('admin.ecommerce.categories.index'),
            route('admin.ecommerce.reviews.index'),
            route('admin.ecommerce.reports', ['period' => 'week']),
            route('admin.ecommerce.reports', ['date_from' => now()->subDays(3)->toDateString(), 'date_to' => now()->toDateString()]),
            route('admin.storefront.index'),
            route('admin.storefront.index', ['tab' => 'layout']),
            route('admin.storefront.banners.index'),
            route('admin.storefront.banners.edit', $data['banner']->id),
            route('admin.storefront.vendor-stores.index'),
            route('admin.storefront.vendor-stores.index', ['status' => 'pending']),
            route('admin.storefront.vendor-stores.show', $data['store']),
            route('admin.storefront.vendor-stores.show', $data['pendingStore']),
            route('admin.storefront.vendor-stores.edit', $data['store']),
            route('admin.featured-stores.index'),
            route('admin.seller-applications.index'),
            route('admin.official-shop.dashboard'),
            route('admin.official-shop.products.index'),
            route('admin.official-shop.products.show', $data['officialProduct']),
            route('admin.official-shop.products.edit', $data['officialProduct']),
            route('admin.mlm.commissions.index'),
            route('admin.mlm.commissions.index', ['status' => 'pending', 'type' => 'unilevel_direct']),
            route('admin.mlm.commissions.show', $data['commission']),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertSame(200, $response->status(), "GET {$url} → {$response->status()}");
            $response->assertSee('tp-card', false);
        }

        // หน้ารายละเอียดออเดอร์แสดงการแบ่งเงิน + ประวัติการเปลี่ยนสถานะชำระเงิน
        $this->actingAs($this->admin)->get(route('admin.ecommerce.orders.show', $data['paidOrder']))
            ->assertSee('การแบ่งเงินของออเดอร์')
            ->assertSee('ตรวจสลิปแล้ว ยอดตรง')
            ->assertSee(route('admin.ecommerce.orders.payment-status.update', $data['paidOrder']), false);
    }

    public function test_ai_selection_pages_show_unavailable_page_instead_of_500(): void
    {
        foreach ([
            'admin.official-shop.selection.index', 'admin.official-shop.selection.warnings', 'admin.official-shop.selection.settings',
            'admin.official-shop.selection.new-promotions', 'admin.official-shop.selection.best-sellers',
        ] as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('ฟีเจอร์นี้ยังไม่เปิดให้บริการ');
        }

        $this->actingAs($this->admin)
            ->postJson(route('admin.official-shop.selection.run'))
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'FEATURE_UNAVAILABLE']);
    }

    public function test_vendor_store_suspend_unsuspend_and_settings(): void
    {
        $data = $this->seedShop();
        $store = $data['store'];

        // ระงับต้องมีเหตุผล
        $this->actingAs($this->admin)
            ->from(route('admin.storefront.vendor-stores.show', $store))
            ->post(route('admin.storefront.vendor-stores.suspend', $store), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->admin)
            ->from(route('admin.storefront.vendor-stores.show', $store))
            ->post(route('admin.storefront.vendor-stores.suspend', $store), ['reason' => 'ขายสินค้าผิดกฎหมาย'])
            ->assertRedirect(route('admin.storefront.vendor-stores.show', $store))
            ->assertSessionHas('success');

        $store->refresh();
        $this->assertSame('suspended', $store->status);
        $this->assertFalse((bool) $store->is_active);
        $this->assertSame('ขายสินค้าผิดกฎหมาย', $store->suspension_reason);
        $this->assertTrue($store->isBlockedFromSelling());

        $this->actingAs($this->admin)
            ->from(route('admin.storefront.vendor-stores.show', $store))
            ->post(route('admin.storefront.vendor-stores.unsuspend', $store))
            ->assertSessionHas('success');

        $store->refresh();
        $this->assertSame('active', $store->status);
        $this->assertTrue((bool) $store->is_active);
        $this->assertNull($store->suspension_reason);

        // แก้ร้าน: แพ็กเกจ + จด VAT + ปิดร้านแนะนำ (checkbox ส่ง hidden 0 ต้องปิดได้จริง)
        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), [
                'store_name' => 'ร้านป้าแดงอัปเดต',
                'package_id' => $data['package']->id,
                'commission_rate' => '12',
                'is_active' => '1',
                'is_verified' => '1',
                'is_featured_home' => '0',
                'vat_registered' => '1',
                // จด VAT ต้องมีเลขผู้เสียภาษี 13 หลัก (ตรวจแบบเดียวกับฝั่งผู้ขาย)
                'tax_id' => '0105561234567',
                'rider_delivery_enabled' => '0',
                'primary_color' => '#f97316',
                'secondary_color' => '#ec4899',
            ])
            ->assertRedirect(route('admin.storefront.vendor-stores.show', $store));

        $store->refresh();
        $this->assertSame('ร้านป้าแดงอัปเดต', $store->store_name);
        $this->assertSame($data['package']->id, (int) $store->package_id);
        $this->assertTrue((bool) $store->vat_registered);
        $this->assertFalse((bool) $store->is_featured_home);
        $this->assertNull($store->featured_home_order);

        // เปิดไรเดอร์โดยที่ร้านยังไม่ตั้งจุดรับของ → ไม่ยอม
        $this->actingAs($this->admin)
            ->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), [
                'store_name' => 'ร้านป้าแดงอัปเดต',
                'rider_delivery_enabled' => '1',
            ])
            ->assertSessionHasErrors('rider_delivery_enabled');
    }

    public function test_featured_store_order_saves_via_json(): void
    {
        $data = $this->seedShop();
        $second = $this->makeStore('ร้านที่สอง', ['is_featured_home' => true, 'featured_home_order' => 2]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.featured-stores.update-order'), [
                'order' => [$second->id => 1, $data['store']->id => 2, $data['pendingStore']->id => 1],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, (int) $second->fresh()->featured_home_order);
        $this->assertSame(2, (int) $data['store']->fresh()->featured_home_order);
        // ร้านที่ไม่ใช่ร้านแนะนำต้องไม่ถูกแก้ลำดับ
        $this->assertNull($data['pendingStore']->fresh()->featured_home_order);
    }

    public function test_admin_gp_rate_is_saved_and_cleared_from_product_edit(): void
    {
        $data = $this->seedShop();
        $product = $data['product'];

        $payload = [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => '150',
            'stock_quantity' => '5',
            'is_active' => '1',
            'track_inventory' => '1',
            'admin_gp_rate' => '7.5',
        ];

        $this->actingAs($this->admin)->put(route('admin.ecommerce.products.update', $product), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertEquals(7.5, (float) $product->fresh()->admin_gp_rate);

        $this->actingAs($this->admin)->put(route('admin.ecommerce.products.update', $product), ['admin_gp_rate' => ''] + $payload)
            ->assertSessionHas('success');
        $this->assertNull($product->fresh()->admin_gp_rate);

        // สต็อกต่ำกว่าเกณฑ์ต้องบันทึกได้ (เดิมเขียน stock_status = 'low_stock' ที่ไม่มีใน enum → บันทึกพัง)
        $this->actingAs($this->admin)->put(route('admin.ecommerce.products.update', $product), ['stock_quantity' => '2', 'low_stock_threshold' => '10'] + $payload)
            ->assertSessionHas('success');
        $this->assertSame('in_stock', $product->fresh()->stock_status);
        $this->actingAs($this->admin)->get(route('admin.ecommerce.products.index', ['stock_status' => 'low_stock']))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_tracking_actions_no_longer_crash(): void
    {
        $data = $this->seedShop();
        $order = $data['paidOrder'];
        $order->forceFill(['status' => 'processing'])->saveQuietly();

        $this->actingAs($this->admin)
            ->from(route('admin.ecommerce.orders.tracking', $order))
            ->post(route('admin.ecommerce.orders.tracking.update', $order), [
                'shipping_provider_id' => $data['provider']->id,
                'tracking_number' => 'TH1234567890',
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('TH1234567890', $order->tracking_number);
        $this->assertSame('shipped', $order->status);

        $this->actingAs($this->admin)
            ->from(route('admin.ecommerce.orders.tracking', $order))
            ->post(route('admin.ecommerce.orders.tracking.history', $order), [
                'status' => 'in_transit',
                'description' => 'สินค้าถึงศูนย์กระจายสินค้า',
                'location' => 'กรุงเทพฯ',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(OrderTrackingHistory::where('order_id', $order->id)->where('status', 'in_transit')->exists());

        $this->actingAs($this->admin)->get(route('admin.ecommerce.orders.tracking', $order))
            ->assertOk()
            ->assertSee('สินค้าถึงศูนย์กระจายสินค้า');
    }

    public function test_v4_forms_save_through_existing_endpoints(): void
    {
        $data = $this->seedShop();

        // เพิ่มสินค้าจากโมดัล (วิธีส่งแบบใช้ค่าร้าน → ไม่มีค่าส่ง ต้องไม่พังเพราะ shipping_fee NOT NULL)
        $this->actingAs($this->admin)
            ->post(route('admin.ecommerce.products.store'), [
                'name' => 'ข้าวเกรียบกุ้ง',
                'category_id' => $data['category']->id,
                'price' => '35',
                'stock_quantity' => '20',
                'shipping_method' => 'store_default',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.ecommerce.products.index'))
            ->assertSessionHas('success');
        $this->assertTrue(Product::where('name', 'ข้าวเกรียบกุ้ง')->exists());

        // หมวดหมู่: แก้ชื่อ + ปิดใช้งาน (checkbox ไม่ติ๊ก = ปิด)
        $this->actingAs($this->admin)
            ->put(route('admin.ecommerce.categories.update', $data['category']), ['name' => 'อาหารแห้งพรีเมียม', 'sort_order' => 2])
            ->assertSessionHas('success');
        $this->assertSame('อาหารแห้งพรีเมียม', $data['category']->fresh()->name);
        $this->assertFalse((bool) $data['category']->fresh()->is_active);

        // แบนเนอร์: hidden is_active=0 ต้องปิดได้จริง (เดิมไม่ติ๊ก = ไม่ส่งค่า = ปิดไม่ได้)
        $this->actingAs($this->admin)
            ->put(route('admin.storefront.banners.update', $data['banner']->id), [
                'location' => 'homepage',
                'title' => 'ลดทั้งร้าน 50%',
                'sort_order' => '3',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.storefront.banners.index'));
        $banner = $data['banner']->fresh();
        $this->assertSame('ลดทั้งร้าน 50%', $banner->title);
        $this->assertFalse((bool) $banner->is_active);

        // สินค้าร้านทางการ: ปิดขาย/เลิกแนะนำผ่าน hidden 0 (controller ใช้ boolean(x, true))
        $official = $data['officialProduct'];
        $this->actingAs($this->admin)
            ->put(route('admin.official-shop.products.update', $official), [
                'name' => $official->name,
                'category_id' => $official->category_id,
                'price' => '55',
                'stock_quantity' => '8',
                'is_active' => '0',
                'is_featured' => '0',
                'track_inventory' => '1',
                'allow_coin_purchase' => '0',
            ])
            ->assertSessionHas('success');
        $official->refresh();
        $this->assertFalse((bool) $official->is_active);
        $this->assertEquals(55.0, (float) $official->price);
    }

    public function test_admin_menu_links_shop_pages(): void
    {
        $routes = collect(config('menus.admin'))
            ->flatMap(fn ($item) => array_merge([$item['route'] ?? null], array_column($item['submenu'] ?? [], 'route')))
            ->filter()
            ->values()
            ->all();

        foreach ([
            'admin.storefront.vendor-stores.index', 'admin.seller-applications.index',
            'admin.featured-stores.index', 'admin.storefront.index', 'admin.ecommerce.reports',
        ] as $routeName) {
            $this->assertContains($routeName, $routes, "เมนูแอดมินต้องมี {$routeName}");
        }
    }

    // =====================================================================
    // ข้อมูลทดสอบ
    // =====================================================================

    /**
     * @return array<string, mixed>
     */
    private function seedShop(): array
    {
        $category = ProductCategory::create(['name' => 'อาหารแห้ง', 'slug' => 'dry-food-'.uniqid(), 'is_active' => true]);
        $package = VendorPackage::create([
            'package_name' => 'Basic', 'package_slug' => 'basic-'.uniqid(), 'display_name' => 'แพ็กเกจเริ่มต้น',
            'price' => 0, 'commission_rate' => 10, 'is_active' => true,
        ]);

        $store = $this->makeStore('ร้านป้าแดง', ['is_featured_home' => true, 'featured_home_order' => 1, 'package_id' => $package->id]);
        $pendingStore = $this->makeStore('ร้านรออนุมัติ', ['status' => 'pending', 'is_active' => false, 'is_verified' => false]);

        $product = Product::withoutEvents(fn () => Product::create([
            'seller_id' => $store->user_id,
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'น้ำพริกเผาป้าแดง',
            'slug' => 'nam-prik-'.uniqid(),
            'sku' => 'NP-'.uniqid(),
            'price' => 120,
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
            'stock_status' => 'in_stock',
            'is_active' => true,
        ]));

        $blocked = Product::withoutEvents(fn () => Product::create([
            'seller_id' => $store->user_id,
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'สินค้าที่ถูกบล็อก',
            'slug' => 'blocked-'.uniqid(),
            'sku' => 'BL-'.uniqid(),
            'price' => 50,
            'is_active' => false,
        ]));
        $blocked->forceFill(['is_blocked' => true, 'blocked_at' => now(), 'blocked_by' => $this->admin->id, 'block_reason' => 'ละเมิดลิขสิทธิ์'])->saveQuietly();

        $buyer = User::factory()->create(['role' => 'user']);

        $paidOrder = Order::withoutEvents(fn () => Order::create([
            'order_number' => 'ORD-TEST-'.strtoupper(uniqid()),
            'user_id' => $buyer->id,
            'store_id' => $store->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => 'promptpay',
            'delivery_method' => 'parcel',
            'subtotal' => 240,
            'shipping_fee' => 40,
            'total_amount' => 280,
            'paid_at' => now(),
            'has_unread_messages' => true,
            'last_message_at' => now(),
            'shipping_address_snapshot' => [
                'recipient_name' => 'สมชาย ใจดี', 'phone_number' => '0812345678', 'address_line_1' => '99/1 ถนนสุขุมวิท',
                'district' => 'คลองเตย', 'province' => 'กรุงเทพมหานคร', 'postal_code' => '10110',
            ],
            'admin_notes' => 'บันทึกทดสอบ',
        ]));

        OrderItem::create([
            'order_id' => $paidOrder->id,
            'product_id' => $product->id,
            'seller_id' => $store->user_id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => 120,
            'quantity' => 2,
            'subtotal' => 240,
            'total' => 240,
            'commission_rate' => 10,
            'commission_amount' => 24,
            'seller_earning' => 216,
            'status' => 'processing',
        ]);

        $pendingOrder = Order::withoutEvents(fn () => Order::create([
            'order_number' => 'ORD-TEST-'.strtoupper(uniqid()),
            'user_id' => $buyer->id,
            'store_id' => $store->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
            'subtotal' => 120,
            'total_amount' => 120,
        ]));

        EarningsLedger::create([
            'user_id' => $store->user_id,
            'earning_type' => EarningsLedger::TYPE_SELLER_SALE,
            'source_type' => 'Order',
            'source_id' => $paidOrder->id,
            'gross_amount' => 280,
            'platform_fee' => 24,
            'vat_amount' => 0,
            'mlm_commission' => 0,
            'net_amount' => 256,
            'status' => EarningsLedger::STATUS_PENDING,
            'available_at' => now()->addDays(7),
            'description' => 'รายได้จากการขาย ออเดอร์ทดสอบ',
        ]);

        AccountingActivityLog::create([
            'user_id' => $this->admin->id,
            'loggable_type' => Order::class,
            'loggable_id' => $paidOrder->id,
            'action' => 'order.payment_status_changed',
            'description' => 'เปลี่ยนสถานะการชำระเงิน pending → paid: ตรวจสลิปแล้ว ยอดตรง',
            'old_values' => ['payment_status' => 'pending'],
            'new_values' => ['payment_status' => 'paid', 'payment_reference' => 'SLIP-001'],
            'ip_address' => '127.0.0.1',
        ]);

        OrderMessage::create([
            'order_id' => $paidOrder->id,
            'sender_id' => $buyer->id,
            'sender_type' => 'customer',
            'message' => 'สินค้าจะส่งเมื่อไหร่คะ',
            'is_read' => false,
        ]);

        ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'rating' => 5,
            'comment' => 'อร่อยมาก',
            'is_approved' => true,
        ]);

        $provider = ShippingProvider::create([
            'code' => 'thp-'.uniqid(), 'name' => 'ไปรษณีย์ไทย', 'name_en' => 'Thailand Post',
            'tracking_url' => 'https://track.thailandpost.co.th/?trackNumber={tracking_number}', 'is_active' => true, 'sort_order' => 1,
        ]);

        $banner = StoreBanner::create([
            'location' => 'homepage', 'title' => 'ลดทั้งร้าน', 'subtitle' => 'เฉพาะสัปดาห์นี้',
            'gradient' => 'from-orange-500 via-red-500 to-pink-600', 'sort_order' => 0, 'is_active' => true,
        ]);

        // สินค้าร้านทางการ: เปิดหน้ารายการก่อนเพื่อให้ระบบสร้างบัญชีผู้ขายทางการ
        $this->actingAs($this->admin)->get(route('admin.official-shop.products.index'))->assertOk();
        $officialSeller = User::where('email', config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com'))->firstOrFail();
        $officialProduct = Product::withoutEvents(fn () => Product::create([
            'seller_id' => $officialSeller->id,
            'category_id' => $category->id,
            'name' => 'ผัดกะเพราราดข้าว',
            'slug' => 'krapao-'.uniqid(),
            'sku' => 'OF-'.uniqid(),
            'price' => 50,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]));

        // คอมมิชชั่นค่าแนะนำ
        $plan = MlmPlan::withoutEvents(fn () => MlmPlan::create(['name' => 'Global', 'slug' => 'global-'.uniqid(), 'is_active' => true]));
        $memberId = DB::table('mlm_members')->insertGetId([
            'user_id' => $buyer->id,
            'mlm_plan_id' => $plan->id,
            'member_code' => 'M'.random_int(100000, 999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $commission = MlmCommission::withoutEvents(fn () => MlmCommission::create([
            'mlm_member_id' => $memberId,
            'mlm_plan_id' => $plan->id,
            'user_id' => $buyer->id,
            'source_type' => 'Order',
            'source_id' => $paidOrder->id,
            'type' => 'unilevel_direct',
            'level' => 1,
            'pv_amount' => 10,
            'sales_amount' => 240,
            'commission_amount' => 12.5,
            'percentage' => 5,
            'status' => 'pending',
        ]));

        return compact('category', 'package', 'store', 'pendingStore', 'product', 'blocked', 'paidOrder', 'pendingOrder', 'provider', 'banner', 'officialProduct', 'commission');
    }

    private function makeStore(string $name, array $overrides = []): VendorStore
    {
        $owner = User::factory()->create(['role' => 'seller']);

        return VendorStore::create(array_merge([
            'user_id' => $owner->id,
            'store_name' => $name,
            'store_slug' => 'store-'.uniqid(),
            'store_description' => 'ร้านทดสอบ',
            'store_phone' => '0811111111',
            'business_type' => 'individual',
            'commission_rate' => 10,
            'status' => 'active',
            'is_active' => true,
            'is_verified' => true,
        ], $overrides));
    }
}
