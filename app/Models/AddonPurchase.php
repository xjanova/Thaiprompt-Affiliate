<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'addon_id',
        'purchase_price',
        'payment_method',
        'payment_status',
        'transaction_id',
        'activated_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the vendor that purchased this addon
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the addon that was purchased
     */
    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    /**
     * Check if the addon purchase is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active || $this->payment_status !== 'completed') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Activate the addon
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate the addon
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope: Active purchases only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('payment_status', 'completed');
    }

    /**
     * Scope: Valid (active and not expired)
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where('payment_status', 'completed')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
