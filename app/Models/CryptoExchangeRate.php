<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_currency_id',
        'rate_thb',
        'buy_rate_thb',
        'sell_rate_thb',
        'spread_percentage',
        'source',
        'api_source',
        'api_response',
        'price_change_24h',
        'volume_24h',
        'market_cap',
        'valid_from',
        'valid_until',
        'updated_by_type',
        'updated_by_admin',
    ];

    protected $casts = [
        'rate_thb' => 'decimal:2',
        'buy_rate_thb' => 'decimal:2',
        'sell_rate_thb' => 'decimal:2',
        'spread_percentage' => 'decimal:4',
        'price_change_24h' => 'decimal:4',
        'volume_24h' => 'decimal:2',
        'market_cap' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'api_response' => 'array',
    ];

    /**
     * Check if rate is still valid
     */
    public function isValid(): bool
    {
        if (!$this->valid_until) {
            return true;
        }
        return now()->lessThan($this->valid_until);
    }

    /**
     * Check if rate is expired
     */
    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    /**
     * Calculate buy price for amount
     */
    public function calculateBuyPrice(float $cryptoAmount): float
    {
        return $cryptoAmount * $this->buy_rate_thb;
    }

    /**
     * Calculate sell price for amount
     */
    public function calculateSellPrice(float $cryptoAmount): float
    {
        return $cryptoAmount * $this->sell_rate_thb;
    }

    /**
     * Calculate how much crypto you get for THB
     */
    public function calculateCryptoFromTHB(float $thbAmount): float
    {
        if ($this->buy_rate_thb <= 0) {
            return 0;
        }
        return $thbAmount / $this->buy_rate_thb;
    }

    /**
     * Calculate how much THB you get for crypto
     */
    public function calculateTHBFromCrypto(float $cryptoAmount): float
    {
        return $cryptoAmount * $this->sell_rate_thb;
    }

    /**
     * Get spread amount in THB
     */
    public function getSpreadAmount(): float
    {
        return $this->buy_rate_thb - $this->sell_rate_thb;
    }

    /**
     * Get percentage change string
     */
    public function getPriceChangeFormatted(): string
    {
        if ($this->price_change_24h === null) {
            return 'N/A';
        }

        $sign = $this->price_change_24h >= 0 ? '+' : '';
        return $sign . number_format($this->price_change_24h, 2) . '%';
    }

    // ==================== Relationships ====================

    /**
     * Cryptocurrency
     */
    public function currency()
    {
        return $this->belongsTo(CryptoCurrency::class, 'crypto_currency_id');
    }

    /**
     * Admin who updated (if manual)
     */
    public function updatedByAdmin()
    {
        return $this->belongsTo(User::class, 'updated_by_admin');
    }

    /**
     * Exchange transactions using this rate
     */
    public function exchangeTransactions()
    {
        return $this->hasMany(CryptoExchangeTransaction::class);
    }

    // ==================== Scopes ====================

    /**
     * Only valid rates
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>', now());
        });
    }

    /**
     * Only expired rates
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('valid_until')
                     ->where('valid_until', '<=', now());
    }

    /**
     * Filter by source
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * API rates only
     */
    public function scopeFromApi($query)
    {
        return $query->where('source', 'api');
    }

    /**
     * Manual rates only
     */
    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    /**
     * Recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== Static Methods ====================

    /**
     * Get current valid rate for currency
     */
    public static function getCurrentRate(int $currencyId): ?self
    {
        return static::where('crypto_currency_id', $currencyId)
            ->valid()
            ->latest()
            ->first();
    }
}
