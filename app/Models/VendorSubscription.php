<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSubscription extends Model
{
    protected $fillable = [
        'store_id',
        'package_id',
        'subscription_type',
        'amount',
        'currency',
        'started_at',
        'expires_at',
        'cancelled_at',
        'payment_method',
        'payment_transaction_id',
        'payment_status',
        'paid_at',
        'auto_renew',
        'next_billing_date',
        'status',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
        'auto_renew' => 'boolean',
        'next_billing_date' => 'date',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Get the package
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(VendorPackage::class, 'package_id');
    }

    /**
     * Scope: Only active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->where('auto_renew', false)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if expiring soon
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expires_at->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Cancel subscription
     */
    public function cancel(string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->auto_renew = false;

        if ($reason) {
            $this->cancellation_reason = $reason;
        }

        return $this->save();
    }

    /**
     * Renew subscription
     */
    public function renew(): bool
    {
        if ($this->subscription_type === 'monthly') {
            $this->expires_at = $this->expires_at->addMonth();
        } elseif ($this->subscription_type === 'yearly') {
            $this->expires_at = $this->expires_at->addYear();
        }

        $this->status = 'active';
        $this->next_billing_date = $this->expires_at->toDateString();

        return $this->save();
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '฿' . number_format($this->amount, 0);
    }
}
