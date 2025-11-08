<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CryptoExchangeTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'exchange_id',
        'type',
        'from_currency_type',
        'from_currency_code',
        'from_amount',
        'from_wallet_id',
        'from_crypto_wallet_id',
        'to_currency_type',
        'to_currency_code',
        'to_amount',
        'to_wallet_id',
        'to_crypto_wallet_id',
        'exchange_rate',
        'crypto_exchange_rate_id',
        'fee_percentage',
        'fee_amount',
        'fee_currency',
        'network_fee',
        'final_from_amount',
        'final_to_amount',
        'status',
        'wallet_transaction_id',
        'crypto_transaction_id',
        'max_slippage_percentage',
        'actual_slippage',
        'executed_at',
        'completed_at',
        'failed_at',
        'error_message',
        'description',
        'admin_note',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'from_amount' => 'decimal:18',
        'to_amount' => 'decimal:18',
        'exchange_rate' => 'decimal:2',
        'fee_percentage' => 'decimal:4',
        'fee_amount' => 'decimal:2',
        'network_fee' => 'decimal:18',
        'final_from_amount' => 'decimal:18',
        'final_to_amount' => 'decimal:18',
        'max_slippage_percentage' => 'decimal:4',
        'actual_slippage' => 'decimal:4',
        'executed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->exchange_id)) {
                $transaction->exchange_id = 'EXG' . strtoupper(Str::random(20));
            }
        });
    }

    /**
     * Check if buying crypto (THB → Crypto)
     */
    public function isBuy(): bool
    {
        return $this->type === 'buy';
    }

    /**
     * Check if selling crypto (Crypto → THB)
     */
    public function isSell(): bool
    {
        return $this->type === 'sell';
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Mark as executed
     */
    public function markAsExecuted(?float $actualSlippage = null): void
    {
        $data = [
            'executed_at' => now(),
        ];

        if ($actualSlippage !== null) {
            $data['actual_slippage'] = $actualSlippage;
        }

        $this->update($data);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Check if slippage is acceptable
     */
    public function isSlippageAcceptable(float $currentRate): bool
    {
        $originalRate = $this->exchange_rate;
        $slippage = abs(($currentRate - $originalRate) / $originalRate * 100);

        return $slippage <= $this->max_slippage_percentage;
    }

    /**
     * Calculate actual slippage
     */
    public function calculateSlippage(float $currentRate): float
    {
        $originalRate = $this->exchange_rate;
        return abs(($currentRate - $originalRate) / $originalRate * 100);
    }

    /**
     * Get display summary
     */
    public function getSummary(): string
    {
        $from = number_format($this->from_amount, 2) . ' ' . $this->from_currency_code;
        $to = number_format($this->to_amount, 8) . ' ' . $this->to_currency_code;

        return "{$from} → {$to}";
    }

    // ==================== Relationships ====================

    /**
     * User who made the exchange
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * From THB wallet
     */
    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    /**
     * From crypto wallet
     */
    public function fromCryptoWallet()
    {
        return $this->belongsTo(CryptoWallet::class, 'from_crypto_wallet_id');
    }

    /**
     * To THB wallet
     */
    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * To crypto wallet
     */
    public function toCryptoWallet()
    {
        return $this->belongsTo(CryptoWallet::class, 'to_crypto_wallet_id');
    }

    /**
     * Exchange rate used
     */
    public function exchangeRate()
    {
        return $this->belongsTo(CryptoExchangeRate::class, 'crypto_exchange_rate_id');
    }

    /**
     * Related THB wallet transaction
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Related crypto transaction
     */
    public function cryptoTransaction()
    {
        return $this->belongsTo(CryptoTransaction::class);
    }

    // ==================== Scopes ====================

    /**
     * Filter by type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Buy transactions only
     */
    public function scopeBuy($query)
    {
        return $query->where('type', 'buy');
    }

    /**
     * Sell transactions only
     */
    public function scopeSell($query)
    {
        return $query->where('type', 'sell');
    }

    /**
     * Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Pending exchanges
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Processing exchanges
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Completed exchanges
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Failed exchanges
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Filter by currency
     */
    public function scopeCurrency($query, string $currencyCode)
    {
        return $query->where(function ($q) use ($currencyCode) {
            $q->where('from_currency_code', $currencyCode)
              ->orWhere('to_currency_code', $currencyCode);
        });
    }
}
