<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\TarotReading;
use App\Models\TarotUserLimit;
use Illuminate\Support\Facades\Log;

/**
 * TarotPaymentService — จบงานบิลทำนายไพ่หลัง "เงินเข้าจริง"
 *
 * ถูกเรียกจาก PaymentService::completePayment() เมื่อ transaction
 * ที่มี metadata.type = 'tarot_reading' ถูกยืนยันการชำระเงิน
 * (SMS Checker match / แอดมิน approve)
 *
 * รองรับ 2 flow:
 * - Single reading (TarotReadingController): metadata.reading_id
 * - Cart checkout (TarotCartController): metadata.reading_ids[]
 *
 * หน้าที่:
 * 1. เปลี่ยน payment_status ของ reading → 'paid'
 * 2. แบ่งคอมมิชชั่น MLM ผ่าน TarotCommissionService (ครั้งแรกที่เงินเข้าเท่านั้น)
 * 3. นับ paid reading ใน TarotUserLimit (เฉพาะ single flow —
 *    cart flow นับไปแล้วตอน checkout)
 *
 * ⚠️ idempotent: เรียกซ้ำได้ ไม่จ่ายคอม/นับ limit ซ้ำ
 */
class TarotPaymentService
{
    public function __construct(
        protected TarotCommissionService $commissionService
    ) {}

    /**
     * จบงานทุก reading ที่ผูกกับ transaction ที่เพิ่งชำระเงินสำเร็จ
     *
     * ห้าม throw ออกไปกระทบ completePayment — เก็บ error เป็น log
     * (เงินเข้าแล้ว transaction ต้อง completed เสมอ ส่วน reading
     * ที่ finalize พลาดจะถูก retry จาก paymentStatus polling)
     *
     * @param  PaymentTransaction  $transaction  transaction ที่ถูก mark completed แล้ว
     */
    public function finalizePaidTransaction(PaymentTransaction $transaction): void
    {
        $metadata = $transaction->metadata ?? [];

        // รวม reading ids จากทั้ง 2 flow
        $singleReadingId = $metadata['reading_id'] ?? null;
        $cartReadingIds = $metadata['reading_ids'] ?? [];

        $readingIds = array_filter(array_unique(array_merge(
            $singleReadingId ? [$singleReadingId] : [],
            is_array($cartReadingIds) ? $cartReadingIds : [],
        )));

        if (empty($readingIds)) {
            Log::warning('TarotPayment: transaction ไม่มี reading_id ใน metadata — ข้าม finalize', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        foreach ($readingIds as $readingId) {
            try {
                $this->finalizeReading(
                    (int) $readingId,
                    $transaction,
                    // single flow = ยังไม่เคยนับ paid limit (cart นับตอน checkout แล้ว)
                    countLimit: $singleReadingId && (int) $readingId === (int) $singleReadingId,
                );
            } catch (\Throwable $e) {
                // ห้าม error ของ reading หนึ่งกระทบตัวอื่น/การ complete transaction
                Log::error('TarotPayment: finalize reading ล้มเหลว', [
                    'transaction_id' => $transaction->id,
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * จบงาน reading เดียว: mark paid + จ่ายคอมมิชชั่น + นับ limit
     *
     * @param  int  $readingId  ID ของ reading
     * @param  PaymentTransaction  $transaction  transaction ที่ชำระแล้ว
     * @param  bool  $countLimit  true = นับ paid reading ใน TarotUserLimit
     */
    protected function finalizeReading(int $readingId, PaymentTransaction $transaction, bool $countLimit): void
    {
        $reading = TarotReading::find($readingId);

        if (! $reading) {
            Log::warning('TarotPayment: ไม่พบ reading — ข้าม', [
                'reading_id' => $readingId,
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        // idempotent: จบงานไปแล้ว (จาก polling retry / เรียกซ้ำ) → ข้าม
        if ($reading->payment_status === 'paid') {
            return;
        }

        // ยอดจริงของ reading นี้ — single flow เก็บใน amount_paid,
        // cart flow เก็บใน price (amount_paid ยังเป็น 0)
        $amount = (float) ($reading->amount_paid ?? 0);
        if ($amount <= 0) {
            $amount = (float) ($reading->price ?? 0);
        }

        // 1. mark reading เป็นชำระแล้ว — atomic claim กัน race
        //    (finalize อาจถูกเรียกพร้อมกันจาก SMS confirm + polling retry
        //    → คนแรกที่ update สำเร็จเท่านั้นที่ไปจ่ายคอม/นับ limit ต่อ)
        $claimed = TarotReading::where('id', $reading->id)
            ->where('payment_status', '!=', 'paid')
            ->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'amount_paid' => $amount,
                'payment_method' => $transaction->payment_method,
                'payment_transaction_id' => $transaction->id,
            ]);

        if (! $claimed) {
            return; // process อื่น claim ไปแล้ว
        }

        // 2. แบ่งคอมมิชชั่น MLM — เฉพาะที่ยังไม่เคยจ่าย
        //    (processPayment ไม่หักเงินซ้ำเพราะ method ไม่ใช่ wallet)
        if ($reading->commission_status !== 'processed') {
            $result = $this->commissionService->processPayment(
                $reading->fresh(),
                $transaction->payment_method,
                $transaction->id,
            );

            if (! $result['success']) {
                // เงินเข้าแล้ว — คอมพลาดต้องเห็นใน log ชัดๆ ให้แอดมินตามแก้
                Log::error('TarotPayment: แบ่งคอมมิชชั่นล้มเหลวหลังเงินเข้า', [
                    'reading_id' => $reading->id,
                    'transaction_id' => $transaction->id,
                    'error' => $result['message'] ?? 'unknown',
                ]);
            }
        }

        // 3. นับ paid reading (เฉพาะ single flow)
        if ($countLimit && $reading->category_id) {
            try {
                TarotUserLimit::incrementPaidReading(
                    $reading->category_id,
                    $reading->user_id,
                    $reading->session_id,
                    $reading->ip_address,
                );
            } catch (\Throwable $limitErr) {
                Log::warning('TarotPayment: นับ paid limit ไม่สำเร็จ (ไม่ critical)', [
                    'reading_id' => $reading->id,
                    'error' => $limitErr->getMessage(),
                ]);
            }
        }

        Log::info('TarotPayment: finalize reading สำเร็จ (เงินเข้า → เปิดคำทำนาย)', [
            'reading_id' => $reading->id,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'payment_method' => $transaction->payment_method,
        ]);
    }
}
