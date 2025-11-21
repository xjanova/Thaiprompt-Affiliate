<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commission Model
 *
 * Model สำหรับจัดการคอมมิชชั่นทั่วไปในระบบ
 *
 * @property int $id
 * @property int $affiliate_id Foreign key ไปยัง affiliates table
 * @property int|null $user_id Foreign key ไปยัง users table
 * @property string|null $order_id Order ID ที่เกี่ยวข้อง
 * @property float $amount จำนวนคอมมิชชั่น
 * @property float $percentage เปอร์เซ็นต์คอมมิชชั่น
 * @property string $type ประเภทคอมมิชชั่น (direct, indirect, bonus)
 * @property string $status สถานะ (pending, approved, rejected, paid)
 * @property \Carbon\Carbon|null $approved_at วันที่อนุมัติ
 * @property \Carbon\Carbon|null $paid_at วันที่จ่ายเงิน
 * @property \Carbon\Carbon|null $rejected_at วันที่ปฏิเสธ
 * @property string|null $notes หมายเหตุ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Commission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'commissions';

    /**
     * ฟิลด์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'affiliate_id',
        'user_id',
        'order_id',
        'amount',
        'percentage',
        'type',
        'status',
        'approved_at',
        'paid_at',
        'rejected_at',
        'notes',
    ];

    /**
     * การแปลงชนิดข้อมูลของฟิลด์
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ User (ผู้รับคอมมิชชั่น)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Affiliate
     *
     * @return BelongsTo
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Scope สำหรับคอมมิชชั่นที่รอดำเนินการ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับคอมมิชชั่นที่อนุมัติแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope สำหรับคอมมิชชั่นที่จ่ายแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope สำหรับคอมมิชชั่นที่ปฏิเสธแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * อนุมัติคอมมิชชั่น
     *
     * @return bool
     */
    public function approve(): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * ปฏิเสธคอมมิชชั่น
     *
     * @param string|null $reason เหตุผลที่ปฏิเสธ
     * @return bool
     */
    public function reject(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * ทำเครื่องหมายว่าจ่ายแล้ว
     *
     * @return bool
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * ตรวจสอบว่าคอมมิชชั่นรอดำเนินการหรือไม่
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * ตรวจสอบว่าคอมมิชชั่นอนุมัติแล้วหรือไม่
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * ตรวจสอบว่าคอมมิชชั่นจ่ายแล้วหรือไม่
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * ตรวจสอบว่าคอมมิชชั่นถูกปฏิเสธหรือไม่
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
