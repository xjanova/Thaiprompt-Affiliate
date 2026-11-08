<?php

namespace Tests\Feature\Fortune;

use App\Jobs\ProcessBufferedChatMessageJob;
use App\Models\AiApiKey;
use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\MessageBuffer;
use App\Services\Fortune\ReplyTokenVault;
use App\Services\Fortune\TakeoverSendGuard;
use App\Services\FortuneChannelManager;
use App\Services\LineAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤍 (2026-11-03) ลูกค้าบอกว่าอยากทำร้ายตัวเอง — บอทแม่หมอบน FB และ LINE ต้อง
 *
 * - ปลอบเบา ๆ ไม่ไล่ไปโทรหาใคร (ไม่ใช่ "ไม่ตอบเรื่องแบบนั้น") ไม่ทำนาย ไม่ส่งไปให้ AI
 * - ไม่มีปุ่มชวนดูดวง/ขาย
 * - แจ้งแอดมินทาง Telegram — LINE ต้องตอบด้วย reply เท่านั้น ห้าม push
 */
#[Group('self-harm-guard')]
class SelfHarmChatReplyTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000088';

    private const LINE_UID = 'U1234567890abcdef1234567890abcd88';

    private const ALERT_TOKEN = '999:alert-token';

    private array $aiCalls = [];

    /** 🟢 token ตาย — LINE reply API ตอบ 400 */
    private bool $lineReplyRejected = false;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TakeoverSendGuard::endMemoScope();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->enable_ai_chat = true;
        $settings->message_debounce_seconds = 30;
        $settings->chat_ai_provider = 'gemini';
        $settings->chat_ai_model = 'gemini-3.1-flash-lite';
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->line_enabled = true;
        $settings->line_channel_access_token = 'line-test-token';
        $settings->line_channel_secret = 'line-test-secret';
        $settings->telegram_alert_bot_token = self::ALERT_TOKEN;
        $settings->telegram_alert_chat_id = '-100123';
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        AiApiKey::create([
            'name' => 'chat-key-a',
            'provider' => 'gemini',
            'purpose' => 'chat',
            'api_key' => 'chat-key-a',
            'model' => 'gemini-3.1-flash-lite',
            'priority' => 50,
            'is_active' => true,
            'last_test_passed_at' => now(),
        ]);

        $this->app->instance(FortuneChatLogService::class, new class extends FortuneChatLogService
        {
            public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void {}
        });
        $this->mock(LineAlertService::class, fn ($m) => $m->shouldIgnoreMissing());

        Http::fake(function (Request $r) {
            if ($this->lineReplyRejected && str_contains($r->url(), '/message/reply')) {
                return Http::response(['message' => 'Invalid reply token'], 400);
            }
            if (str_contains($r->url(), ':generateContent')) {
                $this->aiCalls[] = $r->url();

                return Http::response([
                    'candidates' => [['content' => ['parts' => [['text' => 'แม่หมอดูให้แล้วค่ะ [OFFER_FORTUNE]']]]]],
                    'usageMetadata' => ['totalTokenCount' => 42],
                ], 200);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 1], 'message_id' => 'mid.1', 'recipient_id' => self::FB_UID], 200);
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function runFlush(string $platform, string $userId, string $text): void
    {
        app(MessageBuffer::class)->append('chat', $userId, $text);
        (new ProcessBufferedChatMessageJob($platform, $userId, 0))->handle();
    }

    /** ข้อความที่แจ้งแอดมินทาง Telegram */
    private function adminAlerts(): array
    {
        return collect(Http::recorded(fn (Request $r) => str_contains($r->url(), 'bot'.self::ALERT_TOKEN.'/sendMessage')))
            ->map(fn ($pair) => (string) ($pair[0]->data()['text'] ?? ''))
            ->values()
            ->all();
    }

    private function sentBodies(string $urlPart): string
    {
        return collect(Http::recorded(fn (Request $r) => str_contains($r->url(), $urlPart)))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function assertPersonaFlagged(string $platform, string $userId): void
    {
        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        $this->assertNotNull($persona, 'ต้องมี persona ไว้จำว่าลูกค้าเปราะบาง');
        $this->assertTrue($persona->hasRiskFlag('mental_fragile'));
        $this->assertNotEmpty($persona->risk_flags['crisis_resources_sent'] ?? null, 'ต้องจดว่าปลอบช่วงวิกฤตไปแล้ว');
    }

    public function test_facebook_self_harm_gets_gentle_comfort_not_dismissal_and_admin_is_alerted(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'ไม่ไหวแล้วค่ะ อยากฆ่าตัวตาย');

        $sent = $this->sentBodies('graph.facebook.com');
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $sent, 'ลูกค้า FB ต้องได้คำปลอบ');
        $this->assertStringNotContainsString('1323', $sent, 'ห้ามไล่ไปโทรหาใคร');
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $sent);
        $this->assertStringNotContainsString('quick_replies', $sent, 'ห้ามมีปุ่มดูดวง/ปุ่มขาย');
        $this->assertStringNotContainsString('START_FORTUNE', $sent);
        $this->assertSame([], $this->aiCalls, 'ห้ามส่งไปให้ AI ทำนาย');

        $alerts = $this->adminAlerts();
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString(self::FB_UID, $alerts[0]);
        $this->assertStringContainsString('อยากฆ่าตัวตาย', $alerts[0]);
        $this->assertStringNotContainsString('[TURN', $alerts[0], 'แอดมินเห็นแค่ข้อความลูกค้า');
        $this->assertStringContainsString('แชท facebook', $alerts[0], 'บอกแอดมินว่ามาจากช่องไหน');

        $this->assertPersonaFlagged('facebook', self::FB_UID);
    }

    public function test_line_self_harm_is_answered_by_reply_never_push(): void
    {
        Queue::fake();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');

        $this->runFlush('line', self::LINE_UID, 'I want to kill myself');

        $reply = $this->sentBodies('/message/reply');
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $reply);
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $reply);
        $this->assertStringNotContainsString('quickReply', $reply, 'ห้ามมี quick reply ดูดวง');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
        $this->assertSame([], $this->aiCalls);

        $alerts = $this->adminAlerts();
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString(self::LINE_UID, $alerts[0]);

        $this->assertPersonaFlagged('line', self::LINE_UID);
    }

    public function test_webhook_entry_answers_before_buffering_on_facebook_and_line(): void
    {
        Queue::fake();
        $channels = app(FortuneChannelManager::class);

        $fb = $channels->processMessage('facebook', self::FB_UID, 'อยากจบชีวิตตัวเอง', ['name' => 'ลูกค้าทดสอบ']);
        $line = $channels->processMessage('line', self::LINE_UID, 'suicide', ['name' => 'ลูกค้าทดสอบ'], ['reply_token' => 'rt-line']);

        $this->assertSame('crisis_support', $fb['action']);
        $this->assertSame('crisis_support', $line['action']);
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $this->sentBodies('graph.facebook.com'));
        $this->assertStringNotContainsString('quick_replies', $this->sentBodies('graph.facebook.com'));
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $this->sentBodies('/message/reply'));
        $this->assertStringNotContainsString('quickReply', $this->sentBodies('/message/reply'));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
        $this->assertSame([], $this->aiCalls);
        $this->assertCount(2, $this->adminAlerts());
        $this->assertPersonaFlagged('facebook', self::FB_UID);
        $this->assertPersonaFlagged('line', self::LINE_UID);
    }

    public function test_mid_prediction_customer_still_gets_comfort_not_silence(): void
    {
        Queue::fake();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');
        // ระหว่าง AI กำลังทำนาย ปกติบอทเงียบทุกข้อความ (silent_skip)
        $reading = $this->makeReading([
            'platform' => 'line',
            'platform_user_id' => self::LINE_UID,
            'facebook_user_id' => self::LINE_UID,
            'conversation_status' => FortuneReading::STATUS_PAID,
            'is_paid' => true,
            'amount_paid' => 39,
        ]);

        $result = app(FortuneChannelManager::class)
            ->processMessage('line', self::LINE_UID, 'ไม่อยากอยู่แล้ว', ['name' => 'ลูกค้าทดสอบ'], ['reply_token' => 'rt-fresh']);

        $this->assertSame('crisis_support', $result['action']);
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $this->sentBodies('/message/reply'));
        $this->assertSame(FortuneReading::STATUS_PAID, $reading->fresh()->conversation_status, 'ห้ามแตะสถานะคำทำนายที่จ่ายแล้ว');
    }

    /**
     * 🤍 (2026-11-09) prod #14660 — ระหว่างเลือกแพคเกจ ลูกค้าบอกอ้อม ๆ ว่าคนอื่นเจอแบบนี้คงโดดน้ำ/ผูกคอไปแล้ว
     * เดิมด่านไม่ติด → ได้คำตอบ AI + เมนู 39/99 + ปุ่มแพคเกจ
     */
    public function test_package_choice_customer_hinting_at_self_harm_gets_comfort_not_the_package_menu(): void
    {
        Queue::fake();
        $reading = $this->makeReading([
            'platform' => 'facebook',
            'platform_user_id' => self::FB_UID,
            'facebook_user_id' => self::FB_UID,
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
        ]);

        $result = app(FortuneChannelManager::class)->processMessage(
            'facebook',
            self::FB_UID,
            'ถ้าเป็นคนอื่นขอโทษนะครับที่ผมใช้คำคำนี้ถ้าเป็นคนอื่นโดดน้ำหรือผูกคอหนือไม่ก็บ้าไปแล้วครับ',
            ['name' => 'ลูกค้าทดสอบ']
        );

        $this->assertSame('crisis_support', $result['action']);
        $sent = $this->sentBodies('graph.facebook.com');
        $this->assertStringContainsString('อยู่ฟังตรงนี้', $sent);
        $this->assertStringNotContainsString('quick_replies', $sent, 'ห้ามมีปุ่มแพคเกจ');
        $this->assertStringNotContainsString('TIER_DEEP_39', $sent);
        $this->assertStringNotContainsString('sticker', $sent, 'ห้ามสติกเกอร์มือชี้เร่งเลือก');
        $this->assertSame([], $this->aiCalls);
        $this->assertCount(1, $this->adminAlerts());
        $this->assertSame(FortuneReading::STATUS_TIER_CHOICE, $reading->fresh()->conversation_status);
    }

    public function test_silenced_customer_is_answered_and_unsilenced(): void
    {
        Queue::fake();
        $persona = FortuneCustomerPersona::getOrCreate('facebook', self::FB_UID);
        $persona->chat_silenced_until = now()->addHours(5);
        $persona->save();

        $this->runFlush('facebook', self::FB_UID, 'อยากตาย');

        $this->assertStringContainsString('อยู่ฟังตรงนี้', $this->sentBodies('graph.facebook.com'));
        $this->assertNull($persona->fresh()->chat_silenced_until, 'คนวิกฤตต้องไม่เจอบอทเงียบในข้อความถัดไป');
    }

    public function test_follow_up_messages_still_get_answered_but_admin_is_not_spammed(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'อยากตาย');
        $this->runFlush('facebook', self::FB_UID, 'ไม่อยากอยู่แล้ว');

        $this->assertSame(2, substr_count($this->sentBodies('graph.facebook.com'), 'อยู่ฟังตรงนี้'), 'ทุกข้อความต้องได้คำตอบ ไม่ตัดบท');
        $this->assertCount(1, $this->adminAlerts(), 'คนเดิมแจ้งแอดมินครั้งเดียวใน 6 ชม.');
    }

    /**
     * 🤍 (2026-11-06 รีวิว PR 2649) ลูกค้าเล่าว่า "คนอื่น" อยากทำร้ายตัวเอง — ไม่ใช่วิกฤตของลูกค้าเอง
     * ให้ AI ตอบ (ไม่ใช่คำปลอบสำเร็จรูป) แต่ ❌ ไม่มีปุ่มดูดวง/ขาย ไม่แจ้งแอดมิน ไม่ติดธง persona
     */
    public function test_someone_elses_self_harm_gets_an_ai_reply_without_any_sales_push(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'แฟนขู่จะฆ่าตัวตายถ้าเลิก ควรเลิกไหมคะ');

        $this->assertNotSame([], $this->aiCalls, 'ต้องให้ AI ตอบ');
        $sent = $this->sentBodies('graph.facebook.com');
        $this->assertStringContainsString('แม่หมอดูให้แล้วค่ะ', $sent);
        $this->assertStringNotContainsString('อยู่ฟังตรงนี้', $sent, 'ไม่ใช่คำปลอบของคนที่อยากทำร้ายตัวเอง');
        $this->assertStringNotContainsString('quick_replies', $sent, 'ห้ามมีปุ่มดูดวง/ปุ่มขาย');
        $this->assertStringNotContainsString('START_FORTUNE', $sent);
        $this->assertStringNotContainsString('OFFER_FORTUNE', $sent);
        $this->assertSame([], $this->adminAlerts());
        $persona = FortuneCustomerPersona::findByPlatformUser('facebook', self::FB_UID);
        $this->assertFalse((bool) $persona?->hasRiskFlag('mental_fragile'));
    }

    /** 🤍 (2026-11-06 รีวิว PR 2649) ถามเรื่องพระแขวนคอ ไม่ใช่วิกฤต — ตอบตามปกติ ไม่แจ้งแอดมิน */
    public function test_an_amulet_question_is_not_a_crisis(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'ใส่พระแขวนคอองค์ไหนดีคะแม่หมอ');

        $this->assertNotSame([], $this->aiCalls);
        $this->assertStringNotContainsString('อยู่ฟังตรงนี้', $this->sentBodies('graph.facebook.com'));
        $this->assertSame([], $this->adminAlerts());
        $persona = FortuneCustomerPersona::findByPlatformUser('facebook', self::FB_UID);
        $this->assertFalse((bool) $persona?->hasRiskFlag('mental_fragile'));
    }

    /** 🟢 (2026-11-04) LINE reply ไม่ออก (token ตาย/ไม่มี) → ❌ ห้ามตกไป push · แอดมินยังได้แจ้งเตือน */
    public function test_line_crisis_reply_never_falls_back_to_push(): void
    {
        Queue::fake();
        $this->lineReplyRejected = true;

        $channels = app(FortuneChannelManager::class);
        $withDeadToken = $channels->processMessage('line', self::LINE_UID, 'อยากฆ่าตัวตาย', ['name' => 'ลูกค้าทดสอบ'], ['reply_token' => 'rt-dead']);
        $withoutToken = $channels->processMessage('line', self::LINE_UID, 'อยากตาย', ['name' => 'ลูกค้าทดสอบ']);

        $this->assertSame('crisis_support', $withDeadToken['action']);
        $this->assertSame('crisis_support', $withoutToken['action']);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
        $this->assertCount(1, $this->adminAlerts(), 'แอดมินต้องรู้ แม้ส่งถึงลูกค้าไม่ได้');
    }
}
