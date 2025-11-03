<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorFeatureUsage extends Model
{
    protected $table = 'vendor_features_usage';

    protected $fillable = [
        'store_id',
        'feature_id',
        'activated_at',
        'expires_at',
        'feature_settings',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'feature_settings' => 'array',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Get the feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VendorPackageFeature::class, 'feature_id');
    }

    /**
     * Scope: Only active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if feature is active and not expired
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->feature_settings[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->feature_settings ?? [];
        $settings[$key] = $value;
        $this->feature_settings = $settings;
        $this->save();
    }

    /**
     * Deactivate feature
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }
}
