<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SlipVerificationLog;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Services\FortuneConversationService;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🧾 (2026-10-04) บิล "โอนก่อนสร้างบิล" ที่สลิปตัดให้ ต้องแสดงหลักฐานครบในแอพ SMS Checker
 *
 * prod 30 วัน (8 บิลที่จ่ายแล้ว):
 *   - SMS เงินเข้าของก้อนนั้นไม่เคยถูกผูกกับบิล (7/8) — SMS มาถึงก่อนบิลเกิด ค้าง pending แล้วหมดอายุ
 *     แอพเห็นเงินเข้าไม่มีบิล + ช่องหลักฐานของบิลเป็นข้อมูลแทนที่ (PROMPTPAY + เวลาเปิดบิล)
 *   - บิล 39 แบบโอนก่อน เปิดดูรูปสลิปไม่ได้ (2/2) — ประวัติสลิปถูกบันทึกเป็น reject_amount
 *     (ตรวจครั้งแรกเทียบบิล Celtic ชั่วคราวขั้นต่ำ 99) ทั้งที่บิลจ่ายแล้ว
 *
 * @group fortune-billing
 */
class SlipPaidBillLinksBankSmsTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '27466581493002956';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-SLIP01',
            'device_name' => 'มือถือรับเงินแม่หมอ',
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /**
     * บิลโอนก่อนที่สลิปตัดแล้ว — ไม่มียอดจอง (UPA)
     *
     * @param  Carbon|null  $transferredAt  เวลาโอนในสลิป (null = ไม่บันทึก slip_verifications)
     */
    private function slipPaidBill(float $amount, string $type = FortuneReading::READING_TYPE_CELTIC_CROSS, ?Carbon $transferredAt = null): FortuneReading
    {
        $transRef = 'SLIP-'.Str::random(10);
        if ($transferredAt !== null) {
            \App\Models\SlipVerification::create([
                'trans_ref' => $transRef,
                'amount' => $amount,
                'status' => 'verified',
                'raw' => ['data' => ['transTimestamp' => $transferredAt->copy()->utc()->toIso8601String(), 'amount' => $amount]],
                'verified_at' => now(),
            ]);
        }

        return FortuneReading::create([
            'facebook_user_id' => self::PSID,
            'facebook_user_name' => 'ลูกค้าโอนก่อน',
            'questions' => [],
            'reading_type' => $type,
            'conversation_status' => FortuneReading::STATUS_PAID,
            'response_type' => 'private_message',
            'ai_response' => '',
            'ai_provider' => '',
            'platform' => 'facebook',
            'platform_user_id' => self::PSID,
            'bill_reference' => FortuneReading::generateBillReference(),
            'is_paid' => true,
            'paid_at' => now(),
            'amount_paid' => $amount,
            'amount_received' => $amount,
            'slipok_verified_at' => now(),
            'slipok_trans_ref' => $transRef,
        ]);
    }

    private function bankSms(float $amount, Carbon $at, string $status = 'pending'): SmsPaymentNotification
    {
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'sender_or_receiver' => 'X-0415',
            'sms_timestamp' => $at,
            'device_id' => 'SMSCHK-SLIP01',
            'nonce' => Str::random(24),
            'status' => $status,
        ]);
        $sms->forceFill(['created_at' => $at])->save();

        return $sms;
    }

    /** ผล SlipOK — transTimestamp เป็น UTC เหมือนของจริง */
    private function verify(float $amount, Carbon $transferredAt, ?string $transRef = null): array
    {
        return [
            'amount' => $amount,
            'trans_timestamp' => $transferredAt->copy()->utc()->toIso8601String(),
            'transRef' => $transRef ?? 'SLIP-'.Str::random(10),
            'sender_name' => 'นาย ลูกค้า โอนก่อน',
            'receiver_account' => 'xxx-x-x5514-x',
        ];
    }

    public function test_slip_paid_bill_gets_the_bank_sms_that_arrived_before_the_bill_existed(): void
    {
        $transferredAt = now()->subMinutes(13);
        $sms = $this->bankSms(99.00, $transferredAt->copy()->addSeconds(5));
        $bill = $this->slipPaidBill(99.00);

        $linked = app(SmsPaymentService::class)->attachSmsToSlipPaidBill($bill, $this->verify(99.00, $transferredAt));

        $this->assertSame($sms->id, $linked?->id);
        $this->assertSame($sms->id, (int) $bill->fresh()->sms_notification_id);
        $this->assertSame('X-0415', $bill->fresh()->sender_info);
        $this->assertSame('matched', $sms->fresh()->status);
        $this->assertSame($bill->id, (int) $sms->fresh()->matched_transaction_id);

        // แอพเห็นหลักฐานเป็น SMS จริง ไม่ใช่ข้อมูลแทนที่
        $order = $this->appOrder($bill->fresh());
        $this->assertSame($sms->id, $order['notification']['id']);
        $this->assertSame('KBANK', $order['notification']['bank']);
        $this->assertSame('auto_approved', $order['approval_status']);
    }

    public function test_it_never_guesses_between_two_matching_sms_or_takes_one_outside_the_window(): void
    {
        $transferredAt = now()->subMinutes(5);
        $this->bankSms(99.00, $transferredAt->copy()->addSeconds(3));
        $this->bankSms(99.00, $transferredAt->copy()->addSeconds(40));
        $twoMatches = $this->slipPaidBill(99.00);
        $this->assertNull(app(SmsPaymentService::class)->attachSmsToSlipPaidBill($twoMatches, $this->verify(99.00, $transferredAt)));
        $this->assertNull($twoMatches->fresh()->sms_notification_id);

        // ยอดตรงแต่ห่างเวลาโอนเกิน 2 นาที = คนละก้อน
        $farAway = $this->bankSms(39.00, now()->subMinutes(30));
        $other = $this->slipPaidBill(39.00, FortuneReading::READING_TYPE_DEEP);
        $this->assertNull(app(SmsPaymentService::class)->attachSmsToSlipPaidBill($other, $this->verify(39.00, now()->subMinutes(20))));
        $this->assertNull($farAway->fresh()->matched_transaction_id);

        // SMS ที่ผูกบิลอื่นแล้ว / ของจันทรา / ที่แอดมินตีตก ห้ามแตะ
        $at = now()->subMinutes(2);
        foreach (['external', 'rejected'] as $status) {
            $this->bankSms(49.00, $at, $status);
        }
        $third = $this->slipPaidBill(49.00, FortuneReading::READING_TYPE_DEEP);
        $this->assertNull(app(SmsPaymentService::class)->attachSmsToSlipPaidBill($third, $this->verify(49.00, $at)));
    }

    public function test_bank_sms_arriving_after_the_slip_already_cut_the_bill_is_attached_not_orphaned(): void
    {
        $bill = $this->slipPaidBill(99.00, FortuneReading::READING_TYPE_CELTIC_CROSS, now()->subSeconds(50));

        $result = $this->notifySms(99.00);

        $this->assertTrue($result['data']['fortune_reading']);
        $this->assertSame('matched', $result['data']['status'], 'ห้ามตกเป็นเงินกำพร้า');
        $this->assertSame((int) $result['data']['notification_id'], (int) $bill->fresh()->sms_notification_id);
    }

    public function test_late_sms_of_another_customer_with_the_same_round_amount_is_not_attached(): void
    {
        // ลูกค้า A โอน 99.00 เมื่อ 8 นาทีก่อน สลิปตัดบิลแล้ว แต่ SMS ของ A ยังมาไม่ถึง
        $billA = $this->slipPaidBill(99.00, FortuneReading::READING_TYPE_CELTIC_CROSS, now()->subMinutes(8));

        // SMS ที่เข้ามาตอนนี้คือเงินของลูกค้า B (โอนตอนนี้) — ห้ามไปเป็นหลักฐานของบิล A
        $result = $this->notifySms(99.00);

        $this->assertFalse($result['data']['fortune_reading']);
        $this->assertNull($billA->fresh()->sms_notification_id);
    }

    private function notifySms(float $amount): array
    {
        return app(SmsPaymentService::class)->processNotification([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'account_number' => '',
            'sender_or_receiver' => 'X-0415',
            'reference_number' => '',
            'sms_timestamp' => now()->getTimestampMs(),
            'device_id' => 'SMSCHK-SLIP01',
            'nonce' => Str::random(24),
        ], SmsCheckerDevice::where('device_id', 'SMSCHK-SLIP01')->firstOrFail(), '127.0.0.1');
    }

    public function test_prepay_deep_bill_slip_is_recorded_as_approved_so_the_app_can_open_it(): void
    {
        $bill = $this->slipPaidBill(39.00, FortuneReading::READING_TYPE_DEEP);
        $verify = $this->verify(39.00, now()->subMinutes(1), 'TRANS-PREPAY-39');

        // ตรวจครั้งแรกเทียบบิล Celtic ชั่วคราว (ขั้นต่ำ 99) → บันทึกเป็น reject_amount
        SlipVerificationLog::create([
            'fortune_reading_id' => $bill->id,
            'platform' => 'facebook',
            'chat_user_id' => self::PSID,
            'context' => 'returning_image',
            'sent_to_slipok' => true,
            'decision' => 'reject_amount',
            'trans_ref' => 'TRANS-PREPAY-39',
            'amount' => 39.00,
            'slip_image_path' => 'fortune/slip_archive/test_prepay_39.jpg',
        ]);
        $this->assertArrayNotHasKey('slip', $this->appOrder($bill)['order_details_json']);

        $service = new FortuneConversationService(FortuneTellingSetting::getSettings());
        $log = new \ReflectionMethod($service, 'ensureSlipApprovalLogged');
        $log->invoke($service, $bill, $verify, 'facebook', self::PSID);
        $log->invoke($service, $bill, $verify, 'facebook', self::PSID); // ซ้ำต้องไม่เพิ่มแถว

        $approved = SlipVerificationLog::where('trans_ref', 'TRANS-PREPAY-39')->where('decision', 'approve')->get();
        $this->assertCount(1, $approved);
        $this->assertFalse((bool) $approved->first()->sent_to_slipok, 'ไม่ได้ยิง SlipOK ซ้ำ — สถิติต้องไม่เพี้ยน');
        $this->assertSame('fortune/slip_archive/test_prepay_39.jpg', $approved->first()->slip_image_path);

        $slip = $this->appOrder($bill)['order_details_json']['slip'] ?? null;
        $this->assertNotNull($slip, 'แอพต้องเปิดดูรูปสลิปที่ตัดบิลนี้ได้');
        $this->assertSame('TRANS-PREPAY-39', $slip['trans_ref']);
    }

    /**
     * ⏱️ migration 2026_10_04_100000 คืนเวลา SMS จริงจาก raw_payload ให้แถวที่ถูกเขียนทับ
     *   (prod MariaDB เติม ON UPDATE CURRENT_TIMESTAMP ให้ sms_timestamp — แถวที่ถูกแก้ทีหลังเวลาเพี้ยน)
     *   MySQL 8 ใน CI ไม่ติดกับดักนี้ → ส่วน ALTER ข้ามเอง เทสต์เฉพาะส่วนคืนเวลา
     */
    public function test_migration_restores_the_real_sms_time_from_the_app_payload(): void
    {
        $realMs = now()->subDays(7)->startOfMinute()->getTimestampMs();
        $make = function (?int $rawMs, string $stored) {
            $sms = $this->bankSms(39.21, now());
            $sms->forceFill([
                'raw_payload' => $rawMs === null ? null : json_encode(['sms_timestamp' => $rawMs, 'amount' => '39.21']),
                'sms_timestamp' => $stored,
            ])->save();

            return $sms;
        };

        $drifted = $make($realMs, now()->toDateTimeString());                  // ถูกเขียนทับเป็นตอนแก้แถว
        $fine = $make($realMs, date('Y-m-d H:i:s', intdiv($realMs, 1000) + 20)); // ห่าง 20 วิ — ปล่อยไว้
        $noRaw = $make(null, now()->toDateTimeString());                       // ไม่มีต้นฉบับ — ไม่แตะ

        (require database_path('migrations/2026_10_04_100000_stop_payment_timestamps_auto_updating.php'))->up();

        $this->assertSame(date('Y-m-d H:i:s', intdiv($realMs, 1000)), (string) $drifted->fresh()->getRawOriginal('sms_timestamp'));
        $this->assertSame(date('Y-m-d H:i:s', intdiv($realMs, 1000) + 20), (string) $fine->fresh()->getRawOriginal('sms_timestamp'));
        $this->assertSame((string) $noRaw->getRawOriginal('sms_timestamp'), (string) $noRaw->fresh()->getRawOriginal('sms_timestamp'));
    }

    private function appOrder(FortuneReading $reading): array
    {
        $controller = app(SmsPaymentController::class);
        $transform = new \ReflectionMethod($controller, 'transformFortuneReadingToOrderApproval');

        return $transform->invoke($controller, $reading);
    }
}
