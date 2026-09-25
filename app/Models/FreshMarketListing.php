<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FreshMarketListing - รายการสินค้าในตลาดสด
 *
 * @property int $id
 * @property int $seller_id
 * @property int|null $category_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property float $price
 * @property float|null $compare_at_price
 * @property int $quantity_available
 * @property string $unit
 * @property array|null $images
 * @property string|null $main_image_url
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float $delivery_radius_km
 * @property string|null $available_from
 * @property string|null $available_until
 * @property bool $is_available
 * @property bool $is_featured
 * @property bool $is_organic
 * @property array|null $tags
 * @property string $freshness_level
 * @property float $pv_value
 * @property float $cashback_amount
 * @property float $cashback_percentage
 * @property float $commission_rate
 * @property int $view_count
 * @property int $order_count
 * @property string $status
 * @property string $created_via
 */
class FreshMarketListing extends Model
{
    use SoftDeletes;

    protected $table = 'fresh_market_listings';

    protected $fillable = [
        'seller_id',
        'category_id',
        'slug',
        'title',
        'description',
        'price',
        'compare_at_price',
        'quantity_available',
        'unit',
        'images',
        'main_image_url',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'available_from',
        'available_until',
        'is_available',
        'is_featured',
        'is_organic',
        'tags',
        'freshness_level',
        'pv_value',
        'cashback_amount',
        'cashback_percentage',
        'commission_rate',
        'view_count',
        'order_count',
        'status',
        'created_via',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'delivery_radius_km' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_organic' => 'boolean',
        'pv_value' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'cashback_percentage' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'quantity_available' => 'integer',
        'view_count' => 'integer',
        'order_count' => 'integer',
    ];

    /**
     * สร้าง slug อัตโนมัติ + จัดสถานะตามสต็อก
     */
    protected static function booted(): void
    {
        static::creating(function (self $listing) {
            if (empty($listing->slug)) {
                $base = Str::slug($listing->title) ?: Str::random(8);
                $listing->slug = $base.'-'.Str::random(5);
            }
        });

        // ทุกทางที่แก้จำนวนสต็อก (เว็บ, API, LINE, แอดมิน) ผ่านจุดนี้:
        // เติมสต็อกแล้ว → sold_out กลับเป็น active / สต็อกหมด → sold_out
        static::saving(function (self $listing) {
            if (! $listing->isDirty('quantity_available')) {
                return;
            }

            $qty = (int) $listing->quantity_available;

            if ($qty > 0 && $listing->status === 'sold_out') {
                $listing->status = 'active';
                $listing->is_available = true;
            } elseif ($qty <= 0 && $listing->status === 'active') {
                $listing->quantity_available = 0;
                $listing->status = 'sold_out';
                $listing->is_available = false;
            }
        });
    }

    // ===== Relationships =====

    /**
     * ผู้ขาย
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'seller_id');
    }

    /**
     * หมวดหมู่
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FreshMarketCategory::class, 'category_id');
    }

    /**
     * ออเดอร์ที่สั่งสินค้านี้
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FreshMarketOrder::class, 'listing_id');
    }

    // ===== Scopes =====

    /**
     * Scope: สินค้าที่เปิดขายอยู่
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_available', true);
    }

    /**
     * Scope: สินค้ายังมีของ
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    /**
     * Scope: สินค้าที่ผู้ซื้อเห็นได้ (หน้าร้าน, ค้นหา, API, LINE)
     *
     * - สินค้าเปิดขาย (active + is_available)
     * - ร้านเปิดอยู่ ไม่ถูกระงับ
     * - ถ้าปิดอนุมัติร้านอัตโนมัติ (Setting fresh_market.auto_approve_sellers = false)
     *   ต้องเป็นร้านที่แอดมินยืนยันแล้วเท่านั้น
     */
    public function scopeVisibleToBuyers($query)
    {
        $requireVerified = ! static::autoApproveSellers();

        return $query->active()->whereHas('seller', function ($seller) use ($requireVerified) {
            $seller->where('is_active', true)->where('is_suspended', false);

            if ($requireVerified) {
                $seller->where('is_verified', true);
            }
        });
    }

    /**
     * อนุมัติร้านอัตโนมัติหรือไม่ (ค่าเริ่มต้น: เปิด)
     */
    public static function autoApproveSellers(): bool
    {
        return (bool) Setting::get('fresh_market.auto_approve_sellers', true);
    }

    /**
     * Scope: ค้นหาสินค้าใกล้พิกัด (Haversine formula)
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        return $query->selectRaw('fresh_market_listings.*, (
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance_km', [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }

    /**
     * Scope: ค้นหาตามคำค้น
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'LIKE', "%{$keyword}%")
                ->orWhere('description', 'LIKE', "%{$keyword}%");
        });
    }

    /**
     * Scope: กรองตามหมวดหมู่ (รับ id หรือ slug, รวมหมวดหมู่ลูก)
     * ไม่พบหมวดหมู่ = ผลลัพธ์ว่าง (ไม่ใช่ TypeError แบบเดิม)
     */
    public function scopeInCategory($query, int|string $category)
    {
        $ids = FreshMarketCategory::resolveIds($category);

        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('category_id', $ids);
    }

    /**
     * Scope: กรองตามช่วงราคา
     */
    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    // ===== Accessors =====

    /**
     * รูปภาพหลัก
     */
    public function getPrimaryImageAttribute(): ?string
    {
        if ($this->main_image_url) {
            return $this->main_image_url;
        }

        $images = $this->images;

        return $images[0] ?? null;
    }

    /**
     * ชื่อเดิมที่หน้าเว็บเก่าเรียก (image_url) → รูปหลัก
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->primary_image;
    }

    /**
     * ชื่อเดิมที่หน้าแอดมินเก่าเรียก (original_price) → ราคาก่อนลด
     */
    public function getOriginalPriceAttribute(): ?float
    {
        return $this->compare_at_price !== null ? (float) $this->compare_at_price : null;
    }

    /**
     * ชื่อเดิมที่หน้าเว็บเก่าเรียก (cashback_percent) → เปอร์เซ็นต์แคชแบ็ค (0 = ไม่มี → null ซ่อนป้าย)
     */
    public function getCashbackPercentAttribute(): ?float
    {
        $percent = (float) $this->cashback_percentage;

        return $percent > 0 ? $percent : null;
    }

    /**
     * เปอร์เซ็นต์ส่วนลด
     */
    public function getDiscountPercentageAttribute(): float
    {
        if (! $this->compare_at_price || $this->compare_at_price <= $this->price) {
            return 0;
        }

        return round(($this->compare_at_price - $this->price) / $this->compare_at_price * 100, 1);
    }

    // ===== Methods =====

    /**
     * เพิ่มจำนวนวิว
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * ตรวจสอบว่าสินค้ายังพร้อมขาย
     */
    public function isAvailableForPurchase(): bool
    {
        return $this->status === 'active'
            && $this->is_available
            && $this->quantity_available > 0;
    }

    /**
     * ร้านของสินค้านี้เปิดให้ผู้ซื้อเห็นอยู่หรือไม่ (เปิด, ไม่ถูกระงับ, ยืนยันแล้วถ้าบังคับ)
     */
    public function sellerIsVisible(): bool
    {
        $seller = $this->seller;

        if (! $seller || ! $seller->is_active || $seller->is_suspended) {
            return false;
        }

        return static::autoApproveSellers() || (bool) $seller->is_verified;
    }

    /**
     * จองสต็อกแบบ atomic (ต้องเรียกใน transaction)
     *
     * ลดจำนวนเฉพาะเมื่อยังมีพอ (conditional update) — สองคนสั่งชิ้นสุดท้ายพร้อมกัน
     * จะสำเร็จแค่คนเดียว ไม่มีทางติดลบ
     *
     * @return bool true = จองสำเร็จ
     */
    public static function reserveStock(int $listingId, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        $affected = static::whereKey($listingId)
            ->where('quantity_available', '>=', $quantity)
            ->update([
                'quantity_available' => DB::raw('quantity_available - '.(int) $quantity),
                'order_count' => DB::raw('order_count + 1'),
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            return false;
        }

        // ของหมดพอดี → ปิดการขายอัตโนมัติ
        static::whereKey($listingId)
            ->where('quantity_available', '<=', 0)
            ->where('status', 'active')
            ->update(['status' => 'sold_out', 'is_available' => false]);

        return true;
    }

    /**
     * คืนสต็อก (ยกเลิกออเดอร์ก่อนส่งมอบ) — sold_out ที่กลับมามีของจะเปิดขายอีกครั้ง
     */
    public static function releaseStock(int $listingId, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        static::withTrashed()->whereKey($listingId)->update([
            'quantity_available' => DB::raw('quantity_available + '.(int) $quantity),
            'order_count' => DB::raw('CASE WHEN order_count > 0 THEN order_count - 1 ELSE 0 END'),
            'updated_at' => now(),
        ]);

        static::whereKey($listingId)
            ->where('status', 'sold_out')
            ->where('quantity_available', '>', 0)
            ->update(['status' => 'active', 'is_available' => true]);
    }

    /**
     * ลดจำนวนสินค้า (คงไว้เพื่อความเข้ากันได้ — ใช้ reserveStock แทน)
     *
     * @deprecated ใช้ FreshMarketListing::reserveStock() ภายใน transaction
     */
    public function decrementStock(int $quantity = 1): void
    {
        if (! static::reserveStock((int) $this->id, $quantity)) {
            throw new \App\Exceptions\FreshMarketException('สินค้าไม่เพียงพอ', 'OUT_OF_STOCK', 409);
        }

        $this->refresh();
    }
}
