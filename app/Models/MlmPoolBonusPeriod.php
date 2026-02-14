<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MlmPoolBonusPeriod - รอบการคำนวณ Pool Bonus (All Sale Bonus)
 *
 * Pool Bonus คือโบนัสที่หักจากยอดขายรวมของระบบ
 * และแบ่งให้สมาชิกที่มีคุณสมบัติตามจำนวน shares
 *
 * @property int $id
 * @property string $period_type daily/weekly/monthly
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property float $total_sales ยอดขายรวม
 * @property float $total_pv PV รวม
 * @property float $pool_percentage % ที่หักเข้า pool
 * @property float $pool_amount จำนวนเงินใน pool
 * @property int $qualified_members_count จำนวนสมาชิกที่ qualify
 * @property float $amount_per_share จำนวนต่อ share
 * @property int $total_shares จำนวน shares ทั้งหมด
 * @property string $status pending/calculating/distributed/cancelled
 */
class MlmPoolBonusPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'total_sales',
        'total_pv',
        'pool_percentage',
        'pool_amount',
        'qualified_members_count',
        'amount_per_share',
        'total_shares',
        'status',
        'calculated_at',
        'distributed_at',
        'notes',
        'calculation_details',
        'distribution_summary',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_sales' => 'decimal:2',
            'total_pv' => 'decimal:2',
            'pool_percentage' => 'decimal:2',
            'pool_amount' => 'decimal:2',
            'amount_per_share' => 'decimal:2',
            'calculated_at' => 'datetime',
            'distributed_at' => 'datetime',
            'calculation_details' => 'array',
            'distribution_summary' => 'array',
        ];
    }

    /**
     * ความสัมพันธ์กับ shares ของสมาชิก
     */
    public function shares(): HasMany
    {
        return $this->hasMany(MlmPoolBonusShare::class, 'pool_period_id');
    }

    /**
     * ดึง shares ที่ approved
     */
    public function approvedShares(): HasMany
    {
        return $this->hasMany(MlmPoolBonusShare::class, 'pool_period_id')
            ->where('status', 'approved');
    }

    /**
     * Scope สำหรับ pending periods
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับ distributed periods
     */
    public function scopeDistributed($query)
    {
        return $query->where('status', 'distributed');
    }

    /**
     * เริ่มการคำนวณ
     */
    public function startCalculation(): void
    {
        $this->update([
            'status' => 'calculating',
        ]);
    }

    /**
     * เสร็จสิ้นการคำนวณ
     */
    public function finishCalculation(array $details = []): void
    {
        $this->update([
            'calculated_at' => now(),
            'calculation_details' => $details,
        ]);
    }

    /**
     * กระจาย pool bonus
     */
    public function markAsDistributed(array $summary = []): void
    {
        $this->update([
            'status' => 'distributed',
            'distributed_at' => now(),
            'distribution_summary' => $summary,
        ]);
    }

    /**
     * ยกเลิก period
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    /**
     * ตรวจสอบว่าสามารถ distribute ได้หรือไม่
     */
    public function canDistribute(): bool
    {
        return $this->status === 'pending' &&
               $this->pool_amount > 0 &&
               $this->qualified_members_count > 0;
    }
}
