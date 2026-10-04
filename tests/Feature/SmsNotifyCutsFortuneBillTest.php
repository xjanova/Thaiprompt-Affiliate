<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🧾 (2026-10-04) POST /notify ต้องตัดบิลดูดวงได้เองครบเส้น — ไม่พึ่ง GET /orders/match
 *
 * ทุกวันนี้แอพ SmsChecker เรียก /orders/match ก่อน /notify ราว 2 วิ บิลทุกใบเลยถูกตัดที่ /orders/match
 * (ล็อก prod 16 วัน /notify ไม่เคยได้ตัดเองสักใบ) — เส้นตัดบิลของ /notify จึงไม่มีอะไรพิสูจน์ว่ายังใช้ได้
 * ก่อนจะเลิกให้ GET ที่ส่งมาแค่ยอดเงินตัดบิล ต้องพิสูจน์ก่อนว่า /notify (HMAC + AES-GCM + nonce) ทำแทนได้จริง
 *
 * ยิง /notify ผ่าน HTTP จริงทั้งเส้น (middleware → ลายเซ็น → ถอดรหัส → จับคู่บิล → ส่งข้อความลูกค้า)
 * ไม่มีอะไรออกนอกเครื่อง: Http::fake() ดัก Graph API · Queue::fake() ดักงานพื้นหลัง
 *
 * @group fortune-billing
 */
class SmsNotifyCutsFortuneBillTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '35272642915716630';

    private string $apiKey;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->deep_reading_price = 39;
        $settings->facebook_page_token = 'TEST-PAGE-TOKEN';
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => self::PSID, 'message_id' => 'm_test'], 200),
            '*' => Http::response([], 200),
        ]);
        Queue::fake();

        $this->apiKey = SmsCheckerDevice::generateApiKey();
        $this->secretKey = SmsCheckerDevice::generateSecretKey();
        SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-NOTIFY01',
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

    /** บิลรอโอนพร้อมยอดทศนิยมที่จองไว้ — แบบที่ FortuneConversationService ออกให้ลูกค้าจริง */
    private function pendingBill(string $type, string $status, float $amount): FortuneReading
    {
        $reading = FortuneReading::create([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'น้ำผึ้ง',
            'questions' => [],
            'reading_type' => $type,
            'conversation_status' => $status,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => false,
            'amount_paid' => $amount,
        ]);

        $upa = UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create([
            'base_amount' => floor($amount),
            'unique_amount' => $amount,
            'decimal_suffix' => (int) round(($amount - floor($amount)) * 100),
            'transaction_id' => $reading->id,
            'transaction_type' => 'fortune_reading',
            'status' => 'reserved',
            'expires_at' => now()->addHours(3),
            'created_at' => now()->subMinutes(2),
        ]));

        $reading->forceFill(['unique_payment_amount_id' => $upa->id])->save();
        if ($type === FortuneReading::READING_TYPE_DEEP) {
            $reading->setConversationState('pay_first_mode', true);
        }

        return $reading->fresh();
    }

    /**
     * เข้ารหัส + เซ็นแบบเดียวกับแอพ SmsChecker (AES-256-GCM + HMAC, คีย์จาก PBKDF2)
     *
     * @param  int|null  $smsTimestampMs  เวลาในตัว SMS (null = ตอนนี้)
     */
    private function signedNotify(float $amount, ?int $smsTimestampMs = null)
    {
        $payload = [
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'account_number' => '',
            'sender_or_receiver' => '',
            'reference_number' => '',
            'sms_timestamp' => $smsTimestampMs ?? now()->getTimestampMs(),
            'device_id' => 'SMSCHK-NOTIFY01',
            'nonce' => Str::random(24),
        ];

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

    /** ข้อความ Messenger ที่ส่งถึงลูกค้าคนนี้ (รวมทุก chunk เป็นข้อความเดียว) */
    private function messagesSentToCustomer(): string
    {
        return Http::recorded()
            ->filter(fn (array $pair) => str_contains($pair[0]->url(), '/me/messages')
                && data_get($pair[0]->data(), 'recipient.id') === self::PSID)
            ->map(fn (array $pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    public function test_notify_cuts_a_pay_first_deep_bill_and_asks_for_the_birthdate(): void
    {
        $bill = $this->pendingBill(FortuneReading::READING_TYPE_DEEP, FortuneReading::STATUS_PENDING_PAYMENT, 39.47);

        $response = $this->signedNotify(39.47)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.fortune_reading', true)
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.order.order_details_json.order_number', $bill->bill_reference)
            // แอพต้องเห็นว่าตัดบิลแล้ว — ไม่ใช่ "รอตรวจ" (ไม่งั้นแอดมินกดอนุมัติซ้ำ)
            ->assertJsonPath('data.order.approval_status', 'auto_approved');

        $notificationId = (int) $response->json('data.notification_id');
        $fresh = $bill->fresh();
        $this->assertTrue((bool) $fresh->is_paid);
        $this->assertNotNull($fresh->paid_at);
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $fresh->conversation_status);
        $this->assertSame($notificationId, (int) $fresh->sms_notification_id, 'หลักฐานต้องเป็น SMS ใบที่เพิ่งเข้า');
        $this->assertTrue((bool) $fresh->getConversationState('sms_match_processed', false));
        $this->assertNotNull($fresh->getConversationState('birthdate_resent_at'), 'ส่งข้อความขอวันเกิดสำเร็จต้องถูกบันทึก');

        $this->assertSame('used', UniquePaymentAmount::find($fresh->unique_payment_amount_id)->status);
        $sms = SmsPaymentNotification::findOrFail($notificationId);
        $this->assertSame('matched', $sms->status);
        $this->assertSame($bill->id, (int) $sms->matched_transaction_id);

        $sent = $this->messagesSentToCustomer();
        $this->assertStringContainsString('ระบบตัดบิลเรียบร้อย', $sent);
        $this->assertStringContainsString('วันเดือนปีเกิด', $sent);
    }

    public function test_notify_cuts_a_celtic_bill_and_starts_the_card_picking(): void
    {
        $bill = $this->pendingBill(FortuneReading::READING_TYPE_CELTIC_CROSS, FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, 99.53);

        $response = $this->signedNotify(99.53)
            ->assertOk()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.fortune_reading', true)
            ->assertJsonPath('data.order.order_details_json.order_number', $bill->bill_reference)
            ->assertJsonPath('data.order.approval_status', 'auto_approved');

        $fresh = $bill->fresh();
        $this->assertTrue((bool) $fresh->is_paid);
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $fresh->conversation_status);
        $this->assertSame((int) $response->json('data.notification_id'), (int) $fresh->sms_notification_id);
        $this->assertSame('matched', SmsPaymentNotification::findOrFail($response->json('data.notification_id'))->status);

        $this->assertNotSame('', $this->messagesSentToCustomer(), 'ลูกค้า Celtic ต้องได้ข้อความเริ่มเปิดไพ่');
    }

    public function test_notify_never_cuts_a_bill_with_an_sms_older_than_the_bill(): void
    {
        $bill = $this->pendingBill(FortuneReading::READING_TYPE_DEEP, FortuneReading::STATUS_PENDING_PAYMENT, 39.21);

        // ยอดตรงแต่เวลาใน SMS เป็นของ 3 ชม.ก่อน (เช่น SMS เก่าถูกส่งซ้ำ) — ต้องไม่ตัดบิลที่เพิ่งเปิด 2 นาที
        $this->signedNotify(39.21, now()->subHours(3)->getTimestampMs())
            ->assertOk()
            ->assertJsonPath('data.fortune_reading', false);

        $this->assertFalse((bool) $bill->fresh()->is_paid);
        $this->assertSame('', $this->messagesSentToCustomer());
    }
}
