<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\TakeoverIngress;
use App\Services\Fortune\TakeoverResumeService;
use App\Services\FortuneBanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🤫 (2026-10-06) webhook ทุกช่องทางระหว่างแอดมินเทคโอเวอร์ — บอทต้องเงียบสนิท
 *
 * ต่อชนิดข้อความ (ข้อความ / "99" / รอโอน / เปิดไพ่ Celtic / ปุ่ม / รูปสลิป / สติกเกอร์ / คนถูกแบน):
 *   - ไม่มีอะไรออกไปหาลูกค้า (ไม่ส่ง ไม่กำลังพิมพ์ ไม่ตอบปุ่มด้วยข้อความ)
 *   - ข้อความขาเข้าถูกจดลงแชทล็อกให้แอดมินเห็น + park ลงบิลที่จ่ายแล้ว
 *   - รูปถูกเก็บถาวร (อาจเป็นสลิป)
 */
#[Group('takeover-silence')]
class TakeoverWebhookSilenceTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const FB_UID = '61550000000077';

    private const LINE_UID = 'U1234567890abcdef1234567890abcdef';

    private const TG_CHAT = 555;

    private const LINE_SECRET = 'line-test-secret';

    private const TG_SECRET = 'test-secret-abcdefghijklmnopqrstuvwxyz0123456789';

    private object $chatLog;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->is_enabled = true;
        $settings->facebook_page_token = 'EAAtestPageToken';
        $settings->line_enabled = true;
        $settings->line_channel_access_token = 'line-test-token';
        $settings->line_channel_secret = self::LINE_SECRET;
        $settings->telegram_enabled = true;
        $settings->telegram_bot_token = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';
        $settings->telegram_webhook_secret = self::TG_SECRET;
        $settings->admin_handover_enabled = true;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $this->chatLog = new class extends FortuneChatLogService
        {
            public array $records = [];

            public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void
            {
                $this->records[] = compact('platform', 'userId', 'role', 'text', 'meta');
            }
        };
        $this->app->instance(FortuneChatLogService::class, $this->chatLog);

        Http::fake([
            'api.telegram.org/bot*/getFile*' => Http::response(['ok' => true, 'result' => ['file_path' => 'photos/a.jpg', 'file_size' => 10]]),
            'api.telegram.org/file/*' => Http::response('JPEGBYTES-TG', 200),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 77]]),
            'api-data.line.me/*' => Http::response('JPEGBYTES-LINE', 200),
            'cdn.example.com/*' => Http::response('JPEGBYTES-FB', 200),
            '*' => Http::response(['message_id' => 'm_1'], 200),
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    // ============================================================
    // ตัวช่วย
    // ============================================================

    private function paidCelticUnderTakeover(string $platform, string $uid): FortuneReading
    {
        return $this->makeReading([
            'platform' => $platform,
            'platform_user_id' => $uid,
            'facebook_user_id' => $uid,
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            'is_paid' => true,
            'amount_paid' => 99,
            'paid_at' => now()->subMinutes(10),
            'bill_reference' => 'FTU-261006-T0001',
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
    }

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

    private function inboundLogged(string $contains): bool
    {
        return collect($this->chatLog->records)
            ->contains(fn ($r) => $r['role'] === 'user' && str_contains((string) $r['text'], $contains));
    }

    private function fbMessage(array $message, string $mid): array
    {
        return [
            'object' => 'page',
            'entry' => [[
                'id' => '100000000000001',
                'time' => time(),
                'messaging' => [[
                    'sender' => ['id' => self::FB_UID],
                    'recipient' => ['id' => '100000000000001'],
                    'timestamp' => time() * 1000,
                    'message' => array_merge(['mid' => $mid], $message),
                ]],
            ]],
        ];
    }

    private function postLine(array $events)
    {
        $body = json_encode(['destination' => 'Ubot', 'events' => $events], JSON_UNESCAPED_UNICODE);
        $sig = base64_encode(hash_hmac('sha256', $body, self::LINE_SECRET, true));

        return $this->call('POST', '/webhook/line/fortune', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body);
    }

    private function lineEvent(string $type, array $extra): array
    {
        return array_merge([
            'type' => $type,
            'replyToken' => 'rt-'.uniqid(),
            'source' => ['type' => 'user', 'userId' => self::LINE_UID],
            'timestamp' => time() * 1000,
            'mode' => 'active',
        ], $extra);
    }

    private function postTelegram(array $update)
    {
        return $this->postJson('/webhook/telegram/fortune', $update, ['X-Telegram-Bot-Api-Secret-Token' => self::TG_SECRET]);
    }

    // ============================================================
    // Facebook
    // ============================================================

    public function test_facebook_text_is_logged_parked_and_bot_stays_silent(): void
    {
        $reading = $this->paidCelticUnderTakeover('facebook', self::FB_UID);

        $this->postJson('/webhook/facebook', $this->fbMessage(['text' => 'น้องหมาตัวนี้ยังมีชีวิตอยู่ไหมคะ'], 'mid.t1'))->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $this->assertTrue($this->inboundLogged('น้องหมาตัวนี้'));
        $fresh = $reading->fresh();
        $this->assertStringContainsString('น้องหมาตัวนี้', (string) $fresh->getConversationState('celtic_parked_context'));
        // Celtic ค้างกลางทาง → พักกล่อง "แม่หมอกลับมาแล้ว เปิดไพ่ต่อได้เลย" ไว้ส่งตอนจบเทคโอเวอร์
        $this->assertArrayHasKey(TakeoverResumeService::ITEM_CELTIC_RESUME, (array) $fresh->getConversationState('takeover_deferred'));
        // ไม่มีการเปิดไพ่/เปลี่ยนสถานะ
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $fresh->conversation_status);
    }

    public function test_facebook_buying_intent_99_does_not_open_a_bill(): void
    {
        $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
        $before = FortuneReading::count();

        $this->postJson('/webhook/facebook', $this->fbMessage(['text' => '99'], 'mid.t2'))->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $this->assertSame($before, FortuneReading::count());
        $this->assertTrue($this->inboundLogged('99'));
    }

    public function test_facebook_pending_payment_text_quick_reply_and_postback_are_silent(): void
    {
        $this->makeReading([
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'bill_reference' => 'FTU-261006-P0001',
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);

        $this->postJson('/webhook/facebook', $this->fbMessage(['text' => 'โอนแล้วค่ะ'], 'mid.t3'))->assertOk();
        $this->postJson('/webhook/facebook', $this->fbMessage([
            'text' => '🔮 ดูดวง 39',
            'quick_reply' => ['payload' => 'DEEP_READING'],
        ], 'mid.t4'))->assertOk();

        $postback = $this->fbMessage([], 'mid.unused');
        unset($postback['entry'][0]['messaging'][0]['message']);
        $postback['entry'][0]['messaging'][0]['postback'] = ['mid' => 'mid.pb1', 'title' => 'ดูดวง 99', 'payload' => 'CELTIC_CROSS'];
        $this->postJson('/webhook/facebook', $postback)->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $this->assertTrue($this->inboundLogged('โอนแล้วค่ะ'));
        $this->assertTrue($this->inboundLogged('[กดปุ่ม]'));
    }

    public function test_facebook_slip_image_is_stored_silently_and_sticker_is_ignored(): void
    {
        $reading = $this->paidCelticUnderTakeover('facebook', self::FB_UID);

        $this->postJson('/webhook/facebook', $this->fbMessage([
            'attachments' => [['type' => 'image', 'payload' => ['url' => 'https://cdn.example.com/slip.jpg']]],
        ], 'mid.i1'))->assertOk();
        $this->postJson('/webhook/facebook', $this->fbMessage([
            'attachments' => [['type' => 'image', 'payload' => ['url' => 'https://cdn.example.com/st.png', 'sticker_id' => 369239263222822]]],
        ], 'mid.s1'))->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $images = (array) $reading->fresh()->getConversationState('takeover_images');
        $this->assertCount(1, $images, 'สลิปต้องถูกเก็บถาวร · สติกเกอร์ไม่ใช่สลิป');
        Storage::disk('local')->assertExists($images[0]['path']);
        // flow สลิปเดิมได้ "สำเนา" (ทางนั้นลบไฟล์หลังหยิบไปตรวจ — ตัวจริงของแอดมินต้องไม่หาย)
        $slipCopy = Cache::get('fortune:pending_slip:facebook:'.self::FB_UID);
        $this->assertNotSame($images[0]['path'], $slipCopy);
        Storage::disk('local')->assertExists($slipCopy);
        $this->assertTrue($this->inboundLogged('ลูกค้าส่งรูป'));
        $this->assertTrue($this->inboundLogged('[สติกเกอร์]'));
    }

    public function test_facebook_banned_user_gets_no_ban_warning_during_takeover(): void
    {
        $this->paidCelticUnderTakeover('facebook', self::FB_UID);
        app(FortuneBanService::class)->ban('facebook', self::FB_UID, 60, 'test');

        $this->postJson('/webhook/facebook', $this->fbMessage(['text' => 'ทำไมไม่ตอบ'], 'mid.b1'))->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $this->assertTrue($this->inboundLogged('ทำไมไม่ตอบ'));
    }

    // ============================================================
    // LINE
    // ============================================================

    public function test_line_text_image_sticker_postback_follow_are_silent(): void
    {
        $reading = $this->paidCelticUnderTakeover('line', self::LINE_UID);

        $this->postLine([
            $this->lineEvent('message', ['message' => ['type' => 'text', 'id' => 'm1', 'text' => 'พร้อมแล้วค่ะ อยากรู้เรื่องงานใหม่']]),
            $this->lineEvent('message', ['message' => ['type' => 'image', 'id' => 'img1']]),
            $this->lineEvent('message', ['message' => ['type' => 'sticker', 'id' => 'st1', 'packageId' => '1', 'stickerId' => '1']]),
            $this->lineEvent('postback', ['postback' => ['data' => 'action=celtic_pick']]),
            $this->lineEvent('follow', []),
        ])->assertOk();

        $this->assertSame([], $this->messagingRequests());
        $fresh = $reading->fresh();
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $fresh->conversation_status);
        $this->assertStringContainsString('อยากรู้เรื่องงานใหม่', (string) $fresh->getConversationState('celtic_parked_context'));
        $this->assertCount(1, (array) $fresh->getConversationState('takeover_images'));
        $this->assertTrue($this->inboundLogged('อยากรู้เรื่องงานใหม่'));
        $this->assertTrue($this->inboundLogged('[สติกเกอร์]'));
        $this->assertTrue($this->inboundLogged('[กดปุ่ม]'));
    }

    // ============================================================
    // Telegram
    // ============================================================

    public function test_telegram_text_start_callback_and_photo_are_silent(): void
    {
        $uid = 'tg_'.self::TG_CHAT;
        $reading = $this->paidCelticUnderTakeover('telegram', $uid);
        $chat = ['id' => self::TG_CHAT, 'type' => 'private'];
        $from = ['id' => self::TG_CHAT, 'is_bot' => false, 'first_name' => 'สมศรี'];

        $this->postTelegram(['update_id' => 9001, 'message' => ['message_id' => 1, 'date' => time(), 'chat' => $chat, 'from' => $from, 'text' => '/start']])->assertOk();
        $this->postTelegram(['update_id' => 9002, 'message' => ['message_id' => 2, 'date' => time(), 'chat' => $chat, 'from' => $from, 'text' => 'แม่หมออยู่ไหมคะ ถามเรื่องย้ายบ้าน']])->assertOk();
        $this->postTelegram(['update_id' => 9003, 'message' => ['message_id' => 3, 'date' => time(), 'chat' => $chat, 'from' => $from,
            'photo' => [['file_id' => 'F1', 'width' => 10, 'height' => 10]]]])->assertOk();
        $this->postTelegram(['update_id' => 9004, 'callback_query' => ['id' => 'cbq1', 'data' => 'p|CELTIC', 'from' => $from,
            'message' => ['message_id' => 50, 'chat' => $chat, 'reply_markup' => ['inline_keyboard' => [[['text' => 'เปิดไพ่', 'callback_data' => 'p|CELTIC']]]]]]])->assertOk();

        $this->assertSame([], $this->messagingRequests());
        // ปิดวงกลมหมุนบนปุ่มได้ (ไม่ใช่ข้อความในแชท)
        $this->assertNotEmpty(Http::recorded(fn (Request $r) => str_contains($r->url(), '/answerCallbackQuery'))->all());
        $fresh = $reading->fresh();
        $this->assertStringContainsString('ย้ายบ้าน', (string) $fresh->getConversationState('celtic_parked_context'));
        $this->assertCount(1, (array) $fresh->getConversationState('takeover_images'));
        $this->assertTrue($this->inboundLogged('ย้ายบ้าน'));
    }

    // ============================================================
    // หลังหมดเวลา: ทักมาครั้งแรก = ปิดเทคโอเวอร์ + ส่งของที่พัก แล้วปล่อย flow ปกติ
    // ============================================================

    public function test_first_inbound_after_expiry_closes_takeover_and_delivers_deferred_once(): void
    {
        $reading = $this->paidCelticUnderTakeover('facebook', self::FB_UID);
        TakeoverResumeService::deferResponse($reading, TakeoverResumeService::ITEM_CELTIC_START, [
            'action' => 'celtic_paid_start',
            'message' => 'ตัดบิลเรียบร้อย เริ่มเปิดไพ่ใบที่ 1 ได้เลยค่ะ',
        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
        $reading->forceFill(['admin_takeover_until' => now()->subMinute()])->save();

        $handled = TakeoverIngress::intercept('facebook', self::FB_UID, ['kind' => 'text', 'text' => 'สวัสดี']);

        $this->assertFalse($handled, 'หมดเวลาแล้ว = ปล่อย flow ปกติ');
        $fresh = $reading->fresh();
        $this->assertNull($fresh->admin_takeover_until);
        $this->assertNull($fresh->getConversationState('takeover_deferred'));
        $sentTexts = collect($this->messagingRequests())->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))->implode("\n");
        $this->assertStringContainsString('เริ่มเปิดไพ่ใบที่ 1', $sentTexts);

        // ทักซ้ำ — ไม่ส่งซ้ำ
        $count = count($this->messagingRequests());
        TakeoverIngress::intercept('facebook', self::FB_UID, ['kind' => 'text', 'text' => 'สวัสดีอีกที']);
        $this->assertCount($count, $this->messagingRequests());
    }
}
