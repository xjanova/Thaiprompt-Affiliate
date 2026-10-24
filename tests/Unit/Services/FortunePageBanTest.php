<?php

namespace Tests\Unit\Services;

use App\Console\Commands\FortuneSyncPageBans;
use App\Models\FortunePage;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserBan;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneBanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class FortunePageBanTest extends TestCase
{
    private FortuneBanService $service;

    private FacebookWebhookService $facebook;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Cache::flush();
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        Http::preventStrayRequests();

        Schema::create('fortune_user_bans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fortune_page_id')->nullable();
            $table->string('platform');
            $table->string('platform_user_id');
            $table->string('display_name')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->unsignedBigInteger('banned_by')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->integer('notify_count')->default(0);
            $table->integer('attempt_count')->default(0);
            $table->timestamps();
            $table->unique(['platform', 'platform_user_id']);
        });
        $migration = require base_path('database/migrations/2026_10_24_120000_add_page_block_status_to_fortune_user_bans.php');
        $migration->up();
        $migration->up(); // รันซ้ำต้องไม่ทำให้ migration พัง

        Schema::create('fortune_readings', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->string('platform');
            $table->string('facebook_user_id');
            $table->unsignedBigInteger('fortune_page_id')->nullable();
        });

        $this->facebook = Mockery::mock(FacebookWebhookService::class);
        $this->service = Mockery::mock(FortuneBanService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->service->shouldReceive('facebookForBan')->andReturn($this->facebook);
        $this->app->instance(FortuneBanService::class, $this->service);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    /** ทดสอบคำสั่งนี้โดยตรง ไม่สร้างบริการของคำสั่งอื่นทั้งระบบ */
    private function syncBans(bool $execute = false): void
    {
        $command = new FortuneSyncPageBans;
        $command->setLaravel($this->app);
        $this->assertSame(0, $command->run(new ArrayInput($execute ? ['--execute' => true] : []), new BufferedOutput));
    }

    public function test_facebook_temporary_ban_blocks_page_and_unblocks_when_expired(): void
    {
        $this->facebook->shouldReceive('blockPageUser')->once()->with('123')->andReturnTrue();
        $this->facebook->shouldReceive('unblockPageUser')->once()->with('123')->andReturnTrue();

        $ban = $this->service->ban('facebook', '123', 60);
        $this->assertTrue($ban->isFullyEnforced(), $ban->page_block_error ?? '');
        $this->assertSame('blocked', $ban->fresh()->page_block_status);
        $this->assertFalse($this->service->shouldNotify($ban));
        $this->travel(61)->minutes();
        $this->assertSame(1, $this->service->cleanupExpired());
        $this->assertNull($ban->fresh());
        $this->assertFalse($this->service->isBanned('facebook', '123'));
    }

    public function test_failed_block_keeps_bot_guard_and_retries_after_cooldown(): void
    {
        $this->facebook->lastFetchError = 'permission denied';
        $this->facebook->shouldReceive('blockPageUser')->twice()->with('123')->andReturn(false, true);
        $ban = $this->service->ban('facebook', '123');
        $this->assertFalse($ban->isFullyEnforced());
        $this->assertSame('failed', $ban->fresh()->page_block_status);
        $this->assertTrue($this->service->isBanned('facebook', '123'));

        $this->syncBans(true);
        $this->assertSame('failed', $ban->fresh()->page_block_status);
        $this->travel(16)->minutes();
        $this->syncBans();
        $this->assertSame('failed', $ban->fresh()->page_block_status);
        $this->syncBans(true);
        $this->assertSame('blocked', $ban->fresh()->page_block_status);
        $this->assertNull($ban->fresh()->page_block_error);
    }

    public function test_failed_manual_unblock_keeps_record_and_is_retried_instead_of_reblocked(): void
    {
        $this->facebook->shouldReceive('blockPageUser')->once()->andReturnTrue();
        $this->facebook->shouldReceive('unblockPageUser')->twice()->andReturn(false, true);
        $ban = $this->service->ban('facebook', '123');

        $this->assertFalse($this->service->unban('facebook', '123'));
        $this->assertSame('unblock_failed', $ban->fresh()->page_block_status);
        $this->travel(16)->minutes();
        $this->syncBans(true);
        $this->assertNull($ban->fresh());
    }

    public function test_legacy_active_bans_are_synced_without_changing_duration(): void
    {
        $this->facebook->shouldReceive('blockPageUser')->once()->with('legacy')->andReturnTrue();
        $ban = FortuneUserBan::create(['platform' => 'facebook', 'platform_user_id' => 'legacy', 'banned_until' => now()->addDays(3)]);
        $until = $ban->banned_until->toDateTimeString();
        DB::table('fortune_readings')->insert(['platform' => 'facebook', 'facebook_user_id' => 'legacy', 'fortune_page_id' => 9]);

        $this->syncBans(true);
        $this->assertSame('blocked', $ban->fresh()->page_block_status);
        $this->assertSame(9, (int) $ban->fresh()->fortune_page_id);
        $this->assertSame($until, $ban->fresh()->banned_until->toDateTimeString());
    }

    public function test_other_platforms_never_call_facebook(): void
    {
        $this->facebook->shouldNotReceive('blockPageUser');
        $this->facebook->shouldNotReceive('unblockPageUser');
        $ban = $this->service->ban('line', 'U123', 60);
        $this->assertTrue($ban->isFullyEnforced());
        $this->assertTrue($this->service->shouldNotify($ban));
        $this->assertTrue($this->service->unban('line', 'U123'));
    }

    public function test_short_ban_is_released_without_waiting_for_failure_retry_cooldown(): void
    {
        $this->facebook->shouldReceive('blockPageUser')->once()->andReturnTrue();
        $this->facebook->shouldReceive('unblockPageUser')->once()->andReturnTrue();
        $ban = $this->service->ban('facebook', '123', 1);
        $this->travel(2)->minutes();

        $this->syncBans(true);
        $this->assertNull($ban->fresh());
    }

    public function test_expired_legacy_bot_only_ban_does_not_unblock_unrelated_page_bans(): void
    {
        $this->facebook->shouldNotReceive('unblockPageUser');
        $ban = FortuneUserBan::create(['platform' => 'facebook', 'platform_user_id' => 'legacy', 'banned_until' => now()->subDay()]);

        $this->syncBans(true);
        $this->assertNull($ban->fresh());
    }

    public function test_page_credentials_follow_ban_record_and_restore_callers_context(): void
    {
        Schema::create('fortune_telling_settings', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->string('facebook_page_id');
            $table->string('facebook_page_token');
        });
        DB::table('fortune_telling_settings')->insert(['facebook_page_id' => 'main', 'facebook_page_token' => 'main-token']);
        Schema::create('fortune_pages', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->string('external_page_id');
            $table->string('page_access_token')->nullable();
            $table->softDeletes();
        });
        DB::table('fortune_pages')->insert([
            ['id' => 1, 'platform' => 'facebook', 'external_page_id' => 'branch-a', 'page_access_token' => 'token-a'],
            ['id' => 2, 'platform' => 'facebook', 'external_page_id' => 'branch-b', 'page_access_token' => 'token-b'],
            ['id' => 3, 'platform' => 'facebook', 'external_page_id' => 'missing-token', 'page_access_token' => null],
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['success' => true])]);
        FortunePageContext::set(FortunePage::findOrFail(1));
        $service = new FortuneBanService;
        $ban = $service->ban('facebook', 'branch-user', 60);
        $this->assertTrue($ban->isFullyEnforced(), $ban->page_block_error ?? '');
        $this->assertSame(1, (int) $ban->fortune_page_id);

        FortunePageContext::set(FortunePage::findOrFail(2));
        $this->assertTrue($service->unban('facebook', 'branch-user'));
        $this->assertSame(2, FortunePageContext::currentId());
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/branch-a/blocked') && $r['access_token'] === 'token-a');
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/branch-a/blocked') && $r['access_token'] === 'token-a');

        FortunePageContext::set(FortunePage::findOrFail(3));
        $missing = $service->ban('facebook', 'no-token');
        $this->assertFalse($missing->isFullyEnforced());
        Http::assertSentCount(2); // ห้ามใช้ token ของเพจหลักแทนสาขาที่ไม่ได้ตั้ง token
    }

    public function test_api_exception_is_saved_as_failed_without_losing_bot_ban(): void
    {
        $this->facebook->shouldReceive('blockPageUser')->once()->andThrow(new \RuntimeException('network timeout'));
        $ban = $this->service->ban('facebook', '123');

        $this->assertSame('failed', $ban->page_block_status);
        $this->assertStringContainsString('network timeout', $ban->page_block_error);
        $this->assertTrue($this->service->isBanned('facebook', '123'));
    }

    public function test_bans_keep_working_during_deployment_before_status_migration(): void
    {
        $migration = require base_path('database/migrations/2026_10_24_120000_add_page_block_status_to_fortune_user_bans.php');
        $migration->down();
        $this->facebook->shouldReceive('blockPageUser')->twice()->with('123')->andReturnTrue();

        $ban = $this->service->ban('facebook', '123');
        $this->assertTrue($ban->isFullyEnforced());
        $this->assertNotNull($ban->fresh());
        $this->assertNull($ban->fresh()->page_block_status);

        $migration->up();
        $this->syncBans(true);
        $this->assertSame('blocked', $ban->fresh()->page_block_status);
    }
}
