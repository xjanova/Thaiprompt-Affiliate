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

    public function test_fortune_goes_to_the_juntra_website(): void
    {
        $juntra = config('services.juntra.url');
        $this->assertNotEmpty($juntra);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('href="'.$juntra.'"', $html, 'การ์ด/เมนูดูดวงต้องพาไปเว็บแม่หมอจันทรา');

        // ส่วนดูดวง/ไพ่ทาโรต์ทั้งหมดบนเว็บนี้เด้งไปเว็บจันทรา (ชื่อ route เดิมยังสร้างลิงก์ได้)
        foreach (['/horoscope', '/horoscope/daily', '/horoscope/tarot/quick', '/tarot', '/tarot/cart', '/tarot/history'] as $path) {
            $this->get($path)->assertRedirect($juntra);
        }
        $this->assertStringEndsWith('/tarot', route('tarot.index'));
    }

    public function test_missing_page_shows_the_nova_404(): void
    {
        $html = $this->get('/ไม่มีหน้านี้-nova-404')->assertNotFound()->getContent();

        $this->assertStringContainsString('ไม่พบหน้าที่คุณต้องการ', $html);
        $this->assertStringContainsString('theme-nova/nova.css', $html);
        $this->assertStringContainsString('images/nova/deco/lamp.webp', $html);
    }

    public function test_logged_in_buyer_sees_cart_in_nova_header(): void
    {
        $buyer = $this->makeBuyer();

        $html = $this->actingAs($buyer)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="nv-nav"', $html);
        $this->assertStringContainsString(route('cart.index'), $html, 'ไม่มีปุ่มตะกร้าสำหรับสมาชิก');
        $this->assertStringContainsString('ออกจากระบบ', $html);
    }

    public function test_browse_mode_uses_the_solid_nova_header_without_the_hero(): void
    {
        $html = $this->get(route('storefront.index', ['search' => 'ทดสอบ']))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="nv-hero"', $html, 'หน้าค้นหาไม่มีฮีโร่');
        $this->assertStringContainsString('nv-nav--solid', $html, 'หน้าค้นหาต้องใช้แถบหัวโนวาแบบทึบ');
        $this->assertStringContainsString('theme-nova/nova.css', $html, 'แถบหัวโนวาต้องโหลด nova.css เอง');
        $this->assertMatchesRegularExpression('/<body class="[^"]*\bnv-body\b/', $html);
        $this->assertStringContainsString('value="ทดสอบ"', $html, 'ช่องค้นหาต้องแสดงคำที่ค้นอยู่');
        $this->assertStringNotContainsString('tp-ph-desk', $html, 'ไม่ซ้อนแถบหัว V4');
    }

    public function test_public_pages_fall_back_to_v4_when_nova_public_is_off(): void
    {
        config(['shop.nova_public' => false]);

        $html = $this->get(route('storefront.index', ['search' => 'ทดสอบ']))->assertOk()->getContent();

        $this->assertStringContainsString('tp-ph-desk', $html, 'ปิดโนวาหน้าสาธารณะ = แถบหัว V4');
        $this->assertStringNotContainsString('theme-nova/nova.css', $html);
        $this->assertStringNotContainsString('nv-body', $html);
    }

    public function test_nova_home_can_be_switched_off(): void
    {
        config(['shop.nova_home' => false, 'shop.nova_public' => false]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="nv-hero"', $html);
        $this->assertStringNotContainsString('theme-nova/nova.css', $html);
        $this->assertStringContainsString('tp-ph-desk', $html, 'ปิดธีมใหม่ทั้งหมดแล้วต้องกลับไปแถบหัว V4');
    }

    public function test_contact_and_guide_pages_use_nova_and_fall_back_when_off(): void
    {
        $contact = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringContainsString('nv-page-hero', $contact);
        $this->assertStringContainsString('nv-ccard', $contact);
        $this->assertStringContainsString('mailto:'.\App\Support\ContactInfo::supportEmail(), $contact);
        $this->assertStringContainsString('theme-nova/nova-tw.css', $contact);

        $guide = $this->get('/how-to-register')->assertOk()->getContent();
        $this->assertStringContainsString('nv-hero-band', $guide);
        $this->assertStringNotContainsString('from-green-600 to-emerald-600', $guide);

        config(['shop.nova_public' => false]);
        $contactOff = $this->get('/contact')->assertOk()->getContent();
        $this->assertStringNotContainsString('nv-page-hero', $contactOff);
        $this->assertStringContainsString('mailto:'.\App\Support\ContactInfo::supportEmail(), $contactOff, 'ปิดธีมแล้วช่องทางอีเมลต้องยังอยู่');
        $this->assertStringContainsString('from-green-600 to-emerald-600', $this->get('/how-to-register')->assertOk()->getContent());
    }

    public function test_status_pages_use_nova_and_never_reload_in_a_loop(): void
    {
        // 503: นับถอยหลังเฉพาะเวลาที่ยังไม่ถึง · เลยเวลาแล้วต้องไม่มีสคริปต์โหลดซ้ำ
        $m = \App\Models\AppMaintenance::getInstance();
        $m->update(['show_countdown' => true, 'scheduled_end' => now()->addHour(), 'message' => 'ทดสอบข้อความปิดปรับปรุง']);
        $html = view('errors.503')->render();
        $this->assertStringContainsString('nv-foil', $html);
        $this->assertStringContainsString('ทดสอบข้อความปิดปรับปรุง', $html);
        $this->assertStringContainsString('id="mt-cd"', $html);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);

        $m->update(['scheduled_end' => now()->subMinute()]);
        $late = view('errors.503')->render();
        $this->assertStringNotContainsString('id="mt-cd"', $late, 'เลยเวลาแล้วห้ามนับถอยหลัง (เดิม reload ทุกวินาที)');
        $this->assertStringNotContainsString('location.reload(); }, 30000', $late);

        // 403 IP ถูกบล็อก: ภาษาไทย + โชว์ IP/สาเหตุ (escape) + ไม่มีปุ่มพากลับเข้าเว็บเป็นปุ่มหลัก
        $blocked = view('errors.blocked', ['ip' => '203.0.113.45', 'reason' => '<b>brute</b>'])->render();
        $this->assertStringContainsString('ระบบระงับการเข้าถึงชั่วคราว', $blocked);
        $this->assertStringContainsString('203.0.113.45', $blocked);
        $this->assertStringContainsString('&lt;b&gt;brute&lt;/b&gt;', $blocked);
        $this->assertStringContainsString('mailto:', $blocked);

        // หน้าออฟไลน์: ต้องอยู่ได้โดยไม่มีเน็ต (ไม่มี CSS ภายนอก) และไม่ reload วนตอนเปิด /offline ตรงๆ
        $offline = $this->get('/offline')->assertOk()->getContent();
        $this->assertStringNotContainsString('<link rel="stylesheet"', $offline);
        $this->assertStringNotContainsString('setTimeout(() => location.reload(), 500)', $offline);
        $this->assertStringContainsString("location.href = '/'", $offline);

        $this->assertStringContainsString('nv-lg', $this->get('/auth/line/register-guide')->assertOk()->getContent());
    }

    public function test_nova_assets_are_shipped(): void
    {
        $files = [
            'theme-nova/nova.css', 'theme-nova/nova.js', 'theme-nova/nova-tw.css', 'images/nova/hero-temple.webp',
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
        foreach (['lotus', 'lantern', 'coins', 'gift', 'crystal', 'garland', 'umbrella', 'bell', 'elephant', 'scroll', 'scooter', 'bag', 'lamp'] as $deco) {
            $files[] = "images/nova/deco/{$deco}.webp";
        }

        foreach ($files as $file) {
            $this->assertFileExists(public_path($file), "ไฟล์ธีมหาย: {$file}");
        }
    }
}
