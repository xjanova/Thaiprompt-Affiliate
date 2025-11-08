<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class MarketplaceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform_id',
        'account_name',
        'shop_id',
        'shop_name',
        'app_key',
        'app_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'additional_credentials',
        'commission_rate',
        'auto_sync_products',
        'auto_sync_orders',
        'sync_frequency',
        'status',
        'last_sync_at',
        'last_error',
        'total_products_synced',
        'total_orders_synced',
        'total_sales',
        'total_commission',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'additional_credentials' => 'array',
        'commission_rate' => 'decimal:2',
        'auto_sync_products' => 'boolean',
        'auto_sync_orders' => 'boolean',
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
    ];

    protected $hidden = [
        'app_key',
        'app_secret',
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the platform for this account
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    /**
     * Get all products for this account
     */
    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class, 'account_id');
    }

    /**
     * Get all orders for this account
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'account_id');
    }

    /**
     * Get all sync logs for this account
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class, 'account_id');
    }

    /**
     * Encrypt app_key before saving
     */
    public function setAppKeyAttribute($value)
    {
        $this->attributes['app_key'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt app_key when retrieving
     */
    public function getAppKeyAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt app_secret before saving
     */
    public function setAppSecretAttribute($value)
    {
        $this->attributes['app_secret'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt app_secret when retrieving
     */
    public function getAppSecretAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt access_token before saving
     */
    public function setAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt access_token when retrieving
     */
    public function getAccessTokenAttribute($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt refresh_token before saving
     */
    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt refresh_token when retrieving
     */
    public function getRefreshTokenAttribute($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get accounts that need syncing
     */
    public function scopeNeedsSync($query)
    {
        return $query->where('status', 'active')
                     ->where('auto_sync_products', true);
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }
}
