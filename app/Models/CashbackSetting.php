<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashbackSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'product_id',
        'value_type',
        'value',
        'is_active',
        'min_order_amount',
        'max_cashback_amount',
        'description',
        'conditions',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'max_cashback_amount' => 'decimal:2',
        'conditions' => 'array',
    ];

    /**
     * Get the product this cashback setting belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Only active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Global settings
     */
    public function scopeGlobal($query)
    {
        return $query->where('type', 'global');
    }

    /**
     * Scope: Product-specific settings
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('type', 'product')
                    ->where('product_id', $productId);
    }

    /**
     * Get the active global cashback setting
     */
    public static function getGlobalSetting(): ?self
    {
        return self::active()
                  ->global()
                  ->orderBy('created_at', 'desc')
                  ->first();
    }

    /**
     * Get cashback setting for a specific product
     */
    public static function getProductSetting(int $productId): ?self
    {
        return self::active()
                  ->forProduct($productId)
                  ->first();
    }

    /**
     * Calculate cashback amount
     */
    public function calculateCashback(float $amount): float
    {
        // Check minimum order amount
        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return 0;
        }

        // Calculate cashback based on type
        $cashback = $this->value_type === 'percentage'
            ? ($amount * $this->value / 100)
            : $this->value;

        // Apply maximum cashback cap if set
        if ($this->max_cashback_amount && $cashback > $this->max_cashback_amount) {
            $cashback = $this->max_cashback_amount;
        }

        return round($cashback, 2);
    }

    /**
     * Check if order qualifies for cashback
     */
    public function qualifiesForCashback(float $amount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted value
     */
    public function getFormattedValueAttribute(): string
    {
        return $this->value_type === 'percentage'
            ? $this->value . '%'
            : '฿' . number_format($this->value, 2);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'global' => 'ทั่วไป',
            'product' => 'เฉพาะสินค้า',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get value type label
     */
    public function getValueTypeLabelAttribute(): string
    {
        return match($this->value_type) {
            'percentage' => 'เปอร์เซ็นต์',
            'fixed' => 'ค่าคงที่',
            default => 'ไม่ระบุ',
        };
    }
}
