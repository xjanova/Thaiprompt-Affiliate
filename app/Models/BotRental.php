<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ai_bot_rentals';

    protected $fillable = [
        'bot_profile_id',
        'renter_id',
        'rental_type',
        'price',
        'commission_rate',
        'start_date',
        'end_date',
        'auto_renew',
        'total_messages',
        'total_amount',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'last_billing_date',
        'next_billing_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'total_amount' => 'decimal:4',
        'total_messages' => 'integer',
        'auto_renew' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_billing_date' => 'datetime',
        'next_billing_date' => 'datetime',
    ];

    /**
     * Get the bot being rented
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get the renter (user who is renting)
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    /**
     * Get all transactions for this rental
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(RentalTransaction::class, 'rental_id');
    }

    /**
     * Get owner earnings
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(OwnerEarning::class, 'rental_id');
    }

    /**
     * Scope: Active rentals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Expired rentals
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope: Monthly rentals
     */
    public function scopeMonthly($query)
    {
        return $query->where('rental_type', 'monthly');
    }

    /**
     * Scope: Per-message rentals
     */
    public function scopePerMessage($query)
    {
        return $query->where('rental_type', 'per_message');
    }

    /**
     * Check if rental is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->rental_type === 'monthly' && $this->end_date) {
            return now()->lte($this->end_date);
        }

        return true;
    }

    /**
     * Check if rental is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->rental_type === 'monthly' && $this->end_date) {
            return now()->gt($this->end_date);
        }

        return false;
    }

    /**
     * Get days remaining (for monthly)
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->rental_type !== 'monthly' || !$this->end_date) {
            return null;
        }

        $days = now()->diffInDays($this->end_date, false);
        return $days > 0 ? $days : 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'expired' => 'secondary',
            'cancelled' => 'danger',
            'suspended' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'ใช้งานอยู่',
            'expired' => 'หมดอายุ',
            'cancelled' => 'ยกเลิกแล้ว',
            'suspended' => 'ระงับ',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Calculate platform commission for an amount
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * ($this->commission_rate / 100);
    }

    /**
     * Calculate owner earning after commission
     */
    public function calculateOwnerEarning(float $amount): float
    {
        return $amount - $this->calculateCommission($amount);
    }
}
