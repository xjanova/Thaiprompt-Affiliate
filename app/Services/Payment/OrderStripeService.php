<?php

namespace App\Services\Payment;

use App\Models\FortuneTellingSetting;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Order Stripe Service — ชำระค่าสินค้า (อีคอมเมิร์ซ) ด้วยบัตรเครดิต/เดบิตผ่าน Stripe
 *
 * ใช้ "บัญชี/คีย์ Stripe เดียวกับฝั่งดูดวง" (เก็บใน FortuneTellingSetting) แต่
 * เป็น "คนละส่วน" กับ FortuneStripeService อย่างสมบูรณ์ — ไม่แตะโค้ดดูดวง
 *
 * Flow:
 *   1. createCheckoutSession($order, $transaction) — POST /v1/checkout/sessions
 *      → redirect ลูกค้าไปหน้า Stripe (hosted, PCI-safe)
 *   2. ลูกค้าจ่ายบัตร → กลับมา success_url → CheckoutController::stripeReturn()
 *      เรียก retrieveSession + completeOrderFromSession (ทางหลัก)
 *   3. webhook (POST /webhook/order-stripe) + pollPendingSessions() = ตัวสำรอง
 *      กันกรณีลูกค้าปิดแท็บหลังจ่าย
 *
 * ทุกทางเรียก completeOrderFromSession() ซึ่ง idempotent (กันตัดซ้ำด้วย lock + isCompleted)
 */
class OrderStripeService
{
    protected ?FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::query()->first();
    }

    /**
     * เปิดใช้งานบัตรเครดิตฝั่งร้านค้าหรือไม่
     *
     * ⚠️ "แยกคนละส่วนกับดูดวง": ใช้ "คีย์ Stripe เดียวกัน" (จาก FortuneTellingSetting)
     * แต่ "ไม่ผูกกับ toggle enable_stripe_payment ของดูดวง" — เปิด/ปิดอิสระจากกัน
     * เงื่อนไข = มี secret key + webhook secret ครบ (ตั้งไว้แล้วฝั่งดูดวง)
     */
    public function isEnabled(): bool
    {
        return $this->settings
            && ! empty($this->settings->stripe_secret_key)
            && ! empty($this->settings->stripe_webhook_secret);
    }

    private function secretKey(): ?string
    {
        return $this->settings->stripe_secret_key ?? null;
    }

    /**
     * สร้าง Stripe Checkout Session สำหรับ 1 ออเดอร์
     *
     * @return array{success: bool, url?: string, session_id?: string, error?: string}
     */
    public function createCheckoutSession(Order $order, PaymentTransaction $transaction): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'error' => 'ยังไม่ได้เปิดใช้งานการชำระด้วยบัตร (Stripe)'];
        }

        try {
            $totalSatang = (int) round(((float) $order->total_amount) * 100);
            if ($totalSatang < 2000) {
                // Stripe THB ขั้นต่ำ ~20 บาท
                return ['success' => false, 'error' => 'ยอดชำระต่ำกว่าขั้นต่ำของบัตร (20 บาท)'];
            }

            $payload = [
                'mode' => 'payment',
                'payment_method_types[0]' => 'card',
                'metadata[order_id]' => (string) $order->id,
                'metadata[order_number]' => (string) $order->order_number,
                'metadata[transaction_id]' => (string) $transaction->id,
                'metadata[type]' => 'order_payment',
                'success_url' => route('checkout.stripe.return', ['orderId' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.stripe.cancel', ['orderId' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'locale' => 'auto',
                'billing_address_collection' => 'required',
                'expires_at' => time() + (31 * 60),
            ];

            // เพิ่ม line items แบบรายการสินค้า + ค่าส่ง — ถ้ารวมแล้วไม่ตรง total (เช่นมีส่วนลด)
            // จะ fallback เป็นรายการเดียว = ยอดรวม เพื่อให้ Stripe คิดเงินตรงกับออเดอร์เสมอ
            $lines = $this->buildLineItems($order, $totalSatang);
            foreach ($lines as $i => $line) {
                $payload["line_items[{$i}][price_data][currency]"] = 'thb';
                $payload["line_items[{$i}][price_data][product_data][name]"] = $line['name'];
                $payload["line_items[{$i}][price_data][unit_amount]"] = $line['unit_amount'];
                $payload["line_items[{$i}][quantity]"] = $line['quantity'];
            }

            $response = Http::withBasicAuth($this->secretKey(), '')
                ->asForm()
                ->timeout(30)
                ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

            if ($response->failed()) {
                $msg = $response->json('error.message') ?? 'Stripe API error';
                Log::error('OrderStripeService: createCheckoutSession failed', [
                    'order_id' => $order->id, 'http' => $response->status(), 'error' => $msg,
                ]);

                return ['success' => false, 'error' => $msg];
            }

            $session = $response->json();
            $sessionId = $session['id'] ?? null;
            $url = $session['url'] ?? null;

            if (! $sessionId || ! $url) {
                return ['success' => false, 'error' => 'Stripe ไม่ส่ง session กลับมา'];
            }

            // เก็บ session id ไว้ใน transaction เพื่อใช้ verify/poll/webhook
            $metadata = $transaction->metadata ?? [];
            $metadata['stripe_session_id'] = $sessionId;
            $transaction->update([
                'gateway' => 'stripe',
                'gateway_transaction_id' => $sessionId,
                'metadata' => $metadata,
            ]);

            Log::info('OrderStripeService: created checkout session', [
                'order_id' => $order->id, 'session_id' => $sessionId, 'amount' => $totalSatang,
            ]);

            return ['success' => true, 'url' => $url, 'session_id' => $sessionId];
        } catch (\Throwable $e) {
            Log::error('OrderStripeService: createCheckoutSession exception', [
                'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * สร้างรายการสินค้า (line items) สำหรับ Stripe
     * ถ้ารวมไม่ตรงยอดออเดอร์ (มีส่วนลด ฯลฯ) → ใช้รายการเดียว = ยอดรวม
     *
     * @return array<int,array{name:string,unit_amount:int,quantity:int}>
     */
    private function buildLineItems(Order $order, int $totalSatang): array
    {
        $lines = [];
        $sum = 0;

        foreach ($order->items as $item) {
            $unit = (int) round(((float) $item->unit_price) * 100);
            $qty = max(1, (int) $item->quantity);
            $lines[] = [
                'name' => mb_substr($item->product_name ?: 'สินค้า', 0, 250),
                'unit_amount' => $unit,
                'quantity' => $qty,
            ];
            $sum += $unit * $qty;
        }

        $shipping = (int) round(((float) $order->shipping_fee) * 100);
        if ($shipping > 0) {
            $lines[] = ['name' => 'ค่าจัดส่ง', 'unit_amount' => $shipping, 'quantity' => 1];
            $sum += $shipping;
        }

        if (empty($lines) || $sum !== $totalSatang) {
            // fallback: รายการเดียว = ยอดรวมจริง (กันคิดเงินผิดเมื่อมีส่วนลด/ปัดเศษ)
            return [[
                'name' => 'คำสั่งซื้อ #'.$order->order_number,
                'unit_amount' => $totalSatang,
                'quantity' => 1,
            ]];
        }

        return $lines;
    }

    /**
     * ดึงข้อมูล session จาก Stripe
     */
    public function retrieveSession(string $sessionId): ?array
    {
        if (! $this->isEnabled() || empty($sessionId)) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->secretKey(), '')
                ->timeout(20)
                ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::warning('OrderStripeService: retrieveSession failed', ['session_id' => $sessionId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ยืนยันการชำระจากข้อมูล session แล้ว complete order (idempotent)
     *
     * เรียกได้จากทั้ง 3 ทาง: success_url return / webhook / poll
     */
    public function completeOrderFromSession(array $session): bool
    {
        $orderId = $session['metadata']['order_id'] ?? null;
        $transactionId = $session['metadata']['transaction_id'] ?? null;
        $paymentStatus = $session['payment_status'] ?? null;
        $amountTotal = (int) ($session['amount_total'] ?? 0);

        if (! $orderId || ! $transactionId) {
            return false; // ไม่ใช่ session ของออเดอร์ (อาจเป็นของดูดวง) → ข้าม
        }

        if ($paymentStatus !== 'paid') {
            return false; // ยังไม่จ่าย
        }

        $transaction = PaymentTransaction::where('id', $transactionId)
            ->where('order_id', $orderId)
            ->where('type', 'order_payment')
            ->first();

        if (! $transaction) {
            Log::warning('OrderStripeService: transaction not found for session', ['session_id' => $session['id'] ?? null]);

            return false;
        }

        if ($transaction->isCompleted()) {
            return true; // ตัดไปแล้ว (idempotent)
        }

        $order = $transaction->order;
        if (! $order) {
            return false;
        }

        // 🔒 กันยอดถูกแก้: ยอดที่จ่ายต้องตรงกับยอดออเดอร์
        $expected = (int) round(((float) $order->total_amount) * 100);
        if ($amountTotal !== $expected) {
            Log::error('OrderStripeService: amount mismatch — ไม่ complete', [
                'order_id' => $order->id, 'expected' => $expected, 'got' => $amountTotal,
            ]);

            return false;
        }

        // เก็บ payment_intent ไว้ใน transaction (ใช้อ้างอิง/refund)
        $metadata = $transaction->metadata ?? [];
        $metadata['stripe_payment_intent_id'] = $session['payment_intent'] ?? null;
        $transaction->update([
            'metadata' => $metadata,
            'gateway_response' => $session,
        ]);

        // complete: mark paid + ตัดสต็อก + status processing (ตรรกะกลางที่มีอยู่แล้ว)
        app(PaymentService::class)->completePayment($transaction);

        // cashback (เหมือน flow ปกติ)
        try {
            if ($order->cashback_amount > 0 && ! $order->cashback_processed) {
                (new CashbackService(new WalletService))->processOrderCashback($order->fresh());
            }
        } catch (\Throwable $e) {
            Log::error('OrderStripeService: cashback failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        Log::info('OrderStripeService: order completed via Stripe', [
            'order_id' => $order->id, 'transaction_id' => $transaction->id, 'session_id' => $session['id'] ?? null,
        ]);

        return true;
    }

    /**
     * ตัวสำรอง: ตรวจ session ที่ค้างจ่าย (กันลูกค้าปิดแท็บหลังจ่าย)
     * รันจาก scheduled command ทุกๆ ไม่กี่นาที
     *
     * @return int จำนวนออเดอร์ที่ complete ในรอบนี้
     */
    public function pollPendingSessions(int $lookbackMinutes = 180): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $pending = PaymentTransaction::where('type', 'order_payment')
            ->where('gateway', 'stripe')
            ->where('status', 'pending')
            ->whereNotNull('gateway_transaction_id')
            ->where('created_at', '>=', now()->subMinutes($lookbackMinutes))
            ->limit(100)
            ->get();

        $completed = 0;
        foreach ($pending as $transaction) {
            $session = $this->retrieveSession($transaction->gateway_transaction_id);
            if ($session && $this->completeOrderFromSession($session)) {
                $completed++;
            }
        }

        if ($completed > 0) {
            Log::info("OrderStripeService: poll completed {$completed} pending order(s)");
        }

        return $completed;
    }

    /**
     * ตรวจสอบ Stripe webhook signature (HMAC-SHA256, timing-safe)
     * ใช้ webhook secret เดียวกับฝั่งดูดวง (บัญชี Stripe เดียวกัน)
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, int $tolerance = 300): bool
    {
        $secret = $this->settings->stripe_webhook_secret ?? null;
        if (empty($secret) || empty($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) !== 2) {
                continue;
            }
            if ($parts[0] === 't') {
                $timestamp = $parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
