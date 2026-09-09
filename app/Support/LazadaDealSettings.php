<?php

namespace App\Support;

use App\Models\MarketplaceSetting;
use Illuminate\Support\Facades\Schema;

/**
 * ⚡ ค่าตั้งของ Flash Deals — **แหล่งเดียว** ที่ทุกฝั่งต้องอ่านผ่าน
 *
 * ลำดับความสำคัญ: ค่าที่แอดมินตั้งใน `marketplace_settings` ➜ ทับ ➜ ค่าปริยายใน `config/lazada-deals.php`
 *
 * 🚨 ทำไมต้องมีคลาสนี้ ไม่ให้เรียก config() ตรง ๆ กระจายกัน
 *    ตัวเลขชุดนี้ถูกใช้ 3 ที่ที่ต้องตรงกันเสมอ:
 *      1. `lazada:scan-deals`                    — ตอนคัดของเข้า
 *      2. `StorefrontController::getFlashDeals()` — ตอนเลือกของขึ้นแถบหน้าแรก
 *      3. ตัวกรอง `?deals=1`                      — หน้า "ดู Flash Deals ทั้งหมด"
 *    ถ้าใครสักที่อ่าน config ตรง ๆ แอดมินแก้ในหลังบ้านแล้วจะมีผลแค่บางหน้า
 *    = ของที่โชว์บนแถบกับของในหน้ารวมเป็นคนละชุด โดยไม่มีอะไรฟ้อง
 *
 * ⚠️ อ่านค่าจาก DB ทุกครั้งที่เรียก — จึงต้อง memo ไว้ในตัวแปร static
 *    (หน้าแรกเรียก fresh_hours 2 ครั้งต่อ request)
 *
 * 🔒 ทุก getter คืนค่าที่ผ่านการ clamp แล้ว — แอดมินกรอกเลขเพี้ยน/ค่าเก่าใน DB เสียหาย
 *    ต้องไม่ทำให้หน้าแรกพังหรือทำให้ตัวกวาดยิง Lazada รัวไม่จำกัด
 */
class LazadaDealSettings
{
    /** คีย์นำหน้าใน marketplace_settings — ทุกคีย์ของหน้านี้ขึ้นต้นด้วยตัวนี้ */
    public const PREFIX = 'lazada_deals_';

    /** สวิตช์ใหญ่: cron จะกวาดหรือไม่ */
    public const KEY_ENABLED = self::PREFIX.'enabled';

    /** คำค้น (JSON array ของ {keyword, category}) */
    public const KEY_SEEDS = self::PREFIX.'seeds';

    /** สรุปผลการรันครั้งล่าสุด (JSON) — ไว้โชว์ในหลังบ้าน */
    public const KEY_LAST_RUN = self::PREFIX.'last_run';

    /** @var array<string,mixed>|null memo ต่อ 1 request */
    private static ?array $cache = null;

    /**
     * ล้าง memo — เรียกหลังแอดมินกดบันทึก (ค่าใหม่ต้องมีผลทันทีในคำขอเดียวกัน)
     */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /**
     * เกณฑ์คัดของ (ผ่านการ clamp แล้ว)
     *
     * @return array{min_discount_percent:int,max_discount_percent:int,min_commission_percent:float,min_price:float,max_price:float,min_rating:float,min_reviews:int,min_sold:int}
     */
    public static function filters(): array
    {
        $defaults = (array) config('lazada-deals.filters', []);

        $minDiscount = self::clampInt('min_discount_percent', $defaults['min_discount_percent'] ?? 25, 1, 95);
        $maxDiscount = self::clampInt('max_discount_percent', $defaults['max_discount_percent'] ?? 80, 1, 99);

        // ⚠️ ถ้าแอดมินตั้งขั้นต่ำสูงกว่าเพดาน จะไม่มีของชิ้นไหนผ่านเลยและหน้าแรกจะว่างเงียบ ๆ
        //    สลับให้อัตโนมัติแทนการปล่อยผ่าน (ค่าใน DB ไม่ถูกแก้ — แค่ตีความให้ใช้งานได้)
        if ($minDiscount > $maxDiscount) {
            [$minDiscount, $maxDiscount] = [$maxDiscount, $minDiscount];
        }

        $minPrice = self::clampFloat('min_price', $defaults['min_price'] ?? 49, 0, 1000000);
        $maxPrice = self::clampFloat('max_price', $defaults['max_price'] ?? 50000, 1, 1000000);
        if ($minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'min_discount_percent' => $minDiscount,
            'max_discount_percent' => $maxDiscount,
            'min_commission_percent' => self::clampFloat('min_commission_percent', $defaults['min_commission_percent'] ?? 3, 0, 100),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_rating' => self::clampFloat('min_rating', $defaults['min_rating'] ?? 4.0, 0, 5),
            'min_reviews' => self::clampInt('min_reviews', $defaults['min_reviews'] ?? 20, 0, 100000),
            'min_sold' => self::clampInt('min_sold', $defaults['min_sold'] ?? 100, 0, 1000000),
        ];
    }

    /**
     * เพดานการยิง (ผ่านการ clamp แล้ว)
     *
     * 🔒 เพดานบนของแต่ละตัวคือ "กันแอดมินยิง Lazada รัวจนโดนบล็อก" ไม่ใช่ค่าที่ปรับได้ตามใจ
     *    หน่วงเวลา (sleep) จงใจไม่เปิดให้แก้จากหลังบ้าน — ปรับผิดทีเดียวโดนกันบอททั้งเซิร์ฟเวอร์
     *
     * @return array{pages_per_keyword:int,publish_limit:int,max_per_seed:int,link_budget:int,listing_sleep_us:int,link_sleep_us:int}
     */
    public static function limits(): array
    {
        $defaults = (array) config('lazada-deals.limits', []);

        return [
            'pages_per_keyword' => self::clampInt('pages_per_keyword', $defaults['pages_per_keyword'] ?? 1, 1, 5),
            'publish_limit' => self::clampInt('publish_limit', $defaults['publish_limit'] ?? 24, 1, 60),
            'max_per_seed' => self::clampInt('max_per_seed', $defaults['max_per_seed'] ?? 2, 1, 20),
            'link_budget' => self::clampInt('link_budget', $defaults['link_budget'] ?? 40, 1, 120),
            // ไม่เปิดให้แก้จากหลังบ้าน — อ่านจาก config อย่างเดียว
            'listing_sleep_us' => (int) ($defaults['listing_sleep_us'] ?? 500000),
            'link_sleep_us' => (int) ($defaults['link_sleep_us'] ?? 200000),
        ];
    }

    /**
     * ดีลถือว่า "ยังสด" กี่ชั่วโมงหลังยืนยันครั้งล่าสุด
     */
    public static function freshHours(): int
    {
        return self::clampInt('fresh_hours', config('lazada-deals.fresh_hours', 8), 1, 168);
    }

    /**
     * รอบตรวจราคาซ้ำ (ชั่วโมง) — ใช้ทำตัวนับถอยหลังบนแถบดีล
     *
     * ⚠️ ค่านี้ **ไม่ได้เปลี่ยนตาราง cron จริง** (cron ตั้งไว้ที่ routes/console.php ทุก 3 ชม.)
     *    เป็นแค่ตัวบอกลูกค้าว่าจะอัปเดตอีกทีเมื่อไหร่ ⇒ ตั้งไม่ตรงกัน = ตัวเลขบนหน้าเว็บโกหก
     */
    public static function rescanHours(): int
    {
        return self::clampInt('rescan_hours', config('lazada-deals.rescan_hours', 3), 1, 24);
    }

    /**
     * cron เปิดอยู่ไหม (ค่าปริยาย = เปิด)
     */
    public static function enabled(): bool
    {
        $raw = self::raw(self::KEY_ENABLED);

        return $raw === null ? true : filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * คำค้นที่จะกวาด — แอดมินแก้ได้ ถ้ายังไม่เคยตั้งจะใช้ชุดปริยายใน config
     *
     * @return array<int,array{keyword:string,category:?string}>
     */
    public static function seeds(): array
    {
        $stored = self::raw(self::KEY_SEEDS);
        $rows = is_string($stored) && $stored !== '' ? json_decode($stored, true) : null;

        if (! is_array($rows) || empty($rows)) {
            $rows = (array) config('lazada-deals.seeds', []);
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $keyword = trim((string) ($row['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }
            $category = trim((string) ($row['category'] ?? ''));
            $out[] = ['keyword' => mb_substr($keyword, 0, 120), 'category' => $category !== '' ? $category : null];
        }

        return $out;
    }

    /**
     * สรุปผลการรันครั้งล่าสุด (ไว้โชว์ในหลังบ้าน)
     *
     * @return array<string,mixed>|null
     */
    public static function lastRun(): ?array
    {
        $raw = self::raw(self::KEY_LAST_RUN);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * บันทึกสรุปผลการรัน
     *
     * @param  array<string,mixed>  $summary
     */
    public static function recordRun(array $summary): void
    {
        MarketplaceSetting::set(
            self::KEY_LAST_RUN,
            json_encode($summary, JSON_UNESCAPED_UNICODE),
            'json',
            'สรุปผลการกวาดดีล Lazada ครั้งล่าสุด'
        );
        self::flush();
    }

    /**
     * ค่าดิบจาก marketplace_settings (null = ยังไม่เคยตั้ง → ให้ผู้เรียกถอยไปใช้ config)
     */
    private static function raw(string $fullKey): ?string
    {
        if (self::$cache === null) {
            self::$cache = [];

            // ⚠️ ตารางอาจยังไม่มีตอนติดตั้งใหม่/ตอนรัน migrate ครั้งแรก — ห้ามให้หน้าแรกล้ม
            try {
                if (Schema::hasTable('marketplace_settings')) {
                    self::$cache = MarketplaceSetting::query()
                        ->where('setting_key', 'like', self::PREFIX.'%')
                        ->pluck('setting_value', 'setting_key')
                        ->all();
                }
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }

        $value = self::$cache[$fullKey] ?? null;

        return ($value === null || $value === '') ? null : (string) $value;
    }

    private static function clampInt(string $shortKey, mixed $default, int $min, int $max): int
    {
        $raw = self::raw(self::PREFIX.$shortKey);
        $value = $raw !== null && is_numeric($raw) ? (int) $raw : (int) $default;

        return max($min, min($max, $value));
    }

    private static function clampFloat(string $shortKey, mixed $default, float $min, float $max): float
    {
        $raw = self::raw(self::PREFIX.$shortKey);
        $value = $raw !== null && is_numeric($raw) ? (float) $raw : (float) $default;

        return max($min, min($max, $value));
    }
}
