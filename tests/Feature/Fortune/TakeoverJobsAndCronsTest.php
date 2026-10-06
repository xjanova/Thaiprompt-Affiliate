<?php

namespace Tests\Feature\Fortune;

use App\Jobs\ProcessBufferedCelticMessageJob;
use App\Jobs\ProcessBufferedChatMessageJob;
use App\Jobs\RetryPayFirstPushJob;
use App\Jobs\SendBillReminderJob;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\UniquePaymentAmount;
use App\Services\Fortune\MessageBuffer;
use App\Services\Fortune\TakeoverResumeService;
use App\Services\LineAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤫 (2026-10-06) job / cron ระหว่างแอดมินเทคโอเวอร์
 *
 * - เทคโอเวอร์เริ่ม "หลัง" job เข้าคิว → job ตื่นมาต้องไม่ส่ง ไม่เรียก AI ไม่เผาตัวนับ retry/attempt
 * - cron วนทีละบิล → ข้ามบิลของลูกค้าที่ถูกเทคโอเวอร์ (ไม่ส่ง ไม่นับ ไม่ปิดบิล)
 * - ของที่จ่ายแล้ว → พักไว้ใน takeover_deferred (ไม่หาย)
 */
#[Group('takeover-silence')]
class TakeoverJobsAndCronsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000077';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->admin_handover_enabled = true;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake(['*' => Http::response(['message_id' => 'm_1'], 200)]);

        // แจ้งแอดมินผ่านช่องแอดมินเท่านั้น — ในเทสต์ไม่ยิงจริง
        $this->mock(LineAlertService::class)->shouldIgnoreMissing();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function takeoverFields(): array
    {
        return [
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ];
    }

    /** คำขอทุกตัวที่ออกไปนอกระบบ (ส่งข้อความ + AI) */
    private function outbound(): array
    {
        return Http::recorded(fn (Request $r) => true)->all();
    }

    private function messagingRequests(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages'))->all();
    }

    // ============================================================
    // jobs
    // ============================================================

    public function test_buffered_celtic_job_does_not_take_the_question_or_call_ai(): void
    {
        $reading = $this->makeReading(array_merge([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(20),
        ], $this->takeoverFields()), ['celtic_pending_q' => 'งานใหม่จะดีไหมคะ', 'celtic_pending_q_at' => now()->subMinute()->toIso8601String()]);

        (new ProcessBufferedCelticMessageJob($reading->id, 'facebook', self::FB_UID, 0))->handle();

        $fresh = $reading->fresh();
        $this->assertSame([], $this->outbound(), 'ห้ามเรียก AI / ห้ามส่ง');
        $this->assertSame('งานใหม่จะดีไหมคะ', $fresh->getConversationState('celtic_pending_q'), 'ห้าม take สำเนาคำถาม');
        $this->assertSame(FortuneReading::STATUS_CELTIC_AWAITING_QUESTION, $fresh->conversation_status);
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_RESUME, (array) $fresh->getConversationState('takeover_deferred'));
    }

    public function test_buffered_chat_job_drops_chatter_without_ai(): void
    {
        $this->makeReading(array_merge(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE], $this->takeoverFields()));
        $buffer = app(MessageBuffer::class);
        $buffer->append('chat', self::FB_UID, 'แม่หมอคะ อยากถามเรื่องความรัก');

        // window 0 = ครบหน้าต่างแล้ว (MessageBuffer ใช้ microtime — travel() ของ Carbon ไม่มีผล)
        (new ProcessBufferedChatMessageJob('facebook', self::FB_UID, 0))->handle();

        $this->assertSame([], $this->outbound());
        $this->assertEmpty($buffer->peek('chat', self::FB_UID), 'แชทค้างถูกทิ้ง ไม่ไปตอบรวบตอนจบเทคโอเวอร์');
    }

    public function test_pay_first_retry_job_defers_instead_of_throwing(): void
    {
        $reading = $this->makeReading(array_merge([
            'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinute(),
        ], $this->takeoverFields()));

        (new RetryPayFirstPushJob($reading->id, 'facebook', self::FB_UID, 'ขอวันเดือนปีเกิดก่อนนะคะ', 'collecting_birthdate'))->handle();

        $this->assertSame([], $this->messagingRequests());
        $deferred = (array) $reading->fresh()->getConversationState('takeover_deferred');
        $this->assertSame('ขอวันเดือนปีเกิดก่อนนะคะ', $deferred[TakeoverResumeService::ITEM_PAYFIRST_BIRTHDATE]['payload']['message'] ?? null);
        $this->assertNull($reading->fresh()->getConversationState('birthdate_resent_at'));
    }

    public function test_bill_reminder_job_neither_sends_nor_marks_the_stage(): void
    {
        $reading = $this->makeReading(array_merge([
            'conversation_status' => FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            'bill_reference' => 'FTU-261006-R0001',
            'created_at' => now()->subMinutes(20),
        ], $this->takeoverFields()));

        (new SendBillReminderJob($reading->id, 1))->handle();

        $this->assertSame([], $this->outbound());
        $this->assertSame(0, (int) $reading->fresh()->getConversationState('bill_reminder_stage', 0));
        $this->assertNull($reading->fresh()->getConversationState('bill_reminder_sent_at'));
    }

    public function test_process_deep_command_defers_delivery_without_burning_retry_counter(): void
    {
        $reading = $this->makeReading(array_merge([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(5),
            'bill_reference' => 'FTU-261006-D0001',
            'deep_response' => 'คำทำนายเชิงลึกของคุณ ... (เนื้อหา)',
        ], $this->takeoverFields()));

        $this->artisan('fortune:process-deep', ['readingId' => $reading->id, 'platform' => 'facebook', 'userId' => self::FB_UID])
            ->assertExitCode(0);

        $fresh = $reading->fresh();
        $this->assertSame([], $this->outbound());
        $this->assertSame(0, (int) $fresh->getConversationState('reading_notification_retry_count', 0));
        $this->assertFalse((bool) $fresh->getConversationState('reading_sent_directly', false));
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_DEEP_READING, (array) $fresh->getConversationState('takeover_deferred'));
        $this->assertFalse(Cache::has("fortune:deep_deliver:{$reading->id}"), 'ห้ามค้างล็อกส่ง');
    }

    // ============================================================
    // crons
    // ============================================================

    public function test_celtic_redeliver_skips_without_counting_attempts(): void
    {
        $reading = $this->makeReading(array_merge([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(30),
        ], $this->takeoverFields()));
        $q = FortuneCelticQuestion::create([
            'fortune_reading_id' => $reading->id, 'sequence' => 1, 'question' => 'งานจะดีไหม',
            'response' => 'คำตอบของแม่หมอ', 'answered_at' => now()->subMinutes(5), 'delivery_attempts' => 0,
        ]);

        $this->artisan('fortune:celtic-redeliver')->assertExitCode(0);

        $q->refresh();
        $this->assertSame([], $this->messagingRequests());
        $this->assertSame(0, (int) $q->delivery_attempts);
        $this->assertNull($q->delivered_at);
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_ANSWERS, (array) $reading->fresh()->getConversationState('takeover_deferred'));
    }

    public function test_celtic_summary_redeliver_skips_without_counting_attempts(): void
    {
        $reading = $this->makeReading(array_merge([
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subHour(),
        ], $this->takeoverFields()), ['celtic_summary_delivered' => false, 'celtic_finale_text' => 'บทสรุปของแม่หมอ']);

        $this->artisan('fortune:celtic-summary-redeliver')->assertExitCode(0);

        $fresh = $reading->fresh();
        $this->assertSame([], $this->messagingRequests());
        $this->assertSame(0, (int) $fresh->getConversationState('celtic_summary_attempts', 0));
        $this->assertFalse((bool) $fresh->getConversationState('celtic_summary_delivered'));
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_SUMMARY, (array) $fresh->getConversationState('takeover_deferred'));
    }

    public function test_check_pending_phase2_defers_instead_of_retrying(): void
    {
        $reading = $this->makeReading(array_merge([
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(10),
            'deep_response' => 'คำทำนายเชิงลึก',
        ], $this->takeoverFields()));

        $this->artisan('fortune:check-pending')->assertExitCode(0);

        $fresh = $reading->fresh();
        $this->assertSame([], $this->messagingRequests());
        $this->assertSame(0, (int) $fresh->getConversationState('phase2_notify_retry_count', 0));
        $this->assertFalse((bool) $fresh->getConversationState('reading_sent_directly', false));
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_DEEP_READING, (array) $fresh->getConversationState('takeover_deferred'));
    }

    public function test_expire_stuck_paid_does_not_flag_a_bill_under_takeover(): void
    {
        $reading = $this->makeReading(array_merge([
            'conversation_status' => FortuneReading::STATUS_PAID,
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subHours(30),
        ], $this->takeoverFields()));

        $this->artisan('fortune:expire-stuck-paid')->assertExitCode(0);

        $this->assertNull($reading->fresh()->getConversationState('admin_review_alerted'));
    }

    public function test_expired_unpaid_bill_is_not_cancelled_while_admin_is_talking(): void
    {
        $makeExpiredBill = function (string $uid, array $extra) {
            $reading = $this->makeReading(array_merge([
                'platform_user_id' => $uid,
                'facebook_user_id' => $uid,
                'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
                'bill_reference' => FortuneReading::generateBillReference(),
                'amount_paid' => 39.17,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ], $extra));
            $upa = UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create([
                'base_amount' => 39, 'unique_amount' => 39.17, 'decimal_suffix' => 17,
                'transaction_id' => $reading->id, 'transaction_type' => 'fortune_reading',
                'status' => 'reserved', 'expires_at' => now()->subHour(),
            ]));
            $reading->timestamps = false;
            $reading->forceFill(['unique_payment_amount_id' => $upa->id])->save();

            return $reading;
        };

        $talking = $makeExpiredBill(self::FB_UID, $this->takeoverFields());
        $control = $makeExpiredBill('61550000000099', []); // ลูกค้าอื่น — ต้องถูกปิดตามปกติ

        FortuneReading::cancelExpiredPendingBills();

        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $talking->fresh()->conversation_status);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $control->fresh()->conversation_status, 'บิลของคนที่ไม่ได้ถูกเทคโอเวอร์ยังหมดอายุตามปกติ');
        $this->assertSame([], Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages')
            && str_contains(json_encode($r->data()), self::FB_UID))->all());
    }
}
