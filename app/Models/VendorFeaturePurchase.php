<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorFeaturePurchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'vendor_feature_id',
        'purchase_number',
        'price_paid',
        'payment_method',
        'payment_status',
        'transaction_id',
        'wallet_transaction_id',
        'purchased_version',
        'status',
        'activated_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $purchase->purchase_number = $purchase->generatePurchaseNumber();
            }
        });
    }

    /**
     * Generate unique purchase number
     */
    public function generatePurchaseNumber(): string
    {
        do {
            $number = 'FP-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (self::where('purchase_number', $number)->exists());

        return $number;
    }

    /**
     * Get the vendor who purchased
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the feature purchased
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VendorFeature::class, 'vendor_feature_id');
    }

    /**
     * Get the wallet transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Check if purchase is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return $this->payment_status === 'completed';
    }

    /**
     * Check if purchase is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Activate the purchase
     */
    public function activate(): bool
    {
        $this->status = 'active';
        $this->activated_at = now();

        // Set expiration date for recurring purchases
        if (in_array($this->feature->price_type, ['monthly', 'yearly'])) {
            $this->expires_at = $this->feature->price_type === 'monthly'
                ? now()->addMonth()
                : now()->addYear();
        }

        return $this->save();
    }

    /**
     * Renew the purchase (for recurring)
     */
    public function renew(): bool
    {
        if (!in_array($this->feature->price_type, ['monthly', 'yearly'])) {
            return false;
        }

        $this->expires_at = $this->feature->price_type === 'monthly'
            ? now()->addMonth()
            : now()->addYear();

        return $this->save();
    }

    /**
     * Cancel the purchase
     */
    public function cancel(string $reason = null): bool
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: " . $reason;
        }

        return $this->save();
    }

    /**
     * Scope for active purchases
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('payment_status', 'completed')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired purchases
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for expiring soon (within days)
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope for payment status
     */
    public function scopePaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }
}
