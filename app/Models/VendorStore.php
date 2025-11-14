<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VendorStore extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'store_name',
        'store_slug',
        'store_description',
        'store_logo',
        'store_banner',
        'banner_position_y',
        'store_domain',
        'store_email',
        'store_phone',
        'store_address',
        'store_city',
        'store_state',
        'store_postal_code',
        'store_country',
        'business_type',
        'tax_id',
        'company_name',
        'primary_color',
        'secondary_color',
        'store_theme',
        'custom_css',
        'facebook_url',
        'line_oa_id',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'commission_rate',
        'minimum_order_amount',
        'shipping_fee',
        'free_shipping_threshold',
        'enable_cod',
        'enable_reviews',
        'auto_approve_orders',
        'total_products',
        'total_orders',
        'total_sales',
        'total_revenue',
        'rating_average',
        'rating_count',
        'is_active',
        'is_verified',
        'verified_at',
        'is_featured_home',
        'featured_home_order',
        'status',
        'suspension_reason',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_status',
        'trial_ends_at',
    ];

    protected $casts = [
        'banner_position_y' => 'integer',
        'commission_rate' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'enable_cod' => 'boolean',
        'enable_reviews' => 'boolean',
        'auto_approve_orders' => 'boolean',
        'total_products' => 'integer',
        'total_orders' => 'integer',
        'total_sales' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_featured_home' => 'boolean',
        'featured_home_order' => 'integer',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->store_slug)) {
                $store->store_slug = Str::slug($store->store_name);
            }
        });
    }

    /**
     * Get the owner (user) of this store
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for owner relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the current package
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VendorPackage::class, 'package_id');
    }

    /**
     * Get all products in this store
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    /**
     * Get all orders for this store
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    /**
     * Get all subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'store_id');
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(VendorSubscription::class, 'store_id')
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get all active features
     */
    public function features(): HasMany
    {
        return $this->hasMany(VendorFeatureUsage::class, 'store_id');
    }

    /**
     * Get active features only
     */
    public function activeFeatures(): HasMany
    {
        return $this->features()->where('is_active', true);
    }

    /**
     * Get all public products
     */
    public function publicProducts(): HasMany
    {
        return $this->hasMany(VendorPublicProduct::class, 'store_id');
    }

    /**
     * Get all marketing campaigns
     */
    public function marketingCampaigns(): HasMany
    {
        return $this->hasMany(VendorMarketingCampaign::class, 'store_id');
    }

    /**
     * Get analytics records
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(VendorAnalytics::class, 'store_id');
    }

    /**
     * Scope: Only active stores
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope: Only verified stores
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' &&
               $this->subscription_expires_at &&
               $this->subscription_expires_at->isFuture();
    }

    /**
     * Check if in trial period
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_status === 'expired' ||
               ($this->subscription_expires_at && $this->subscription_expires_at->isPast());
    }

    /**
     * Get store logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->store_logo) {
            return null;
        }

        return asset($this->store_logo);
    }

    /**
     * Get store banner URL
     */
    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->store_banner) {
            return null;
        }

        return asset($this->store_banner);
    }

    /**
     * Get store URL
     */
    public function getStoreUrlAttribute(): string
    {
        if ($this->store_domain) {
            return 'https://' . $this->store_domain;
        }

        return route('vendor.stores.show', $this->store_slug);
    }

    /**
     * Check if store can add more products
     */
    public function canAddProducts(): bool
    {
        if (!$this->package) {
            return false;
        }

        return !$this->package->isProductLimitReached($this);
    }

    /**
     * Check if store can process more orders this month
     */
    public function canProcessOrders(): bool
    {
        if (!$this->package) {
            return false;
        }

        return !$this->package->isMonthlyOrderLimitReached($this);
    }

    /**
     * Check if store has a specific feature
     */
    public function hasFeature(string $featureSlug): bool
    {
        // Check package features first
        if ($this->package && $this->package->allowsFeature($featureSlug)) {
            return true;
        }

        // Check purchased add-on features
        return $this->activeFeatures()
            ->whereHas('feature', function($q) use ($featureSlug) {
                $q->where('feature_slug', $featureSlug);
            })
            ->exists();
    }

    /**
     * Calculate commission for an amount
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Calculate store earning after commission
     */
    public function calculateEarning(float $amount): float
    {
        return $amount - $this->calculateCommission($amount);
    }

    /**
     * Increment product count
     */
    public function incrementProductCount(): void
    {
        $this->increment('total_products');
    }

    /**
     * Decrement product count
     */
    public function decrementProductCount(): void
    {
        $this->decrement('total_products');
    }

    /**
     * Update store statistics
     */
    public function updateStatistics(): void
    {
        $this->total_products = $this->products()->count();
        $this->total_orders = $this->orders()->count();
        $this->total_sales = $this->orders()->where('status', 'completed')->sum('total_amount');
        $this->total_revenue = $this->orders()->where('status', 'completed')->sum('store_earning');
        $this->save();
    }
}
