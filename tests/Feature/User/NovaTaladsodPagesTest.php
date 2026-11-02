<?php

namespace Tests\Feature\User;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketCartService;
use App\Services\FreshMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * หน้าผู้ขายตลาดสด (taladsod) ธีมโนวาเต็ม (layouts.user-nova)
 *
 * ครอบคลุม: สมัครเปิดร้าน / หน้าร้านวันนี้ / ออเดอร์ / รายละเอียดออเดอร์ / สินค้า / ลงขาย / แก้ไข / รายได้ / ตั้งค่าร้าน
 * - ทุกหน้าตอบ 200 ด้วยโครงโนวา ไม่มีโครง V4 หลงเหลือ
 * - ร้านอื่นเปิด/แก้/ลบสินค้าหรือออเดอร์ของร้านเราไม่ได้ (403)
 * - เบอร์ลูกค้าไม่โผล่จนกว่าร้านจะรับออเดอร์
 */
class NovaTaladsodPagesTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER_PHONE = '0891112222';

    protected User $buyer;

    protected User $sellerUser;

    protected User $otherSellerUser;

    protected FreshMarketSeller $shop;

    protected FreshMarketSeller $otherShop;

    protected FreshMarketCategory $category;

    protected FreshMarketListing $listing;

    protected FreshMarketListing $otherListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
        Storage::fake('public');
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

        $this->buyer = $this->member(['name' => 'ลูกค้าทดสอบ', 'phone' => self::BUYER_PHONE]);
        $this->sellerUser = $this->member(['name' => 'แม่ค้าโนวา']);
        $this->otherSellerUser = $this->member(['name' => 'ร้านข้างๆ']);

        foreach ([$this->buyer, $this->sellerUser, $this->otherSellerUser] as $user) {
            Wallet::create(['user_id' => $user->id, 'balance' => $user->is($this->buyer) ? 1000 : 0, 'currency' => 'THB', 'status' => 'active']);
        }

        $this->category = FreshMarketCategory::create(['name' => 'อาหารพร้อมทาน', 'slug' => 'ready-meals-nova', 'icon' => '🍛', 'is_active' => true]);

        $this->shop = $this->makeShop($this->sellerUser, 'ครัวโนวา');
        $this->otherShop = $this->makeShop($this->otherSellerUser, 'ร้านข้างๆ ตลาด');
        $this->listing = $this->makeListing($this->shop, 'ข้าวมันไก่โนวา');
        $this->otherListing = $this->makeListing($this->otherShop, 'ก๋วยเตี๋ยวร้านข้างๆ');
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    // =====================================================
    // โครงโนวาเต็มทุกหน้า
    // =====================================================

    public function test_every_seller_page_renders_the_full_nova_shell(): void
    {
        $order = $this->pendingOrder();
        $this->actingAs($this->sellerUser);

        $pages = [
            'แดชบอร์ด' => [route('taladsod.seller.dashboard'), ['ครัวโนวา', 'ร้านเปิดอยู่', 'มีออเดอร์รอรับ', 'tsPresence']],
            'ออเดอร์' => [route('taladsod.seller.orders'), [$order->order_number, 'รับออเดอร์']],
            'รายละเอียดออเดอร์' => [route('taladsod.seller.orders.show', $order), ['รายการที่ต้องเตรียม', 'ข้าวมันไก่โนวา']],
            'สินค้า' => [route('taladsod.seller.listings'), ['ข้าวมันไก่โนวา', 'ลงขายสินค้าใหม่']],
            'ลงขาย' => [route('taladsod.listing.create'), ['ลงขายสินค้าใหม่', 'ตัวเลือกสินค้า']],
            'แก้ไขสินค้า' => [route('taladsod.listing.edit', $this->listing->id), ['แก้ไขสินค้า', 'ลบสินค้า']],
            'รายได้' => [route('taladsod.seller.earnings'), ['รายได้ร้าน', 'เงินที่กำลังจะได้']],
            'ตั้งค่าร้าน' => [route('taladsod.seller.profile'), ['ตั้งค่าร้าน', 'ปักหมุดร้าน']],
        ];

        foreach ($pages as $name => [$url, $texts]) {
            $html = $this->assertNovaShell($this->get($url), $name);
            foreach ($texts as $text) {
                $this->assertStringContainsString($text, $html, "หน้า {$name} ไม่มีข้อความ {$text}");
            }
            // เมนูผู้ขายใช้ไอคอนโนวา
            $this->assertStringContainsString('nvm-td-nav', $html, "หน้า {$name} ไม่มีเมนูผู้ขาย");
            $this->assertStringContainsString('#nvm-i-store', $html, "หน้า {$name} ไม่ใช้ไอคอนโนวา");
        }

        // คนที่ยังไม่มีร้าน → หน้าสมัคร (ธีมโนวาเหมือนกัน)
        $newbie = $this->member();
        $this->actingAs($newbie)->get(route('taladsod.seller.dashboard'))->assertRedirect(route('taladsod.register-seller'));
        $this->actingAs($newbie)->get(route('taladsod.seller.orders'))->assertRedirect(route('taladsod.register-seller'));
        $html = $this->assertNovaShell($this->actingAs($newbie)->get(route('taladsod.register-seller')), 'สมัครเปิดร้าน');
        $this->assertStringContainsString('สมัครเปิดร้านตลาดสด', $html);
        $this->assertStringContainsString('name="agree_terms"', $html);
        $this->assertStringContainsString('name="shop_name"', $html);
    }

    public function test_seller_pages_require_login(): void
    {
        $this->get(route('taladsod.seller.dashboard'))->assertRedirect(route('login'));
        $this->get(route('taladsod.register-seller'))->assertRedirect(route('login'));
    }

    // =====================================================
    // ความเป็นเจ้าของ
    // =====================================================

    public function test_seller_cannot_open_edit_or_delete_another_shops_listing(): void
    {
        $this->actingAs($this->sellerUser);

        $this->get(route('taladsod.listing.edit', $this->otherListing->id))->assertForbidden();
        $this->put(route('taladsod.listing.update', $this->otherListing), [
            'title' => 'แอบแก้',
            'category_id' => $this->category->id,
            'price' => 1,
            'unit' => 'จาน',
            'track_stock' => '0',
        ])->assertForbidden();
        $this->delete(route('taladsod.listing.destroy', $this->otherListing->id), ['return_to' => 'listings'])->assertForbidden();

        $fresh = $this->otherListing->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame('ก๋วยเตี๋ยวร้านข้างๆ', $fresh->title);

        // หน้ารายการสินค้าแสดงเฉพาะของร้านตัวเอง
        $this->get(route('taladsod.seller.listings'))->assertOk()->assertDontSee('ก๋วยเตี๋ยวร้านข้างๆ');
    }

    public function test_seller_cannot_open_or_act_on_another_shops_order(): void
    {
        $order = $this->pendingOrder();

        $this->actingAs($this->otherSellerUser);
        $this->get(route('taladsod.seller.orders.show', $order))->assertForbidden();
        $this->put(route('taladsod.seller.orders.status', $order), ['action' => 'accept', 'return_to' => 'list'])->assertForbidden();
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $order->fresh()->order_status);

        // รายการออเดอร์ของร้านอื่นไม่มีออเดอร์นี้
        $this->get(route('taladsod.seller.orders'))->assertOk()->assertDontSee($order->order_number);
    }

    // =====================================================
    // ความเป็นส่วนตัวของลูกค้า
    // =====================================================

    public function test_buyer_phone_stays_hidden_until_the_shop_accepts(): void
    {
        $order = $this->pendingOrder();
        $this->actingAs($this->sellerUser);

        $this->get(route('taladsod.seller.orders.show', $order))->assertOk()->assertDontSee(self::BUYER_PHONE);
        $this->get(route('taladsod.seller.orders'))->assertOk()->assertDontSee(self::BUYER_PHONE);
        $this->get(route('taladsod.seller.dashboard'))->assertOk()->assertDontSee(self::BUYER_PHONE);

        app(FreshMarketService::class)->applyAction($order->fresh(), 'accept', 'seller', $this->sellerUser);

        $this->get(route('taladsod.seller.orders.show', $order))->assertOk()->assertSee('โทรหาลูกค้า '.self::BUYER_PHONE, false);
        // หน้ารายการยังไม่แสดงเบอร์ (ต้องเปิดรายละเอียดเอง)
        $this->get(route('taladsod.seller.orders'))->assertOk()->assertDontSee(self::BUYER_PHONE);
    }

    // =====================================================
    // Regression
    // =====================================================

    public function test_no_tailwind_or_v4_leftovers_and_kit_script_still_loads(): void
    {
        $this->actingAs($this->sellerUser);

        $profile = (string) $this->get(route('taladsod.seller.profile'))->getContent();
        $this->assertStringNotContainsString('text-amber-700', $profile, 'ยังมีคลาส Tailwind หลงในหน้าตั้งค่าร้าน');
        $this->assertStringNotContainsString('ts-scope', $profile);
        // สคริปต์ชุดตลาดสด (window.ts / แผนที่ปักหมุด) ยังโหลด
        $this->assertStringContainsString('window.ts', $profile);
        $this->assertStringContainsString('tsPinMap', $profile);

        $dashboard = (string) $this->get(route('taladsod.seller.dashboard'))->getContent();
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bml-2\b/', $dashboard, 'ยังมีคลาส Tailwind หลงในหน้าร้านวันนี้');
        $this->assertStringNotContainsString('ts-scope', $dashboard);

        // partial แจ้งเตือนเก่าที่ไม่มีใครใช้ถูกลบแล้ว (โครงโนวาแสดง flash เป็น toast เอง)
        $this->assertFalse(view()->exists('taladsod.partials.flash'));
        $this->assertTrue(view()->exists('taladsod.partials.kit-js'));
    }

    public function test_order_buttons_lock_without_font_awesome_and_unlock_on_back(): void
    {
        $order = $this->pendingOrder();
        $this->actingAs($this->sellerUser);

        $pages = [
            'ออเดอร์' => route('taladsod.seller.orders'),
            'รายละเอียดออเดอร์' => route('taladsod.seller.orders.show', $order),
        ];

        foreach ($pages as $name => $url) {
            $html = (string) $this->get($url)->assertOk()->getContent();
            // ปุ่มรับ/อัปเดตออเดอร์ห้ามยัด HTML สปินเนอร์ Font Awesome
            $this->assertStringNotContainsString('insertAdjacentHTML', $html, "หน้า {$name} ยังยัด HTML ใส่ปุ่ม");
            $this->assertStringNotContainsString('fa-circle-notch', $html, "หน้า {$name} ยังใช้สปินเนอร์ Font Awesome");
            $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bfa-[a-z]/', $html, "หน้า {$name} ยังมีไอคอน Font Awesome");
            // ล็อกปุ่มด้วย data-td-locked แล้วปลดตอนกดย้อนกลับ (bfcache)
            $this->assertStringContainsString('data-td-locked', $html, "หน้า {$name} ไม่ล็อกปุ่มระหว่างส่ง");
            $this->assertStringContainsString('pageshow', $html, "หน้า {$name} ไม่ปลดล็อกปุ่มตอนกดย้อนกลับ");
        }
    }

    public function test_profile_shop_type_radio_resets_after_failed_save(): void
    {
        $this->actingAs($this->sellerUser);
        $html = (string) $this->get(route('taladsod.seller.profile'))->assertOk()->getContent();

        $this->assertStringContainsString('name="sp_mode"', $html);
        // บันทึกไม่สำเร็จ → ตั้งปุ่มเลือกกลับตามสถานะจริง
        $this->assertStringContainsString("querySelectorAll('input[name=sp_mode]')", $html, 'ไม่มีโค้ดตั้งปุ่มประเภทร้านกลับหลังบันทึกพลาด');
    }

    public function test_dashboard_header_shows_the_shop_image_or_falls_back(): void
    {
        $this->actingAs($this->sellerUser);

        // ไม่มีรูปร้าน → ใช้ภาพตลาดของชุดโนวา
        $html = (string) $this->get(route('taladsod.seller.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('images/nova/svc/market.webp', $html);
        $this->assertStringNotContainsString('nvm-td-logo', $html);

        // มีรูปร้าน → หัวหน้าแสดงรูปร้านแทน
        $this->shop->update(['shop_image' => 'https://cdn.example.test/shop-nova.jpg']);
        $html = (string) $this->get(route('taladsod.seller.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('nvm-td-logo', $html);
        $this->assertStringContainsString('https://cdn.example.test/shop-nova.jpg', $html);
    }

    public function test_register_seller_info_flash_shows_once_as_toast(): void
    {
        $newbie = $this->member();
        $message = 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ';

        $html = (string) $this->actingAs($newbie)
            ->withSession(['info' => $message])
            ->get(route('taladsod.register-seller'))
            ->assertOk()
            ->getContent();

        // โครงโนวาแปลง flash เป็น toast อยู่แล้ว → ห้ามมีกล่องซ้ำในหน้า
        $this->assertSame(1, substr_count($html, (string) \Illuminate\Support\Js::from($message)), 'ไม่มี toast ข้อความ info จากโครงโนวา');
        $this->assertStringNotContainsString($message, $html, 'ข้อความ info ยังแสดงเป็นกล่องซ้ำในหน้า');
        $this->assertStringNotContainsString('nvm-alert--info', $html);
    }

    public function test_map_pin_colours_come_from_nova_variables(): void
    {
        $css = (string) file_get_contents(public_path('theme-nova/member/taladsod.css'));

        $this->assertStringContainsString('.nvm-td-map .tp-map-pin', $css, 'ไม่มีการตั้งสีหมุดแผนที่ชุดโนวา');
        $this->assertMatchesRegularExpression('/\.nvm-td-map \.tp-map-pin\.is-shop \{[^}]*var\(--nvm-good\)/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.tp-map-pin\.is-shop \{[^}]*#[0-9a-f]{3,6}/i', $css, 'หมุดร้านยังใช้สี hex ตายตัว');
    }

    public function test_listing_form_keeps_field_names(): void
    {
        $this->actingAs($this->sellerUser);
        $html = (string) $this->get(route('taladsod.listing.edit', $this->listing->id))->getContent();

        foreach (['name="title"', 'name="category_id"', 'name="price"', 'name="unit"', 'name="track_stock"', 'name="images[]"', 'name="_token"', 'name="_method"'] as $field) {
            $this->assertStringContainsString($field, $html, "ฟอร์มสินค้าไม่มีช่อง {$field}");
        }
    }

    // =====================================================
    // Helpers
    // =====================================================

    private function assertNovaShell($response, string $name): string
    {
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('nvm-body', $html, "หน้า {$name} ไม่ได้ใช้โครงโนวา");
        $this->assertStringContainsString('nova-member.css', $html, "หน้า {$name} ไม่ได้โหลด CSS โนวา");
        $this->assertStringContainsString('theme-nova/member/taladsod.css', $html, "หน้า {$name} ไม่ได้โหลด CSS ตลาดสดโนวา");
        $this->assertStringNotContainsString('class="nvm-v4"', $html, "หน้า {$name} ยังห่อด้วย V4");
        $this->assertStringNotContainsString('tp-card', $html, "หน้า {$name} ยังมีคลาส V4");

        return $html;
    }

    private function pendingOrder(): FreshMarketOrder
    {
        $cart = app(FreshMarketCartService::class);
        $cart->addItem($this->buyer, $this->listing->id, 1, [], 'ไม่ใส่ผัก');

        return $cart->checkout($this->buyer, $this->shop->id, [
            'delivery_type' => 'pickup',
            'payment_method' => 'cod',
            'channel' => 'web',
        ]);
    }

    private function makeShop(User $user, string $name): FreshMarketSeller
    {
        return FreshMarketSeller::create([
            'user_id' => $user->id,
            'shop_name' => $name,
            'phone' => '0812345678',
            'address' => '99 ถนนทดสอบ',
            'province' => 'กรุงเทพมหานคร',
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
            'total_listings' => 1,
        ]);
    }

    private function makeListing(FreshMarketSeller $shop, string $title): FreshMarketListing
    {
        return FreshMarketListing::create([
            'seller_id' => $shop->id,
            'category_id' => $this->category->id,
            'title' => $title,
            'description' => 'ทำสดใหม่ทุกวัน',
            'price' => 45,
            'unit' => 'จาน',
            'quantity_available' => 0,
            'track_stock' => false,
            'latitude' => $shop->latitude,
            'longitude' => $shop->longitude,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }
}
