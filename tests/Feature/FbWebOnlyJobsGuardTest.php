<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FortuneMarketingController;
use App\Jobs\ProcessFortuneTelling;
use App\Jobs\SendBillReminderJob;
use App\Jobs\SendMuOfferJob;
use App\Jobs\SendPricingFollowUpJob;
use App\Models\FortuneMarketingCampaign;
use App\Models\FortuneProductOffer;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\UniquePaymentAmount;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FbWebOnlyOutboundGuard;
use App\Services\Fortune\FortuneMuOfferService;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneChartService;
use App\Services\FortuneConversationService;
use App\Services\LineAlertService;
use App\Services\LineFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🌐 (2026-11-03) T10 — job/cron ขาออกต้องเช็คธง FB web-only ตอน handle()
 *
 * สถานการณ์จริงที่กัน: job ถูก dispatch "ก่อน" แอดมินสลับธง (ทวงบิล · อธิบายค่าครู · การ์ดขายของหน่วง 1 ชม.
 * · ทำนายจากคอมเมนต์ที่ค้างคิว) แล้วมาทำงาน "หลัง" สลับธง ⇒ ต้องไม่มีอะไรวิ่งถึง FB
 * และ LINE ต้องได้ข้อความเหมือนเดิมทุกตัว
 */
class FbWebOnlyJobsGuardTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000101';

    private const LINE_UID = 'U0123456789abcdef0123456789abc101';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->settings(false);

        Http::fake(['*' => Http::response(['message_id' => 'm_1', 'recipient_id' => self::FB_UID], 200)]);

        // แจ้งแอดมินผ่าน LINE ส่วนตัว — ในเทสต์ไม่ยิงจริง
        $this->mock(LineAlertService::class)->shouldIgnoreMissing();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        $this->tearDownAdminAppWorld();
        Mockery::close();

        parent::tearDown();
    }

    // ============================================================
    // helpers
    // ============================================================

    /**
     * ตั้งค่าแถวกลาง + สลับธง (ล้าง memo ก่อน/หลัง — getSettings มี TTL 5 วิ)
     */
    private function settings(bool $webOnly): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill([
            'is_enabled' => true,
            'facebook_page_token' => 'EAAtestPageToken',
            'line_channel_access_token' => 'line-test-token',
            'enable_bill_payment_nudge' => true,
            'facebook_web_only' => $webOnly,
            'enable_web_fortune_button' => false,
        ])->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    /** คำขอทุกตัวที่ออกไปหา Facebook Graph (ส่งข้อความ / typing / โปรไฟล์) */
    private function fbRequests(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'))->all();
    }

    /** คำขอที่ออกไปหา LINE ของลูกค้าคนนี้ */
    private function lineRequestsTo(string $uid): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.line.me')
            && str_contains((string) json_encode($r->data()), $uid))->all();
    }

    /** ใส่ LINE service ตัวบันทึก (ไม่ยิง API · โควตาเหลือเสมอ) */
    private function lineDouble(): FbWebOnlyLineDouble
    {
        $double = new FbWebOnlyLineDouble(FortuneTellingSetting::getSettings());
        $this->app->instance(LineFortuneService::class, $double);

        return $double;
    }

    /**
     * บิลค้างจ่ายพร้อม UPA ที่ยังไม่หมดอายุ (ครบเงื่อนไขทวงของ job)
     */
    private function pendingBill(string $platform, string $uid, int $ageMinutes = 5): FortuneReading
    {
        $reading = $this->makeReading([
            'platform' => $platform,
            'platform_user_id' => $uid,
            'facebook_user_id' => $uid,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'bill_reference' => FortuneReading::generateBillReference(),
            'amount_paid' => 39.17,
            'created_at' => now()->subMinutes($ageMinutes),
        ]);

        $upa = UniquePaymentAmount::unguarded(fn () => UniquePaymentAmount::create([
            'base_amount' => 39, 'unique_amount' => 39.17, 'decimal_suffix' => 17,
            'transaction_id' => $reading->id, 'transaction_type' => 'fortune_reading',
            'status' => 'reserved', 'expires_at' => now()->addHours(2),
        ]));
        $reading->timestamps = false;
        $reading->forceFill(['unique_payment_amount_id' => $upa->id])->save();

        return $reading->fresh();
    }

    // ============================================================
    // ด่านกลาง
    // ============================================================

    public function test_ด่านกลาง_บล็อกเฉพาะ_fb_ตอนธงเปิด_และ_psid_ตัวเลขชนะ_hint_line(): void
    {
        $this->assertFalse(FbWebOnlyOutboundGuard::blocks('facebook', self::FB_UID), 'ธงปิด = ไม่บล็อก');

        $this->settings(true);

        $this->assertTrue(FbWebOnlyOutboundGuard::blocks('facebook', self::FB_UID));
        // FCS ที่สร้างจาก cron ไม่เคย setPlatform → hint 'line' แต่ id เป็น PSID → ต้องบล็อก
        $this->assertTrue(FbWebOnlyOutboundGuard::blocks('line', self::FB_UID));
        $this->assertFalse(FbWebOnlyOutboundGuard::blocks('line', self::LINE_UID), 'LINE ห้ามโดน');
        $this->assertFalse(FbWebOnlyOutboundGuard::blocks('facebook', self::LINE_UID), 'id LINE ชนะ hint');
        $this->assertFalse(FbWebOnlyOutboundGuard::blocks('telegram', 'tg_12345'), 'Telegram นอกขอบเขต');
        $this->assertTrue(FbWebOnlyOutboundGuard::flagOn());
    }

    // ============================================================
    // SendBillReminderJob + fortune:bill-reminder
    // ============================================================

    public function test_ทวงบิล_job_ที่เข้าคิวก่อนสลับธง_ไม่ส่งถึง_fb_และไม่_mark_stage(): void
    {
        $reading = $this->pendingBill('facebook', self::FB_UID);
        $job = new SendBillReminderJob($reading->id, 1); // dispatch ตอนธงยังปิด

        $this->settings(true); // แอดมินสลับธงระหว่างที่ job รอคิว
        $job->handle();

        $this->assertSame([], $this->fbRequests(), 'ห้ามมีอะไรวิ่งถึง FB');
        $this->assertSame(0, (int) $reading->fresh()->getConversationState('bill_reminder_stage', 0));
        $this->assertNull($reading->fresh()->getConversationState('bill_reminder_sent_at'));
    }

    public function test_ทวงบิล_job_ธงปิด_ยังส่ง_fb_ตามเดิม(): void
    {
        $reading = $this->pendingBill('facebook', self::FB_UID);

        (new SendBillReminderJob($reading->id, 1))->handle();

        $this->assertNotEmpty($this->fbRequests(), 'ธงปิด = พฤติกรรมเดิม (ยืนยันว่าเทสต์บล็อกไม่ได้ผ่านเพราะส่งไม่ได้อยู่แล้ว)');
        $this->assertSame(1, (int) $reading->fresh()->getConversationState('bill_reminder_stage', 0));
    }

    public function test_ทวงบิล_job_ฝั่ง_line_ยังส่งตามเดิมแม้ธงเปิด(): void
    {
        $reading = $this->pendingBill('line', self::LINE_UID);
        $job = new SendBillReminderJob($reading->id, 1);
        $line = $this->lineDouble();

        $this->settings(true);
        $job->handle();

        $this->assertNotEmpty($line->sent, 'LINE ต้องได้ข้อความทวงตามเดิม');
        $this->assertSame(self::LINE_UID, $line->sent[0][1]);
        $this->assertSame(1, (int) $reading->fresh()->getConversationState('bill_reminder_stage', 0));
        $this->assertSame([], $this->fbRequests());
    }

    public function test_cron_ทวงบิล_ไม่_dispatch_บิล_fb_แต่_dispatch_บิล_line(): void
    {
        $fb = $this->pendingBill('facebook', self::FB_UID);
        $line = $this->pendingBill('line', self::LINE_UID);
        $this->settings(true);
        Queue::fake();

        $this->artisan('fortune:bill-reminder')->assertExitCode(0);

        Queue::assertPushed(SendBillReminderJob::class, fn (SendBillReminderJob $j) => $j->readingId === $line->id);
        Queue::assertNotPushed(SendBillReminderJob::class, fn (SendBillReminderJob $j) => $j->readingId === $fb->id);
    }

    // ============================================================
    // SendPricingFollowUpJob
    // ============================================================

    public function test_อธิบายค่าครู_job_ค้างคิว_ไม่ส่งถึง_fb_และไม่_mark_7_วัน(): void
    {
        $job = new SendPricingFollowUpJob('facebook', self::FB_UID, ['deep_price' => 39]);

        $this->settings(true);
        $job->handle();

        $this->assertSame([], $this->fbRequests());
        $this->assertFalse(Cache::has('fortune:cafru_explained:facebook:'.self::FB_UID));
    }

    public function test_อธิบายค่าครู_ฝั่ง_line_ส่งตามเดิม(): void
    {
        $job = new SendPricingFollowUpJob('line', self::LINE_UID, ['deep_price' => 39]);
        $line = $this->lineDouble();

        $this->settings(true);
        $job->handle();

        $this->assertCount(1, $line->sent);
        $this->assertSame(self::LINE_UID, $line->sent[0][1]);
        $this->assertTrue(Cache::has('fortune:cafru_explained:line:'.self::LINE_UID));
    }

    // ============================================================
    // SendMuOfferJob
    // ============================================================

    public function test_การ์ดขายของหน่วงเวลา_fb_ไม่ส่ง_line_ส่ง(): void
    {
        $fbJob = new SendMuOfferJob('facebook', self::FB_UID, FortuneProductOffer::TRIGGER_PITCH_DECLINED);
        $lineJob = new SendMuOfferJob('line', self::LINE_UID, FortuneProductOffer::TRIGGER_PITCH_DECLINED);

        $service = Mockery::mock(FortuneMuOfferService::class);
        $service->shouldReceive('offer')->once()
            ->withArgs(fn ($platform, $uid) => $platform === 'line' && $uid === self::LINE_UID)
            ->andReturn(true);

        $this->settings(true);
        $fbJob->handle($service);
        $lineJob->handle($service);

        $this->assertSame([], $this->fbRequests());
    }

    public function test_การ์ดสินค้าท้ายบิลและลูกค้าถามเอง_ไม่ยื่นบน_fb_ใต้ธง_line_ยังได้(): void
    {
        \App\Models\MarketplaceSetting::set('fortune_mu_offer_enabled', '1');
        \App\Models\MarketplaceSetting::set('fortune_mu_offer_delays', json_encode([
            FortuneProductOffer::TRIGGER_CELTIC_END => 30,
        ]));
        Queue::fake();
        $svc = app(FortuneMuOfferService::class);

        $this->settings(true);
        foreach ([FortuneProductOffer::TRIGGER_CELTIC_END, FortuneProductOffer::TRIGGER_DEEP_END, FortuneProductOffer::TRIGGER_CUSTOMER_ASK] as $trigger) {
            $this->assertTrue($svc->isEnabled($trigger), "fixture: {$trigger} ต้องเปิดอยู่");
            $this->assertFalse($svc->canOffer('facebook', self::FB_UID, $trigger), "{$trigger} ห้ามยื่นบน FB ใต้ธง");
        }

        // เข้าคิวแบบหน่วงก็ไม่ได้ (งานหน่วงไม่ควรเกิดตั้งแต่แรก)
        $this->assertFalse($svc->send('facebook', self::FB_UID, FortuneProductOffer::TRIGGER_CELTIC_END));
        Queue::assertNotPushed(SendMuOfferJob::class);

        // LINE ไม่โดนธงนี้
        $svc->send('line', self::LINE_UID, FortuneProductOffer::TRIGGER_CELTIC_END);
        Queue::assertPushed(SendMuOfferJob::class, 1);
    }

    // ============================================================
    // ProcessFortuneTelling
    // ============================================================

    public function test_ทำนายจากคอมเมนต์ที่ค้างคิว_ไม่เรียก_ai_ไม่สร้างใบ_ไม่ส่งอะไรถึง_fb(): void
    {
        $job = new ProcessFortuneTelling([
            'facebook_user_id' => self::FB_UID,
            'facebook_user_name' => 'ลูกค้าคอมเมนต์',
            'questions' => ['งานจะดีไหม'],
            'is_comment' => true,
            'is_deep' => false,
            'is_paid' => false,
            'amount_paid' => 0,
            'comment_id' => 'c_1',
            'post_id' => 'p_1',
            'reply_type' => 'comment',
        ]);

        $this->settings(true);

        $fb = Mockery::mock(FacebookWebhookService::class);
        $fb->shouldNotReceive('sendTypingIndicator', 'getUserProfile', 'getUserPosts', 'sendFortuneTelling',
            'sendMessage', 'sendQuickReplies', 'replyToComment');

        $job->handle($fb);

        $this->assertSame(0, FortuneReading::count(), 'ห้ามสร้างใบดูดวง');
        $this->assertSame([], Http::recorded()->all(), 'ห้ามเรียก AI / FB');
    }

    // ============================================================
    // fortune:flow-nudge
    // ============================================================

    private function consentReading(string $platform, string $uid, int $silentMinutes): FortuneReading
    {
        return $this->makeReading([
            'platform' => $platform,
            'platform_user_id' => $uid,
            'facebook_user_id' => $uid,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
        ], [
            'consent_gate_shown_at' => now()->subMinutes($silentMinutes)->toIso8601String(),
            'consent_gate_tier' => 'deep',
        ]);
    }

    public function test_flow_nudge_ไม่ส่งกล่องพร้อมบูชาครูถึง_fb_แต่_line_ได้ตามเดิม(): void
    {
        $fb = $this->consentReading('facebook', self::FB_UID, 3);
        $line = $this->consentReading('line', self::LINE_UID, 3);
        $this->lineDouble(); // ด่านโควตา LINE ผ่านเสมอ (ตัวส่งจริงสร้างด้วย new → ยิง Http::fake)

        $this->settings(true);
        $this->artisan('fortune:flow-nudge')->assertExitCode(0);

        $this->assertSame([], $this->fbRequests(), 'ห้ามมีกล่องกระตุ้นจ่ายเงินใน Messenger');
        $this->assertEmpty($fb->fresh()->getConversationState('flow_nudge_sent_at'));

        $this->assertNotEmpty($line->fresh()->getConversationState('flow_nudge_sent_at'), 'LINE ต้องถูกกระตุ้นตามเดิม');
        $this->assertNotEmpty($this->lineRequestsTo(self::LINE_UID));
    }

    public function test_flow_nudge_exit_ยังปิดเมนูค้าง_แต่ไม่ทิ้งการ์ดขายของบน_fb(): void
    {
        $fb = $this->consentReading('facebook', self::FB_UID, 40);

        $offer = Mockery::mock(FortuneMuOfferService::class);
        $offer->shouldNotReceive('send');
        $this->app->instance(FortuneMuOfferService::class, $offer);

        $this->settings(true);
        $this->artisan('fortune:flow-nudge')->assertExitCode(0);

        $this->assertSame(FortuneReading::STATUS_COMPLETED, $fb->fresh()->conversation_status, 'การปิดเมนูค้างเงียบ ๆ ยังทำตามเดิม');
        $this->assertSame([], $this->fbRequests());
    }

    // ============================================================
    // fortune:marketing-send + executeCampaign
    // ============================================================

    public function test_แคมเปญเป้าหมาย_fb_ถูกพักทั้งก้อน(): void
    {
        $this->makeReading(['platform' => 'facebook', 'platform_user_id' => self::FB_UID, 'facebook_user_id' => self::FB_UID]);
        $campaign = FortuneMarketingCampaign::create([
            'name' => 'แคมเปญทดสอบ', 'topic' => 'ดวงประจำสัปดาห์',
            'message_template' => 'ดวงดาวสัปดาห์นี้สดใสค่ะ', 'use_ai_generate' => false,
            'target_platform' => 'facebook', 'schedule_type' => 'once',
            'scheduled_at' => now()->subMinute(), 'status' => FortuneMarketingCampaign::STATUS_SCHEDULED,
        ]);

        $this->settings(true);
        $this->artisan('fortune:marketing-send')->assertExitCode(0);

        $this->assertSame([], $this->fbRequests());
        $this->assertSame(FortuneMarketingCampaign::STATUS_SCHEDULED, $campaign->fresh()->status, 'พักไว้ ไม่ mark ส่งแล้ว/ไม่ mark error');
        $this->assertSame(0, (int) $campaign->fresh()->sent_count);
    }

    public function test_แคมเปญทุกช่องทาง_ข้ามผู้รับ_fb_ส่ง_line_ตามเดิม(): void
    {
        $this->makeReading(['platform' => 'facebook', 'platform_user_id' => self::FB_UID, 'facebook_user_id' => self::FB_UID]);
        $this->makeReading(['platform' => 'line', 'platform_user_id' => self::LINE_UID, 'facebook_user_id' => self::LINE_UID]);
        $campaign = FortuneMarketingCampaign::create([
            'name' => 'แคมเปญทดสอบ', 'topic' => 'ดวงประจำสัปดาห์',
            'message_template' => 'ดวงดาวสัปดาห์นี้สดใสค่ะ', 'use_ai_generate' => false,
            'target_platform' => 'all', 'schedule_type' => 'once',
            'scheduled_at' => now()->subMinute(), 'status' => FortuneMarketingCampaign::STATUS_SCHEDULED,
        ]);

        $chart = Mockery::mock(FortuneChartService::class);
        $chart->shouldReceive('generateQuickChart')->andReturn(null);
        $chart->shouldReceive('generateBirthChart')->andReturn(null);
        $this->app->instance(FortuneChartService::class, $chart);
        $this->lineDouble();

        $this->settings(true);
        (new FortuneMarketingController)->executeCampaign($campaign);

        $this->assertSame([], $this->fbRequests(), 'ผู้รับ FB ต้องถูกข้าม');
        $this->assertNotEmpty($this->lineRequestsTo(self::LINE_UID), 'ผู้รับ LINE ต้องได้ตามเดิม');
    }

    // ============================================================
    // PendingPaymentListenerTrait (บิลค้างระดับ B)
    // ============================================================

    private function listen(FortuneConversationService $fcs, FortuneReading $reading, string $text): array
    {
        $m = new \ReflectionMethod($fcs, 'respondWhilePendingPayment');
        $m->setAccessible(true);

        return $m->invoke($fcs, $reading, $text, [
            'action' => 'waiting_payment_reply',
            'pay_amount' => '39.17',
            'expires_at' => now()->addHours(2),
            'remaining_minutes' => 120,
            'footer' => '💸 ยอดที่ต้องโอน ฿39.17 (บิล FTU-TEST)',
            'partial_line' => '',
        ]);
    }

    public function test_รอโอน_fb_ใต้ธง_ตอบด้วยกล่องห้องแม่หมอ_ไม่มียอดบิล_และตอบครั้งเดียวใน_10_นาที(): void
    {
        $reading = $this->pendingBill('facebook', self::FB_UID);
        $fcs = new FortuneConversationService($this->settings(true));

        $first = $this->listen($fcs, $reading, 'โอนแล้วค่ะ');

        $this->assertSame('transfer_box', $first['action']);
        $this->assertTrue($first['web_handoff'] ?? false);
        $this->assertStringNotContainsString('฿', (string) $first['message']);
        $this->assertStringNotContainsString('39.17', json_encode($first['fb_template'] ?? [], JSON_UNESCAPED_UNICODE));
        $this->assertSame(FortuneReading::STATUS_PENDING_PAYMENT, $reading->fresh()->conversation_status, 'ไม่แตะบิล');

        $second = $this->listen($fcs, $reading, 'ไม่มีบัญชีธนาคารค่ะ');
        $this->assertSame('silent_skip', $second['action'], 'ภายใน 10 นาทีตอบครั้งเดียว');

        $this->assertSame('silent_skip', $this->listen($fcs, $reading, 'ค่ะ')['action'], 'คำรับทราบ = เงียบ');
        $this->assertSame([], $this->fbRequests(), 'ตัวฟังไม่ส่งเอง — คืน response ให้ ChannelManager');
    }

    public function test_รอโอน_line_ใต้ธง_พฤติกรรมเดิม(): void
    {
        $reading = $this->pendingBill('line', self::LINE_UID, 30);
        // บิลออกมา 30 นาทีแล้ว (เกินหน้าต่างเงียบ 10 นาที) → คำรับทราบได้ข้อความตายตัวเส้นเดิม
        UniquePaymentAmount::whereKey($reading->unique_payment_amount_id)
            ->update(['created_at' => now()->subMinutes(30)]);
        $fcs = new FortuneConversationService($this->settings(true));

        $res = $this->listen($fcs, $reading->fresh(), 'ได้ค่ะ');

        $this->assertSame('waiting_payment_reply', $res['action'], 'LINE ต้องเดินเส้นเดิม');
        $this->assertArrayNotHasKey('web_handoff', $res);
        $this->assertStringContainsString('โอนเสร็จ', (string) $res['message'], 'ข้อความรอโอนเดิมของ LINE');
    }
}

/**
 * LINE service ตัวบันทึก — โควตาเหลือเสมอ · ไม่ยิง API
 */
class FbWebOnlyLineDouble extends LineFortuneService
{
    /** @var list<array{0: string, 1: string, 2: mixed}> */
    public array $sent = [];

    public function canSpendNonCriticalPush(): bool
    {
        return true;
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        $this->sent[] = ['sendMessage', $recipientId, $message];

        return true;
    }

    public function sendMessagesWithReplyFallback(string $recipientId, array $messages, ?string $replyToken = null): bool
    {
        $this->sent[] = ['sendMessagesWithReplyFallback', $recipientId, $messages];

        return true;
    }
}
