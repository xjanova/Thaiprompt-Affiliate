<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'request_id',
        'amount',
        'fee',
        'tax',
        'net_amount',
        'currency',
        'status',
        'payment_method_id',
        'payment_type',
        'payment_details',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'transfer_slip',
        'transfer_completed_at',
        'transfer_note',
        'user_note',
        'admin_note',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_details' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'transfer_completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_id)) {
                $request->request_id = 'WDR' . strtoupper(Str::random(20));
            }
            if (empty($request->ip_address)) {
                $request->ip_address = request()->ip();
            }
            if (empty($request->user_agent)) {
                $request->user_agent = request()->userAgent();
            }
        });
    }

    /**
     * Get the user that owns the withdrawal request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the payment method
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the admin who approved
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who rejected
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if request is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังดำเนินการ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'cancelled' => 'ยกเลิก',
            'completed' => 'สำเร็จ',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            'completed' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get payment type label
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'promptpay' => 'พร้อมเพย์',
            'bank_transfer' => 'โอนผ่านธนาคาร',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            default => 'อื่นๆ',
        };
    }

    /**
     * Format amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Format net amount with currency
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get transfer slip URL
     */
    public function getTransferSlipUrlAttribute(): ?string
    {
        return $this->transfer_slip ? asset('storage/' . $this->transfer_slip) : null;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing requests
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
