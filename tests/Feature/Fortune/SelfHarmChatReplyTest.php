<?php

namespace Tests\Feature\Fortune;

use App\Jobs\ProcessBufferedChatMessageJob;
use App\Models\AiApiKey;
use App\Models\FortuneCustomerPersona;
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
 * 🤍 (2026-11-09 owner) เลิกตรวจจับ self-harm/วิกฤตแล้ว — "ให้ AI แม่หมอตอบตามปกติ" ทั้งบอท FB และ LINE
 *
 * - ข้อความแบบ "อยากฆ่าตัวตาย" ไปให้ AI ตอบ ไม่มีคำปลอบสำเร็จรูป ไม่มีคำปัด "ไม่ตอบเรื่องแบบนั้น"
 * - ไม่แจ้งแอดมินทาง Telegram · ไม่ติดธง crisis_resources_sent
 * - LINE ยังตอบด้วย reply ห้าม push (กฎโควต้า)
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

    /** ไม่มีการแจ้งแอดมินเรื่อง self-harm และไม่มีคำตอบสำเร็จรูปของด่านเดิม */
    private function assertTreatedAsNormalChat(string $sent, string $platform, string $userId): void
    {
        $this->assertNotEmpty($this->aiCalls, 'ต้องส่งให้ AI ตอบตามปกติ');
        $this->assertStringNotContainsString('อยู่ฟังตรงนี้', $sent, 'ไม่มีคำปลอบสำเร็จรูปของด่านเดิม');
        $this->assertStringNotContainsString('ไม่ตอบเรื่องแบบนั้น', $sent, 'ไม่ใช่คำปัดของด่านความรุนแรง');
        foreach ($this->adminAlerts() as $alert) {
            $this->assertStringNotContainsString($userId, $alert, 'ไม่แจ้งแอดมินเรื่อง self-harm แล้ว');
        }
        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $userId);
        $this->assertEmpty($persona?->risk_flags['crisis_resources_sent'] ?? null);
    }

    public function test_facebook_self_harm_message_goes_to_the_normal_ai_reply(): void
    {
        Queue::fake();

        $this->runFlush('facebook', self::FB_UID, 'ไม่ไหวแล้วค่ะ อยากฆ่าตัวตาย');

        $sent = $this->sentBodies('graph.facebook.com');
        $this->assertStringContainsString('แม่หมอดูให้แล้วค่ะ', $sent, 'ลูกค้า FB ต้องได้คำตอบจาก AI');
        $this->assertTreatedAsNormalChat($sent, 'facebook', self::FB_UID);
    }

    public function test_line_self_harm_message_goes_to_the_normal_ai_reply_by_reply_never_push(): void
    {
        Queue::fake();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');

        $this->runFlush('line', self::LINE_UID, 'อยากตาย ไม่ไหวแล้ว');

        $reply = $this->sentBodies('/message/reply');
        $this->assertStringContainsString('แม่หมอดูให้แล้วค่ะ', $reply, 'ลูกค้า LINE ต้องได้คำตอบจาก AI ทาง reply');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
        $this->assertTreatedAsNormalChat($reply, 'line', self::LINE_UID);
    }
}
