<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id',
        'software_quotation_id',
        'plan_number',
        'total_amount',
        'down_payment',
        'remaining_amount',
        'total_installments',
        'paid_installments',
        'installment_amount',
        'interest_rate',
        'total_interest',
        'total_paid',
        'status',
        'payment_frequency',
        'first_payment_date',
        'last_payment_date',
        'next_payment_date',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_installments' => 'integer',
        'paid_installments' => 'integer',
        'installment_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'first_payment_date' => 'date',
        'last_payment_date' => 'date',
        'next_payment_date' => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->plan_number)) {
                $plan->plan_number = 'IP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quotation
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SoftwareQuotation::class, 'software_quotation_id');
    }

    /**
     * Get all payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'installment_plan_id')->orderBy('installment_number');
    }

    /**
     * Get pending payments
     */
    public function pendingPayments(): HasMany
    {
        return $this->payments()->where('status', 'pending');
    }

    /**
     * Get paid payments
     */
    public function paidPayments(): HasMany
    {
        return $this->payments()->where('status', 'paid');
    }

    /**
     * Get overdue payments
     */
    public function overduePayments(): HasMany
    {
        return $this->payments()->where('status', 'overdue');
    }

    /**
     * Calculate installment payments
     */
    public function generatePayments(): void
    {
        // Calculate installment amount with interest
        $principalPerPayment = $this->remaining_amount / $this->total_installments;
        $interestPerPayment = ($this->remaining_amount * ($this->interest_rate / 100)) / $this->total_installments;
        $this->installment_amount = $principalPerPayment + $interestPerPayment;
        $this->total_interest = $interestPerPayment * $this->total_installments;

        // Generate payment records
        $currentDate = $this->first_payment_date ?? now()->addMonth();

        for ($i = 1; $i <= $this->total_installments; $i++) {
            InstallmentPayment::create([
                'installment_plan_id' => $this->id,
                'installment_number' => $i,
                'amount' => $this->installment_amount,
                'principal_amount' => $principalPerPayment,
                'interest_amount' => $interestPerPayment,
                'due_date' => $currentDate,
                'status' => 'pending',
            ]);

            // Calculate next payment date based on frequency
            $currentDate = match ($this->payment_frequency) {
                'monthly' => $currentDate->addMonth(),
                'quarterly' => $currentDate->addMonths(3),
                'yearly' => $currentDate->addYear(),
                default => $currentDate->addMonth(),
            };
        }

        $this->first_payment_date = $this->payments()->orderBy('due_date')->first()->due_date;
        $this->last_payment_date = $this->payments()->orderBy('due_date', 'desc')->first()->due_date;
        $this->next_payment_date = $this->first_payment_date;
        $this->save();
    }

    /**
     * Mark payment as paid
     */
    public function recordPayment(InstallmentPayment $payment, float $amount, string $paymentMethod, string $reference = null): void
    {
        $payment->paid_amount = $amount;
        $payment->payment_method = $paymentMethod;
        $payment->payment_reference = $reference;
        $payment->paid_at = now();
        $payment->status = 'paid';
        $payment->save();

        // Update plan
        $this->paid_installments += 1;
        $this->total_paid += $amount;

        // Find next pending payment
        $nextPayment = $this->payments()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();

        if ($nextPayment) {
            $this->next_payment_date = $nextPayment->due_date;
        } else {
            $this->next_payment_date = null;
        }

        // Check if completed
        if ($this->paid_installments >= $this->total_installments) {
            $this->status = 'completed';
            $this->completed_at = now();
        } else {
            $this->status = 'active';
        }

        $this->save();
    }

    /**
     * Check and update overdue status
     */
    public function checkOverdue(): void
    {
        $overduePayments = $this->payments()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overduePayments as $payment) {
            $payment->status = 'overdue';
            $payment->days_overdue = now()->diffInDays($payment->due_date);
            $payment->save();
        }

        if ($overduePayments->count() > 0) {
            $this->status = 'overdue';
            $this->save();
        }
    }

    /**
     * Cancel plan
     */
    public function cancel(string $reason = null): void
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Cancel all pending payments
        $this->payments()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_installments == 0) {
            return 0;
        }

        return ($this->paid_installments / $this->total_installments) * 100;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'active' => 'primary',
            'completed' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            'defaulted' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอชำระเงินดาวน์',
            'active' => 'กำลังผ่อนชำระ',
            'completed' => 'ชำระครบแล้ว',
            'overdue' => 'ค้างชำระ',
            'cancelled' => 'ยกเลิก',
            'defaulted' => 'ผิดนัด',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
