<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareProductOptionValue extends Model
{
    protected $fillable = [
        'software_product_option_id',
        'value',
        'display_label',
        'description',
        'price_modifier',
        'price_type',
        'setup_fee',
        'monthly_fee',
        'icon',
        'image_url',
        'additional_info',
        'is_default',
        'is_recommended',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'additional_info' => 'array',
        'is_default' => 'boolean',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the option this value belongs to
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(SoftwareProductOption::class, 'software_product_option_id');
    }

    /**
     * Calculate price based on base price
     */
    public function calculatePrice(float $basePrice): float
    {
        if ($this->price_type === 'percentage') {
            return $basePrice * ($this->price_modifier / 100);
        }

        return $this->price_modifier;
    }

    /**
     * Get total one-time cost (price_modifier + setup_fee)
     */
    public function getTotalOneTimeCostAttribute(): float
    {
        return $this->price_modifier + $this->setup_fee;
    }

    /**
     * Scope: Only active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default values
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Recommended values
     */
    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get formatted price modifier
     */
    public function getFormattedPriceModifierAttribute(): string
    {
        if ($this->price_type === 'percentage') {
            return $this->price_modifier > 0
                ? "+{$this->price_modifier}%"
                : "{$this->price_modifier}%";
        }

        return number_format($this->price_modifier, 2);
    }
}
