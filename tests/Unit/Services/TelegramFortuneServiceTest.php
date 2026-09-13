<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneMessengerFactory;
use App\Services\Fortune\FortuneRecipient;
use App\Services\TelegramFortuneService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ✈️ (2026-09-13) TelegramFortuneService — แปลงของทรง Messenger เป็นของ Telegram
 *
 * ตัวเรนเดอร์ FB ของ FortuneChannelManager ถูกใช้กับ Telegram ทั้งก้อน ⇒ ถ้าตัวแปลงพัง
 * ลูกค้า Telegram ไม่ได้ปุ่มเลือกแพคเกจ/ปุ่มจ่ายเงิน/การ์ด — เทสต์นี้กันจุดนั้น + ความปลอดภัยของ token
 *
 * ไม่แตะฐานข้อมูล (settings สร้างในหน่วยความจำ)
 *
 * @group telegram
 */
class TelegramFortuneServiceTest extends TestCase
{
    private const TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function service(): TelegramFortuneService
    {
        $settings = new FortuneTellingSetting;
        $settings->telegram_bot_token = self::TOKEN;
        $settings->telegram_enabled = true;

        return new TelegramFortuneService($settings);
    }

    private function fakeOk(int $messageId = 10): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => $messageId]]),
        ]);
    }

    /**
     * @return array<int, array{method:string,data:array}>
     */
    private function telegramCalls(): array
    {
        $calls = [];
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            if (! str_contains($request->url(), 'api.telegram.org')) {
                continue;
            }
            $calls[] = [
                'method' => basename(parse_url($request->url(), PHP_URL_PATH)),
                'data' => $request->data(),
            ];
        }

        return $calls;
    }

    public function test_send_message_strips_prefix_and_disables_link_preview(): void
    {
        $this->fakeOk();

        $this->assertTrue($this->service()->sendMessage('tg_555', 'สวัสดีค่ะ https://s.lazada.co.th/x'));

        $calls = $this->telegramCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('sendMessage', $calls[0]['method']);
        $this->assertSame('555', (string) $calls[0]['data']['chat_id']);
        $this->assertTrue($calls[0]['data']['link_preview_options']['is_disabled']);
        $this->assertArrayNotHasKey('parse_mode', $calls[0]['data'], 'ข้อความล้วน — ห้ามเปิด parse_mode (ข้อความ AI มีอักขระพิเศษ)');
    }

    public function test_misrouted_recipient_never_calls_telegram(): void
    {
        $this->fakeOk();
        $service = $this->service();

        // PSID ของ FB (ตัวเลขล้วน) / LINE uid — ต้องไม่ถูกยิงเข้า Telegram
        $this->assertFalse($service->sendMessage('26273302092329161', 'x'));
        $this->assertFalse($service->sendMessage('U'.str_repeat('a', 32), 'x'));
        $this->assertFalse($service->sendImage('26273302092329161', 'https://example.com/a.png'));

        $this->assertSame([], $this->telegramCalls());
    }

    public function test_quick_replies_become_inline_buttons_and_long_payload_round_trips(): void
    {
        $this->fakeOk();
        $service = $this->service();

        $longThai = 'ดูดวงความรัก เนื้อคู่ คู่ครอง ความสัมพันธ์ในอนาคต';
        $service->sendQuickReplies('tg_555', 'เลือกได้เลยค่ะ', [
            ['title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE'],
            // ทรง LINE (label/text) ต้องใช้ได้ด้วย — ตัวเรนเดอร์บางจุดประกอบปุ่มทรงนี้
            ['label' => '💕 ความรัก', 'text' => $longThai],
        ]);

        $call = $this->telegramCalls()[0];
        $keyboard = $call['data']['reply_markup']['inline_keyboard'];
        $buttons = array_merge(...$keyboard);

        $this->assertSame('🔮 ดูดวง', $buttons[0]['text']);
        $this->assertSame('p|MENU_FORTUNE', $buttons[0]['callback_data']);

        // payload ภาษาไทยยาวเกิน 64 ไบต์ → เก็บ cache ส่งแค่ hash แล้วถอดกลับได้ครบ
        $this->assertStringStartsWith('h|', $buttons[1]['callback_data']);
        $this->assertLessThanOrEqual(64, strlen($buttons[1]['callback_data']));
        $this->assertSame($longThai, $service->decodeCallback($buttons[1]['callback_data']));
        $this->assertSame('MENU_FORTUNE', $service->decodeCallback('p|MENU_FORTUNE'));

        // hash ที่ cache หาย (ข้าม deploy) → null ให้ webhook ใช้ป้ายปุ่มแทน
        Cache::flush();
        $this->assertNull($service->decodeCallback($buttons[1]['callback_data']));
    }

    public function test_button_template_keeps_url_buttons_and_maps_postbacks(): void
    {
        $this->fakeOk();

        $this->service()->sendButtonTemplate('tg_555', [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => '💳 ชำระค่าครู 39 บาท',
                    'buttons' => [
                        ['type' => 'web_url', 'url' => 'https://main.thaiprompt.online/pay/abc', 'title' => '💳 จ่ายด้วยบัตร'],
                        ['type' => 'postback', 'title' => '✅ แจ้งโอนแล้ว', 'payload' => 'REPORT_PAYMENT'],
                        // scheme อื่นนอกจาก http(s) Telegram ปฏิเสธทั้งข้อความ → ต้องทิ้งปุ่มนั้น
                        ['type' => 'web_url', 'url' => 'tel:0812345678', 'title' => 'โทร'],
                    ],
                ],
            ],
        ]);

        $call = $this->telegramCalls()[0];
        $this->assertSame('💳 ชำระค่าครู 39 บาท', $call['data']['text']);
        $rows = $call['data']['reply_markup']['inline_keyboard'];
        $this->assertCount(2, $rows);
        $this->assertSame('https://main.thaiprompt.online/pay/abc', $rows[0][0]['url']);
        $this->assertSame('p|REPORT_PAYMENT', $rows[1][0]['callback_data']);
    }

    public function test_generic_template_with_image_becomes_photo_with_caption_and_buttons(): void
    {
        $this->fakeOk();

        $this->service()->sendGenericTemplate('tg_555', [[
            'title' => 'ไพ่ The Star',
            'subtitle' => 'ความหวังกำลังมา',
            'image_url' => 'https://cdn.example.com/star.jpg',
            'buttons' => [['type' => 'postback', 'title' => '🃏 เปิดใบถัดไป', 'payload' => 'CELTIC_READY']],
        ]]);

        $call = $this->telegramCalls()[0];
        $this->assertSame('sendPhoto', $call['method']);
        $this->assertSame('https://cdn.example.com/star.jpg', $call['data']['photo']);
        $this->assertSame("ไพ่ The Star\nความหวังกำลังมา", $call['data']['caption']);
        $this->assertSame('p|CELTIC_READY', $call['data']['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_long_message_is_split_and_keyboard_rides_on_last_chunk(): void
    {
        $this->fakeOk();

        $paragraph = str_repeat('แม่หมอเห็นดวงของลูกช่วงนี้มีเกณฑ์ดีมาก ', 40); // ~1,600 ตัวต่อย่อหน้า
        $message = implode("\n\n", array_fill(0, 5, trim($paragraph)));

        $this->service()->sendQuickReplies('tg_555', $message, [['title' => 'ถามต่อ', 'payload' => 'CELTIC_CONTINUE']]);

        $calls = $this->telegramCalls();
        $this->assertGreaterThan(1, count($calls));

        foreach ($calls as $i => $call) {
            $this->assertLessThanOrEqual(4096, $this->service()->utf16Length($call['data']['text']), "ท่อน {$i} ยาวเกินเพดาน Telegram");
            $isLast = $i === count($calls) - 1;
            $this->assertSame($isLast, isset($call['data']['reply_markup']), 'ปุ่มต้องอยู่ท่อนสุดท้ายเท่านั้น');
        }
    }

    public function test_split_never_cuts_through_thai_graphemes(): void
    {
        $service = $this->service();
        // ไม่มีช่องว่างเลย — บังคับให้ไปทางตัดแข็ง
        $text = str_repeat('กิ่งไม้ใบหญ้าน้ำค้าง', 400);

        $chunks = $service->splitMessage($text, 500);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame($text, implode('', $chunks), 'ตัดแล้วต่อกลับต้องได้ข้อความเดิมทุกตัวอักษร');
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(500, $service->utf16Length($chunk));
            $this->assertDoesNotMatchRegularExpression('/^\p{M}/u', $chunk, 'ท่อนใหม่ห้ามขึ้นต้นด้วยสระ/วรรณยุกต์ (ผ่ากลางตัวอักษร)');
        }
    }

    public function test_utf16_length_counts_emoji_as_two_units(): void
    {
        $service = $this->service();

        $this->assertSame(3, $service->utf16Length('ดวง'));
        $this->assertSame(2, $service->utf16Length('🔮'));
    }

    public function test_new_quick_reply_set_clears_previous_keyboard(): void
    {
        Http::fakeSequence('api.telegram.org/*')
            ->push(['ok' => true, 'result' => ['message_id' => 101]])
            ->push(['ok' => true, 'result' => ['message_id' => 102]])
            ->push(['ok' => true, 'result' => true]);

        $service = $this->service();
        $service->sendQuickReplies('tg_555', 'ชุดแรก', [['title' => 'ก', 'payload' => 'A']]);
        $service->sendQuickReplies('tg_555', 'ชุดสอง', [['title' => 'ข', 'payload' => 'B']]);

        $calls = $this->telegramCalls();
        $edit = collect($calls)->firstWhere('method', 'editMessageReplyMarkup');

        $this->assertNotNull($edit, 'ปุ่มชุดเก่าต้องถูกถอด (เหมือน quick reply ของ FB ที่หายเมื่อมีชุดใหม่)');
        $this->assertSame(101, (int) $edit['data']['message_id']);
        $this->assertSame([], $edit['data']['reply_markup']['inline_keyboard']);
    }

    public function test_blocked_by_user_is_remembered_and_stops_remaining_chunks(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was blocked by the user'], 403),
        ]);

        $service = $this->service();
        $message = implode("\n\n", array_fill(0, 3, str_repeat('ก', 3000)));

        $this->assertFalse($service->sendMessage('tg_555', $message));
        $this->assertTrue($service->isBlocked('tg_555'));
        $this->assertCount(1, $this->telegramCalls(), 'บล็อกแล้วห้ามยิงท่อนที่เหลือ');
    }

    public function test_bot_token_never_reaches_the_log(): void
    {
        Http::fake(function () {
            // Guzzle ใส่ URL เต็ม (มี token) ไว้ในข้อความ error
            throw new ConnectionException('cURL error 28: timed out for https://api.telegram.org/bot'.self::TOKEN.'/sendMessage');
        });

        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context, JSON_UNESCAPED_UNICODE);
        });

        $this->assertFalse($this->service()->sendMessage('tg_555', 'x'));

        $this->assertNotEmpty($logged);
        foreach ($logged as $line) {
            $this->assertStringNotContainsString(self::TOKEN, $line);
            $this->assertStringNotContainsString('AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ', $line);
        }
    }

    public function test_not_configured_service_never_calls_api(): void
    {
        Http::fake();

        $service = new TelegramFortuneService(new FortuneTellingSetting);

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->sendMessage('tg_555', 'x'));
        Http::assertNothingSent();
    }

    public function test_remember_profile_never_uses_username_as_name(): void
    {
        $service = $this->service();

        $withName = $service->rememberProfile('tg_1', ['first_name' => 'สมศรี', 'last_name' => 'ใจดี', 'username' => 'somsri99']);
        $this->assertSame('สมศรี ใจดี', $withName['name']);

        // มีแต่ @username → ห้ามเรียกลูกค้าว่า "somsri99" (FCM หาชื่อจากประวัติเอง)
        $onlyUsername = $service->rememberProfile('tg_2', ['username' => 'somsri99']);
        $this->assertNull($onlyUsername['name']);
        $this->assertSame($onlyUsername, $service->getUserProfile('tg_2'));
    }

    public function test_factory_routes_by_platform_and_id_shape(): void
    {
        $settings = new FortuneTellingSetting;

        $this->assertInstanceOf(TelegramFortuneService::class, FortuneMessengerFactory::sender('telegram', 'tg_9', $settings));
        // payload job เก่าที่ถือ platform='facebook' แต่ id เป็นทรง Telegram → id ชนะ
        $this->assertInstanceOf(TelegramFortuneService::class, FortuneMessengerFactory::sender('facebook', 'tg_9', $settings));
        $this->assertNull(FortuneMessengerFactory::sender('line', 'U'.str_repeat('b', 32), $settings));

        $this->assertSame(FortuneRecipient::PLATFORM_FACEBOOK, FortuneMessengerFactory::resolvePlatform('telegram', '26273302092329161'));
        $this->assertSame(FortuneRecipient::PLATFORM_TELEGRAM, FortuneMessengerFactory::resolvePlatform('telegram'));
    }
}
