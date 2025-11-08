<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplacePlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'api_documentation_url',
        'is_active',
        'requires_app_key',
        'requires_app_secret',
        'requires_access_token',
        'requires_shop_id',
        'additional_fields',
        'default_commission_rate',
        'min_commission_rate',
        'max_commission_rate',
        'supports_product_sync',
        'supports_order_sync',
        'supports_real_time_webhook',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_app_key' => 'boolean',
        'requires_app_secret' => 'boolean',
        'requires_access_token' => 'boolean',
        'requires_shop_id' => 'boolean',
        'additional_fields' => 'array',
        'default_commission_rate' => 'decimal:2',
        'min_commission_rate' => 'decimal:2',
        'max_commission_rate' => 'decimal:2',
        'supports_product_sync' => 'boolean',
        'supports_order_sync' => 'boolean',
        'supports_real_time_webhook' => 'boolean',
    ];

    /**
     * Get all accounts for this platform
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(MarketplaceAccount::class, 'platform_id');
    }

    /**
     * Get all products for this platform
     */
    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class, 'platform_id');
    }

    /**
     * Get all orders for this platform
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'platform_id');
    }

    /**
     * Get all sync logs for this platform
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class, 'platform_id');
    }

    /**
     * Get all commissions for this platform
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(MarketplaceCommission::class, 'platform_id');
    }

    /**
     * Get all affiliate links for this platform
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(MarketplaceAffiliateLink::class, 'platform_id');
    }

    /**
     * Scope to get only active platforms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get platform by slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}
