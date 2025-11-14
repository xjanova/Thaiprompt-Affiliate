<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NFCTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nfc_transactions';

    protected $fillable = [
        'transaction_id',
        'nfc_card_id',
        'user_id',
        'nfc_reader_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'status',
        'status_message',
        'card_data_hash',
        'encryption_verified',
        'verification_signature',
        'verification_attempts',
        'payment_transaction_id',
        'wallet_transaction_id',
        'reference_id',
        'receipt_number',
        'location',
        'ip_address',
        'user_agent',
        'device_info',
        'description',
        'metadata',
        'error_message',
        'notes',
        'processed_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'encryption_verified' => 'boolean',
        'verification_attempts' => 'integer',
        'device_info' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Transaction Types
     */
    const TYPE_PAYMENT = 'payment';
    const TYPE_TOPUP = 'topup';
    const TYPE_REFUND = 'refund';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_VERIFICATION = 'verification';

    /**
     * Transaction Status
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = self::generateTransactionId();
            }

            if (!$transaction->receipt_number) {
                $transaction->receipt_number = self::generateReceiptNumber();
            }
        });
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        do {
            $id = 'NFC' . strtoupper(Str::random(10)) . time();
        } while (self::where('transaction_id', $id)->exists());

        return $id;
    }

    /**
     * Generate unique receipt number
     */
    public static function generateReceiptNumber(): string
    {
        do {
            $number = 'RCP' . date('Ymd') . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('receipt_number', $number)->exists());

        return $number;
    }

    /**
     * Get the NFC card
     */
    public function nfcCard(): BelongsTo
    {
        return $this->belongsTo(NFCCard::class, 'nfc_card_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the NFC reader
     */
    public function nfcReader(): BelongsTo
    {
        return $this->belongsTo(NFCReader::class, 'nfc_reader_id');
    }

    /**
     * Get the related payment transaction
     */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Get the related wallet transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Check if encryption is verified
     */
    public function isEncryptionVerified(): bool
    {
        return $this->encryption_verified === true;
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(string $message = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'status_message' => $message,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $message = null, string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'status_message' => $message,
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(string $message = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'status_message' => $message,
        ]);
    }

    /**
     * Mark as refunded
     */
    public function markAsRefunded(): bool
    {
        return $this->update([
            'status' => self::STATUS_REFUNDED,
        ]);
    }

    /**
     * Mark encryption as verified
     */
    public function markEncryptionVerified(string $signature): bool
    {
        return $this->update([
            'encryption_verified' => true,
            'verification_signature' => $signature,
        ]);
    }

    /**
     * Increment verification attempts
     */
    public function incrementVerificationAttempts(): void
    {
        $this->increment('verification_attempts');
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'green',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'สำเร็จ',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_FAILED => 'ล้มเหลว',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_REFUNDED => 'คืนเงิน',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PAYMENT => 'ชำระเงิน',
            self::TYPE_TOPUP => 'เติมเงิน',
            self::TYPE_REFUND => 'คืนเงิน',
            self::TYPE_TRANSFER => 'โอนเงิน',
            self::TYPE_VERIFICATION => 'ตรวจสอบ',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for specific card
     */
    public function scopeForCard($query, int $cardId)
    {
        return $query->where('nfc_card_id', $cardId);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific reader
     */
    public function scopeForReader($query, int $readerId)
    {
        return $query->where('nfc_reader_id', $readerId);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
                     ->whereMonth('created_at', now()->month);
    }
}
