<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PayoutRequest Model
 *
 * คำขอเบิกเงิน / รายการจ่ายเงิน
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $payout_setting_id
 * @property string $payout_type
 * @property string|null $earning_type ประเภทรายได้ต้นทาง เช่น mlm_commission
 * @property float $amount
 * @property float $fee
 * @property float $debt_deduction ยอดหนี้ที่หักไปตอนสร้างคำขอ
 * @property float $net_amount
 * @property string $status
 * @property \Carbon\Carbon|null $scheduled_at เวลาที่ตั้งไว้ให้จ่ายอัตโนมัติ
 * @property string $payment_method
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $approval_note
 * @property int|null $rejected_by
 * @property \Carbon\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $payment_reference
 * @property string|null $payment_note
 * @property string|null $error_message ข้อความ error ตอนจ่ายไม่สำเร็จ
 * @property int|null $wallet_transaction_id
 * @property string|null $description
 * @property array|null $metadata
 * @property string|null $batch_id
 */
class PayoutRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'payout_setting_id',
        'payout_type',
        'earning_type',
        'amount',
        'fee',
        'debt_deduction',
        'net_amount',
        'status',
        'scheduled_at',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'approved_by',
        'approved_at',
        'approval_note',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'paid_at',
        'payment_reference',
        'payment_note',
        'error_message',
        'wallet_transaction_id',
        'description',
        'metadata',
        'batch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'fee' => 'decimal:4',
        'debt_deduction' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * ประเภทการจ่าย
     */
    const TYPE_SELLER = 'seller';

    const TYPE_MLM = 'mlm';

    const TYPE_SERVICE = 'service';

    const TYPE_WITHDRAWAL = 'withdrawal';

    /**
     * สถานะ
     */
    const STATUS_PENDING = 'pending';

    /**
     * ตั้งเวลาจ่ายไว้แล้ว รอ cron `payouts:process-scheduled` ถึงกำหนด
     * ใช้เฉพาะ PayoutSetting ที่ payout_mode = 'auto_schedule'
     */
    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_APPROVED = 'approved';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PAID = 'paid';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_FAILED = 'failed';

    /**
     * วิธีการจ่าย
     */
    const METHOD_WALLET = 'wallet';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_PROMPTPAY = 'promptpay';

    const METHOD_OTHER = 'other';

    /**
     * ผู้ขอเบิก
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ตั้งค่า Payout ที่ใช้ตอนสร้างคำขอนี้
     */
    public function payoutSetting(): BelongsTo
    {
        return $this->belongsTo(PayoutSetting::class);
    }

    /**
     * ผู้อนุมัติ
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ผู้ปฏิเสธ
     */
    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Wallet Transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * รายการรายได้ที่เกี่ยวข้อง
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(EarningsLedger::class);
    }

    /**
     * รอดำเนินการ
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * ตั้งเวลาจ่ายไว้แล้ว รอถึงกำหนด
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * อนุมัติแล้ว รอจ่าย
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * จ่ายแล้ว
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * ของ user
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
        return $query->where('payout_type', $type);
    }

    /**
     * อนุมัติคำขอ
     */
    public function approve(int $approvedBy, ?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_note' => $note,
        ]);
    }

    /**
     * ปฏิเสธคำขอ
     */
    public function reject(int $rejectedBy, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * เริ่มดำเนินการจ่าย
     */
    public function startProcessing(): bool
    {
        return $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * จ่ายเงินสำเร็จ
     */
    public function markAsPaid(string $reference, ?int $walletTransactionId = null, ?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $reference,
            'wallet_transaction_id' => $walletTransactionId,
            'payment_note' => $note,
        ]);
    }

    /**
     * จ่ายเงินล้มเหลว
     */
    public function markAsFailed(?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'payment_note' => $note,
        ]);
    }

    /**
     * ยกเลิก
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * ตรวจสอบว่าจ่ายได้หรือไม่
     */
    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PENDING]);
    }

    /**
     * ตรวจสอบว่ายกเลิกได้หรือไม่
     */
    public function canBeCancelled(): bool
    {
        // รวม scheduled ด้วย เพราะยังไม่ได้จ่ายจริง ยกเลิกทัน
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_SCHEDULED,
            self::STATUS_APPROVED,
        ]);
    }

    /**
     * สีตามสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_SCHEDULED => 'indigo',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_PAID => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_FAILED => 'orange',
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
            self::STATUS_SCHEDULED => 'ตั้งเวลาจ่ายแล้ว',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_PAID => 'จ่ายแล้ว',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_FAILED => 'ล้มเหลว',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Label ไทยตามประเภท
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->payout_type) {
            self::TYPE_SELLER => 'จ่ายให้ผู้ขาย',
            self::TYPE_MLM => 'คอมมิชชัน MLM',
            self::TYPE_SERVICE => 'จ่ายให้ผู้ให้บริการ',
            self::TYPE_WITHDRAWAL => 'ถอนเงิน',
            default => 'อื่นๆ',
        };
    }

    /**
     * Label ไทยตามวิธีการจ่าย
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_WALLET => 'โอนเข้า Wallet',
            self::METHOD_BANK_TRANSFER => 'โอนเงินธนาคาร',
            self::METHOD_PROMPTPAY => 'พร้อมเพย์',
            self::METHOD_OTHER => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }
}
