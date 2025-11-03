<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalTransaction extends Model
{
    use HasFactory;

    protected $table = 'ai_rental_transactions';

    protected $fillable = [
        'rental_id',
        'user_id',
        'type',
        'amount',
        'platform_commission',
        'owner_earning',
        'message_count',
        'rate_per_message',
        'period_start',
        'period_end',
        'payment_method',
        'payment_reference',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'platform_commission' => 'decimal:4',
        'owner_earning' => 'decimal:4',
        'message_count' => 'integer',
        'rate_per_message' => 'decimal:4',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the rental this transaction belongs to
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(BotRental::class, 'rental_id');
    }

    /**
     * Get the user who made this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get owner earning record
     */
    public function ownerEarning(): BelongsTo
    {
        return $this->belongsTo(OwnerEarning::class, 'transaction_id');
    }

    /**
     * Scope: Completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Subscription transactions
     */
    public function scopeSubscription($query)
    {
        return $query->where('type', 'subscription');
    }

    /**
     * Scope: Usage transactions
     */
    public function scopeUsage($query)
    {
        return $query->where('type', 'usage');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'สำเร็จ',
            'pending' => 'รอดำเนินการ',
            'failed' => 'ล้มเหลว',
            'refunded' => 'คืนเงินแล้ว',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'subscription' => 'สมัครสมาชิก',
            'usage' => 'ค่าใช้งาน',
            'refund' => 'คืนเงิน',
            default => 'อื่นๆ',
        };
    }
}
