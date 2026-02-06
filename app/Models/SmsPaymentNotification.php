<?php

namespace App\Models;

use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SMS Payment Notification Model
 *
 * เก็บข้อมูล SMS notification ที่ได้รับจาก Android App
 * ใช้ในการจับคู่กับ PaymentTransaction ที่ pending อยู่
 *
 * @property int $id
 * @property string $bank รหัสธนาคาร (KBANK, SCB, KTB, ...)
 * @property string $type ประเภท (credit/debit)
 * @property float $amount จำนวนเงิน
 * @property string|null $account_number หมายเลขบัญชี (masked)
 * @property string|null $sender_or_receiver ชื่อผู้ส่ง/ผู้รับ
 * @property string|null $reference_number หมายเลขอ้างอิง
 * @property \Carbon\Carbon $sms_timestamp เวลาที่ได้รับ SMS
 * @property string $device_id รหัสอุปกรณ์
 * @property string $nonce nonce สำหรับป้องกัน replay attack
 * @property string $status สถานะ (pending/matched/confirmed/rejected/expired)
 * @property int|null $matched_transaction_id ID ของ transaction ที่จับคู่ได้
 * @property string|null $raw_payload ข้อมูล payload ดิบ (JSON)
 * @property string|null $ip_address IP address ของอุปกรณ์
 */
class SmsPaymentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank',
        'type',
        'amount',
        'account_number',
        'sender_or_receiver',
        'reference_number',
        'sms_timestamp',
        'device_id',
        'nonce',
        'status',
        'matched_transaction_id',
        'raw_payload',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sms_timestamp' => 'datetime',
    ];

    // Scopes

    /**
     * กรองเฉพาะ notification ที่ยังรอจับคู่
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * กรองเฉพาะ notification ประเภทเงินเข้า
     */
    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * กรองเฉพาะ notification ประเภทเงินออก
     */
    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    // ความสัมพันธ์

    /**
     * อุปกรณ์ที่ส่ง notification นี้
     */
    public function device()
    {
        return $this->belongsTo(SmsCheckerDevice::class, 'device_id', 'device_id');
    }

    /**
     * Transaction ที่จับคู่ได้
     */
    public function matchedTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'matched_transaction_id');
    }

    /**
     * ดึงบัญชีธนาคารที่ตรงกับ bank code ของ notification นี้
     * ใช้ตรวจสอบว่า SMS มาจากธนาคารที่ลงทะเบียนไว้และเปิดใช้ SMS Checker
     *
     * หมายเหตุ: เช็คเฉพาะ bank code เท่านั้น (เช่น KBANK, SCB)
     * ไม่เช็คเลขบัญชี เพราะ SMS ไม่ได้แสดงเลขบัญชีเต็ม
     *
     * @return PaymentBankAccount|null
     */
    public function getMatchingBankAccount(): ?PaymentBankAccount
    {
        return PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->byBank($this->bank)
            ->first();
    }

    /**
     * ตรวจสอบว่า notification มาจากธนาคารที่ลงทะเบียนไว้หรือไม่
     *
     * เช็คว่ามีบัญชีธนาคารที่:
     * - ตรงกับ bank code (เช่น KBANK, SCB)
     * - เปิดใช้งาน (is_active = true)
     * - เปิด SMS Checker (sms_checker_enabled = true)
     *
     * @return bool
     */
    public function isFromRegisteredAccount(): bool
    {
        return $this->getMatchingBankAccount() !== null;
    }

    /**
     * พยายามจับคู่ notification นี้กับ pending payment transaction
     *
     * ลำดับการจับคู่:
     * 1. จับคู่ด้วย unique decimal amount
     * 2. Fallback: จับคู่ด้วย reference_number + จำนวนเงินใกล้เคียง
     *
     * @param bool $autoConfirm ยืนยัน transaction อัตโนมัติเมื่อจับคู่ได้
     * @return bool จับคู่สำเร็จหรือไม่
     */
    public function attemptMatch(bool $autoConfirm = true): bool
    {
        if ($this->type !== 'credit') {
            return false;
        }

        // ใช้ DB transaction + lock ป้องกัน race condition ในการจับคู่
        return \Illuminate\Support\Facades\DB::transaction(function () use ($autoConfirm) {
            // จับคู่ด้วย unique amount (พร้อม lock)
            $uniqueAmount = UniquePaymentAmount::where('unique_amount', $this->amount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($uniqueAmount) {
                // จับคู่สำเร็จ
                $this->status = 'matched';
                $this->matched_transaction_id = $uniqueAmount->transaction_id;
                $this->save();

                $uniqueAmount->status = 'used';
                $uniqueAmount->matched_at = now();
                $uniqueAmount->save();

                // ยืนยัน payment transaction อัตโนมัติ (ถ้าเปิดใช้งาน)
                // ใช้ PaymentService เพื่อให้ Order ถูกอัพเดทและ downstream logic ทำงาน
                if ($autoConfirm && $uniqueAmount->transaction_id) {
                    $transaction = PaymentTransaction::find($uniqueAmount->transaction_id);
                    if ($transaction && $transaction->status === 'pending') {
                        app(PaymentService::class)->completePayment($transaction);
                    }
                }

                return true;
            }

            // Fallback: จับคู่ด้วย reference_number (พร้อม lock)
            if ($this->reference_number) {
                $transaction = PaymentTransaction::where('promptpay_ref_no', $this->reference_number)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($transaction && abs((float) $transaction->amount - (float) $this->amount) < 0.01) {
                    $this->status = 'matched';
                    $this->matched_transaction_id = $transaction->id;
                    $this->save();

                    if ($autoConfirm) {
                        app(PaymentService::class)->completePayment($transaction);
                    }
                    return true;
                }
            }

            return false;
        });
    }
}
