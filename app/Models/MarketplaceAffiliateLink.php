<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketplaceAffiliateLink extends Model
{
    use HasFactory;

    /**
     * แพลตฟอร์มเดียวที่ระบบ attribution รองรับตอนนี้
     *
     * ⚠️ Lazada ไม่ยอมรับ subId (ทดสอบแล้ว 6 พารามิเตอร์ได้ token เดียวกันหมด)
     *    การรู้ว่า "ใครกดลิงก์" จึงต้องมาจาก click log ของเราเองเท่านั้น
     */
    public const PLATFORM_SLUG = 'lazada';

    /**
     * แคช platform_id ของ lazada ต่อ 1 request (กันยิง query ซ้ำในลูป grid)
     *
     * @var int|null|false false = ยังไม่เคยค้น, null = ค้นแล้วไม่เจอ
     */
    protected static int|null|false $cachedPlatformId = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'platform_id',
        'short_code',
        'original_url',
        'affiliate_url',
        'tracking_url',
        'click_count',
        'unique_click_count',
        'conversion_count',
        'total_sales',
        'total_commission',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'custom_params',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'custom_params' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($link) {
            if (empty($link->short_code)) {
                $link->short_code = static::generateUniqueShortCode();
            }
        });
    }

    /**
     * Generate a unique short code
     */
    protected static function generateUniqueShortCode(): string
    {
        do {
            $code = Str::random(10);
        } while (static::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * หา (หรือสร้าง) ลิงก์ติดตามของ "ผู้ใช้คนนี้ + สินค้าชิ้นนี้"
     *
     * ใช้ตอนต้องการ short_code ทีละชิ้น (เช่นหน้ารายละเอียดสินค้า)
     * ⚠️ ห้ามเรียกในลูป grid — ให้ใช้ shortCodesForProducts() แทน (ดูเหตุผลที่นั่น)
     *
     * @param  \App\Models\User  $user  ผู้ใช้ที่กำลังแชร์/กดลิงก์
     * @param  \App\Models\Product  $product  สินค้า affiliate ในหน้าร้าน (ตาราง products)
     * @return static|null null = ผูก attribution ไม่ได้ ให้ผู้เรียก fallback ไปลิงก์ดิบ
     *
     * @example
     * $link = MarketplaceAffiliateLink::forUserAndProduct($user, $product);
     * $url  = $link ? route('affiliate.go', $link->short_code) : $product->affiliate_url;
     */
    public static function forUserAndProduct(User $user, Product $product): ?self
    {
        $codes = static::shortCodesForProducts($user, [$product]);

        if (! isset($codes[$product->id])) {
            return null;
        }

        return static::where('short_code', $codes[$product->id])->first();
    }

    /**
     * แปลงสินค้าหลายชิ้นเป็น short_code ทีเดียว (batched — ใช้กับ product grid)
     *
     * 🚀 PERFORMANCE: grid แสดง 30+ ชิ้น ถ้าเรียกทีละชิ้นจะได้ 60+ queries
     *    เมธอดนี้ใช้จำนวน query "คงที่" ไม่ขึ้นกับจำนวนสินค้า:
     *      - 1 query  หา platform_id ของ lazada (แคชไว้ทั้ง request → ครั้งเดียวเท่านั้น)
     *      - 1 query  LEFT JOIN marketplace_products × marketplace_affiliate_links
     *                 ได้ทั้ง mapping สินค้า + ลิงก์เดิมที่มีอยู่ ในนัดเดียว
     *      - อีก 2 query เฉพาะตอนมีสินค้าที่ "ยังไม่เคยมีลิงก์" (เช็ค short_code ชน + bulk insert)
     *    → สภาวะปกติ (ลิงก์ครบแล้ว) = 1 query ต่อการ render 1 หน้า
     *
     * ⚠️ marketplace_affiliate_links.product_id มี FK ไป marketplace_products (ไม่ใช่ products)
     *    จึงต้อง map products → marketplace_products ผ่าน (platform_id, external_product_id)
     *    สินค้าที่หาต้นทางไม่เจอ = ผูก attribution ไม่ได้ → ไม่ใส่ในผลลัพธ์ ให้ fallback ลิงก์ดิบ
     *
     * @param  \App\Models\User  $user  ผู้ใช้ที่ล็อกอินอยู่
     * @param  iterable  $products  รายการสินค้า (App\Models\Product)
     * @return array<int, string> map: products.id => short_code
     */
    public static function shortCodesForProducts(User $user, iterable $products): array
    {
        // ── คัดเฉพาะสินค้า affiliate ของ lazada ที่มีข้อมูลครบพอจะผูก attribution ──
        $eligible = [];
        foreach ($products as $product) {
            if (! $product instanceof Product) {
                continue;
            }

            $externalId = trim((string) ($product->external_product_id ?? ''));

            if (! $product->is_affiliate
                || empty($product->affiliate_url)
                || $externalId === ''
                || strtolower((string) ($product->external_platform ?? '')) !== static::PLATFORM_SLUG) {
                continue;
            }

            // เก็บเป็น "ลิสต์" ไม่ใช่ตัวเดียว — สินค้าในร้านหลายชิ้นอาจชี้ Lazada ตัวเดียวกันได้
            // (publisher จับคู่ด้วย store_id+external_product_id → คนละร้านซ้ำกันได้)
            // ทั้งหมดต้องใช้ short_code เดียวกัน เพราะ attribution ผูกที่ "สินค้าบน Lazada"
            $eligible[$externalId][] = $product;
        }

        if (empty($eligible)) {
            return [];
        }

        $platformId = static::lazadaPlatformId();

        if ($platformId === null) {
            return [];
        }

        try {
            return static::resolveShortCodes($user, $eligible, $platformId);
        } catch (\Throwable $e) {
            // ผูก attribution ไม่ได้ ≠ หน้าร้านต้องพัง — ปล่อยให้ fallback ไปลิงก์ดิบ
            Log::warning('marketplace: สร้างลิงก์ติดตาม affiliate ไม่สำเร็จ', [
                'user_id' => $user->id,
                'products' => count($eligible),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * เนื้องานจริงของ shortCodesForProducts (แยกออกมาเพื่อครอบ try/catch ได้สะอาด)
     *
     * @param  array<string, array<int, \App\Models\Product>>  $eligible  map: external_product_id => Product[]
     * @return array<int, string> map: products.id => short_code
     */
    protected static function resolveShortCodes(User $user, array $eligible, int $platformId): array
    {
        // ⚠️ ต้อง cast กลับเป็น string เสมอ — PHP แปลง array key ที่เป็นตัวเลขล้วนให้เป็น int อัตโนมัติ
        //    (array_keys ของคีย์ '3456789012' คืน int) พอส่งเข้า whereIn ตัว PDO จะ bind เป็น PARAM_INT
        //    ผลเสีย 2 อย่างกับคอลัมน์ external_product_id ที่เป็น VARCHAR(100):
        //      1) MySQL ต้องแปลงคอลัมน์เป็นตัวเลขก่อนเทียบ → index (platform_id, external_product_id)
        //         ใช้ได้แค่ prefix → สแกนสินค้า Lazada ทั้งก้อนทุกครั้งที่ลูกค้าเปิดหน้าร้าน
        //      2) การเทียบแบบตัวเลข "ไม่ใช่การเทียบสตริงแบบเป๊ะ" → '123 ', ' 123', '123x'
        //         ถือว่าเท่ากับ 123 หมด = ผูก short_code เข้ากับสินค้า Lazada ผิดตัว
        //         แล้วยอดคลิกจะไปเครดิตให้สินค้าคนละชิ้น
        $externalIds = array_map('strval', array_keys($eligible));

        // ── QUERY เดียว: หา marketplace_products + ลิงก์เดิมของ user คนนี้พร้อมกัน ──
        // ใช้ index (platform_id, external_product_id) บน marketplace_products
        // LEFT JOIN เพื่อให้ได้แถวสินค้าที่ "ยังไม่มีลิงก์" กลับมาด้วย (short_code = null)
        $rows = DB::table('marketplace_products as mp')
            ->leftJoin('marketplace_affiliate_links as mal', function ($join) use ($user) {
                $join->on('mal.product_id', '=', 'mp.id')
                    ->where('mal.user_id', '=', $user->id);
            })
            ->where('mp.platform_id', $platformId)
            ->whereIn('mp.external_product_id', $externalIds)
            ->orderBy('mp.id')
            ->orderBy('mal.id')
            ->get([
                'mp.id as mp_id',
                'mp.external_product_id',
                'mp.source_url',
                'mal.short_code',
                'mal.is_active',
                'mal.expires_at',
            ]);

        $codes = [];      // ผลลัพธ์: products.id => short_code
        $toCreate = [];   // สินค้า Lazada ที่ยังไม่มีลิงก์ รอ insert (คีย์ = external_product_id)
        $handled = [];    // external_product_id ที่ตัดสินใจไปแล้ว — กันแถวซ้ำเขียนทับ

        foreach ($rows as $row) {
            $externalId = (string) $row->external_product_id;
            $products = $eligible[$externalId] ?? null;

            if ($products === null) {
                continue;
            }

            // แถวซ้ำเกิดได้ 2 ทาง: marketplace_products ซ้ำ (account_id เป็น null ได้ →
            // unique_external_product ไม่กัน) หรือลิงก์ซ้ำจาก race condition
            // → ยึด "แถวแรก" ตาม order by mp.id, mal.id ให้ผลลัพธ์นิ่งทุกครั้ง
            if (isset($handled[$externalId])) {
                continue;
            }
            $handled[$externalId] = true;

            if ($row->short_code !== null) {
                // มีลิงก์อยู่แล้ว — ใช้ได้ต่อเมื่อยัง active และยังไม่หมดอายุ
                // strtotime พังคืน false → false <= time() เป็นจริง → ถือว่าหมดอายุ (ปลอดภัยไว้ก่อน)
                $expired = $row->expires_at !== null && strtotime((string) $row->expires_at) <= time();

                if ((int) $row->is_active === 1 && ! $expired) {
                    foreach ($products as $product) {
                        $codes[$product->id] = (string) $row->short_code;
                    }
                }

                // ปิด/หมดอายุแล้ว = แอดมินตั้งใจปิด → ห้ามปลุกคืน และห้ามสร้างซ้ำ
                continue;
            }

            $toCreate[$externalId] = [
                'mp_id' => (int) $row->mp_id,
                'products' => $products,
                'source_url' => $row->source_url,
            ];
        }

        if (empty($toCreate)) {
            return $codes;
        }

        return $codes + static::createMissingLinks($user, $toCreate, $platformId);
    }

    /**
     * สร้างลิงก์ที่ยังขาดแบบ bulk insert (1 query เช็คโค้ดชน + 1 query insert)
     *
     * @param  array<string, array{mp_id:int, products:array<int,\App\Models\Product>, source_url:?string}>  $toCreate
     * @return array<int, string> map: products.id => short_code
     */
    protected static function createMissingLinks(User $user, array $toCreate, int $platformId): array
    {
        // สุ่มโค้ดล่วงหน้าให้ครบจำนวน แล้วเช็คชนทีเดียว (แทนที่จะเช็คทีละตัวแบบ boot hook)
        $codes = static::generateUniqueShortCodes(count($toCreate));
        $now = now();

        $rowsToInsert = [];
        $result = [];       // products.id => short_code
        $codeToMpId = [];   // short_code => marketplace_products.id ที่ตั้งใจผูก

        foreach (array_values($toCreate) as $index => $item) {
            // 1 marketplace_product = 1 ลิงก์ = 1 โค้ด (ใช้ร่วมกันทุก product ที่ชี้มาที่เดียวกัน)
            $product = $item['products'][0];
            $code = $codes[$index];

            // original_url = หน้าสินค้าต้นทาง (ยังไม่ติด tracking) — ไม่มีก็ใช้หน้าสินค้าในร้านเรา
            $originalUrl = ! empty($item['source_url'])
                ? (string) $item['source_url']
                : url('/shop/'.($product->slug ?: $product->id));

            $rowsToInsert[] = [
                'user_id' => $user->id,
                'product_id' => $item['mp_id'],
                'platform_id' => $platformId,
                'short_code' => $code,
                'original_url' => $originalUrl,
                'affiliate_url' => (string) $product->affiliate_url,
                'tracking_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $codeToMpId[$code] = $item['mp_id'];
            foreach ($item['products'] as $p) {
                $result[$p->id] = $code;
            }
        }

        // insertOrIgnore: ถ้า short_code ดันชนกับของที่มีอยู่ (race กับอีก request)
        // แถวเราจะถูก "ข้าม" เงียบ ๆ — โค้ดนั้นจะกลายเป็นของคนอื่น
        DB::table('marketplace_affiliate_links')->insertOrIgnore($rowsToInsert);

        // 🔒 ยืนยันความเป็นเจ้าของ — ไม่ใช่แค่ "โค้ดมีอยู่จริง"
        //    ถ้าแจกโค้ดที่เป็นของ user อื่นออกไป ค่าคอมจะวิ่งเข้ากระเป๋าผิดคน (เสียเงินจริง)
        //    จึงต้องเช็คว่าแถวนั้น user_id + product_id ตรงกับที่เราตั้งใจสร้าง
        $verified = DB::table('marketplace_affiliate_links')
            ->where('user_id', $user->id)
            ->whereIn('short_code', array_values($result))
            ->pluck('product_id', 'short_code')
            ->all();

        return array_filter(
            $result,
            fn ($code) => isset($verified[$code])
                && (int) $verified[$code] === ($codeToMpId[$code] ?? -1)
        );
    }

    /**
     * สุ่ม short_code ที่ไม่ซ้ำกับในฐานข้อมูล จำนวน $count ตัว (เช็คชนด้วย query เดียว)
     *
     * @return array<int, string>
     */
    protected static function generateUniqueShortCodes(int $count): array
    {
        $codes = [];
        $attempts = 0;

        // ปกติวนรอบเดียวจบ — โอกาสชนของ Str::random(10) ต่ำมาก
        while (count($codes) < $count && $attempts < 5) {
            $attempts++;

            $candidates = [];
            for ($i = count($codes); $i < $count; $i++) {
                $candidates[] = Str::random(10);
            }
            $candidates = array_unique($candidates);

            $taken = static::whereIn('short_code', $candidates)->pluck('short_code')->all();
            $codes = array_merge($codes, array_values(array_diff($candidates, $taken)));
        }

        if (count($codes) < $count) {
            throw new \RuntimeException('สุ่ม short_code ไม่ซ้ำไม่สำเร็จ');
        }

        return array_slice($codes, 0, $count);
    }

    /**
     * platform_id ของ lazada (แคชไว้ต่อ request)
     */
    protected static function lazadaPlatformId(): ?int
    {
        if (static::$cachedPlatformId === false) {
            $id = DB::table('marketplace_platforms')
                ->where('slug', static::PLATFORM_SLUG)
                ->value('id');

            static::$cachedPlatformId = $id !== null ? (int) $id : null;
        }

        return static::$cachedPlatformId;
    }

    /**
     * Get the user that owns this link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product for this link
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    /**
     * Get the platform for this link
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    /**
     * Get all clicks for this link
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(MarketplaceLinkClick::class, 'affiliate_link_id');
    }

    /**
     * Get all orders generated from this link
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'affiliate_link_id');
    }

    /**
     * Get all commissions generated from this link
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(MarketplaceCommission::class, 'affiliate_link_id');
    }

    /**
     * Scope to get only active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get full tracking URL
     */
    public function getFullTrackingUrlAttribute(): string
    {
        // ⚠️ ต้องชี้ route จริงที่จดไว้ (/go/{code}) — เดิม hardcode /marketplace/redirect/ ซึ่งไม่มี route รองรับ
        //    ถ้ามีหน้า "คัดลอกลิงก์ของฉัน" มาใช้ค่านี้ ลูกค้าจะได้ลิงก์ 404 และคลิกไม่ถูกบันทึกเลย
        return route('affiliate.go', $this->short_code);
    }

    /**
     * Increment click count
     */
    public function incrementClicks(bool $isUnique = false)
    {
        $this->increment('click_count');

        if ($isUnique) {
            $this->increment('unique_click_count');
        }
    }

    /**
     * Increment conversion count
     */
    public function incrementConversions()
    {
        $this->increment('conversion_count');
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->click_count == 0) {
            return 0;
        }

        return ($this->conversion_count / $this->click_count) * 100;
    }
}
