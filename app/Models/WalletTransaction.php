<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'related_wallet_id',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN' . strtoupper(Str::random(20));
            }
            if (empty($transaction->ip_address)) {
                $transaction->ip_address = request()->ip();
            }
            if (empty($transaction->user_agent)) {
                $transaction->user_agent = request()->userAgent();
            }
        });
    }

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related wallet (for transfers)
     */
    public function relatedWallet()
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    /**
     * Get transaction type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'deposit' => '💵',
            'withdrawal' => '💸',
            'transfer_in' => '📥',
            'transfer_out' => '📤',
            'commission' => '💰',
            'refund' => '↩️',
            'fee' => '💳',
            'bonus' => '🎁',
            default => '📊',
        };
    }

    /**
     * Get transaction type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'deposit' => 'ฝากเงิน',
            'withdrawal' => 'ถอนเงิน',
            'transfer_in' => 'รับโอน',
            'transfer_out' => 'โอนออก',
            'commission' => 'คอมมิชชั่น',
            'refund' => 'คืนเงิน',
            'fee' => 'ค่าธรรมเนียม',
            'bonus' => 'โบนัส',
            default => 'อื่นๆ',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'processing' => 'yellow',
            'pending' => 'blue',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'สำเร็จ',
            'processing' => 'กำลังดำเนินการ',
            'pending' => 'รอดำเนินการ',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Format amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = in_array($this->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? '+' : '-';
        return $sign . number_format(abs($this->amount), 2) . ' ' . $this->currency;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
