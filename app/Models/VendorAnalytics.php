<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAnalytics extends Model
{
    protected $fillable = [
        'store_id',
        'date',
        'page_views',
        'unique_visitors',
        'bounce_rate',
        'avg_session_duration',
        'orders_count',
        'total_sales',
        'avg_order_value',
        'products_viewed',
        'products_added_to_cart',
        'conversion_rate',
        'cart_abandonment_rate',
        'gross_revenue',
        'net_revenue',
        'commission_paid',
    ];

    protected $casts = [
        'date' => 'date',
        'page_views' => 'integer',
        'unique_visitors' => 'integer',
        'bounce_rate' => 'decimal:2',
        'avg_session_duration' => 'integer',
        'orders_count' => 'integer',
        'total_sales' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'products_viewed' => 'integer',
        'products_added_to_cart' => 'integer',
        'conversion_rate' => 'decimal:2',
        'cart_abandonment_rate' => 'decimal:2',
        'gross_revenue' => 'decimal:2',
        'net_revenue' => 'decimal:2',
        'commission_paid' => 'decimal:2',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: This month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
            ->whereMonth('date', now()->month);
    }

    /**
     * Scope: Last month
     */
    public function scopeLastMonth($query)
    {
        $lastMonth = now()->subMonth();
        return $query->whereYear('date', $lastMonth->year)
            ->whereMonth('date', $lastMonth->month);
    }

    /**
     * Scope: This year
     */
    public function scopeThisYear($query)
    {
        return $query->whereYear('date', now()->year);
    }

    /**
     * Get formatted average session duration
     */
    public function getFormattedSessionDurationAttribute(): string
    {
        $minutes = floor($this->avg_session_duration / 60);
        $seconds = $this->avg_session_duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get add to cart rate
     */
    public function getAddToCartRateAttribute(): float
    {
        if ($this->products_viewed === 0) {
            return 0;
        }

        return round(($this->products_added_to_cart / $this->products_viewed) * 100, 2);
    }

    /**
     * Get revenue per visitor
     */
    public function getRevenuePerVisitorAttribute(): float
    {
        if ($this->unique_visitors === 0) {
            return 0;
        }

        return round($this->gross_revenue / $this->unique_visitors, 2);
    }

    /**
     * Calculate and record analytics for a specific date
     */
    public static function recordDailyAnalytics(VendorStore $store, $date = null): self
    {
        $date = $date ?? now()->toDateString();

        $analytics = self::firstOrNew([
            'store_id' => $store->id,
            'date' => $date,
        ]);

        // Calculate metrics here
        // This is a placeholder - implement actual calculations
        $analytics->page_views = 0; // Implement tracking
        $analytics->unique_visitors = 0; // Implement tracking
        $analytics->orders_count = $store->orders()
            ->whereDate('created_at', $date)
            ->count();

        $analytics->total_sales = $store->orders()
            ->whereDate('created_at', $date)
            ->sum('total_amount');

        $analytics->gross_revenue = $analytics->total_sales;
        $analytics->commission_paid = $store->orders()
            ->whereDate('created_at', $date)
            ->sum('store_commission');

        $analytics->net_revenue = $analytics->gross_revenue - $analytics->commission_paid;

        if ($analytics->orders_count > 0) {
            $analytics->avg_order_value = $analytics->total_sales / $analytics->orders_count;
        }

        $analytics->save();

        return $analytics;
    }
}
