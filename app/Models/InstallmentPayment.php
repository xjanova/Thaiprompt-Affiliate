<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_plan_id',
        'payment_number',
        'installment_number',
        'amount',
        'principal_amount',
        'interest_amount',
        'late_fee',
        'paid_amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'payment_reference',
        'payment_note',
        'receipt_url',
        'days_overdue',
        'reminder_sent_at',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'days_overdue' => 'integer',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'PMT-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    /**
     * Get the installment plan
     */
    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(float $amount, string $paymentMethod, string $reference = null, string $note = null): void
    {
        $this->paid_amount = $amount;
        $this->payment_method = $paymentMethod;
        $this->payment_reference = $reference;
        $this->payment_note = $note;
        $this->paid_at = now();
        $this->status = $amount >= $this->amount ? 'paid' : 'partial';
        $this->save();

        // Update installment plan
        $this->installmentPlan->recordPayment($this, $amount, $paymentMethod, $reference);
    }

    /**
     * Calculate late fee
     */
    public function calculateLateFee(float $lateFeePercentage = 2): void
    {
        if ($this->status === 'overdue' && $this->days_overdue > 0) {
            $this->late_fee = $this->amount * ($lateFeePercentage / 100) * ($this->days_overdue / 30);
            $this->save();
        }
    }

    /**
     * Check if overdue
     */
    public function checkIfOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date < now()) {
            $this->status = 'overdue';
            $this->days_overdue = now()->diffInDays($this->due_date);
            $this->save();
        }
    }

    /**
     * Send reminder
     */
    public function sendReminder(): void
    {
        // TODO: Implement email/notification sending
        $this->reminder_sent_at = now();
        $this->save();
    }

    /**
     * Get total amount due (including late fee)
     */
    public function getTotalDueAttribute(): float
    {
        return $this->amount + $this->late_fee;
    }

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_due - $this->paid_amount;
    }

    /**
     * Check if fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->total_due;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'partial' => 'info',
            'cancelled' => 'secondary',
            'refunded' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอชำระ',
            'paid' => 'ชำระแล้ว',
            'overdue' => 'เกินกำหนด',
            'partial' => 'ชำระบางส่วน',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงิน',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }
}
