<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'type',
        'payment_method',
        'status',
        'amount',
        'currency',
        'order_id',
        'wallet_transaction_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'promptpay_qr_code',
        'promptpay_ref_no',
        'metadata',
        'notes',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = 'TXN-' . strtoupper(Str::random(12));
            }
        });
    }

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the wallet transaction
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired()
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
}
