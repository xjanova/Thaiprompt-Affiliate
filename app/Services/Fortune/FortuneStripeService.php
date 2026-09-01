<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Fortune Stripe Service (2026-05-09, refactored 2026-05-22)
 *
 * จัดการ Stripe Checkout Session — รองรับลูกค้าทั้งในไทยและต่างประเทศ
 *
 * Flow:
 *   1. createCheckoutSession($reading, $tier) — POST /v1/checkout/sessions
 *      → return checkout URL ส่งให้ลูกค้า + บันทึก stripe_session_id + stripe_customer_region
 *   2. ลูกค้ากดลิงก์ → จ่ายบัตร → Stripe webhook ยิงกลับ
 *   3. handleWebhookEvent($event) — verify signature + match session_id → trigger reading
 *   4. pollPendingSessions() — fallback กรณี webhook ตก (รัน every 5 min)
 *
 * Pricing (2026-05-22 tier-based, integer THB เท่านั้น):
 *   tier='th'      — ในไทย ไม่บวกค่าบริการ
 *     • Deep 39฿  → 39 THB (3900 satang)
 *     • Celtic 99฿ → 99 THB (9900 satang)
 *   tier='foreign' — ต่างประเทศ +15฿
 *     • Deep 39฿  → 39 + 15 = 54 THB (5400 satang)
 *     • Celtic 99฿ → 99 + 15 = 114 THB (11400 satang)
 *
 * ใช้ existing Stripe products (เตรียมไว้ใน Stripe dashboard แล้ว):
 *   - prod_UU1wXx9DI4s2gq — ดูดวงแม่หมอจันทราแบบธรรมดา 39 บาท (Deep)
 *   - prod_UU1zVarkNVzkpp — ดูดวงไพ่10ใบ 99 บาท (Celtic)
 *
 * 🔒 Security:
 *   - Webhook signature verify HMAC-SHA256 (timing-safe compare)
 *   - Idempotent webhook handler (ตรวจ stripe_payment_intent_id ซ้ำ)
 *   - First-write-wins guard (กัน double-trigger ถ้า QR Thai จ่ายทันด้วย)
 *   - billing_address_collection=required → ดึง country audit ลง webhook
 */
class FortuneStripeService
{
    protected ?FortuneTellingSetting $settings = null;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::query()->first();
    }

    /**
     * 🔑 มีคีย์ครบพอจะยิง Stripe API ไหม (ไม่สนสวิตช์)
     *
     * ต้องมี secret key + webhook secret — publishable key ไม่จำเป็น (ใช้ Checkout hosted)
     */
    protected function hasKeys(): bool
    {
        if (! $this->settings) {
            return false;
        }

        return ! empty($this->settings->stripe_secret_key)
            && ! empty($this->settings->stripe_webhook_secret);
    }

    /**
     * 💳 เลน "เมนูเลือกวิธีจ่าย" เปิดอยู่ไหม (สวิตช์ enable_stripe_payment)
     *
     * เปิด = ลูกค้า **ทุกคน** รวมคนไทยจะเจอเมนู 2 ปุ่ม (QR ไทย / บัตร) ก่อนสร้างบิล
     * ตัวนี้เท่านั้นที่ขับ getActivePaymentMode() — อย่าเอาเลนต่างประเทศมาปน
     */
    public function isMenuEnabled(): bool
    {
        return $this->hasKeys() && (bool) ($this->settings->enable_stripe_payment ?? false);
    }

    /**
     * 🌍 (2026-08-23) เลน "บัตรสำหรับลูกค้าต่างประเทศ" เปิดอยู่ไหม
     *
     * เปิด = ยื่นบัตรให้เฉพาะตอนลูกค้าบอกเองว่าอยู่ต่างประเทศ / ไม่มีพร้อมเพย์ / ขอจ่ายบัตร
     * funnel ไทยไม่กระทบ (ไม่มีเมนูเพิ่ม ไม่มีสเตปเพิ่ม)
     *
     * เปิด enable_stripe_payment ก็ถือว่าเลนนี้เปิดด้วย (superset — เมนูเปิดแล้วบัตรย่อมใช้ได้)
     */
    public function isForeignFallbackEnabled(): bool
    {
        if (! $this->hasKeys()) {
            return false;
        }

        return (bool) ($this->settings->enable_stripe_foreign_fallback ?? false)
            || (bool) ($this->settings->enable_stripe_payment ?? false);
    }

    /**
     * เปิดใช้งาน Stripe หรือเปล่า — "เลนไหนก็ได้"
     *
     * ⚠️ (2026-08-23) ความหมายเปลี่ยน: เดิมผูกกับ enable_stripe_payment ตัวเดียว
     *    ตอนนี้ = มีคีย์ + เปิดอย่างน้อย 1 เลน
     *
     * ตัวนี้คือด่านของ **ทุก API call** (createCheckoutSession / retrieveSession /
     * expireSession / refundPayment / pollPendingSessions) — ต้องกว้างที่สุด
     * ไม่งั้นเปิดเลนต่างประเทศแล้วลูกค้าจ่ายได้ แต่ poller กู้บิลตอน webhook ตกไม่ได้
     * = จ่ายเงินแล้วไม่ได้คำทำนาย
     */
    public function isEnabled(): bool
    {
        return $this->isMenuEnabled() || $this->isForeignFallbackEnabled();
    }

    /**
     * 💳 (2026-05-22) คำนวณยอดรวม Stripe — รองรับ tier-based pricing
     *
     * tier='th'       → ในไทย ไม่บวก fee (เท่าราคา QR Thai)
     * tier='foreign'  → ต่างประเทศ +stripe_service_fee (default 15฿)
     * tier=null       → fallback: ใช้ $reading->stripe_customer_region, ถ้าไม่มีใช้ 'foreign' (legacy)
     *
     * ราคา Stripe เป็น integer THB เสมอ (ไม่มีทศนิยม) — แตกต่างจาก QR Thai ที่ +random satang
     *
     * @param  string|null  $tier  'th' | 'foreign' | null
     * @return array{base: int, fee: int, total: int, currency: string, tier: string}
     */
    public function calculateAmounts(FortuneReading $reading, ?string $tier = null): array
    {
        $isCeltic = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;

        // base ราคาแพ็กเกจ — cast เป็น int (ไม่มีทศนิยม)
        $base = $isCeltic
            ? (int) round((float) ($this->settings->celtic_cross_price ?? 99))
            : (int) round((float) ($this->settings->deep_reading_price ?? 39));

        // resolve tier
        $resolvedTier = $tier ?? ($reading->stripe_customer_region ?? 'foreign');
        if (! in_array($resolvedTier, ['th', 'foreign'], true)) {
            $resolvedTier = 'foreign';
        }

        // fee = 0 สำหรับ TH, ค่า settings สำหรับ foreign — cast เป็น int
        $fee = $resolvedTier === 'th'
            ? 0
            : (int) round((float) ($this->settings->stripe_service_fee ?? 15));

        return [
            'base' => $base,
            'fee' => $fee,
            'total' => $base + $fee,
            'currency' => 'thb',
            'tier' => $resolvedTier,
        ];
    }

    /**
     * 💳 (2026-05-22) สร้าง Stripe Checkout Session ใหม่ + อัพเดท reading
     *
     * $tier ระบุ tier ก่อนสร้าง:
     *   - 'th'      → ในไทย, ไม่บวก fee
     *   - 'foreign' → ต่างประเทศ, +15฿
     *   - null      → fallback ตาม reading->stripe_customer_region (legacy = foreign)
     *
     * @param  string|null  $tier  'th' | 'foreign' | null
     * @return array{success: bool, url?: string, session_id?: string, error?: string, amounts?: array}
     */
    public function createCheckoutSession(FortuneReading $reading, ?string $tier = null): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Stripe payment ไม่ได้เปิดใช้งาน',
            ];
        }

        try {
            $amounts = $this->calculateAmounts($reading, $tier);
            $resolvedTier = $amounts['tier']; // 'th' | 'foreign'
            $isCeltic = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;

            // 🐛 (audit-2 fix #6) ถ้ามี session เก่าค้าง — expire ก่อนสร้างใหม่
            //    ป้องกัน user มี 2 sessions พร้อมกัน → จ่ายหลาย session → admin งง
            //    เคสจริง: user spam ปุ่ม "💳 บัตร ตปท." → createCheckoutSession ถูกเรียกซ้ำ
            //    DB เก็บ session_id ตัวล่าสุด → session ตัวแรกค้างใน Stripe (ลูกค้ายังจ่ายได้)
            if (! empty($reading->stripe_session_id) && ! $reading->is_paid) {
                try {
                    $this->expireSession($reading->stripe_session_id);
                    Log::info('FortuneStripeService: expired old session before creating new', [
                        'reading_id' => $reading->id,
                        'old_session_id' => $reading->stripe_session_id,
                    ]);
                } catch (\Throwable $e) {
                    // expire fail = ไม่ block flow (อาจ session expire ตามอายุอยู่แล้ว)
                    Log::debug('FortuneStripeService: expire old session failed (non-blocking)', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 🐛 (self-review fix) Stripe ต้องการ expires_at > now+30min STRICT (clock drift)
            //    ถ้า expiryMinutes = 30 exact → เผลอ 1ms ช้า → Stripe API reject
            //    Min 31 min = ปลอดภัยกับ clock drift / network latency
            $expiryMinutes = max(31, (int) ($this->settings->stripe_session_expiry_minutes ?? 30));
            $expiresAt = time() + ($expiryMinutes * 60);

            // 🌙 (2026-05-23) Round 7 — Deep ปิด → ใช้ generic name ไม่ระบุ "(เชิงลึก)"
            //   ลูกค้าเห็นชื่อนี้บน Stripe Checkout page → ต้องสะอาด
            //   ถ้า Deep ปิดแล้วลูกค้ายังมีบิล Deep ค้าง (legacy) + จ่ายผ่าน Stripe → label generic
            $deepEnabledStripe = (bool) ($this->settings->isDeepReadingEnabled());
            $brandName = $this->settings->getFortuneBrandName();
            $packageName = $isCeltic
                ? "ดูดวงไพ่ 10 ใบ Celtic Cross ({$brandName})"
                : ($deepEnabledStripe
                    ? "ดูดวง{$brandName} (เชิงลึก)"
                    : "ดูดวง{$brandName}");

            // 🛒 Line items
            //   1. ราคาแพ็กเกจ (39 / 99) — integer THB เสมอ
            //   2. ถ้า tier=foreign + fee>0 → เพิ่มรายการค่าบริการบัตรต่างประเทศ (+15)
            //      tier=th → ไม่เพิ่ม line item ที่ 2 เลย (ลูกค้าเห็นแค่ราคาแพ็กเกจ)
            // Stripe ใช้ minor units (THB satang) — 5400 = 54 บาท, 3900 = 39 บาท
            $payload = [
                'mode' => 'payment',
                'payment_method_types[0]' => 'card',
                'line_items[0][price_data][currency]' => $amounts['currency'],
                'line_items[0][price_data][product_data][name]' => $packageName,
                'line_items[0][price_data][unit_amount]' => $amounts['base'] * 100,
                'line_items[0][quantity]' => 1,
                'metadata[fortune_reading_id]' => (string) $reading->id,
                'metadata[reading_type]' => $reading->reading_type,
                'metadata[platform]' => $reading->platform ?? 'facebook',
                'metadata[bill_reference]' => $reading->bill_reference ?? '',
                'metadata[customer_region]' => $resolvedTier,
                'expires_at' => $expiresAt,
                'success_url' => route('fortune.stripe.success', ['reading' => $reading->id]).'?session_id={CHECKOUT_SESSION_ID}',
                // 🐛 (audit-2 fix #3) ส่ง session_id ใน cancel_url ด้วย — controller ใช้ verify ก่อน revert state
                'cancel_url' => route('fortune.stripe.cancel', ['reading' => $reading->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'locale' => 'auto', // Stripe ตรวจ browser locale อัตโนมัติ (รองรับลาว/EN/TH)
                // 💳 (2026-05-22) บังคับเก็บ billing address — ใช้ audit ว่าลูกค้าอยู่ประเทศไหนจริง
                //                  (จะส่งกลับใน webhook customer_details.address.country)
                'billing_address_collection' => 'required',
            ];

            // 💰 ค่าบริการบัตรต่างประเทศ (tier=foreign เท่านั้น)
            if ($amounts['fee'] > 0) {
                $payload['line_items[1][price_data][currency]'] = $amounts['currency'];
                $payload['line_items[1][price_data][product_data][name]'] = 'ค่าบริการบัตรต่างประเทศ';
                $payload['line_items[1][price_data][unit_amount]'] = $amounts['fee'] * 100;
                $payload['line_items[1][quantity]'] = 1;
            }

            // เลือก existing Stripe Product ID ถ้ามี (อ้างอิงไปใน Stripe dashboard)
            $productId = $isCeltic
                ? $this->settings->stripe_product_celtic_id
                : $this->settings->stripe_product_deep_id;
            if ($productId) {
                $payload['metadata[stripe_product_id]'] = $productId;
            }

            $response = Http::withBasicAuth($this->settings->stripe_secret_key, '')
                ->asForm()
                ->timeout(30)
                ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

            if ($response->failed()) {
                $error = $response->json();
                $msg = $error['error']['message'] ?? 'Stripe API error';
                Log::error('FortuneStripeService: createCheckoutSession failed', [
                    'reading_id' => $reading->id,
                    'http_status' => $response->status(),
                    'error' => $msg,
                ]);

                return [
                    'success' => false,
                    'error' => $msg,
                ];
            }

            $session = $response->json();
            $sessionId = $session['id'] ?? null;
            $checkoutUrl = $session['url'] ?? null;

            if (! $sessionId || ! $checkoutUrl) {
                return [
                    'success' => false,
                    'error' => 'Stripe ไม่ส่ง session_id/url กลับมา',
                ];
            }

            // 💾 บันทึกข้อมูล Stripe ลง reading — integer THB เท่านั้น
            $reading->update([
                'payment_method' => FortuneReading::PAYMENT_METHOD_STRIPE,
                'service_fee' => $amounts['fee'], // 0 (th) หรือ 15 (foreign)
                'amount_paid' => $amounts['total'], // 39 / 54 / 99 / 114 THB exact (no random satang)
                'stripe_session_id' => $sessionId,
                'stripe_customer_region' => $resolvedTier, // 'th' หรือ 'foreign'
            ]);

            Log::info('FortuneStripeService: created checkout session', [
                'reading_id' => $reading->id,
                'session_id' => $sessionId,
                'tier' => $resolvedTier,
                'total' => $amounts['total'],
                'fee' => $amounts['fee'],
                'expires_at' => date('c', $expiresAt),
            ]);

            return [
                'success' => true,
                'url' => $checkoutUrl,
                'session_id' => $sessionId,
                'amounts' => $amounts,
                'expires_at' => $expiresAt,
            ];
        } catch (Exception $e) {
            Log::error('FortuneStripeService: createCheckoutSession exception', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบ Stripe webhook signature (HMAC-SHA256)
     *
     * @see https://stripe.com/docs/webhooks/signatures
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, int $tolerance = 300): bool
    {
        $secret = $this->settings->stripe_webhook_secret ?? null;
        if (empty($secret)) {
            Log::warning('FortuneStripeService: webhook_secret ไม่ได้ตั้งค่า — reject');

            return false;
        }

        if (empty($signatureHeader)) {
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

        // 🕒 Timestamp tolerance (default 5 min) — กัน replay
        if (abs(time() - (int) $timestamp) > $tolerance) {
            Log::warning('FortuneStripeService: webhook timestamp เก่าเกินไป', [
                'diff_seconds' => abs(time() - (int) $timestamp),
            ]);

            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        // 🔐 timing-safe compare ทุก signature ที่ส่งมา (Stripe อาจหมุน secret = ส่ง 2 ตัว)
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        Log::warning('FortuneStripeService: webhook signature ไม่ตรง');

        return false;
    }

    /**
     * จัดการ event จาก Stripe webhook
     *
     * รองรับ event types:
     *   - checkout.session.completed — ลูกค้าจ่ายเสร็จ → trigger reading flow
     *   - checkout.session.expired — session หมดอายุ → revert state
     *   - charge.refunded — admin refund → mark reading
     *
     * @return array{handled: bool, reading_id?: int, action?: string, error?: string}
     */
    public function handleWebhookEvent(array $event): array
    {
        $type = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];

        switch ($type) {
            case 'checkout.session.completed':
                return $this->onSessionCompleted($object);

                // 🐛 (audit-2 fix #2) Async payments (bank transfer ที่ Stripe รองรับ)
                //    ส่ง completed (unpaid) → รอจริง → async_payment_succeeded (paid)
                //    ใช้ handler เดียวกับ session.completed — แต่ payment_status='paid' แล้ว
            case 'checkout.session.async_payment_succeeded':
                return $this->onSessionCompleted($object);

            case 'checkout.session.async_payment_failed':
                // ลูกค้าจ่ายไม่สำเร็จ — revert state ให้เลือกใหม่
                return $this->onSessionExpired($object);

            case 'checkout.session.expired':
                return $this->onSessionExpired($object);

            case 'charge.refunded':
                return $this->onChargeRefunded($object);

            default:
                // Event อื่นๆ (เช่น payment_intent.created) ไม่ต้องทำอะไร
                return [
                    'handled' => false,
                    'action' => 'ignored',
                ];
        }
    }

    /**
     * Session completed — ลูกค้าจ่ายเสร็จ
     *
     * - ตรวจ session_id match กับ reading
     * - ถ้า reading อยู่ status แล้ว (จ่ายผ่าน QR ก่อน) → ignore (first-write-wins)
     * - บันทึก payment_intent_id + paid_at
     * - return reading_id ให้ caller (controller) trigger flow
     */
    protected function onSessionCompleted(array $session): array
    {
        $sessionId = $session['id'] ?? null;
        $paymentIntentId = $session['payment_intent'] ?? null;
        $readingIdMeta = $session['metadata']['fortune_reading_id'] ?? null;
        // 🐛 (audit-2 fix #2) ตรวจ payment_status ก่อน — async methods (bank transfer)
        //    ส่ง 'checkout.session.completed' ตอน status=unpaid (รอ async)
        //    แล้วส่ง 'checkout.session.async_payment_succeeded' หลัง paid
        //    ถ้าไม่ check → mark paid ทั้งที่ยังไม่จ่าย → ลูกค้าได้คำทำนายฟรี
        $paymentStatus = $session['payment_status'] ?? '';

        if (! $sessionId || ! $paymentIntentId) {
            return [
                'handled' => false,
                'error' => 'session.id หรือ payment_intent ไม่มี',
            ];
        }

        if ($paymentStatus !== 'paid') {
            // ยังไม่จ่ายจริง — รอ async_payment_succeeded
            Log::info('FortuneStripeService: session.completed but payment_status not paid — awaiting async', [
                'session_id' => $sessionId,
                'payment_status' => $paymentStatus,
            ]);

            return [
                'handled' => true,
                'action' => 'awaiting_async_payment',
            ];
        }

        // หา reading จาก session_id (primary key) หรือ metadata (fallback)
        $reading = FortuneReading::where('stripe_session_id', $sessionId)->first();
        if (! $reading && $readingIdMeta) {
            $reading = FortuneReading::find((int) $readingIdMeta);
        }

        if (! $reading) {
            Log::warning('FortuneStripeService: ไม่พบ reading ตรงกับ session', [
                'session_id' => $sessionId,
                'metadata_reading_id' => $readingIdMeta,
            ]);

            return [
                'handled' => false,
                'error' => 'reading not found',
            ];
        }

        // 🌍 (2026-05-22) Audit detected country — Stripe ส่ง customer_details.address.country
        //    ใช้ log + sanity check (ไม่ override tier เพราะอาจทำให้ refund flow ยุ่ง)
        $detectedCountry = $session['customer_details']['address']['country'] ?? null;
        if ($detectedCountry && $reading->stripe_customer_region) {
            $expectedTier = strtoupper($detectedCountry) === 'TH' ? 'th' : 'foreign';
            if ($expectedTier !== $reading->stripe_customer_region) {
                Log::info('FortuneStripeService: customer-selected tier mismatch with detected country', [
                    'reading_id' => $reading->id,
                    'selected_tier' => $reading->stripe_customer_region,
                    'detected_country' => $detectedCountry,
                    'expected_tier' => $expectedTier,
                ]);
            }
        }

        // 🛡️ (audit-2 fix #1) Atomic compare-and-swap — กัน webhook ซ้ำ trigger reading 2x
        //    เคสจริง: Stripe retry — 2 webhooks มาพร้อมกัน
        //    เดิม: read $is_paid=false → check pass → 2 workers concurrent update + dispatch ซ้ำ
        //    ใหม่: WHERE is_paid=false update — DB-level atomic, first-write-wins
        $rowsAffected = FortuneReading::where('id', $reading->id)
            ->where('is_paid', false)
            ->whereNull('stripe_payment_intent_id')
            ->update([
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_paid_at' => now(),
                'is_paid' => true,
                'paid_at' => now(),
            ]);

        if ($rowsAffected === 0) {
            // อีก worker เพิ่ง mark paid → skip duplicate
            Log::info('FortuneStripeService: lost race for paid update — skip duplicate trigger', [
                'reading_id' => $reading->id,
                'session_id' => $sessionId,
            ]);

            return [
                'handled' => true,
                'reading_id' => $reading->id,
                'action' => 'already_paid',
            ];
        }

        // 🎇 (2026-09-01) CAS ตั้ง is_paid ตรง = confirmPayment() ที่ถูกเรียกทีหลังจะชน early-return
        //   → side-effects หลังจ่าย (ล้างธงปิดปาก + eager persona) ไม่เคยทำงานสายบัตรเลย
        //   ต้องยิงเองตรงนี้หลังชนะ race (idempotent + non-blocking ในตัวเมธอดอยู่แล้ว)
        try {
            $reading->fresh()?->firePaidSideEffects();
        } catch (\Throwable $e) {
            Log::warning('FortuneStripeService: firePaidSideEffects ล้มเหลว (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('FortuneStripeService: Stripe payment confirmed', [
            'reading_id' => $reading->id,
            'session_id' => $sessionId,
            'payment_intent_id' => $paymentIntentId,
            'amount_paid' => $reading->amount_paid,
        ]);

        return [
            'handled' => true,
            'reading_id' => $reading->id,
            'action' => 'paid',
        ];
    }

    /**
     * Session expired — ลูกค้าไม่จ่ายภายใน 30 นาที
     *
     * - ถ้า reading ยัง pending_stripe_payment → revert ไป awaiting_payment_method
     * - ถ้า reading จ่ายไปแล้ว (race condition) → ignore
     */
    protected function onSessionExpired(array $session): array
    {
        $sessionId = $session['id'] ?? null;
        if (! $sessionId) {
            return ['handled' => false, 'error' => 'no session_id'];
        }

        $reading = FortuneReading::where('stripe_session_id', $sessionId)->first();
        if (! $reading) {
            return ['handled' => false, 'error' => 'reading not found'];
        }

        // ถ้าจ่ายไปแล้ว — ignore (race condition: paid + expired ในเวลาใกล้กัน)
        if ($reading->is_paid) {
            return [
                'handled' => true,
                'reading_id' => $reading->id,
                'action' => 'already_paid',
            ];
        }

        // ถ้ายัง pending → คืนสถานะด้วย logic เดียวกับหน้า cancel (แยกเป็นเมธอดกลาง 2026-09-01)
        if ($reading->conversation_status === FortuneReading::STATUS_PENDING_STRIPE_PAYMENT) {
            $this->restoreAfterCheckoutAbandoned($reading, 'session_expired');
        }

        Log::info('FortuneStripeService: Stripe session expired', [
            'reading_id' => $reading->id,
            'session_id' => $sessionId,
        ]);

        return [
            'handled' => true,
            'reading_id' => $reading->id,
            'action' => 'expired',
        ];
    }

    /**
     * 🚪 คืนสถานะบิลหลังลูกค้าทิ้ง Stripe checkout (กด cancel ในหน้า / session หมดอายุ)
     *
     * 🐛 (2026-09-01) เดิม logic นี้อยู่เฉพาะใน onSessionExpired — หน้า cancel
     *   (FortuneStripeWebhookController::cancel) revert เป็น AWAITING_PAYMENT_METHOD ดื้อๆ
     *   ⇒ ลูกค้าเลนต่างประเทศ (เมนูเลือกวิธีจ่ายปิด) กด cancel แล้ว **ค้าง**:
     *   UPA ไทยถูก cancel ไปตั้งแต่เปิดเลนบัตร + สถานะไม่มี handler + webhook session.expired
     *   ที่ตามมา restore ไม่ได้ (guard เช็ค PENDING_STRIPE_PAYMENT ซึ่งไม่จริงแล้ว)
     *   Fix: แยกเป็นเมธอดกลาง ให้ทั้งสองทางเรียกตัวเดียวกัน — idempotent
     *
     * 🌍 บิลออกไปแล้ว (เลนบัตรต่างประเทศ / เมนูปิด) → คืนสถานะรอชำระเดิม + จองยอด QR ไทยใหม่
     *   ห้ามโยนไป awaiting_payment_method: เมนูเลือกวิธีจ่ายเปิดเฉพาะตอน enable_stripe_payment=true
     *   — ถ้าเมนูปิดอยู่ ลูกค้าจะค้างในสถานะที่ไม่มี handler จริง และยอด QR ที่ยังจองอยู่
     *   จะกลายเป็นบิลกำพร้า (SMS เข้าแต่ไม่มีอะไรรับ)
     */
    public function restoreAfterCheckoutAbandoned(FortuneReading $reading, string $trigger = 'session_expired'): void
    {
        // 🛡️ (2026-09-01 จับผี #5) กัน TOCTOU: cancel GET ถือโมเดล stale ขณะ CAS จ่ายชนกัน
        //   refresh ก่อนเช็ค — ไม่งั้น restore เขียน status ทับบิลที่เพิ่งจ่ายในหน้าต่าง ms
        $reading->refresh();

        if ($reading->is_paid) {
            return; // จ่ายแล้ว — ไม่แตะ
        }

        $hasOpenBill = ! empty($reading->bill_reference) && ! $this->isMenuEnabled();

        if ($hasOpenBill) {
            $restoreStatus = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                ? FortuneReading::STATUS_CELTIC_PENDING_PAYMENT
                : FortuneReading::STATUS_PENDING_PAYMENT;

            // 🇹🇭 (2026-08-23) ตอนสลับมาเลนบัตร เราปิดบิลไทยทิ้งไปแล้ว
            //    ลิงก์บัตรหมดอายุ/ถูกยกเลิก = ลูกค้าเหลือ 0 ช่องทาง ทั้งที่บิลยังไม่ถึงกำหนดตาย (3 ชม.)
            //    → จองยอด QR ไทยใหม่ให้ ไม่งั้นบิลกลายเป็นซาก: สถานะบอก "รอชำระ" แต่จ่ายไม่ได้
            $upa = $reading->uniquePaymentAmount;
            $needNewAmount = ! $upa
                || $upa->status !== 'reserved'
                || $upa->expires_at <= now();

            $reissued = null;
            if ($needNewAmount) {
                try {
                    $reissued = $reading->reissueThaiQrAmount();
                } catch (\Throwable $e) {
                    Log::warning('FortuneStripeService: จองยอด QR ไทยใหม่ล้มเหลวตอนคืนสถานะบิล', [
                        'reading_id' => $reading->id,
                        'trigger' => $trigger,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $reading->update(['conversation_status' => $restoreStatus]);

            Log::info('FortuneStripeService: คืนสถานะบิลเดิมหลังทิ้ง checkout (เลนต่างประเทศ)', [
                'reading_id' => $reading->id,
                'bill' => $reading->bill_reference,
                'trigger' => $trigger,
                'restore_status' => $restoreStatus,
                'thai_amount_reissued' => $reissued?->unique_amount,
            ]);
        } else {
            $reading->update([
                'conversation_status' => FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            ]);
        }
    }

    /**
     * Charge refunded — admin refund บิลใน Stripe
     *
     * - แค่ log ไว้ — ไม่ต้องเปลี่ยน reading status (admin จัดการเองในหน้า admin)
     */
    protected function onChargeRefunded(array $charge): array
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;
        if (! $paymentIntentId) {
            return ['handled' => false];
        }

        $reading = FortuneReading::where('stripe_payment_intent_id', $paymentIntentId)->first();
        if (! $reading) {
            return ['handled' => false, 'error' => 'reading not found'];
        }

        Log::info('FortuneStripeService: charge refunded', [
            'reading_id' => $reading->id,
            'payment_intent_id' => $paymentIntentId,
            'refunded' => $charge['amount_refunded'] ?? 0,
        ]);

        return [
            'handled' => true,
            'reading_id' => $reading->id,
            'action' => 'refunded',
        ];
    }

    /**
     * Polling fallback — fetch session ที่ pending แล้ว check status จาก Stripe API
     *
     * ใช้ใน fortune:stripe-poll command (รัน every 5 min)
     * กรณี webhook ตก / network error / firewall block
     *
     * @return array{processed: int, paid: int, expired: int}
     */
    public function pollPendingSessions(int $maxAge = 7200): array
    {
        $stats = ['processed' => 0, 'paid' => 0, 'expired' => 0];

        if (! $this->isEnabled()) {
            return $stats;
        }

        $pending = FortuneReading::query()
            ->where('payment_method', FortuneReading::PAYMENT_METHOD_STRIPE)
            ->where('conversation_status', FortuneReading::STATUS_PENDING_STRIPE_PAYMENT)
            ->whereNotNull('stripe_session_id')
            ->where('updated_at', '>=', now()->subSeconds($maxAge))
            ->limit(50)
            ->get();

        foreach ($pending as $reading) {
            $stats['processed']++;
            $sessionStatus = $this->retrieveSession($reading->stripe_session_id);
            if (! $sessionStatus) {
                continue;
            }

            $status = $sessionStatus['payment_status'] ?? '';
            $sessionState = $sessionStatus['status'] ?? '';

            if ($status === 'paid' && ! $reading->is_paid) {
                // Manually trigger session.completed handler
                $result = $this->onSessionCompleted($sessionStatus);
                if (($result['action'] ?? '') === 'paid') {
                    $stats['paid']++;
                }
            } elseif ($sessionState === 'expired' && ! $reading->is_paid) {
                $this->onSessionExpired($sessionStatus);
                $stats['expired']++;
            }
        }

        return $stats;
    }

    /**
     * ดึงข้อมูล Stripe Checkout Session
     *
     * @return array|null Stripe session payload หรือ null ถ้า fail
     */
    public function retrieveSession(string $sessionId): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->settings->stripe_secret_key, '')
                ->timeout(20)
                ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        } catch (Exception $e) {
            Log::warning('FortuneStripeService: retrieveSession failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Expire Checkout Session ที่ยังไม่จ่าย (admin action)
     */
    public function expireSession(string $sessionId): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::withBasicAuth($this->settings->stripe_secret_key, '')
                ->asForm()
                ->timeout(20)
                ->post("https://api.stripe.com/v1/checkout/sessions/{$sessionId}/expire");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('FortuneStripeService: expireSession failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refund payment (admin action)
     *
     * @param  float|null  $amount  null = full refund, ระบุ = partial
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null, ?string $reason = null): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'error' => 'Stripe ไม่เปิดใช้'];
        }

        try {
            $payload = ['payment_intent' => $paymentIntentId];
            if ($amount !== null) {
                $payload['amount'] = (int) round($amount * 100); // satang
            }
            if ($reason) {
                $payload['metadata[admin_reason]'] = mb_substr($reason, 0, 500);
            }

            $response = Http::withBasicAuth($this->settings->stripe_secret_key, '')
                ->asForm()
                ->timeout(30)
                ->post('https://api.stripe.com/v1/refunds', $payload);

            if ($response->failed()) {
                $error = $response->json();

                return [
                    'success' => false,
                    'error' => $error['error']['message'] ?? 'Stripe refund failed',
                ];
            }

            $refund = $response->json();

            return [
                'success' => true,
                'refund_id' => $refund['id'] ?? null,
                'amount' => ($refund['amount'] ?? 0) / 100,
                'status' => $refund['status'] ?? null,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build URL ลิงก์ไป Stripe Dashboard ของ payment_intent นั้น
     *
     * Live: https://dashboard.stripe.com/{acct_xxx}/payments/{pi_xxx}
     * Test: https://dashboard.stripe.com/{acct_xxx}/test/payments/{pi_xxx}
     */
    public function buildDashboardUrl(string $paymentIntentId): string
    {
        $acct = $this->settings->stripe_account_id ?? '';
        $isTest = $this->isTestMode();

        $base = $acct
            ? "https://dashboard.stripe.com/{$acct}"
            : 'https://dashboard.stripe.com';

        $modePath = $isTest ? '/test' : '';

        return "{$base}{$modePath}/payments/{$paymentIntentId}";
    }

    /**
     * Build URL ไป Checkout Session
     */
    public function buildSessionDashboardUrl(string $sessionId): string
    {
        $acct = $this->settings->stripe_account_id ?? '';
        $isTest = $this->isTestMode();

        $base = $acct
            ? "https://dashboard.stripe.com/{$acct}"
            : 'https://dashboard.stripe.com';

        $modePath = $isTest ? '/test' : '';

        return "{$base}{$modePath}/checkout/sessions/{$sessionId}";
    }

    /**
     * 🐛 (audit-2 fix #5) Auto-detect test/live mode from key prefix
     *
     * เคสที่พังจริง: admin ใส่ sk_live_xxx แต่เผลอเปิด stripe_test_mode toggle=true
     *   → API call สำเร็จ (live key ทำงานได้) → เงินจริงโดน charge
     *   → buildDashboardUrl ใช้ /test/ → admin หาบิลไม่เจอ
     * Fix: ตรวจ key prefix เป็น source of truth (override toggle)
     *   - sk_test_... → test mode
     *   - sk_live_... → live mode
     *   - empty → fallback ไป toggle
     */
    public function isTestMode(): bool
    {
        $key = $this->settings->stripe_secret_key ?? '';
        if (str_starts_with($key, 'sk_test_')) {
            return true;
        }
        if (str_starts_with($key, 'sk_live_')) {
            return false;
        }

        // Empty / unknown — fallback ไป toggle
        return (bool) ($this->settings->stripe_test_mode ?? true);
    }
}
