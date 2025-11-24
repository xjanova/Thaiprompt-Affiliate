<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceBooking Model - การจองบริการ
 *
 * ตารางหลักสำหรับเก็บข้อมูลการจองบริการทั้งหมด
 * รวมถึงระบบ notification, accept/reject, payment, tracking
 *
 * @property int $id
 * @property string $booking_number เลขที่จอง
 * @property int $user_id ลูกค้า
 * @property int|null $service_id บริการเดี่ยว
 * @property int|null $package_id แพคเกจ
 * @property int|null $provider_id ผู้ให้บริการ
 * @property \Carbon\Carbon $scheduled_at วันเวลานัดหมาย
 * @property int $service_duration_minutes ระยะเวลาบริการ
 * @property float $base_price ราคาบริการ
 * @property float $distance_km ระยะทาง
 * @property float $distance_price ค่าเดินทาง
 * @property float $options_price ค่าออฟชั่น
 * @property float $subtotal รวมย่อย
 * @property float $tax_amount ภาษี
 * @property float $discount_amount ส่วนลด
 * @property float $total_amount ราคารวมสุทธิ
 * @property string|null $payment_method วิธีชำระเงิน
 * @property string $payment_status สถานะการชำระเงิน
 * @property int|null $payment_transaction_id รายการชำระเงิน
 * @property \Carbon\Carbon|null $provider_notified_at เวลาแจ้งเตือน provider
 * @property \Carbon\Carbon|null $provider_response_deadline deadline ตอบกลับ
 * @property \Carbon\Carbon|null $provider_viewed_at เวลาที่ดู
 * @property \Carbon\Carbon|null $provider_accepted_at เวลารับงาน
 * @property \Carbon\Carbon|null $provider_rejected_at เวลาปฏิเสธ
 * @property string|null $provider_rejected_reason เหตุผลปฏิเสธ
 * @property int|null $provider_response_minutes เวลาตอบกลับ (นาที)
 * @property string $status สถานะการจอง
 * @property string|null $customer_notes หมายเหตุลูกค้า
 * @property string|null $provider_notes หมายเหตุ provider
 * @property string|null $admin_notes หมายเหตุ admin
 * @property string|null $cancellation_reason เหตุผลยกเลิก
 * @property \Carbon\Carbon|null $cancelled_at เวลายกเลิก
 * @property string|null $cancelled_by ยกเลิกโดย
 * @property \Carbon\Carbon|null $confirmed_at เวลายืนยัน
 * @property \Carbon\Carbon|null $started_at เวลาเริ่ม
 * @property \Carbon\Carbon|null $completed_at เวลาเสร็จ
 * @property float $commission_amount คอมมิชชั่น MLM
 * @property float $platform_fee_percentage ค่าแพลตฟอร์ม (%)
 * @property float $platform_fee_amount ค่าแพลตฟอร์ม (บาท)
 * @property float $provider_pv_percentage ค่า PV (%)
 * @property float $provider_pv_amount ค่า PV (บาท)
 * @property float $provider_earnings รายได้สุทธิของ Provider
 * @property float $system_revenue รายได้รวมของระบบ
 * @property int|null $mlm_member_id สมาชิก MLM
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 */
class ServiceBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_bookings';

    protected $fillable = [
        'booking_number',
        'user_id',
        'service_id',
        'package_id',
        'provider_id',
        'scheduled_at',
        'service_duration_minutes',
        'base_price',
        'distance_km',
        'distance_price',
        'options_price',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'provider_notified_at',
        'provider_response_deadline',
        'provider_viewed_at',
        'provider_accepted_at',
        'provider_rejected_at',
        'provider_rejected_reason',
        'provider_response_minutes',
        'status',
        'customer_notes',
        'provider_notes',
        'admin_notes',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'confirmed_at',
        'started_at',
        'completed_at',
        'commission_amount',
        'platform_fee_percentage',
        'platform_fee_amount',
        'provider_pv_percentage',
        'provider_pv_amount',
        'provider_earnings',
        'system_revenue',
        'mlm_member_id',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'service_duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'distance_price' => 'decimal:2',
        'options_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'platform_fee_percentage' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'provider_pv_percentage' => 'decimal:2',
        'provider_pv_amount' => 'decimal:2',
        'provider_earnings' => 'decimal:2',
        'system_revenue' => 'decimal:2',
        'provider_notified_at' => 'datetime',
        'provider_response_deadline' => 'datetime',
        'provider_viewed_at' => 'datetime',
        'provider_accepted_at' => 'datetime',
        'provider_rejected_at' => 'datetime',
        'provider_response_minutes' => 'integer',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    //===========================================
    // Lifecycle Hooks
    //===========================================

    protected static function boot()
    {
        parent::boot();

        // สร้างเลขที่การจองอัตโนมัติ
        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = static::generateBookingNumber();
            }
        });
    }

    //===========================================
    // Relationships
    //===========================================

    /**
     * ลูกค้า
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * บริการ (ถ้าจองบริการเดี่ยว)
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * แพคเกจ (ถ้าจองแพคเกจ)
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    /**
     * ผู้ให้บริการ
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * รายการชำระเงิน
     */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * สมาชิก MLM ที่ได้รับคอมมิชชั่น
     */
    public function mlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * รายการย่อยในการจอง
     */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceBookingItem::class, 'booking_id');
    }

    /**
     * ตำแหน่งที่อยู่การจอง
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ServiceBookingLocation::class, 'booking_id');
    }

    /**
     * ประวัติการ tracking
     */
    public function trackings(): HasMany
    {
        return $this->hasMany(ServiceBookingTracking::class, 'booking_id');
    }

    /**
     * การแจ้งเตือน
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ServiceBookingNotification::class, 'booking_id');
    }

    /**
     * ประวัติการทำงาน (Audit trail)
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ServiceBookingAction::class, 'booking_id');
    }

    /**
     * รีวิว (1 booking = 1 review)
     */
    public function review(): HasMany
    {
        return $this->hasMany(ServiceReview::class, 'booking_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: กรองตามสถานะ
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: การจองที่รอชำระเงิน
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: การจองที่รอ provider รับงาน
     */
    public function scopeWaitingProvider($query)
    {
        return $query->whereIn('status', ['paid', 'notifying_provider', 'waiting_provider']);
    }

    /**
     * Scope: การจองที่ provider รับแล้ว
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'provider_accepted');
    }

    /**
     * Scope: การจองที่กำลังดำเนินการ
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'provider_accepted',
            'provider_on_way',
            'in_progress',
        ]);
    }

    /**
     * Scope: การจองที่เสร็จสิ้นแล้ว
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: การจองที่ยกเลิก
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: การจองในวันที่กำหนด
     */
    public function scopeScheduledOn($query, $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }

    //===========================================
    // Methods - Booking Number
    //===========================================

    /**
     * สร้างเลขที่การจอง
     */
    public static function generateBookingNumber(): string
    {
        $prefix = 'SB';
        $date = now()->format('Ymd');
        $lastBooking = static::whereDate('created_at', now())
            ->latest('id')
            ->first();

        $sequence = $lastBooking ? intval(substr($lastBooking->booking_number, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    //===========================================
    // Methods - Status Management
    //===========================================

    /**
     * อัพเดทสถานะ
     */
    public function updateStatus(string $newStatus, ?string $notes = null): void
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        // บันทึก action log
        $this->logAction('status_changed', [
            'from' => $oldStatus,
            'to' => $newStatus,
            'notes' => $notes,
        ]);
    }

    /**
     * ตรวจสอบว่าสามารถยกเลิกได้หรือไม่
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            'pending',
            'paid',
            'notifying_provider',
            'waiting_provider',
            'provider_accepted',
        ]);
    }

    /**
     * ยกเลิกการจอง
     */
    public function cancel(string $reason, string $cancelledBy = 'customer'): void
    {
        if (!$this->canCancel()) {
            throw new \Exception('ไม่สามารถยกเลิกการจองได้');
        }

        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        $this->logAction('cancelled', [
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }

    //===========================================
    // Methods - Provider Response
    //===========================================

    /**
     * แจ้งเตือน provider
     */
    public function notifyProvider(ServiceProvider $provider, int $responseMinutes = 15): void
    {
        $this->update([
            'provider_id' => $provider->id,
            'provider_notified_at' => now(),
            'provider_response_deadline' => now()->addMinutes($responseMinutes),
            'status' => 'notifying_provider',
        ]);

        $this->logAction('provider_notified', [
            'provider_id' => $provider->id,
            'deadline_minutes' => $responseMinutes,
        ]);
    }

    /**
     * Provider ดูการจอง
     */
    public function markAsViewed(): void
    {
        if (!$this->provider_viewed_at) {
            $this->update([
                'provider_viewed_at' => now(),
                'status' => 'waiting_provider',
            ]);

            $this->logAction('provider_viewed');
        }
    }

    /**
     * Provider รับงาน
     */
    public function acceptByProvider(?string $notes = null): void
    {
        $responseMinutes = $this->provider_notified_at
            ? now()->diffInMinutes($this->provider_notified_at)
            : 0;

        $this->update([
            'provider_accepted_at' => now(),
            'provider_response_minutes' => $responseMinutes,
            'status' => 'provider_accepted',
            'provider_notes' => $notes,
        ]);

        // อัพเดทสถิติ provider
        if ($this->provider) {
            $this->provider->incrementAccepted();
            $this->provider->updateAverageResponseTime($responseMinutes);
        }

        $this->logAction('provider_accepted', [
            'response_minutes' => $responseMinutes,
            'notes' => $notes,
        ]);
    }

    /**
     * Provider ปฏิเสธงาน
     */
    public function rejectByProvider(string $reason): void
    {
        $responseMinutes = $this->provider_notified_at
            ? now()->diffInMinutes($this->provider_notified_at)
            : 0;

        $this->update([
            'provider_rejected_at' => now(),
            'provider_rejected_reason' => $reason,
            'provider_response_minutes' => $responseMinutes,
            'status' => 'provider_rejected',
        ]);

        // อัพเดทสถิติ provider
        if ($this->provider) {
            $this->provider->incrementRejected();
        }

        $this->logAction('provider_rejected', [
            'reason' => $reason,
            'response_minutes' => $responseMinutes,
        ]);
    }

    /**
     * ตรวจสอบว่า provider ตอบกลับทันเวลาหรือไม่
     */
    public function isProviderResponseOnTime(): bool
    {
        if (!$this->provider_response_deadline) {
            return true;
        }

        $responseTime = $this->provider_accepted_at ?? $this->provider_rejected_at;

        if (!$responseTime) {
            return false;
        }

        return $responseTime->lte($this->provider_response_deadline);
    }

    //===========================================
    // Methods - Service Lifecycle
    //===========================================

    /**
     * เริ่มเดินทาง
     */
    public function startJourney(): void
    {
        $this->updateStatus('provider_on_way');
        $this->logAction('journey_started');
    }

    /**
     * เริ่มให้บริการ
     */
    public function startService(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->logAction('service_started');
    }

    /**
     * เสร็จสิ้นบริการ
     */
    public function completeService(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // อัพเดทสถิติ provider
        if ($this->provider) {
            $this->provider->increment('total_bookings');
        }

        $this->logAction('service_completed');
    }

    //===========================================
    // Methods - Payment
    //===========================================

    /**
     * ชำระเงิน
     */
    public function markAsPaid(PaymentTransaction $transaction): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        $this->logAction('payment_completed', [
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * คืนเงิน
     */
    public function refund(): void
    {
        $this->update([
            'payment_status' => 'refunded',
        ]);

        $this->logAction('payment_refunded');
    }

    //===========================================
    // Methods - Price Calculation
    //===========================================

    /**
     * คำนวณราคารวม
     */
    public function calculateTotalPrice(): void
    {
        $this->subtotal = $this->base_price + $this->distance_price + $this->options_price;
        $this->tax_amount = $this->subtotal * 0.07; // VAT 7%
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    /**
     * คำนวณค่าธรรมเนียมทั้งหมด (Platform Fee + PV + Provider Earnings)
     *
     * ใช้ค่าจากบริการเป็น snapshot ณ เวลาจอง
     *
     * @return void
     */
    public function calculateFees(): void
    {
        // คำนวณค่า Platform Fee
        $this->platform_fee_amount = $this->total_amount * ($this->platform_fee_percentage / 100);

        // คำนวณค่า Provider PV
        $this->provider_pv_amount = $this->total_amount * ($this->provider_pv_percentage / 100);

        // คำนวณรายได้สุทธิของ Provider
        $this->provider_earnings = $this->total_amount - $this->platform_fee_amount - $this->provider_pv_amount;

        // คำนวณรายได้รวมของระบบ
        $this->system_revenue = $this->platform_fee_amount + $this->provider_pv_amount;

        $this->save();
    }

    /**
     * คำนวณทั้งหมด (ราคา + ค่าธรรมเนียม)
     *
     * @return void
     */
    public function calculateAll(): void
    {
        $this->calculateTotalPrice();
        $this->calculateFees();
    }

    /**
     * ได้ข้อมูลสรุปค่าธรรมเนียม
     *
     * @return array
     */
    public function getFeeBreakdown(): array
    {
        return [
            'total_amount' => (float) $this->total_amount,
            'platform_fee' => [
                'percentage' => (float) $this->platform_fee_percentage,
                'amount' => (float) $this->platform_fee_amount,
            ],
            'provider_pv' => [
                'percentage' => (float) $this->provider_pv_percentage,
                'amount' => (float) $this->provider_pv_amount,
            ],
            'provider_earnings' => (float) $this->provider_earnings,
            'system_revenue' => (float) $this->system_revenue,
            'provider_percentage' => 100 - $this->platform_fee_percentage - $this->provider_pv_percentage,
        ];
    }

    //===========================================
    // Methods - Audit Trail
    //===========================================

    /**
     * บันทึก action log
     */
    protected function logAction(string $action, array $data = []): void
    {
        ServiceBookingAction::create([
            'booking_id' => $this->id,
            'action' => $action,
            'from_status' => $this->getOriginal('status'),
            'to_status' => $this->status,
            'data' => $data,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    //===========================================
    // Accessors
    //===========================================

    /**
     * สถานะเป็นภาษาไทย
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอชำระเงิน',
            'paid' => 'ชำระเงินแล้ว',
            'notifying_provider' => 'กำลังแจ้งเตือนผู้ให้บริการ',
            'waiting_provider' => 'รอผู้ให้บริการตอบกลับ',
            'provider_accepted' => 'ผู้ให้บริการรับงาน',
            'provider_rejected' => 'ผู้ให้บริการปฏิเสธ',
            'provider_on_way' => 'กำลังเดินทาง',
            'in_progress' => 'กำลังให้บริการ',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * เวลานัดหมายในรูปแบบที่อ่านง่าย
     */
    public function getScheduledAtFormattedAttribute(): string
    {
        return $this->scheduled_at->format('d/m/Y H:i');
    }
}
