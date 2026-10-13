<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Services\TpixPay\TpixPaymentPresenter;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use Exception;

/**
 * ช่องทางชำระด้วยเหรียญ TPIX (เชน 4289) ใน PaymentService
 *
 * process() = ออกใบเสนอยอดเหรียญ (ยังไม่ได้เงิน) → สถานะบิล processing
 * การยืนยันเงินเข้ามาจาก TpixPaymentService (ตรวจธุรกรรมบนเชน) แล้วเรียก PaymentService::completePayment
 */
class TpixPaymentProvider implements PaymentProviderInterface
{
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        if (! app(TpixPaySettings::class)->paymentsEnabled()) {
            throw new Exception('ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }

        return true;
    }

    public function process(PaymentTransaction $transaction, array $data): array
    {
        // TpixPayException = ข้อความภาษาไทยที่ผู้ใช้อ่านได้ → processPayment ส่งต่อเป็น message
        $payment = app(TpixPaymentService::class)->quoteForTransaction($transaction);
        $presented = TpixPaymentPresenter::present($payment);

        return [
            'status' => 'pending',
            'gateway' => 'tpix',
            'gateway_transaction_id' => $payment->reference,
            'response' => [
                'tpix_reference' => $payment->reference,
                'amount_tpix' => (string) $payment->amount_tpix,
                'rate_thb' => (string) $payment->rate_thb,
                'receive_address' => $payment->receive_address,
            ],
            'tpix' => $presented,
        ];
    }

    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // ยืนยันจากเชนเท่านั้น (TpixPaymentService) — ไม่มี webhook
        return false;
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // เหรียญที่ได้รับคืนอัตโนมัติไม่ได้ (ต้องเซ็นจากกระเป๋ารับเงิน) → คืนเป็นเงินเข้ากระเป๋าในระบบโดยแอดมิน
        return ['status' => 'manual', 'refund_amount' => $amount, 'message' => 'คืนเงิน TPIX ต้องดำเนินการโดยแอดมิน'];
    }
}
