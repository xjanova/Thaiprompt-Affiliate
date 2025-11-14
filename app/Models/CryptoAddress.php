<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class CryptoAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'crypto_wallet_id',
        'crypto_currency_id',
        'address',
        'network',
        'address_type',
        'is_imported',
        'encrypted_private_key',
        'public_key',
        'address_index',
        'balance',
        'balance_updated_at',
        'label',
        'notes',
        'is_active',
        'is_watched',
        'total_received_transactions',
        'total_sent_transactions',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:18',
        'balance_updated_at' => 'datetime',
        'is_imported' => 'boolean',
        'is_active' => 'boolean',
        'is_watched' => 'boolean',
        'address_index' => 'integer',
        'total_received_transactions' => 'integer',
        'total_sent_transactions' => 'integer',
        'last_transaction_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_private_key',
    ];

    protected $appends = [
        'formatted_balance',
        'balance_in_thb',
    ];

    /**
     * Get formatted balance with currency decimals
     */
    public function getFormattedBalanceAttribute(): string
    {
        if (!$this->currency) {
            return number_format($this->balance, 8);
        }
        return $this->currency->formatAmount($this->balance);
    }

    /**
     * Get balance in THB
     */
    public function getBalanceInThbAttribute(): float
    {
        if ($this->balance <= 0 || !$this->currency) {
            return 0;
        }

        $rate = $this->currency->getCachedCurrentRate();
        if (!$rate) {
            return 0;
        }

        return $this->balance * $rate->rate_thb;
    }

    /**
     * Get decrypted private key (use with extreme caution!)
     */
    public function getDecryptedPrivateKey(): ?string
    {
        if (!$this->encrypted_private_key) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_private_key);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt private key', [
                'address_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set encrypted private key
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->update([
            'encrypted_private_key' => Crypt::encryptString($privateKey),
        ]);
    }

    /**
     * Update balance from blockchain
     */
    public function updateBalanceFromBlockchain(float $newBalance): void
    {
        $this->update([
            'balance' => $newBalance,
            'balance_updated_at' => now(),
        ]);
    }

    /**
     * Increment transaction counters
     */
    public function incrementReceived(): void
    {
        $this->increment('total_received_transactions');
        $this->update(['last_transaction_at' => now()]);
    }

    public function incrementSent(): void
    {
        $this->increment('total_sent_transactions');
        $this->update(['last_transaction_at' => now()]);
    }

    /**
     * Get masked address for display
     */
    public function getMaskedAddress(): string
    {
        if (strlen($this->address) <= 10) {
            return $this->address;
        }
        return substr($this->address, 0, 6) . '...' . substr($this->address, -4);
    }

    /**
     * Get block explorer URL
     */
    public function getExplorerUrl(): string
    {
        if (!$this->currency) {
            return '';
        }
        return $this->currency->getExplorerAddressUrl($this->address);
    }

    // ==================== Relationships ====================

    /**
     * Parent crypto wallet
     */
    public function cryptoWallet()
    {
        return $this->belongsTo(CryptoWallet::class);
    }

    /**
     * Currency of this address
     */
    public function currency()
    {
        return $this->belongsTo(CryptoCurrency::class, 'crypto_currency_id');
    }

    /**
     * User who owns this address
     */
    public function user()
    {
        return $this->cryptoWallet->user();
    }

    /**
     * Transactions for this address
     */
    public function transactions()
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    // ==================== Scopes ====================

    /**
     * Only active addresses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only watched addresses
     */
    public function scopeWatched($query)
    {
        return $query->where('is_watched', true);
    }

    /**
     * Filter by network
     */
    public function scopeNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Filter by currency
     */
    public function scopeCurrency($query, int $currencyId)
    {
        return $query->where('crypto_currency_id', $currencyId);
    }

    /**
     * Has balance
     */
    public function scopeHasBalance($query)
    {
        return $query->where('balance', '>', 0);
    }
}
