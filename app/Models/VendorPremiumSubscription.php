<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPremiumSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'premium_feature_id',
        'started_at',
        'expires_at',
        'status',
        'amount_paid',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the premium feature
     */
    public function premiumFeature(): BelongsTo
    {
        return $this->belongsTo(PremiumFeature::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at === null) {
            return true; // Lifetime
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
