<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RiderJob Model
 *
 * จัดการข้อมูลงานของไรเดอร์
 *
 * @property int $id
 * @property string $job_number
 * @property int $rider_id
 * @property string $job_type
 * @property string $status
 * @property decimal $total_fee
 */
class RiderJob extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'rider_jobs';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_number',
        'rider_id',
        'job_type',
        'title',
        'description',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_contact_name',
        'pickup_contact_phone',
        'pickup_notes',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_contact_name',
        'delivery_contact_phone',
        'delivery_notes',
        'distance_km',
        'estimated_duration_minutes',
        'base_fee',
        'distance_fee',
        'extra_fee',
        'total_fee',
        'rider_earnings',
        'platform_fee',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'accepted_at',
        'picked_up_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'customer_id',
        'pickup_proof_image',
        'delivery_proof_image',
        'signature_image',
        'customer_rating',
        'customer_review',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'distance_fee' => 'decimal:2',
        'extra_fee' => 'decimal:2',
        'total_fee' => 'decimal:2',
        'rider_earnings' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'base_fee' => 0,
        'distance_fee' => 0,
        'extra_fee' => 0,
        'total_fee' => 0,
        'rider_earnings' => 0,
        'platform_fee' => 0,
    ];

    // =====================================================
    // Boot
    // =====================================================

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // สร้างเลขที่งานอัตโนมัติ
        static::creating(function ($job) {
            if (empty($job->job_number)) {
                $job->job_number = self::generateJobNumber();
            }
        });
    }

    /**
     * สร้างเลขที่งาน
     *
     * @return string
     */
    public static function generateJobNumber(): string
    {
        $prefix = 'JOB';
        $date = now()->format('ymd');
        $lastJob = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = (int) substr($lastJob->job_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * ความสัมพันธ์กับ Rider
     *
     * @return BelongsTo
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * ความสัมพันธ์กับ Customer
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * ประวัติตำแหน่งของงานนี้
     *
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class, 'job_id');
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope สำหรับงานที่กำลังดำเนินการ
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'accepted',
            'picking_up',
            'picked_up',
            'delivering',
        ]);
    }

    /**
     * Scope สำหรับงานที่เสร็จสิ้น
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope สำหรับงานที่รอไรเดอร์
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * ชื่อสถานะภาษาไทย
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รอไรเดอร์รับงาน',
            'accepted' => 'ไรเดอร์รับงานแล้ว',
            'picking_up' => 'กำลังไปรับของ',
            'picked_up' => 'รับของแล้ว',
            'delivering' => 'กำลังจัดส่ง',
            'delivered' => 'ส่งแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            'failed' => 'ล้มเหลว',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ชื่อประเภทงานภาษาไทย
     *
     * @return string
     */
    public function getJobTypeTextAttribute(): string
    {
        return match($this->job_type) {
            'delivery' => 'ส่งของ',
            'food' => 'ส่งอาหาร',
            'document' => 'ส่งเอกสาร',
            'service' => 'ให้บริการ',
            'pickup' => 'รับของ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * ตรวจสอบว่าสามารถ track GPS ได้หรือไม่
     *
     * @return bool
     */
    public function getIsTrackableAttribute(): bool
    {
        return in_array($this->status, [
            'accepted',
            'picking_up',
            'picked_up',
            'delivering',
        ]);
    }

    // =====================================================
    // Methods
    // =====================================================

    /**
     * ไรเดอร์รับงาน
     *
     * @return void
     */
    public function accept(): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('ไม่สามารถรับงานนี้ได้');
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // เปลี่ยนสถานะไรเดอร์เป็น busy
        $this->rider->setBusy();
    }

    /**
     * ไปถึงจุดรับ
     *
     * @return void
     */
    public function arrivedAtPickup(): void
    {
        $this->update(['status' => 'picking_up']);
    }

    /**
     * รับของแล้ว
     *
     * @param string|null $proofImage
     * @return void
     */
    public function pickUp(?string $proofImage = null): void
    {
        $updateData = [
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ];

        if ($proofImage) {
            $updateData['pickup_proof_image'] = $proofImage;
        }

        $this->update($updateData);
    }

    /**
     * กำลังจัดส่ง
     *
     * @return void
     */
    public function startDelivery(): void
    {
        $this->update(['status' => 'delivering']);
    }

    /**
     * ส่งแล้ว
     *
     * @param string|null $proofImage
     * @param string|null $signatureImage
     * @return void
     */
    public function deliver(?string $proofImage = null, ?string $signatureImage = null): void
    {
        $updateData = [
            'status' => 'delivered',
            'delivered_at' => now(),
        ];

        if ($proofImage) {
            $updateData['delivery_proof_image'] = $proofImage;
        }
        if ($signatureImage) {
            $updateData['signature_image'] = $signatureImage;
        }

        $this->update($updateData);
    }

    /**
     * เสร็จสิ้นงาน
     *
     * @return void
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // อัปเดตสถิติไรเดอร์
        $this->rider->increment('completed_jobs');
        $this->rider->increment('total_jobs');
        $this->rider->increment('total_earnings', $this->rider_earnings);

        // เปลี่ยนสถานะไรเดอร์เป็น online
        $this->rider->goOnline();
    }

    /**
     * ยกเลิกงาน
     *
     * @param string $reason
     * @param string $cancelledBy
     * @return void
     */
    public function cancel(string $reason, string $cancelledBy): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        if ($cancelledBy === 'rider') {
            $this->rider->increment('cancelled_jobs');
        }

        // เปลี่ยนสถานะไรเดอร์เป็น online
        $this->rider->goOnline();
    }
}
