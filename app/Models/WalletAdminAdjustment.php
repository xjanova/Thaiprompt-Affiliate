<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บันทึกตรวจสอบ (audit trail) ของรายการที่แอดมินแตะยอดเงินในกระเป๋าโดยตรง
 *
 * ⚠️ ห้ามแก้/ลบแถวในตารางนี้จากหน้าจอใด ๆ — เป็นหลักฐานทางบัญชี (append-only)
 *
 * @property int $id
 * @property int|null $wallet_id null = กระเป๋าถูกลบไปแล้ว (แถวตรวจสอบยังอยู่)
 * @property int $user_id เจ้าของกระเป๋า
 * @property int|null $admin_id แอดมินที่ทำรายการ
 * @property string|null $admin_name ชื่อแอดมิน ณ ตอนทำรายการ
 * @property string $action credit|debit|refund|rollback|transfer_out|transfer_in
 * @property string $amount
 * @property string $balance_before
 * @property string $balance_after
 * @property string $reason
 * @property int|null $wallet_transaction_id
 * @property int|null $original_transaction_id
 * @property string|null $idempotency_key
 */
class WalletAdminAdjustment extends Model
{
    /** รายการที่ทำให้ยอดเพิ่ม */
    public const CREDIT_ACTIONS = ['credit', 'refund', 'transfer_in'];

    /** ป้ายภาษาไทยของแต่ละประเภท */
    public const ACTION_LABELS = [
        'credit' => 'เติมเงิน',
        'debit' => 'หักเงิน',
        'refund' => 'คืนเงิน',
        'rollback' => 'ย้อนรายการ',
        'transfer_out' => 'โอนออก (แอดมิน)',
        'transfer_in' => 'รับโอน (แอดมิน)',
    ];

    protected $fillable = [
        'wallet_id',
        'user_id',
        'admin_id',
        'admin_name',
        'action',
        'amount',
        'balance_before',
        'balance_after',
        'reason',
        'wallet_transaction_id',
        'original_transaction_id',
        'idempotency_key',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * ป้ายประเภทรายการภาษาไทย
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * รายการนี้ทำให้ยอดเพิ่มหรือไม่
     *
     * ดูจากยอดก่อน/หลังที่บันทึกไว้ — ใช้ได้กับทุก action รวม rollback ซึ่งทิศทางขึ้นกับรายการเดิม
     * (ย้อนรายการเติม = หัก · ย้อนรายการหัก = คืน)
     */
    public function getIsCreditAttribute(): bool
    {
        if ($this->balance_before !== null && $this->balance_after !== null) {
            return (float) $this->balance_after > (float) $this->balance_before;
        }

        return in_array($this->action, self::CREDIT_ACTIONS, true);
    }
}
