<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SMS Gateway Pricing Model
 *
 * แพ็กเกจราคาค่าเช่า SMS Payment Gateway
 * แอดมินกำหนดราคาและจำนวนอุปกรณ์สูงสุดต่อแพ็กเกจ
 */
class SmsGatewayPricing extends Model
{
    protected $table = 'sms_gateway_pricing';

    protected $fillable = [
        'name',
        'description',
        'plan_type',
        'price',
        'currency',
        'max_devices',
        'trial_days',
        'is_active',
        'is_featured',
        'sort_order',
        'features_json',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'features_json' => 'array',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SmsGatewaySubscription::class, 'pricing_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('plan_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('plan_type', 'yearly');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // ========================================
    // HELPERS
    // ========================================

    public function getFormattedPriceAttribute(): string
    {
        return '฿' . number_format($this->price, 0);
    }

    public function getPricePerMonthAttribute(): float
    {
        if ($this->plan_type === 'yearly') {
            return round($this->price / 12, 2);
        }
        return (float) $this->price;
    }

    public function getSavingsPercentAttribute(): int
    {
        if ($this->plan_type !== 'yearly') {
            return 0;
        }

        // หาราคา monthly ของแพ็กเกจเดียวกัน (ตาม max_devices)
        $monthly = static::where('plan_type', 'monthly')
            ->where('max_devices', $this->max_devices)
            ->where('is_active', true)
            ->first();

        if (!$monthly) {
            return 0;
        }

        $yearlyFromMonthly = $monthly->price * 12;
        if ($yearlyFromMonthly <= 0) {
            return 0;
        }

        return (int) round((($yearlyFromMonthly - $this->price) / $yearlyFromMonthly) * 100);
    }
}
