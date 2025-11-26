<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EarningsLedger Model
 *
 * บัญชีรายได้รอจ่าย - บันทึกทุกรายการรายได้ก่อนโอนเข้า wallet
 *
 * @property int $id
 * @property int $user_id
 * @property string $earning_type
 * @property string $source_type
 * @property int $source_id
 * @property float $gross_amount
 * @property float $platform_fee
 * @property float $vat_amount
 * @property float $mlm_commission
 * @property float $other_deductions
 * @property float $debt_deduction
 * @property float $net_amount
 * @property string $status
 * @property \Carbon\Carbon|null $available_at
 * @property \Carbon\Carbon|null $paid_at
 * @property int|null $payout_request_id
 * @property int|null $wallet_transaction_id
 * @property string|null $description
 * @property array|null $breakdown
 */
class EarningsLedger extends Model
{
    use SoftDeletes;

    protected $table = 'earnings_ledger';

    protected $fillable = [
        'user_id',
        'earning_type',
        'source_type',
        'source_id',
        'gross_amount',
        'platform_fee',
        'vat_amount',
        'mlm_commission',
        'other_deductions',
        'debt_deduction',
        'net_amount',
        'status',
        'available_at',
        'paid_at',
        'payout_request_id',
        'wallet_transaction_id',
        'description',
        'breakdown',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:4',
        'platform_fee' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'mlm_commission' => 'decimal:4',
        'other_deductions' => 'decimal:4',
        'debt_deduction' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'available_at' => 'datetime',
        'paid_at' => 'datetime',
        'breakdown' => 'array',
    ];

    /**
     * ประเภทรายได้
     */
    const TYPE_SELLER_SALE = 'seller_sale';
    const TYPE_SERVICE_PROVIDER = 'service_provider';
    const TYPE_MLM_COMMISSION = 'mlm_commission';
    const TYPE_AFFILIATE_COMMISSION = 'affiliate_commission';
    const TYPE_REFERRAL_BONUS = 'referral_bonus';
    const TYPE_OTHER = 'other';

    /**
     * สถานะ
     */
    const STATUS_PENDING = 'pending';
    const STATUS_AVAILABLE = 'available';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PAID = 'paid';
    const STATUS_HELD = 'held';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * เจ้าของรายได้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * คำขอเบิกเงิน
     */
    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class);
    }

    /**
     * Wallet Transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * รายได้ที่พร้อมจ่าย
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * รายได้ที่รอดำเนินการ
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * รายได้ที่จ่ายแล้ว
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * รายได้ที่ถึงเวลาพร้อมจ่ายแล้ว
     */
    public function scopeReadyForPayout($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
            ->where(function ($q) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            });
    }

    /**
     * รายได้ของ user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * ตามประเภท
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('earning_type', $type);
    }

    /**
     * คำนวณรวมค่าหัก
     */
    public function getTotalDeductionsAttribute(): float
    {
        return $this->platform_fee
            + $this->vat_amount
            + $this->mlm_commission
            + $this->other_deductions
            + $this->debt_deduction;
    }

    /**
     * ทำให้พร้อมจ่าย
     */
    public function markAsAvailable(): bool
    {
        return $this->update([
            'status' => self::STATUS_AVAILABLE,
            'available_at' => now(),
        ]);
    }

    /**
     * ทำให้จ่ายแล้ว
     */
    public function markAsPaid(int $payoutRequestId, ?int $walletTransactionId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payout_request_id' => $payoutRequestId,
            'wallet_transaction_id' => $walletTransactionId,
        ]);
    }

    /**
     * ระงับ
     */
    public function hold(): bool
    {
        return $this->update(['status' => self::STATUS_HELD]);
    }

    /**
     * ยกเลิก
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * สีตามสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_AVAILABLE => 'blue',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_PAID => 'green',
            self::STATUS_HELD => 'orange',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Label ไทยตามสถานะ
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_AVAILABLE => 'พร้อมจ่าย',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_PAID => 'จ่ายแล้ว',
            self::STATUS_HELD => 'ถูกระงับ',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Label ไทยตามประเภท
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->earning_type) {
            self::TYPE_SELLER_SALE => 'รายได้จากการขาย',
            self::TYPE_SERVICE_PROVIDER => 'รายได้จากบริการ',
            self::TYPE_MLM_COMMISSION => 'คอมมิชชัน MLM',
            self::TYPE_AFFILIATE_COMMISSION => 'คอมมิชชัน Affiliate',
            self::TYPE_REFERRAL_BONUS => 'โบนัสแนะนำ',
            self::TYPE_OTHER => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }
}
