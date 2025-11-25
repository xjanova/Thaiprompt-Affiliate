<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceBookingDispute Model
 *
 * จัดการข้อพิพาท/ร้องเรียนระหว่างผู้ใช้และผู้ให้บริการ
 *
 * @property int $id
 * @property string $dispute_number
 * @property int $service_booking_id
 * @property string $dispute_type
 * @property string $status
 * @property string $priority
 */
class ServiceBookingDispute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dispute_number',
        'service_booking_id',
        'reporter_user_id',
        'reporter_provider_id',
        'reporter_type',
        'accused_user_id',
        'accused_provider_id',
        'accused_type',
        'dispute_type',
        'title',
        'description',
        'evidence_files',
        'location_evidence',
        'status',
        'resolution_notes',
        'refund_amount',
        'penalty_amount',
        'resulted_in_ban',
        'handled_by',
        'resolved_at',
        'priority',
    ];

    protected $casts = [
        'evidence_files' => 'array',
        'location_evidence' => 'array',
        'refund_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'resulted_in_ban' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * ประเภทการร้องเรียน
     */
    public const DISPUTE_TYPES = [
        'no_show' => 'ไม่มาตามนัด',
        'late_cancellation' => 'ยกเลิกกระทันหัน',
        'payment_issue' => 'ปัญหาการชำระเงิน',
        'service_quality' => 'คุณภาพบริการไม่ดี',
        'fraud' => 'การฉ้อโกง',
        'harassment' => 'คุกคาม/ล่วงละเมิด',
        'safety_concern' => 'ปัญหาความปลอดภัย',
        'property_damage' => 'ทำทรัพย์สินเสียหาย',
        'overcharge' => 'เก็บเงินเกิน',
        'fake_location' => 'ปลอมตำแหน่ง GPS',
        'other' => 'อื่นๆ',
    ];

    /**
     * สถานะข้อพิพาท
     */
    public const STATUSES = [
        'pending' => 'รอตรวจสอบ',
        'investigating' => 'กำลังตรวจสอบ',
        'awaiting_response' => 'รอคำชี้แจง',
        'resolved_favor_reporter' => 'ตัดสินให้ผู้ร้อง',
        'resolved_favor_accused' => 'ตัดสินให้ผู้ถูกร้อง',
        'resolved_mutual' => 'ยอมความ',
        'dismissed' => 'ยกฟ้อง',
        'escalated' => 'ส่งต่อ',
    ];

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispute) {
            if (empty($dispute->dispute_number)) {
                $dispute->dispute_number = 'DIS-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    /**
     * ความสัมพันธ์กับ Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    /**
     * ผู้ร้องเรียน (User)
     */
    public function reporterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /**
     * ผู้ร้องเรียน (Provider)
     */
    public function reporterProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'reporter_provider_id');
    }

    /**
     * ผู้ถูกร้องเรียน (User)
     */
    public function accusedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accused_user_id');
    }

    /**
     * ผู้ถูกร้องเรียน (Provider)
     */
    public function accusedProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'accused_provider_id');
    }

    /**
     * Admin ที่จัดการ
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * ดึงชื่อประเภทร้องเรียน
     */
    public function getDisputeTypeLabel(): string
    {
        return self::DISPUTE_TYPES[$this->dispute_type] ?? $this->dispute_type;
    }

    /**
     * ดึงชื่อสถานะ
     */
    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Scope: กรองตามสถานะที่ยังเปิดอยู่
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'investigating', 'awaiting_response']);
    }

    /**
     * Scope: กรองตาม priority สูง
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    /**
     * ตรวจสอบว่ายังเปิดอยู่หรือไม่
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'investigating', 'awaiting_response']);
    }

    /**
     * ตัดสินให้ผู้ร้อง
     */
    public function resolveForReporter(string $notes, float $refundAmount = 0, float $penaltyAmount = 0): void
    {
        $this->update([
            'status' => 'resolved_favor_reporter',
            'resolution_notes' => $notes,
            'refund_amount' => $refundAmount,
            'penalty_amount' => $penaltyAmount,
            'resolved_at' => now(),
        ]);
    }

    /**
     * ตัดสินให้ผู้ถูกร้อง
     */
    public function resolveForAccused(string $notes): void
    {
        $this->update([
            'status' => 'resolved_favor_accused',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }
}
