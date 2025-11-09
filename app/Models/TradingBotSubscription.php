<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TradingBotSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'bots_used',
        'exchanges_connected',
        'strategies_created',
        'trades_executed',
        'payment_gateway',
        'payment_id',
        'auto_renew',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package
     */
    public function package()
    {
        return $this->belongsTo(TradingBotPackage::class, 'package_id');
    }

    /**
     * Get bots associated with this subscription
     */
    public function bots()
    {
        return $this->hasMany(TradingBot::class, 'subscription_id');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is in trial
     */
    public function isInTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at !== null &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription has expired
     */
    public function hasExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * Check if can create more bots
     */
    public function canCreateBot(): bool
    {
        return $this->isActive() && $this->bots_used < $this->package->max_bots;
    }

    /**
     * Check if can connect more exchanges
     */
    public function canConnectExchange(): bool
    {
        return $this->isActive() && $this->exchanges_connected < $this->package->max_exchanges;
    }

    /**
     * Check if has feature access
     */
    public function hasFeature(string $feature): bool
    {
        return $this->isActive() && $this->package->hasFeature($feature);
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);
    }

    /**
     * Renew subscription
     */
    public function renew(): void
    {
        $duration = match($this->package->billing_cycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        $this->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($duration),
            'cancelled_at' => null,
        ]);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                    });
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('ends_at', '<=', now());
    }
}
