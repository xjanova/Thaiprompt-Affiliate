<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneBanService;
use App\Services\FortuneChannelManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ✈️ (2026-09-13) Telegram webhook — ช่องทางที่ 3 ของบอทแม่หมอ
 *
 * สิ่งที่ห้ามหลุด:
 *   1. ไม่มี/ผิดค่าลับ = 403 และไม่ยิงอะไรออกไปเลย (endpoint เปิดสาธารณะ)
 *   2. Telegram ส่ง update ซ้ำ → ประมวลผลครั้งเดียว (ไม่งั้นบิล/คำตอบซ้ำ)
 *   3. แชทกลุ่ม = ไม่ตอบ (ข้อมูลดวง/บิลลูกค้าห้ามโผล่ในกลุ่ม)
 *   4. กดปุ่ม → ตอบรับปุ่ม + ถอดปุ่มที่กดแล้ว + เดินต่อถูกทาง
 *   5. ตัวเรนเดอร์ของ FB ถูกใช้กับ Telegram — ข้อความ+ปุ่มต้องออก Telegram **ไม่ใช่ Facebook**
 *   6. แบนลูกค้า Telegram ได้จริง (enum ถูกขยายแล้ว)
 *
 * @group telegram
 */
class TelegramFortuneWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';

    private const SECRET = 'test-secret-abcdefghijklmnopqrstuvwxyz0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortuneTellingSetting::clearSettingsCache();

        $settings = FortuneTellingSetting::getSettings();
        $settings->telegram_enabled = true;
        $settings->telegram_bot_token = self::TOKEN;
        $settings->telegram_webhook_secret = self::SECRET;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 77]]),
            '*' => Http::response([], 200),
        ]);
    }

    private function postUpdate(array $update, ?string $secret = self::SECRET)
    {
        $headers = $secret === null ? [] : ['X-Telegram-Bot-Api-Secret-Token' => $secret];

        return $this->postJson('/webhook/telegram/fortune', $update, $headers);
    }

    private function textUpdate(int $updateId, string $text, int $chatId = 555, string $chatType = 'private'): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'chat' => ['id' => $chatType === 'private' ? $chatId : -100123, 'type' => $chatType],
                'from' => ['id' => $chatId, 'is_bot' => false, 'first_name' => 'สมศรี'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function telegramCalls(?string $method = null): array
    {
        $calls = [];
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            if (! str_contains($request->url(), 'api.telegram.org')) {
                continue;
            }
            $name = basename(parse_url($request->url(), PHP_URL_PATH));
            if ($method === null || $name === $method) {
                $calls[] = $request->data() + ['__method' => $name];
            }
        }

        return $calls;
    }

    private function facebookCalls(): int
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))
            ->count();
    }

    public function test_rejects_missing_or_wrong_secret_without_side_effects(): void
    {
        $this->postUpdate($this->textUpdate(1, '/start'), null)->assertStatus(403);
        $this->postUpdate($this->textUpdate(2, '/start'), 'wrong-secret')->assertStatus(403);

        $this->assertSame([], $this->telegramCalls());
    }

    public function test_disabled_channel_accepts_but_ignores(): void
    {
        $settings = FortuneTellingSetting::getSettings();
        $settings->telegram_enabled = false;
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        $this->postUpdate($this->textUpdate(3, '/start'))->assertOk();

        $this->assertSame([], $this->telegramCalls());
    }

    public function test_start_greets_with_package_menu_button(): void
    {
        $this->postUpdate($this->textUpdate(4, '/start'))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $this->assertNotEmpty($sent, 'กด Start แล้วต้องมีคำทักทาย — ไม่งั้นลูกค้าเจอความเงียบ');

        $greeting = end($sent);
        $this->assertSame('555', (string) $greeting['chat_id']);
        $this->assertStringContainsString('สมศรี', $greeting['text']);
        $this->assertSame('p|MENU_FORTUNE', $greeting['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_duplicate_update_is_processed_once(): void
    {
        $update = $this->textUpdate(5, '/start');

        $this->postUpdate($update)->assertOk();
        $afterFirst = count($this->telegramCalls('sendMessage'));

        $this->postUpdate($update)->assertOk();

        $this->assertSame($afterFirst, count($this->telegramCalls('sendMessage')));
    }

    public function test_group_chat_is_ignored(): void
    {
        $this->postUpdate($this->textUpdate(6, 'ดูดวง', 555, 'supergroup'))->assertOk();

        $this->assertSame([], $this->telegramCalls());
    }

    private function callbackUpdate(int $updateId, string $data, int $messageId = 42): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'cbq-'.$updateId,
                'from' => ['id' => 555, 'is_bot' => false, 'first_name' => 'สมศรี'],
                'data' => $data,
                'message' => [
                    'message_id' => $messageId,
                    'chat' => ['id' => 555, 'type' => 'private'],
                    'text' => 'ไว้ค่อยดูไหมคะ',
                    'reply_markup' => ['inline_keyboard' => [
                        [['text' => '⏰ ไว้ทีหลัง', 'callback_data' => 'p|VIEW_LATER']],
                        [['text' => '💳 จ่ายด้วยบัตร', 'url' => 'https://main.thaiprompt.online/pay/x']],
                    ]],
                ],
            ],
        ];
    }

    public function test_button_press_is_answered_marked_and_routed(): void
    {
        // ข้อความ 42 = ชุดปุ่มแบบ quick reply ชุดล่าสุด (TelegramFortuneService จดไว้ตอนส่ง)
        Cache::put('tg_last_kb:tg_555', 42, now()->addDay());

        $this->postUpdate($this->callbackUpdate(7, 'p|VIEW_LATER'))->assertOk();

        $this->assertCount(1, $this->telegramCalls('answerCallbackQuery'), 'ต้องปิดวงกลมหมุนบนปุ่มเสมอ');

        $edit = $this->telegramCalls('editMessageText')[0] ?? null;
        $this->assertNotNull($edit, 'ปุ่มที่กดแล้วต้องถูกถอด + โชว์ตัวเลือก');
        $this->assertStringContainsString('👉 ⏰ ไว้ทีหลัง', $edit['text']);
        // ปุ่มลิงก์ (จ่ายเงิน/เปิดเว็บ) ต้องยังกดได้ · ปุ่ม callback ถูกถอด
        $this->assertSame(
            [[['text' => '💳 จ่ายด้วยบัตร', 'url' => 'https://main.thaiprompt.online/pay/x']]],
            $edit['reply_markup']['inline_keyboard']
        );

        $replies = collect($this->telegramCalls('sendMessage'))->pluck('text')->implode("\n");
        $this->assertStringContainsString('ดูคำทำนาย', $replies);
    }

    public function test_spoofed_callback_not_on_the_message_is_ignored(): void
    {
        // client ดัดแปลงยิง callback_data ที่ไม่มีบนปุ่ม — ห้ามวิ่งเข้าสมองแบบ "กดปุ่ม" (ข้าม /aistop + ด่านสแปม)
        $this->postUpdate($this->callbackUpdate(20, 'p|ข้อความอะไรก็ได้ที่อยากให้ AI ตอบ'))->assertOk();

        $this->assertCount(1, $this->telegramCalls('answerCallbackQuery'));
        $this->assertSame([], $this->telegramCalls('sendMessage'));
    }

    public function test_double_tap_on_quick_reply_set_counts_once(): void
    {
        Cache::put('tg_last_kb:tg_555', 42, now()->addDay());

        // client ส่ง 2 callback จากข้อความเดิม (ก่อนปุ่มถูกถอด) — ต้องทำงานครั้งเดียว
        $this->postUpdate($this->callbackUpdate(21, 'p|VIEW_LATER'))->assertOk();
        $this->postUpdate($this->callbackUpdate(22, 'p|VIEW_LATER'))->assertOk();

        $this->assertCount(1, $this->telegramCalls('sendMessage'));
    }

    public function test_template_buttons_stay_and_still_route(): void
    {
        // ข้อความ 99 ไม่ใช่ชุด quick reply (เช่น การ์ดเมนูแพคเกจ/บิล) → ปุ่มคงไว้เหมือนปุ่ม template ของ FB
        Cache::put('tg_last_kb:tg_555', 42, now()->addDay());

        $this->postUpdate($this->callbackUpdate(23, 'p|VIEW_LATER', 99))->assertOk();

        $this->assertSame([], $this->telegramCalls('editMessageText'));
        $this->assertSame([], $this->telegramCalls('editMessageReplyMarkup'));
        $this->assertCount(1, $this->telegramCalls('sendMessage'));
    }

    public function test_fb_renderer_output_is_delivered_through_telegram(): void
    {
        $manager = new FortuneChannelManager(FortuneTellingSetting::getSettings());

        $ok = $manager->sendResponse('telegram', 'tg_555', [
            'action' => 'keyword_matched',
            'message' => 'สวัสดีค่ะ แม่หมอยินดีช่วยดูให้นะคะ',
            'show_quick_replies' => true,
            'quick_replies' => [['title' => '🔮 ดูดวง', 'payload' => 'MENU_FORTUNE']],
        ]);

        $this->assertTrue($ok);
        $this->assertSame(0, $this->facebookCalls(), 'ลูกค้า Telegram ห้ามถูกยิงเข้า Facebook Send API');

        $sent = collect($this->telegramCalls('sendMessage'))->last();
        $this->assertNotNull($sent);
        $this->assertSame('555', (string) $sent['chat_id']);
        $this->assertStringContainsString('แม่หมอยินดีช่วยดูให้', $sent['text']);
    }

    public function test_typed_fortune_request_runs_the_brain_and_answers_on_telegram(): void
    {
        $this->postUpdate($this->textUpdate(10, 'ดูดวง'))->assertOk();

        $this->assertSame(0, $this->facebookCalls(), 'ลูกค้า Telegram ห้ามถูกยิงเข้า Facebook Send API');
        $this->assertNotEmpty(
            array_merge($this->telegramCalls('sendMessage'), $this->telegramCalls('sendPhoto')),
            'พิมพ์ "ดูดวง" แล้วต้องได้คำตอบทาง Telegram'
        );

        // บิล/บทสนทนาที่ถูกสร้าง ต้องผูกช่องทาง telegram — ไม่งั้น cron/job ยิงผิดช่องทาง
        $readings = \App\Models\FortuneReading::where('facebook_user_id', 'tg_555')->get();
        foreach ($readings as $reading) {
            $this->assertSame('telegram', $reading->platform, "reading {$reading->id} ผูกช่องทางผิด");
            $this->assertSame(
                ['platform' => 'telegram', 'user_id' => 'tg_555'],
                \App\Services\Fortune\FortuneRecipient::resolve($reading)
            );
        }
    }

    public function test_banned_telegram_customer_is_told_once_then_ignored(): void
    {
        app(FortuneBanService::class)->ban('telegram', 'tg_555', 60, 'ทดสอบ');

        $this->postUpdate($this->textUpdate(8, 'ดูดวง'))->assertOk();
        $this->postUpdate($this->textUpdate(9, 'ดูดวงหน่อย'))->assertOk();

        // เตือนครั้งแรกครั้งเดียว (cooldown ของ FortuneBanService) — ไม่ประมวลผลข้อความ
        $this->assertCount(1, $this->telegramCalls('sendMessage'));
    }
}
