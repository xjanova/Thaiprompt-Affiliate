<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Payment\OrderStripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Webhook รับ event จาก Stripe สำหรับ "ค่าสินค้า (อีคอมเมิร์ซ)"
 *
 * แยกจาก FortuneStripeWebhookController โดยสมบูรณ์ (คนละ endpoint) แต่ใช้บัญชี Stripe เดียวกัน
 * เป็นตัวสำรองของ success_url return — กันกรณีลูกค้าปิดแท็บหลังจ่ายบัตร
 *
 * URL: POST /webhook/order-stripe  (CSRF-exempt ด้วย webhook/* wildcard)
 * ⚠️ ต้องอ่าน raw body ก่อน parse (signature verify ใช้ payload ดิบ)
 */
class OrderStripeWebhookController extends Controller
{
    public function handle(Request $request, OrderStripeService $stripe)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        if (! $stripe->verifyWebhookSignature($payload, $signature)) {
            Log::warning('OrderStripeWebhook: signature ไม่ผ่าน');

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        $type = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];

        // จัดการเฉพาะ event ที่เกี่ยวกับการจ่ายเสร็จ — event อื่นตอบ 200 เฉยๆ
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            try {
                // completeOrderFromSession เป็น idempotent + จะ "ข้าม" session ที่ไม่ใช่ของ order เอง
                $stripe->completeOrderFromSession($object);
            } catch (\Throwable $e) {
                Log::error('OrderStripeWebhook: complete failed', ['error' => $e->getMessage()]);
                // ตอบ 200 อยู่ดี เพื่อไม่ให้ Stripe retry ถี่ — poll fallback จะตามเก็บ
            }
        }

        return response()->json(['received' => true]);
    }
}
