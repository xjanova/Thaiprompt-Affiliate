<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingPayment extends Model
{
    protected $fillable = [
        'user_id',
        'payable_type',
        'payable_id',
        'payment_type',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'bank_account_id',
        'reference',
        'note',
        'receipt_image',
        'flowaccount_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber($payment->user_id, $payment->payment_type);
            }
        });
    }

    public static function generatePaymentNumber($userId, $paymentType): string
    {
        $prefix = $paymentType === 'income' ? 'PAY-IN' : 'PAY-OUT';
        $year = date('y');
        $month = date('m');

        $count = self::where('user_id', $userId)
            ->where('payment_type', $paymentType)
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;

        return $prefix . $year . $month . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingBankAccount::class, 'bank_account_id');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_image ? asset('storage/' . $this->receipt_image) : null;
    }
}
