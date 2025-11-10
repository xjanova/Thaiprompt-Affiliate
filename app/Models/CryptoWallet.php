<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class CryptoWallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_type',
        'is_master_wallet',
        'master_wallet_id',
        'name',
        'encrypted_seed',
        'master_seed_encrypted',
        'derivation_path',
        'derivation_index',
        'total_derived_wallets',
        'pin_hash',
        'two_factor_enabled',
        'two_factor_secret',
        'status',
        'locked_until',
        'failed_attempts',
        'is_default',
        'device_info',
        'ip_address',
        'user_agent',
        'metadata',
        'is_verified',
        'verified_at',
        'signature_proof',
        'last_activity_at',
        'total_transactions',
    ];

    protected $casts = [
        'is_master_wallet' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_activity_at' => 'datetime',
        'failed_attempts' => 'integer',
        'total_transactions' => 'integer',
        'derivation_index' => 'integer',
        'total_derived_wallets' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'encrypted_seed',
        'master_seed_encrypted',
        'pin_hash',
        'two_factor_secret',
    ];

    /**
     * Check if wallet is custodial (system-managed)
     */
    public function isCustodial(): bool
    {
        return $this->wallet_type === 'custodial';
    }

    /**
     * Check if wallet is a master wallet
     */
    public function isMasterWallet(): bool
    {
        return $this->is_master_wallet === true;
    }

    /**
     * Check if wallet is a child wallet (derived from master)
     */
    public function isChildWallet(): bool
    {
        return !$this->is_master_wallet && $this->master_wallet_id !== null;
    }

    /**
     * Check if wallet is external (MetaMask, WalletConnect)
     */
    public function isExternal(): bool
    {
        return $this->wallet_type === 'external';
    }

    /**
     * Check if wallet is locked
     */
    public function isLocked(): bool
    {
        if ($this->status === 'locked' && $this->locked_until) {
            if (now()->lessThan($this->locked_until)) {
                return true;
            }
            // Auto-unlock if time has passed
            $this->unlock();
            return false;
        }
        return $this->status === 'locked';
    }

    /**
     * Check if wallet is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isLocked();
    }

    /**
     * Lock the wallet
     */
    public function lock(int $minutes = 30): void
    {
        $this->update([
            'status' => 'locked',
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock the wallet
     */
    public function unlock(): void
    {
        $this->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_attempts' => 0,
        ]);
    }

    /**
     * Increment failed PIN attempts
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_attempts');

        // Lock after 5 failed attempts
        if ($this->failed_attempts >= 5) {
            $this->lock(30); // Lock for 30 minutes
        }
    }

    /**
     * Reset failed attempts
     */
    public function resetFailedAttempts(): void
    {
        $this->update(['failed_attempts' => 0]);
    }

    /**
     * Verify PIN
     */
    public function verifyPin(string $pin): bool
    {
        if (!$this->pin_hash) {
            return false;
        }

        if (Hash::check($pin, $this->pin_hash)) {
            $this->resetFailedAttempts();
            return true;
        }

        $this->incrementFailedAttempts();
        return false;
    }

    /**
     * Set PIN
     */
    public function setPin(string $pin): void
    {
        $this->update([
            'pin_hash' => Hash::make($pin),
        ]);
    }

    /**
     * Get decrypted seed phrase (use with caution!)
     */
    public function getDecryptedSeed(): ?string
    {
        if (!$this->encrypted_seed) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_seed);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt seed phrase', [
                'wallet_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set encrypted seed phrase
     */
    public function setSeedPhrase(string $seedPhrase): void
    {
        $this->update([
            'encrypted_seed' => Crypt::encryptString($seedPhrase),
        ]);
    }

    /**
     * Update activity timestamp
     */
    public function touchActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Set as default wallet
     */
    public function setAsDefault(): void
    {
        // Remove default from other wallets
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get total balance across all addresses
     */
    public function getTotalBalance(CryptoCurrency $currency)
    {
        return $this->cryptoAddresses()
            ->where('crypto_currency_id', $currency->id)
            ->sum('balance');
    }

    /**
     * Get total balance in THB
     */
    public function getTotalBalanceInTHB()
    {
        $total = 0;
        foreach ($this->cryptoAddresses as $address) {
            if ($address->balance > 0) {
                $rate = $address->currency->getCachedCurrentRate();
                if ($rate) {
                    $total += $address->balance * $rate->rate_thb;
                }
            }
        }
        return $total;
    }

    // ==================== Relationships ====================

    /**
     * Wallet owner
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Master wallet (for child wallets)
     */
    public function masterWallet()
    {
        return $this->belongsTo(CryptoWallet::class, 'master_wallet_id');
    }

    /**
     * Child wallets (for master wallets)
     */
    public function childWallets()
    {
        return $this->hasMany(CryptoWallet::class, 'master_wallet_id')
            ->orderBy('derivation_index');
    }

    /**
     * Crypto addresses for this wallet
     */
    public function cryptoAddresses()
    {
        return $this->hasMany(CryptoAddress::class);
    }

    /**
     * All transactions
     */
    public function cryptoTransactions()
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    /**
     * Withdrawal requests
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(CryptoWithdrawalRequest::class);
    }

    /**
     * Deposit addresses
     */
    public function depositAddresses()
    {
        return $this->hasMany(CryptoDepositAddress::class);
    }

    /**
     * Exchange transactions from crypto
     */
    public function exchangeTransactionsFrom()
    {
        return $this->hasMany(CryptoExchangeTransaction::class, 'from_crypto_wallet_id');
    }

    /**
     * Exchange transactions to crypto
     */
    public function exchangeTransactionsTo()
    {
        return $this->hasMany(CryptoExchangeTransaction::class, 'to_crypto_wallet_id');
    }

    // ==================== Scopes ====================

    /**
     * Only active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Only custodial wallets
     */
    public function scopeCustodial($query)
    {
        return $query->where('wallet_type', 'custodial');
    }

    /**
     * Only external wallets
     */
    public function scopeExternal($query)
    {
        return $query->where('wallet_type', 'external');
    }

    /**
     * Only verified wallets
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Only master wallets
     */
    public function scopeMasterWallets($query)
    {
        return $query->where('is_master_wallet', true);
    }

    /**
     * Only child wallets
     */
    public function scopeChildWallets($query)
    {
        return $query->where('is_master_wallet', false)
            ->whereNotNull('master_wallet_id');
    }
}
