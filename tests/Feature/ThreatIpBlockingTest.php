<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SecurityController;
use App\Http\Middleware\CheckBlockedIp;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\Setting;
use App\Models\ThreatIp;
use App\Models\User;
use App\Services\ThreatIntelligenceService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * การบล็อกตาม threat intelligence ต้องปิดจนกว่าแอดมินจะเปิดเอง และเปิดแล้วต้องบล็อกตาม threshold จริง
 *
 * ที่มา: Setting::get ถูกเรียกแบบ 3 อาร์กิวเมนต์ ทั้งที่รับแค่ (key, default) เมื่อไม่มีแถวใน settings
 * จึงได้คำว่า 'boolean' / 'integer' กลับมา ผลคือ `90 < 'integer'` เป็น true เสมอ middleware ไม่เคยบล็อก
 * และฟอร์มตั้งค่าติ๊กทุกช่องไว้เอง กดบันทึกพร้อม threshold การบล็อกจะเริ่มทันที
 * ส่วนแถว CIDR ไม่เคยแมตช์เพราะเทียบ ip_address แบบตรงตัว
 */
class ThreatIpBlockingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Cache::flush();
        Http::fake();

        Schema::create('threat_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('threat_type')->index();
            $table->string('source', 50);
            $table->integer('confidence_score')->default(0);
            $table->text('details')->nullable();
            $table->timestamp('last_seen')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable();
            $table->string('type')->default('blacklist');
            $table->string('ip_type')->default('single');
            $table->string('ip_range_start')->nullable();
            $table->string('ip_range_end')->nullable();
            $table->string('ip_cidr')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('ip_address', 45);
            $table->string('country_code', 2)->nullable();
            $table->string('country_name', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('os', 50)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->boolean('is_vpn')->default(false);
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_tor')->default(false);
            $table->string('severity')->default('low');
            $table->text('description')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * ส่ง request ผ่าน middleware แล้วคืน HTTP status ที่ได้
     */
    private function statusFor(string $ip, string $path = '/'): int
    {
        $request = Request::create($path, 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);

        return (new CheckBlockedIp)
            ->handle($request, fn () => response('ok'))
            ->getStatusCode();
    }

    private function threat(string $ip, string $type = 'abuse', int $confidence = 90, ?string $expiresAt = '+7 days'): ThreatIp
    {
        return ThreatIp::create([
            'ip_address' => $ip,
            'threat_type' => $type,
            'source' => 'firehol',
            'confidence_score' => $confidence,
            'last_seen' => now(),
            'expires_at' => $expiresAt ? now()->modify($expiresAt) : null,
        ]);
    }

    /**
     * ตั้งค่าแบบที่ฟอร์มบันทึกจริง: checkbox เป็น boolean ส่วน threshold จากฟอร์มเป็นข้อความ
     */
    private function enable(array $checkboxes = ['threat_block_abuse'], ?string $threshold = '85'): void
    {
        Setting::set('threat_intelligence_enabled', true, 'boolean', 'security');

        foreach ($checkboxes as $checkbox) {
            Setting::set($checkbox, true, 'boolean', 'security');
        }

        if ($threshold !== null) {
            Setting::set('threat_confidence_threshold', $threshold, 'string', 'security');
        }
    }

    private function blockEvents(): int
    {
        return SecurityLog::where('event_type', 'threat_ip_blocked')->count();
    }

    public function test_without_settings_rows_nobody_is_blocked(): void
    {
        $this->threat('8.8.8.8', 'abuse', 100);
        $this->threat('9.9.9.0/24', 'abuse', 100);
        $this->threat('8.8.4.4', 'proxy', 100);

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
        $this->assertSame(200, $this->statusFor('9.9.9.9'));
        $this->assertSame(200, $this->statusFor('8.8.4.4'));
        $this->assertSame(0, $this->blockEvents());
    }

    public function test_enabling_the_master_switch_alone_blocks_nobody(): void
    {
        $this->threat('8.8.8.8', 'abuse', 100);
        Setting::set('threat_intelligence_enabled', true, 'boolean', 'security');

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
    }

    public function test_type_checkbox_without_master_switch_blocks_nobody(): void
    {
        $this->threat('8.8.8.8', 'abuse', 100);
        Setting::set('threat_block_abuse', true, 'boolean', 'security');
        Setting::set('threat_confidence_threshold', '85', 'string', 'security');

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
    }

    public function test_unticking_in_the_form_stops_blocking_at_once(): void
    {
        $this->threat('8.8.8.8', 'abuse', 90);
        $this->enable();
        $this->assertSame(403, $this->statusFor('8.8.8.8'));

        Setting::set('threat_block_abuse', false, 'boolean', 'security');
        $this->assertSame(200, $this->statusFor('8.8.8.8'), 'เอาติ๊กประเภทออกต้องหยุดบล็อก');

        Setting::set('threat_block_abuse', true, 'boolean', 'security');
        Setting::set('threat_intelligence_enabled', false, 'boolean', 'security');
        $this->assertSame(200, $this->statusFor('8.8.8.8'), 'ปิดสวิตช์หลักต้องหยุดบล็อก');
    }

    public function test_blocks_at_threshold_and_passes_just_below(): void
    {
        $this->threat('8.8.8.8', 'abuse', 85);
        $this->threat('8.8.4.4', 'abuse', 84);
        $this->enable();

        $this->assertSame(403, $this->statusFor('8.8.8.8'));
        $this->assertSame(200, $this->statusFor('8.8.4.4'));
        $this->assertSame(1, $this->blockEvents());
    }

    public function test_default_threshold_only_passes_firehol_level1_confidence(): void
    {
        $this->threat('8.8.8.8', 'abuse', 90);
        $this->threat('8.8.4.4', 'scanner', 75);
        $this->threat('1.1.1.1', 'proxy', 80);
        $this->enable(['threat_block_abuse', 'threat_block_proxy'], threshold: null);

        $this->assertSame(403, $this->statusFor('8.8.8.8'));
        $this->assertSame(200, $this->statusFor('8.8.4.4'), 'webserver feed ต้องไม่ผ่าน threshold เริ่มต้น');
        $this->assertSame(200, $this->statusFor('1.1.1.1'), 'proxies feed ต้องไม่ผ่าน threshold เริ่มต้น');
    }

    public function test_lowering_threshold_reaches_scanner_through_abuse_checkbox(): void
    {
        $this->threat('8.8.4.4', 'scanner', 75);
        $this->enable(threshold: '75');

        $this->assertSame(403, $this->statusFor('8.8.4.4'));
    }

    public function test_only_ticked_types_are_blocked(): void
    {
        $this->threat('8.8.8.8', 'proxy', 90);
        $this->enable();

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
    }

    public function test_row_of_a_disabled_type_does_not_hide_a_blockable_row(): void
    {
        // proxy confidence สูงกว่า ถ้าตัดสินจากแถวบนสุดแถวเดียว IP นี้จะรอดทั้งที่อยู่ในฟีด abuse
        $this->threat('8.8.8.8', 'proxy', 95);
        $this->threat('8.8.8.0/24', 'abuse', 90);
        $this->enable();

        $this->assertSame(403, $this->statusFor('8.8.8.8'));
    }

    public function test_ip_inside_cidr_is_blocked_and_neighbours_are_not(): void
    {
        $this->threat('203.0.113.0/24');
        $this->threat('198.51.100.8/31');
        $this->threat('45.0.0.0/12');
        $this->enable();

        $this->assertSame(403, $this->statusFor('203.0.113.200'));
        $this->assertSame(200, $this->statusFor('203.0.114.1'));
        $this->assertSame(403, $this->statusFor('198.51.100.9'));
        $this->assertSame(200, $this->statusFor('198.51.100.10'));
        $this->assertSame(403, $this->statusFor('45.15.255.255'));
        $this->assertSame(200, $this->statusFor('45.16.0.0'));
    }

    public function test_internal_addresses_listed_as_bogons_are_never_blocked(): void
    {
        foreach (['10.0.0.0/8', '127.0.0.0/8', '192.168.0.0/16', '172.16.0.0/12', '169.254.0.0/16', '100.64.0.0/10', '0.0.0.0/8'] as $bogon) {
            $this->threat($bogon, 'abuse', 90);
        }
        $this->enable();

        foreach (['10.1.2.3', '127.0.0.1', '192.168.1.1', '172.16.0.1', '169.254.1.1', '100.64.0.1', '100.127.255.255'] as $ip) {
            $this->assertSame(200, $this->statusFor($ip), "{$ip} ต้องไม่โดนบล็อก");
        }

        $this->assertSame(0, $this->blockEvents());
    }

    public function test_row_wider_than_slash_8_cannot_sweep_public_addresses(): void
    {
        $this->threat('0.0.0.0/1');
        $this->threat('224.0.0.0/3');
        $this->enable();

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
    }

    public function test_expired_row_does_not_block(): void
    {
        $this->threat('8.8.8.8', 'abuse', 90, '-1 hour');
        $this->enable();

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
    }

    public function test_whitelist_wins_over_threat_feed(): void
    {
        $this->threat('8.8.8.0/24');
        $this->enable();
        BlockedIp::create(['ip_address' => '8.8.8.8', 'type' => 'whitelist', 'ip_type' => 'single', 'is_active' => true]);

        $this->assertSame(200, $this->statusFor('8.8.8.8'));
        $this->assertSame(403, $this->statusFor('8.8.8.9'));
    }

    public function test_webhooks_skip_threat_feed_but_not_manual_blocks(): void
    {
        $this->threat('8.8.8.8');
        $this->enable();

        $this->assertSame(200, $this->statusFor('8.8.8.8', '/webhook/line/abc'));
        $this->assertSame(200, $this->statusFor('8.8.8.8', '/api/webhook/payment'));
        $this->assertSame(200, $this->statusFor('8.8.8.8', '/api/v1/sms-payment/notify'));
        $this->assertSame(200, $this->statusFor('8.8.8.8', '/payment/callback/stripe'));
        $this->assertSame(403, $this->statusFor('8.8.8.8', '/login'));

        BlockedIp::create(['ip_address' => '8.8.8.8', 'type' => 'blacklist', 'ip_type' => 'single', 'is_active' => true]);

        $this->assertSame(403, $this->statusFor('8.8.8.8', '/webhook/line/abc'));
    }

    public function test_repeated_hits_from_one_ip_log_once(): void
    {
        $this->threat('8.8.8.8');
        $this->threat('8.8.4.4');
        $this->enable();

        $this->assertSame(403, $this->statusFor('8.8.8.8'));
        $this->assertSame(403, $this->statusFor('8.8.8.8'));
        $this->assertSame(403, $this->statusFor('8.8.8.8'));
        $this->assertSame(1, $this->blockEvents());

        $this->assertSame(403, $this->statusFor('8.8.4.4'));
        $this->assertSame(2, $this->blockEvents());
    }

    public function test_blocking_a_new_ip_makes_no_outbound_http_call(): void
    {
        // hook ปกติของ SecurityLog ถาม geolocation ภายนอก บอทหลาย IP จะกิน php-fpm worker จนเว็บล่ม
        $this->threat('8.8.8.8');
        $this->enable();

        $this->assertSame(403, $this->statusFor('8.8.8.8'));

        Http::assertNothingSent();
        $this->assertSame(1, $this->blockEvents());
    }

    public function test_block_event_keeps_the_reason_for_later_review(): void
    {
        $this->threat('8.8.8.0/24', 'abuse', 90);
        $this->enable();

        $this->statusFor('8.8.8.8');

        $log = SecurityLog::where('event_type', 'threat_ip_blocked')->first();

        $this->assertSame('8.8.8.8', $log->ip_address);
        $this->assertSame('abuse', $log->metadata['threat_type']);
        $this->assertSame(90, $log->metadata['confidence_score']);
    }

    public function test_log_failure_does_not_let_the_blocked_ip_through(): void
    {
        $this->threat('8.8.8.8');
        $this->enable();
        Schema::drop('security_logs');

        $this->assertSame(403, $this->statusFor('8.8.8.8'));
    }

    public function test_oversized_user_agent_still_fits_the_log_column(): void
    {
        $this->threat('8.8.8.8');
        $this->enable();

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '8.8.8.8',
            'HTTP_USER_AGENT' => str_repeat('a', 500),
        ]);
        (new CheckBlockedIp)->handle($request, fn () => response('ok'));

        $this->assertSame(255, strlen(SecurityLog::first()->user_agent));
    }

    public function test_is_threat_ip_and_get_threat_info_understand_cidr(): void
    {
        $this->threat('203.0.113.0/24', 'abuse', 90);
        $this->threat('203.0.113.7', 'scanner', 75);

        $this->assertTrue(ThreatIp::isThreatIp('203.0.113.200'));
        $this->assertFalse(ThreatIp::isThreatIp('203.0.113.200', 91));
        $this->assertFalse(ThreatIp::isThreatIp('203.0.114.1'));
        $this->assertSame('abuse', ThreatIp::getThreatInfo('203.0.113.7')->threat_type);
        $this->assertSame('scanner', ThreatIp::getThreatInfo('203.0.113.7', 0, ['scanner'])->threat_type);
        $this->assertNull(ThreatIp::getThreatInfo('203.0.113.7', 95));
    }

    public function test_ipv6_matches_exactly_and_garbage_matches_nothing(): void
    {
        $this->threat('2001:4860:4860::8888');
        $this->enable();

        $this->assertSame(403, $this->statusFor('2001:4860:4860::8888'));
        $this->assertSame(200, $this->statusFor('2001:4860:4860::8844'));
        $this->assertSame(200, $this->statusFor('::1'));
        $this->assertSame([], ThreatIp::matchCandidates('not-an-ip'));
    }

    public function test_lookup_stays_within_a_handful_of_index_probes(): void
    {
        // ทุก request เรียก query นี้ จำนวน candidate คือจำนวนรอบที่ MySQL ต้องค้น index
        $this->assertCount(26, ThreatIp::matchCandidates('8.8.8.8'));
        $this->assertContains('8.8.8.0/24', ThreatIp::matchCandidates('8.8.8.8'));
        $this->assertContains('8.0.0.0/8', ThreatIp::matchCandidates('8.8.8.8'));
    }

    /**
     * PHP ไม่ฟ้องอาร์กิวเมนต์เกินของฟังก์ชัน userland บั๊กนี้จึงเงียบมาตลอด เทสต์นี้ต้องเป็นตัวฟ้องแทน
     */
    public function test_no_setting_get_call_passes_a_type_as_second_argument(): void
    {
        $offenders = [];

        foreach ([app_path(), base_path('routes'), resource_path('views')] as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if (preg_match("/Setting::get\(\s*'[^']+'\s*,\s*'(boolean|bool|integer|int|float|string|array|json)'\s*,/", $file->getContents())) {
                    $offenders[] = $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_settings_form_defaults_to_everything_off(): void
    {
        $form = File::get(resource_path('views/admin/security/index.blade.php'));

        foreach (['threat_intelligence_enabled', 'threat_block_proxy', 'threat_block_vpn', 'threat_block_tor', 'threat_block_abuse'] as $key) {
            $this->assertStringContainsString("Setting::get('{$key}', false)", $form);
        }

        $this->assertStringContainsString('ThreatIp::DEFAULT_CONFIDENCE_THRESHOLD', $form);
        $this->assertSame(85, ThreatIp::DEFAULT_CONFIDENCE_THRESHOLD);
    }

    public function test_update_button_only_queues_the_work(): void
    {
        Gate::define('manage-settings', fn () => true);
        $this->be(new User);

        $service = $this->mock(ThreatIntelligenceService::class);
        $service->shouldReceive('requestBackgroundUpdate')->once()->andReturn(true);
        $service->shouldNotReceive('updateAllSources');

        $request = Request::create('/admin/security/threat-intelligence/update', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(SecurityController::class)->updateThreatIntelligence($request, $service);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData()->success);
    }

    public function test_double_click_queues_one_run_and_progress_is_visible_at_once(): void
    {
        $service = app(ThreatIntelligenceService::class);

        $this->assertTrue($service->requestBackgroundUpdate());
        $this->assertFalse($service->requestBackgroundUpdate());
        $this->assertSame(0, $service->getProgress()['percentage']);
    }

    public function test_cannot_queue_while_a_run_is_in_progress_but_can_after_it_finished(): void
    {
        $service = app(ThreatIntelligenceService::class);

        Cache::put('threat_update_progress', ['percentage' => 60, 'message' => 'x', 'updated_at' => now()->toIso8601String()], 300);
        $this->assertFalse($service->requestBackgroundUpdate());

        Cache::put('threat_update_progress', ['percentage' => 100, 'message' => 'x', 'updated_at' => now()->toIso8601String()], 300);
        $this->assertTrue($service->requestBackgroundUpdate());
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function threatUpdateEvents()
    {
        return collect($this->app->make(Schedule::class)->events())
            ->filter(fn (Event $event) => str_contains((string) $event->command, 'threat:update'));
    }

    public function test_scheduler_runs_the_button_request_exactly_once(): void
    {
        $event = $this->threatUpdateEvents()->firstWhere('description', 'threat-update-manual');

        $this->assertNotNull($event, 'ต้องลงทะเบียน event ของปุ่มแม้ปิด auto update');
        $this->assertTrue($event->runInBackground);
        $this->assertTrue($event->withoutOverlapping);

        $this->assertFalse($event->filtersPass($this->app), 'ไม่มีคำสั่งจากปุ่ม ต้องไม่รัน');

        app(ThreatIntelligenceService::class)->requestBackgroundUpdate();

        $this->assertTrue($event->filtersPass($this->app));
        $this->assertFalse($event->filtersPass($this->app), 'กดหนึ่งครั้งต้องได้งานหนึ่งรอบ');
    }

    public function test_button_and_timed_runs_share_one_mutex(): void
    {
        $this->assertCount(1, $this->threatUpdateEvents()->map(fn (Event $event) => $event->mutexName())->unique());
    }
}
