<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * LazadaImportService
 *
 * ดึงข้อมูลสินค้าจากหน้า "รายละเอียดสินค้า" (PDP) ของ Lazada Thailand
 * แล้วแปลงเป็นโครงสร้างที่พร้อมนำเข้าระบบ Product ของเรา
 *
 * แนวทาง:
 *  - Lazada ฝัง JSON ของสินค้าไว้ในตัวแปร `var __moduleData__ = {...}` ในหน้า PDP
 *  - เราดึง HTML ของหน้าสินค้า (server-side) แล้วแกะ JSON ก้อนนั้นออกมา
 *  - หน้า "ค้นหา/หมวดหมู่" ของ Lazada โหลดสินค้าด้วย JS + ถูกบล็อกฝั่ง server
 *    (ขึ้น captcha/punish) จึง "ไม่รองรับ" — แอดมินต้องวางลิงก์หน้าสินค้าโดยตรง
 *
 * ⚠️ ความปลอดภัย: รายละเอียด (description) ของ Lazada เป็น HTML จากภายนอก
 *    ที่ไม่น่าเชื่อถือ → ต้อง sanitize ก่อนเก็บเสมอ (กัน stored XSS)
 *    เพราะหน้าร้านเรา render ด้วย {!! strip_tags(...) !!} ซึ่งไม่ตัด attribute
 */
class LazadaImportService
{
    /** โดเมนที่อนุญาตให้ดึงข้อมูล */
    private const ALLOWED_HOSTS = ['www.lazada.co.th', 'lazada.co.th', 'm.lazada.co.th'];

    /** แท็ก HTML ที่อนุญาตให้คงไว้ในรายละเอียด (ตรงกับที่หน้าร้านใช้) */
    private const ALLOWED_DESC_TAGS = '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><img><blockquote><table><thead><tbody><tr><th><td><hr><span>';

    /**
     * ดึง + แปลงข้อมูลสินค้า 1 รายการจากลิงก์ Lazada
     *
     * @param  string  $url  ลิงก์หน้าสินค้า Lazada (เช่น https://www.lazada.co.th/products/...-i123.html)
     * @return array ข้อมูลสินค้าที่ normalize แล้ว
     *
     * @throws RuntimeException เมื่อ URL ไม่ถูกต้อง / ถูกบล็อก / แกะข้อมูลไม่ได้
     */
    public function fetchProductData(string $url): array
    {
        $url = $this->normalizeUrl($url);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'th-TH,th;q=0.9,en;q=0.8',
            'Referer' => 'https://www.lazada.co.th/',
        ])->timeout(30)->retry(2, 1500)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("ดึงข้อมูลไม่สำเร็จ (HTTP {$response->status()}) — {$url}");
        }

        $html = $response->body();

        if ($this->looksBlocked($html)) {
            throw new RuntimeException('Lazada บล็อกการดึงข้อมูลจากเซิร์ฟเวอร์ (captcha/punish) — ลองใหม่อีกครั้ง หรือเปลี่ยนเครือข่าย/ลิงก์');
        }

        $module = $this->extractModuleData($html);

        if (! $module) {
            throw new RuntimeException('ไม่พบข้อมูลสินค้าในหน้านี้ — ตรวจสอบว่าเป็นลิงก์ "หน้าสินค้า" ของ Lazada จริง');
        }

        return $this->mapProduct($module, $url);
    }

    /**
     * แปลงข้อมูลหลายลิงก์พร้อมกัน — แต่ละลิงก์ที่ล้มเหลวจะถูกบันทึกเป็น error
     * (ไม่ทำให้ทั้ง batch ล้ม)
     *
     * @param  array<string>  $urls
     * @return array{ ok: array<int,array>, failed: array<int,array{url:string,error:string}> }
     */
    public function fetchMany(array $urls): array
    {
        $ok = [];
        $failed = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }

            try {
                $ok[] = $this->fetchProductData($url);
            } catch (\Throwable $e) {
                $failed[] = ['url' => $url, 'error' => $e->getMessage()];
                Log::warning('Lazada import: ดึงข้อมูลล้มเหลว', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return ['ok' => $ok, 'failed' => $failed];
    }

    /**
     * สร้าง Product + ProductImage จากข้อมูลที่ดึงมา (1 รายการ)
     *
     * @throws RuntimeException ถ้าเคยนำเข้าแล้ว
     */
    public function createProductFromData(array $data, int $sellerId, int $categoryId): Product
    {
        if ($this->isAlreadyImported($data['item_id'])) {
            throw new RuntimeException('นำเข้าแล้วก่อนหน้านี้');
        }

        return DB::transaction(function () use ($data, $sellerId, $categoryId) {
            $slugBase = Str::slug($data['name']);
            // จำกัดความยาว slug กันเกิน varchar(255) (ชื่อภาษาอังกฤษยาวๆ) — ต่อท้ายด้วย item_id ให้ไม่ซ้ำ
            $slug = mb_substr($slugBase !== '' ? $slugBase : 'lazada', 0, 200).'-'.$data['item_id'];

            $product = Product::create([
                'seller_id' => $sellerId,
                'category_id' => $categoryId,
                'store_id' => null,
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => 'LZD-'.$data['item_id'],
                'description' => $data['description_html'],
                'short_description' => $data['short_description'],
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'],
                'cost_price' => round($data['price'] * 0.7, 2),
                'commission_rate' => 10,
                'brand' => $data['brand'],
                'stock_quantity' => 50,
                'track_inventory' => true,
                'low_stock_threshold' => 5,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => false,
                'is_hidden' => false,            // คอลัมน์ prod nullable → ต้องตั้งชัดเจน ไม่งั้น scope visible() (is_hidden=false) ตกหล่น
                'is_public_approved' => true,    // แอดมินเป็นคนนำเข้าเอง = อนุมัติให้ขึ้นหน้าร้านได้เลย
                'public_approved_at' => now(),
                'has_variants' => false, // ตัวเลือกเก็บใน attributes (ยังไม่ทำ variant-based purchase)
                'published_at' => now(),
                'main_image_url' => $data['main_image'],
                'image_urls' => $data['images'],
                'weight' => 1,
                'meta_title' => mb_substr($data['name'], 0, 255),
                'meta_description' => $data['short_description'],
                'tags' => array_values(array_filter([$data['brand'], $data['lazada_category']])),
                'attributes' => [
                    'source' => [
                        'platform' => 'lazada',
                        'item_id' => $data['item_id'],
                        'url' => $data['source_url'],
                        'seller_name' => $data['seller_name'],
                        'imported_at' => now()->toIso8601String(),
                    ],
                    'lazada_category' => $data['lazada_category'],
                    'variants' => $data['variants'],
                ],
            ]);

            // บันทึกรูปทั้งหมด (รูปแรก = primary)
            foreach ($data['images'] as $i => $imageUrl) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imageUrl,
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                    'alt_text' => mb_substr($data['name'], 0, 255),
                ]);
            }

            return $product;
        });
    }

    /**
     * เคยนำเข้าสินค้านี้แล้วหรือยัง (กันซ้ำด้วย item_id ใน attributes)
     */
    public function isAlreadyImported(string $itemId): bool
    {
        return Product::query()
            ->where('attributes->source->item_id', $itemId)
            ->exists();
    }

    /**
     * เดาหมวดหมู่จาก breadcrumb ของ Lazada + ชื่อสินค้า
     */
    public function suggestCategoryId(string $lazadaCategory, string $name): ?int
    {
        $haystack = mb_strtolower($lazadaCategory.' '.$name);

        $applianceKeywords = ['เครื่องใช้ไฟฟ้า', 'หม้อ', 'ไมโครเวฟ', 'พัดลม', 'ตู้เย็น', 'เครื่องซักผ้า', 'เครื่องดูดฝุ่น', 'เตา', 'กระติก', 'หม้อทอด', 'แอร์', 'เครื่องปรับอากาศ', 'เครื่องฟอก'];
        foreach ($applianceKeywords as $kw) {
            if (str_contains($haystack, mb_strtolower($kw))) {
                return optional($this->findApplianceCategory())->id;
            }
        }

        // ค่าเริ่มต้น = ไอที / คอมพิวเตอร์
        return $this->ensureItCategory()->id;
    }

    /**
     * หา/สร้างหมวด "ไอที / คอมพิวเตอร์"
     */
    public function ensureItCategory(): ProductCategory
    {
        return ProductCategory::firstOrCreate(
            ['slug' => 'it-computer'],
            [
                'name' => 'ไอที / คอมพิวเตอร์',
                'description' => 'สินค้าไอที คอมพิวเตอร์ โน้ตบุ๊ค และอุปกรณ์เสริม',
                'is_active' => true,
                'sort_order' => 16,
            ]
        );
    }

    /**
     * หาหมวด "เครื่องใช้ไฟฟ้าในบ้าน" (มีจาก seeder อยู่แล้ว)
     */
    public function findApplianceCategory(): ?ProductCategory
    {
        return ProductCategory::where('name', 'เครื่องใช้ไฟฟ้าในบ้าน')->first()
            ?? ProductCategory::where('name', 'like', '%เครื่องใช้ไฟฟ้า%')->first();
    }

    /**
     * ตรวจ + ทำให้ URL อยู่ในรูปแบบมาตรฐาน
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // เผื่อแอดมินวางลิงก์แบบไม่มี protocol
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            throw new RuntimeException("ลิงก์ไม่ถูกต้อง: {$url}");
        }

        $host = strtolower($parts['host']);
        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new RuntimeException("รองรับเฉพาะลิงก์ของ lazada.co.th เท่านั้น (พบ: {$host})");
        }

        $path = $parts['path'] ?? '/';

        // หน้าสินค้า Lazada จะมี /products/ และ item id แบบ -i<ตัวเลข>
        if (! str_contains($path, '/products/') && ! preg_match('/-i\d+/', $path)) {
            throw new RuntimeException('ลิงก์นี้ไม่ใช่หน้าสินค้า (PDP) ของ Lazada');
        }

        // ตัด query/fragment ทิ้ง (หน้า PDP เปิดได้ด้วย path ล้วน) เพื่อความสะอาด/กันข้อมูลติดตาม
        return 'https://www.lazada.co.th'.$path;
    }

    /**
     * ตรวจว่าหน้าที่ได้คือหน้า captcha/บล็อกหรือไม่
     */
    private function looksBlocked(string $html): bool
    {
        if (strlen($html) < 5000 && (stripos($html, 'captcha') !== false || stripos($html, 'punish') !== false)) {
            return true;
        }

        return stripos($html, 'slidecaptcha') !== false
            || stripos($html, 'nc_iconfont') !== false
            || stripos($html, 'Access Denied') !== false;
    }

    /**
     * แกะ JSON ก้อน `var __moduleData__ = {...}` ออกจาก HTML
     * ใช้การจับคู่วงเล็บปีกกาโดยข้าม string/escape อย่างถูกต้อง
     */
    private function extractModuleData(string $html): ?array
    {
        $marker = 'var __moduleData__ = ';
        $pos = strpos($html, $marker);
        if ($pos === false) {
            return null;
        }

        $start = $pos + strlen($marker);
        $len = strlen($html);
        $depth = 0;
        $inStr = false;
        $esc = false;
        $end = $start;

        for ($i = $start; $i < $len; $i++) {
            $ch = $html[$i];

            if ($inStr) {
                if ($esc) {
                    $esc = false;
                } elseif ($ch === '\\') {
                    $esc = true;
                } elseif ($ch === '"') {
                    $inStr = false;
                }

                continue;
            }

            if ($ch === '"') {
                $inStr = true;

                continue;
            }

            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i + 1;
                    break;
                }
            }
        }

        $json = substr($html, $start, $end - $start);
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    /**
     * แปลง __moduleData__ → โครงสร้างสินค้าของเรา
     */
    private function mapProduct(array $module, string $sourceUrl): array
    {
        $fields = data_get($module, 'data.root.fields', []);

        $title = trim((string) data_get($fields, 'product.title', ''));
        if ($title === '') {
            $title = trim((string) data_get($fields, 'tracking.pdt_name', ''));
        }
        if ($title === '') {
            throw new RuntimeException('ไม่พบชื่อสินค้า');
        }

        $brand = trim((string) data_get($fields, 'product.brand.name', '')) ?: null;

        // ราคา: อยู่ในชุด tracking เป็นข้อความ เช่น "฿18,000.00"
        $price = $this->parsePrice((string) data_get($fields, 'tracking.pdt_price', ''));
        if ($price <= 0) {
            // เผื่อ fallback: หาค่า ฿ แรกในข้อมูล sku
            $price = $this->parsePrice((string) data_get($fields, 'price.priceText', ''));
        }
        if ($price <= 0) {
            throw new RuntimeException('ไม่พบราคาสินค้า (อาจต้องเปิดดูสินค้าก่อน หรือสินค้าหมด)');
        }

        // ราคาเปรียบเทียบ (ราคาก่อนลด) ถ้ามี
        $comparePrice = $this->parsePrice((string) data_get($fields, 'tracking.pdt_originalPrice', ''));
        if ($comparePrice <= $price) {
            $comparePrice = null;
        }

        $images = $this->extractImages($fields);
        $variants = $this->extractVariants($fields);
        $lazadaCategory = $this->asString(data_get($fields, 'tracking.pdt_category', ''), ' > ');

        $descHtml = $this->sanitizeHtml((string) data_get($fields, 'product.desc', ''));

        // ถ้ารายละเอียดจริง "บางเกินไป" หรือเป็นแค่คำเชิญชวนติดต่อ (QR/LINE)
        // → สร้างรายละเอียดภาษาไทยแบบมาตรฐานจากชื่อ/แบรนด์/ตัวเลือก แทน
        $descText = trim(strip_tags($descHtml));
        $looksLikePitch = (bool) preg_match('/(QR|สแกน|add\s*friend|contact\s*(the\s*)?seller|line\s*id)/i', $descText);
        if (mb_strlen($descText) < 120 || $looksLikePitch) {
            $descHtml = $this->buildGeneratedDescription($title, $brand, $variants, $lazadaCategory);
        }

        $itemId = $this->asString(data_get($fields, 'tracking.pdt_sku'))
            ?: $this->asString(data_get($fields, 'primaryKey'))
            ?: $this->itemIdFromUrl($sourceUrl);

        return [
            'item_id' => $itemId,
            'source_url' => $sourceUrl,
            'source_platform' => 'lazada',
            'name' => mb_substr($title, 0, 255),
            'brand' => $brand ? mb_substr($brand, 0, 100) : null,
            'price' => $price,
            'compare_at_price' => $comparePrice,
            'description_html' => $descHtml,
            'short_description' => $this->buildShortDescription($title, $brand),
            'images' => $images,
            'main_image' => $images[0] ?? null,
            'variants' => $variants,
            'has_variants' => count($variants) > 0,
            'lazada_category' => $lazadaCategory,
            'seller_name' => $this->asString(data_get($fields, 'tracking.seller_name', '')),
        ];
    }

    /**
     * สร้างคำโปรยสั้นภาษาไทยแบบสะอาด (ใช้บนการ์ดสินค้า/SEO)
     */
    private function buildShortDescription(string $name, ?string $brand): string
    {
        $brandPart = $brand ? "{$brand} " : '';
        $text = "{$brandPart}{$name} ของแท้ พร้อมส่ง รับประกันคุณภาพ จัดส่งทั่วไทย";

        return mb_substr($text, 0, 480);
    }

    /**
     * สร้างรายละเอียดภาษาไทย (HTML) จากข้อมูลที่มี เมื่อรายละเอียดต้นทางไม่พอ
     */
    private function buildGeneratedDescription(string $name, ?string $brand, array $variants, string $category): string
    {
        $html = "<p><strong>{$this->esc($name)}</strong></p>";

        $facts = [];
        if ($brand) {
            $facts[] = 'แบรนด์: '.$this->esc($brand);
        }
        if ($category !== '') {
            $facts[] = 'หมวดหมู่: '.$this->esc($category);
        }
        foreach ($variants as $v) {
            $facts[] = $this->esc($v['name']).': '.$this->esc(implode(', ', $v['values']));
        }

        if (! empty($facts)) {
            $html .= '<ul>';
            foreach ($facts as $f) {
                $html .= '<li>'.$f.'</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<p>✅ สินค้าคุณภาพ คัดสรรมาเพื่อคุณ<br>'
            .'✅ จัดส่งรวดเร็วทั่วประเทศ<br>'
            .'✅ รับประกันความพึงพอใจ เปลี่ยน/คืนได้ตามเงื่อนไข</p>';

        return $html;
    }

    /**
     * escape ข้อความสำหรับใส่ใน HTML ที่เราสร้างเอง
     */
    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * บีบค่าให้เป็น string อย่างปลอดภัย
     * (บางฟิลด์ของ Lazada เป็น array เช่น pdt_category → ต่อด้วย separator)
     */
    private function asString(mixed $value, string $glue = ' '): string
    {
        if (is_array($value)) {
            $flat = array_filter(array_map(
                fn ($v) => is_scalar($v) ? (string) $v : '',
                $value
            ));

            return trim(implode($glue, $flat));
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * ดึงรูปภาพทั้งหมด (ใช้แกลเลอรีของ SKU ตัวแรกที่มีรูป)
     *
     * @return array<int,string> รายการ URL รูปเต็ม (https)
     */
    private function extractImages(array $fields): array
    {
        $images = [];
        $galleries = data_get($fields, 'skuGalleries', []);

        if (is_array($galleries)) {
            // รอบแรก: เก็บเฉพาะ "รูปจริง" (type=img) จากแกลเลอรีชุดแรกที่มีรูป
            foreach ($galleries as $gallery) {
                if (! is_array($gallery)) {
                    continue;
                }
                foreach ($gallery as $item) {
                    if (($item['type'] ?? '') === 'img') {
                        $this->pushImage($images, $item['src'] ?? $item['poster'] ?? null);
                    }
                }
                if (! empty($images)) {
                    break;
                }
            }

            // รอบสอง: ถ้ายังว่าง (เช่นแกลเลอรีมีแต่วิดีโอ) ใช้ poster/รูปทุก item
            if (empty($images)) {
                foreach ($galleries as $gallery) {
                    if (! is_array($gallery)) {
                        continue;
                    }
                    foreach ($gallery as $item) {
                        $this->pushImage($images, $item['poster'] ?? $item['src'] ?? null);
                    }
                    if (! empty($images)) {
                        break;
                    }
                }
            }
        }

        // fallback สุดท้าย: รูปหลักจาก skuInfos
        if (empty($images)) {
            foreach ((array) data_get($fields, 'skuInfos', []) as $sku) {
                if (is_array($sku)) {
                    $this->pushImage($images, $sku['image'] ?? null);
                }
            }
        }

        return array_values(array_unique($images));
    }

    /**
     * เพิ่มรูปเข้า list หลัง normalize + ตรวจความปลอดภัย + ตัด suffix ปรับขนาด
     */
    private function pushImage(array &$images, ?string $url): void
    {
        if (! $url) {
            return;
        }

        $url = $this->normalizeImageUrl($url);
        if ($this->isSafeImageUrl($url)) {
            $images[] = $this->stripImageResize($url);
        }
    }

    /**
     * แปลง URL รูปให้สมบูรณ์ — Lazada บางสินค้าใช้ protocol-relative (//host/...)
     */
    private function normalizeImageUrl(string $url): string
    {
        $url = trim($url);

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return $url;
    }

    /**
     * ดึงตัวเลือกสินค้า (variants/options) เช่น สี / รุ่น / ขนาด
     *
     * @return array<int,array{name:string,values:array<int,string>}>
     */
    private function extractVariants(array $fields): array
    {
        $properties = data_get($fields, 'productOption.skuBase.properties', []);
        if (! is_array($properties)) {
            return [];
        }

        $out = [];
        foreach ($properties as $prop) {
            if (! is_array($prop)) {
                continue;
            }
            $name = trim((string) ($prop['name'] ?? ''));
            $values = [];
            foreach ((array) ($prop['values'] ?? []) as $val) {
                $vn = is_array($val) ? trim((string) ($val['name'] ?? '')) : trim((string) $val);
                if ($vn !== '') {
                    $values[] = $vn;
                }
            }
            if ($name !== '' && ! empty($values)) {
                $out[] = ['name' => $name, 'values' => array_values(array_unique($values))];
            }
        }

        return $out;
    }

    /**
     * แปลงข้อความราคา "฿18,000.00" → 18000.00
     */
    private function parsePrice(string $text): float
    {
        $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '', $text));

        return $clean === '' ? 0.0 : (float) $clean;
    }

    /**
     * ตัด suffix ปรับขนาดรูปของ Lazada ออก เพื่อให้ได้รูปความละเอียดเต็ม
     * เช่น ..._200x200q80.jpg → ...jpg
     */
    private function stripImageResize(string $url): string
    {
        $url = explode('?', $url)[0];

        // รูปแบบ .jpg_200x200q80.jpg / .png_720x720q80.png
        return preg_replace('/\.(jpg|jpeg|png|webp)_\d+x\d+q\d+\.(jpg|jpeg|png|webp)$/i', '.$1', $url);
    }

    /**
     * รูปต้องเป็น http(s) และมาจาก CDN ของ Lazada/Alibaba เท่านั้น
     */
    private function isSafeImageUrl(string $url): bool
    {
        if (! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (['lazcdn.com', 'slatic.net', 'lazada.co.th', 'alicdn.com'] as $allowed) {
            if (str_ends_with($host, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ดึง item id จาก URL (รูปแบบ -i<ตัวเลข>)
     */
    private function itemIdFromUrl(string $url): string
    {
        if (preg_match('/-i(\d+)/', $url, $m)) {
            return $m[1];
        }

        return md5($url);
    }

    /**
     * Sanitize รายละเอียด HTML จากภายนอก (กัน stored XSS)
     *
     * ขั้นตอน:
     *  1) ลบบล็อกอันตรายทั้งก้อน (script/style/iframe/object/embed/svg)
     *  2) ลบ event handler attribute ทั้งหมด (on*)
     *  3) ลบ protocol อันตราย (javascript:/vbscript:/data: ใน href/src)
     *  4) strip_tags เหลือเฉพาะแท็กที่อนุญาต
     *  5) จำกัดความยาว
     */
    private function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // 1) ลบบล็อกอันตราย (รวมเนื้อหาข้างใน)
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|noscript)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|noscript)\b[^>]*/?>#is', '', $html);

        // 2) ลบ event handler attribute เช่น onclick=, onerror=
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/i', '', $html);

        // 3) ลบ protocol อันตรายใน href/src
        $html = preg_replace('/(href|src)\s*=\s*"\s*(javascript|vbscript|data)\s*:[^"]*"/i', '$1="#"', $html);
        $html = preg_replace("/(href|src)\s*=\s*'\s*(javascript|vbscript|data)\s*:[^']*'/i", '$1="#"', $html);

        // 4) เหลือเฉพาะแท็กที่อนุญาต (หมายเหตุ: strip_tags ไม่ตัด attribute
        //    แต่เราได้ลบ on*/protocol อันตรายไปแล้วในขั้น 2-3)
        $html = strip_tags($html, self::ALLOWED_DESC_TAGS);

        // 5) จำกัดความยาว
        return mb_substr(trim($html), 0, 20000);
    }
}
