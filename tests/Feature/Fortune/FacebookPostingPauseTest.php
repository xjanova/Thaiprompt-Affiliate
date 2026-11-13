<?php

namespace Tests\Feature\Fortune;

use App\Models\ChatbotAutoContentPost;
use App\Models\ChatbotPlatformIntegration;
use App\Models\FortuneContentCampaign;
use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortunePage;
use App\Models\FortunePageRepost;
use App\Models\FortuneTellingSetting;
use App\Services\Chatbot\AutoContentService;
use App\Services\ContentCampaignAutoPostService;
use App\Services\DailyHoroscopeAutoPostService;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\FortuneRepostService;
use App\Services\FortuneHoroscopePublishService;
use App\Services\MysticContentAutoPostService;
use App\Support\FacebookGraphApi;
use App\Support\FacebookPagePostingGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class FacebookPostingPauseTest extends TestCase
{
    private FortunePage $page;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('p', 32)),
        ]);
        DB::purge('sqlite');
        Schema::create('fortune_pages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('platform');
            $table->string('external_page_id');
            $table->text('page_access_token');
            $table->json('settings_override')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('auto_post_enabled')->default(true);
            $table->boolean('repost_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes(); // โมเดล FortunePage ใช้ SoftDeletes — ไม่มีคอลัมน์นี้ query ทุกตัวจะล้ม
        });
        $this->page = FortunePage::create([
            'name' => 'Old branch name', 'platform' => 'facebook',
            'external_page_id' => '222', 'page_access_token' => 'test-page-token',
            'settings_override' => ['existing_setting' => 'preserved'],
        ]);
        FortunePage::create([
            'name' => 'Main', 'platform' => 'facebook', 'external_page_id' => '111',
            'page_access_token' => 'test-main-token', 'is_default' => true,
        ]);
        Http::preventStrayRequests();
        Storage::fake('local');
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        Schema::create('fortune_telling_settings', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_page_id');
            $table->string('facebook_page_token');
            $table->softDeletes();
        });
        DB::table('fortune_telling_settings')->insert(['facebook_page_id' => '222', 'facebook_page_token' => 'test-page-token']);
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        parent::tearDown();
    }

    private function fakeGraph(array $scheduled = [], ?array $identity = null, bool $deleteSucceeds = true, bool $published = false): void
    {
        $cancelled = [];
        Http::fake(function ($request) use ($scheduled, $identity, $deleteSucceeds, $published, &$cancelled) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/me')) {
                return Http::response($identity ?? ['id' => '222', 'name' => 'จันทราเมืองเลย']);
            }
            if (str_ends_with($path, '/222/scheduled_posts')) {
                return Http::response(['data' => array_values(array_filter($scheduled, fn ($post) => ! isset($cancelled[$post['id']])))]);
            }
            $id = basename($path);
            if ($request->method() === 'DELETE') {
                if ($deleteSucceeds) {
                    $cancelled[$id] = true;

                    return Http::response(['success' => true]);
                }

                return Http::response(['error' => ['code' => 100]], 400);
            }

            return Http::response([
                'id' => $id, 'is_published' => $published,
                'scheduled_publish_time' => now()->addHour()->timestamp,
                'from' => ['id' => '222'],
            ]);
        });
    }

    private function pauseOptions(bool $apply = true): array
    {
        return array_filter([
            '--page-id' => '222', '--expected-name' => 'จันทราเมืองเลย', '--apply' => $apply,
        ], fn ($value) => $value !== false);
    }

    public function test_dry_run_verifies_live_identity_without_writes(): void
    {
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions(false))->assertSuccessful();
        $this->assertTrue($this->page->fresh()->repost_enabled);
        $this->assertArrayNotHasKey(FacebookPagePostingGuard::PAUSED_KEY, $this->page->fresh()->settings_override);
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');
    }

    public function test_wrong_live_name_or_token_page_id_changes_nothing(): void
    {
        $this->fakeGraph([], ['id' => '999', 'name' => 'จันทราเมืองเลย']);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertTrue($this->page->fresh()->repost_enabled);
        $this->fakeGraph([], ['id' => '222', 'name' => 'Similar but different']);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertArrayNotHasKey(FacebookPagePostingGuard::PAUSED_KEY, $this->page->fresh()->settings_override);
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');
    }

    public function test_main_page_is_refused(): void
    {
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', [
            '--page-id' => '111', '--expected-name' => 'Main', '--apply' => true,
        ])->assertFailed();
        $this->assertTrue(FortunePage::where('external_page_id', '111')->first()->repost_enabled);
        Http::assertNothingSent();
    }

    public function test_pause_is_idempotent_preserves_chat_main_page_and_original_settings(): void
    {
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();
        $snapshot = $this->page->fresh()->settings_override['facebook_posting_pause_snapshot'];
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();
        $page = $this->page->fresh();
        $this->assertFalse($page->auto_post_enabled);
        $this->assertFalse($page->repost_enabled);
        $this->assertTrue($page->is_active);
        $this->assertSame($snapshot, $page->settings_override['facebook_posting_pause_snapshot']);
        $this->assertTrue($snapshot['repost_enabled']);
        $this->assertSame('preserved', $page->settings_override['existing_setting']);
        $this->assertTrue(FortunePage::where('external_page_id', '111')->first()->auto_post_enabled);
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    public function test_cancels_only_verified_future_unpublished_posts_and_keeps_audit(): void
    {
        $this->fakeGraph([['id' => '222_333', 'message' => 'Keep content', 'scheduled_publish_time' => now()->addHour()->timestamp]]);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();
        Storage::disk('local')->assertExists('facebook-posting-pauses/222/222_333.json');
        $this->assertStringContainsString('Keep content', Storage::disk('local')->get('facebook-posting-pauses/222/222_333.json'));
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/222_333'));
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    public function test_already_published_post_is_never_deleted(): void
    {
        $this->fakeGraph([['id' => '222_333']], published: true);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertTrue($this->page->fresh()->settings_override[FacebookPagePostingGuard::PAUSED_KEY]);
        Http::assertNotSent(fn ($r) => $r->method() === 'DELETE');
    }

    public function test_failed_remote_cancellation_keeps_pause_and_reports_failure_including_code_100(): void
    {
        $this->fakeGraph([['id' => '222_333']], deleteSucceeds: false);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertTrue($this->page->fresh()->settings_override[FacebookPagePostingGuard::PAUSED_KEY]);
    }

    /**
     * สร้างสมุดรีโพสให้มีคอลัมน์เท่าที่คำสั่งหยุดและตัวตรวจสถานะ (reconcile) ใช้จริง
     */
    private function createRepostLedger(): void
    {
        Schema::create('fortune_page_reposts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fortune_page_id');
            $table->string('source_post_id')->nullable();
            $table->string('media_type')->default('photo');
            $table->dateTime('slot_at')->nullable();
            $table->string('target_post_id')->nullable();
            $table->string('target_object_id')->nullable();
            $table->string('status');
            $table->text('error')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * เพิ่มแถวในสมุดรีโพสของเพจเป้าหมาย แล้วคืน id
     */
    private function ledgerRow(array $attributes): int
    {
        return DB::table('fortune_page_reposts')->insertGetId($attributes + [
            'fortune_page_id' => $this->page->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_unknown_inflight_reposts_are_retained_and_reported(): void
    {
        $this->createRepostLedger();
        // เพิ่งจองแถวเมื่อครู่ (ยังไม่ถึง 30 นาที) = ไม่รู้ว่า Facebook รับไปหรือยัง → ห้ามเดาว่าจบแล้ว
        $id = $this->ledgerRow(['status' => FortunePageRepost::STATUS_SENDING, 'slot_at' => now()->addHour()]);
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertSame('sending', DB::table('fortune_page_reposts')->where('id', $id)->value('status'));
    }

    /**
     * ⚠️ Facebook ไม่คืน post_id ตอนตั้งเวลารูป — แถวจึงจับคู่กับ /scheduled_posts ไม่ได้
     *    เมื่อ Facebook ยืนยันว่าคิวว่างแล้ว แถวที่ยังไม่ถึงเวลาต้องถูกปิด ไม่ค้าง "ตั้งเวลาแล้ว" ตลอดไป
     */
    public function test_scheduled_photo_rows_without_post_id_are_closed_once_facebook_queue_is_empty(): void
    {
        $this->createRepostLedger();
        $photo = $this->ledgerRow(['status' => FortunePageRepost::STATUS_SCHEDULED, 'slot_at' => now()->addHours(2), 'target_object_id' => '444']);
        $history = $this->ledgerRow(['status' => FortunePageRepost::STATUS_PUBLISHED, 'slot_at' => now()->subDay(), 'target_post_id' => '222_100']);
        $this->fakeGraph([['id' => '222_333', 'scheduled_publish_time' => now()->addHours(2)->timestamp]]);

        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();

        $this->assertSame('cancelled', DB::table('fortune_page_reposts')->where('id', $photo)->value('status'));
        $this->assertSame('published', DB::table('fortune_page_reposts')->where('id', $history)->value('status'));
        Http::assertNotSent(fn ($r) => $r->method() === 'POST');
    }

    public function test_ledger_rows_stay_scheduled_while_facebook_queue_is_not_empty(): void
    {
        $this->createRepostLedger();
        $photo = $this->ledgerRow(['status' => FortunePageRepost::STATUS_SCHEDULED, 'slot_at' => now()->addHours(2), 'target_object_id' => '444']);
        $this->fakeGraph([['id' => '222_333']], deleteSucceeds: false);

        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();

        $this->assertSame('scheduled', DB::table('fortune_page_reposts')->where('id', $photo)->value('status'));
    }

    /**
     * cron รีโพสไม่วิ่งให้เพจที่ปิดสวิตช์แล้ว — แถวที่เลยเวลาต้องถูกตรวจกับ Facebook จากคำสั่งนี้เอง
     */
    public function test_past_due_rows_are_settled_against_facebook_instead_of_blocking_forever(): void
    {
        $this->createRepostLedger();
        $due = $this->ledgerRow(['status' => FortunePageRepost::STATUS_SCHEDULED, 'media_type' => 'text', 'slot_at' => now()->subHour(), 'target_post_id' => '222_900']);
        $this->fakeGraph(published: true);

        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();

        $this->assertSame('published', DB::table('fortune_page_reposts')->where('id', $due)->value('status'));
        Http::assertNotSent(fn ($r) => in_array($r->method(), ['POST', 'DELETE'], true));
    }

    public function test_resume_lifts_the_lock_but_never_turns_posting_back_on(): void
    {
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();
        $guard = new FacebookPagePostingGuard;
        $this->assertTrue($guard->isPaused('222'));

        $this->artisan('fortune:facebook-posting-pause', [
            '--page-id' => '222', '--expected-name' => 'จันทราเมืองเลย', '--resume' => true,
        ])->assertSuccessful();

        $page = $this->page->fresh();
        $this->assertFalse($guard->isPaused('222'));
        $guard->assertAllowed('222');
        // สวิตช์ยังปิด — การเปิดโพสต์กลับเป็นของเจ้าของ ไม่ใช่ของคำสั่งนี้
        $this->assertFalse($page->auto_post_enabled);
        $this->assertFalse($page->repost_enabled);
        $this->assertArrayNotHasKey(FacebookPagePostingGuard::PAUSED_KEY, $page->settings_override);
        $this->assertArrayNotHasKey(FacebookPagePostingGuard::SNAPSHOT_KEY, $page->settings_override);
        $this->assertTrue($page->settings_override[FacebookPagePostingGuard::LAST_PAUSE_KEY]['repost_enabled']);
        $this->assertArrayHasKey('resumed_at', $page->settings_override[FacebookPagePostingGuard::LAST_PAUSE_KEY]);
        $this->assertSame('preserved', $page->settings_override['existing_setting']);
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET');

        // ปลดซ้ำ = ไม่มีอะไรให้ปลด ไม่พัง
        $this->artisan('fortune:facebook-posting-pause', [
            '--page-id' => '222', '--expected-name' => 'จันทราเมืองเลย', '--resume' => true,
        ])->assertSuccessful();
    }

    public function test_resume_needs_the_same_live_identity_proof_and_cannot_combine_with_apply(): void
    {
        $this->fakeGraph();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertSuccessful();

        // ชื่อที่สั่งมาไม่ตรงกับชื่อที่ Facebook ตอบ → ไม่ปลด
        $this->artisan('fortune:facebook-posting-pause', [
            '--page-id' => '222', '--expected-name' => 'จันทราเมืองเลย สาขาอื่น', '--resume' => true,
        ])->assertFailed();
        $this->artisan('fortune:facebook-posting-pause', ['--page-id' => '222', '--resume' => true])->assertFailed();
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions() + ['--resume' => true])->assertFailed();

        $this->assertTrue((new FacebookPagePostingGuard)->isPaused('222'));
    }

    /**
     * cron วนหลายสาขาด้วย service ตัวเดียว — service ถูกสร้างตอนยังเห็นแถวกลาง (เพจหลัก)
     * ตัวเช็คต้นทางต้องดูเพจของสาขาที่ "กำลังทำงานให้" ไม่ใช่เพจที่จำไว้ตอนสร้าง
     */
    public function test_early_check_uses_the_branch_being_served_not_the_page_cached_at_construction(): void
    {
        DB::table('fortune_telling_settings')->update(['facebook_page_id' => '111']);
        FortuneTellingSetting::clearSettingsCache();
        Http::fake(['*' => Http::response(['id' => 'must-not-send'])]);
        $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
        $this->page->save();

        foreach ([DailyHoroscopeAutoPostService::class, MysticContentAutoPostService::class] as $class) {
            $service = app($class);
            try {
                FortunePageContext::run($this->page->fresh(), fn () => $service->generateAndPublish(1, force: true));
                $this->fail('Branch publish bypassed pause');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('publishing paused', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    public function test_admin_cannot_switch_repost_on_while_the_owner_pause_is_active(): void
    {
        $this->page->forceFill([
            'repost_enabled' => false,
            'settings_override' => [FacebookPagePostingGuard::PAUSED_KEY => true],
        ])->save();
        $controller = app(\App\Http\Controllers\Admin\FortuneRepostController::class);

        $problem = (new ReflectionMethod($controller, 'readinessProblem'))->invoke($controller, $this->page->fresh());

        $this->assertStringContainsString('ถูกสั่งหยุดโพสต์', (string) $problem);
        $this->assertStringContainsString('--resume', (string) $problem);
    }

    public function test_incomplete_pagination_never_claims_remote_queue_is_empty(): void
    {
        Http::fake([
            '*/me?*' => Http::response(['id' => '222', 'name' => 'จันทราเมืองเลย']),
            '*/222/scheduled_posts?*' => Http::response(['data' => [], 'paging' => ['next' => 'not-followed']]),
        ]);
        $this->artisan('fortune:facebook-posting-pause', $this->pauseOptions())->assertFailed();
        $this->assertTrue($this->page->fresh()->settings_override[FacebookPagePostingGuard::PAUSED_KEY]);
    }

    public function test_fresh_guard_blocks_all_publishing_edges_without_blocking_chat_or_other_pages(): void
    {
        Http::fake(['*' => Http::response(['id' => 'fake'])]);
        $staleRequest = Http::asForm();
        $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
        $this->page->save();
        foreach (['feed', 'photos', 'videos', 'video_reels', 'photo_stories', 'video_stories'] as $edge) {
            try {
                $staleRequest->post(FacebookGraphApi::url("222/{$edge}"), ['message' => 'must not send']);
                $this->fail("Paused {$edge} was sent");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('publishing paused', $e->getMessage());
            }
        }
        Http::assertNothingSent();
        Http::post(FacebookGraphApi::url('111/feed'), []);
        Http::post(FacebookGraphApi::url('me/messages'), []);
        Http::post(FacebookGraphApi::url('222/messages'), []);
        Http::delete(FacebookGraphApi::url('222_333'), []);
        Http::get(FacebookGraphApi::url('222/scheduled_posts'));
        Http::assertSentCount(5);
    }

    public function test_guard_rechecks_database_before_http_retry(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
            $this->page->save();

            return Http::response(['error' => 'retry'], 500);
        });
        try {
            Http::retry(2, 0)->post(FacebookGraphApi::url('222/feed'), []);
            $this->fail('Retry should be blocked');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('publishing paused', $e->getMessage());
        }
        $this->assertSame(1, $calls);
    }

    public static function publishers(): array
    {
        return \Tests\Feature\FacebookGraphApiVersionTest::autoPublishers();
    }

    #[DataProvider('publishers')]
    public function test_real_publisher_paths_cannot_send_to_paused_page(string $serviceClass, string $postClass, bool $image): void
    {
        $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
        $this->page->save();
        Http::fake(['*' => Http::response(['id' => 'must-not-send'])]);
        $service = app($serviceClass);
        $post = new $postClass;
        $post->forceFill(['caption' => 'Test', 'post_content' => 'Test']);
        $post->image_url = $image ? 'https://example.test/photo.jpg' : null;
        $arguments = [$post];
        if ($serviceClass === FortuneHoroscopePublishService::class) {
            $post->image_urls = $image ? ['https://example.test/photo.jpg'] : [];
            $campaign = new FortuneHoroscopeCampaign;
            $campaign->forceFill(['use_fortune_settings_tokens' => true]);
            $arguments[] = $campaign;
        }
        try {
            (new ReflectionMethod($service, 'publishToFacebook'))->invoke($service, ...$arguments);
            $this->fail('Publisher bypassed pause');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('publishing paused', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_chatbot_auto_content_and_stale_repost_are_blocked(): void
    {
        Http::fake(['*' => Http::response(['id' => 'must-not-send'])]);
        $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
        $this->page->save();
        $integration = new ChatbotPlatformIntegration;
        $integration->forceFill(['fb_page_id' => '222', 'access_token' => 'test-page-token']);
        $service = app(AutoContentService::class);
        $result = (new ReflectionMethod($service, 'postToFacebook'))->invoke($service, new ChatbotAutoContentPost, $integration, 'Test');
        $this->assertFalse($result['success']);
        try {
            (new FortuneRepostService)->run($this->page);
            $this->fail('Stale repost bypassed pause');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('publishing paused', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_force_publish_is_blocked_before_reading_or_deleting_previous_posts(): void
    {
        Http::fake(['*' => Http::response(['id' => 'must-not-send'])]);
        $this->page->settings_override = [FacebookPagePostingGuard::PAUSED_KEY => true];
        $this->page->save();
        foreach ([DailyHoroscopeAutoPostService::class, MysticContentAutoPostService::class, ContentCampaignAutoPostService::class] as $class) {
            try {
                $service = app($class);
                $class === ContentCampaignAutoPostService::class
                    ? $service->generateAndPublish(new FortuneContentCampaign, '12:00', force: true)
                    : $service->generateAndPublish(1, force: true);
                $this->fail('Force publish bypassed pause');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('publishing paused', $e->getMessage());
            }
        }
        Http::assertNothingSent();
    }
}
