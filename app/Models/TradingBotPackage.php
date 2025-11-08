<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingBotPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_bots',
        'max_exchanges',
        'max_strategies',
        'max_active_trades',
        'ai_signals',
        'advanced_analytics',
        'backtesting',
        'paper_trading',
        'copy_trading',
        'strategy_marketplace',
        'custom_indicators',
        'api_access',
        'priority_support',
        'display_order',
        'is_featured',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'ai_signals' => 'boolean',
        'advanced_analytics' => 'boolean',
        'backtesting' => 'boolean',
        'paper_trading' => 'boolean',
        'copy_trading' => 'boolean',
        'strategy_marketplace' => 'boolean',
        'custom_indicators' => 'boolean',
        'api_access' => 'boolean',
        'priority_support' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    /**
     * Get subscriptions for this package
     */
    public function subscriptions()
    {
        return $this->hasMany(TradingBotSubscription::class, 'package_id');
    }

    /**
     * Get active subscriptions
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured packages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . config('app.currency', 'USD');
    }

    /**
     * Get billing cycle label
     */
    public function getBillingCycleLabelAttribute()
    {
        return match($this->billing_cycle) {
            'monthly' => 'รายเดือน',
            'quarterly' => 'รายไตรมาส',
            'yearly' => 'รายปี',
            'lifetime' => 'ตลอดชีพ',
            default => $this->billing_cycle,
        };
    }

    /**
     * Check if package has feature
     */
    public function hasFeature(string $feature): bool
    {
        return $this->{$feature} ?? false;
    }
}
