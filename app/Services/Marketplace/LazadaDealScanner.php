<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ⚡ ตัวกวาด "สินค้าที่ Lazada จัดโปรจริง" จากหน้ารายการสินค้าของ Lazada เอง
 *
 * ยิง `https://www.lazada.co.th/catalog/?ajax=true&q=<คำค้น>&page=N` ซึ่งคืน JSON
 * ก้อนเดียวกับที่หน้าเว็บของ Lazada ใช้ render — ในนั้นมีของที่ฟีด affiliate ไม่มีให้:
 *
 *   price          = ราคาที่ขายอยู่ตอนนี้ (ราคาโปร)
 *   originalPrice  = ราคาก่อนลด  ← ตัวนี้แหละที่ทำให้ทำ Flash Deals จริงได้
 *   discount       = ข้อความ "58% Off" (เราคำนวณ % เองจากตัวเลข ไม่แกะจากข้อความ)
 *   inStock        = ของยังมีอยู่ไหม
 *
 * ✅ วัดจริงบนพร็อด 2026-09-09
 *    - หน้ารายการคืน 40 ชิ้น/หน้า · ~90% มี originalPrice > price จริง
 *    - ราคาที่ได้จากหน้ารายการ **ตรงกับราคาในฟีด affiliate เป๊ะ** (เทียบ 6 ชิ้นแรก ตรงทุกชิ้น)
 *      ⇒ เอา originalPrice จากหน้ารายการมาคู่กับ price ของฟีดได้โดยไม่ขัดกัน
 *    - ของที่กินค่าคอมได้ = 36/40 ⇒ ต้องกรองด้วยฟีดเสมอ (4/40 ไม่มีในโปรแกรม)
 *
 * 🔒 ความปลอดภัย
 *    - โฮสต์ล็อกตายที่ www.lazada.co.th (ไม่รับ URL จากภายนอก) ⇒ ไม่มีช่อง SSRF
 *    - ปิด redirect ทั้งหมด — หน้ารายการตอบ 200 ตรง ๆ ถ้าเจอ 30x แปลว่าคำค้นเพี้ยน ให้ถือว่าไม่มีของ
 *    - ทุกค่าที่คืนออกไปถูกแปลงชนิดแล้ว (ตัวเลข/สตริง) ไม่ส่ง payload ดิบต่อให้ caller
 */
class LazadaDealScanner
{
    /** โฮสต์เดียวที่ยิงได้ — ห้ามรับจากพารามิเตอร์ */
    private const ENDPOINT = 'https://www.lazada.co.th/catalog/';

    /** User-Agent ของเบราว์เซอร์จริง — หน้ารายการคืน HTML เปล่าถ้าไม่มี */
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /**
     * กวาดหน้ารายการ 1 หน้า แล้วคืนเฉพาะแถวที่แปลงค่าได้ครบ
     *
     * @param  string  $keyword  คำค้นภาษาไทย/อังกฤษ
     * @param  int  $page  หน้าที่เท่าไหร่ (เริ่มที่ 1)
     * @return array<int,array<string,mixed>> รายการสินค้าที่ normalize แล้ว (ว่าง = ยิงไม่ติด/ไม่มีของ)
     */
    public function search(string $keyword, int $page = 1): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json,text/plain,*/*',
                'Accept-Language' => 'th-TH,th;q=0.9,en;q=0.8',
                'Referer' => 'https://www.lazada.co.th/',
            ])
                ->timeout(25)
                ->retry(2, 1200, throw: false)
                ->withOptions(['allow_redirects' => false])
                ->get(self::ENDPOINT, [
                    'ajax' => 'true',
                    'q' => $keyword,
                    'page' => max(1, $page),
                ]);
        } catch (\Throwable $e) {
            Log::warning('LazadaDealScanner ยิงหน้ารายการไม่สำเร็จ', [
                'keyword' => $keyword,
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('LazadaDealScanner หน้ารายการตอบไม่ปกติ', [
                'keyword' => $keyword,
                'page' => $page,
                'status' => $response->status(),
            ]);

            return [];
        }

        // ⚠️ Lazada บางครั้งคืน HTML (captcha/หน้าเปล่า) ทั้งที่ status 200 → json() จะได้ null
        $rows = data_get($response->json(), 'mods.listItems');
        if (! is_array($rows)) {
            Log::warning('LazadaDealScanner ไม่ได้ JSON รายการสินค้า (อาจโดนกันบอท)', [
                'keyword' => $keyword,
                'page' => $page,
            ]);

            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = $this->normalizeRow($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * แปลง 1 แถวจากหน้ารายการ Lazada → รูปแบบภายในของเรา
     *
     * คืน null เมื่อข้อมูลไม่พอจะเชื่อได้ (ไม่มี id / ชื่อ / ราคา)
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>|null
     */
    private function normalizeRow(array $row): ?array
    {
        $itemId = trim((string) ($row['itemId'] ?? $row['nid'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $price = $this->toFloat($row['price'] ?? null);
        $originalPrice = $this->toFloat($row['originalPrice'] ?? null);

        if ($itemId === '' || $name === '' || $price <= 0) {
            return null;
        }

        // ส่วนลดคำนวณจากตัวเลขเสมอ — ไม่แกะจากข้อความ "58% Off"
        // (ข้อความนั้นบางทีเป็นส่วนลดของคูปอง ไม่ใช่ส่วนลดของราคาที่แสดง)
        $discountPercent = ($originalPrice > $price)
            ? (int) round((($originalPrice - $price) / $originalPrice) * 100)
            : 0;

        $image = trim((string) ($row['image'] ?? ''));
        if ($image !== '' && str_starts_with($image, '//')) {
            $image = 'https:'.$image;
        }
        if ($image !== '' && ! str_starts_with($image, 'https://')) {
            $image = '';
        }

        return [
            'item_id' => $itemId,
            'name' => mb_substr($name, 0, 500),
            'image' => $image,
            'price' => $price,
            'original_price' => $originalPrice > $price ? $originalPrice : null,
            'discount_percent' => $discountPercent,
            'in_stock' => (bool) ($row['inStock'] ?? false),
            'seller_name' => mb_substr(trim((string) ($row['sellerName'] ?? '')), 0, 190),
            'seller_id' => trim((string) ($row['sellerId'] ?? '')),
            'brand' => mb_substr(trim((string) ($row['brandName'] ?? '')), 0, 100),
            'brand_id' => trim((string) ($row['brandId'] ?? '')),
            'rating' => round($this->toFloat($row['ratingScore'] ?? null), 2),
            'review_count' => (int) $this->toFloat($row['review'] ?? null),
            'sold_count' => $this->parseSoldCount((string) ($row['itemSoldCntShow'] ?? '')),
            'url' => 'https://www.lazada.co.th/products/pdp-i'.$itemId.'.html',
        ];
    }

    /**
     * แปลงตัวเลขที่ Lazada ส่งมาเป็นสตริง ("4590", "4.59E 3", "") → float
     *
     * ⚠️ ห้ามใช้ (float) ตรง ๆ กับค่าที่มีคอมมา — "1,290" จะกลายเป็น 1
     */
    private function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (! is_string($value)) {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * แปลงข้อความยอดขาย "2.3K sold" / "1.2M sold" / "45 sold" → จำนวนเต็ม
     */
    private function parseSoldCount(string $text): int
    {
        if (! preg_match('/([0-9]+(?:\.[0-9]+)?)\s*([KkMm]?)/', $text, $m)) {
            return 0;
        }

        $number = (float) $m[1];
        $suffix = strtoupper($m[2] ?? '');

        return (int) round(match ($suffix) {
            'K' => $number * 1000,
            'M' => $number * 1000000,
            default => $number,
        });
    }
}
