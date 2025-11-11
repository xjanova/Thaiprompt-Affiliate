<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGenSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'image_credits_total',
        'image_credits_used',
        'video_credits_total',
        'video_credits_used',
        'started_at',
        'expires_at',
        'cancelled_at',
        'status',
        'amount_paid',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'image_credits_total' => 'integer',
        'image_credits_used' => 'integer',
        'video_credits_total' => 'integer',
        'video_credits_used' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package that this subscription belongs to.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(AiGenPackage::class, 'package_id');
    }

    /**
     * Get the usage logs for this subscription.
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiGenUsageLog::class, 'subscription_id');
    }

    /**
     * Get remaining image credits.
     */
    public function getRemainingImageCreditsAttribute(): int
    {
        return max(0, $this->image_credits_total - $this->image_credits_used);
    }

    /**
     * Get remaining video credits.
     */
    public function getRemainingVideoCreditsAttribute(): int
    {
        return max(0, $this->video_credits_total - $this->video_credits_used);
    }

    /**
     * Check if subscription has credits for a type.
     */
    public function hasCredits(string $type): bool
    {
        if ($type === 'image') {
            return $this->remaining_image_credits > 0;
        } elseif ($type === 'video') {
            return $this->remaining_video_credits > 0;
        }
        return false;
    }

    /**
     * Use credits.
     */
    public function useCredits(string $type, int $amount = 1): bool
    {
        if (!$this->hasCredits($type)) {
            return false;
        }

        if ($type === 'image') {
            $this->image_credits_used += $amount;
        } elseif ($type === 'video') {
            $this->video_credits_used += $amount;
        }

        return $this->save();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);
            return false;
        }

        return true;
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'active')
                    ->where('expires_at', '<', now());
            });
    }
}
