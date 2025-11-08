<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class CryptoCurrency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'network',
        'contract_address',
        'token_standard',
        'decimals',
        'chain_id',
        'icon',
        'color',
        'price_source',
        'coingecko_id',
        'binance_symbol',
        'manual_price_thb',
        'exchange_fee_percentage',
        'min_exchange_amount',
        'min_deposit',
        'min_withdrawal',
        'max_withdrawal',
        'withdrawal_fee',
        'withdrawal_fee_type',
        'confirmations_required',
        'rpc_endpoints',
        'explorer_url',
        'explorer_tx_path',
        'explorer_address_path',
        'is_active',
        'deposit_enabled',
        'withdrawal_enabled',
        'exchange_enabled',
        'payment_gateway_enabled',
        'sort_order',
        'description',
        'metadata',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'exchange_fee_percentage' => 'decimal:4',
        'min_exchange_amount' => 'decimal:8',
        'min_deposit' => 'decimal:8',
        'min_withdrawal' => 'decimal:8',
        'max_withdrawal' => 'decimal:8',
        'withdrawal_fee' => 'decimal:8',
        'confirmations_required' => 'integer',
        'is_active' => 'boolean',
        'deposit_enabled' => 'boolean',
        'withdrawal_enabled' => 'boolean',
        'exchange_enabled' => 'boolean',
        'payment_gateway_enabled' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get RPC endpoints as array
     */
    public function getRpcEndpointsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * Set RPC endpoints from array
     */
    public function setRpcEndpointsAttribute($value)
    {
        $this->attributes['rpc_endpoints'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Check if this is a native token (not ERC-20, BEP-20, etc.)
     */
    public function isNative(): bool
    {
        return $this->token_standard === 'native';
    }

    /**
     * Check if this is an EVM-compatible token
     */
    public function isEVM(): bool
    {
        return in_array($this->network, ['ethereum', 'bsc', 'polygon']);
    }

    /**
     * Get block explorer transaction URL
     */
    public function getExplorerTxUrl(string $txHash): string
    {
        if (empty($this->explorer_url)) {
            return '';
        }
        return rtrim($this->explorer_url, '/') . $this->explorer_tx_path . $txHash;
    }

    /**
     * Get block explorer address URL
     */
    public function getExplorerAddressUrl(string $address): string
    {
        if (empty($this->explorer_url)) {
            return '';
        }
        return rtrim($this->explorer_url, '/') . $this->explorer_address_path . $address;
    }

    /**
     * Get current exchange rate
     */
    public function getCurrentRate()
    {
        return $this->exchangeRates()->latest()->first();
    }

    /**
     * Get cached current rate (faster)
     */
    public function getCachedCurrentRate()
    {
        $cacheKey = "crypto_rate_{$this->id}";
        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getCurrentRate();
        });
    }

    /**
     * Format amount with correct decimals
     */
    public function formatAmount($amount): string
    {
        return number_format($amount, $this->decimals, '.', '');
    }

    /**
     * Convert from wei/smallest unit to readable unit
     */
    public function fromWei($weiAmount)
    {
        return bcdiv($weiAmount, bcpow('10', (string)$this->decimals, 0), $this->decimals);
    }

    /**
     * Convert from readable unit to wei/smallest unit
     */
    public function toWei($amount)
    {
        return bcmul($amount, bcpow('10', (string)$this->decimals, 0), 0);
    }

    // ==================== Relationships ====================

    /**
     * Crypto wallets that hold this currency
     */
    public function cryptoAddresses()
    {
        return $this->hasMany(CryptoAddress::class);
    }

    /**
     * All transactions for this currency
     */
    public function cryptoTransactions()
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    /**
     * Exchange rates history
     */
    public function exchangeRates()
    {
        return $this->hasMany(CryptoExchangeRate::class);
    }

    /**
     * Withdrawal requests for this currency
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(CryptoWithdrawalRequest::class);
    }

    /**
     * Deposit addresses for this currency
     */
    public function depositAddresses()
    {
        return $this->hasMany(CryptoDepositAddress::class);
    }

    // ==================== Scopes ====================

    /**
     * Only active currencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only deposit-enabled currencies
     */
    public function scopeDepositEnabled($query)
    {
        return $query->where('is_active', true)->where('deposit_enabled', true);
    }

    /**
     * Only withdrawal-enabled currencies
     */
    public function scopeWithdrawalEnabled($query)
    {
        return $query->where('is_active', true)->where('withdrawal_enabled', true);
    }

    /**
     * Only exchange-enabled currencies
     */
    public function scopeExchangeEnabled($query)
    {
        return $query->where('is_active', true)->where('exchange_enabled', true);
    }

    /**
     * Filter by network
     */
    public function scopeNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ==================== Static Methods ====================

    /**
     * Get currency by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->first();
    }

    /**
     * Get all active currencies grouped by network
     */
    public static function getByNetwork(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy('network')
            ->toArray();
    }
}
