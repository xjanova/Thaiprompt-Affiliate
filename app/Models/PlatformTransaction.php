<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlatformTransaction Model
 *
 * บันทึกรายการเข้าออกของกระเป๋าเงิน Platform
 *
 * @property int $id
 * @property int $platform_wallet_id
 * @property string $type
 * @property string|null $sub_type
 * @property float $amount
 * @property float $balance_before
 * @property float $balance_after
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $related_user_id
 * @property string|null $description
 * @property string|null $reference_number
 * @property array|null $metadata
 * @property string $status
 * @property int|null $processed_by
 */
class PlatformTransaction extends Model
{
    protected $fillable = [
        'platform_wallet_id',
        'type',
        'sub_type',
        'amount',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'related_user_id',
        'description',
        'reference_number',
        'metadata',
        'status',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * ประเภทรายการ
     */
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_REFUND = 'refund';

    /**
     * กระเป๋าที่เกี่ยวข้อง
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PlatformWallet::class, 'platform_wallet_id');
    }

    /**
     * ผู้ใช้ที่เกี่ยวข้อง (Seller/User)
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * ผู้ดำเนินการ (Admin)
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * ดึงรายการตามวัน
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * ดึงรายการตามเดือน
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * ดึงรายการเข้า
     * ใช้ชื่อตารางเต็มเพื่อป้องกันปัญหา ambiguous column เมื่อ join กับตารางอื่น
     */
    public function scopeIncome($query)
    {
        return $query->where('platform_transactions.type', self::TYPE_INCOME);
    }

    /**
     * ดึงรายการออก
     * ใช้ชื่อตารางเต็มเพื่อป้องกันปัญหา ambiguous column เมื่อ join กับตารางอื่น
     */
    public function scopeExpense($query)
    {
        return $query->where('platform_transactions.type', self::TYPE_EXPENSE);
    }

    /**
     * ดึงรายการที่เสร็จสมบูรณ์
     * ใช้ชื่อตารางเต็มเพื่อป้องกันปัญหา ambiguous column เมื่อ join กับตารางอื่น
     */
    public function scopeCompleted($query)
    {
        return $query->where('platform_transactions.status', 'completed');
    }

    /**
     * สีตามประเภท
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INCOME, self::TYPE_TRANSFER_IN => 'green',
            self::TYPE_EXPENSE, self::TYPE_TRANSFER_OUT => 'red',
            self::TYPE_ADJUSTMENT => 'yellow',
            self::TYPE_REFUND => 'orange',
            default => 'gray',
        };
    }

    /**
     * Icon ตามประเภท
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INCOME => 'fa-arrow-down',
            self::TYPE_EXPENSE => 'fa-arrow-up',
            self::TYPE_TRANSFER_IN => 'fa-exchange-alt',
            self::TYPE_TRANSFER_OUT => 'fa-exchange-alt',
            self::TYPE_ADJUSTMENT => 'fa-edit',
            self::TYPE_REFUND => 'fa-undo',
            default => 'fa-circle',
        };
    }

    /**
     * Label ไทยตามประเภท
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INCOME => 'รายได้เข้า',
            self::TYPE_EXPENSE => 'รายจ่ายออก',
            self::TYPE_TRANSFER_IN => 'โอนเข้า',
            self::TYPE_TRANSFER_OUT => 'โอนออก',
            self::TYPE_ADJUSTMENT => 'ปรับยอด',
            self::TYPE_REFUND => 'คืนเงิน',
            default => 'อื่นๆ',
        };
    }

    /**
     * Label ไทยตามประเภทย่อย
     */
    public function getSubTypeLabelAttribute(): string
    {
        return match ($this->sub_type) {
            'order_fee' => 'ค่าธรรมเนียมจากออเดอร์',
            'service_fee' => 'ค่าธรรมเนียมจากบริการ',
            'vat_collection' => 'เก็บภาษี VAT',
            'mlm_commission_pool' => 'กองทุนคอมมิชชัน MLM',
            'seller_payout' => 'จ่ายให้ผู้ขาย',
            'mlm_payout' => 'จ่ายคอมมิชชัน MLM',
            'refund_order' => 'คืนเงินจากออเดอร์',
            default => $this->sub_type ?? 'ไม่ระบุ',
        };
    }

    /**
     * เครื่องหมาย + หรือ -
     */
    public function getSignAttribute(): string
    {
        return in_array($this->type, [self::TYPE_INCOME, self::TYPE_TRANSFER_IN])
            ? '+' : '-';
    }

    /**
     * จำนวนเงินพร้อมเครื่องหมาย
     */
    public function getSignedAmountAttribute(): string
    {
        return $this->sign . number_format($this->amount, 2);
    }
}
