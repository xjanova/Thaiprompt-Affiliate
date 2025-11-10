<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class TradingAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'exchange_id',
        'account_name',
        'account_type',
        'market_type',
        'api_key',
        'api_secret',
        'api_passphrase',
        'additional_credentials',
        'initial_balance',
        'current_balance',
        'balance_currency',
        'last_sync_at',
        'status',
        'error_message',
        'is_verified',
        'verified_at',
        'allow_trading',
        'notifications_enabled',
        'settings',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:8',
        'current_balance' => 'decimal:8',
        'last_sync_at' => 'datetime',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'allow_trading' => 'boolean',
        'notifications_enabled' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'api_passphrase',
        'additional_credentials',
    ];

    /**
     * Get the user that owns the account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exchange
     */
    public function exchange()
    {
        return $this->belongsTo(TradingExchange::class, 'exchange_id');
    }

    /**
     * Get bots using this account
     */
    public function bots()
    {
        return $this->hasMany(TradingBot::class, 'account_id');
    }

    /**
     * Get trades for this account
     */
    public function trades()
    {
        return $this->hasMany(TradingTrade::class, 'account_id');
    }

    /**
     * Get portfolio snapshots
     */
    public function portfolioSnapshots()
    {
        return $this->hasMany(TradingPortfolioSnapshot::class, 'account_id');
    }

    /**
     * Encrypt API key before saving
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API key when retrieving
     */
    public function getApiKeyAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt API secret before saving
     */
    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API secret when retrieving
     */
    public function getApiSecretAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encrypt API passphrase before saving
     */
    public function setApiPassphraseAttribute($value)
    {
        $this->attributes['api_passphrase'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API passphrase when retrieving
     */
    public function getApiPassphraseAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_verified;
    }

    /**
     * Check if account can trade
     */
    public function canTrade(): bool
    {
        return $this->isActive() && $this->allow_trading;
    }

    /**
     * Get profit/loss
     */
    public function getProfitLoss(): float
    {
        return $this->current_balance - $this->initial_balance;
    }

    /**
     * Get ROI percentage
     */
    public function getRoiPercentage(): float
    {
        if ($this->initial_balance == 0) {
            return 0;
        }
        return (($this->current_balance - $this->initial_balance) / $this->initial_balance) * 100;
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_verified', true);
    }

    /**
     * Scope for live accounts
     */
    public function scopeLive($query)
    {
        return $query->where('account_type', 'live');
    }

    /**
     * Scope for demo accounts
     */
    public function scopeDemo($query)
    {
        return $query->where('account_type', 'demo');
    }
}
