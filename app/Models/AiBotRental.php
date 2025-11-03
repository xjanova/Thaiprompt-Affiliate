<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiBotRental extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'renter_id',
        'owner_id',
        'rental_plan',
        'start_date',
        'end_date',
        'is_active',
        'monthly_price',
        'price_per_message',
        'total_messages',
        'total_tokens_used',
        'total_cost',
        'commission_rate',
        'platform_commission',
        'owner_earning',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'price_per_message' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'owner_earning' => 'decimal:2',
        'cancelled_at' => 'datetime',
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
     * Get the owner (bot creator)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get conversations under this rental
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'rental_id');
    }

    /**
     * Scope: Only active rentals
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereNull('cancelled_at');
    }

    /**
     * Scope: For specific renter
     */
    public function scopeForRenter($query, int $renterId)
    {
        return $query->where('renter_id', $renterId);
    }

    /**
     * Scope: For specific owner
     */
    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope: Expired rentals
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now())
                     ->where('is_active', true);
    }

    /**
     * Calculate commission from total cost
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
     * Add usage and update commission
     */
    public function addUsage(int $messages, int $tokens, float $cost): void
    {
        $this->total_messages += $messages;
        $this->total_tokens_used += $tokens;
        $this->total_cost += $cost;

        // Recalculate commission
        $commission = $this->calculateCommission($this->total_cost);
        $this->platform_commission = $commission['platform_commission'];
        $this->owner_earning = $commission['owner_earning'];

        $this->save();
    }

    /**
     * Cancel rental
     */
    public function cancel(): void
    {
        $this->is_active = false;
        $this->cancelled_at = now();
        $this->end_date = now();
        $this->save();
    }

    /**
     * Check if rental is expired
     */
    public function isExpired(): bool
    {
        if ($this->rental_plan === 'monthly' && $this->end_date) {
            return $this->end_date->isPast();
        }

        return false;
    }

    /**
     * Get rental status
     */
    public function getStatus(): string
    {
        if ($this->cancelled_at) {
            return 'cancelled';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->is_active) {
            return 'active';
        }

        return 'inactive';
    }

    /**
     * Get days remaining (for monthly plan)
     */
    public function getDaysRemaining(): ?int
    {
        if ($this->rental_plan !== 'monthly' || !$this->end_date) {
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
        if ($this->rental_plan !== 'monthly') {
            return false;
        }

        $this->end_date = $this->end_date
            ? $this->end_date->addMonths($months)
            : now()->addMonths($months);

        $this->is_active = true;
        $this->cancelled_at = null;

        return $this->save();
    }
}
