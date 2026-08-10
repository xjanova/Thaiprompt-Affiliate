<?php

namespace Tests\Feature;

use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ทดสอบระบบ SMS Payment Checker
 *
 * ครอบคลุม: API endpoints, encryption, signature, matching, rate limiting
 */
class SmsPaymentTest extends TestCase
{
    use RefreshDatabase;

    private SmsCheckerDevice $device;

    private string $apiKey;

    private string $secretKey;

    /**
     * ตั้งค่าก่อนทดสอบแต่ละกรณี
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = SmsCheckerDevice::generateApiKey();
        $this->secretKey = SmsCheckerDevice::generateSecretKey();

        $this->device = SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-TEST0001',
            'device_name' => 'อุปกรณ์ทดสอบ',
            'api_key' => $this->apiKey,
            'secret_key' => $this->secretKey,
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    // =============================================
    // ทดสอบ Middleware: VerifySmsCheckerDevice
    // =============================================

    /**
     * ทดสอบ: ไม่ส่ง API Key → 401
     */
    public function test_middleware_rejects_missing_api_key(): void
    {
        $response = $this->getJson('/api/v1/sms-payment/status');

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'API key is required']);
    }

    /**
     * ทดสอบ: ส่ง API Key ผิด → 401
     */
    public function test_middleware_rejects_invalid_api_key(): void
    {
        $response = $this->getJson('/api/v1/sms-payment/status', [
            'X-Api-Key' => 'invalid-key',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Invalid API key']);
    }

    /**
     * ทดสอบ: อุปกรณ์ถูก block → 403
     */
    public function test_middleware_rejects_blocked_device(): void
    {
        $this->device->update(['status' => 'blocked']);

        $response = $this->getJson('/api/v1/sms-payment/status', [
            'X-Api-Key' => $this->apiKey,
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Device is blocked']);
    }

    /**
     * ทดสอบ: Device ID ไม่ตรง → **auto-sync** (ไม่ใช่ 403 แล้ว)
     *
     * 🔧 (2026-08-10) เทสต์นี้เข้ารหัสสัญญาเก่าไว้ — พฤติกรรมถูกเปลี่ยนโดยตั้งใจตั้งแต่
     *    commit c7debf1ed (2026-02-18) "Fix device_id mismatch: auto-sync instead of
     *    403 rejection" เหตุผลในโค้ด: device_id เป็นค่า global ค่าเดียวในแอพ แต่ server
     *    หลายตัวเก็บแยกกัน พอ user เพิ่ม server ใหม่ device_id จะเปลี่ยน → server เก่า
     *    ถูกล็อกออกทั้งที่ API key ถูกต้อง
     *
     * ⚠️ ผลด้านความปลอดภัยที่ตามมา: device_id **ไม่ใช่ปัจจัยที่สองอีกต่อไป**
     *    ใครถือ API key ที่ถูกต้องก็เขียนทับ device_id ได้ = API key คือของชิ้นเดียว
     *    ที่ป้องกันอยู่ (ตั้งใจแลกความสะดวกกับความเข้ม — เป็นการตัดสินใจของเจ้าของ)
     *    เทสต์นี้จึงล็อกพฤติกรรม auto-sync ไว้ให้ชัด ไม่ใช่แค่ลบเทสต์เก่าทิ้ง
     */
    public function test_middleware_auto_syncs_device_id_mismatch(): void
    {
        $response = $this->getJson('/api/v1/sms-payment/status', [
            'X-Api-Key' => $this->apiKey,
            'X-Device-Id' => 'SMSCHK-WRONGID0',
        ]);

        $response->assertStatus(200);

        // ต้อง sync ค่าใหม่ลง DB จริง ไม่ใช่แค่ปล่อยผ่านเฉย ๆ
        $this->assertSame('SMSCHK-WRONGID0', $this->device->fresh()->device_id);
    }

    // =============================================
    // ทดสอบ: GET /status
    // =============================================

    /**
     * ทดสอบ: ดูสถานะอุปกรณ์สำเร็จ
     */
    public function test_status_returns_device_info(): void
    {
        $response = $this->getJson('/api/v1/sms-payment/status', [
            'X-Api-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'active',
                'pending_count' => 0,
            ]);
    }

    // =============================================
    // ทดสอบ: POST /register-device
    // =============================================

    /**
     * ทดสอบ: ลงทะเบียนอุปกรณ์สำเร็จ
     */
    public function test_register_device_updates_info(): void
    {
        $response = $this->postJson('/api/v1/sms-payment/register-device', [
            'device_id' => 'SMSCHK-TEST0001',
            'device_name' => 'Samsung Galaxy S24',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ], [
            'X-Api-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->device->refresh();
        $this->assertEquals('Samsung Galaxy S24', $this->device->device_name);
        $this->assertEquals('1.0.0', $this->device->app_version);
    }

    // =============================================
    // ทดสอบ: POST /notify
    // =============================================

    /**
     * ทดสอบ: ส่ง notify โดยไม่มี security headers → 400
     */
    public function test_notify_rejects_missing_headers(): void
    {
        $response = $this->postJson('/api/v1/sms-payment/notify', [
            'data' => 'encrypted_data_here',
        ], [
            'X-Api-Key' => $this->apiKey,
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Missing required security headers']);
    }

    /**
     * ทดสอบ: timestamp เก่าเกิน tolerance → 400
     */
    public function test_notify_rejects_expired_timestamp(): void
    {
        $oldTimestamp = strval(intval(round(microtime(true) * 1000)) - 600000); // 10 นาทีก่อน

        $response = $this->postJson('/api/v1/sms-payment/notify', [
            'data' => 'encrypted_data_here',
        ], [
            'X-Api-Key' => $this->apiKey,
            'X-Signature' => 'fake-sig',
            'X-Nonce' => base64_encode(random_bytes(16)),
            'X-Timestamp' => $oldTimestamp,
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Request timestamp expired']);
    }

    /**
     * ทดสอบ: rate limit ทำงาน
     */
    public function test_notify_enforces_rate_limit(): void
    {
        // ตั้งค่า rate limit ต่ำสุดสำหรับทดสอบ
        config(['smschecker.rate_limit_per_minute' => 2]);

        $timestamp = strval(intval(round(microtime(true) * 1000)));
        $nonce = base64_encode(random_bytes(16));
        $headers = [
            'X-Api-Key' => $this->apiKey,
            'X-Signature' => 'fake-sig',
            'X-Nonce' => $nonce,
            'X-Timestamp' => $timestamp,
        ];

        // ส่ง 2 requests (ไม่ถูก block แต่จะ fail ด้วย reason อื่นก็ได้)
        $this->postJson('/api/v1/sms-payment/notify', ['data' => 'test1'], $headers);
        $this->postJson('/api/v1/sms-payment/notify', ['data' => 'test2'], $headers);

        // Request ที่ 3 ต้องถูก rate limit
        $response = $this->postJson('/api/v1/sms-payment/notify', ['data' => 'test3'], $headers);

        $response->assertStatus(429);

        // ล้าง cache
        Cache::forget('smschecker:rate:SMSCHK-TEST0001');
    }

    // =============================================
    // ทดสอบ: Encryption & Signature
    // =============================================

    /**
     * ทดสอบ: encrypt → decrypt roundtrip สำเร็จ
     */
    public function test_encrypt_decrypt_roundtrip(): void
    {
        $service = app(SmsPaymentService::class);

        $originalPayload = [
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => '500.37',
            'account_number' => 'xxx1234',
            'sender_or_receiver' => 'John Doe',
            'reference_number' => 'REF123',
            'sms_timestamp' => intval(round(microtime(true) * 1000)),
            'device_id' => 'SMSCHK-TEST0001',
            'nonce' => base64_encode(random_bytes(16)),
        ];

        // เข้ารหัส (จำลอง Android app)
        // 🔧 (2026-08-10) เดิมจำลองคีย์แบบ str_pad(substr($secret,0,32)) ซึ่งเป็นสูตรเก่า
        //    ตอนนี้ service ใช้ PBKDF2-SHA256 100,000 รอบ (ต้องตรงกับ Android CryptoManager)
        //    → decryptPayload คืน null ตลอด เทสต์เลยไม่เคยพิสูจน์อะไรเลย
        //    เรียก deriveKey ของ service เองผ่าน Reflection แทนการฝังพารามิเตอร์คริปโตซ้ำ
        //    (ฝังซ้ำ = วันหน้าเปลี่ยน salt/รอบ แล้วเทสต์เพี้ยนเงียบ ๆ อีกรอบ)
        $json = json_encode($originalPayload);
        $key = $this->deriveServiceKey($service, $this->secretKey, 'encryption');
        $iv = random_bytes(12);
        $cipherText = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        $combined = $iv.$cipherText.$tag;
        $encrypted = base64_encode($combined);

        // ถอดรหัส (ฝั่ง server)
        $decrypted = $service->decryptPayload($encrypted, $this->secretKey);

        $this->assertNotNull($decrypted);
        $this->assertEquals('KBANK', $decrypted['bank']);
        $this->assertEquals('credit', $decrypted['type']);
        $this->assertEquals('500.37', $decrypted['amount']);
    }

    /**
     * ทดสอบ: HMAC signature verification สำเร็จ
     */
    public function test_signature_verification(): void
    {
        $service = app(SmsPaymentService::class);

        $data = 'test-data-to-sign';
        // 🔧 (2026-08-10) verifySignature ใช้คีย์ HMAC แยกต่างหาก (context 'hmac-signing')
        //    ที่ผ่าน PBKDF2 มาก่อน — เดิมเทสต์เซ็นด้วย secret ดิบ จึงไม่มีทางตรง
        $hmacKey = $this->deriveServiceKey($service, $this->secretKey, 'hmac-signing');
        $signature = base64_encode(hash_hmac('sha256', $data, $hmacKey, true));

        $this->assertTrue($service->verifySignature($data, $signature, $this->secretKey));
    }

    /**
     * 🔑 เรียก SmsPaymentService::deriveKey() (private) ผ่าน Reflection
     *
     * ให้เทสต์ใช้สูตรเดียวกับของจริงเสมอ แทนการก็อปพารามิเตอร์ PBKDF2 มาไว้ในเทสต์
     * (ถ้าวันหน้าเปลี่ยน salt/จำนวนรอบ เทสต์จะยังตรวจของจริง ไม่เพี้ยนเงียบ ๆ)
     */
    private function deriveServiceKey(SmsPaymentService $service, string $secret, string $context): string
    {
        $m = new \ReflectionMethod($service, 'deriveKey');
        $m->setAccessible(true);

        return $m->invoke($service, $secret, $context);
    }

    /**
     * ทดสอบ: HMAC signature ผิด → false
     */
    public function test_signature_verification_fails_with_wrong_key(): void
    {
        $service = app(SmsPaymentService::class);

        $data = 'test-data-to-sign';
        $signature = base64_encode(hash_hmac('sha256', $data, 'wrong-key', true));

        $this->assertFalse($service->verifySignature($data, $signature, $this->secretKey));
    }

    // =============================================
    // ทดสอบ: UniquePaymentAmount
    // =============================================

    /**
     * ทดสอบ: สร้าง unique amount สำเร็จ
     */
    public function test_generate_unique_amount(): void
    {
        $amount = UniquePaymentAmount::generate(500.00, 1, 'order', 30);

        $this->assertNotNull($amount);
        $this->assertEquals(500, intval($amount->base_amount));
        $this->assertGreaterThanOrEqual(500.01, (float) $amount->unique_amount);
        $this->assertLessThanOrEqual(500.99, (float) $amount->unique_amount);
        $this->assertEquals('reserved', $amount->status);
        $this->assertGreaterThan(now(), $amount->expires_at);
    }

    /**
     * ทดสอบ: unique amount ไม่ซ้ำกัน
     */
    public function test_generate_unique_amounts_are_unique(): void
    {
        $amounts = [];
        for ($i = 0; $i < 10; $i++) {
            $amount = UniquePaymentAmount::generate(500.00, $i + 1, 'order', 30);
            $this->assertNotNull($amount);
            $amounts[] = (float) $amount->unique_amount;
        }

        // ทุก amount ต้องไม่ซ้ำกัน
        $this->assertCount(10, array_unique($amounts));
    }

    /**
     * ทดสอบ: base_amount ที่มีทศนิยมจะถูก intval() เป็นจำนวนเต็ม
     */
    public function test_generate_handles_decimal_base_amount(): void
    {
        $amount = UniquePaymentAmount::generate(500.50, 1, 'order', 30);

        $this->assertNotNull($amount);
        // base_amount ถูก intval() เป็น 500
        $this->assertEquals(500, intval($amount->base_amount));
        // unique_amount ต้องอยู่ในช่วง 500.01-500.99
        $this->assertGreaterThanOrEqual(500.01, (float) $amount->unique_amount);
        $this->assertLessThanOrEqual(500.99, (float) $amount->unique_amount);
    }

    /**
     * ทดสอบ: suffix เต็ม → return null
     */
    public function test_generate_returns_null_when_all_suffixes_used(): void
    {
        // ตั้งค่า max_pending ให้เป็น 3 เพื่อทดสอบได้ง่าย
        config(['smschecker.max_pending_per_amount' => 3]);

        // สร้าง 3 amounts
        for ($i = 0; $i < 3; $i++) {
            $result = UniquePaymentAmount::generate(100.00, $i + 1, 'order', 30);
            $this->assertNotNull($result);
        }

        // ตัวที่ 4 ต้อง return null
        $result = UniquePaymentAmount::generate(100.00, 4, 'order', 30);
        $this->assertNull($result);
    }

    // =============================================
    // ทดสอบ: Payment Matching
    // =============================================

    /**
     * ทดสอบ: จับคู่ notification กับ unique amount สำเร็จ
     */
    public function test_attempt_match_by_unique_amount(): void
    {
        // สร้าง unique amount
        $uniqueAmount = UniquePaymentAmount::create([
            'base_amount' => 500,
            'unique_amount' => 500.37,
            'decimal_suffix' => 37,
            'transaction_id' => null,
            'transaction_type' => 'order',
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(30),
        ]);

        // สร้าง credit notification ที่ตรงกัน
        $notification = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 500.37,
            'device_id' => 'SMSCHK-TEST0001',
            'nonce' => 'test-nonce-1',
            'sms_timestamp' => now(),
            'status' => 'pending',
        ]);

        $matched = $notification->attemptMatch(false); // ไม่ auto-confirm (ไม่มี transaction_id)

        $this->assertTrue($matched);
        $notification->refresh();
        $this->assertEquals('matched', $notification->status);

        $uniqueAmount->refresh();
        $this->assertEquals('used', $uniqueAmount->status);
        $this->assertNotNull($uniqueAmount->matched_at);
    }

    /**
     * ทดสอบ: debit notification ไม่จับคู่
     */
    public function test_attempt_match_ignores_debit_notifications(): void
    {
        $notification = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'debit',
            'amount' => 500.37,
            'device_id' => 'SMSCHK-TEST0001',
            'nonce' => 'test-nonce-2',
            'sms_timestamp' => now(),
            'status' => 'pending',
        ]);

        $matched = $notification->attemptMatch();
        $this->assertFalse($matched);
    }

    /**
     * ทดสอบ: notification ที่ไม่ตรงกับ amount ใดๆ → ไม่จับคู่
     */
    public function test_attempt_match_fails_when_no_matching_amount(): void
    {
        $notification = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => 999.99,
            'device_id' => 'SMSCHK-TEST0001',
            'nonce' => 'test-nonce-3',
            'sms_timestamp' => now(),
            'status' => 'pending',
        ]);

        $matched = $notification->attemptMatch();
        $this->assertFalse($matched);
        $this->assertEquals('pending', $notification->status);
    }

    // =============================================
    // ทดสอบ: Cleanup Service
    // =============================================

    /**
     * ทดสอบ: cleanup ล้างข้อมูลหมดอายุ
     */
    public function test_cleanup_expires_old_data(): void
    {
        // สร้าง expired unique amount
        UniquePaymentAmount::create([
            'base_amount' => 100,
            'unique_amount' => 100.50,
            'decimal_suffix' => 50,
            'status' => 'reserved',
            'expires_at' => now()->subHour(),
        ]);

        // สร้าง old pending notification
        $notification = SmsPaymentNotification::create([
            'bank' => 'SCB',
            'type' => 'credit',
            'amount' => 200,
            'device_id' => 'SMSCHK-TEST0001',
            'nonce' => 'old-nonce',
            'sms_timestamp' => now()->subDays(8),
            'status' => 'pending',
            'created_at' => now()->subDays(8),
        ]);
        // ต้อง update created_at manually เพราะ Eloquent จะ override
        SmsPaymentNotification::where('id', $notification->id)
            ->update(['created_at' => now()->subDays(8)]);

        $service = app(SmsPaymentService::class);
        $service->cleanup();

        // unique amount ต้องเป็น expired
        $this->assertEquals('expired', UniquePaymentAmount::first()->status);

        // notification ต้องเป็น expired
        $notification->refresh();
        $this->assertEquals('expired', $notification->status);
    }

    // =============================================
    // ทดสอบ: SmsCheckerDevice Model
    // =============================================

    /**
     * ทดสอบ: สร้าง API Key และ Secret Key
     */
    public function test_generate_keys(): void
    {
        $apiKey = SmsCheckerDevice::generateApiKey();
        $secretKey = SmsCheckerDevice::generateSecretKey();

        // ต้องเป็น 64 chars hex
        $this->assertEquals(64, strlen($apiKey));
        $this->assertEquals(64, strlen($secretKey));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $apiKey);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $secretKey);
    }

    /**
     * ทดสอบ: ค้นหาอุปกรณ์จาก API Key
     */
    public function test_find_by_api_key(): void
    {
        $found = SmsCheckerDevice::findByApiKey($this->apiKey);
        $this->assertNotNull($found);
        $this->assertEquals($this->device->id, $found->id);

        $notFound = SmsCheckerDevice::findByApiKey('nonexistent-key');
        $this->assertNull($notFound);
    }

    /**
     * ทดสอบ: isActive() ทำงานถูกต้อง
     */
    public function test_device_is_active(): void
    {
        $this->assertTrue($this->device->isActive());

        $this->device->update(['status' => 'inactive']);
        $this->assertFalse($this->device->isActive());

        $this->device->update(['status' => 'blocked']);
        $this->assertFalse($this->device->isActive());
    }
}
