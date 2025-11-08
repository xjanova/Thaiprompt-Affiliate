<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoDepositAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'crypto_wallet_id',
        'crypto_currency_id',
        'address',
        'network',
        'qr_code',
        'is_monitored',
        'last_checked_at',
        'last_checked_block',
        'total_deposits',
        'total_amount_received',
        'last_deposit_at',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'label',
        'metadata',
    ];

    protected $casts = [
        'is_monitored' => 'boolean',
        'last_checked_at' => 'datetime',
        'total_deposits' => 'integer',
        'total_amount_received' => 'decimal:18',
        'last_deposit_at' => 'datetime',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'masked_address',
        'qr_code_url',
    ];

    /**
     * Get masked address
     */
    public function getMaskedAddressAttribute(): string
    {
        if (strlen($this->address) <= 10) {
            return $this->address;
        }
        return substr($this->address, 0, 6) . '...' . substr($this->address, -4);
    }

    /**
     * Get QR code URL or data
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code;
    }

    /**
     * Check if monitoring is active
     */
    public function isMonitored(): bool
    {
        return $this->is_monitored && $this->is_active;
    }

    /**
     * Check if address is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Activate address
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'is_monitored' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ]);
    }

    /**
     * Deactivate address
     */
    public function deactivate(string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'is_monitored' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);
    }

    /**
     * Update last checked timestamp and block
     */
    public function updateLastChecked(?string $blockNumber = null): void
    {
        $data = [
            'last_checked_at' => now(),
        ];

        if ($blockNumber !== null) {
            $data['last_checked_block'] = $blockNumber;
        }

        $this->update($data);
    }

    /**
     * Record a deposit
     */
    public function recordDeposit(float $amount): void
    {
        $this->increment('total_deposits');
        $this->increment('total_amount_received', $amount);
        $this->update([
            'last_deposit_at' => now(),
        ]);
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

    /**
     * Generate QR code for this address
     */
    public function generateQRCode(): string
    {
        // This would use a QR code library like SimpleSoftwareIO/simple-qrcode
        // For now, we'll return a placeholder
        // In production, use: QrCode::format('svg')->size(300)->generate($this->address);

        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(300)
                    ->generate($this->address);

                $this->update(['qr_code' => $qrCode]);
                return $qrCode;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to generate QR code', [
                'address_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }

    // ==================== Relationships ====================

    /**
     * User who owns this deposit address
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crypto wallet
     */
    public function cryptoWallet()
    {
        return $this->belongsTo(CryptoWallet::class);
    }

    /**
     * Cryptocurrency
     */
    public function currency()
    {
        return $this->belongsTo(CryptoCurrency::class, 'crypto_currency_id');
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
     * Only monitored addresses
     */
    public function scopeMonitored($query)
    {
        return $query->where('is_monitored', true)->where('is_active', true);
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
     * Needs checking (not checked recently)
     */
    public function scopeNeedsChecking($query, int $minutes = 5)
    {
        return $query->monitored()
            ->where(function ($q) use ($minutes) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subMinutes($minutes));
            });
    }

    /**
     * Has received deposits
     */
    public function scopeHasDeposits($query)
    {
        return $query->where('total_deposits', '>', 0);
    }
}
