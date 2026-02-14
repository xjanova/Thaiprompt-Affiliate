<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TarotCommission Model
 *
 * เก็บประวัติคอมมิชชั่นจากการทำนายไพ่ทาโร่ต์
 *
 * @property int $id
 * @property int $tarot_reading_id ID ของการทำนาย
 * @property int $user_id ผู้รับคอมมิชชั่น
 * @property int|null $mlm_member_id ID ของ MLM member
 * @property int $level ระดับ MLM (1=direct, 2=upline level 2)
 * @property float $commission_rate อัตราคอมมิชชั่น (%)
 * @property float $commission_amount จำนวนคอมมิชชั่น
 * @property float $pv_amount PV ที่ได้รับ
 * @property string $status pending|approved|paid|cancelled
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $notes
 */
class TarotCommission extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'tarot_commissions';

    /**
     * Attributes ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'tarot_reading_id',
        'user_id',
        'mlm_member_id',
        'level',
        'commission_rate',
        'commission_amount',
        'pv_amount',
        'status',
        'paid_at',
        'notes',
    ];

    /**
     * Attribute casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'pv_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ TarotReading
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reading()
    {
        return $this->belongsTo(TarotReading::class, 'tarot_reading_id');
    }

    /**
     * ความสัมพันธ์กับ User ผู้รับคอมมิชชั่น
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ MlmMember
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mlmMember()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Scope สำหรับสถานะ pending
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับสถานะ approved
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope สำหรับสถานะ paid
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope กรองตาม user
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope กรองตาม level
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * อนุมัติคอมมิชชั่น
     */
    public function approve(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'approved';

        return $this->save();
    }

    /**
     * บันทึกว่าจ่ายคอมมิชชั่นแล้ว
     */
    public function markAsPaid(): bool
    {
        if (! in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $this->status = 'paid';
        $this->paid_at = now();

        return $this->save();
    }

    /**
     * ยกเลิกคอมมิชชั่น
     */
    public function cancel(?string $reason = null): bool
    {
        if ($this->status === 'paid') {
            return false; // ไม่สามารถยกเลิกที่จ่ายแล้ว
        }

        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = $reason;
        }

        return $this->save();
    }

    /**
     * ตรวจสอบว่าจ่ายได้หรือไม่
     */
    public function canBePaid(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * คำนวณยอดคอมมิชชั่นรวมของ user
     */
    public static function getTotalCommission(int $userId, string $status = 'paid'): float
    {
        return static::where('user_id', $userId)
            ->where('status', $status)
            ->sum('commission_amount');
    }

    /**
     * คำนวณยอด PV รวมของ user
     */
    public static function getTotalPv(int $userId, string $status = 'paid'): float
    {
        return static::where('user_id', $userId)
            ->where('status', $status)
            ->sum('pv_amount');
    }
}
