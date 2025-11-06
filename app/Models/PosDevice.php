<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'device_name',
        'device_code',
        'device_type',
        'license_key',
        'hardware_id',
        'subscription_status',
        'trial_ends_at',
        'subscription_starts_at',
        'subscription_ends_at',
        'monthly_fee',
        'auto_renew',
        'features',
        'settings',
        'dual_screen_enabled',
        'offline_mode_enabled',
        'receipt_printer_enabled',
        'is_active',
        'is_online',
        'last_online_at',
        'last_ip_address',
        'device_info',
        'total_transactions',
        'total_sales',
        'last_transaction_at',
    ];

    protected $casts = [
        'features' => 'array',
        'settings' => 'array',
        'dual_screen_enabled' => 'boolean',
        'offline_mode_enabled' => 'boolean',
        'receipt_printer_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'auto_renew' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_starts_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_online_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'monthly_fee' => 'decimal:2',
        'total_sales' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function offlineQueue(): HasMany
    {
        return $this->hasMany(PosOfflineQueue::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Helper Methods
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active'
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function canOperate(): bool
    {
        return $this->is_active
            && ($this->hasActiveSubscription() || $this->isOnTrial());
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function updateOnlineStatus(bool $isOnline, ?string $ipAddress = null): void
    {
        $this->update([
            'is_online' => $isOnline,
            'last_online_at' => now(),
            'last_ip_address' => $ipAddress ?? $this->last_ip_address,
        ]);
    }

    public function incrementTransactionStats(float $amount): void
    {
        $this->increment('total_transactions');
        $this->increment('total_sales', $amount);
        $this->update(['last_transaction_at' => now()]);
    }

    public function generateDeviceCode(): string
    {
        return 'POS-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function generateLicenseKey(): string
    {
        return strtoupper(
            substr(md5(uniqid()), 0, 4) . '-' .
            substr(md5(uniqid()), 0, 4) . '-' .
            substr(md5(uniqid()), 0, 4) . '-' .
            substr(md5(uniqid()), 0, 4)
        );
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->device_code)) {
                $device->device_code = $device->generateDeviceCode();
            }
            if (empty($device->license_key)) {
                $device->license_key = $device->generateLicenseKey();
            }
        });
    }
}
