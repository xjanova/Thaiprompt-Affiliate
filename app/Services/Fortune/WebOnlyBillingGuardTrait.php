<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * 🌐 (2026-11-03) FB web-only — คำตอบเมื่อ "จุดสร้างบิล" ใน FortuneConversationService ถูกบล็อก (T5)
 *
 * ใช้คู่กับ FbBillingGuard (ตัวตัดสิน) + WebOnlyGateTrait (webOnlyHandoff / saveWebOnlyAdminQuestion /
 * alertWebOnlyPayment) — ต้อง use ใน FCS ที่มี WebHandoffTrait + WebOnlyGateTrait อยู่แล้ว
 *
 * 2 แบบ:
 *   1. ยังไม่มีเงินเข้า → ปิดบิลเป็น moved_to_web + กล่องพาไปห้องแม่หมอ (ไม่มีวันคืน null)
 *   2. มีเงินเข้ามาแล้วบางส่วน (โอนขาด / ยอดกำกวม) → **ห้ามออก QR เติม** พักยอดไว้ให้แอดมินตัดสิน
 *      (partial_hold_at + ยกเลิก UPA กัน SMS จับคู่) + ฝากคำถาม + แจ้งแอดมิน
 *      แล้วตอบลูกค้าสั้น ๆ ไม่พูดเรื่องเงิน (design 1.4 ระดับ B)
 */
trait WebOnlyBillingGuardTrait
{
    /**
     * จุดสร้างบิลนี้ต้องหยุดไหม (FB + ธงเปิด) — log critical เมื่อบล็อก
     */
    protected function webOnlyBillingBlocked(?FortuneReading $reading, string $point, ?string $platformUserId = null): bool
    {
        return FbBillingGuard::blocks($reading, $platformUserId, $this->currentPlatform ?? null, $point);
    }

    /**
     * คำตอบแทนการออกบิล — ปิดบิลที่ยังไม่จ่ายเป็น moved_to_web แล้วพาไปห้องแม่หมอ
     *
     * @param  string  $kind  WebHandoffCopy::KIND_* (default KIND_READING)
     */
    protected function webOnlyBillBlockedResponse(FortuneReading $reading, string $point, string $kind = WebHandoffCopy::KIND_READING): array
    {
        FbBillingGuard::closeAsMovedToWeb($reading);

        $uid = (string) ($reading->facebook_user_id ?: ($reading->platform_user_id ?: ''));
        $name = $reading->facebook_user_name ?? null;

        // ไม่บันทึกข้อความลูกค้าซ้ำ (ด่านก่อนหน้า/processMessage บันทึกเอง) — บันทึกแค่คำตอบแม่หมอ
        $res = $this->webOnlyHandoff($uid, '', $kind, ['name' => $name], 'bill_guard:'.$point, false);
        $res['bill_guard'] = $point;
        $res['reading'] = null;

        return $res;
    }

    /**
     * มีเงินเข้ามาแล้วแต่ไม่ครบ/กำกวม — พักยอดให้แอดมิน ห้ามออก QR เติมบน FB
     *
     * @param  array  $fill  ฟิลด์ยอดสะสมที่ต้อง persist ก่อนพัก (partial_paid_total ฯลฯ)
     */
    protected function webOnlyParkMoneyForAdmin(FortuneReading $reading, string $userId, string $point, array $fill = []): array
    {
        if (trim($userId) === '') {
            $userId = (string) ($reading->facebook_user_id ?: ($reading->platform_user_id ?: ''));
        }

        try {
            $reading->forceFill(array_merge($fill, ['partial_hold_at' => now()]))->save();
        } catch (\Throwable $e) {
            Log::error('🌐 WebOnlyBilling: บันทึกยอดสะสมก่อนพักไม่สำเร็จ', [
                'reading_id' => $reading->id, 'point' => $point, 'error' => $e->getMessage(),
            ]);
        }

        // กัน SMS จับคู่ยอด top-up เก่าระหว่างพัก (เงินที่เข้ามาภายหลังไปคิวเงินกำพร้าให้แอดมินดู)
        try {
            $upa = $reading->uniquePaymentAmount;
            if ($upa && $upa->status === 'reserved') {
                $upa->cancel();
            }
        } catch (\Throwable $e) {
            // non-blocking
        }

        // ยอดสะสมที่เครดิตแล้ว หรือยอดสลิปที่พักไว้ยังไม่เครดิต (สลิปใบแรกของบิลโอนก่อนบิล)
        $paid = max(
            (float) ($reading->partial_paid_total ?? 0),
            (float) ($reading->getConversationState('web_only_parked_amount', 0) ?? 0)
        );
        $target = (float) ($reading->partial_target_total ?? 0);
        $note = '[FB web-only] ลูกค้าโอนมาไม่ครบ/ยอดกำกวม — ระบบไม่ออก QR เติมในแชท FB'
            .' · บิล '.($reading->bill_reference ?: '#'.$reading->id)
            .' · สะสม ฿'.number_format($paid, 2)
            .($target > 0 ? ' / เป้า ฿'.number_format($target, 2) : '')
            .' · ให้แอดมินตัดสิน (ส่งของ / คืนเงิน / เติมวอลเลตจันทรา) ห้ามตอบเรื่องเงินในแชท FB';

        $this->saveWebOnlyAdminQuestion($userId, $note, $reading->facebook_user_name ?? null);
        $this->alertWebOnlyPayment($userId, 'ยอดโอนไม่ครบ/กำกวม พักรอแอดมิน ('.$point.')', $reading);

        Log::warning('🌐 WebOnlyBilling: พักยอดที่โอนมาให้แอดมิน (ไม่ออก top-up บน FB)', [
            'reading_id' => $reading->id, 'point' => $point, 'paid_total' => $paid, 'target' => $target,
        ]);

        return [
            'action' => 'web_only_payment_ack',
            'message' => WebHandoffCopy::SLIP_RECEIVED,
            'reading' => $reading,
            'web_only_gate' => true,
            'bill_guard' => $point,
        ];
    }
}
