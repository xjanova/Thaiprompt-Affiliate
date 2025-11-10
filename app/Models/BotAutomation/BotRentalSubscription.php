<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotRentalSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'listing_id',
        'automation_id',
        'subscription_id',
        'status',
        'billing_cycle',
        'price_paid',
        'platform_commission',
        'owner_earning',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'next_billing_date',
        'cancelled_at',
        'expires_at',
        'usage_count',
        'usage_limit',
        'total_spent',
        'settings',
        'cancellation_reason',
        'auto_renew',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'owner_earning' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'next_billing_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'total_spent' => 'decimal:2',
        'settings' => 'array',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace listing
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(BotMarketplaceListing::class);
    }

    /**
     * Get the automation
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(BotAutomation::class);
    }

    /**
     * Scope: Active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Trial subscriptions
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    /**
     * Scope: Due for renewal
     */
    public function scopeDueForRenewal($query)
    {
        return $query->where('status', 'active')
                     ->where('auto_renew', true)
                     ->where('next_billing_date', '<=', now());
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']) &&
               (!$this->expires_at || now()->lessThan($this->expires_at));
    }

    /**
     * Check if in trial period
     */
    public function isInTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               now()->lessThan($this->trial_ends_at);
    }

    /**
     * Cancel subscription
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    /**
     * Renew subscription
     */
    public function renew(float $amount): void
    {
        $listing = $this->listing;
        $commission = $amount * ($listing->commission_rate / 100);
        $ownerEarning = $amount - $commission;

        $this->update([
            'current_period_start' => now(),
            'current_period_end' => $this->calculatePeriodEnd(),
            'next_billing_date' => $this->calculateNextBillingDate(),
            'usage_count' => 0, // Reset usage for new period
        ]);

        $this->increment('total_spent', $amount);
    }

    /**
     * Calculate period end based on billing cycle
     */
    protected function calculatePeriodEnd(): \Carbon\Carbon
    {
        return match($this->billing_cycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    /**
     * Calculate next billing date
     */
    protected function calculateNextBillingDate(): \Carbon\Carbon
    {
        return $this->calculatePeriodEnd();
    }

    /**
     * Increment usage
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');

        // Check if usage limit exceeded
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            $this->update(['status' => 'paused']);
        }
    }

    /**
     * Generate subscription ID
     */
    public static function generateSubscriptionId(): string
    {
        return 'SUB-' . strtoupper(uniqid());
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::creating(function ($subscription) {
            if (!$subscription->subscription_id) {
                $subscription->subscription_id = self::generateSubscriptionId();
            }
        });
    }
}
