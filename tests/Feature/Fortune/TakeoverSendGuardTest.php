<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\TakeoverSendGuard;
use App\Services\FortuneChannelManager;
use App\Services\FortuneTakeoverService;
use App\Services\LineFortuneService;
use App\Services\TelegramFortuneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤫 (2026-10-06) ด่านกลาง "แอดมินเทคโอเวอร์อยู่ = บอทห้ามส่งอะไรเลย" + ชั้นส่งของ 3 แพลตฟอร์ม
 *
 * เจ้าของสั่ง: "ถ้าเทคโอเวอร์คือแอดมินคุยแล้ว บอทต้องหยุดแทรกก่อน"
 */
#[Group('takeover-silence')]
class TakeoverSendGuardTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000077';

    private const LINE_UID = 'U1234567890abcdef1234567890abcdef';

    private const TG_UID = 'tg_555';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->line_enabled = true;
        $settings->line_channel_access_token = 'line-test-token';
        $settings->line_channel_secret = 'line-test-secret';
        $settings->telegram_enabled = true;
        $settings->telegram_bot_token = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';
        $settings->admin_handover_enabled = true;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
            '*' => Http::response(['message_id' => 'm_1', 'recipient_id' => self::FB_UID], 200),
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function takenOver(string $uid = self::FB_UID, string $platform = 'facebook', string $reason = FortuneReading::TAKEOVER_REASON_MANUAL, int $minutes = 20): FortuneReading
    {
        return $this->makeReading([
            'platform' => $platform,
            'platform_user_id' => $uid,
            'facebook_user_id' => $uid,
            'admin_takeover_reason' => $reason,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes($minutes),
        ]);
    }

    /** คำขอที่ "ไปโผล่ในแชทลูกค้า" */
    private function messagingRequests(): array
    {
        return Http::recorded(function (Request $r) {
            $url = $r->url();

            return str_contains($url, '/me/messages')
                || str_contains($url, 'api.line.me/v2/bot/message/push')
                || str_contains($url, 'api.line.me/v2/bot/message/reply')
                || str_contains($url, 'api.line.me/v2/bot/chat/loading')
                || preg_match('#api\.telegram\.org/bot[^/]+/(send|edit|setMessageReaction)#', $url);
        })->all();
    }

    // ============================================================
    // ตัวเช็คกลาง
    // ============================================================

    public function test_active_takeover_blocks_and_expired_does_not(): void
    {
        $this->assertFalse(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));

        $reading = $this->takenOver();
        $this->assertTrue(TakeoverSendGuard::isTakenOver('facebook', self::FB_UID));
        $this->assertTrue(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));

        $reading->forceFill(['admin_takeover_until' => now()->subSecond()])->save();
        $this->assertFalse(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));
    }

    public function test_takeover_is_per_customer_across_all_their_readings(): void
    {
        // บิลเก่าถูกเทคโอเวอร์ — บิลใหม่ของคนเดียวกันก็ต้องเงียบ
        $this->takenOver();
        $newer = $this->makeReading(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);

        $this->assertTrue(TakeoverSendGuard::readingIsTakenOver($newer));
        // ลูกค้าคนอื่นไม่โดน
        $this->assertFalse(TakeoverSendGuard::blocks('facebook', '61550000000099', 'sendMessage'));
    }

    public function test_human_admin_scope_bypasses_but_from_admin_option_does_not(): void
    {
        $this->takenOver();
        $fb = new FacebookWebhookService(FortuneTellingSetting::getSettings());

        // from_admin = "ข้อความระบบแบบ push" ที่บอทอัตโนมัติตั้งไว้ — ต้องโดนบล็อก
        $this->assertFalse($fb->sendMessage(self::FB_UID, 'ข้อความจากระบบ', ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']));
        $this->assertSame([], $this->messagingRequests());

        // แอดมินตัวจริง → ส่งได้
        $sent = TakeoverSendGuard::asHumanAdmin(fn () => $fb->sendMessage(self::FB_UID, 'แอดมินพิมพ์เอง'));
        $this->assertTrue($sent);
        $this->assertCount(1, $this->messagingRequests());

        // ออกจาก scope แล้วบล็อกตามเดิม
        $this->assertFalse(TakeoverSendGuard::inHumanAdminScope());
        $this->assertTrue(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));
    }

    public function test_manual_takeover_is_honoured_even_when_auto_handover_toggle_is_off(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_handover_enabled = false;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $manual = $this->takenOver();
        $this->assertTrue(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));
        $this->assertTrue(app(FortuneTakeoverService::class)->isActiveByPlatform('facebook', self::FB_UID));
        $this->assertTrue(app(FortuneTakeoverService::class)->isActive($manual));

        // เทคโอเวอร์อัตโนมัติ (ลูกค้าขอคุยกับคน) ปิดตามสวิตช์
        $this->takenOver('61550000000088', 'facebook', FortuneReading::TAKEOVER_REASON_CUSTOMER_REQUEST);
        $this->assertFalse(TakeoverSendGuard::blocks('facebook', '61550000000088', 'sendMessage'));
    }

    public function test_cache_clear_does_not_unblock_db_is_the_truth(): void
    {
        $this->takenOver();
        Cache::flush();

        $this->assertTrue(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));
        $this->assertTrue(app(FortuneTakeoverService::class)->isActiveByPlatform('facebook', self::FB_UID));
    }

    public function test_check_failure_fails_open(): void
    {
        $this->takenOver();

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'admin_takeover_until')) {
                throw new \RuntimeException('db down');
            }
        });

        $this->assertFalse(TakeoverSendGuard::blocks('facebook', self::FB_UID, 'sendMessage'));
        $this->assertFalse(TakeoverSendGuard::userIsTakenOver('facebook', self::FB_UID));
    }

    public function test_bypass_helper_no_longer_lets_anything_through(): void
    {
        $svc = app(FortuneTakeoverService::class);
        $this->makeReading(['is_paid' => true, 'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);

        $this->assertFalse($svc->shouldBypassTakeover('facebook', self::FB_UID, '99'));
        $this->assertFalse($svc->shouldBypassTakeover('facebook', self::FB_UID, '', true));
    }

    public function test_manual_takeover_without_minutes_defaults_to_30_even_if_setting_is_1(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_handover_timeout = 1;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $reading = $this->makeReading();
        $minutes = app(FortuneTakeoverService::class)->takeover($reading, FortuneReading::TAKEOVER_REASON_MANUAL, null, null, null, true);

        $this->assertSame(30, $minutes);
        $this->assertEqualsWithDelta(now()->addMinutes(30)->getTimestamp(), $reading->fresh()->admin_takeover_until->getTimestamp(), 5);
    }

    // ============================================================
    // ชั้นส่ง: Facebook 10 เมธอด
    // ============================================================

    public function test_facebook_all_ten_send_methods_are_blocked(): void
    {
        $this->takenOver();
        $fb = new FacebookWebhookService(FortuneTellingSetting::getSettings());
        $u = self::FB_UID;
        $tpl = ['template_type' => 'button', 'text' => 'x', 'buttons' => [['type' => 'postback', 'title' => 'A', 'payload' => 'A']]];

        $this->assertFalse($fb->sendMessage($u, 'สวัสดีค่ะ'));
        $this->assertFalse($fb->sendImage($u, 'https://example.com/a.jpg'));
        $this->assertFalse($fb->sendAudio($u, 'https://example.com/a.mp3'));
        $fb->sendTypingIndicator($u, true);
        $this->assertFalse($fb->sendQuickReplies($u, 'เลือกเลย', [['title' => '39', 'payload' => 'P39']]));
        $this->assertFalse($fb->sendSticker($u, 369239263222822));          // postGesture
        $this->assertFalse($fb->reactToMessage($u, 'mid.123', '❤️'));        // postGesture
        $this->assertFalse($fb->sendButtonTemplate($u, $tpl));
        $this->assertFalse($fb->sendGenericTemplate($u, [['title' => 'x']]));
        $this->assertFalse($fb->sendTemplateWithQuickReplies($u, $tpl, [['title' => 'A', 'payload' => 'A']]));
        $this->assertFalse($fb->sendRichMessage($u, ['type' => 'text', 'text' => 'x']));

        $this->assertSame([], $this->messagingRequests());
    }

    public function test_facebook_sends_normally_without_takeover(): void
    {
        $fb = new FacebookWebhookService(FortuneTellingSetting::getSettings());

        $this->assertTrue($fb->sendMessage(self::FB_UID, 'คำทำนายของคุณค่ะ'));
        $this->assertNotEmpty($this->messagingRequests());
    }

    // ============================================================
    // ชั้นส่ง: LINE push / priority / reply / loading
    // ============================================================

    public function test_line_push_priority_reply_and_loading_are_blocked(): void
    {
        $this->takenOver(self::LINE_UID, 'line');
        $line = new LineFortuneService(FortuneTellingSetting::getSettings());
        $msg = [['type' => 'text', 'text' => 'คำทำนาย']];

        $this->assertFalse($line->pushPaidDeliverable(self::LINE_UID, $msg, true));   // pushMessagePriority
        $this->assertFalse($line->pushPaidDeliverable(self::LINE_UID, $msg, false));  // pushMessage
        $this->assertFalse($line->sendMessage(self::LINE_UID, 'ข้อความ'));
        $this->assertFalse($line->showLoadingAnimation(self::LINE_UID, 20));

        TakeoverSendGuard::bindReplyToken('rt-owned', 'line', self::LINE_UID);
        $this->assertFalse($line->replyMessage('rt-owned', $msg));

        $this->assertSame([], $this->messagingRequests());

        // token ที่ไม่รู้เจ้าของ = ปล่อยผ่าน (fail open) · ลูกค้าคนอื่นไม่โดน
        $this->assertTrue($line->replyMessage('rt-unknown', $msg));
        $this->assertCount(1, $this->messagingRequests());
    }

    // ============================================================
    // ชั้นส่ง: Telegram call()
    // ============================================================

    public function test_telegram_send_methods_blocked_but_answer_callback_allowed(): void
    {
        $this->takenOver(self::TG_UID, 'telegram');
        $tg = new TelegramFortuneService(FortuneTellingSetting::getSettings());

        $this->assertFalse($tg->sendMessage(self::TG_UID, 'สวัสดี'));
        $tg->sendTypingIndicator(self::TG_UID);
        $this->assertFalse($tg->call('editMessageText', ['chat_id' => 555, 'message_id' => 1, 'text' => 'x'])['ok']);
        $this->assertSame([], $this->messagingRequests());

        $tg->answerCallbackQuery('cbq-1');
        $answered = Http::recorded(fn (Request $r) => str_contains($r->url(), '/answerCallbackQuery'));
        $this->assertCount(1, $answered);
    }

    // ============================================================
    // FortuneChannelManager::sendResponse
    // ============================================================

    public function test_channel_manager_send_response_returns_false_and_logs_suppression(): void
    {
        $spy = new class extends FortuneChatLogService
        {
            public array $records = [];

            public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void
            {
                $this->records[] = compact('platform', 'userId', 'role', 'text', 'meta');
            }
        };
        $this->app->instance(FortuneChatLogService::class, $spy);

        $this->takenOver();
        $cm = new FortuneChannelManager(FortuneTellingSetting::getSettings());

        $sent = $cm->sendResponse('facebook', self::FB_UID, ['action' => 'chat_response', 'message' => 'บอทอยากตอบ'], ['from_admin' => true]);

        $this->assertFalse($sent);
        $this->assertSame([], $this->messagingRequests());
        $suppressed = collect($spy->records)->firstWhere('role', 'system');
        $this->assertNotNull($suppressed, 'ต้องจด "บอทงดส่ง" ลงแชทล็อกของแอดมิน');
        $this->assertStringContainsString('บอทอยากตอบ', $suppressed['text']);
    }
}
