<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Services\Fortune\FortuneStripeService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Stripe Webhook Controller สำหรับ Fortune (2026-05-09)
 *
 * Endpoint: POST /webhook/fortune-stripe
 *
 * ตรวจสอบ HMAC-SHA256 signature → handle event → trigger fortune flow
 *
 * Events ที่รองรับ:
 *   - checkout.session.completed → จ่ายเสร็จ → call processPaymentConfirmed → reading
 *   - checkout.session.expired → revert state ไป AWAITING_PAYMENT_METHOD
 *   - charge.refunded → log only (admin จัดการในหน้า admin)
 *
 * Stripe webhook quota: ตอบ 200 ภายใน 30 วินาที — ไม่ทำงานหนักใน controller
 *   → trigger reading ผ่าน job (async) ไม่ blocking
 */
class FortuneStripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook
     *
     * Stripe ส่ง raw JSON body + Stripe-Signature header
     * - ห้ามใช้ $request->input() (อ่านแล้วเข้า PHP parse → break signature)
     * - ต้องใช้ $request->getContent() (raw)
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        $service = new FortuneStripeService;

        // 🛡️ Verify signature
        if (! $service->verifyWebhookSignature($payload, $signature)) {
            Log::warning('FortuneStripeWebhook: invalid signature — reject', [
                'has_payload' => ! empty($payload),
                'has_signature' => ! empty($signature),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        // Parse JSON
        $event = json_decode($payload, true);
        if (! is_array($event) || empty($event['type'])) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        $eventId = $event['id'] ?? 'unknown';
        $type = $event['type'];

        Log::info('FortuneStripeWebhook: event received', [
            'event_id' => $eventId,
            'type' => $type,
            'livemode' => $event['livemode'] ?? null,
        ]);

        $result = $service->handleWebhookEvent($event);

        // ถ้า paid (checkout.session.completed สำเร็จ) → trigger fortune flow
        if (($result['action'] ?? '') === 'paid' && ! empty($result['reading_id'])) {
            try {
                $this->triggerFortuneFlow((int) $result['reading_id']);
            } catch (\Throwable $e) {
                // ⚠️ ห้าม return error ที่นี่ — Stripe จะ retry ทำให้ trigger ซ้ำ
                // ทำ best effort แล้ว log error — polling fallback จะ recovery ทีหลัง
                Log::error('FortuneStripeWebhook: triggerFortuneFlow failed (will retry via poll)', [
                    'reading_id' => $result['reading_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Stripe ต้องการ HTTP 200 เพื่อยืนยันว่ารับ event แล้ว — ไม่งั้น retry
        return response()->json([
            'received' => true,
            'event_id' => $eventId,
            'handled' => $result['handled'] ?? false,
            'action' => $result['action'] ?? 'unknown',
        ]);
    }

    /**
     * Trigger fortune flow หลังจ่าย Stripe สำเร็จ
     *
     * - Deep 39฿ → dispatch ProcessDeepFortuneReadingJob
     * - Celtic 99฿ → call processPaymentConfirmed → handleCelticPaymentMatched
     *
     * ใช้ pattern เดียวกับ SmsPaymentService::dispatchFortuneApprovalFlow
     */
    protected function triggerFortuneFlow(int $readingId): void
    {
        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            Log::warning('FortuneStripeWebhook: reading not found for trigger', ['reading_id' => $readingId]);

            return;
        }

        // Idempotency — ถ้า already PAID/COMPLETED → skip
        if (in_array($reading->conversation_status, [
            FortuneReading::STATUS_PAID,
            FortuneReading::STATUS_COMPLETED,
            FortuneReading::STATUS_CELTIC_PICKING,
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            FortuneReading::STATUS_CELTIC_GENERATING,
            FortuneReading::STATUS_CELTIC_QA_PROMPT,
        ], true)) {
            Log::info('FortuneStripeWebhook: reading already processing/done — skip trigger', [
                'reading_id' => $readingId,
                'status' => $reading->conversation_status,
            ]);

            return;
        }

        // Mark PAID
        $reading->update([
            'conversation_status' => FortuneReading::STATUS_PAID,
        ]);

        // Branch ตาม reading_type
        if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
            $this->triggerCelticFlow($reading);
        } else {
            $this->triggerDeepFlow($reading);
        }
    }

    /**
     * Deep 39฿ → dispatch async job ที่ทำนายแล้วส่งกลับลูกค้า
     */
    protected function triggerDeepFlow(FortuneReading $reading): void
    {
        // 🐛 (self-review fix) Job constructor signature = (int $readingId, ?int $notificationId, string $platform, string $userId)
        //    เดิม dispatch($reading->id, null) — missing 2 args → ArgumentCountError ตอนรัน
        //    ใหม่: ใช้ canonical dispatchSmart() เหมือน SmsPaymentService:868 ส่ง args ครบ
        $platform = $reading->platform ?? 'facebook';
        $userId = $reading->facebook_user_id ?? $reading->platform_user_id ?? '';

        try {
            \App\Jobs\ProcessDeepFortuneReadingJob::dispatchSmart(
                $reading->id,
                /* notificationId: */ null,
                $platform,
                $userId
            );

            Log::info('FortuneStripeWebhook: dispatched ProcessDeepFortuneReadingJob', [
                'reading_id' => $reading->id,
                'platform' => $platform,
            ]);
        } catch (\Throwable $e) {
            Log::error('FortuneStripeWebhook: dispatch deep job failed — fallback sync', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback sync (ใช้ใน sync queue)
            try {
                $convService = app(FortuneConversationService::class);
                $channelManager = app(FortuneChannelManager::class);
                $convService->processPaymentConfirmed($reading, null, $channelManager);
            } catch (\Throwable $syncErr) {
                Log::critical('FortuneStripeWebhook: sync fallback ก็ fail', [
                    'reading_id' => $reading->id,
                    'error' => $syncErr->getMessage(),
                ]);
            }
        }
    }

    /**
     * Celtic 99฿ → call SmsPaymentService::handleCelticPaymentMatched
     *
     * ใช้ method เดียวกับ SMS path เพื่อ flow consistency
     */
    protected function triggerCelticFlow(FortuneReading $reading): void
    {
        try {
            $smsService = app(\App\Services\SmsPaymentService::class);
            // handleCelticPaymentMatched signature: (reading, ?notification, platform, userId, amount)
            // notification=null คือ admin force approve / Stripe path (ไม่ผ่าน SMS)
            $platform = $reading->platform ?? 'facebook';
            $userId = $reading->facebook_user_id ?? $reading->platform_user_id ?? '';
            $amount = (float) $reading->amount_paid;

            $smsService->handleCelticPaymentMatched($reading, null, $platform, $userId, $amount);

            Log::info('FortuneStripeWebhook: Celtic flow triggered', [
                'reading_id' => $reading->id,
                'platform' => $platform,
            ]);
        } catch (\Throwable $e) {
            Log::error('FortuneStripeWebhook: handleCelticPaymentMatched failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🎉 Success page — ลูกค้ากดจ่ายเสร็จ Stripe redirect กลับมาที่นี่
     *
     * URL: GET /fortune/stripe/success/{reading}?session_id=cs_xxx
     */
    public function success(Request $request, int $reading): \Illuminate\Contracts\View\View
    {
        $sessionId = $request->query('session_id');
        $readingModel = FortuneReading::find($reading);

        return view('fortune.stripe.success', [
            'reading' => $readingModel,
            'session_id' => $sessionId,
            'platform' => $readingModel?->platform ?? 'facebook',
        ]);
    }

    /**
     * ❌ Cancel page — ลูกค้ากดยกเลิก / ปิด tab
     *
     * URL: GET /fortune/stripe/cancel/{reading}?session_id=cs_xxx
     *
     * 🐛 (audit-2 fix #3) Privilege escalation guard
     *   เดิม: route ไม่มี auth → user คนอื่นเปิด /fortune/stripe/cancel/9999
     *         → revert state ของ reading คนอื่น → ทำลาย flow
     *   ใหม่: ตรวจ ?session_id query param ตรงกับ $reading->stripe_session_id
     *         ถ้าไม่ตรง → แสดงหน้า cancel เฉยๆ ไม่ revert state
     *
     *   Stripe ส่ง {CHECKOUT_SESSION_ID} ใน cancel_url อัตโนมัติ — เป็น authentic source
     */
    public function cancel(Request $request, int $reading): \Illuminate\Contracts\View\View
    {
        $readingModel = FortuneReading::find($reading);
        $providedSession = (string) $request->query('session_id', '');

        // 🛡️ Revert state เฉพาะถ้า session_id ตรงกัน (proof of authenticity)
        $sessionMatches = $readingModel
            && $providedSession !== ''
            && $readingModel->stripe_session_id !== null
            && hash_equals((string) $readingModel->stripe_session_id, $providedSession);

        if ($sessionMatches
            && ! $readingModel->is_paid
            && $readingModel->conversation_status === FortuneReading::STATUS_PENDING_STRIPE_PAYMENT
        ) {
            // 🐛 (2026-09-01) เดิม revert เป็น AWAITING_PAYMENT_METHOD ดื้อๆ — ลูกค้าเลนต่างประเทศ
            //   (เมนูเลือกวิธีจ่ายปิด) จะค้างในสถานะไม่มี handler + QR ไทยถูกยกเลิกไปแล้ว
            //   ใช้ logic กลางตัวเดียวกับ session.expired: คืนสถานะบิลเดิม + จองยอด QR ไทยใหม่ให้
            (new FortuneStripeService)->restoreAfterCheckoutAbandoned($readingModel, 'cancel_url');
        } elseif ($readingModel && ! $sessionMatches) {
            Log::warning('FortuneStripe cancel: session_id mismatch — skip revert', [
                'reading_id' => $reading,
                'has_provided' => $providedSession !== '',
            ]);
        }

        return view('fortune.stripe.cancel', [
            'reading' => $readingModel,
            'platform' => $readingModel?->platform ?? 'facebook',
        ]);
    }
}
