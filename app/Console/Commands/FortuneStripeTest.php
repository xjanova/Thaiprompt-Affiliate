<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * 🧪 fortune:stripe-test
 *
 * เทส Stripe keys + connectivity แบบไม่ต้องเปิด setting หรือสร้าง bill จริง
 *
 * Tests:
 *   1. Setting fields มีค่าไหม (mask แสดง)
 *   2. GET /v1/balance — verify secret_key ใช้ได้ + account active
 *   3. POST /v1/checkout/sessions — สร้าง test session 54 THB → ลบทันที
 *   4. Webhook URL reachable (HEAD /fortune/stripe/webhook — expect 405 ไม่ใช่ 404)
 *
 * Usage:
 *   php artisan fortune:stripe-test          # Run all tests
 *   php artisan fortune:stripe-test --keep   # Don't expire test session (ดูใน Stripe dashboard)
 */
class FortuneStripeTest extends Command
{
    protected $signature = 'fortune:stripe-test
                            {--keep : ไม่ expire test session (เก็บไว้ดูใน Stripe dashboard)}';

    protected $description = 'เทส Stripe configuration + connectivity โดยไม่กระทบ setting/bills จริง';

    public function handle(): int
    {
        $this->info('🧪 Fortune Stripe Test');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $settings = FortuneTellingSetting::getSettings();

        // ───────────────────────────────────────
        // Test 1: Settings inspection
        // ───────────────────────────────────────
        $this->info('1️⃣  Settings Inspection');
        $this->info('───────────────────────');

        $enable = (bool) ($settings->enable_stripe_payment ?? false);
        $enableSms = (bool) ($settings->enable_sms_payment ?? true);
        $sk = $settings->stripe_secret_key ?? '';
        $pk = $settings->stripe_publishable_key ?? '';
        $whsec = $settings->stripe_webhook_secret ?? '';
        $fee = (int) round((float) ($settings->stripe_service_fee ?? 15));
        $expiry = $settings->stripe_session_expiry_minutes ?? 30;
        $prodDeep = $settings->stripe_product_deep_id ?? '';
        $prodCeltic = $settings->stripe_product_celtic_id ?? '';

        // 💳 (2026-05-22) Detect payment mode
        $mode = 'none';
        if ($enable && $enableSms) {
            $mode = 'both (3 ปุ่ม)';
        } elseif ($enable && ! $enableSms) {
            $mode = 'stripe_only (2 ปุ่ม)';
        } elseif (! $enable && $enableSms) {
            $mode = 'sms_only (default backward compat)';
        } else {
            $mode = 'none (admin misconfig — fallback SMS)';
        }

        // 🌍 (2026-08-23) เลนบัตรต่างประเทศ — แยกจากเมนู
        $foreignLane = (bool) ($settings->enable_stripe_foreign_fallback ?? false);

        $this->table(['Field', 'Value'], [
            ['Payment Mode', $mode],
            ['enable_stripe_payment (เมนูให้ทุกคน)', $enable ? '✅ ON' : '⛔ OFF'],
            ['enable_stripe_foreign_fallback (เลนต่างประเทศ)', $foreignLane ? '✅ ON' : '⛔ OFF'],
            ['enable_sms_payment', $enableSms ? '✅ ON' : '⛔ OFF'],
            ['stripe_secret_key', $this->maskKey($sk, 'sk_')],
            ['stripe_publishable_key', $this->maskKey($pk, 'pk_')],
            ['stripe_webhook_secret', $this->maskKey($whsec, 'whsec_')],
            ['stripe_service_fee (foreign only)', "{$fee} THB"],
            ['stripe_session_expiry_minutes', "{$expiry} min"],
            ['stripe_product_deep_id', $prodDeep ?: '(empty)'],
            ['stripe_product_celtic_id', $prodCeltic ?: '(empty)'],
        ]);

        $blockers = [];
        if (empty($sk)) {
            $blockers[] = 'stripe_secret_key ว่าง';
        } elseif (! preg_match('/^sk_(test|live)_/', $sk)) {
            $blockers[] = 'stripe_secret_key format ผิด (ต้องขึ้นต้น sk_test_ หรือ sk_live_)';
        }
        if (empty($whsec)) {
            $blockers[] = 'stripe_webhook_secret ว่าง';
        } elseif (! str_starts_with($whsec, 'whsec_')) {
            $blockers[] = 'stripe_webhook_secret format ผิด (ต้องขึ้นต้น whsec_)';
        }

        if (! empty($blockers)) {
            $this->newLine();
            $this->error('❌ Setting ยังไม่พร้อม:');
            foreach ($blockers as $b) {
                $this->warn('   • '.$b);
            }
            $this->info('💡 แก้ที่ /admin/fortune/settings → Stripe section');

            return self::FAILURE;
        }

        $this->newLine();

        // ───────────────────────────────────────
        // Test 2: GET /v1/balance — verify secret_key
        // ───────────────────────────────────────
        $this->info('2️⃣  Verify secret_key (GET /v1/balance)');
        $this->info('───────────────────────');

        try {
            $balanceResp = Http::withBasicAuth($sk, '')
                ->timeout(15)
                ->get('https://api.stripe.com/v1/balance');

            if ($balanceResp->successful()) {
                $balance = $balanceResp->json();
                $available = $balance['available'][0] ?? null;
                $pending = $balance['pending'][0] ?? null;
                $livemode = $balance['livemode'] ?? null;

                $this->info('   ✅ secret_key ใช้ได้');
                $this->info('   Mode: '.($livemode ? '🔴 LIVE' : '🟡 TEST'));
                if ($available) {
                    $this->info('   Available: '.($available['amount'] / 100).' '.strtoupper($available['currency']));
                }
                if ($pending) {
                    $this->info('   Pending: '.($pending['amount'] / 100).' '.strtoupper($pending['currency']));
                }
            } else {
                $err = $balanceResp->json('error.message') ?? 'Unknown error';
                $this->error('   ❌ secret_key ใช้ไม่ได้');
                $this->error('   HTTP '.$balanceResp->status().': '.$err);
                $this->newLine();
                $this->info('💡 ตรวจ Stripe Dashboard → Developers → API keys');

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('   ❌ Network/connectivity error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        // ───────────────────────────────────────
        // Test 3: Create test checkout session (Deep 39 + 15 fee = 54 THB, foreign tier)
        // ───────────────────────────────────────
        $totalForeign = 39 + $fee; // integer THB เสมอ
        $this->info("3️⃣  Create test checkout session ({$totalForeign} THB, foreign tier)");
        $this->info('───────────────────────');

        $expiresAt = time() + (max(31, (int) $expiry) * 60);
        $testPayload = [
            'mode' => 'payment',
            'payment_method_types[0]' => 'card',
            'line_items[0][price_data][currency]' => 'thb',
            'line_items[0][price_data][product_data][name]' => 'TEST — Fortune Stripe Test',
            'line_items[0][price_data][unit_amount]' => 3900, // 39 THB (integer)
            'line_items[0][quantity]' => 1,
            'line_items[1][price_data][currency]' => 'thb',
            'line_items[1][price_data][product_data][name]' => 'TEST — Service fee (foreign)',
            'line_items[1][price_data][unit_amount]' => $fee * 100, // integer * 100 satang
            'line_items[1][quantity]' => 1,
            'metadata[test]' => '1',
            'metadata[customer_region]' => 'foreign',
            'metadata[run_at]' => now()->toIso8601String(),
            'expires_at' => $expiresAt,
            'success_url' => url('/fortune/stripe/test-success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/fortune/stripe/test-cancel?session_id={CHECKOUT_SESSION_ID}'),
            'locale' => 'auto',
            'billing_address_collection' => 'required',
        ];

        try {
            $resp = Http::withBasicAuth($sk, '')
                ->asForm()
                ->timeout(30)
                ->post('https://api.stripe.com/v1/checkout/sessions', $testPayload);

            if ($resp->successful()) {
                $sess = $resp->json();
                $sessionId = $sess['id'] ?? null;
                $url = $sess['url'] ?? null;

                $this->info('   ✅ สร้าง session สำเร็จ');
                $this->info('   session_id: '.$sessionId);
                $this->info('   url: '.substr((string) $url, 0, 80).'...');
                $this->info('   amount: '.($sess['amount_total'] / 100).' '.strtoupper($sess['currency']));
                $this->info('   expires_at: '.date('Y-m-d H:i:s', $sess['expires_at']));

                // Expire ทันที (เว้นแต่ --keep)
                if (! $this->option('keep') && $sessionId) {
                    try {
                        $expireResp = Http::withBasicAuth($sk, '')
                            ->asForm()
                            ->timeout(10)
                            ->post("https://api.stripe.com/v1/checkout/sessions/{$sessionId}/expire");

                        if ($expireResp->successful()) {
                            $this->info('   🗑️  Expired test session แล้ว (ไม่เก็บค้างใน Stripe)');
                        } else {
                            $this->warn('   ⚠️  Expire fail: '.$expireResp->status());
                        }
                    } catch (\Throwable $e) {
                        $this->warn('   ⚠️  Expire exception: '.$e->getMessage());
                    }
                } elseif ($this->option('keep')) {
                    $this->warn('   ℹ️  Session ค้างไว้ใน Stripe dashboard (ตามที่ --keep)');
                    $this->warn('   URL: '.$url);
                }
            } else {
                $err = $resp->json('error.message') ?? 'Unknown error';
                $errType = $resp->json('error.type') ?? '?';
                $errCode = $resp->json('error.code') ?? '?';

                $this->error('   ❌ สร้าง session ล้มเหลว');
                $this->error('   HTTP '.$resp->status());
                $this->error('   Type: '.$errType.' / Code: '.$errCode);
                $this->error('   Message: '.$err);
                $this->newLine();

                // 💡 Diagnostic hints ตาม error code
                if (str_contains((string) $err, 'currency')) {
                    $this->info('💡 Hint: Stripe account อาจไม่รองรับ THB → ต้องใช้ Stripe Thailand account');
                } elseif (str_contains((string) $err, 'expires_at')) {
                    $this->info('💡 Hint: expires_at ต้อง > now+30min — ตอนนี้ตั้ง '.$expiry.' min');
                } elseif ($errType === 'invalid_request_error') {
                    $this->info('💡 Hint: ตรวจ payload — line_items format / metadata length');
                }

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('   ❌ Exception: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        // ───────────────────────────────────────
        // Test 4: Webhook URL reachable
        // ───────────────────────────────────────
        $this->info('4️⃣  Webhook URL reachable');
        $this->info('───────────────────────');

        try {
            // 🐛 (2026-05-16) Route name หลายแบบ — group prefix 'webhook.' ทำให้ name จริง = 'webhook.fortune.stripe.webhook'
            //    Try 2 name patterns + fallback hardcoded URL
            $webhookUrl = null;
            $candidateNames = ['webhook.fortune.stripe.webhook', 'fortune.stripe.webhook'];
            foreach ($candidateNames as $name) {
                if (\Illuminate\Support\Facades\Route::has($name)) {
                    $webhookUrl = route($name);
                    $this->info('   Route name: '.$name);
                    break;
                }
            }
            if (! $webhookUrl) {
                $webhookUrl = url('/webhook/fortune-stripe');
                $this->warn('   ⚠️  Route name ทั้ง 2 แบบไม่ register — fallback hardcoded URL');
            }
            $this->info('   URL: '.$webhookUrl);

            $headResp = Http::timeout(10)->withHeaders([
                'Stripe-Signature' => 'test=fake',
            ])->post($webhookUrl, ['test' => true]);

            // Webhook ต้อง reject เพราะ signature ผิด → HTTP 400/401 = route reachable
            // HTTP 404 = route ไม่มี = bug
            // HTTP 200 = signature ไม่ verify (bug ใหญ่)
            if ($headResp->status() === 404) {
                $this->error('   ❌ HTTP 404 — Webhook route ไม่มี!');
                $this->info('   💡 ตรวจ routes/web.php หาเส้น "fortune.stripe.webhook"');
            } elseif ($headResp->status() === 200) {
                $this->error('   ⚠️  HTTP 200 — Webhook ไม่ verify signature?! (security issue)');
            } elseif (in_array($headResp->status(), [400, 401, 403], true)) {
                $this->info('   ✅ HTTP '.$headResp->status().' — Webhook reachable (rejected fake signature ถูกต้อง)');
            } else {
                $this->warn('   ⚠️  HTTP '.$headResp->status().' — Unexpected status, ดู laravel.log');
            }
        } catch (\Throwable $e) {
            $this->warn('   ⚠️  Webhook reachability test failed: '.$e->getMessage());
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('✅ Test complete');

        // 🌍 (2026-08-23) แยกคำเตือน 2 เลน — ปิดทั้งคู่ = Stripe ตายสนิท
        if (! $enable && ! $foreignLane) {
            $this->newLine();
            $this->warn('⚠️  ปิดทั้ง 2 เลน — keys ใช้ได้แต่ไม่มีเส้นทางไหนพาลูกค้าไป Stripe เลย');
            $this->info('💡 ลูกค้าต่างประเทศจ่ายไม่ได้ → เปิด "enable_stripe_foreign_fallback"');
            $this->info('   (เลนสำรอง ไม่กระทบ funnel ไทย — ลูกค้าไทยยังเจอ QR ตรงๆ เหมือนเดิม)');
            $this->info('💡 อยากให้ทุกคนเลือกเองก่อนสร้างบิล → เปิด "enable_stripe_payment"');
            $this->info('   ตั้งค่าที่ /admin/fortune/settings → section "วิธีรับชำระเงิน"');
        } elseif (! $enable && $foreignLane) {
            $this->newLine();
            $this->info('🌍 เลนต่างประเทศเปิดอยู่ — ลูกค้าไทยเจอ QR ตรงๆ เหมือนเดิม (ไม่มีเมนูเพิ่ม)');
            $this->info('   ลิงก์บัตรจะออกเมื่อลูกค้าบอกว่าอยู่ต่างประเทศ / ไม่มีพร้อมเพย์ / พิมพ์ "จ่ายบัตร"');
        }

        return self::SUCCESS;
    }

    /**
     * Mask key — เก็บ prefix + แสดง 4 ตัวท้าย
     */
    protected function maskKey(string $key, string $expectedPrefix): string
    {
        if (empty($key)) {
            return '❌ (empty)';
        }

        $len = strlen($key);
        if (! str_starts_with($key, $expectedPrefix)) {
            return '⚠️ format ผิด (len='.$len.', prefix='.substr($key, 0, 8).'...)';
        }

        if ($len < 12) {
            return '⚠️ สั้นเกินไป (len='.$len.')';
        }

        return $expectedPrefix.'...'.substr($key, -4).' (len='.$len.')';
    }
}
