<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'package_name',
        'package_slug',
        'display_name',
        'description',
        'features',
        'price',
        'yearly_price',
        'setup_fee',
        'currency',
        'max_products',
        'max_images_per_product',
        'max_categories',
        'max_storage_mb',
        'max_monthly_orders',
        'commission_rate',
        'allow_custom_domain',
        'allow_custom_theme',
        'allow_api_access',
        'allow_export_data',
        'allow_advanced_analytics',
        'allow_bulk_operations',
        'allow_ai_bot',
        'allow_marketing_tools',
        'priority_support',
        'trial_days',
        'badge',
        'badge_color',
        'sort_order',
        'is_featured',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'max_products' => 'integer',
        'max_images_per_product' => 'integer',
        'max_categories' => 'integer',
        'max_storage_mb' => 'integer',
        'max_monthly_orders' => 'integer',
        'commission_rate' => 'decimal:2',
        'allow_custom_domain' => 'boolean',
        'allow_custom_theme' => 'boolean',
        'allow_api_access' => 'boolean',
        'allow_export_data' => 'boolean',
        'allow_advanced_analytics' => 'boolean',
        'allow_bulk_operations' => 'boolean',
        'allow_ai_bot' => 'boolean',
        'allow_marketing_tools' => 'boolean',
        'priority_support' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get all stores with this package
     */
    public function stores(): HasMany
    {
        return $this->hasMany(VendorStore::class, 'package_id');
    }

    /**
     * Get all subscriptions for this package
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'package_id');
    }

    /**
     * Scope: Only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get package level (for feature requirements)
     * 0 = Free, 1 = Basic, 2 = Premium, 3 = Enterprise
     */
    public function getLevelAttribute(): int
    {
        return match(strtolower($this->package_slug)) {
            'free' => 0,
            'basic' => 1,
            'premium' => 2,
            'enterprise' => 3,
            default => 0,
        };
    }

    /**
     * Check if package allows a specific feature
     */
    public function allowsFeature(string $feature): bool
    {
        $featureKey = 'allow_' . $feature;
        return $this->$featureKey ?? false;
    }

    /**
     * Check if products limit reached for a store
     */
    public function isProductLimitReached(VendorStore $store): bool
    {
        if ($this->max_products === null) {
            return false; // Unlimited
        }

        return $store->total_products >= $this->max_products;
    }

    /**
     * Check if orders limit reached for current month
     */
    public function isMonthlyOrderLimitReached(VendorStore $store): bool
    {
        if ($this->max_monthly_orders === null) {
            return false; // Unlimited
        }

        $currentMonthOrders = $store->orders()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $currentMonthOrders >= $this->max_monthly_orders;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) {
            return __('Free');
        }

        return '฿' . number_format($this->price, 0);
    }

    /**
     * Get formatted yearly price
     */
    public function getFormattedYearlyPriceAttribute(): ?string
    {
        if (!$this->yearly_price) {
            return null;
        }

        return '฿' . number_format($this->yearly_price, 0) . '/' . __('year');
    }

    /**
     * Calculate monthly savings if paying yearly
     */
    public function getYearlySavingsAttribute(): ?float
    {
        if (!$this->yearly_price || !$this->price) {
            return null;
        }

        $monthlyTotal = $this->price * 12;
        return $monthlyTotal - $this->yearly_price;
    }

    /**
     * Get savings percentage
     */
    public function getYearlySavingsPercentageAttribute(): ?int
    {
        $savings = $this->yearly_savings;
        if (!$savings || !$this->price) {
            return null;
        }

        return round(($savings / ($this->price * 12)) * 100);
    }
}
