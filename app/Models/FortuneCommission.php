<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneCommission Model
 *
 * บันทึกคอมมิชชั่นจากบิลดูดวง (แยกจาก mlm_commissions)
 * รองรับ Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)
 *
 * @property int $id
 * @property int $user_id ผู้รับคอมมิชชั่น
 * @property int $from_user_id ผู้จ่ายค่าดูดวง (คนดูดวง)
 * @property int $fortune_reading_id บิลดูดวง
 * @property int|null $mlm_member_id MLM member ผู้รับ
 * @property int|null $from_mlm_member_id MLM member ผู้จ่าย
 * @property int $level ชั้น (1=สายตรง, 2=หลาน)
 * @property string $commission_type ประเภท (fixed/percent)
 * @property float $commission_rate อัตราที่ใช้คำนวณ
 * @property float $amount จำนวนเงินจริงที่ได้
 * @property float $reading_price ราคาดูดวง ณ ตอนนั้น
 * @property string $status สถานะ (pending/approved/paid/rejected)
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $rejected_at
 * @property int|null $wallet_transaction_id
 * @property string|null $notes
 */
class FortuneCommission extends Model
{
    use SoftDeletes;

    /** สถานะคงที่ */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const STATUS_REJECTED = 'rejected';

    /**
     * ชื่อตาราง
     */
    protected $table = 'fortune_commissions';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'user_id',
        'from_user_id',
        'fortune_reading_id',
        'mlm_member_id',
        'from_mlm_member_id',
        'level',
        'commission_type',
        'commission_rate',
        'amount',
        'reading_price',
        'status',
        'approved_at',
        'paid_at',
        'rejected_at',
        'wallet_transaction_id',
        'notes',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'level' => 'integer',
        'commission_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'reading_price' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * ผู้รับคอมมิชชั่น
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ผู้จ่ายค่าดูดวง (คนดูดวง)
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * บิลดูดวงที่เกี่ยวข้อง
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'fortune_reading_id');
    }

    /**
     * MLM member ผู้รับคอมมิชชั่น
     */
    public function mlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * MLM member ผู้จ่ายค่าดูดวง
     */
    public function fromMlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'from_mlm_member_id');
    }

    // ===== Scopes =====

    /**
     * กรองเฉพาะสถานะ pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * กรองเฉพาะสถานะ approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * กรองเฉพาะสถานะ paid
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * กรองเฉพาะ Level 1 (สายตรง)
     */
    public function scopeLevel1($query)
    {
        return $query->where('level', 1);
    }

    /**
     * กรองเฉพาะ Level 2 (หลาน)
     */
    public function scopeLevel2($query)
    {
        return $query->where('level', 2);
    }

    /**
     * กรองเฉพาะ user ที่ระบุ
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ===== Helper Methods =====

    /**
     * ตรวจสอบว่าเป็น Level 1 (สายตรง) หรือไม่
     */
    public function isLevel1(): bool
    {
        return $this->level === 1;
    }

    /**
     * ตรวจสอบว่าเป็น Level 2 (หลาน) หรือไม่
     */
    public function isLevel2(): bool
    {
        return $this->level === 2;
    }

    /**
     * ชื่อชั้นภาษาไทย
     */
    public function getLevelNameAttribute(): string
    {
        return match ($this->level) {
            1 => 'สายตรง',
            2 => 'ชั้นหลาน',
            default => "ชั้น {$this->level}",
        };
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_PAID => 'จ่ายแล้ว',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            default => $this->status,
        };
    }
}
