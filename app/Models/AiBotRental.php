<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AiBotRental Model
 *
 * จัดการข้อมูลการเช่าบอท AI
 */
class AiBotRental extends Model
{
    use SoftDeletes;

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
     * Get the bot profile being rented
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get the renter (person renting the bot)
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    /**
     * Get the owner (bot creator) through bot profile
     *
     * ดึงเจ้าของบอทผ่าน botProfile
     */
    public function owner(): BelongsTo
    {
        return $this->botProfile->owner();
    }

    /**
     * Get conversations under this rental
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'rental_id');
    }

    /**
     * Get owner earnings for this rental
     */
    public function ownerEarnings(): HasMany
    {
        return $this->hasMany(OwnerEarning::class, 'rental_id');
    }

    /**
     * Scope: Only active rentals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: For specific renter
     */
    public function scopeForRenter($query, int $renterId)
    {
        return $query->where('renter_id', $renterId);
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
     * Calculate commission from amount
     */
    public function calculateCommission(float $amount): array
    {
        $platformCommission = $amount * ($this->commission_rate / 100);
        $ownerEarning = $amount - $platformCommission;

        return [
            'total' => $amount,
            'platform_commission' => round($platformCommission, 2),
            'owner_earning' => round($ownerEarning, 2),
        ];
    }

    /**
     * Add usage (for per-message rentals)
     */
    public function addUsage(int $messages, float $amount): void
    {
        $this->total_messages += $messages;
        $this->total_amount += $amount;
        $this->save();
    }

    /**
     * Cancel rental
     */
    public function cancel(?string $reason = null): void
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        $this->cancelled_at = now();
        $this->save();
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
     * Get days remaining (for monthly plan)
     */
    public function getDaysRemaining(): ?int
    {
        if ($this->rental_type !== 'monthly' || ! $this->end_date) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * Renew monthly rental
     */
    public function renew(int $months = 1): bool
    {
        if ($this->rental_type !== 'monthly') {
            return false;
        }

        $this->end_date = $this->end_date
            ? $this->end_date->addMonths($months)
            : now()->addMonths($months);

        $this->status = 'active';
        $this->cancelled_at = null;
        $this->next_billing_date = now()->addMonth();
        $this->last_billing_date = now();

        return $this->save();
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
}
