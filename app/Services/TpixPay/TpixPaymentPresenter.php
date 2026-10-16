<?php

namespace App\Services\TpixPay;

use App\Models\TpixPayment;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * รูปแบบข้อมูลใบเสนอยอด TPIX ที่ส่งให้หน้าเว็บ/แอป (ชุดเดียวกันทุกแพลตฟอร์ม)
 *
 * ❗ ไม่มีข้อมูลส่วนตัวของผู้ซื้อ — หน้าจ่ายเปิดด้วยรหัสอ้างอิง (ลิงก์ใบแจ้งหนี้) ได้โดยไม่ต้องล็อกอิน
 */
final class TpixPaymentPresenter
{
    private function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public static function present(TpixPayment $payment): array
    {
        $required = app(TpixPaySettings::class)->confirmations();
        $secondsLeft = $payment->expires_at ? max(0, now()->diffInSeconds($payment->expires_at, false)) : 0;
        // ตลาดสด / จองบริการ: ผลการสั่งหลังได้เหรียญ (เลขออเดอร์/การจอง + ข้อความไทย) — คีย์เพิ่ม แอปรุ่นเก่าไม่อ่านก็ไม่พัง
        $checkout = TpixCheckoutService::present($payment);

        return [
            'checkout' => $checkout,
            'fresh_market_order_id' => $checkout['fresh_market_order_id'] ?? null,
            'service_booking_id' => $checkout['service_booking_id'] ?? null,
            'fulfilment_status' => $checkout['status'] ?? null,
            'fulfilment_message' => $checkout['message'] ?? null,
            'method' => 'tpix',
            'reference' => $payment->reference,
            'purpose' => $payment->purpose,
            'order_id' => $payment->purpose === TpixPayment::PURPOSE_ORDER ? (int) $payment->payable_id : null,
            'order_number' => $payment->meta['order_number'] ?? null,
            'status' => $payment->status,
            'status_label' => $payment->statusLabel(),
            'is_settled' => $payment->isSettled(),
            'amount_thb' => round((float) $payment->amount_thb, 2),
            'amount_tpix' => TpixAmount::display((string) $payment->amount_tpix, 8, 8),
            'amount_tpix_exact' => rtrim(rtrim(bcadd((string) $payment->amount_tpix, '0', 8), '0'), '.'),
            'amount_tpix_display' => TpixAmount::display((string) $payment->amount_tpix, 8),
            'amount_wei' => (string) $payment->amount_wei,
            'amount_wei_hex' => TpixAmount::decToHex((string) $payment->amount_wei),
            'rate_thb' => (string) $payment->rate_thb,
            'rate_display' => TpixPriceService::formatRate((string) $payment->rate_thb),
            'rate_basis' => $payment->meta['rate_basis'] ?? 'spot',
            'price_usd' => $payment->price_usd !== null ? (string) $payment->price_usd : null,
            'usd_thb' => $payment->usd_thb !== null ? (string) $payment->usd_thb : null,
            'fixed_price_lines' => (int) ($payment->meta['fixed_price_lines'] ?? 0),
            'receive_address' => $payment->receive_address,
            'chain_id' => (int) config('tpix_pay.chain_id'),
            'chain_id_hex' => (string) config('tpix_pay.chain_id_hex'),
            'chain_name' => (string) config('tpix_pay.chain_name'),
            'rpc_url' => (string) config('tpix_pay.rpc_url'),
            'explorer_url' => (string) config('tpix_pay.explorer_url'),
            'eip681' => $payment->eip681Uri(),
            'pay_url' => route('tpix-pay.show', $payment->reference),
            'status_url' => route('tpix-pay.status', $payment->reference),
            'submit_url' => route('tpix-pay.submit', $payment->reference),
            'qr_url' => route('tpix-pay.qr', $payment->reference),
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'seconds_left' => (int) $secondsLeft,
            'confirmations' => (int) $payment->confirmations,
            'confirmations_required' => $required,
            'tx_hash' => $payment->tx_hash,
            'explorer_tx_url' => $payment->explorerTxUrl(),
            'failure_reason' => $payment->failure_reason,
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * ข้อมูล TPIX ของบิล (PaymentTransaction) — ใช้ค่าจาก processPayment ถ้ามี ไม่งั้นอ่านใบล่าสุดของบิล
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>|null
     */
    public static function forTransaction(\App\Models\PaymentTransaction $transaction, array $paymentData = []): ?array
    {
        if ($transaction->payment_method !== 'tpix') {
            return null;
        }

        if (isset($paymentData['tpix']) && is_array($paymentData['tpix'])) {
            return $paymentData['tpix'];
        }

        $payment = TpixPayment::where('payment_transaction_id', $transaction->id)->latest('id')->first();

        return $payment ? self::present($payment) : null;
    }

    /**
     * QR ของลิงก์ EIP-681 (SVG) — กระเป๋ามือถือสแกนแล้วกรอกที่อยู่+ยอดให้เอง
     */
    public static function qrSvg(TpixPayment $payment, int $size = 280): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($payment->eip681Uri());
    }
}
