<?php

namespace Tests\Feature;

use App\Models\AiBotProfile;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\ChatbotAutoContentPost;
use App\Models\ChatbotPlatformIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 📣 (2026-09-14) โพสต์คอนเทนต์อัตโนมัติของบอทเช่า — ต้องส่งจริง หรือบอกตรง ๆ ว่าไม่สำเร็จ
 *
 * เดิม postToLine/Facebook/Instagram/Telegram/Discord เป็นตัวหลอก: เขียน log แล้วตอบ "สำเร็จ"
 * ทั้งที่ไม่ได้ส่งอะไรออกไป → โพสต์ถูกมาร์ก posted และตั้งรอบถัดไปต่อเรื่อย ๆ
 *
 * สิ่งที่ห้ามหลุด:
 *   1. ส่งจริงผ่าน API ของแต่ละแพลตฟอร์ม และมาร์ก posted เฉพาะเมื่อสำเร็จจริง
 *   2. API ปฏิเสธ / หมดเวลา → failed + ข้อความภาษาไทย ไม่ใช่ exception ดิบ
 *   3. token ไม่หลุดไปใน log, error_message หรือ response (Telegram ฝัง token ไว้ใน URL)
 *   4. Instagram ยังไม่รองรับ = บอกว่าไม่รองรับ ไม่ใช่ "สำเร็จ"
 *   5. กดโพสต์ซ้ำระหว่างกำลังส่ง = ไม่ส่งซ้ำ
 *   6. token เก็บในฐานข้อมูลแบบเข้ารหัส
 *
 * @group chatbot
 */
class ChatbotAutoContentPostingTest extends TestCase
{
    use RefreshDatabase;

    private const TELEGRAM_TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsawZ';

    private const TELEGRAM_CHAT = '-1001234567890';

    // ห้ามแต่งให้หน้าตาเหมือนโทเค็น Discord จริง (MTI…xxxxxx.xxx) — GitHub push protection บล็อกทั้ง push
    private const DISCORD_TOKEN = 'test_only.not_a_real_discord_token.0123456789abcdefghijklmnop';

    private const DISCORD_CHANNEL = '112233445566778899';

    private const LINE_TOKEN = 'line-channel-access-token-abcdefghijklmnopqrstuvwxyz0123';

    private const FB_PAGE = '1234567890123';

    private const FB_TOKEN = 'EAAGpageaccesstokenabcdefghijklmnopqrstuvwxyz';

    private User $owner;

    private AiBotProfile $bot;

    /** @var list<string> ทุกบรรทัด log ระหว่างเทสต์ (ข้อความ + context) */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $provider = AiProvider::create([
            'name' => 'openai',
            'display_name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'is_active' => true,
            'config' => ['api_key' => 'sk-test-owner-key-abcdefghijklmnop'],
        ]);
        $model = AiModel::create([
            'provider_id' => $provider->id,
            'model_identifier' => 'gpt-4o-mini',
            'display_name' => 'GPT-4o mini',
            'is_active' => true,
        ]);
        $this->bot = AiBotProfile::create([
            'owner_id' => $this->owner->id,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'name' => 'บอทร้านกาแฟ',
        ]);

        Sanctum::actingAs($this->owner);

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $this->logged[] = $event->message.' '.json_encode($event->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
    }

    private function integration(string $platform, array $attributes = []): ChatbotPlatformIntegration
    {
        return ChatbotPlatformIntegration::create(array_merge([
            'bot_profile_id' => $this->bot->id,
            'platform_type' => $platform,
            'is_active' => true,
            'is_verified' => true,
            'can_post_content' => true,
        ], $attributes));
    }

    private function telegram(array $attributes = []): ChatbotPlatformIntegration
    {
        return $this->integration('telegram', array_merge([
            'telegram_bot_token' => self::TELEGRAM_TOKEN,
            'platform_settings' => ['post_chat_id' => self::TELEGRAM_CHAT],
        ], $attributes));
    }

    private function discord(array $attributes = []): ChatbotPlatformIntegration
    {
        return $this->integration('discord', array_merge([
            'discord_bot_token' => self::DISCORD_TOKEN,
            'discord_client_id' => '123456789012345678',
            'platform_settings' => ['post_channel_id' => self::DISCORD_CHANNEL],
        ], $attributes));
    }

    private function line(): ChatbotPlatformIntegration
    {
        return $this->integration('line', [
            'line_channel_id' => '1650000000',
            'line_channel_secret' => 'line-secret-0123456789',
            'line_channel_access_token' => self::LINE_TOKEN,
        ]);
    }

    private function facebook(): ChatbotPlatformIntegration
    {
        return $this->integration('facebook', [
            'fb_page_id' => self::FB_PAGE,
            'access_token' => self::FB_TOKEN,
        ]);
    }

    private function contentPost(array $platforms, array $attributes = []): ChatbotAutoContentPost
    {
        return ChatbotAutoContentPost::create(array_merge([
            'bot_profile_id' => $this->bot->id,
            'content_prompt' => 'แนะนำโปรโมชันประจำสัปดาห์',
            'generated_content' => 'โปรโมชันสัปดาห์นี้ ลาเต้เย็นลด 20% ทุกสาขา #โปรโมชัน',
            'target_platforms' => $platforms,
            'frequency' => 'once',
            'status' => 'draft',
        ], $attributes));
    }

    private function postNow(ChatbotAutoContentPost $post)
    {
        return $this->postJson("/api/v1/chatbot/bots/{$this->bot->id}/auto-content/{$post->id}/post-now");
    }

    /**
     * token ต้องไม่โผล่ในทุกช่องที่ผู้ใช้/ผู้ดูแลมองเห็น
     */
    private function assertSecretNeverLeaked(string $secret, string $responseBody, ChatbotAutoContentPost $post): void
    {
        $post->refresh();

        $this->assertStringNotContainsString($secret, $responseBody, 'token หลุดไปใน response');
        $this->assertStringNotContainsString($secret, (string) $post->error_message, 'token หลุดไปใน error_message');
        $this->assertStringNotContainsString($secret, json_encode($post->post_results, JSON_UNESCAPED_SLASHES), 'token หลุดไปใน post_results');
        $this->assertNotEmpty($this->logged, 'ควรมี log บอกเหตุที่ส่งไม่ได้');
        foreach ($this->logged as $line) {
            $this->assertStringNotContainsString($secret, $line, 'token หลุดไปใน log');
        }
    }

    // ── Telegram ───────────────────────────────────────────────────────────

    public function test_telegram_sends_the_real_message_once_and_marks_the_post_posted(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);
        $this->telegram();
        // เลือกซ้ำ = ต้องส่งครั้งเดียว ไม่ใช่สองรอบ
        $post = $this->contentPost(['telegram', 'telegram']);

        $this->postNow($post)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.results.telegram.message_ids', [42]);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bot'.self::TELEGRAM_TOKEN.'/sendMessage'
            && $request['chat_id'] === self::TELEGRAM_CHAT
            && $request['text'] === $post->generated_content
            && ! isset($request['parse_mode']));

        $post->refresh();
        $this->assertSame('posted', $post->status);
        $this->assertNotNull($post->posted_at);
        $this->assertNull($post->error_message);
    }

    public function test_telegram_splits_content_longer_than_one_message(): void
    {
        Http::fake(['api.telegram.org/*' => Http::sequence()
            ->push(['ok' => true, 'result' => ['message_id' => 1]])
            ->push(['ok' => true, 'result' => ['message_id' => 2]])]);
        $this->telegram();
        $paragraph = str_repeat('ก', 3000);
        $post = $this->contentPost(['telegram'], ['generated_content' => $paragraph."\n\n".$paragraph]);

        $this->postNow($post)->assertOk()->assertJsonPath('data.results.telegram.message_ids', [1, 2]);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request['text'] === $paragraph);
    }

    public function test_telegram_sends_a_photo_with_caption_when_the_post_has_an_image(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 7]])]);
        $this->telegram();
        $post = $this->contentPost(['telegram'], ['generated_media' => ['https://cdn.example.com/latte.jpg']]);

        $this->postNow($post)->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendPhoto')
            && $request['photo'] === 'https://cdn.example.com/latte.jpg'
            && $request['caption'] === $post->generated_content);
    }

    public function test_telegram_rejection_fails_the_post_with_a_thai_reason(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 400, 'description' => 'Bad Request: chat not found'], 400)]);
        $this->telegram();
        $post = $this->contentPost(['telegram']);

        $response = $this->postNow($post)->assertStatus(422)->assertJsonPath('success', false);

        $this->assertStringContainsString('ไม่พบแชทปลายทาง', $response->json('message'));
        $post->refresh();
        $this->assertSame('failed', $post->status);
        $this->assertStringContainsString('Telegram — ไม่พบแชทปลายทาง', $post->error_message);
        $this->assertFalse($post->post_results['telegram']['success']);
        $this->assertSame(400, $post->post_results['telegram']['status']);
    }

    public function test_telegram_timeout_is_reported_in_thai_without_leaking_the_token(): void
    {
        Http::fake(function (Request $request) {
            // Guzzle ใส่ URL เต็มไว้ในข้อความ error — URL ของ Telegram มี token อยู่ในนั้น
            throw new ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds for '.$request->url());
        });
        $this->telegram();
        $post = $this->contentPost(['telegram']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('ไม่สำเร็จหรือหมดเวลา', $response->json('message'));
        $this->assertSame('timeout', $response->json('data.results.telegram.error_code'));
        $this->assertSame('failed', $post->fresh()->status);
        $this->assertSecretNeverLeaked(self::TELEGRAM_TOKEN, $response->getContent(), $post);
    }

    public function test_telegram_without_a_target_chat_fails_before_calling_the_api(): void
    {
        Http::fake();
        $this->telegram(['platform_settings' => null]);
        $post = $this->contentPost(['telegram']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('chat_id', $response->json('message'));
        Http::assertNothingSent();
    }

    // ── Discord ────────────────────────────────────────────────────────────

    public function test_discord_posts_to_the_configured_channel_without_pinging_everyone(): void
    {
        Http::fake(['discord.com/*' => Http::response(['id' => '998877665544332211'])]);
        $this->discord();
        $post = $this->contentPost(['discord'], ['generated_content' => '@everyone เมนูใหม่มาแล้ว']);

        $this->postNow($post)->assertOk()->assertJsonPath('data.results.discord.message_ids', ['998877665544332211']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://discord.com/api/v10/channels/'.self::DISCORD_CHANNEL.'/messages'
            && $request->hasHeader('Authorization', 'Bot '.self::DISCORD_TOKEN)
            && $request['content'] === '@everyone เมนูใหม่มาแล้ว'
            && $request['allowed_mentions'] === ['parse' => []]);
        $this->assertSame('posted', $post->fresh()->status);
    }

    public function test_discord_channel_set_on_the_post_overrides_the_integration_default(): void
    {
        Http::fake(['discord.com/*' => Http::response(['id' => '1'])]);
        $this->discord();
        $post = $this->contentPost(['discord'], ['platform_specific_config' => ['discord' => ['channel_id' => '223344556677889900']]]);

        $this->postNow($post)->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/channels/223344556677889900/messages'));
    }

    public function test_discord_splits_messages_over_the_2000_character_limit(): void
    {
        Http::fake(['discord.com/*' => Http::response(['id' => '1'])]);
        $this->discord();
        $post = $this->contentPost(['discord'], ['generated_content' => str_repeat('ข', 1500)."\n".str_repeat('ค', 1500)]);

        $this->postNow($post)->assertOk();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => mb_strlen($request['content']) <= 2000);
    }

    public function test_discord_without_a_channel_fails_before_calling_the_api(): void
    {
        Http::fake();
        $this->discord(['platform_settings' => null]);
        $post = $this->contentPost(['discord']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('ยังไม่ได้ตั้งห้องปลายทางของ Discord', $response->json('message'));
        Http::assertNothingSent();
        $this->assertSame('failed', $post->fresh()->status);
    }

    public function test_discord_missing_permission_is_explained_in_thai(): void
    {
        Http::fake(['discord.com/*' => Http::response(['message' => 'Missing Permissions', 'code' => 50013], 403)]);
        $this->discord();
        $post = $this->contentPost(['discord']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('บอทไม่มีสิทธิ์ส่งข้อความในห้องนี้', $response->json('message'));
        $this->assertStringNotContainsString('Missing Permissions', $response->json('message'));
    }

    public function test_discord_timeout_is_reported_without_leaking_the_token(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out (Authorization: Bot '.self::DISCORD_TOKEN.')');
        });
        $this->discord();
        $post = $this->contentPost(['discord']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('Discord ไม่สำเร็จหรือหมดเวลา', $response->json('message'));
        $this->assertSecretNeverLeaked(self::DISCORD_TOKEN, $response->getContent(), $post);
    }

    // ── LINE ───────────────────────────────────────────────────────────────

    public function test_line_broadcasts_the_content(): void
    {
        Http::fake(['api.line.me/*' => Http::response([], 200, ['X-Line-Request-Id' => 'req-123'])]);
        $this->line();
        $post = $this->contentPost(['line']);

        $this->postNow($post)->assertOk()->assertJsonPath('data.results.line.request_id', 'req-123');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.line.me/v2/bot/message/broadcast'
            && $request->hasHeader('Authorization', 'Bearer '.self::LINE_TOKEN)
            && $request['messages'] === [['type' => 'text', 'text' => $post->generated_content]]);
        $this->assertSame('posted', $post->fresh()->status);
    }

    public function test_line_quota_exhaustion_fails_with_a_thai_reason(): void
    {
        Http::fake(['api.line.me/*' => Http::response(['message' => 'You have reached your monthly limit.'], 429)]);
        $this->line();
        $post = $this->contentPost(['line']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('โควตาข้อความ LINE', $response->json('message'));
        $this->assertSame('failed', $post->fresh()->status);
    }

    public function test_line_timeout_is_reported_without_leaking_the_token(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out (Bearer '.self::LINE_TOKEN.')');
        });
        $this->line();
        $post = $this->contentPost(['line']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('LINE ไม่สำเร็จหรือหมดเวลา', $response->json('message'));
        $this->assertSecretNeverLeaked(self::LINE_TOKEN, $response->getContent(), $post);
    }

    // ── Facebook / Instagram ───────────────────────────────────────────────

    public function test_facebook_posts_to_the_page_feed_with_the_token_in_the_body_not_the_url(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => self::FB_PAGE.'_555'])]);
        $this->facebook();
        $post = $this->contentPost(['facebook']);

        $this->postNow($post)->assertOk()->assertJsonPath('data.results.facebook.platform_post_id', self::FB_PAGE.'_555');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/'.self::FB_PAGE.'/feed'
            && $request['message'] === $post->generated_content
            && $request['access_token'] === self::FB_TOKEN);
    }

    public function test_facebook_expired_token_fails_with_a_thai_reason(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Error validating access token', 'code' => 190]], 400)]);
        $this->facebook();
        $post = $this->contentPost(['facebook']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('Page Access Token ของ Facebook หมดอายุ', $response->json('message'));
    }

    public function test_facebook_timeout_is_reported_in_thai(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        $this->facebook();
        $post = $this->contentPost(['facebook']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('Facebook ไม่สำเร็จหรือหมดเวลา', $response->json('message'));
        $this->assertSecretNeverLeaked(self::FB_TOKEN, $response->getContent(), $post);
    }

    public function test_instagram_is_reported_as_not_supported_instead_of_a_fake_success(): void
    {
        Http::fake();
        $this->integration('instagram', ['fb_page_id' => self::FB_PAGE, 'access_token' => self::FB_TOKEN]);
        $post = $this->contentPost(['instagram']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('ยังไม่รองรับการโพสต์อัตโนมัติไป Instagram', $response->json('message'));
        $this->assertSame('not_supported', $response->json('data.results.instagram.error_code'));
        $this->assertSame('failed', $post->fresh()->status);
        Http::assertNothingSent();
    }

    // ── ภาพรวมของโพสต์ ──────────────────────────────────────────────────────

    public function test_partial_success_is_failed_but_names_what_was_already_posted(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]]),
            'discord.com/*' => Http::response(['message' => 'Unknown Channel', 'code' => 10003], 404),
        ]);
        $this->telegram();
        $this->discord();
        $post = $this->contentPost(['telegram', 'discord']);

        $response = $this->postNow($post)->assertStatus(422);

        $message = $response->json('message');
        $this->assertStringContainsString('Discord — ไม่พบห้อง Discord ปลายทาง', $message);
        $this->assertStringContainsString('โพสต์สำเร็จแล้ว: Telegram', $message);
        $post->refresh();
        $this->assertSame('failed', $post->status);
        $this->assertTrue($post->post_results['telegram']['success']);
        $this->assertFalse($post->post_results['discord']['success']);
    }

    public function test_a_platform_without_an_integration_is_refused_in_thai(): void
    {
        Http::fake();
        $post = $this->contentPost(['discord']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('ยังไม่ได้เชื่อมต่อ Discord', $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_an_integration_without_posting_permission_is_refused(): void
    {
        Http::fake();
        $this->telegram(['can_post_content' => false]);
        $post = $this->contentPost(['telegram']);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('ยังไม่ได้เปิดสิทธิ์', $response->json('message'));
        Http::assertNothingSent();
    }

    public function test_a_post_with_no_target_platforms_is_not_reported_as_posted(): void
    {
        Http::fake();
        $post = $this->contentPost([]);

        $this->postNow($post)->assertStatus(422)->assertJsonPath('message', 'ยังไม่ได้เลือกแพลตฟอร์มที่จะโพสต์');

        $this->assertSame('failed', $post->fresh()->status);
    }

    public function test_pressing_post_again_while_it_is_still_sending_does_not_send_twice(): void
    {
        Http::fake();
        $this->telegram();
        $post = $this->contentPost(['telegram'], ['status' => 'processing']);

        $this->postNow($post)->assertStatus(409)->assertJsonPath('success', false);

        Http::assertNothingSent();
        $this->assertSame('processing', $post->fresh()->status);
    }

    public function test_a_post_stuck_in_processing_can_be_sent_again_after_it_goes_stale(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 3]])]);
        $this->telegram();
        $post = $this->contentPost(['telegram'], ['status' => 'processing']);
        DB::table('chatbot_auto_content_posts')->where('id', $post->id)->update([
            'updated_at' => now()->subMinutes(ChatbotAutoContentPost::PROCESSING_STALE_MINUTES + 1),
        ]);

        $this->postNow($post)->assertOk();

        $this->assertSame('posted', $post->fresh()->status);
    }

    public function test_ai_generation_failure_returns_thai_text_not_the_provider_error(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'error' => ['message' => 'Incorrect API key provided: sk-proj-leakedsecret1234567890', 'type' => 'invalid_request_error'],
        ], 401)]);
        $post = $this->contentPost(['telegram'], ['generated_content' => null]);

        $response = $this->postNow($post)->assertStatus(422);

        $this->assertStringContainsString('สร้างคอนเทนต์ด้วย AI ไม่สำเร็จ', $response->json('message'));
        $this->assertStringNotContainsString('sk-proj', $response->getContent());
        $this->assertSame('failed', $post->fresh()->status);
        foreach ($this->logged as $line) {
            $this->assertStringNotContainsString('leakedsecret', $line);
        }
    }

    public function test_ai_generated_content_is_saved_and_then_posted(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'กาแฟใหม่หอมกรุ่น #กาแฟ #โปรโมชัน'], 'finish_reason' => 'stop']],
                'usage' => ['total_tokens' => 120],
            ]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 11]]),
        ]);
        $this->telegram();
        $post = $this->contentPost(['telegram'], ['generated_content' => null]);

        $this->postNow($post)->assertOk();

        $post->refresh();
        $this->assertSame('กาแฟใหม่หอมกรุ่น #กาแฟ #โปรโมชัน', $post->generated_content);
        $this->assertSame(['กาแฟ', 'โปรโมชัน'], $post->hashtags);
        $this->assertSame('posted', $post->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['text'] === 'กาแฟใหม่หอมกรุ่น #กาแฟ #โปรโมชัน');
    }

    // ── การเก็บ token ────────────────────────────────────────────────────────

    public function test_platform_tokens_are_encrypted_at_rest(): void
    {
        $integration = $this->telegram();

        $raw = DB::table('chatbot_platform_integrations')->where('id', $integration->id)->value('telegram_bot_token');

        $this->assertNotSame(self::TELEGRAM_TOKEN, $raw);
        $this->assertSame(self::TELEGRAM_TOKEN, Crypt::decryptString($raw));
        $this->assertSame(self::TELEGRAM_TOKEN, $integration->fresh()->telegram_bot_token);
    }

    public function test_migration_encrypts_existing_plaintext_tokens_and_leaves_encrypted_ones_alone(): void
    {
        $alreadyEncrypted = Crypt::encryptString(self::LINE_TOKEN);
        $id = DB::table('chatbot_platform_integrations')->insertGetId([
            'bot_profile_id' => $this->bot->id,
            'platform_type' => 'telegram',
            'telegram_bot_token' => self::TELEGRAM_TOKEN,
            'line_channel_access_token' => $alreadyEncrypted,
            'webhook_secret' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_14_100000_encrypt_chatbot_platform_integration_secrets.php');
        $migration->up();

        $row = DB::table('chatbot_platform_integrations')->find($id);
        $this->assertSame(self::TELEGRAM_TOKEN, Crypt::decryptString($row->telegram_bot_token));
        $this->assertSame($alreadyEncrypted, $row->line_channel_access_token);
        $this->assertNull($row->webhook_secret);
        $this->assertSame(self::TELEGRAM_TOKEN, ChatbotPlatformIntegration::find($id)->telegram_bot_token);
    }

    public function test_integration_rejects_a_malformed_discord_channel_id(): void
    {
        $integration = $this->discord();

        $this->putJson("/api/v1/chatbot/bots/{$this->bot->id}/integrations/{$integration->id}", [
            'platform_settings' => ['post_channel_id' => '../../guilds/1/members'],
        ])->assertStatus(422)->assertJsonValidationErrors('platform_settings.post_channel_id', 'errors');

        $this->assertSame(self::DISCORD_CHANNEL, $integration->fresh()->platform_settings['post_channel_id']);
    }
}
