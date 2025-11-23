<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MlmTeamTransferRequest Model
 *
 * จัดการคำขอย้ายทีมของสมาชิก MLM
 *
 * @property int $id
 * @property int $mlm_member_id สมาชิกที่ขอย้าย
 * @property int $user_id User ที่ขอย้าย
 * @property int $old_unilevel_sponsor_id แม่ทีมเก่า
 * @property int $new_unilevel_sponsor_id แม่ทีมใหม่
 * @property int|null $old_binary_parent_id Binary parent เก่า
 * @property string|null $old_binary_position Binary position เก่า
 * @property int|null $new_binary_parent_id Binary parent ใหม่
 * @property string|null $new_binary_position Binary position ใหม่
 * @property string $status สถานะคำขอ
 * @property int|null $approved_by ผู้อนุมัติ
 * @property \Carbon\Carbon|null $approved_at วันที่อนุมัติ
 * @property int|null $rejected_by ผู้ปฏิเสธ
 * @property \Carbon\Carbon|null $rejected_at วันที่ปฏิเสธ
 * @property string|null $rejection_reason เหตุผลที่ปฏิเสธ
 * @property float $transfer_fee ค่าธรรมเนียม
 * @property int|null $wallet_transaction_id Transaction ID
 * @property \Carbon\Carbon|null $paid_at วันที่ชำระเงิน
 * @property int|null $processed_by Admin ที่ดำเนินการ
 * @property \Carbon\Carbon|null $processed_at วันที่ดำเนินการ
 * @property string|null $admin_notes หมายเหตุจาก Admin
 * @property string|null $reason เหตุผลที่ขอย้าย
 * @property string|null $notes หมายเหตุ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read MlmMember $member
 * @property-read User $user
 * @property-read MlmMember $oldSponsor
 * @property-read MlmMember $newSponsor
 * @property-read MlmMember|null $oldBinaryParent
 * @property-read MlmMember|null $newBinaryParent
 * @property-read User|null $approver
 * @property-read User|null $rejecter
 * @property-read User|null $processor
 * @property-read WalletTransaction|null $payment
 */
class MlmTeamTransferRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mlm_team_transfer_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'mlm_member_id',
        'user_id',
        'old_unilevel_sponsor_id',
        'new_unilevel_sponsor_id',
        'old_binary_parent_id',
        'old_binary_position',
        'new_binary_parent_id',
        'new_binary_position',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'transfer_fee',
        'wallet_transaction_id',
        'paid_at',
        'processed_by',
        'processed_at',
        'admin_notes',
        'reason',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transfer_fee' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * ความสัมพันธ์กับ MlmMember (ผู้ขอย้าย)
     *
     * @return BelongsTo
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ขอย้าย)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (แม่ทีมเก่า)
     *
     * @return BelongsTo
     */
    public function oldSponsor(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'old_unilevel_sponsor_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (แม่ทีมใหม่)
     *
     * @return BelongsTo
     */
    public function newSponsor(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'new_unilevel_sponsor_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (Binary parent เก่า)
     *
     * @return BelongsTo
     */
    public function oldBinaryParent(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'old_binary_parent_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (Binary parent ใหม่)
     *
     * @return BelongsTo
     */
    public function newBinaryParent(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'new_binary_parent_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้อนุมัติ)
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ปฏิเสธ)
     *
     * @return BelongsTo
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * ความสัมพันธ์กับ User (Admin ที่ดำเนินการ)
     *
     * @return BelongsTo
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * ความสัมพันธ์กับ WalletTransaction (การชำระเงิน)
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    /**
     * Scope: คำขอที่รอดำเนินการ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: คำขอที่อนุมัติแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: คำขอที่ชำระเงินแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: คำขอที่เสร็จสมบูรณ์
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * ตรวจสอบว่าคำขอนี้รอการอนุมัติหรือไม่
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * ตรวจสอบว่าคำขอนี้ได้รับการอนุมัติแล้วหรือไม่
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * ตรวจสอบว่าคำขอนี้ถูกปฏิเสธหรือไม่
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * ตรวจสอบว่าคำขอนี้ชำระเงินแล้วหรือไม่
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * ตรวจสอบว่าคำขอนี้เสร็จสมบูรณ์หรือไม่
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * ตรวจสอบว่าคำขอนี้ถูกยกเลิกหรือไม่
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * ตรวจสอบว่า user นี้เป็นแม่ทีมเก่าหรือไม่
     *
     * @param int $userId
     * @return bool
     */
    public function isOldSponsor(int $userId): bool
    {
        return $this->oldSponsor && $this->oldSponsor->user_id === $userId;
    }

    /**
     * ตรวจสอบว่าสามารถอนุมัติได้หรือไม่
     *
     * @return bool
     */
    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    /**
     * ตรวจสอบว่าสามารถปฏิเสธได้หรือไม่
     *
     * @return bool
     */
    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    /**
     * ตรวจสอบว่าสามารถชำระเงินได้หรือไม่
     *
     * @return bool
     */
    public function canBePaid(): bool
    {
        return $this->isApproved() && !$this->paid_at;
    }

    /**
     * ตรวจสอบว่าสามารถดำเนินการย้ายได้หรือไม่
     *
     * @return bool
     */
    public function canBeProcessed(): bool
    {
        return $this->isPaid();
    }

    /**
     * ตรวจสอบว่าสามารถยกเลิกได้หรือไม่
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * Get status label in Thai
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'รออนุมัติ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            self::STATUS_PAID => 'ชำระเงินแล้ว',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_COMPLETED => 'เสร็จสมบูรณ์',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get status color for UI
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_PAID => 'blue',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }
}
