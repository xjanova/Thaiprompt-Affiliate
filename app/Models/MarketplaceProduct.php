<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'platform_id',
        'external_product_id',
        'external_sku',
        'name',
        'description',
        'category',
        'brand',
        'price',
        'original_price',
        'currency',
        'stock_quantity',
        'is_available',
        'main_image_url',
        'images',
        'affiliate_url',
        'commission_rate',
        'commission_amount',
        'attributes',
        'variants',
        'tags',
        'view_count',
        'share_count',
        'click_count',
        'sales_count',
        'sync_status',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'attributes' => 'array',
        'variants' => 'array',
        'tags' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the account that owns this product
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'account_id');
    }

    /**
     * Get the platform for this product
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    /**
     * Get all affiliate links for this product
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(MarketplaceAffiliateLink::class, 'product_id');
    }

    /**
     * Get all order items for this product
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'product_id');
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only available products
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to search products
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%")
                     ->orWhere('brand', 'like', "%{$search}%");
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Increment click count
     */
    public function incrementClicks()
    {
        $this->increment('click_count');
    }

    /**
     * Increment share count
     */
    public function incrementShares()
    {
        $this->increment('share_count');
    }
}
