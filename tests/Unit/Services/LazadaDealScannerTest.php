<?php

namespace Tests\Unit\Services;

use App\Services\Marketplace\LazadaDealScanner;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * ⚡ ล็อกการแปลงข้อมูล "ดีลจริง" จากหน้ารายการ Lazada
 *
 * 🚨 ทำไมต้องมีเทสต์นี้ (2026-09-09):
 *   แถบ Flash Deals หน้าแรกเคยเป็นของปลอม 100% — วัดจริงบนพร็อดได้ 85 ชิ้น
 *   เป็นสินค้า seeder เดโมทั้ง 85 ชิ้น เพราะเกณฑ์เดิมคือ "compare_at_price > price"
 *   ซึ่งมีแต่ของ seeder เท่านั้นที่มี (สินค้า affiliate จริง 2,104 ชิ้นไม่มีสักชิ้น)
 *
 *   ตัวกวาดใหม่ไปอ่านราคาก่อนลดจากหน้ารายการของ Lazada เอง เทสต์นี้ล็อกว่า:
 *     1. ราคาที่มาเป็นสตริง/มีคอมมา ต้องแปลงถูก (ห้าม (float)"1,290" = 1)
 *     2. % ส่วนลดคำนวณจาก "ตัวเลข" ไม่ใช่แกะจากข้อความ "58% Off"
 *        (ข้อความนั้นบางทีเป็นส่วนลดคูปอง ไม่ใช่ส่วนลดของราคาที่แสดง)
 *     3. ของที่ไม่มีราคาก่อนลด → original_price = null ⇒ ตกรอบ ไม่ขึ้นหน้าแรก
 *     4. ยอดขาย "2.3K sold" ต้องกลายเป็น 2300 ไม่ใช่ 2
 *
 * ⚠️ ไม่แตะฐานข้อมูลและไม่ยิงเน็ต — ทดสอบเฉพาะตัวแปลง (normalizeRow) ด้วย payload จริง
 *    ที่ก็อปโครงมาจาก response ของ www.lazada.co.th/catalog/?ajax=true (2026-09-09)
 */
class LazadaDealScannerTest extends TestCase
{
    private function normalize(array $row): ?array
    {
        $method = new ReflectionMethod(LazadaDealScanner::class, 'normalizeRow');
        $method->setAccessible(true);

        return $method->invoke(new LazadaDealScanner, $row);
    }

    /** โครง payload จริงจากหน้ารายการ Lazada (ตัดเหลือฟิลด์ที่เราใช้) */
    private function sampleRow(array $overrides = []): array
    {
        return array_merge([
            'itemId' => '5223874171',
            'name' => 'Samsung Galaxy Watch7 AI สมาร์ทวอทช์ ระบบบลูทูธ 40MM,44MM',
            'image' => 'https://th-live-01.slatic.net/p/38d3358790b79e9ef0a3dd141033361f.jpg',
            'price' => '4590',
            'originalPrice' => '10900',
            'discount' => '58% Off',
            'inStock' => true,
            'sellerName' => 'Samsung',
            'sellerId' => '1000001003',
            'brandName' => 'Samsung',
            'brandId' => '127166803',
            'ratingScore' => '4.964322120285423',
            'review' => '981',
            'itemSoldCntShow' => '2.3K sold',
        ], $overrides);
    }

    /** แถวจริงจากหน้ารายการ → แปลงครบ และ % ส่วนลดคิดจากตัวเลข */
    public function test_normalizes_a_real_listing_row(): void
    {
        $item = $this->normalize($this->sampleRow());

        $this->assertNotNull($item, 'แถวปกติต้องแปลงได้');
        $this->assertSame('5223874171', $item['item_id']);
        $this->assertSame(4590.0, $item['price']);
        $this->assertSame(10900.0, $item['original_price']);
        // (10900-4590)/10900 = 57.89% → ปัดเป็น 58
        $this->assertSame(58, $item['discount_percent']);
        $this->assertTrue($item['in_stock']);
        $this->assertSame(2300, $item['sold_count']);
        $this->assertSame(981, $item['review_count']);
        $this->assertSame('https://www.lazada.co.th/products/pdp-i5223874171.html', $item['url']);
    }

    /** ราคามีคอมมา/สัญลักษณ์บาท ต้องไม่กลายเป็นเลขหลักเดียว */
    public function test_price_with_commas_is_not_truncated(): void
    {
        $item = $this->normalize($this->sampleRow([
            'price' => '฿1,290.00',
            'originalPrice' => '฿2,590.00',
        ]));

        $this->assertNotNull($item);
        $this->assertSame(1290.0, $item['price'], '(float)"1,290" = 1 — ต้องล้างคอมมาก่อนแปลง');
        $this->assertSame(2590.0, $item['original_price']);
        $this->assertSame(50, $item['discount_percent']);
    }

    /** ไม่มีราคาก่อนลด = ไม่ใช่ดีล → original_price ต้องเป็น null เพื่อให้ตกรอบ */
    public function test_missing_original_price_yields_null(): void
    {
        $item = $this->normalize($this->sampleRow(['originalPrice' => '', 'discount' => '']));

        $this->assertNotNull($item);
        $this->assertNull($item['original_price'], 'ไม่มีราคาก่อนลด = ไม่ใช่ดีล ต้องคัดออกที่คำสั่ง');
        $this->assertSame(0, $item['discount_percent']);
    }

    /** ราคาก่อนลด ≤ ราคาขาย ต้องไม่นับเป็นส่วนลด (ห้ามติดลบ) */
    public function test_original_price_not_higher_is_not_a_discount(): void
    {
        $item = $this->normalize($this->sampleRow(['originalPrice' => '4590']));

        $this->assertNotNull($item);
        $this->assertNull($item['original_price']);
        $this->assertSame(0, $item['discount_percent'], 'ราคาเท่ากัน = ลด 0% ห้ามกลายเป็นค่าติดลบ');
    }

    /** ข้อความ "80% Off" ของ Lazada (รวมคูปอง) ห้ามชนะการคำนวณจากราคาจริง */
    public function test_discount_text_never_overrides_computed_percent(): void
    {
        // Lazada เขียน "80% Off" (ส่วนลดรวมคูปอง) แต่ราคาจริงลดแค่ 20%
        $item = $this->normalize($this->sampleRow([
            'price' => '800',
            'originalPrice' => '1000',
            'discount' => '80% Off',
        ]));

        $this->assertSame(20, $item['discount_percent'], 'ต้องคิดจากราคา ไม่ใช่แกะจากข้อความ');
    }

    /** แถวที่ขาด id / ชื่อ / ราคา ต้องถูกทิ้ง */
    public function test_incomplete_rows_are_dropped(): void
    {
        $this->assertNull($this->normalize($this->sampleRow(['itemId' => '', 'nid' => ''])), 'ไม่มี id ต้องทิ้ง');
        $this->assertNull($this->normalize($this->sampleRow(['name' => ''])), 'ไม่มีชื่อต้องทิ้ง');
        $this->assertNull($this->normalize($this->sampleRow(['price' => '0'])), 'ราคา 0 ต้องทิ้ง');
    }

    /** รูป protocol-relative ต้องเติม https · รูปที่ไม่ใช่ https ต้องถูกตัดทิ้ง */
    public function test_only_https_images_are_kept(): void
    {
        $protocolRelative = $this->normalize($this->sampleRow(['image' => '//th-live-01.slatic.net/p/x.jpg']));
        $this->assertSame('https://th-live-01.slatic.net/p/x.jpg', $protocolRelative['image']);

        $insecure = $this->normalize($this->sampleRow(['image' => 'http://evil.example/x.jpg']));
        $this->assertSame('', $insecure['image'], 'รูปที่ไม่ใช่ https ต้องไม่ถูกฝังลงหน้าร้าน');
    }

    /** ยอดขาย "2.3K sold" ต้องกลายเป็น 2300 ไม่ใช่ 2 */
    public function test_sold_count_formats(): void
    {
        $cases = [
            '2.3K sold' => 2300,
            '1.2M sold' => 1200000,
            '45 sold' => 45,
            'ขายแล้ว' => 0,
            '' => 0,
        ];

        foreach ($cases as $text => $expected) {
            $item = $this->normalize($this->sampleRow(['itemSoldCntShow' => $text]));
            $this->assertSame($expected, $item['sold_count'], "แปลง \"{$text}\" ผิด");
        }
    }
}
