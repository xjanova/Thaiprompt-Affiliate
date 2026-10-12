<?php

namespace App\Models;

use App\Services\NotificationService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

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
 * @property string $status สถานะ (pending/matched/confirmed/rejected/expired/requires_admin_review/
 *                          external = เงินของเว็บ จันทรา.online ไม่ใช่ของเรา)
 * @property int|null $matched_transaction_id ID ของ transaction ที่จับคู่ได้
 * @property string|null $raw_payload ข้อมูล payload ดิบ (JSON)
 * @property string|null $ip_address IP address ของอุปกรณ์
 */
class SmsPaymentNotification extends Model
{
    use HasFactory;

    /**
     * 🧾 (2026-10-13) คีย์ใน raw_payload ที่บันทึกว่า "เงินก้อนนี้ตรงกับบิลที่ปิด/ยกเลิกไปแล้ว — แอดมินต้องคืนเงิน"
     *    ตารางนี้ไม่มีคอลัมน์ notes → เก็บโน้ตไว้ใน raw_payload แบบเดียวกับ _rejected_by_admin (PaymentReconController)
     */
    public const CLOSED_BILL_REVIEW_KEY = '_closed_bill_review';

    /** ประเภทยอดจองที่ไม่ใช่บิล PaymentTransaction (transaction_id ชี้ตารางอื่น/ไม่มี) — ห้ามเส้นอีคอมเมิร์ซแตะ */
    private const NON_PAYMENT_TRANSACTION_TYPES = ['fortune_reading', UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP];

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
     */
    public function isFromRegisteredAccount(): bool
    {
        return $this->getMatchingBankAccount() !== null;
    }

    /**
     * พยายามจับคู่ notification นี้กับ pending payment transaction
     *
     * ลำดับการจับคู่:
     * 1. จับคู่ด้วย unique decimal amount (UniquePaymentAmount ที่ยังไม่หมดอายุ)
     * 2. Fallback: จับคู่ด้วย PaymentTransaction.amount ตรงๆ (กรณี UniquePaymentAmount หมดอายุ แต่ transaction ยังเป็น pending)
     * 3. Fallback: จับคู่ด้วย reference_number + จำนวนเงินใกล้เคียง
     *
     * @param  bool  $autoConfirm  ยืนยัน transaction อัตโนมัติเมื่อจับคู่ได้
     * @return bool จับคู่สำเร็จหรือไม่
     */
    public function attemptMatch(bool $autoConfirm = true): bool
    {
        if ($this->type !== 'credit') {
            return false;
        }

        // 🔒 GUARD #1 (2026-04-28): SMS notification นี้ถูก match แล้ว → ห้าม reuse
        // กฎทอง: 1 SMS ใช้ได้กับ 1 บิล เท่านั้น
        if ($this->matched_transaction_id !== null) {
            \Illuminate\Support\Facades\Log::warning('🚫 SMS Payment: notification ถูก match แล้ว — ปฏิเสธ reuse (ecommerce)', [
                'notification_id' => $this->id,
                'matched_transaction_id' => $this->matched_transaction_id,
            ]);

            return false;
        }

        // 🔒 SMS timestamp — ใช้กัน SMS ที่มาก่อนบิลถูกสร้าง
        $smsTimestamp = $this->sms_timestamp ?? $this->created_at;

        // 🌙 (2026-09-15) เงินของเว็บ จันทรา.online — ห้ามตัดบิลของเรา (รวมทางลัดยอดดิบ/เลขอ้างอิงด้านล่าง)
        //   เส้น /notify กันไว้ก่อนแล้ว — ด่านนี้กันเส้นอื่นที่เรียกตรง เช่น แอดมินกด re-match (PaymentReconController)
        if ($this->status === 'external'
            || UniquePaymentAmount::findJuntrawebClaim((float) $this->amount, $smsTimestamp) !== null) {
            \Illuminate\Support\Facades\Log::info('🌙 SMS Payment: ยอดนี้เป็นของจันทรา.online — ไม่จับคู่บิลอีคอมเมิร์ซ', [
                'notification_id' => $this->id,
                'amount' => $this->amount,
            ]);

            return false;
        }

        // ใช้ DB transaction + lock ป้องกัน race condition ในการจับคู่
        return \Illuminate\Support\Facades\DB::transaction(function () use ($autoConfirm, $smsTimestamp) {
            // ดึง store_id ของ device เพื่อ filter เฉพาะ transactions ของร้านตัวเอง
            // ป้องกันการ match ข้ามร้านเมื่อมี multi-store
            // Admin device (store_id = null) → match ทุกร้าน (เพราะเป็น device หลักรับ SMS ของทั้ง platform)
            // Seller device → match เฉพาะร้านของตัวเอง
            $deviceModel = SmsCheckerDevice::where('device_id', $this->device_id)->first();
            $isAdminDevice = $deviceModel?->isAdminDevice() ?? true;
            $deviceStoreId = $isAdminDevice ? null : (int) $deviceModel->store_id;

            // ขั้นที่ 1: จับคู่ด้วย unique amount ที่ยังไม่หมดอายุ (พร้อม lock)
            // กรองเฉพาะบิลอีคอมเมิร์ซ (order, order_payment, topup, tarot_reading)
            // ไม่รวมบิลดูดวง (fortune_reading) เพราะจัดการแยกใน handleFortuneReadingPayment()
            // ⭐ (2026-04-28) เพิ่ม temporal check + FIFO order
            // 🌙 (2026-09-15) ไม่รวมยอดของจันทรา.online (juntraweb_topup) — ไม่ใช่บิลของเรา
            //   (NULL ถูกตัดทิ้งเหมือนเดิม: whereNotIn กับ != ให้ผลกับ NULL เท่ากัน)
            $uniqueAmountQuery = UniquePaymentAmount::where('unique_amount', $this->amount)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->whereNotIn('transaction_type', ['fortune_reading', UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP])
                ->where('created_at', '<=', $smsTimestamp)  // 🔒 SMS หลัง bill
                ->orderBy('created_at', 'asc')              // FIFO — bill เก่าสุด
                ->lockForUpdate();

            // Filter ตาม store_id ของ device (ป้องกัน match ข้ามร้าน)
            // Admin device → ไม่ filter store_id (match ทุกร้านได้)
            // Seller device → filter เฉพาะร้านตัวเอง
            if ($deviceStoreId !== null) {
                $uniqueAmountQuery->whereHas('transaction', function ($q) use ($deviceStoreId) {
                    $q->where('store_id', $deviceStoreId);
                });
            }

            $uniqueAmount = $uniqueAmountQuery->first();

            if ($uniqueAmount) {
                $transaction = $uniqueAmount->transaction_id
                    ? PaymentTransaction::find($uniqueAmount->transaction_id)
                    : null;

                // 🧾 (2026-10-13) ยอดจองยังค้าง reserved แต่บิล/ออเดอร์ถูกปิดไปแล้ว (ยกเลิก/ล้มเหลว/คืนเงิน)
                //   เดิม: ตั้ง SMS matched + ยอด used แล้วข้ามการตัดบิล (บิลไม่ใช่ pending) แต่ยังตอบ true
                //   = เงินลูกค้าถูกกลืนเงียบ ไม่มีออเดอร์ ไม่มีใครคืน และ SMS หายจากคิวเงินที่หาบิลไม่เจอของแอดมิน
                //   ตอนนี้: ไม่แตะยอดจอง ไม่ผูกบิล → ปักธงให้แอดมินตรวจ/คืนเงิน + แจ้งเตือนแอดมิน
                if ($transaction && ($closedReason = static::closedBillReason($transaction)) !== null) {
                    return $this->flagPaymentForClosedBill($transaction, $uniqueAmount, $closedReason);
                }

                // 🔒 (2026-10-13) ยอดจองต้องเป็น "ของบิลนี้จริง" ก่อนตัดบิล
                //   ทุกทางที่ระบบสร้างบิลจะเก็บ metadata.unique_amount_id และตั้งยอดบิล = ยอดจองเสมอ
                //   เดิมไม่เช็ค → ยอดจองที่ถูกผูกกับบิลอื่น (เช่นยิง generate-amount ฐาน ฿1 ใส่บิลเติมเงิน ฿10,000)
                //   โอน ฿1.xx แล้วบิลหลักหมื่นถูกตัดว่าจ่ายครบ → ยอดไม่ใช่ของบิล = ไม่ตัด ส่งให้แอดมินตรวจ
                if ($transaction && ! static::uniqueAmountBelongsTo($uniqueAmount, $transaction)) {
                    return $this->flagPaymentForClosedBill(
                        $transaction,
                        $uniqueAmount,
                        'unique_amount_mismatch',
                        sprintf(
                            '⚠️ ยอดโอน ฿%s ไม่ตรงกับยอดของบิล %s (฿%s) — ยอดจองนี้ไม่ได้สร้างจากบิลนี้ อาจเป็นการปลอมยอด ห้ามยืนยันบิลจากรายการนี้ ตรวจสอบแล้วคืนเงินตามยอดที่โอนจริง',
                            number_format((float) $this->amount, 2),
                            $transaction->transaction_id,
                            number_format((float) $transaction->amount, 2)
                        )
                    );
                }

                // จับคู่สำเร็จ
                $this->status = 'matched';
                $this->matched_transaction_id = $uniqueAmount->transaction_id;
                $this->save();

                $uniqueAmount->status = 'used';
                $uniqueAmount->matched_at = now();
                $uniqueAmount->save();

                // ยืนยัน payment transaction อัตโนมัติ (ถ้าเปิดใช้งาน)
                // ใช้ PaymentService เพื่อให้ Order ถูกอัพเดทและ downstream logic ทำงาน
                // ⚠️ ต้องเช็คทั้ง 'pending' และ 'processing' เพราะ:
                // - pending = เพิ่งสร้าง transaction ยังไม่ได้เรียก processPayment()
                // - processing = เรียก processPayment() แล้ว (เช่น PromptPay สร้าง QR แล้ว) รอลูกค้าโอน
                if ($autoConfirm && $uniqueAmount->transaction_id) {
                    if ($transaction && in_array($transaction->status, ['pending', 'processing'])) {
                        try {
                            app(PaymentService::class)->completePayment($transaction);
                        } catch (\Exception $e) {
                            // Log error แต่ไม่ rollback การจับคู่ — admin สามารถ approve ด้วยมือได้
                            \Illuminate\Support\Facades\Log::error('SMS Payment: completePayment ล้มเหลว (Step 1)', [
                                'notification_id' => $this->id,
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                return true;
            }

            // ขั้นที่ 2: Fallback - จับคู่ด้วย PaymentTransaction.amount ตรงๆ
            // กรณี UniquePaymentAmount หมดอายุแล้ว (เช่น ลูกค้าจ่ายหลัง 30 นาที)
            // แต่ PaymentTransaction ยังเป็น pending อยู่ (ยังไม่ถูก cleanup)
            // ใช้ exact match กับ amount ที่มีทศนิยม (unique decimal) เพื่อความแม่นยำ
            // ⭐ (2026-04-28) เพิ่ม temporal check + FIFO — กัน SMS เก่ามา match transaction ใหม่
            $txnQuery = PaymentTransaction::where('amount', $this->amount)
                ->whereIn('status', ['pending', 'processing'])
                ->whereIn('payment_method', ['promptpay', 'bank_transfer'])
                ->where('created_at', '<=', $smsTimestamp)  // 🔒 SMS หลัง transaction
                ->lockForUpdate()
                ->orderBy('created_at', 'asc');             // FIFO — txn เก่าสุดก่อน

            // Admin device → match ทุกร้าน, Seller device → match เฉพาะร้านตัวเอง
            if ($deviceStoreId !== null) {
                $txnQuery->where('store_id', $deviceStoreId);
            }

            // 🧾 (2026-10-13) บิลยังรอจ่ายแต่ออเดอร์ถูกยกเลิกไปแล้ว (ข้อมูลเก่าก่อนปิดบิลตอนยกเลิก) → ห้ามตัดเงียบ
            //   ไล่ FIFO หาใบแรกที่ออเดอร์ยังเปิดอยู่ · ไม่มีเลยแต่เจอใบที่ปิดแล้ว = ปักธงให้แอดมินคืนเงิน
            $transaction = null;
            $closedCandidate = null;
            $closedCandidateReason = null;
            foreach ($txnQuery->get() as $candidate) {
                $reason = static::closedBillReason($candidate);
                if ($reason === null) {
                    $transaction = $candidate;
                    break;
                }
                if ($closedCandidate === null) {
                    $closedCandidate = $candidate;
                    $closedCandidateReason = $reason;
                }
            }

            if (! $transaction && $closedCandidate) {
                return $this->flagPaymentForClosedBill($closedCandidate, null, $closedCandidateReason);
            }

            if ($transaction) {
                $this->status = 'matched';
                $this->matched_transaction_id = $transaction->id;
                $this->save();

                // อัพเดท UniquePaymentAmount ที่หมดอายุให้เป็น used (ถ้ามี)
                //   🧾 (2026-10-13) ต้องไม่ใช่ยอดดูดวง/จันทรา — transaction_id ของสองประเภทนั้นชี้ตารางอื่น
                //   (เลข id ชนกันได้ = ไปปิดยอดจองของบิลดูดวงคนอื่นเป็น used แล้วบิลนั้นรับเงินไม่ได้)
                $expiredUniqueAmount = UniquePaymentAmount::where('transaction_id', $transaction->id)
                    ->where(function ($q) {
                        $q->whereNull('transaction_type')
                            ->orWhereNotIn('transaction_type', self::NON_PAYMENT_TRANSACTION_TYPES);
                    })
                    ->whereIn('status', ['reserved', 'expired'])
                    ->first();
                if ($expiredUniqueAmount) {
                    $expiredUniqueAmount->update(['status' => 'used', 'matched_at' => now()]);
                }

                if ($autoConfirm) {
                    try {
                        app(PaymentService::class)->completePayment($transaction);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('SMS Payment: completePayment ล้มเหลว (Step 2 fallback)', [
                            'notification_id' => $this->id,
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                \Illuminate\Support\Facades\Log::info('SMS Payment: จับคู่ผ่าน PaymentTransaction fallback (UniquePaymentAmount หมดอายุ)', [
                    'notification_id' => $this->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $this->amount,
                    'device_store_id' => $deviceStoreId,
                ]);

                return true;
            }

            // ขั้นที่ 3: Fallback - จับคู่ด้วย reference_number (พร้อม lock)
            // ⭐ (2026-04-28) เพิ่ม temporal check
            if ($this->reference_number) {
                $refQuery = PaymentTransaction::where('promptpay_ref_no', $this->reference_number)
                    ->whereIn('status', ['pending', 'processing'])
                    ->where('created_at', '<=', $smsTimestamp)  // 🔒 SMS หลัง transaction
                    ->lockForUpdate();

                // Admin device → match ทุกร้าน, Seller device → match เฉพาะร้านตัวเอง
                if ($deviceStoreId !== null) {
                    $refQuery->where('store_id', $deviceStoreId);
                }

                $transaction = $refQuery->first();

                if ($transaction && abs((float) $transaction->amount - (float) $this->amount) < 0.01) {
                    // 🧾 (2026-10-13) ออเดอร์ของบิลนี้ถูกยกเลิกแล้ว → ปักธงให้แอดมิน ไม่ตัดเงียบ (เหมือนขั้นที่ 1-2)
                    if (($closedReason = static::closedBillReason($transaction)) !== null) {
                        return $this->flagPaymentForClosedBill($transaction, null, $closedReason);
                    }

                    $this->status = 'matched';
                    $this->matched_transaction_id = $transaction->id;
                    $this->save();

                    if ($autoConfirm) {
                        try {
                            app(PaymentService::class)->completePayment($transaction);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('SMS Payment: completePayment ล้มเหลว (Step 3 ref match)', [
                                'notification_id' => $this->id,
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    return true;
                }
            }

            return false;
        });
    }

    // =====================================================
    // 🧾 (2026-10-13) เงินโอนเข้าบิลที่ปิด/ยกเลิกไปแล้ว — ห้ามกลืนเงียบ ต้องถึงมือแอดมิน
    // =====================================================

    /**
     * บิลนี้ "ปิดแล้ว" หรือยัง (รับเงินเพิ่มไม่ได้ ถ้ามีเงินเข้ามาต้องให้แอดมินคืน)
     *
     *   - บิลถูกยกเลิก / ล้มเหลว / คืนเงินไปแล้ว
     *   - หรือออเดอร์ของบิลถูกยกเลิก / คืนเงิน / ถูกลบ — completePayment จะไม่เปิดออเดอร์ให้ (log อย่างเดียว)
     *
     * บิลที่ completed กับออเดอร์ที่ยังเปิดอยู่ = ไม่ถือว่าปิด (SMS คือหลักฐานของบิลที่แอดมินยืนยันมือไปก่อน — พฤติกรรมเดิม)
     *
     * @return string|null เหตุผล (เช่น order_cancelled) · null = บิลยังเปิด
     */
    public static function closedBillReason(PaymentTransaction $transaction): ?string
    {
        if (in_array($transaction->status, ['cancelled', 'failed', 'refunded'], true)) {
            return 'transaction_'.$transaction->status;
        }

        if ($transaction->order_id) {
            $order = Order::withTrashed()->find($transaction->order_id);

            if (! $order) {
                return 'order_missing';
            }

            if ($order->trashed()) {
                return 'order_deleted';
            }

            if (in_array($order->status, ['cancelled', 'refunded'], true)) {
                return 'order_'.$order->status;
            }
        }

        return null;
    }

    /**
     * 🔒 (2026-10-13) ยอดจองนี้ถูกสร้างจากบิลนี้จริงไหม
     *
     * ทุกทางที่ระบบสร้างยอดจองให้บิล (PaymentService · PaymentTransaction::booted · CheckoutController)
     * เก็บ metadata.unique_amount_id = ยอดจอง และตั้งยอดบิล = ยอดจอง (มีทศนิยม) เสมอ
     * → ผ่านข้อใดข้อหนึ่ง = ของบิลนี้ · ไม่ผ่านทั้งคู่ = ยอดจองถูกผูกมาจากที่อื่น ห้ามตัดบิล
     */
    public static function uniqueAmountBelongsTo(UniquePaymentAmount $uniqueAmount, PaymentTransaction $transaction): bool
    {
        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];

        if ((int) ($metadata['unique_amount_id'] ?? 0) === (int) $uniqueAmount->id) {
            return true;
        }

        return abs((float) $transaction->amount - (float) $uniqueAmount->unique_amount) < 0.005;
    }

    /**
     * ขั้นสุดท้ายของ /notify — SMS ที่ไม่มีบิลไหนรับเลย: ยอดตรงกับ "ยอดจองของบิลที่ปิดไปแล้ว" ไหม
     *
     * เคส: ยกเลิกออเดอร์/ขอ QR ใหม่ → ยอดจองถูกปล่อย (cancelled) หรือหมดเวลา (expired) → ลูกค้าโอนตาม QR เดิมทีหลัง
     *   ไม่มีบิลไหนจับคู่ได้ (ถูกต้อง) แต่แอดมินต้องรู้ว่าเงินก้อนนี้ของออเดอร์ไหน จะได้คืนเงิน/ยืนยันให้ถูกคน
     *
     * เกณฑ์ (ยอมไม่เจอ ดีกว่าชี้ผิดใบ):
     *   - ยอดเท่ากันเป๊ะ · ยอดจองยังไม่ถูกใช้ (status != used) · เป็นบิล PaymentTransaction (ไม่ใช่ดูดวง/จันทรา)
     *   - ยอดจองเกิด "ก่อน" SMS และไม่เกิน 24 ชม. (ช่วงที่ generate() ห้ามแจกทศนิยมนี้ซ้ำ → ยอดนี้มีเจ้าของคนเดียว)
     *   - เครื่องร้านค้าเห็นเฉพาะบิลของร้านตัวเอง (เหมือน attemptMatch)
     *
     * ⚠️ เรียกหลังด่านดูดวงทุกด่าน (ไม่แย่งเงินที่บิลดูดวงรับได้) — ไม่ผูกบิล ไม่แตะยอดจอง แค่ปักธง + แจ้งแอดมิน
     *
     * @return bool true = ปักธงให้แอดมินแล้ว
     */
    public function flagIfPaidToClosedBill(): bool
    {
        if ($this->type !== 'credit' || $this->matched_transaction_id !== null) {
            return false;
        }

        $smsTimestamp = $this->sms_timestamp ?? $this->created_at;
        if ($smsTimestamp === null) {
            return false;
        }

        $deviceModel = SmsCheckerDevice::where('device_id', $this->device_id)->first();
        $deviceStoreId = ($deviceModel?->isAdminDevice() ?? true) ? null : (int) $deviceModel->store_id;

        $candidates = UniquePaymentAmount::where('unique_amount', $this->amount)
            ->where('status', '!=', 'used')
            ->whereNotNull('transaction_id')
            ->where(function ($q) {
                $q->whereNull('transaction_type')
                    ->orWhereNotIn('transaction_type', self::NON_PAYMENT_TRANSACTION_TYPES);
            })
            ->where('created_at', '<=', $smsTimestamp)
            ->where('created_at', '>=', $smsTimestamp->copy()->subMinutes(UniquePaymentAmount::SUFFIX_COOLDOWN_MINUTES))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($candidates as $uniqueAmount) {
            $transaction = PaymentTransaction::find($uniqueAmount->transaction_id);
            if (! $transaction) {
                continue;
            }

            if ($deviceStoreId !== null && (int) $transaction->store_id !== $deviceStoreId) {
                continue;
            }

            $reason = static::closedBillReason($transaction);
            if ($reason === null) {
                // บิลยังเปิดอยู่แต่จับคู่ไม่ได้ (เช่น ร้านอื่น/วิธีชำระอื่น) — ไม่ใช่เคสเงินเข้าบิลที่ปิด
                continue;
            }

            $this->flagPaymentForClosedBill($transaction, $uniqueAmount, $reason);

            return true;
        }

        return false;
    }

    /**
     * SMS ใบนี้ถูกปักธง "เงินเข้าบิลที่ปิดแล้ว" หรือไม่
     */
    public function isClosedBillReview(): bool
    {
        return isset($this->rawPayloadArray()[self::CLOSED_BILL_REVIEW_KEY]);
    }

    /**
     * โน้ตภาษาไทยสำหรับแอดมิน (null = ไม่ได้ถูกปักธง)
     */
    public function closedBillReviewNote(): ?string
    {
        $review = $this->rawPayloadArray()[self::CLOSED_BILL_REVIEW_KEY] ?? null;

        return is_array($review) && isset($review['note']) ? (string) $review['note'] : null;
    }

    /**
     * ปักธง SMS ให้อยู่ในคิวแอดมิน (requires_admin_review · ไม่ผูกบิล) + โน้ตไทย + แจ้งเตือนแอดมิน
     *
     * ไม่แตะยอดจอง (ไม่ตั้ง used) และไม่ตั้ง matched_transaction_id → คิว "SMS ที่ยังไม่ผูกบิล"
     * (OpsSummaryController::smsUnmatched — pending/requires_admin_review + matched_transaction_id ว่าง) ยังเห็นใบนี้
     *
     * @return bool false เสมอ (= ไม่ได้จับคู่) — ให้ caller return ต่อได้ทันที
     */
    protected function flagPaymentForClosedBill(PaymentTransaction $transaction, ?UniquePaymentAmount $uniqueAmount, string $reason, ?string $note = null): bool
    {
        $order = $transaction->order_id ? Order::withTrashed()->find($transaction->order_id) : null;
        $note ??= static::closedBillNote($transaction, $order);

        $payload = $this->rawPayloadArray();
        $alreadyFlagged = isset($payload[self::CLOSED_BILL_REVIEW_KEY]);
        $payload[self::CLOSED_BILL_REVIEW_KEY] = [
            'note' => $note,
            'reason' => $reason,
            'transaction_id' => $transaction->id,
            'bill_reference' => $transaction->transaction_id,
            'transaction_status' => $transaction->status,
            'order_id' => $order?->id,
            'order_number' => $order?->order_number,
            'order_status' => $order?->status,
            'unique_amount_id' => $uniqueAmount?->id ?? ($transaction->metadata['unique_amount_id'] ?? null),
            'flagged_at' => now()->toIso8601String(),
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        try {
            static::whereKey($this->id)->update([
                'status' => 'requires_admin_review',
                'raw_payload' => $encoded,
            ]);
        } catch (QueryException $e) {
            // enum ยังไม่มี requires_admin_review (migration 2026_05_05_000100) → คง pending (ยังอยู่ในคิวแอดมิน) เก็บแค่โน้ต
            Log::error('SMS Payment: status enum ไม่มี requires_admin_review — เก็บโน้ตอย่างเดียว', [
                'notification_id' => $this->id,
                'sql_error' => $e->getMessage(),
            ]);
            static::whereKey($this->id)->update(['raw_payload' => $encoded]);
        }

        $this->refresh();

        Log::critical('🧾 SMS Payment: เงินโอนเข้าบิลที่ปิด/ยกเลิกไปแล้ว — ไม่ตัดบิล รอแอดมินตรวจ/คืนเงิน', [
            'notification_id' => $this->id,
            'amount' => $this->amount,
            'reason' => $reason,
            'transaction_id' => $transaction->id,
            'transaction_status' => $transaction->status,
            'order_id' => $order?->id,
            'order_status' => $order?->status,
            'unique_amount_id' => $uniqueAmount?->id,
        ]);

        // แจ้งแอดมินหลัง commit เท่านั้น (ถ้าการจับคู่ทั้งก้อน rollback จะไม่มีแจ้งเตือนหลอก) · ปักธงซ้ำไม่แจ้งซ้ำ
        if (! $alreadyFlagged) {
            DB::afterCommit(function () use ($note) {
                static::alertAdminsClosedBillPayment($this, $note);
            });
        }

        return false;
    }

    /**
     * ข้อความไทยบอกแอดมินว่าเงินก้อนนี้ของออเดอร์/บิลไหน และต้องทำอะไรต่อ
     */
    private static function closedBillNote(PaymentTransaction $transaction, ?Order $order): string
    {
        if ($order === null) {
            return "ยอดโอนตรงกับบิล {$transaction->transaction_id} ที่ปิดไปแล้ว (สถานะ {$transaction->status}) — ตรวจสอบและคืนเงินลูกค้า";
        }

        $orderLabel = '#'.($order->order_number ?: $order->id);

        if ($order->trashed() || in_array($order->status, ['cancelled', 'refunded'], true)) {
            return "ยอดโอนตรงกับออเดอร์ที่ยกเลิกแล้ว {$orderLabel} — ตรวจสอบและคืนเงินลูกค้า";
        }

        if ($order->payment_status === 'paid') {
            return "ยอดโอนตรงกับบิลเดิมของออเดอร์ {$orderLabel} ที่ชำระด้วยช่องทางอื่นแล้ว (อาจจ่ายซ้ำ) — ตรวจสอบและคืนเงินลูกค้า";
        }

        return "ยอดโอนตรงกับบิลเดิมของออเดอร์ {$orderLabel} ที่ถูกปิดไปแล้ว (ออเดอร์ยังรอชำระ) — ตรวจสอบแล้วยืนยันการชำระหรือคืนเงินลูกค้า";
    }

    /**
     * แจ้งแอดมินทุกคน (กล่องแจ้งเตือน + push ผ่าน NotificationService · ไม่ใช้ LINE push) — ล้มได้ ไม่กระทบการรับ SMS
     */
    private static function alertAdminsClosedBillPayment(self $sms, string $note): void
    {
        try {
            $admins = User::query()
                ->where(function ($q) {
                    $q->where('is_super_admin', true)->orWhereIn('role', ['admin', 'super_admin']);
                })
                ->whereNull('blocked_at')
                ->limit(50)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('SMS Payment: โหลดรายชื่อแอดมินไม่สำเร็จ (แจ้งเงินเข้าบิลที่ปิดแล้ว)', [
                'notification_id' => $sms->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $amount = number_format((float) $sms->amount, 2);
        $sender = $sms->sender_or_receiver ?: 'ไม่ทราบชื่อผู้โอน';
        $actionUrl = Route::has('admin.smschecker.notifications')
            ? route('admin.smschecker.notifications', ['status' => 'requires_admin_review'])
            : null;
        $service = app(NotificationService::class);

        foreach ($admins as $admin) {
            try {
                $service->create(
                    $admin,
                    'sms_payment_review',
                    "💸 เงินโอนเข้าบิลที่ปิดแล้ว ฿{$amount}",
                    "{$note} · ผู้โอน {$sender} ({$sms->bank}) · SMS #{$sms->id}",
                    [
                        'sms_notification_id' => $sms->id,
                        'amount' => (float) $sms->amount,
                        'review' => $sms->rawPayloadArray()[self::CLOSED_BILL_REVIEW_KEY] ?? null,
                    ],
                    $actionUrl,
                    'ตรวจสอบ',
                    'high',
                    true,
                    true,
                    '💸',
                    'red'
                );
            } catch (\Throwable $e) {
                Log::warning('SMS Payment: แจ้งแอดมินเรื่องเงินเข้าบิลที่ปิดแล้วไม่สำเร็จ', [
                    'notification_id' => $sms->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * raw_payload เป็น array (ว่าง/ไม่ใช่ JSON → เก็บค่าเดิมไว้ใต้ original แบบเดียวกับ PaymentReconController::reject)
     */
    private function rawPayloadArray(): array
    {
        $raw = $this->raw_payload;
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['original' => $raw];
    }
}
