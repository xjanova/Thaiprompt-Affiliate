<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * 🛒 ล็อกกติกา 2 ข้อของหน้าแรก (หน้าร้าน) ที่ owner สั่งไว้ 2026-09-09
 *
 *   1. รายการสินค้าต้อง "กดปุ่มโหลดเพิ่ม" เท่านั้น — ห้ามโหลดอัตโนมัติตอนเลื่อน
 *   2. แถบ Flash Deals ต้องเป็นของที่ลดราคาจริง — ห้ามกลับไปใช้เกณฑ์ที่ดูดของ seeder เดโมเข้ามา
 *
 * 🚨 ทำไมต้องล็อกด้วยเทสต์
 *   เกณฑ์เดิม `compare_at_price > price OR is_featured` ดูไม่มีพิษภัย แต่วัดจริงบนพร็อด
 *   ได้ 85 ชิ้น **เป็นสินค้า seeder เดโมทั้ง 85 ชิ้น** ส่วนสินค้า affiliate จริง 2,104 ชิ้น
 *   ไม่มี compare_at_price สักชิ้น ⇒ ใครเผลอ "ใส่ fallback กันหน้าแรกว่าง" กลับเข้าไป
 *   แถบดีลจะกลับไปเป็นของปลอมทันทีโดยไม่มีอะไรฟ้อง
 *
 * ⚠️ อ่าน source ตรง ๆ แทนการ boot แอป — ทั้งสองจุดต้องมี DB + Blade compiler
 *    ([[rule_verification_script_can_lie]] — ตรวจไฟล์จริง ไม่ใช่เดาจากชื่อฟังก์ชัน)
 */
class StorefrontFlashDealsAndLoadMoreTest extends TestCase
{
    private function storefrontIndexBlade(): string
    {
        $path = __DIR__.'/../../../resources/views/storefront/index.blade.php';
        $this->assertFileExists($path, 'ไม่เจอ view หน้าร้าน');

        return file_get_contents($path);
    }

    private function flashDealsMethodSource(): string
    {
        $path = __DIR__.'/../../../app/Http/Controllers/StorefrontController.php';
        $this->assertFileExists($path, 'ไม่เจอ StorefrontController');

        $src = file_get_contents($path);
        $start = strpos($src, 'private function getFlashDeals');
        $this->assertNotFalse($start, 'ไม่เจอเมธอด getFlashDeals');

        $end = strpos($src, 'private function getFilteredProducts', $start);
        $this->assertNotFalse($end, 'ไม่เจอเมธอด getFilteredProducts (โครงไฟล์เปลี่ยน?)');

        return substr($src, $start, $end - $start);
    }

    /** รายการสินค้าห้ามโหลดอัตโนมัติตอนเลื่อน — ต้องกดปุ่มเท่านั้น */
    public function test_product_list_never_auto_loads_on_scroll(): void
    {
        $blade = $this->storefrontIndexBlade();

        // x-intersect ที่ "สั่งงาน" (มี = ในบรรทัดเดียวกัน) คือตัวโหลดอัตโนมัติ
        // ส่วนคำว่า x-intersect ที่อยู่ในคอมเมนต์เตือนห้ามใส่กลับ ไม่นับ
        $this->assertDoesNotMatchRegularExpression(
            '/x-intersect[^\s>]*\s*=\s*"[^"]*loadMore/',
            $blade,
            'พบ x-intersect ที่เรียก loadMore() — owner สั่งให้ "กดโหลดเพิ่ม" ไม่ใช่โหลดตอนเลื่อน'
        );

        $this->assertStringContainsString(
            '@click="loadMore()"',
            $blade,
            'ต้องเหลือปุ่มกดโหลดเพิ่มไว้ ไม่งั้นหน้าจะดูสินค้าได้แค่หน้าแรก'
        );
    }

    /** Flash Deals ต้องคัดจากดีลที่ยืนยันกับปลายทางจริงเท่านั้น */
    public function test_flash_deals_only_from_verified_deals(): void
    {
        $source = $this->flashDealsMethodSource();

        $this->assertStringContainsString(
            "whereNotNull('deal_verified_at')",
            $source,
            'ต้องคัดเฉพาะของที่ยืนยันราคาลดกับปลายทางแล้ว'
        );

        $this->assertStringContainsString(
            "where('deal_verified_at', '>='",
            $source,
            'ต้องมีเพดานความสด ไม่งั้นโปรที่จบไปแล้วค้างหน้าแรกตลอดกาล'
        );

        $this->assertStringNotContainsString(
            "orWhere('is_featured', true)",
            $source,
            'ห้ามดึงของ is_featured กลับเข้ามา — สินค้า seeder เดโมทั้ง 85 ชิ้นเข้าทางนี้'
        );
    }

    /** ปุ่ม "ดู Flash Deals ทั้งหมด" ต้องกรองด้วยเกณฑ์ชุดเดียวกับแถบหน้าแรก */
    public function test_view_all_deals_link_uses_same_filter(): void
    {
        $path = __DIR__.'/../../../resources/views/components/storefront/flash-deals.blade.php';
        $this->assertFileExists($path, 'ไม่เจอ component flash-deals');
        $component = file_get_contents($path);

        $this->assertStringContainsString(
            "'deals' => 1",
            $component,
            'ลิงก์ "ดู Flash Deals ทั้งหมด" ต้องส่ง deals=1 ไม่งั้นได้สินค้าทั้งร้านเรียงตามส่วนลด'
        );

        $controller = file_get_contents(__DIR__.'/../../../app/Http/Controllers/StorefrontController.php');
        $this->assertStringContainsString(
            "\$request->boolean('deals')",
            $controller,
            'controller ต้องรู้จักตัวกรอง deals ไม่งั้นลิงก์ส่งไปแล้วไม่มีผล'
        );

        $this->assertStringContainsString(
            "case 'discount':",
            $controller,
            'sort_by=discount ต้องมี case จริง (ของเดิมตกไป default = เรียงตามใหม่ล่าสุด)'
        );
    }
}
