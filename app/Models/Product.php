<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
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
        'requires_rental',
        'linked_bot_rental_id',
        'meta_title',
        'meta_description',
        'tags',
        'is_active',
        'is_featured',
        'is_public_approved',
        'public_approved_at',
        'public_approved_by',
        'published_at',
        'view_count',
        'sales_count',
        'rating_average',
        'rating_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
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
        'requires_rental' => 'boolean',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_public_approved' => 'boolean',
        'public_approved_at' => 'datetime',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'sales_count' => 'integer',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
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
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope: Only featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: In stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock')
                     ->where(function($q) {
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
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0 && $this->stock_status === 'in_stock';
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price) {
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
        if (!$this->track_inventory) {
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
        if (!$this->track_inventory) {
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
}
