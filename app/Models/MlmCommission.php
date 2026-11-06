<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mlm_member_id',
        'mlm_plan_id',
        'user_id',
        'from_member_id',
        'source_type',
        'source_id',
        'type',
        'level',
        'leg',
        'pv_amount',
        'sales_amount',
        'commission_amount',
        'percentage',
        'left_leg_pv',
        'right_leg_pv',
        'pairs_count',
        'status',
        'approved_at',
        'paid_at',
        'rejected_at',
        'notes',
        'rejection_reason',
        'wallet_transaction_id',
        // Roll-up tracking fields
        'is_rollup',
        'rollup_from_member_id',
        'rollup_original_level',
        'rollup_chain',
        'source_leg',
        'tree_type',
        'calculation_details',
        // F9: เหตุผลที่จ่ายไม่ได้ (null = ไม่มีปัญหา)
        'payout_blocked_reason',
        'payout_blocked_at',
    ];

    /**
     * 🛡️ audit F8 / M4: เงินโรลอัพที่ไม่มีผู้รับ — บันทึกเป็นแถว ledger ของกอง (type pool_bonus, tree_type pool)
     *    เงินยังอยู่ใน mlm_pool ไม่มีเจ้าของ (mlm_member_id/user_id = null) อนุมัติ/จ่ายไม่ได้
     *    เดิมเป็นแถว pending ของ member #1 (หรือของผู้ซื้อเอง) พอกดอนุมัติ+จ่าย เงินกองเข้ากระเป๋าคน
     */
    public const STATUS_POOLED = 'pooled';

    /**
     * รหัสเหตุผลที่จ่ายคอมไม่ได้ → ข้อความภาษาไทยสำหรับหน้าจอแอดมิน (audit F9)
     */
    public const PAYOUT_BLOCK_REASONS = [
        'pool_insufficient' => 'กองทุน MLM มีเงินไม่พอจ่าย',
        'order_budget_exceeded' => 'เกินเงินที่ออเดอร์ต้นทางเก็บเข้ากอง (ห้ามใช้เงินกองของออเดอร์อื่น)',
        'source_order_missing' => 'ไม่พบออเดอร์ต้นทาง',
        'no_user' => 'ไม่พบผู้รับเงิน',
        'zero_amount' => 'ยอดเงินเป็น 0',
        'wallet_inactive' => 'กระเป๋าเงินของผู้รับถูกระงับ',
        'payout_error' => 'ระบบจ่ายเงินผิดพลาด (ดู log)',
    ];

    protected function casts(): array
    {
        return [
            'pv_amount' => 'decimal:2',
            'sales_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'left_leg_pv' => 'decimal:2',
            'right_leg_pv' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
            'payout_blocked_at' => 'datetime',
            // Roll-up tracking casts
            'is_rollup' => 'boolean',
            'rollup_chain' => 'array',
            'calculation_details' => 'array',
        ];
    }

    /**
     * Get the member who earned this commission
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get the beneficiary user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the member who generated this commission
     */
    public function fromMember()
    {
        return $this->belongsTo(MlmMember::class, 'from_member_id');
    }

    /**
     * Get the wallet transaction (when paid)
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the source (polymorphic)
     */
    public function source()
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    /**
     * Scope for pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Approve this commission
     * แก้ Bug #32: เพิ่ม status guard ป้องกันการเปลี่ยนสถานะที่ไม่ถูกต้อง
     */
    public function approve()
    {
        // แถว pooled (เงินกอง) ไม่ใช่ pending จึงอนุมัติไม่ได้โดยอัตโนมัติ — ห้ามเพิ่ม pooled เข้าเงื่อนไขนี้ (F8)
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject this commission
     * แก้ Bug #32: เพิ่ม status guard
     */
    public function reject($reason = null)
    {
        if (! in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Mark as paid
     * แก้ Bug #32: เพิ่ม status guard
     */
    public function markAsPaid($walletTransactionId = null)
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'wallet_transaction_id' => $walletTransactionId,
            // จ่ายได้แล้ว → ล้างเหตุผลที่เคยติด (F9)
            'payout_blocked_reason' => null,
            'payout_blocked_at' => null,
        ]);

        return true;
    }

    /**
     * ยอดของแถวที่ยังดึงคืนได้ (ยังไม่ถูกดึงคืน) — ใช้สูตรเดียวทุกทาง (คืนเงินออเดอร์ / ดึงคืนแบบแมนนวล)
     *
     * 🛡️ คู่ binary ที่ถูกดึงคืนบางส่วน (MlmBinaryService::unwindPairRow) เก็บ
     *    original_commission_amount (ยอดตั้งต้น) + clawback_amount (ดึงคืนไปแล้วสะสม) ใน calculation_details
     *    - แถวที่ยังไม่จ่าย: commission_amount ถูกลดลงแล้ว (= ตั้งต้น − ดึงคืน)
     *    - แถวที่จ่ายแล้ว: commission_amount คงยอดเดิม
     *    → ยอดคงเหลือ = ตั้งต้น − ดึงคืนแล้ว ถูกทั้งสองแบบ (เดิมใช้ commission_amount − clawback_amount ทำให้หักซ้ำ)
     */
    public function remainingClawableAmount(): float
    {
        $details = is_array($this->calculation_details) ? $this->calculation_details : [];
        $original = isset($details['original_commission_amount'])
            ? (float) $details['original_commission_amount']
            : (float) $this->commission_amount;

        return round(max(0.0, $original - (float) ($details['clawback_amount'] ?? 0)), 2);
    }

    /**
     * บันทึกว่าแถวนี้ถูกดึงคืนครบแล้ว (clawback_amount = ยอดตั้งต้น) — กันดึงคืนซ้ำจากทางอื่น
     */
    public function markFullyClawedBack(): void
    {
        $details = is_array($this->calculation_details) ? $this->calculation_details : [];
        $original = isset($details['original_commission_amount'])
            ? (float) $details['original_commission_amount']
            : (float) $this->commission_amount;
        $details['original_commission_amount'] = round($original, 2);
        $details['clawback_amount'] = round($original, 2);
        $this->forceFill(['calculation_details' => $details]);
    }

    /**
     * ข้อความเหตุผลที่จ่ายไม่ได้ (null = ไม่ติดอะไร)
     */
    public function getPayoutBlockedLabelAttribute(): ?string
    {
        $code = $this->payout_blocked_reason;

        return $code ? (self::PAYOUT_BLOCK_REASONS[$code] ?? $code) : null;
    }
}
