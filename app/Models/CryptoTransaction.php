<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CryptoTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'crypto_wallet_id',
        'crypto_currency_id',
        'crypto_address_id',
        'transaction_id',
        'tx_hash',
        'network',
        'from_address',
        'to_address',
        'block_number',
        'block_timestamp',
        'type',
        'amount',
        'fee',
        'amount_thb',
        'exchange_rate',
        'status',
        'confirmations',
        'confirmations_required',
        'gas_price',
        'gas_limit',
        'gas_used',
        'nonce',
        'payment_transaction_id',
        'wallet_transaction_id',
        'description',
        'admin_note',
        'error_message',
        'metadata',
        'ip_address',
        'user_agent',
        'requires_review',
        'is_suspicious',
        'reviewed_at',
        'reviewed_by',
        'broadcasted_at',
        'confirmed_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'fee' => 'decimal:18',
        'amount_thb' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'confirmations' => 'integer',
        'confirmations_required' => 'integer',
        'block_number' => 'integer',
        'block_timestamp' => 'datetime',
        'requires_review' => 'boolean',
        'is_suspicious' => 'boolean',
        'reviewed_at' => 'datetime',
        'broadcasted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'formatted_amount',
        'is_incoming',
        'is_confirmed',
        'confirmation_progress',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'CRTX' . strtoupper(Str::random(20));
            }
        });
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        if (!$this->currency) {
            return number_format($this->amount, 8);
        }
        return $this->currency->formatAmount($this->amount);
    }

    /**
     * Check if transaction is incoming
     */
    public function getIsIncomingAttribute(): bool
    {
        return in_array($this->type, ['deposit', 'transfer_in', 'exchange_buy', 'refund']);
    }

    /**
     * Check if transaction is confirmed
     */
    public function getIsConfirmedAttribute(): bool
    {
        return $this->confirmations >= $this->confirmations_required;
    }

    /**
     * Get confirmation progress percentage
     */
    public function getConfirmationProgressAttribute(): int
    {
        if ($this->confirmations_required <= 0) {
            return 100;
        }
        return min(100, (int)(($this->confirmations / $this->confirmations_required) * 100));
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'broadcasted', 'confirming']);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as broadcasted
     */
    public function markAsBroadcasted(string $txHash): void
    {
        $this->update([
            'tx_hash' => $txHash,
            'status' => 'broadcasted',
            'broadcasted_at' => now(),
        ]);
    }

    /**
     * Update confirmations
     */
    public function updateConfirmations(int $confirmations, ?int $blockNumber = null): void
    {
        $data = [
            'confirmations' => $confirmations,
        ];

        if ($blockNumber) {
            $data['block_number'] = $blockNumber;
        }

        // Change status based on confirmations
        if ($confirmations > 0 && $this->status === 'broadcasted') {
            $data['status'] = 'confirming';
        }

        if ($confirmations >= $this->confirmations_required && $this->status !== 'completed') {
            $data['status'] = 'confirmed';
            $data['confirmed_at'] = now();
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
     * Get block explorer URL
     */
    public function getExplorerUrl(): string
    {
        if (!$this->tx_hash || !$this->currency) {
            return '';
        }
        return $this->currency->getExplorerTxUrl($this->tx_hash);
    }

    /**
     * Get total amount including fee
     */
    public function getTotalAmount(): float
    {
        return $this->amount + $this->fee;
    }

    // ==================== Relationships ====================

    /**
     * User who made the transaction
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
     * Crypto currency
     */
    public function currency()
    {
        return $this->belongsTo(CryptoCurrency::class, 'crypto_currency_id');
    }

    /**
     * Crypto address
     */
    public function cryptoAddress()
    {
        return $this->belongsTo(CryptoAddress::class);
    }

    /**
     * Related payment transaction
     */
    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    /**
     * Related wallet transaction (for exchange)
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Reviewer (admin)
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==================== Scopes ====================

    /**
     * Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Pending transactions
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'broadcasted', 'confirming']);
    }

    /**
     * Completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Requires review
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true)->whereNull('reviewed_at');
    }

    /**
     * Suspicious transactions
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Filter by network
     */
    public function scopeNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Incoming transactions (deposits, transfers in, etc.)
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('type', ['deposit', 'transfer_in', 'exchange_buy', 'refund']);
    }

    /**
     * Outgoing transactions (withdrawals, transfers out, etc.)
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('type', ['withdrawal', 'transfer_out', 'exchange_sell', 'payment', 'gas_fee']);
    }
}
