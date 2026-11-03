<?php

namespace Tests\Feature\Fortune;

use App\Jobs\ProcessBufferedChatMessageJob;
use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\MessageBuffer;
use App\Services\Fortune\ReplyTokenVault;
use App\Services\Fortune\TakeoverSendGuard;
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
 * - ตอบห่วงใย + สายด่วนใจ 1323 (ไม่ใช่ "ไม่ตอบเรื่องแบบนั้น") ไม่ทำนาย ไม่ส่งไปให้ AI
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

    public function test_facebook_self_harm_gets_hotline_not_dismissal_and_admin_is_alerted(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'ไม่ไหวแล้วค่ะ อยากฆ่าตัวตาย');

        $sent = $this->sentBodies('graph.facebook.com');
        $this->assertStringContainsString('1323', $sent, 'ลูกค้า FB ต้องได้สายด่วนใจ');
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $sent);
        $this->assertSame([], $this->aiCalls, 'ห้ามส่งไปให้ AI ทำนาย');

        $alerts = $this->adminAlerts();
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString(self::FB_UID, $alerts[0]);
        $this->assertStringContainsString('อยากฆ่าตัวตาย', $alerts[0]);
        $this->assertStringNotContainsString('[TURN', $alerts[0], 'แอดมินเห็นแค่ข้อความลูกค้า');
    }

    public function test_line_self_harm_is_answered_by_reply_never_push(): void
    {
        Queue::fake();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');

        $this->runFlush('line', self::LINE_UID, 'I want to kill myself');

        $reply = $this->sentBodies('/message/reply');
        $this->assertStringContainsString('1323', $reply);
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $reply);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
        $this->assertSame([], $this->aiCalls);

        $alerts = $this->adminAlerts();
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString(self::LINE_UID, $alerts[0]);
    }

    public function test_follow_up_messages_still_get_answered_but_admin_is_not_spammed(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'อยากตาย');
        $this->runFlush('facebook', self::FB_UID, 'ไม่อยากอยู่แล้ว');

        $this->assertSame(2, substr_count($this->sentBodies('graph.facebook.com'), '1323'), 'ทุกข้อความต้องได้คำตอบ ไม่ตัดบท');
        $this->assertCount(1, $this->adminAlerts(), 'คนเดิมแจ้งแอดมินครั้งเดียวใน 6 ชม.');
    }
}
