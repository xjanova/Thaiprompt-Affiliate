<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsPaymentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank',
        'type',
        'amount',
        'account_number',
        'sender_or_receiver',
        'reference_number',
        'sms_timestamp',
        'device_id',
        'nonce',
        'status',
        'matched_transaction_id',
        'raw_payload',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sms_timestamp' => 'datetime',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    // Relationships
    public function matchedTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'matched_transaction_id');
    }

    /**
     * Try to match this notification with a pending payment transaction.
     * Uses unique decimal amount matching.
     */
    public function attemptMatch(): bool
    {
        if ($this->type !== 'credit') {
            return false;
        }

        // Find matching unique amount
        $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now())
            ->first();

        if ($uniqueAmount) {
            // Match found
            $this->status = 'matched';
            $this->matched_transaction_id = $uniqueAmount->transaction_id;
            $this->save();

            $uniqueAmount->status = 'used';
            $uniqueAmount->matched_at = now();
            $uniqueAmount->save();

            // Auto-confirm the payment transaction
            if ($uniqueAmount->transaction_id) {
                $transaction = PaymentTransaction::find($uniqueAmount->transaction_id);
                if ($transaction && $transaction->status === 'pending') {
                    $transaction->markAsCompleted();
                }
            }

            return true;
        }

        // Fallback: try to match by exact amount and reference
        if ($this->reference_number) {
            $transaction = PaymentTransaction::where('promptpay_ref_no', $this->reference_number)
                ->where('status', 'pending')
                ->first();

            if ($transaction && abs((float)$transaction->amount - (float)$this->amount) < 0.01) {
                $this->status = 'matched';
                $this->matched_transaction_id = $transaction->id;
                $this->save();

                $transaction->markAsCompleted();
                return true;
            }
        }

        return false;
    }
}
