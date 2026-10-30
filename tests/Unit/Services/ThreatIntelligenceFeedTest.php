<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Admin\SecurityController;
use App\Models\ThreatIp;
use App\Models\User;
use App\Services\ThreatIntelligenceService;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * threat:update ต้องกินหน่วยความจำคงที่ไม่ว่าฟีดจะใหญ่แค่ไหน และล้มกลางทางแล้วรายการเดิมต้องอยู่ครบ
 *
 * ที่มา: ฟีด proxies 41 MB (2.9 ล้านบรรทัด) ทำ explode() ชนเพดาน 128M ที่ CLI production
 * ทุกคืนที่โหลดทัน จนฟีดนี้ไม่เคยลง DB และลิสต์ webserver ที่รันต่อจากมันค้างไปด้วย
 */
class ThreatIntelligenceFeedTest extends TestCase
{
    /**
     * ไฟล์ชั่วคราวที่มีอยู่ก่อนเทสต์ — ของเก่าที่ค้างในเครื่องต้องไม่ทำให้เทสต์ล้ม
     *
     * @var array<int, string>
     */
    private array $feedFilesBeforeTest = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Cache::flush();
        Http::preventStrayRequests();
        $this->feedFilesBeforeTest = $this->feedTempFiles();

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
    }

    private function runUniqueIndexMigration(): void
    {
        $migration = require base_path('database/migrations/2026_10_31_090000_add_unique_ip_source_to_threat_ips_table.php');
        $migration->up();
    }

    /**
     * ตั้งเนื้อหาของแต่ละลิสต์ ค่า null = ปลายทางตอบ 503
     *
     * สร้าง response ใหม่ทุกครั้ง: sink ของ fake อ่าน stream จนหมด ถ้าใช้ตัวเดิมรอบถัดไปจะได้ body ว่าง
     *
     * @param  array{level1: ?string, proxies: ?string, webserver: ?string}  $bodies
     */
    private function fakeFeeds(array $bodies): void
    {
        Http::fake(function ($request) use ($bodies) {
            foreach ($bodies as $list => $body) {
                if (str_contains($request->url(), "firehol_{$list}.netset")) {
                    return $body === null ? Http::response('', 503) : Http::response($body);
                }
            }

            return Http::response('', 404);
        });
    }

    /**
     * ฟีดเล็ก ๆ ที่ทุกบรรทัดใช้ได้ ขึ้นต้นด้วย octet ที่กำหนดเพื่อไม่ให้ชนกันข้ามลิสต์
     */
    private function feedOf(int $count, int $firstOctet): string
    {
        $lines = ['# Firehol header', ''];

        for ($i = 0; $i < $count; $i++) {
            $lines[] = sprintf('%d.%d.%d.%d', $firstOctet, ($i >> 16) & 255, ($i >> 8) & 255, $i & 255);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * เขียนไฟล์ฟีดลงดิสก์ทีละบรรทัด เพื่อให้ตัวเทสต์เองไม่ถือฟีดทั้งก้อนในหน่วยความจำ
     */
    private function writeFeedFile(int $count): string
    {
        $path = tempnam(sys_get_temp_dir(), 'threat_feed_test_');
        $handle = fopen($path, 'w');
        fwrite($handle, "# Entries : {$count} subnets\n");

        for ($i = 0; $i < $count; $i++) {
            fwrite($handle, sprintf("%d.%d.%d.%d\n", 10 + ($i >> 24), ($i >> 16) & 255, ($i >> 8) & 255, $i & 255));
        }

        fclose($handle);

        return $path;
    }

    /**
     * @return array<int, string>
     */
    private function feedTempFiles(): array
    {
        return glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'firehol_*') ?: [];
    }

    private function assertNoFeedFileLeftBehind(): void
    {
        $this->assertSame([], array_values(array_diff($this->feedTempFiles(), $this->feedFilesBeforeTest)));
    }

    public function test_large_feed_is_processed_in_batches_without_holding_the_list_in_memory(): void
    {
        $feedLines = 1_000_000;
        $feedPath = $this->writeFeedFile($feedLines);

        // แทนที่ทั้งการโหลดและการเขียน DB เพื่อวัดเฉพาะหน่วยความจำของงานเอง
        $service = new class($feedPath) extends ThreatIntelligenceService
        {
            public int $largestBatch = 0;

            public function __construct(private string $feedPath) {}

            protected function downloadFeed(string $url, string $path): Response
            {
                copy($this->feedPath, $path);

                return new Response(new Psr7Response(200));
            }

            protected function storeThreatBatch(array $ips, string $type, int $confidence, string $source): int
            {
                $this->largestBatch = max($this->largestBatch, count($ips));

                return count($ips);
            }
        };

        $baseline = memory_get_usage();
        memory_reset_peak_usage();

        try {
            $results = $service->updateFromFirehol();
            $peakGrowth = memory_get_peak_usage() - $baseline;
        } finally {
            unlink($feedPath);
        }

        $this->assertSame($feedLines, $results['proxies']['updated']);
        $this->assertSame(1000, $service->largestBatch);
        $this->assertLessThan(
            4 * 1024 * 1024,
            $peakGrowth,
            'ฟีด 14 MB ต้องไม่ถูกถือไว้ทั้งก้อน — หน่วยความจำต้องขึ้นกับขนาด batch ไม่ใช่ขนาดฟีด'
        );
        $this->assertNoFeedFileLeftBehind();
    }

    public function test_feed_lands_in_database_and_rerun_updates_instead_of_duplicating(): void
    {
        $this->runUniqueIndexMigration();
        $this->fakeFeeds([
            'level1' => "# comment\n1.2.3.0/24\nnot-an-ip\n1.2.3.4/99\n\n5.6.7.8\n",
            'proxies' => $this->feedOf(2500, 20),
            'webserver' => $this->feedOf(3, 30),
        ]);
        $service = app(ThreatIntelligenceService::class);

        $this->travelTo(now()->subDay());
        $first = $service->updateFromFirehol();
        $this->travelBack();

        $this->assertSame(2, $first['level1']['updated']);
        $this->assertSame(2500, $first['proxies']['updated']);
        $this->assertSame(2505, ThreatIp::count());
        $this->assertSame('proxy', ThreatIp::where('ip_address', '20.0.0.1')->value('threat_type'));
        $this->assertTrue(ThreatIp::where('ip_address', '1.2.3.0/24')->exists());

        $firstExpiry = ThreatIp::where('ip_address', '20.0.0.1')->value('expires_at');

        $second = $service->updateFromFirehol();

        $this->assertSame(2500, $second['proxies']['updated']);
        $this->assertSame(2505, ThreatIp::count(), 'รันซ้ำต้องอัปเดตแถวเดิม ไม่ใช่เพิ่มแถวซ้ำ');
        $this->assertGreaterThan(
            $firstExpiry,
            ThreatIp::where('ip_address', '20.0.0.1')->value('expires_at'),
            'แถวที่ยังอยู่ในฟีดต้องถูกต่ออายุ'
        );
        $this->assertNoFeedFileLeftBehind();
    }

    public function test_rows_written_by_other_sources_survive_the_batch_upsert(): void
    {
        $this->runUniqueIndexMigration();
        ThreatIp::create([
            'ip_address' => '20.0.0.1',
            'source' => 'abuseipdb',
            'threat_type' => 'abuse',
            'confidence_score' => 55,
            'details' => ['total_reports' => 3],
            'last_seen' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        $this->fakeFeeds([
            'level1' => '1.1.1.1',
            'proxies' => $this->feedOf(5, 20),
            'webserver' => '3.3.3.3',
        ]);

        app(ThreatIntelligenceService::class)->updateFromFirehol();

        $other = ThreatIp::where('ip_address', '20.0.0.1')->where('source', 'abuseipdb')->first();
        $this->assertSame(55, $other->confidence_score);
        $this->assertSame(['total_reports' => 3], $other->details);
        $this->assertSame(1, ThreatIp::where('ip_address', '20.0.0.1')->where('source', 'firehol')->count());
    }

    public function test_database_failure_mid_feed_keeps_existing_rows_and_later_lists_and_rerun_heals(): void
    {
        $this->runUniqueIndexMigration();

        // รายการจากรอบที่แล้ว: แถวนี้อยู่ใน batch หลังจุดที่จะล้ม จึงต้องไม่โดนแตะและยังใช้งานได้
        $previousExpiry = now()->addDays(2)->startOfSecond();
        ThreatIp::create([
            'ip_address' => '20.0.8.0',
            'source' => 'firehol',
            'threat_type' => 'proxy',
            'confidence_score' => 80,
            'last_seen' => now()->subDays(5),
            'expires_at' => $previousExpiry,
        ]);

        $this->fakeFeeds([
            'level1' => $this->feedOf(10, 10),
            'proxies' => $this->feedOf(3000, 20),
            'webserver' => $this->feedOf(10, 30),
        ]);

        // ให้ DB ปฏิเสธจริงกลาง batch ที่สอง (IP ลำดับที่ 1500 = 20.0.5.220)
        DB::statement("CREATE TRIGGER threat_feed_fail BEFORE INSERT ON threat_ips
            WHEN NEW.ip_address = '20.0.5.220'
            BEGIN SELECT RAISE(ABORT, 'simulated disk full'); END");

        $service = app(ThreatIntelligenceService::class);
        $failed = $service->updateFromFirehol();

        $this->assertSame(1000, $failed['proxies']['updated'], 'ต้องรายงานยอดที่เขียนลง DB จริงก่อนล้ม');
        $this->assertStringContainsString('simulated disk full', $failed['proxies']['error']);
        $this->assertLessThanOrEqual(303, mb_strlen($failed['proxies']['error']), 'ห้ามคาย SQL ทั้ง batch ออกไป');
        $this->assertTrue($failed['level1']['success']);
        $this->assertTrue($failed['webserver']['success'], 'ลิสต์ที่ตามหลังต้องไม่ถูกลากล้มไปด้วย');

        $survivor = ThreatIp::where('ip_address', '20.0.8.0')->first();
        $this->assertTrue($survivor->expires_at->equalTo($previousExpiry), 'แถวที่ยังไม่ถึงคิวต้องคงอายุเดิม');
        $this->assertTrue(ThreatIp::isThreatIp('20.0.8.0'), 'รายการเดิมต้องยังใช้บล็อกได้ระหว่างรอรอบถัดไป');
        $this->assertSame(1001, ThreatIp::where('threat_type', 'proxy')->count());
        $this->assertNoFeedFileLeftBehind();

        DB::statement('DROP TRIGGER threat_feed_fail');
        $healed = $service->updateFromFirehol();

        $this->assertSame(3000, $healed['proxies']['updated']);
        $this->assertSame(3000, ThreatIp::where('threat_type', 'proxy')->count(), 'รอบซ่อมต้องไม่สร้างแถวซ้ำ');
    }

    public function test_download_failure_leaves_the_list_untouched_and_runs_the_next_list(): void
    {
        $this->runUniqueIndexMigration();
        $previousExpiry = now()->addDays(3)->startOfSecond();
        ThreatIp::create([
            'ip_address' => '20.0.0.1',
            'source' => 'firehol',
            'threat_type' => 'proxy',
            'confidence_score' => 80,
            'last_seen' => now()->subDays(4),
            'expires_at' => $previousExpiry,
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'firehol_proxies')) {
                return new RejectedPromise(new ConnectionException('cURL error 28: Operation timed out'));
            }

            return Http::response($this->feedOf(3, str_contains($request->url(), 'level1') ? 10 : 30));
        });

        $results = app(ThreatIntelligenceService::class)->updateFromFirehol();

        $this->assertStringContainsString('cURL error 28', $results['proxies']['error']);
        $this->assertSame(0, $results['proxies']['updated']);
        $this->assertTrue($results['webserver']['success']);
        $this->assertTrue(
            ThreatIp::where('ip_address', '20.0.0.1')->first()->expires_at->equalTo($previousExpiry)
        );
        $this->assertNoFeedFileLeftBehind();
    }

    public function test_download_uses_a_timeout_long_enough_for_the_proxies_feed(): void
    {
        $this->runUniqueIndexMigration();
        $timeouts = [];
        Http::fake(function ($request, array $options) use (&$timeouts) {
            $timeouts[] = $options['timeout'];

            return Http::response('1.1.1.1');
        });

        app(ThreatIntelligenceService::class)->updateFromFirehol();

        // production เคยโหลด 41 MB ได้แค่ 14.6 MB ใน 30 วินาที จนลิสต์นี้ล้มทุกคืนที่ไม่ OOM
        $this->assertSame([180, 180, 180], $timeouts);
    }

    public function test_html_error_page_with_status_200_is_reported_as_failure(): void
    {
        $this->runUniqueIndexMigration();
        $maintenancePage = "<html>\n<body>maintenance</body>\n</html>\n";
        $this->fakeFeeds(['level1' => $maintenancePage, 'proxies' => $maintenancePage, 'webserver' => $maintenancePage]);

        $results = app(ThreatIntelligenceService::class)->updateFromFirehol();

        foreach (['level1', 'proxies', 'webserver'] as $list) {
            $this->assertStringContainsString('ไม่มีรายการ IP', $results[$list]['error']);
        }
        $this->assertSame(0, ThreatIp::count());
    }

    public function test_line_without_newline_cannot_pull_the_whole_body_into_one_read(): void
    {
        $this->runUniqueIndexMigration();
        $garbage = tempnam(sys_get_temp_dir(), 'threat_feed_test_');
        file_put_contents($garbage, str_repeat('x', 2 * 1024 * 1024)."\n9.9.9.9\n");

        $service = new class extends ThreatIntelligenceService
        {
            public function batchesOf(string $path): array
            {
                return iterator_to_array($this->readFeedBatches($path), false);
            }
        };

        try {
            $batches = $service->batchesOf($garbage);
        } finally {
            unlink($garbage);
        }

        $this->assertSame([['9.9.9.9']], $batches);
    }

    public function test_command_exits_with_failure_when_a_list_fails_but_still_cleans_expired_rows(): void
    {
        $this->runUniqueIndexMigration();
        ThreatIp::create([
            'ip_address' => '9.9.9.9',
            'source' => 'firehol',
            'threat_type' => 'proxy',
            'confidence_score' => 80,
            'last_seen' => now()->subDays(10),
            'expires_at' => now()->subDays(3),
        ]);
        $this->fakeFeeds([
            'level1' => '1.1.1.1',
            'proxies' => null,
            'webserver' => '3.3.3.3',
        ]);

        $this->artisan('threat:update')->assertExitCode(1);

        $this->assertFalse(ThreatIp::where('ip_address', '9.9.9.9')->exists());
        $this->assertSame(2, ThreatIp::count());
    }

    public function test_command_with_source_option_completes_and_cleans_up(): void
    {
        $this->runUniqueIndexMigration();
        $this->fakeFeeds(['level1' => '1.1.1.1', 'proxies' => '2.2.2.2', 'webserver' => '3.3.3.3']);

        $this->artisan('threat:update', ['--source' => 'firehol'])->assertExitCode(0);

        $this->assertSame(3, ThreatIp::count());
    }

    public function test_admin_page_takes_distribution_from_cached_statistics(): void
    {
        $this->seedPageRows();
        $service = app(ThreatIntelligenceService::class);
        $service->getStatistics();

        DB::enableQueryLog();
        $data = $this->openThreatPage(Request::create('/admin/security/threat-intelligence'), $service);
        $queries = implode("\n", array_column(DB::getQueryLog(), 'query'));

        $this->assertStringNotContainsString('group by', strtolower($queries));
        $this->assertStringNotContainsString('distinct', strtolower($queries));

        $this->assertSame('proxy', $data['threatsByType']->first()->threat_type);
        $this->assertSame(3, $data['threatsByType']->first()->count);
        $this->assertSame('firehol', $data['threatsBySource']->first()->source);
        $this->assertSame(['abuse', 'proxy'], $data['threatTypes']->all());
        $this->assertSame(['abuseipdb', 'firehol'], $data['sources']->all());
    }

    public function test_admin_page_defaults_to_newest_rows_and_caps_page_size(): void
    {
        $this->seedPageRows();
        $service = app(ThreatIntelligenceService::class);

        $data = $this->openThreatPage(
            Request::create('/admin/security/threat-intelligence', 'GET', ['per_page' => 9_999_999]),
            $service
        );

        $this->assertSame(100, $data['recentThreats']->perPage());
        $this->assertSame('11.0.0.1', $data['recentThreats']->first()->ip_address);
    }

    public function test_statistics_stay_cached_across_the_daily_run(): void
    {
        $this->seedPageRows();
        $service = app(ThreatIntelligenceService::class);
        $service->getStatistics();

        $this->travel(25)->hours();
        ThreatIp::query()->delete();

        $this->assertSame(3, $service->getStatistics()['by_type']['proxy']);
    }

    /**
     * proxy 3 แถวจาก firehol และ abuse 1 แถวจาก abuseipdb ซึ่งเป็นแถวล่าสุด
     */
    private function seedPageRows(): void
    {
        $this->runUniqueIndexMigration();

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            ThreatIp::create([
                'ip_address' => $ip,
                'source' => 'firehol',
                'threat_type' => 'proxy',
                'confidence_score' => 80,
                'last_seen' => now(),
                'expires_at' => now()->addDays(7),
            ]);
        }

        ThreatIp::create([
            'ip_address' => '11.0.0.1',
            'source' => 'abuseipdb',
            'threat_type' => 'abuse',
            'confidence_score' => 60,
            'last_seen' => now()->subDays(2),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * @return array<string, mixed> ข้อมูลที่ส่งให้ view
     */
    private function openThreatPage(Request $request, ThreatIntelligenceService $service): array
    {
        Gate::define('manage-settings', fn () => true);
        $this->be(new User);

        return app(SecurityController::class)->threatIntelligence($request, $service)->getData();
    }

    public function test_clean_expired_removes_more_rows_than_one_delete_batch(): void
    {
        $now = now();
        foreach (array_chunk(range(1, 10_005), 500) as $chunk) {
            ThreatIp::insert(array_map(fn (int $i) => [
                'ip_address' => "ip-{$i}",
                'source' => 'firehol',
                'threat_type' => 'proxy',
                'confidence_score' => 80,
                'last_seen' => $now,
                'expires_at' => $now->copy()->subDay(),
            ], $chunk));
        }
        ThreatIp::create([
            'ip_address' => 'still-active',
            'source' => 'firehol',
            'threat_type' => 'proxy',
            'confidence_score' => 80,
            'last_seen' => $now,
            'expires_at' => $now->copy()->addDay(),
        ]);

        $deleted = app(ThreatIntelligenceService::class)->cleanExpired();

        $this->assertSame(10_005, $deleted);
        $this->assertSame(['still-active'], ThreatIp::pluck('ip_address')->all());
    }

    public function test_migration_keeps_the_newest_duplicate_and_is_safe_to_rerun(): void
    {
        $base = [
            'ip_address' => '7.7.7.7',
            'source' => 'firehol',
            'threat_type' => 'proxy',
            'confidence_score' => 80,
            'last_seen' => now(),
        ];
        ThreatIp::insert($base);
        ThreatIp::insert([...$base, 'confidence_score' => 90]);
        ThreatIp::insert([...$base, 'source' => 'abuseipdb']);

        $this->runUniqueIndexMigration();
        $this->runUniqueIndexMigration();

        $this->assertSame(2, ThreatIp::count());
        $this->assertSame(90, ThreatIp::where('source', 'firehol')->value('confidence_score'));
    }
}
