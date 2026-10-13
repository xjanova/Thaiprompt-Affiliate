<?php

namespace Tests\Feature;

use App\Http\Controllers\FacebookWebhookController;
use App\Models\FortuneReading;
use App\Models\FortuneStoryEvent;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneMonthlyClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/** ทดสอบ payload จริงผ่าน dispatch โดยแทนเฉพาะปลายทาง AI */
class FacebookStoryReferralTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '123456789012345';

    private FacebookStoryTestController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        FortunePageContext::forget();
        FortunePageContext::flushMemo();
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getGlobalSettings();
        $settings->facebook_page_id = '999999';
        $settings->facebook_page_token = 'TEST_TOKEN';
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['recipient_id' => self::PSID, 'message_id' => 'out.1'], 200)]);
        $this->controller = new FacebookStoryTestController;
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        parent::tearDown();
    }

    private function event(array $body): array
    {
        return array_merge([
            'sender' => ['id' => self::PSID],
            'recipient' => ['id' => '999999'],
            'timestamp' => now()->getTimestampMs(),
        ], $body);
    }

    private function reply(string $text): array
    {
        return $this->event(['message' => [
            'mid' => 'm.story.1', 'text' => $text,
            'reply_to' => ['story' => ['id' => 'story-1', 'url' => 'https://facebook.com/stories/story-1']],
        ]]);
    }

    private function dispatch(array $event): void
    {
        $this->controller->dispatchEntry(['messaging' => [$event]]);
    }

    private function welcomes(): array
    {
        return Http::recorded(fn ($request) => str_contains((string) ($request['message']['text'] ?? ''), 'จากสตอรี่'))
            ->map(fn ($pair) => $pair[0]->data())->all();
    }

    public function test_emoji_story_reply_is_tracked_and_welcomed_as_response(): void
    {
        $this->dispatch($this->reply('❤️'));
        $this->assertDatabaseHas('fortune_story_events', [
            'facebook_user_id' => self::PSID, 'source' => 'story', 'event_type' => 'reply',
            'story_id' => 'story-1', 'story_url' => 'https://facebook.com/stories/story-1',
        ]);
        $this->assertCount(1, $this->welcomes());
        $welcome = $this->welcomes()[0];
        $this->assertSame('RESPONSE', $welcome['messaging_type']);
        $this->assertArrayNotHasKey('tag', $welcome);
        $this->assertStringContainsString('วัน เดือน ปีเกิด', $welcome['message']['text']);
        $this->assertNotNull(FortuneStoryEvent::first()->welcomed_at);
        $this->assertSame([], $this->controller->conversations);
    }

    public function test_short_reply_is_welcomed_but_birthdate_uses_existing_flow(): void
    {
        $this->dispatch($this->reply('Hi'));
        $this->assertCount(1, $this->welcomes());
        $event = $this->reply('เกิด 12/3/2535 เวลา 10:30');
        $event['message']['mid'] = 'm.birthdate';
        $this->dispatch($event);
        $this->assertSame([[self::PSID, 'เกิด 12/3/2535 เวลา 10:30']], $this->controller->conversations);
        $this->assertCount(1, $this->welcomes());
        $this->assertDatabaseCount('fortune_story_events', 2);
    }

    public function test_normal_story_text_is_not_hijacked(): void
    {
        $this->dispatch($this->reply('อยากดูดวงเรื่องงานค่ะ'));
        $this->assertSame([[self::PSID, 'อยากดูดวงเรื่องงานค่ะ']], $this->controller->conversations);
        $this->assertCount(0, $this->welcomes());
    }

    public function test_existing_user_referral_is_tracked_and_welcomed(): void
    {
        $this->dispatch($this->event(['referral' => ['ref' => 'STORY_october-love', 'source' => 'SHORTLINK']]));
        $this->assertDatabaseHas('fortune_story_events', ['event_type' => 'referral', 'story_slug' => 'october-love']);
        $this->assertCount(1, $this->welcomes());
        $this->assertTrue(Cache::has('fb_last_inbound:'.self::PSID));
    }

    public function test_new_user_postback_referral_sends_one_contextual_welcome(): void
    {
        $this->dispatch($this->event(['postback' => [
            'mid' => 'pb.story.1', 'payload' => 'GET_STARTED',
            'referral' => ['ref' => 'STORY_new-user'],
        ]]));
        $this->assertCount(1, $this->welcomes());
        $this->assertSame(0, $this->controller->getStartedCount);
        $this->assertDatabaseHas('fortune_story_events', ['story_slug' => 'new-user']);
    }

    public function test_retries_are_deduplicated_even_after_cache_expires(): void
    {
        $event = $this->reply('👍');
        $this->dispatch($event);
        Cache::forget('fb_webhook_mid:m.story.1');
        $this->dispatch($event);
        $this->assertDatabaseCount('fortune_story_events', 1);
        $this->assertCount(1, $this->welcomes());
        $referral = $this->event(['referral' => ['ref' => 'STORY_retry']]);
        $this->dispatch($referral);
        $this->dispatch($referral);
        $this->assertDatabaseCount('fortune_story_events', 2);
        $this->assertCount(2, $this->welcomes());
    }

    public function test_stale_or_missing_timestamp_never_sends_story_welcome(): void
    {
        $event = $this->reply('❤️');
        $event['timestamp'] = now()->subHours(25)->getTimestampMs();
        $this->dispatch($event);
        $referral = $this->event(['referral' => ['ref' => 'STORY_old']]);
        $referral['timestamp'] = now()->subHours(25)->getTimestampMs();
        $this->dispatch($referral);
        unset($referral['timestamp']);
        $referral['referral']['ref'] = 'STORY_missing-time';
        $this->dispatch($referral);
        $this->assertCount(0, $this->welcomes());
        $this->assertDatabaseCount('fortune_story_events', 3);
    }

    private function reading(string $status, bool $paid = false): FortuneReading
    {
        return FortuneReading::create([
            'facebook_user_id' => self::PSID, 'platform_user_id' => self::PSID,
            'platform' => 'facebook', 'conversation_status' => $status,
            'reading_type' => FortuneReading::READING_TYPE_DEEP,
            'is_paid' => $paid, 'questions' => [], 'ai_response' => '', 'ai_provider' => 'none',
        ]);
    }

    public function test_pending_payment_is_preserved_for_reply_and_referral(): void
    {
        $reading = $this->reading(FortuneReading::STATUS_PENDING_PAYMENT);
        $before = $reading->fresh()->getAttributes();
        $this->dispatch($this->reply('❤️'));
        $this->dispatch($this->event(['postback' => ['payload' => 'GET_STARTED', 'referral' => ['ref' => 'STORY_bill']]]));
        $this->assertCount(0, $this->welcomes());
        $this->assertSame($before, $reading->fresh()->getAttributes());
        $this->assertSame([[self::PSID, '❤️']], $this->controller->conversations);
        $this->assertDatabaseCount('fortune_readings', 1);
        $this->assertDatabaseCount('fortune_story_events', 2);
    }

    public function test_paid_reading_awaiting_birthdate_is_preserved(): void
    {
        $reading = $this->reading(FortuneReading::STATUS_COLLECTING_BIRTHDATE, true);
        $this->dispatch($this->reply('❤️'));
        $this->assertCount(0, $this->welcomes());
        $this->assertSame(FortuneReading::STATUS_COLLECTING_BIRTHDATE, $reading->fresh()->conversation_status);
        $this->assertSame([[self::PSID, '❤️']], $this->controller->conversations);
    }

    public function test_paid_prediction_waiting_for_delivery_takes_priority(): void
    {
        $reading = $this->reading(FortuneReading::STATUS_COMPLETED, true);
        $reading->update(['deep_response' => 'คำทำนายที่ลูกค้าจ่ายแล้ว']);
        $this->dispatch($this->reply('❤️'));
        $this->dispatch($this->event(['referral' => ['ref' => 'STORY_paid']]));
        $this->assertCount(0, $this->welcomes());
        $this->assertSame([[self::PSID, '❤️']], $this->controller->conversations);
        $this->assertDatabaseCount('fortune_story_events', 2);
    }

    public function test_banned_referral_is_tracked_without_a_welcome(): void
    {
        app(\App\Services\FortuneBanService::class)->ban('facebook', self::PSID, 60, 'ทดสอบ');
        $this->dispatch($this->event(['referral' => ['ref' => 'STORY_banned']]));
        $this->assertCount(0, $this->welcomes());
        $this->assertDatabaseCount('fortune_story_events', 1);
    }

    public function test_referral_attached_to_a_normal_message_does_not_add_a_welcome(): void
    {
        $event = $this->event([
            'message' => ['mid' => 'm.normal.ref', 'text' => 'เกิด 12/3/2535'],
            'referral' => ['ref' => 'STORY_birthdate'],
        ]);
        $this->dispatch($event);
        $this->assertCount(0, $this->welcomes());
        $this->assertSame([[self::PSID, 'เกิด 12/3/2535']], $this->controller->conversations);
        $this->assertDatabaseCount('fortune_story_events', 1);
    }

    public function test_takeover_tracks_source_but_does_not_welcome(): void
    {
        $reading = $this->reading(FortuneReading::STATUS_PENDING_PAYMENT);
        $reading->forceFill(['admin_takeover_until' => now()->addHour(), 'admin_takeover_reason' => 'manual'])->save();
        $this->dispatch($this->reply('❤️'));
        $this->dispatch($this->event(['referral' => ['ref' => 'STORY_takeover']]));
        $this->assertCount(0, $this->welcomes());
        $this->assertSame([], $this->controller->conversations);
        $this->assertDatabaseCount('fortune_story_events', 2);
    }

    public function test_monthly_claim_referral_still_uses_original_claim_flow(): void
    {
        $ref = 'MONTHLY_CLAIM_'.now()->format('Y-m');
        $claim = Mockery::mock(FortuneMonthlyClaimService::class);
        $claim->shouldReceive('parseRefForCurrentMonth')->once()->with($ref)->andReturn(now()->format('Y-m'));
        $claim->shouldReceive('claimForUser')->once()->with(self::PSID, 'facebook',
            FortuneMonthlyClaimService::SOURCE_GROUP_POST, ['referrer' => $ref])
            ->andReturn(['status' => 'granted', 'message' => 'รับสิทธิ์แล้ว']);
        $this->app->instance(FortuneMonthlyClaimService::class, $claim);
        $this->dispatch($this->event(['referral' => ['ref' => $ref]]));
        $this->assertSame([[self::PSID, 'ทำนายฟรี']], $this->controller->conversations);
        $this->assertDatabaseCount('fortune_story_events', 0);
        $this->assertCount(0, $this->welcomes());
    }

    public function test_plain_get_started_remains_unchanged(): void
    {
        $this->dispatch($this->event(['postback' => ['mid' => 'pb.normal', 'payload' => 'GET_STARTED']]));
        $this->assertSame(1, $this->controller->getStartedCount);
        $this->assertDatabaseCount('fortune_story_events', 0);
    }
}

/** ใช้ dispatch/ด่านจริง แต่ไม่เรียก AI หรือ welcome แบบเดิม */
class FacebookStoryTestController extends FacebookWebhookController
{
    public array $conversations = [];

    public int $getStartedCount = 0;

    public function dispatchEntry(array $entry): void
    {
        $this->processEntry($entry);
    }

    protected function processConversationalMessage(string $senderId, string $messageText): void
    {
        $this->conversations[] = [$senderId, $messageText];
    }

    protected function handleGetStarted(string $senderId): void
    {
        $this->getStartedCount++;
    }
}
