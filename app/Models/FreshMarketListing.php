<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
     * สร้าง slug อัตโนมัติ
     */
    protected static function booted(): void
    {
        static::creating(function (self $listing) {
            if (empty($listing->slug)) {
                $base = Str::slug($listing->title) ?: Str::random(8);
                $listing->slug = $base . '-' . Str::random(5);
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
     * Scope: ค้นหาสินค้าใกล้พิกัด (Haversine formula)
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        return $query->selectRaw("fresh_market_listings.*, (
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance_km", [$lat, $lng, $lat])
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
     * Scope: กรองตามหมวดหมู่
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
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
     * ลดจำนวนสินค้า
     */
    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('quantity_available', $quantity);
        $this->increment('order_count');

        if ($this->quantity_available <= 0) {
            $this->update(['status' => 'sold_out', 'is_available' => false]);
        }
    }
}
