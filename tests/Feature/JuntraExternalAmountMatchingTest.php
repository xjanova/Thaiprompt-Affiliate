<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\SmsPaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🌙 CONTRACT §C3 — ทุกเส้นจับคู่ SMS ของ Thaiprompt ต้อง "ไม่แตะ" ยอดที่จองให้ จันทรา.online
 *
 * มือถือ SMS เครื่องเดียวรับเงินของทั้งสองเว็บ → SMS ยอดของจันทราก็วิ่งมาที่ /notify ของเราด้วย
 *   - ห้ามตัดบิลของเรา (เงินก้อนเดียวจะถูกนับสองเว็บ)
 *   - ห้ามเตือนว่าเป็นเงินกำพร้า (มีเจ้าของแล้ว)
 *   - ตอบแอพว่า matched:false + external_site ให้แอพรู้ว่าเงินเป็นของเว็บไหน
 */
class JuntraExternalAmountMatchingTest extends TestCase
{
    use BuildsJuntraServerSchema;

    private SmsCheckerDevice $device;

    private string $apiKey;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        Cache::flush();

        $this->apiKey = SmsCheckerDevice::generateApiKey();
        $this->secretKey = SmsCheckerDevice::generateSecretKey();
        $this->device = SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-JUNTRA1',
            'device_name' => 'มือถือรับเงินแม่หมอ',
            'api_key' => $this->apiKey,
            'secret_key' => $this->secretKey,
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────────────

    /** unguarded — เทสต์ต้องกำหนด created_at/updated_at เองได้ */
    private function upa(array $attrs): UniquePaymentAmount
    {
        return UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create($attrs));
    }

    private function juntraReservation(array $attrs = []): UniquePaymentAmount
    {
        return $this->upa(array_merge([
            'base_amount' => 100,
            'unique_amount' => 100.37,
            'decimal_suffix' => 37,
            'transaction_id' => null,
            'transaction_type' => UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP,
            'external_ref' => 'TUP-AB12CD34',
            'external_id' => 555,
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(60),
        ], $attrs));
    }

    private function payload(float $amount, ?Carbon $at = null): array
    {
        return [
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'account_number' => 'xxx5514',
            'sender_or_receiver' => 'นาย ลูกค้า จันทรา',
            'reference_number' => '',
            'sms_timestamp' => ($at ?? now())->getTimestampMs(),
            'device_id' => $this->device->device_id,
            'nonce' => Str::random(24),
        ];
    }

    private function processSms(float $amount, ?Carbon $at = null): array
    {
        return app(SmsPaymentService::class)->processNotification($this->payload($amount, $at), $this->device, '127.0.0.1');
    }

    /** เข้ารหัส + เซ็นแบบเดียวกับแอพ SmsChecker (AES-256-GCM + HMAC, คีย์จาก PBKDF2) */
    private function signedNotify(array $payload)
    {
        $key = hash_pbkdf2('sha256', $this->secretKey, 'thaiprompt-smschecker-v1:encryption', 100000, 32, true);
        $iv = random_bytes(12);
        $cipher = openssl_encrypt(json_encode($payload), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        $data = base64_encode($iv.$cipher.$tag);

        $nonce = Str::random(24);
        $ts = (string) (int) round(microtime(true) * 1000);
        $hmacKey = hash_pbkdf2('sha256', $this->secretKey, 'thaiprompt-smschecker-v1:hmac-signing', 100000, 32, true);
        $signature = base64_encode(hash_hmac('sha256', $data.$nonce.$ts, $hmacKey, true));

        return $this->postJson('/api/v1/sms-payment/notify', ['data' => $data], [
            'X-Api-Key' => $this->apiKey,
            'X-Signature' => $signature,
            'X-Nonce' => $nonce,
            'X-Timestamp' => $ts,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // findMatch / attemptMatch
    // ─────────────────────────────────────────────────────────────────────

    public function test_find_match_never_returns_a_juntraweb_reservation(): void
    {
        $this->juntraReservation();
        $ours = $this->upa([
            'base_amount' => 100, 'unique_amount' => 100.38, 'decimal_suffix' => 38,
            'transaction_type' => 'order', 'status' => 'reserved', 'expires_at' => now()->addMinutes(30),
        ]);

        $this->assertNull(UniquePaymentAmount::findMatch(100.37));
        $this->assertNull(UniquePaymentAmount::findMatch(100.37, 'fortune_reading'));
        $this->assertNull(UniquePaymentAmount::findMatch(100.37, ['order', 'fortune_reading']));
        $this->assertSame($ours->id, UniquePaymentAmount::findMatch(100.38)?->id);
    }

    public function test_attempt_match_ignores_juntraweb_amounts_but_still_matches_ours(): void
    {
        $juntra = $this->juntraReservation(['created_at' => now()->subMinutes(10)]);
        $ours = $this->upa([
            'base_amount' => 100, 'unique_amount' => 100.38, 'decimal_suffix' => 38,
            'transaction_type' => 'order', 'status' => 'reserved', 'expires_at' => now()->addMinutes(30),
            'created_at' => now()->subMinutes(10),
        ]);

        $juntraSms = SmsPaymentNotification::create($this->notificationRow(100.37));
        $this->assertFalse($juntraSms->attemptMatch(false));
        $this->assertSame('reserved', $juntra->fresh()->status);
        $this->assertNull($juntraSms->fresh()->matched_transaction_id);

        $ourSms = SmsPaymentNotification::create($this->notificationRow(100.38));
        $this->assertTrue($ourSms->attemptMatch(false));
        $this->assertSame('used', $ours->fresh()->status);
    }

    private function notificationRow(float $amount, string $status = 'pending'): array
    {
        return [
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'sender_or_receiver' => 'นาย ลูกค้า',
            'sms_timestamp' => now(),
            'device_id' => $this->device->device_id,
            'nonce' => Str::random(24),
            'status' => $status,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // /notify (SmsPaymentService::processNotification)
    // ─────────────────────────────────────────────────────────────────────

    public function test_sms_for_an_active_juntraweb_reservation_is_recorded_as_external(): void
    {
        $juntra = $this->juntraReservation(['created_at' => now()->subMinutes(5)]);

        $result = $this->processSms(100.37);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['data']['matched']);
        $this->assertSame('จันทรา.online', $result['data']['external_site']);
        $this->assertSame('external', $result['data']['status']);
        $this->assertNull($result['data']['matched_transaction_id']);
        $this->assertNull($result['matched_model']);

        $sms = SmsPaymentNotification::findOrFail($result['data']['notification_id']);
        $this->assertSame('external', $sms->status);
        $this->assertNull($sms->matched_transaction_id);

        // ยอดของจันทราไม่ถูกเราแตะ (จันทราเป็นคนปิดเองผ่าน amounts/release)
        $fresh = $juntra->fresh();
        $this->assertSame('reserved', $fresh->status);
        $this->assertNull($fresh->matched_at);
    }

    public function test_bot_treats_a_slip_for_web_wallet_money_as_already_used(): void
    {
        // A customer tops up the web wallet by QR — the bank SMS is the web's (external here, no
        // transRef anywhere) — then sends the same slip to แม่หมอ on LINE for a reading.
        $this->juntraReservation(['created_at' => now()->subMinutes(5)]);
        $result = $this->processSms(100.37);
        $this->assertSame('external', $result['data']['status']);

        $sms = SmsPaymentNotification::findOrFail($result['data']['notification_id']);
        $verify = [
            'amount' => 100.37,
            'trans_timestamp' => Carbon::parse($sms->sms_timestamp, 'Asia/Bangkok')->utc()->toIso8601String(),
        ];
        $slipok = app(\App\Services\Fortune\SlipOkService::class);

        // The bot's paths count web money as spent…
        $this->assertTrue($slipok->slipMatchesUsedSmsPayment($verify, 999, includeExternal: true));
        $this->assertTrue($slipok->slipMatchesUsedSmsPayment($verify, null, includeExternal: true));
        // …the web's own verify path does not (the web checks its own SMS, and this may be the very
        // top-up the slip belongs to), and the old default behaviour is unchanged.
        $this->assertFalse($slipok->slipMatchesUsedSmsPayment($verify));
        $this->assertFalse($slipok->slipMatchesUsedSmsPayment($verify, 999));
    }

    public function test_recently_expired_or_released_juntraweb_amounts_are_still_external(): void
    {
        // หมดอายุไป 2 ชม. — ลูกค้าจันทราโอนช้า
        $this->juntraReservation([
            'status' => 'expired',
            'created_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $this->assertSame('จันทรา.online', $this->processSms(100.37)['data']['external_site'] ?? null);

        // จันทราตัดยอดไปแล้ว (used) — SMS ใบนี้มาถึงเราทีหลัง ต้องไม่กลายเป็นเงินกำพร้า
        $this->juntraReservation([
            'unique_amount' => 100.41, 'decimal_suffix' => 41, 'external_ref' => 'TUP-USED0001',
            'status' => 'used', 'matched_at' => now()->subMinutes(1),
            'created_at' => now()->subMinutes(20),
        ]);
        $this->assertSame('จันทรา.online', $this->processSms(100.41)['data']['external_site'] ?? null);
    }

    public function test_old_juntraweb_amounts_and_sms_older_than_the_reservation_are_not_external(): void
    {
        // หมดอายุเกิน 24 ชม. แล้ว → ยอดนี้ไม่ใช่ของจันทราอีกต่อไป
        $this->juntraReservation([
            'status' => 'expired',
            'created_at' => now()->subHours(27),
            'expires_at' => now()->subHours(26),
            'updated_at' => now()->subHours(26),
        ]);
        $result = $this->processSms(100.37);
        $this->assertArrayNotHasKey('external_site', $result['data']);
        $this->assertSame('pending', $result['data']['status']);

        // SMS เก่ากว่าตอนจองยอด (เช่น SMS เดิมถูกส่งซ้ำ) → ไม่ใช่เงินของรายการนี้
        $this->juntraReservation([
            'unique_amount' => 100.52, 'decimal_suffix' => 52, 'external_ref' => 'TUP-NEW00001',
            'created_at' => now(),
        ]);
        $result = $this->processSms(100.52, now()->subHours(2));
        $this->assertArrayNotHasKey('external_site', $result['data']);
    }

    public function test_falls_back_to_rejected_when_the_status_enum_is_not_migrated_yet(): void
    {
        // จำลอง prod ที่ยังไม่ได้รัน migration enum — สร้างตารางใหม่ด้วย enum เดิม (sqlite = CHECK ไม่มี 'external')
        \Illuminate\Support\Facades\Schema::drop('sms_payment_notifications');
        (require database_path('migrations/2024_01_01_000001_create_sms_payment_tables.php'))->up();
        $this->juntraReservation(['created_at' => now()->subMinutes(5)]);

        $result = $this->processSms(100.37);

        $this->assertSame('จันทรา.online', $result['data']['external_site']);
        $this->assertFalse($result['data']['matched']);
        $this->assertSame('rejected', SmsPaymentNotification::findOrFail($result['data']['notification_id'])->status,
            'ห้ามปล่อยค้าง pending ให้เส้นอื่นหยิบไปตัดบิลเรา');
    }

    public function test_notify_endpoint_returns_external_site(): void
    {
        $this->juntraReservation(['created_at' => now()->subMinutes(5)]);

        $this->signedNotify($this->payload(100.37))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.matched', false)
            ->assertJsonPath('data.external_site', 'จันทรา.online')
            ->assertJsonPath('data.status', 'external');
    }

    // ─────────────────────────────────────────────────────────────────────
    // /orders/match + orphan endpoints
    // ─────────────────────────────────────────────────────────────────────

    public function test_orders_match_answers_external_site_for_a_juntraweb_amount(): void
    {
        $juntra = $this->juntraReservation();

        $this->getJson('/api/v1/sms-payment/orders/match?amount=100.37', ['X-Api-Key' => $this->apiKey])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.matched', false)
            ->assertJsonPath('data.order', null)
            ->assertJsonPath('data.external_site', 'จันทรา.online');

        $this->assertSame('reserved', $juntra->fresh()->status);
    }

    public function test_orphan_candidate_search_offers_no_bills_for_a_juntraweb_amount(): void
    {
        $this->juntraReservation(['created_at' => now()->subMinutes(5)]);

        $this->postJson('/api/v1/sms-payment/orphans/find-bill-candidates', [
            'amount' => 100.37,
            'sender_name' => 'นาย ลูกค้า จันทรา',
            'sms_timestamp' => now()->toIso8601String(),
        ], ['X-Api-Key' => $this->apiKey])
            ->assertOk()
            ->assertJsonPath('data.candidates', [])
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.external_site', 'จันทรา.online');
    }

    public function test_orphan_confirm_refuses_to_bind_an_external_sms_to_our_bill(): void
    {
        DB::table('fortune_readings')->insert([
            'bill_reference' => 'FTU-260915-A0001',
            'reading_type' => 'celtic_cross',
            'conversation_status' => 'celtic_pending_payment',
            'is_paid' => false,
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);
        $sms = SmsPaymentNotification::create($this->notificationRow(100.37, 'external'));

        $this->postJson('/api/v1/sms-payment/orphans/confirm-match', [
            'bill_reference' => 'FTU-260915-A0001',
            'sms_notification_id' => $sms->id,
            'auto_smart' => true,
        ], ['X-Api-Key' => $this->apiKey])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'external_site');

        $this->assertFalse((bool) DB::table('fortune_readings')->where('bill_reference', 'FTU-260915-A0001')->value('is_paid'));
        $this->assertNull($sms->fresh()->matched_transaction_id);
    }
}
