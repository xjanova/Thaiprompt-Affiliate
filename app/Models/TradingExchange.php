<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingExchange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'logo',
        'website_url',
        'api_base_url',
        'supported_markets',
        'supported_pairs',
        'requires_kyc',
        'supports_websocket',
        'supports_paper_trading',
        'supports_margin',
        'supports_futures',
        'is_active',
        'is_maintenance',
        'display_order',
        'rate_limit_per_minute',
        'additional_config',
    ];

    protected $casts = [
        'supported_markets' => 'array',
        'supported_pairs' => 'array',
        'requires_kyc' => 'boolean',
        'supports_websocket' => 'boolean',
        'supports_paper_trading' => 'boolean',
        'supports_margin' => 'boolean',
        'supports_futures' => 'boolean',
        'is_active' => 'boolean',
        'is_maintenance' => 'boolean',
        'additional_config' => 'array',
    ];

    /**
     * Get accounts connected to this exchange
     */
    public function accounts()
    {
        return $this->hasMany(TradingAccount::class, 'exchange_id');
    }

    /**
     * Get market data for this exchange
     */
    public function marketData()
    {
        return $this->hasMany(TradingMarketData::class, 'exchange_id');
    }

    /**
     * Get backtests using this exchange
     */
    public function backtests()
    {
        return $this->hasMany(TradingBacktest::class, 'exchange_id');
    }

    /**
     * Scope for active exchanges
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_maintenance', false);
    }

    /**
     * Scope for crypto exchanges
     */
    public function scopeCrypto($query)
    {
        return $query->where('type', 'crypto');
    }

    /**
     * Scope for forex exchanges
     */
    public function scopeForex($query)
    {
        return $query->where('type', 'forex');
    }

    /**
     * Check if exchange supports market type
     */
    public function supportsMarket(string $market): bool
    {
        return in_array($market, $this->supported_markets ?? []);
    }

    /**
     * Check if exchange supports trading pair
     */
    public function supportsPair(string $pair): bool
    {
        return in_array($pair, $this->supported_pairs ?? []);
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('images/exchanges/default.png');
    }
}
