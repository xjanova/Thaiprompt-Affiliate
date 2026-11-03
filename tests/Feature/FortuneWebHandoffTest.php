<?php

namespace Tests\Feature;

use App\Contracts\FortuneMessengerSender;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneWebHandoff;
use App\Models\LineBotConversation;
use App\Services\FacebookRichMessageService;
use App\Services\Fortune\FortuneBotMode;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebHandoffCopy;
use App\Services\FortuneConversationService;
use App\Services\FortuneWebLinkService;
use App\Services\LineAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🌙 (2026-11-03) T3 — กล่องพาไปห้องแม่หมอ + ชุดข้อความ + sendWebHandoff (FB web-only)
 *
 * ล็อกกติกา:
 *  1. ถ้อยคำทุกบรรทัดไม่มีคำเรื่องเงิน/LINE/ฟรี (FB แบนเพจที่มีสลิป — ห้ามชวนคุยเรื่องเงิน)
 *  2. กล่องมีปุ่ม web_url ปุ่มเดียว ลิงก์ไป /chat/continue/{nonce} พร้อม h/pg ใน token
 *  3. ธง web-only เปิด = มีลิงก์แม้ enable_web_fortune_button ปิด
 *  4. sendWebHandoff สร้าง handoff + บันทึกบทสนทนา + ไม่สร้างบิล
 *  5. สร้างลิงก์ไม่ได้ / service พัง → ข้อความ fail-closed + quick reply WEB_ROOM + แจ้งแอดมิน (ไม่มีบิล)
 *  6. LINE / Telegram ไม่โดน (คืน null)
 */
class FortuneWebHandoffTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '61550000000033';

    private const LINE_UID = 'U0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง memo ก่อน/หลัง — แบบเดียวกับ FortuneTransferModeTest)
     */
    private function settings(array $override = []): FortuneTellingSetting
    {
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge([
            'fortune_bot_mode' => FortuneBotMode::MODE_CLASSIC,
            'facebook_web_only' => false,
            'enable_web_fortune_button' => false,
        ], $override))->save();

        FortuneTellingSetting::clearSettingsCache();

        return FortuneTellingSetting::getSettings();
    }

    private function webOnly(): FortuneTellingSetting
    {
        return $this->settings(['facebook_web_only' => true, 'enable_web_fortune_button' => false]);
    }

    /**
     * ดึง token จาก URL ของปุ่มแล้ว verify
     */
    private function tokenFromUrl(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertNotEmpty($q['tk'] ?? null, 'URL ต้องมี tk');

        $payload = app(FortuneWebLinkService::class)->verifyToken($q['tk']);
        $this->assertIsArray($payload, 'token ต้อง verify ผ่าน');

        return $payload;
    }

    private function historyFor(string $uid): array
    {
        $conv = LineBotConversation::findOrCreateForPlatform($uid, 'facebook', 1440);

        return $conv->messages()->orderBy('id')->get(['role', 'message'])
            ->map(fn ($m) => [$m->role, $m->message])->all();
    }

    // ============================================================
    // 1. ชุดข้อความ
    // ============================================================

    public function test_ถ้อยคำทุกบรรทัดไม่มีคำเรื่องเงิน_line_ฟรี_และยาวไม่เกินเพดาน(): void
    {
        foreach (WebHandoffCopy::allLines() as $key => $line) {
            // คำตอบ (reply_*) พูด "ฟรี" ได้ (เจ้าของสั่ง 2026-11-03) — DM ชวน/ป้ายปุ่มยังห้ามทุกคำ
            $regex = str_starts_with($key, 'reply_') ? WebHandoffCopy::REPLY_FORBIDDEN_REGEX : WebHandoffCopy::FORBIDDEN_REGEX;
            $this->assertDoesNotMatchRegularExpression($regex, $line, "บรรทัด {$key} มีคำต้องห้าม");
            $this->assertDoesNotMatchRegularExpression('#https?://|www\.#i', $line, "บรรทัด {$key} ห้ามมี URL ดิบ");
            $this->assertLessThanOrEqual(WebHandoffCopy::BOX_TEXT_MAX, mb_strlen($line));
        }

        foreach ([WebHandoffCopy::LABEL_WEB_ROOM, WebHandoffCopy::LABEL_WEB_ROOM_ALT, WebHandoffCopy::LABEL_HOWTO,
            WebHandoffCopy::LABEL_SNOOZE, WebHandoffCopy::LABEL_OPTOUT] as $label) {
            $this->assertLessThanOrEqual(20, mb_strlen($label), "ป้ายปุ่ม {$label} ยาวเกิน 20");
        }

        // ข้อความที่ใส่ชื่อแล้วก็ต้องสะอาด
        foreach (range(1, 9) as $n) {
            $this->assertDoesNotMatchRegularExpression(WebHandoffCopy::FORBIDDEN_REGEX, (string) WebHandoffCopy::invite($n, 'สมศรี'));
            $this->assertStringNotContainsString('{name}', (string) WebHandoffCopy::invite($n, null));
        }
        foreach (WebHandoffCopy::KINDS as $kind) {
            $this->assertStringNotContainsString('{name}', WebHandoffCopy::reply($kind));
        }
    }

    public function test_ชื่อใส่เฉพาะชื่อจริงที่ไม่ยาวเกิน_ไม่งั้นใช้คำแทน(): void
    {
        $this->assertSame('🌙 สมศรีคะ', mb_substr(WebHandoffCopy::invite(1, 'สมศรี'), 0, 9));
        $this->assertStringStartsWith('🌙 เจ้าชะตาคะ', WebHandoffCopy::invite(1, 'User'));
        $this->assertStringStartsWith('🌙 เจ้าชะตาคะ', WebHandoffCopy::invite(1, str_repeat('ก', 21)));
        $this->assertStringStartsWith('🌙 เจ้าชะตาคะ', WebHandoffCopy::invite(1, 'https://evil.com'));
        $this->assertStringStartsWith('ไม่ยากเลยค่ะ แตะปุ่ม', WebHandoffCopy::reply(WebHandoffCopy::KIND_CANNOT, null));
        $this->assertStringStartsWith('ไม่ยากเลยค่ะสมชาย แตะปุ่ม', WebHandoffCopy::reply(WebHandoffCopy::KIND_CANNOT, " สมชาย\n"));
        // ชนิดที่ไม่รู้จัก → ข้อความขอดูดวง
        $this->assertSame(WebHandoffCopy::REPLIES[WebHandoffCopy::KIND_READING], WebHandoffCopy::reply('???'));
    }

    public function test_หัวข้อ_เรื่องจ่ายเงินไม่ถูกตีเป็นการเงิน(): void
    {
        $this->assertSame('love', WebHandoffCopy::detectTopic('แฟนเก่าจะกลับมาไหมคะ'));
        $this->assertSame('love', WebHandoffCopy::detectTopic('ปีนี้จะได้แต่งงานไหม'));
        $this->assertSame('career', WebHandoffCopy::detectTopic('ย้ายงานดีไหม'));
        $this->assertSame('money', WebHandoffCopy::detectTopic('การเงินเดือนหน้า'));
        $this->assertSame('kunsai', WebHandoffCopy::detectTopic('ดูคุณไสย'));
        $this->assertSame('health', WebHandoffCopy::detectTopic('สุขภาพแม่'));
        $this->assertSame('general', WebHandoffCopy::detectTopic('ขอเลขบัญชีหน่อย'));
        $this->assertSame('general', WebHandoffCopy::detectTopic('โอนเงินยังไงคะ'));
        $this->assertSame('general', WebHandoffCopy::detectTopic('ดูดวง'));
        $this->assertSame('general', WebHandoffCopy::detectTopic(null));
    }

    public function test_ข้อความชวนตามช่วงเวลาและความต่อเนื่อง(): void
    {
        $evening = Carbon::parse('2026-11-03 20:00', 'Asia/Bangkok');
        $morning = Carbon::parse('2026-11-03 07:00', 'Asia/Bangkok');
        $noon = Carbon::parse('2026-11-03 13:00', 'Asia/Bangkok');

        $this->assertContains(5, WebHandoffCopy::eligibleInviteNumbers($evening));
        $this->assertNotContains(6, WebHandoffCopy::eligibleInviteNumbers($evening));
        $this->assertContains(6, WebHandoffCopy::eligibleInviteNumbers($morning));
        $this->assertNotContains(5, WebHandoffCopy::eligibleInviteNumbers($morning));
        $this->assertSame([1, 2, 4, 7], WebHandoffCopy::eligibleInviteNumbers($noon));
        $this->assertSame([1, 2, 3, 4, 7, 8, 9], WebHandoffCopy::eligibleInviteNumbers($noon, true, 'love'));
        // เวลา UTC ต้องแปลงเป็นเวลาไทยก่อน (13:00 UTC = 20:00 ไทย)
        $this->assertContains(5, WebHandoffCopy::eligibleInviteNumbers(Carbon::parse('2026-11-03 13:00', 'UTC')));

        $this->assertSame(WebHandoffCopy::pickInvite('สมศรี', $noon, false, null, 42), WebHandoffCopy::pickInvite('สมศรี', $noon, false, null, 42));

        $qr = WebHandoffCopy::inviteQuickReplies();
        $this->assertSame(['WEB_ROOM', 'INVITE_SNOOZE_7D', 'INVITE_OPTOUT'], array_column($qr, 'payload'));
        $this->assertNotContains('INVITE_READ_NOW', array_column($qr, 'payload'));
    }

    // ============================================================
    // 2. กล่อง buildWebHandoffBox / getWebFortuneUrl
    // ============================================================

    public function test_กล่องมีปุ่ม_web_url_ปุ่มเดียว_ไป_chat_continue_พร้อม_h_และ_pg(): void
    {
        $rich = new FacebookRichMessageService($this->webOnly());
        $nonce = '0f8fad5b-d9cb-469f-a165-70867728950e';

        $box = $rich->buildWebHandoffBox(self::PSID, WebHandoffCopy::KIND_PRICE, strtoupper($nonce), 7);

        $this->assertIsArray($box, 'ธง web-only เปิด ต้องมีลิงก์แม้ enable_web_fortune_button ปิด');
        $payload = $box['attachment']['payload'];
        $this->assertSame('button', $payload['template_type']);
        $this->assertSame(WebHandoffCopy::REPLIES[WebHandoffCopy::KIND_PRICE], $payload['text']);
        $this->assertCount(1, $payload['buttons']);
        $this->assertSame('web_url', $payload['buttons'][0]['type']);
        $this->assertSame(WebHandoffCopy::LABEL_WEB_ROOM, $payload['buttons'][0]['title']);
        $this->assertStringContainsString('/web-fortune/go?tk=', $payload['buttons'][0]['url']);
        $this->assertStringNotContainsString('line.me', json_encode($box));
        $this->assertStringNotContainsString('TRANSFER_STAY_FB', json_encode($box));

        $token = $this->tokenFromUrl($payload['buttons'][0]['url']);
        $this->assertSame('facebook', $token['p']);
        $this->assertSame(self::PSID, $token['u']);
        $this->assertSame('/chat/continue/'.$nonce, $token['to']);
        $this->assertSame($nonce, $token['h']);
        $this->assertSame(7, $token['pg']);
    }

    public function test_กล่องทำไม่เป็นมีปุ่มเข้ายังไงคะ_และ_nonce_เพี้ยนไป_chat(): void
    {
        $rich = new FacebookRichMessageService($this->webOnly());

        $box = $rich->buildWebHandoffBox(self::PSID, WebHandoffCopy::KIND_CANNOT, 'not-a-uuid', 0, 'สมชาย');
        $buttons = $box['attachment']['payload']['buttons'];

        $this->assertCount(2, $buttons);
        $this->assertSame('postback', $buttons[1]['type']);
        $this->assertSame(WebHandoffCopy::PAYLOAD_WEB_ROOM_HOWTO, $buttons[1]['payload']);
        $this->assertStringContainsString('สมชาย', $box['attachment']['payload']['text']);

        $token = $this->tokenFromUrl($buttons[0]['url']);
        $this->assertSame('/chat', $token['to']);
        $this->assertArrayNotHasKey('h', $token);
        $this->assertArrayNotHasKey('pg', $token);
    }

    public function test_ปิดทั้งสองสวิตช์หรือไม่มี_psid_กล่องคืน_null(): void
    {
        $rich = new FacebookRichMessageService($this->settings(['facebook_web_only' => false, 'enable_web_fortune_button' => false]));
        $this->assertNull($rich->buildWebHandoffBox(self::PSID));
        $this->assertNull($rich->getWebFortuneUrl(self::PSID));

        $rich = new FacebookRichMessageService($this->webOnly());
        $this->assertNull($rich->buildWebHandoffBox(null));
        $this->assertNull($rich->buildWebHandoffBox(''));

        // สวิตช์เดิม (ไม่ใช่ web-only) ยังใช้ได้เหมือนเดิม — caller เดิมไม่เปลี่ยนพฤติกรรม
        $rich = new FacebookRichMessageService($this->settings(['facebook_web_only' => false, 'enable_web_fortune_button' => true]));
        $url = $rich->getWebFortuneUrl(self::PSID);
        $this->assertNotNull($url);
        $this->assertSame('/tarot/free', $this->tokenFromUrl($url)['to']);
    }

    // ============================================================
    // 3. sendWebHandoff
    // ============================================================

    public function test_send_web_handoff_สร้าง_handoff_บันทึกบทสนทนา_ไม่สร้างบิล(): void
    {
        $settings = $this->webOnly();
        // ⚠️ ไม่เรียก setPlatform — currentPlatform = 'line' (ค่าเริ่มต้น) แต่ PSID ตัวเลขต้องถูกมองเป็น FB
        $fcs = new FortuneConversationService($settings);
        $readingsBefore = FortuneReading::count();

        $res = $fcs->sendWebHandoff(self::PSID, 'แฟนจะกลับมาไหมคะ ดูดวง 99 บาท', [
            'kind' => WebHandoffCopy::KIND_READING,
            'source' => 'web_only_gate',
            'page_id' => 5,
        ]);

        $this->assertIsArray($res);
        $this->assertSame('transfer_box', $res['action']);
        $this->assertTrue($res['web_handoff']);
        $this->assertFalse($res['fail_closed']);
        $this->assertNull($res['sent'], 'ไม่ได้ขอ deliver = ให้ ChannelManager ส่ง');
        $this->assertNull($res['reading']);
        $this->assertSame(WebHandoffCopy::REPLIES[WebHandoffCopy::KIND_READING], $res['message']);

        $handoff = FortuneWebHandoff::where('nonce', $res['handoff_nonce'])->firstOrFail();
        $this->assertSame('facebook', $handoff->platform);
        $this->assertSame(self::PSID, $handoff->platform_user_id);
        $this->assertSame(5, $handoff->fortune_page_id);
        $this->assertSame('love', $handoff->intent_topic);
        $this->assertSame('web_only_gate', $handoff->source);
        $this->assertNotNull($handoff->expires_at);
        $this->assertStringContainsString('แฟนจะกลับมาไหมคะ', (string) $handoff->intent_text);
        $this->assertStringNotContainsString('99 บาท', (string) $handoff->intent_text);

        $token = $this->tokenFromUrl($res['fb_template']['attachment']['payload']['buttons'][0]['url']);
        $this->assertSame($handoff->nonce, $token['h']);
        $this->assertSame('/chat/continue/'.$handoff->nonce, $token['to']);
        $this->assertSame(5, $token['pg']);

        $history = $this->historyFor(self::PSID);
        $this->assertSame(['user', 'แฟนจะกลับมาไหมคะ ดูดวง 99 บาท'], $history[0]);
        $this->assertSame(['assistant', $res['message']], $history[1]);

        $this->assertSame($readingsBefore, FortuneReading::count(), 'ห้ามสร้างบิล/คำทำนายบน FB');
    }

    public function test_intent_ตัด_url_เลขยาว_และจำนวนเงิน(): void
    {
        $fcs = new FortuneConversationService($this->webOnly());

        $res = $fcs->sendWebHandoff(self::PSID, 'ดูคุณไสย โอนแล้ว 199 บาท https://evil.example/x เลข 0812345678 ฿50');
        $intent = (string) FortuneWebHandoff::where('nonce', $res['handoff_nonce'])->value('intent_text');

        $this->assertStringContainsString('คุณไสย', $intent);
        $this->assertStringNotContainsString('http', $intent);
        $this->assertStringNotContainsString('0812345678', $intent);
        $this->assertStringNotContainsString('199', $intent);
        $this->assertStringNotContainsString('฿', $intent);
        $this->assertSame('kunsai', FortuneWebHandoff::where('nonce', $res['handoff_nonce'])->value('intent_topic'));
    }

    public function test_ไม่มีข้อความ_เช่นกดปุ่ม_ไม่บันทึกฝั่งลูกค้า(): void
    {
        $fcs = new FortuneConversationService($this->webOnly());

        $res = $fcs->sendWebHandoff(self::PSID, null, ['kind' => WebHandoffCopy::KIND_ROOM, 'source' => 'web_room_postback']);

        $this->assertFalse($res['fail_closed']);
        // 🔐 (รีวิว) กดปุ่ม = ไม่มีหัวข้อ (null) — ห้องเว็บต้องไม่ทักว่า "รับเรื่องที่ลูกทักมาในเพจ"
        $this->assertNull(FortuneWebHandoff::where('nonce', $res['handoff_nonce'])->value('intent_topic'));
        $this->assertNull(FortuneWebHandoff::where('nonce', $res['handoff_nonce'])->value('intent_text'));
        $this->assertSame([['assistant', WebHandoffCopy::REPLIES[WebHandoffCopy::KIND_ROOM]]], $this->historyFor(self::PSID));
    }

    public function test_สร้างลิงก์ไม่ได้_fail_closed_พร้อม_quick_reply_และแจ้งแอดมินครั้งเดียว(): void
    {
        // ปิดทั้งสองสวิตช์ → getWebFortuneUrl คืน null (เหมือนกรณีระบบลิงก์พัง)
        $settings = $this->settings(['facebook_web_only' => false, 'enable_web_fortune_button' => false]);
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->once();
        });

        $fcs = new FortuneConversationService($settings);
        $readingsBefore = FortuneReading::count();

        $res = $fcs->sendWebHandoff(self::PSID, 'ดูดวง');
        $res2 = $fcs->sendWebHandoff(self::PSID, 'ราคาเท่าไหร่');

        foreach ([$res, $res2] as $r) {
            $this->assertTrue($r['fail_closed']);
            $this->assertSame('transfer_box', $r['action']);
            $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $r['message']);
            $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $r['fb_template']['text']);
            $this->assertSame(['WEB_ROOM'], array_column($r['fb_template']['quick_replies'], 'payload'));
            $this->assertArrayNotHasKey('attachment', $r['fb_template']);
        }

        $this->assertSame($readingsBefore, FortuneReading::count(), 'fail-closed ต้องไม่ตกไปออกบิล');
    }

    public function test_service_ลิงก์โยน_exception_ก็_fail_closed(): void
    {
        $settings = $this->webOnly();
        $this->mock(FortuneWebLinkService::class, function ($m) {
            $m->shouldReceive('isEnabledFor')->andReturn(true);
            $m->shouldReceive('generateChatLink')->andThrow(new \RuntimeException('APP_KEY หาย'));
        });
        $this->mock(LineAlertService::class, function ($m) {
            $m->shouldReceive('alertSystemError')->once();
        });

        $rich = new FacebookRichMessageService($settings);
        $this->assertNull($rich->buildWebHandoffBox(self::PSID));

        $res = (new FortuneConversationService($settings))->sendWebHandoff(self::PSID, 'ขอเลขบัญชี', ['kind' => WebHandoffCopy::KIND_PRICE]);

        $this->assertTrue($res['fail_closed']);
        $this->assertSame(WebHandoffCopy::FAIL_CLOSED, $res['fb_template']['text']);
        $this->assertSame(0, FortuneReading::count());
    }

    public function test_line_และ_telegram_ไม่โดน(): void
    {
        $fcs = (new FortuneConversationService($this->webOnly()))->setPlatform('line');

        $this->assertNull($fcs->sendWebHandoff(self::LINE_UID, 'ดูดวง'));
        $this->assertNull($fcs->sendWebHandoff('tg_123456789', 'ดูดวง'));
        $this->assertSame(0, FortuneWebHandoff::count());
    }

    public function test_deliver_ส่งเองด้วย_sender_ที่ส่งมา(): void
    {
        $fcs = new FortuneConversationService($this->webOnly());
        $sender = new class implements FortuneMessengerSender
        {
            public array $calls = [];

            public function sendMessage(string $recipientId, string $message, array $options = []): bool
            {
                $this->calls[] = ['text', $recipientId];

                return true;
            }

            public function sendQuickReplies(string $recipientId, string $message, array $quickReplies, array $options = []): bool
            {
                $this->calls[] = ['qr', $recipientId];

                return true;
            }

            public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null, array $options = []): bool
            {
                return true;
            }

            public function sendButtonTemplate(string $recipientId, array $templatePayload, array $options = []): bool
            {
                $this->calls[] = ['button', $recipientId, $templatePayload];

                return true;
            }

            public function sendGenericTemplate(string $recipientId, array $elements, array $options = []): bool
            {
                return true;
            }

            public function sendAudio(string $recipientId, string $audioUrl, array $options = []): bool
            {
                return true;
            }

            public function sendTypingIndicator(string $recipientId, bool $on = true): void {}

            public function getPlatformName(): string
            {
                return 'facebook';
            }
        };

        $res = $fcs->sendWebHandoff(self::PSID, null, ['kind' => WebHandoffCopy::KIND_ROOM, 'deliver' => true, 'sender' => $sender]);

        $this->assertTrue($res['sent']);
        $this->assertCount(1, $sender->calls);
        $this->assertSame('button', $sender->calls[0][0]);
        $this->assertSame(self::PSID, $sender->calls[0][1]);
        $this->assertSame($res['fb_template'], $sender->calls[0][2]);
    }
}
