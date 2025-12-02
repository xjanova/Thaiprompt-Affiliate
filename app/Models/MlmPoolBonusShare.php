<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MlmPoolBonusShare - ส่วนแบ่ง Pool Bonus ของสมาชิกแต่ละคน
 *
 * @property int $id
 * @property int $pool_period_id
 * @property int $mlm_member_id
 * @property int $user_id
 * @property int $shares จำนวน shares (ตาม rank)
 * @property float $share_amount จำนวนเงินต่อ share
 * @property float $total_amount shares × share_amount
 * @property string $qualification_reason เหตุผลที่ qualify
 * @property int $rank_id rank ที่ใช้คำนวณ
 * @property float $personal_pv PV ส่วนตัวในรอบนี้
 * @property float $team_pv PV ทีมในรอบนี้
 * @property string $status pending/approved/paid/cancelled
 */
class MlmPoolBonusShare extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pool_period_id',
        'mlm_member_id',
        'user_id',
        'shares',
        'share_amount',
        'total_amount',
        'qualification_reason',
        'rank_id',
        'personal_pv',
        'team_pv',
        'status',
        'mlm_commission_id',
    ];

    protected function casts(): array
    {
        return [
            'shares' => 'integer',
            'share_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'personal_pv' => 'decimal:2',
            'team_pv' => 'decimal:2',
        ];
    }

    /**
     * ความสัมพันธ์กับ period
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(MlmPoolBonusPeriod::class, 'pool_period_id');
    }

    /**
     * ความสัมพันธ์กับ MLM member
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * ความสัมพันธ์กับ user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ rank
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    /**
     * ความสัมพันธ์กับ commission record
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(MlmCommission::class, 'mlm_commission_id');
    }

    /**
     * Scope สำหรับ pending shares
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับ approved shares
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Approve share
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Mark as paid พร้อม link กับ commission
     */
    public function markAsPaid(int $commissionId): void
    {
        $this->update([
            'status' => 'paid',
            'mlm_commission_id' => $commissionId,
        ]);
    }

    /**
     * Cancel share
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
