<?php

namespace Tests\Feature\Fortune;

use App\Jobs\ProcessBufferedChatMessageJob;
use App\Jobs\RetryFailedChatReplyJob;
use App\Models\AiApiKey;
use App\Models\FortuneReading;
use App\Models\FortuneSavedQuestion;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\ChatReplyRecovery;
use App\Services\Fortune\CustomerPersonaService;
use App\Services\Fortune\FortuneChatLogService;
use App\Services\Fortune\MessageBuffer;
use App\Services\Fortune\ReplyTokenVault;
use App\Services\Fortune\TakeoverSendGuard;
use App\Services\FortuneAIService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\LineAlertService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🌙 (2026-10-30) AI ตอบไม่ทันในแชท — ลูกค้าต้องไม่เจอความเงียบ
 *
 * - คีย์แรก timeout → ลองคีย์อื่นในงบเวลา (เดิมคีย์แรกกินงบหมด ไม่เคยถึงคีย์ที่เหลือ)
 * - ทุกคีย์ล้ม → Telegram/Facebook ได้กล่องแจ้งแล้วตอบตามหลัง · LINE ได้กล่องแจ้งด้วย reply เท่านั้น
 * - ลูกค้าไม่มีทางได้คำตอบซ้ำ และ LINE ไม่มีวัน push กล่องแจ้ง
 *
 * ใช้ Telegram เป็นตัวแทนเส้นส่งตรง — เส้นเดียวกับ Facebook แต่ไม่มี sleep 15 วิของ humanLikeDelayFb
 */
#[Group('chat-reply-recovery')]
class ChatReplyRecoveryTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const TG_UID = 'tg_550000000077';

    private const FB_UID = '61550000000077';

    private const LINE_UID = 'U1234567890abcdef1234567890abcdef';

    private const QUESTION = 'แม่หมอคะ อยากถามเรื่องความรักค่ะ';

    private const AI_ANSWER = 'แม่หมอดูให้แล้วค่ะ ความรักกำลังไปได้สวยนะคะ';

    /** สิ่งที่ AI ตอบ — ใส่ [SKIP] เมื่อต้องการให้เทิร์นนี้เงียบ */
    private string $aiReply = self::AI_ANSWER;

    /** ให้ Telegram ส่งไม่สำเร็จ */
    private bool $telegramDown = false;

    /** เรียกตอน AI กำลังตอบ — จำลองเรื่องที่เกิดระหว่างเทิร์น */
    private ?\Closure $whileAiAnswers = null;

    /** คีย์ไหน timeout บ้าง — คีย์ที่ไม่อยู่ในนี้ตอบสำเร็จ */
    private array $timeoutKeys = [];

    /** ให้ N ครั้งแรก timeout ไม่ว่าคีย์ไหน — ระบบหมุนคีย์ ระบุชื่อคีย์แล้วเทสไม่นิ่ง */
    private int $timeoutFirstCalls = 0;

    /** ลำดับคีย์ที่ถูกเรียกจริง */
    private array $aiCalls = [];

    /** timeout ที่ส่งไปจริงทุกครั้ง — เทสนี้โยน exception ทันที เวลาไม่เดิน จึงพิสูจน์เพดานจากค่านี้ */
    private array $aiTimeouts = [];

    /** หน่วงการเรียก AI แต่ละครั้ง (มิลลิวินาที) — ให้เวลาเดินจริงในเทสงบเวลา */
    private int $aiCallDelayMs = 0;

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
        $settings->telegram_bot_token = '12345:test-token';
        $settings->line_enabled = true;
        $settings->line_channel_access_token = 'line-test-token';
        $settings->line_channel_secret = 'line-test-secret';
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        foreach (['chat-key-a', 'chat-key-b', 'chat-key-c'] as $i => $key) {
            $this->makeChatKey($key, 50 + $i);
        }

        $this->app->instance(FortuneChatLogService::class, new class extends FortuneChatLogService
        {
            public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void {}
        });

        $this->mock(LineAlertService::class, fn ($m) => $m->shouldIgnoreMissing());

        Http::fake(function (Request $r, array $options) {
            // นับเฉพาะการแชทจริง — embedding ของ RAG วิ่ง host เดียวกันแต่คนละ endpoint
            if (str_contains($r->url(), ':generateContent')) {
                $key = $r->header('x-goog-api-key')[0] ?? '';
                $this->aiCalls[] = $key;
                $this->aiTimeouts[] = $options['timeout'] ?? null;
                usleep($this->aiCallDelayMs * 1000);

                if (in_array($key, $this->timeoutKeys, true) || count($this->aiCalls) <= $this->timeoutFirstCalls) {
                    throw new ConnectionException('cURL error 28: Operation timed out after 8001 milliseconds');
                }

                if ($this->whileAiAnswers !== null) {
                    ($this->whileAiAnswers)();
                }

                return Http::response([
                    'candidates' => [['content' => ['parts' => [['text' => $this->aiReply]]]]],
                    'usageMetadata' => ['totalTokenCount' => 42],
                ], 200);
            }

            if ($this->telegramDown && str_contains($r->url(), 'api.telegram.org')) {
                return Http::response(['ok' => false, 'description' => 'Bad Gateway'], 502);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200);
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function makeChatKey(string $key, int $priority): AiApiKey
    {
        return AiApiKey::create([
            'name' => $key,
            'provider' => 'gemini',
            'purpose' => 'chat',
            'api_key' => $key,
            'model' => 'gemini-3.1-flash-lite',
            'priority' => $priority,
            'is_active' => true,
            'last_test_passed_at' => now(),
        ]);
    }

    private function allKeysTimeOut(): void
    {
        $this->timeoutKeys = ['chat-key-a', 'chat-key-b', 'chat-key-c'];
    }

    private function runFlush(string $platform, string $userId, string $text = self::QUESTION): void
    {
        app(MessageBuffer::class)->append('chat', $userId, $text);
        (new ProcessBufferedChatMessageJob($platform, $userId, 0))->handle();
    }

    /** ข้อความที่ส่งถึงลูกค้า Telegram */
    private function telegramTexts(): array
    {
        return collect(Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org') && str_contains($r->url(), '/sendMessage')))
            ->map(fn ($pair) => (string) ($pair[0]->data()['text'] ?? ''))
            ->values()
            ->all();
    }

    private function lineRequests(string $path): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), $path))->values()->all();
    }

    /** พรอมต์ของการแชทครั้งล่าสุดที่ส่งเข้า AI */
    private function lastAiPrompt(): string
    {
        $request = collect(Http::recorded(fn (Request $r) => str_contains($r->url(), ':generateContent')))->last()[0];

        return (string) ($request->data()['contents'][0]['parts'][0]['text'] ?? '');
    }

    private function assertNoLinePush(): void
    {
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/message/push'));
    }

    private function pending(string $platform, string $userId): ?array
    {
        return Cache::get("fortune:chat_pending:{$platform}:{$userId}");
    }

    private function savedQuestion(): FortuneSavedQuestion
    {
        return FortuneSavedQuestion::query()->latest('id')->firstOrFail();
    }

    /** ลูกค้า Telegram มีคำถามค้างจากรอบที่ทุกคีย์ล้ม แล้ว AI กลับมาปกติ @return string id ของคำถามค้าง */
    private function leaveAPendingQuestion(): string
    {
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $this->timeoutKeys = [];

        return $this->pending('telegram', self::TG_UID)['id'];
    }

    private function startTakeover(): void
    {
        $this->makeReading([
            'facebook_user_id' => self::TG_UID,
            'platform_user_id' => self::TG_UID,
            'platform' => 'telegram',
            'conversation_status' => FortuneReading::STATUS_TIER_CHOICE,
            'admin_takeover_reason' => FortuneReading::TAKEOVER_REASON_MANUAL,
            'admin_takeover_started_at' => now()->subMinute(),
            'admin_takeover_until' => now()->addMinutes(20),
        ]);
        TakeoverSendGuard::endMemoScope();
    }

    // ============================================================
    // ลองคีย์อื่นในงบเวลา
    // ============================================================

    public function test_first_key_timeout_falls_through_to_another_key_with_no_hold_box(): void
    {
        Queue::fake();
        $this->timeoutFirstCalls = 2;

        $this->runFlush('telegram', self::TG_UID);

        $this->assertCount(3, $this->aiCalls, 'ล้ม 2 คีย์แล้วต้องไปถึงคีย์ที่ 3');
        $this->assertCount(3, array_unique($this->aiCalls), 'ห้ามลองคีย์ที่เพิ่งล้มซ้ำ');
        $this->assertSame([self::AI_ANSWER], $this->telegramTexts());
        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
        $this->assertSame(0, FortuneSavedQuestion::count(), 'ตอบได้แล้วไม่ต้องรบกวนแอดมิน');
    }

    public function test_line_first_key_timeout_is_answered_by_reply_not_push(): void
    {
        Queue::fake();
        $this->timeoutFirstCalls = 1;
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');

        $this->runFlush('line', self::LINE_UID);

        $this->assertCount(1, $this->lineRequests('/message/reply'));
        $this->assertStringContainsString('ไปได้สวย', json_encode($this->lineRequests('/message/reply')[0][0]->data(), JSON_UNESCAPED_UNICODE));
        $this->assertNoLinePush();
    }

    public function test_timeout_with_history_never_retries_the_same_key(): void
    {
        Queue::fake();
        $this->runFlush('telegram', self::TG_UID);

        $this->aiCalls = [];
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID, 'แล้วเรื่องงานล่ะคะ');

        // เดิมคีย์เดียวกันถูกลองซ้ำแบบไม่มี history = รอ 30 วิ แล้วจบเทิร์น
        $this->assertCount(3, $this->aiCalls);
        $this->assertCount(3, array_unique($this->aiCalls));
    }

    public function test_each_key_gets_eight_seconds_not_the_whole_budget(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();

        $this->runFlush('telegram', self::TG_UID);

        // เดิมคีย์แรกรอ 15 วิ = เท่างบรวมพอดี ⇒ timeout ครั้งเดียวจบเทิร์น
        $this->assertSame([8, 8, 8], $this->aiTimeouts);
    }

    public function test_each_key_gets_eight_seconds_with_history_too(): void
    {
        Queue::fake();
        $this->runFlush('telegram', self::TG_UID);

        $this->aiTimeouts = [];
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID, 'แล้วเรื่องงานล่ะคะ');

        $this->assertSame([8, 8, 8], $this->aiTimeouts);
    }

    public function test_low_remaining_budget_does_not_start_another_key(): void
    {
        $this->allKeysTimeOut();
        $this->aiCallDelayMs = 600;
        $ai = new FortuneAIService(FortuneTellingSetting::getSettings());
        $threw = false;

        try {
            $ai->generateChatResponseWithPoolFallback(self::QUESTION, null, [], 3500, 8, self::TG_UID);
        } catch (Exception) {
            $threw = true;
        }

        $this->assertTrue($threw, 'ล้มหมดต้องโยน exception ให้ผู้เรียกจัดการต่อ');
        $this->assertSame([3], $this->aiTimeouts, 'คีย์แรกได้แค่เวลาที่เหลือ และเหลือไม่ถึง 3 วิต้องไม่เริ่มคีย์ใหม่');
    }

    public function test_timeout_only_rests_the_key_and_never_counts_a_strike(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();

        $this->runFlush('telegram', self::TG_UID);

        $keys = AiApiKey::all();
        $this->assertTrue($keys->contains(fn (AiApiKey $key) => $key->disabled_until?->isFuture()), 'ต้องพักคีย์ที่ timeout');

        // ช่วง Gemini ช้า ถ้านับ strike คีย์ในเลนแชทจะโดนปิดถาวรทีละตัว
        foreach ($keys as $key) {
            $this->assertTrue((bool) $key->is_active);
            $this->assertFalse((bool) $key->is_critical);
            $this->assertSame(0, (int) $key->error_check_attempts);
        }
    }

    public function test_sync_path_still_returns_null_so_the_process_message_fallback_runs(): void
    {
        $this->allKeysTimeOut();
        $service = new FortuneConversationService(FortuneTellingSetting::getSettings());
        $service->setPlatform('telegram');

        $this->assertNull($service->tryAIChatResponse(self::TG_UID, self::QUESTION));
    }

    public function test_job_budget_lets_three_keys_run_but_sync_stays_within_fifteen_seconds(): void
    {
        $ai = new class(FortuneTellingSetting::getSettings()) extends FortuneAIService
        {
            public function optionsFor(string $provider, string $model, int $remainingMs): array
            {
                return $this->chatAttemptOptions($provider, $model, $remainingMs);
            }
        };

        $this->assertSame(['timeout' => 8], $ai->optionsFor('gemini', 'gemini-3.1-flash-lite', 24000));
        $this->assertSame(['timeout' => 7], $ai->optionsFor('gemini', 'gemini-3.1-flash-lite', 7500), 'คีย์สุดท้ายต้องไม่เกินเวลาที่เหลือ');
    }

    public function test_reasoning_models_keep_their_own_long_timeout(): void
    {
        $ai = new class(FortuneTellingSetting::getSettings()) extends FortuneAIService
        {
            public function optionsFor(string $provider, string $model, int $remainingMs): array
            {
                return $this->chatAttemptOptions($provider, $model, $remainingMs);
            }
        };

        $this->assertSame([], $ai->optionsFor('gemini', 'gemini-2.5-pro', 24000));
        $this->assertSame([], $ai->optionsFor('openai', 'gpt-5.2', 24000));
    }

    // ============================================================
    // ทุกคีย์ล้ม — Telegram/Facebook
    // ============================================================

    public function test_all_keys_fail_sends_hold_box_and_schedules_retry(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();

        $this->runFlush('telegram', self::TG_UID);

        $texts = $this->telegramTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('รอสักครู่', $texts[0]);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 1 && $job->userId === self::TG_UID);
        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text']);
    }

    public function test_facebook_hold_box_goes_out_at_once_with_no_sales_buttons(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $startedAt = microtime(true);

        $this->runFlush('facebook', self::FB_UID);

        $this->assertLessThan(10, microtime(true) - $startedAt, 'กล่องแจ้งห้ามโดน sleep 15 วิของ humanLikeDelayFb');

        $sent = collect(Http::recorded(fn (Request $r) => str_contains($r->url(), '/me/messages') && ! empty($r->data()['message'])))
            ->values()
            ->map(fn ($pair) => $pair[0]->data()['message']);

        $this->assertCount(1, $sent);
        $this->assertStringContainsString('รอสักครู่', $sent[0]['text']);
        $this->assertArrayNotHasKey('quick_replies', $sent[0]);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->platform === 'facebook');
        $this->assertSame('facebook', $this->savedQuestion()->platform);
    }

    public function test_saved_question_records_the_real_platform(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();

        $this->runFlush('telegram', self::TG_UID);

        $this->assertSame('telegram', $this->savedQuestion()->platform, 'หน้าแอดมินต้องกรองตามช่องทางจริงได้');
    }

    public function test_retry_answers_once_and_closes_the_admin_question(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        $this->timeoutKeys = [];
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $texts = $this->telegramTexts();
        $this->assertCount(2, $texts, 'กล่องแจ้ง 1 + คำตอบ 1 — รันซ้ำต้องไม่ตอบซ้ำ');
        $this->assertStringContainsString('ไปได้สวย', $texts[1]);
        $this->assertNull($this->pending('telegram', self::TG_UID));

        $question = $this->savedQuestion();
        $this->assertTrue($question->is_replied, 'แอดมินต้องไม่ต้องตอบซ้ำ');
        $this->assertTrue($question->is_sent_to_user);
        $this->assertSame('ai_recovered', $question->reason);
        $this->assertSame(0, FortuneSavedQuestion::pending()->count());
    }

    public function test_failed_retry_does_not_save_a_second_admin_question_or_a_second_hold_box(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame(1, FortuneSavedQuestion::count());
        $this->assertCount(1, $this->telegramTexts(), 'กล่องแจ้งต้องไม่ซ้ำ');
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 2);
    }

    public function test_retry_with_every_key_resting_still_tries_again_later(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        // คีย์พัก 90 วิ แต่ retry แรกมาที่ 45 วิ — รอบนี้ไม่มีคีย์ให้ใช้เลย
        AiApiKey::query()->update(['disabled_until' => now()->addSeconds(90)]);
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 2);
        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text']);
        $this->assertCount(1, $this->telegramTexts());
    }

    public function test_retry_does_not_count_the_turn_a_second_time(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        $this->partialMock(CustomerPersonaService::class, function ($mock) {
            $mock->shouldNotReceive('recordFreeChatTurn');
            $mock->shouldNotReceive('recordChitchatAfterPitch');
        });

        $this->timeoutKeys = [];
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertCount(2, $this->telegramTexts());
    }

    public function test_exhausted_retries_fall_back_and_stop_retrying(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];
        Queue::assertPushed(RetryFailedChatReplyJob::class, 1);

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, count(ChatReplyRecovery::RETRY_DELAYS)))->handle();

        Queue::assertPushed(RetryFailedChatReplyJob::class, 1);
        $this->assertCount(2, $this->telegramTexts(), 'กล่องแจ้ง 1 + กล่องยอมแพ้ 1 — ห้ามจบแบบเงียบ');
        $this->assertNull($this->pending('telegram', self::TG_UID));
    }

    public function test_outage_skips_retry_and_uses_the_offline_box(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        Cache::put('fortune:ai_outage_active', true, 600);

        $this->runFlush('telegram', self::TG_UID);

        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
    }

    // ============================================================
    // กันตอบซ้ำ
    // ============================================================

    public function test_new_message_merges_the_pending_question_and_cancels_the_retry(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        $this->timeoutKeys = [];
        $this->aiCalls = [];
        $this->runFlush('telegram', self::TG_UID, 'แล้วเรื่องงานล่ะคะ');
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertCount(1, $this->aiCalls, 'retry ต้องไม่เรียก AI อีก');
        $this->assertCount(2, $this->telegramTexts(), 'กล่องแจ้ง 1 + คำตอบรวม 1');

        $prompt = $this->lastAiPrompt();
        $this->assertStringContainsString('ความรัก', $prompt);
        $this->assertStringContainsString('เรื่องงาน', $prompt);
        $this->assertTrue($this->savedQuestion()->is_replied);
    }

    public function test_admin_reply_cancels_the_retry(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];
        $this->savedQuestion()->markAsReplied('แอดมินตอบให้แล้วค่ะ', 1);

        $this->timeoutKeys = [];
        $this->aiCalls = [];
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame([], $this->aiCalls);
        $this->assertCount(1, $this->telegramTexts());
        $this->assertSame('แอดมินตอบให้แล้วค่ะ', $this->savedQuestion()->admin_reply);
    }

    public function test_takeover_cancels_the_retry(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];
        $this->startTakeover();

        $this->timeoutKeys = [];
        $this->aiCalls = [];
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame([], $this->aiCalls);
        $this->assertCount(1, $this->telegramTexts());
    }

    public function test_paid_reading_started_while_waiting_cancels_the_retry(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        $this->runFlush('telegram', self::TG_UID);
        $id = $this->pending('telegram', self::TG_UID)['id'];

        $this->makeReading([
            'facebook_user_id' => self::TG_UID,
            'platform_user_id' => self::TG_UID,
            'platform' => 'telegram',
            'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
            'is_paid' => true,
            'amount_paid' => 99,
            'paid_at' => now()->subMinute(),
        ]);
        Cache::forget('fortune:has_paid_active:'.self::TG_UID);

        $this->timeoutKeys = [];
        $this->aiCalls = [];
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame([], $this->aiCalls, 'ห้ามแชทค้างแทรกกลางการทำนาย');
        $this->assertCount(1, $this->telegramTexts());
    }

    public function test_intentional_silence_gets_no_hold_box(): void
    {
        Queue::fake();
        FortuneTellingSetting::query()->update(['enable_ai_chat' => false]);
        FortuneTellingSetting::clearSettingsCache();

        $this->runFlush('telegram', self::TG_UID);

        $this->assertSame([], $this->telegramTexts());
        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
    }

    public function test_ai_choosing_silence_is_not_a_failure(): void
    {
        Queue::fake();
        $this->aiReply = '[SKIP]';

        $this->runFlush('telegram', self::TG_UID, 'ค่ะ');

        // ตัวลอกแท็กกิน [SKIP] จนว่าง ถ้านับเป็น AI ล้ม ลูกค้าที่แค่รับคำจะได้กล่องแจ้งทุกครั้ง
        $this->assertSame([], $this->telegramTexts());
        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
        $this->assertSame(0, FortuneSavedQuestion::count(), 'ไม่ใช่คำถามที่แอดมินต้องตอบ');
        $this->assertNull($this->pending('telegram', self::TG_UID));
    }

    public function test_truly_empty_ai_reply_still_counts_as_a_failure(): void
    {
        Queue::fake();
        $this->aiReply = '[TURN 5]';

        $this->runFlush('telegram', self::TG_UID);

        $this->assertCount(1, $this->telegramTexts());
        $this->assertStringContainsString('รอสักครู่', $this->telegramTexts()[0]);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 1);
    }

    // ============================================================
    // หยิบคำถามค้างไปแล้วแต่ตอบไม่ถึงลูกค้า — คำถามต้องไม่หาย
    // ============================================================

    public function test_silent_new_message_keeps_the_pending_question(): void
    {
        Queue::fake();
        $oldId = $this->leaveAPendingQuestion();
        $this->aiReply = '[SKIP]';

        $this->runFlush('telegram', self::TG_UID, 'ค่ะ');

        $pending = $this->pending('telegram', self::TG_UID);
        $this->assertSame(self::QUESTION, $pending['text'], 'ข้อความที่ตั้งใจเงียบไม่ต้องตามไปด้วย');
        $this->assertNotSame($oldId, $pending['id']);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->pendingId === $pending['id'] && $job->attempt === 1);

        // retry ตัวเดิมที่ยังคิวอยู่ต้องไม่ตอบ ตัวใหม่ตอบครั้งเดียว
        $this->aiReply = self::AI_ANSWER;
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $oldId, 1))->handle();
        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $pending['id'], 1))->handle();

        $this->assertCount(2, $this->telegramTexts(), 'กล่องแจ้ง 1 + คำตอบ 1');
        $this->assertTrue($this->savedQuestion()->is_replied);
    }

    public function test_silent_retry_tries_again_and_stops_after_the_last_round(): void
    {
        Queue::fake();
        $id = $this->leaveAPendingQuestion();
        $this->aiReply = '[SKIP]';

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $nextId = $this->pending('telegram', self::TG_UID)['id'];
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->pendingId === $nextId && $job->attempt === 2);

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $nextId, 2))->handle();

        Queue::assertNotPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt > 2);
        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text'], 'เก็บไว้ตอบรวมตอนลูกค้าทักรอบถัดไป');
        $this->assertCount(1, $this->telegramTexts(), 'เคสเงียบต้องไม่มีกล่องเพิ่ม');
    }

    public function test_failed_delivery_in_retry_keeps_the_question(): void
    {
        Queue::fake();
        $id = $this->leaveAPendingQuestion();
        $this->telegramDown = true;

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text']);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 2);
        $this->assertFalse($this->savedQuestion()->is_replied, 'ส่งไม่ถึงต้องไม่ปิดรายการรอแอดมิน');
    }

    public function test_failed_delivery_of_a_merged_answer_keeps_both_messages(): void
    {
        Queue::fake();
        $this->leaveAPendingQuestion();
        $this->telegramDown = true;

        $this->runFlush('telegram', self::TG_UID, 'แล้วเรื่องงานล่ะคะ');

        $pending = $this->pending('telegram', self::TG_UID);
        $this->assertStringContainsString(self::QUESTION, $pending['text']);
        $this->assertStringContainsString('แล้วเรื่องงานล่ะคะ', $pending['text']);
        $this->assertSame([$this->savedQuestion()->id], $pending['saved_question_ids']);
        $this->assertFalse($this->savedQuestion()->is_replied);
    }

    public function test_exception_in_retry_keeps_the_question(): void
    {
        Queue::fake();
        $id = $this->leaveAPendingQuestion();
        $this->mock(FortuneChannelManager::class, fn ($m) => $m->shouldReceive('sendResponse')->andThrow(new RuntimeException('boom')));

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text']);
        Queue::assertPushed(RetryFailedChatReplyJob::class, fn ($job) => $job->attempt === 2);
    }

    public function test_exception_in_the_chat_job_keeps_the_pending_question(): void
    {
        Queue::fake();
        $this->leaveAPendingQuestion();
        $this->mock(FortuneChannelManager::class, fn ($m) => $m->shouldReceive('sendResponse')->andThrow(new RuntimeException('boom')));

        $this->runFlush('telegram', self::TG_UID, 'แล้วเรื่องงานล่ะคะ');

        $this->assertSame(self::QUESTION, $this->pending('telegram', self::TG_UID)['text']);
    }

    public function test_takeover_during_delivery_does_not_put_the_question_back(): void
    {
        Queue::fake();
        $id = $this->leaveAPendingQuestion();
        $this->whileAiAnswers = fn () => $this->startTakeover();

        (new RetryFailedChatReplyJob('telegram', self::TG_UID, $id, 1))->handle();

        $this->assertNull($this->pending('telegram', self::TG_UID), 'เจ้าของสั่งไม่ให้ตอบรวบตอนจบเทคโอเวอร์');
        Queue::assertPushed(RetryFailedChatReplyJob::class, 1);
        $this->assertCount(1, $this->telegramTexts());
    }

    public function test_line_silence_keeps_the_question_without_a_retry_job(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-first');
        $this->runFlush('line', self::LINE_UID);

        $this->timeoutKeys = [];
        $this->aiReply = '[SKIP]';
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-second');
        $this->runFlush('line', self::LINE_UID, 'ค่ะ');

        $this->assertSame(self::QUESTION, $this->pending('line', self::LINE_UID)['text']);
        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
        $this->assertNoLinePush();
    }

    // ============================================================
    // LINE — reply เท่านั้น
    // ============================================================

    public function test_line_all_keys_fail_replies_with_a_button_and_never_pushes(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');

        $this->runFlush('line', self::LINE_UID);

        $replies = $this->lineRequests('/message/reply');
        $this->assertCount(1, $replies);
        $this->assertSame('rt-fresh', $replies[0][0]->data()['replyToken']);
        $this->assertSame(
            ChatReplyRecovery::LINE_RETRY_TEXT,
            $replies[0][0]->data()['messages'][0]['quickReply']['items'][0]['action']['text']
        );
        $this->assertLessThanOrEqual(20, mb_strlen($replies[0][0]->data()['messages'][0]['quickReply']['items'][0]['action']['label']));
        $this->assertNoLinePush();
        Queue::assertNotPushed(RetryFailedChatReplyJob::class);
        $this->assertSame(self::QUESTION, $this->pending('line', self::LINE_UID)['text']);
    }

    public function test_line_without_a_token_sends_nothing_and_keeps_the_question(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();

        $this->runFlush('line', self::LINE_UID);

        $this->assertSame([], $this->lineRequests('/message/reply'));
        $this->assertNoLinePush();
        $this->assertSame(self::QUESTION, $this->pending('line', self::LINE_UID)['text']);
    }

    public function test_line_dead_token_does_not_fall_through_to_push(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-dead');
        Http::fake(function (Request $r) {
            if (str_contains($r->url(), ':generateContent')) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return str_contains($r->url(), '/message/reply')
                ? Http::response(['message' => 'Invalid reply token'], 400)
                : Http::response([], 200);
        });

        $this->runFlush('line', self::LINE_UID);

        $this->assertNoLinePush();
        $this->assertSame(self::QUESTION, $this->pending('line', self::LINE_UID)['text']);
    }

    public function test_line_button_press_answers_the_pending_question_by_reply(): void
    {
        Queue::fake();
        $this->allKeysTimeOut();
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-first');
        $this->runFlush('line', self::LINE_UID);

        $this->timeoutKeys = [];
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-second');
        $this->runFlush('line', self::LINE_UID, ChatReplyRecovery::LINE_RETRY_TEXT);

        $replies = $this->lineRequests('/message/reply');
        $this->assertCount(2, $replies);
        $this->assertSame('rt-second', $replies[1][0]->data()['replyToken']);
        $this->assertStringContainsString('ไปได้สวย', json_encode($replies[1][0]->data(), JSON_UNESCAPED_UNICODE));
        $this->assertNoLinePush();

        $prompt = $this->lastAiPrompt();
        $this->assertStringContainsString('ความรัก', $prompt);
        $this->assertStringNotContainsString(ChatReplyRecovery::LINE_RETRY_TEXT, $prompt, 'ข้อความปุ่มไม่ใช่คำถาม');
        $this->assertNull($this->pending('line', self::LINE_UID));
        $this->assertTrue($this->savedQuestion()->is_replied);
    }

    public function test_line_button_text_goes_through_the_chat_buffer(): void
    {
        $service = new class(FortuneTellingSetting::getSettings()) extends FortuneConversationService
        {
            public function buffers(string $text): bool
            {
                return $this->shouldBufferChatMessage($text);
            }
        };

        $this->assertTrue($service->buffers(ChatReplyRecovery::LINE_RETRY_TEXT));
    }

    public function test_line_budget_follows_the_reply_token_age(): void
    {
        $job = new class('line', self::LINE_UID, 0) extends ProcessBufferedChatMessageJob
        {
            public function budget(): int
            {
                return $this->chatBudgetMs();
            }
        };

        $this->assertSame(15000, $job->budget(), 'ไม่มี token ใช้งบเดิม');

        $this->travelTo(now());
        ReplyTokenVault::remember('line', self::LINE_UID, 'rt-fresh');
        $this->assertSame(24000, $job->budget(), 'token สด ใช้งบเต็ม');

        $this->travel(31)->seconds();
        $this->assertSame(16000, $job->budget(), 'flush ที่ 31 วิ เหลือ 19 วิ เผื่อส่ง 3 วิ');
        $this->assertSame('rt-fresh', ReplyTokenVault::take('line', self::LINE_UID), 'ดูเวลาต้องไม่หยิบ token ออก');
    }
}
