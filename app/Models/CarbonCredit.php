<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'food_product_id',
        'credit_amount',
        'credit_type',
        'baseline_emission',
        'actual_emission',
        'reduction_percentage',
        'credit_value_per_ton',
        'total_value',
        'issued_date',
        'expiry_date',
        'status',
        'tradeable',
        'traded_to_user_id',
        'traded_at',
        'trade_price',
        'verified_by',
        'verification_standard',
        'certificate_number',
        'blockchain_hash',
        'token_id',
    ];

    protected $casts = [
        'credit_amount' => 'decimal:4',
        'baseline_emission' => 'decimal:4',
        'actual_emission' => 'decimal:4',
        'reduction_percentage' => 'decimal:2',
        'credit_value_per_ton' => 'decimal:2',
        'total_value' => 'decimal:2',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'tradeable' => 'boolean',
        'traded_at' => 'datetime',
        'trade_price' => 'decimal:2',
    ];

    /**
     * Get the user who owns this credit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the food product that earned this credit
     */
    public function foodProduct(): BelongsTo
    {
        return $this->belongsTo(FoodProduct::class);
    }

    /**
     * Get the user this credit was traded to
     */
    public function tradedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traded_to_user_id');
    }

    /**
     * Check if credit is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expiry_date > now();
    }

    /**
     * Check if credit is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date <= now();
    }

    /**
     * Check if credit has been traded
     */
    public function isTraded(): bool
    {
        return $this->status === 'traded';
    }

    /**
     * Check if credit can be traded
     */
    public function canTrade(): bool
    {
        return $this->tradeable
            && $this->status === 'active'
            && !$this->isExpired();
    }

    /**
     * Get current market value
     */
    public function getCurrentValueAttribute(): float
    {
        // This could be dynamic based on market rates
        return $this->credit_amount * $this->credit_value_per_ton;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * Scope: Active credits
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope: Tradeable credits
     */
    public function scopeTradeable($query)
    {
        return $query->where('tradeable', true)
            ->active();
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->where('status', 'active');
    }
}
