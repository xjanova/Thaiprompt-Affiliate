<?php

namespace Tests\Feature\Shop;

use App\Models\ProductCategory;
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
 * หน้าแรกธีม "โนวา" (ต้องใช้ MySQL)
 *
 * - หน้าแรก (/ และ storefront.index ที่ยังไม่ค้นหา/เลือกหมวด) = ฮีโร่กลางคืน + การ์ดบริการ 8 ใบ
 *   + ดีลเด็ด + หมวดหมู่ภาพชุดใหม่ + มาสคอต — แต่รายการสินค้าทั้งหมด (#products) ของเดิมต้องยังอยู่
 * - หน้าค้นหา/หมวด ยังเป็นธีม V4 เดิม (แถบหัว V4 ไม่มีฮีโร่)
 * - ปิดได้ด้วย config shop.nova_home=false → กลับไปหน้าแรก V4 เดิมทันที
 */
#[Group('shop')]
class StorefrontNovaHomeTest extends TestCase
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

    public function test_home_renders_nova_theme_for_guest(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['name' => 'สินค้าหน้าแรกธีมโนวา']);
        // หมวดที่ชื่อ/slug ตรงชุดภาพใหม่ ต้องได้ภาพประจำหมวด
        ProductCategory::whereKey($product->category_id)->update(['name' => 'สัตว์เลี้ยง', 'slug' => 'pets']);

        foreach (['/', route('storefront.index')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('tp-root', $html, 'ต้องยังอยู่บน layout frontend-v4');
            $this->assertMatchesRegularExpression('/<body class="tp-root\s+nv-body"/', $html, 'body ต้องมี nv-body ให้การ์ดสินค้า/ร้านเป็นสีโนวา');
            $this->assertStringContainsString('id="nv-hero"', $html, 'ไม่มีฮีโร่ธีมใหม่');
            $this->assertStringContainsString('theme-nova/nova.css', $html);
            $this->assertStringContainsString('theme-nova/nova.js', $html);
            $this->assertStringContainsString('id="nv-svc-grid"', $html, 'ไม่มีการ์ดบริการ');
            $this->assertStringContainsString('id="nv-guide"', $html, 'ไม่มีมาสคอต');
            $this->assertStringContainsString(route('taladsod.home'), $html, 'การ์ดตลาดสดต้องลิงก์ไปหน้าจริง');
            $this->assertStringContainsString('id="nv-cats"', $html, 'ไม่มีแถบหมวดหมู่');
            $this->assertStringContainsString('images/nova/cat/pets.webp', $html, 'หมวดสัตว์เลี้ยงต้องได้ภาพชุดใหม่');
            $this->assertStringContainsString('id="products"', $html, 'รายการสินค้าทั้งหมดของเดิมหาย');
            $this->assertStringContainsString($product->name, $html);
            // เมนูหลักเดียวกับแถบหัวสาธารณะ
            $this->assertStringContainsString('ตลาดสด', $html);
            $this->assertStringContainsString('เป็นไรเดอร์', $html);
            $this->assertStringContainsString('เปิดร้าน', $html);
            // ไม่ซ้อนแถบหัว V4 กับแถบหัวธีมใหม่
            $this->assertStringNotContainsString('tp-ph-desk', $html, 'แถบหัว V4 ซ้อนมาด้วย');
        }
    }

    public function test_logged_in_buyer_sees_cart_in_nova_header(): void
    {
        $buyer = $this->makeBuyer();

        $html = $this->actingAs($buyer)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="nv-nav"', $html);
        $this->assertStringContainsString(route('cart.index'), $html, 'ไม่มีปุ่มตะกร้าสำหรับสมาชิก');
        $this->assertStringContainsString('ออกจากระบบ', $html);
    }

    public function test_browse_mode_keeps_v4_layout(): void
    {
        $html = $this->get(route('storefront.index', ['search' => 'ทดสอบ']))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="nv-hero"', $html);
        $this->assertStringNotContainsString('theme-nova/nova.css', $html);
        $this->assertStringContainsString('tp-ph-desk', $html, 'หน้าค้นหาต้องยังใช้แถบหัว V4');
        $this->assertStringNotContainsString('nv-body', $html, 'หน้าค้นหาต้องไม่โดนสกินโนวา');
    }

    public function test_nova_home_can_be_switched_off(): void
    {
        config(['shop.nova_home' => false]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="nv-hero"', $html);
        $this->assertStringNotContainsString('theme-nova/nova.css', $html);
        $this->assertStringContainsString('tp-ph-desk', $html, 'ปิดธีมใหม่แล้วต้องกลับไปแถบหัว V4');
    }

    public function test_nova_assets_are_shipped(): void
    {
        $files = [
            'theme-nova/nova.css', 'theme-nova/nova.js', 'images/nova/hero-temple.webp',
            'images/nova/mascot/welcome.webp', 'images/nova/mascot/present.webp', 'images/nova/mascot/face.webp',
            'images/nova/brand/kanok-gold.webp', 'images/nova/brand/tabbar-kanok-arch.webp', 'images/nova/brand/tabbar-kanok-medallion.webp',
        ];
        foreach (['shop', 'market', 'cart', 'rider', 'store', 'fortune', 'earn'] as $svc) {
            $files[] = "images/nova/svc/{$svc}.webp";
        }
        foreach (['electronics', 'fashion', 'beauty', 'home', 'sports', 'books', 'toys', 'food', 'health', 'pets', 'amulet', 'wallet'] as $cat) {
            $files[] = "images/nova/cat/{$cat}.webp";
        }
        foreach (['rider', 'store', 'earn'] as $earn) {
            $files[] = "images/nova/earn/{$earn}.webp";
        }
        foreach (['lotus', 'lantern', 'coins', 'gift', 'crystal', 'garland'] as $deco) {
            $files[] = "images/nova/deco/{$deco}.webp";
        }

        foreach ($files as $file) {
            $this->assertFileExists(public_path($file), "ไฟล์ธีมหาย: {$file}");
        }
    }
}
