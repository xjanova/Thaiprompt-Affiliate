<?php

namespace App\Models;

use App\Models\Concerns\CalculatesEarnings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use CalculatesEarnings, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'short_description',
        'price',
        'price_coins',
        'allow_coin_purchase',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'track_inventory',
        'stock_status',
        'brand',
        'weight',
        'dimensions',
        'main_image_url',
        'image_urls',
        'attributes',
        'has_variants',
        'parent_product_id',
        'commission_rate',
        'customer_cashback',
        'cashback_percentage',
        'vat_percentage',
        'pv_value',
        'platform_fee_percentage',
        'requires_rental',
        'linked_bot_rental_id',
        'meta_title',
        'meta_description',
        'tags',
        'is_active',
        'is_featured',
        'is_hidden',
        'is_virtual',
        // Affiliate (สินค้าจากแพลตฟอร์มนอก — ปุ่มซื้อวิ่งไปลิงก์ค่าคอม)
        'is_affiliate',
        'affiliate_url',
        'external_platform',
        'external_product_id',
        'shipping_speed',
        // Shipping fields
        'shipping_method',
        'shipping_fee',
        'shipping_weight_kg',
        'free_shipping_min_amount',
        'is_public_approved',
        'public_approved_at',
        'public_approved_by',
        'published_at',
        'view_count',
        'sales_count',
        'rating_average',
        'rating_count',
        // Block fields
        'is_blocked',
        'blocked_at',
        'blocked_by',
        'block_reason',
        'unblocked_at',
        'unblocked_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_coins' => 'decimal:2',
        'allow_coin_purchase' => 'boolean',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'track_inventory' => 'boolean',
        'weight' => 'decimal:2',
        'image_urls' => 'array',
        'attributes' => 'array',
        'has_variants' => 'boolean',
        'commission_rate' => 'decimal:2',
        'customer_cashback' => 'decimal:2',
        'cashback_percentage' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'pv_value' => 'decimal:2',
        'platform_fee_percentage' => 'decimal:2',
        'requires_rental' => 'boolean',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_hidden' => 'boolean',
        'is_virtual' => 'boolean',
        'is_affiliate' => 'boolean',
        // Shipping casts
        'shipping_fee' => 'decimal:2',
        'shipping_weight_kg' => 'decimal:3',
        'free_shipping_min_amount' => 'decimal:2',
        'is_public_approved' => 'boolean',
        'public_approved_at' => 'datetime',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'sales_count' => 'integer',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        // Block casts
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    /**
     * Accessors to append to model's array/JSON form
     */
    protected $appends = [
        'primary_image',
        'primary_image_url',
    ];

    /**
     * Accessor: แปลง main_image_url เป็น URL ที่ใช้งานได้
     *
     * รองรับทั้ง full URL (https://...) และ relative path (products/xxx.webp)
     * แก้ปัญหา: ภาพไม่แสดงในหน้า shop/show เพราะ relative path ถูก resolve
     * ผิดเป็น /shop/products/xxx.webp แทน /storage/products/xxx.webp
     *
     * @param string|null $value ค่า raw จาก database
     * @return string|null URL ที่ใช้งานได้
     */
    public function getMainImageUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // เป็น full URL อยู่แล้ว (https://... หรือ http://...)
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // เป็น relative path → แปลงด้วย Storage::url()
        return Storage::url($value);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $slug = Str::slug($product->name);
                // Str::slug ไม่รองรับภาษาไทย — ถ้าผลลัพธ์ว่าง ใช้ชื่อสินค้าแปลงเป็น URL-safe แทน
                if (empty($slug)) {
                    $slug = preg_replace('/\s+/', '-', trim($product->name));
                    $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug);
                    $slug = mb_strtolower($slug);
                }
                // ถ้ายังว่างอยู่ ใช้ random string
                if (empty($slug)) {
                    $slug = 'product-' . Str::random(8);
                }
                $product->slug = $slug;
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-'.strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the seller of this product
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * ร้านค้าเจ้าของสินค้า
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the parent product (for variants)
     */
    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    /**
     * Get all variants of this product
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_product_id');
    }

    /**
     * Get all images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * Get all reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    /**
     * Get approved reviews
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get order items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    /**
     * Get linked bot rental
     */
    public function linkedBotRental(): BelongsTo
    {
        return $this->belongsTo(AiBotRental::class, 'linked_bot_rental_id');
    }

    /**
     * ผู้ที่บล็อกสินค้า (Admin)
     */
    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * ผู้ที่ปลดบล็อกสินค้า (Admin)
     */
    public function unblockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * รายการใน Official Shop
     */
    public function officialShopProducts(): HasMany
    {
        return $this->hasMany(OfficialShopProduct::class, 'product_id');
    }

    /**
     * โปรโมทสินค้าใหม่
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function newProductPromotion()
    {
        return $this->hasOne(NewProductPromotion::class, 'product_id');
    }

    /**
     * สินค้าขายดีรายเดือน
     */
    public function bestSellerRecords(): HasMany
    {
        return $this->hasMany(BestSellerMonthly::class, 'product_id');
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: สินค้าที่ไม่ถูกบล็อก
     */
    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    /**
     * Scope: สินค้าที่ถูกบล็อก
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope: Only featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: สินค้าที่ไม่ถูกซ่อน (แสดงในหน้าเว็บ)
     *
     * ใช้สำหรับ: storefront, marketplace, search results
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope: สินค้าที่ถูกซ่อน
     *
     * ใช้สำหรับ: admin panel filter
     */
    public function scopeHidden($query)
    {
        return $query->where('is_hidden', true);
    }

    /**
     * Scope: สินค้าที่พร้อมแสดงในหน้าเว็บ (active + visible + not blocked)
     *
     * ใช้สำหรับ: public product listings
     */
    public function scopePublicVisible($query)
    {
        return $query->active()
            ->visible()
            ->notBlocked();
    }

    /**
     * Scope: In stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock')
            ->where(function ($q) {
                $q->where('track_inventory', false)
                    ->orWhere('stock_quantity', '>', 0);
            });
    }

    /**
     * Scope: Low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope: Out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
            ->where('stock_quantity', '<=', 0);
    }

    /**
     * Scope: สินค้าของ Official Shop
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfficialShop($query)
    {
        $officialSellerId = self::getOfficialSellerId();

        return $query->where('seller_id', $officialSellerId);
    }

    /**
     * Scope: สินค้าของ Seller ทั่วไป (ไม่ใช่ Official Shop)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotOfficialShop($query)
    {
        $officialSellerId = self::getOfficialSellerId();

        return $query->where('seller_id', '!=', $officialSellerId);
    }

    /**
     * ดึง Official Shop Seller ID
     */
    public static function getOfficialSellerId(): int
    {
        static $officialSellerId = null;

        if ($officialSellerId === null) {
            $email = config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com');
            $seller = User::where('email', $email)->first();

            if ($seller) {
                $officialSellerId = $seller->id;
            } else {
                // ถ้าไม่พบ ให้สร้างใหม่
                $seller = User::create([
                    'name' => config('shop.official_shop.name', 'Official Shop'),
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                    'role' => 'seller',
                    'email_verified_at' => now(),
                ]);
                $officialSellerId = $seller->id;
            }
        }

        return $officialSellerId;
    }

    /**
     * ตรวจสอบว่าสินค้านี้เป็นของ Official Shop หรือไม่
     */
    public function isOfficialShopProduct(): bool
    {
        return $this->seller_id === self::getOfficialSellerId();
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0 && $this->stock_status === 'in_stock';
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        if (! $this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (! $this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->cost_price) {
            return null;
        }

        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    /**
     * Calculate commission amount
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Calculate seller earning after commission
     */
    public function calculateSellerEarning(float $amount): float
    {
        return $amount - $this->calculateCommission($amount);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Update rating
     */
    public function updateRating(): void
    {
        $reviews = $this->approvedReviews;
        $this->rating_count = $reviews->count();
        $this->rating_average = $reviews->count() > 0 ? $reviews->avg('rating') : 0;
        $this->save();
    }

    /**
     * Decrease stock
     */
    public function decreaseStock(int $quantity): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);

        // Update stock status
        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
            $this->save();
        }

        return true;
    }

    /**
     * Increase stock
     */
    public function increaseStock(int $quantity): void
    {
        if (! $this->track_inventory) {
            return;
        }

        $this->increment('stock_quantity', $quantity);

        // Update stock status
        if ($this->stock_quantity > 0 && $this->stock_status === 'out_of_stock') {
            $this->stock_status = 'in_stock';
            $this->save();
        }
    }

    /**
     * ดึง tags ในรูปแบบ array เสมอ
     *
     * แก้ไขปัญหากรณีที่ข้อมูลใน database เป็น string แทน JSON array
     * รวมถึง double-encoded JSON และ unicode escape sequences
     */
    public function getTagsAttribute($value): array
    {
        // ถ้าเป็น null หรือ empty ให้คืนค่า array ว่าง
        if (empty($value)) {
            return [];
        }

        // ถ้าเป็น array แล้ว ให้ตรวจสอบว่า elements เป็น string ปกติหรือไม่
        if (is_array($value)) {
            return $this->cleanTagsArray($value);
        }

        // ถ้าเป็น string ลอง decode เป็น JSON
        if (is_string($value)) {
            // ลอง decode หลายรอบเพื่อแก้ double-encoded JSON
            $decoded = $value;
            $maxAttempts = 3;

            for ($i = 0; $i < $maxAttempts; $i++) {
                $temp = json_decode($decoded, true);
                if ($temp === null || ! is_array($temp)) {
                    break;
                }
                $decoded = $temp;

                // ถ้า decode แล้วได้ array ของ strings ที่ถูกต้อง ให้หยุด
                if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0]) && ! str_starts_with($decoded[0], '[')) {
                    break;
                }
            }

            if (is_array($decoded)) {
                return $this->cleanTagsArray($decoded);
            }

            // ถ้าไม่ใช่ JSON ให้แยกด้วย comma
            return array_filter(array_map('trim', explode(',', $value)));
        }

        return [];
    }

    /**
     * ทำความสะอาด tags array
     * แก้ปัญหา unicode escape sequences และ double-quoted strings
     */
    protected function cleanTagsArray(array $tags): array
    {
        $cleaned = [];
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                // ถ้ายังเป็น JSON string อยู่ ให้ decode อีกครั้ง
                if (str_starts_with($tag, '"') || str_starts_with($tag, '[')) {
                    $decoded = json_decode($tag, true);
                    if (is_string($decoded)) {
                        $tag = $decoded;
                    } elseif (is_array($decoded)) {
                        // recursive clean ถ้าเป็น array
                        $cleaned = array_merge($cleaned, $this->cleanTagsArray($decoded));

                        continue;
                    }
                }

                // Decode unicode escape sequences เช่น \u0e1a เป็น ภาษาไทย
                $tag = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                }, $tag);

                $tag = trim($tag);
                if (! empty($tag)) {
                    $cleaned[] = $tag;
                }
            } elseif (is_array($tag)) {
                $cleaned = array_merge($cleaned, $this->cleanTagsArray($tag));
            }
        }

        return array_unique($cleaned);
    }

    /**
     * Get primary image URL (main_image_url or first image)
     *
     * รองรับทั้ง relative path และ full URL
     */
    public function getPrimaryImageAttribute(): ?string
    {
        // If main_image_url exists, use it
        if ($this->main_image_url) {
            // ตรวจสอบว่าเป็น full URL หรือไม่
            if (filter_var($this->main_image_url, FILTER_VALIDATE_URL)) {
                // เป็น full URL อยู่แล้ว (https://...)
                return $this->main_image_url;
            }

            // เป็น relative path (products/xxx.webp)
            return Storage::url($this->main_image_url);
        }

        // Otherwise, use first image from images relationship
        // Use eager loaded relationship to avoid N+1 query
        $firstImage = $this->images->first();
        if ($firstImage && $firstImage->image_url) {
            // ตรวจสอบว่าเป็น full URL หรือไม่
            if (filter_var($firstImage->image_url, FILTER_VALIDATE_URL)) {
                return $firstImage->image_url;
            }

            return Storage::url($firstImage->image_url);
        }

        return null;
    }

    /**
     * Alias for getPrimaryImageAttribute
     * หลาย view ใช้ชื่อ primary_image_url
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->primary_image;
    }

    /**
     * MLM Relationships
     */

    /**
     * Get MLM PV configurations
     */
    public function mlmProductPv(): HasMany
    {
        return $this->hasMany(\App\Models\MlmProductPv::class);
    }

    /**
     * Get MLM PV for a specific plan
     */
    public function getMlmPv($planId)
    {
        return $this->mlmProductPv()
            ->where('mlm_plan_id', $planId)
            ->first();
    }

    /**
     * ดึง barcode (ใช้ SKU ถ้า barcode ไม่มี)
     *
     * Accessor สำหรับ backward compatibility กับระบบเก่า
     * ที่อาจยังไม่มี barcode field
     */
    public function getBarcodeOrSkuAttribute(): ?string
    {
        return $this->barcode ?? $this->sku;
    }
}
