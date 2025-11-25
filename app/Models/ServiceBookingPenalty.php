<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceBookingPenalty Model
 *
 * บันทึกค่าปรับ/บทลงโทษจากการละเมิดกฎ
 *
 * @property int $id
 * @property int $service_booking_id
 * @property string $penalty_type
 * @property float $penalty_amount
 * @property string $status
 */
class ServiceBookingPenalty extends Model
{
    protected $fillable = [
        'service_booking_id',
        'user_id',
        'provider_id',
        'penalized_type',
        'penalty_type',
        'penalty_amount',
        'currency',
        'trust_score_deduction',
        'status',
        'reason',
        'notes',
        'charged_at',
        'paid_at',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'trust_score_deduction' => 'decimal:2',
        'charged_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * ประเภทค่าปรับ
     */
    public const PENALTY_TYPES = [
        'late_cancellation' => 'ยกเลิกสาย',
        'no_show' => 'ไม่มาตามนัด',
        'payment_default' => 'ไม่จ่ายเงิน',
        'service_incomplete' => 'บริการไม่ครบ',
        'dispute_lost' => 'แพ้ข้อพิพาท',
        'policy_violation' => 'ละเมิดนโยบาย',
        'fraud' => 'ฉ้อโกง',
        'abuse' => 'กลั่นแกล้ง',
    ];

    /**
     * ความสัมพันธ์กับ Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * ดึง label ประเภทค่าปรับ
     */
    public function getPenaltyTypeLabel(): string
    {
        return self::PENALTY_TYPES[$this->penalty_type] ?? $this->penalty_type;
    }

    /**
     * Scope: กรองตามสถานะ pending
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * บันทึกการหักเงิน
     */
    public function markAsCharged(string $paymentMethod, ?string $transactionId = null): void
    {
        $this->update([
            'status' => 'charged',
            'charged_at' => now(),
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * บันทึกการจ่ายเงิน
     */
    public function markAsPaid(string $paymentMethod, ?string $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * ยกเว้นค่าปรับ
     */
    public function waive(string $reason): void
    {
        $this->update([
            'status' => 'waived',
            'notes' => $reason,
        ]);
    }
}
