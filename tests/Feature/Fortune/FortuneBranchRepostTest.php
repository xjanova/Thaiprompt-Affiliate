<?php

namespace Tests\Feature\Fortune;

use App\Models\FortunePage;
use App\Models\FortunePageRepost;
use App\Models\FortuneTellingSetting;
use App\Models\User;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\FortunePageIdentity;
use App\Services\Fortune\FortuneRepostService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 🔁 รีโพสจากเพจหลักลงเพจสาขา (2026-11-07)
 *
 * ครอบกติกาที่เจ้าของสั่ง: เวลาเหลื่อม +30 นาที · คอนเทนต์จากเพจหลักแต่ไม่ซ้ำกับเพจหลักวันเดียวกัน
 * · ปุ่มเปิด/ปิดหลังบ้าน · บอทสาขารู้เลขสาขาตัวเอง
 */
class FortuneBranchRepostTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN_TOKEN = 'EAAmaintoken1234567890';

    private const BRANCH_TOKEN = 'EAAbranchtoken1234567890';

    private FortunePage $main;

    private FortunePage $branch;

    /** @var array<int, Request> คำขอ POST/DELETE ที่ยิงไป Graph */
    private array $writes = [];

    /** ให้ POST /photos ของสาขาพังกี่ครั้งแรก */
    private int $photoFailures = 0;

    /** ให้ POST /photos ของสาขาหมดเวลา (ไม่ได้คำตอบ) กี่ครั้งแรก */
    private int $photoTimeouts = 0;

    /** false = ทำตัวเหมือน Facebook จริง: ตั้งเวลารูป (published=false) ไม่คืน post_id */
    private bool $photoReturnsPostId = true;

    protected function setUp(): void
    {
        parent::setUp();

        // ตอนนี้ = 7 พ.ย. 2026 06:00 เวลาไทย
        Carbon::setTestNow(Carbon::parse('2026-11-07 06:00', 'Asia/Bangkok'));

        $this->main = FortunePage::create([
            'code' => 'main-facebook',
            'name' => 'แม่หมอจันทรา',
            'platform' => 'facebook',
            'external_page_id' => '111',
            'page_access_token' => self::MAIN_TOKEN,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->branch = FortunePage::create([
            'code' => '5',
            'name' => 'จันทราพยากรณ์ จ.เลย - 5',
            'platform' => 'facebook',
            'external_page_id' => '222',
            'page_access_token' => self::BRANCH_TOKEN,
            'is_active' => true,
            'is_default' => false,
            'repost_enabled' => true,
            'repost_offset_minutes' => 30,
        ]);

        $this->fakeGraph();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        FortunePageContext::forget();
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-07 06:00', 'Asia/Bangkok');
    }

    private function ts(string $local): int
    {
        return CarbonImmutable::parse($local, 'Asia/Bangkok')->getTimestamp();
    }

    private function iso(string $local): string
    {
        return CarbonImmutable::parse($local, 'Asia/Bangkok')->utc()->format('Y-m-d\TH:i:sO');
    }

    /**
     * Graph ปลอม: ตารางเพจหลักวันนี้ 06:05 (ใกล้ไป) 07:00 รูป 09:00 วิดีโอ + คลังโพสย้อนหลัง
     */
    private function fakeGraph(): void
    {
        $photo = fn (string $id, string $created, string $msg, string $type = 'photo') => [
            'id' => '111_'.$id,
            'created_time' => $this->iso($created),
            'message' => $msg,
            'status_type' => 'added_photos',
            'attachments' => ['data' => [[
                'media_type' => 'photo',
                'type' => $type,
                'target' => ['id' => 'obj'.$id],
                'media' => ['image' => ['src' => "https://cdn.test/{$id}_720.jpg"]],
            ]]],
        ];

        $library = [
            // โพสของวันนี้ — ห้ามเอามาลงวันนี้ (เพจหลักเพิ่งลงไป)
            $photo('today', '2026-11-07 00:30', 'โพสวันนี้ของเพจหลัก'),
            $photo('y1', '2026-11-06 19:00', 'แผ่เมตตาก่อนนอน ปิดวันด้วยใจที่นุ่มนวล'),
            [
                'id' => '111_y2',
                'created_time' => $this->iso('2026-11-06 18:00'),
                'message' => 'ใครทำให้คุณลังเล ฟังทางนี้ค่ะ',
                'status_type' => 'added_video',
                'attachments' => ['data' => [['media_type' => 'video', 'type' => 'video_inline', 'target' => ['id' => 'vid2']]]],
            ],
            // ผูกกับวันที่ → ตัดทิ้ง
            $photo('horo', '2026-11-06 12:00', 'ดวงรายวัน 06/11/2569 โดย แม่หมอจันทรา'),
            $photo('dated', '2026-11-06 11:00', 'พรุ่งนี้ 07/11/2026 ทำบุญ'),
            // อัลบั้ม → ไม่รองรับ
            $photo('album', '2026-11-06 10:00', 'อัลบั้มหลายรูป', 'album'),
            // แชร์โพสคนอื่น → ไม่ใช่คอนเทนต์ของเรา
            ['id' => '111_shared', 'created_time' => $this->iso('2026-11-06 09:30'), 'message' => 'แชร์มา', 'status_type' => 'shared_story'],
            $photo('old', '2026-11-05 07:00', 'ปีชงคืออะไร อ่านให้เข้าใจโดยไม่ต้องกลัว'),
        ];

        Http::fake(function (Request $request) use ($library) {
            $url = $request->url();
            $path = parse_url($url, PHP_URL_PATH);

            if ($request->method() !== 'GET') {
                $this->writes[] = $request;

                if (str_ends_with($path, '/222/photos')) {
                    if ($this->photoTimeouts > 0) {
                        $this->photoTimeouts--;

                        throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
                    }

                    if ($this->photoFailures > 0) {
                        $this->photoFailures--;

                        return Http::response(['error' => ['message' => 'Invalid image (access_token='.self::BRANCH_TOKEN.')', 'code' => 324]], 400);
                    }

                    return Http::response($this->photoReturnsPostId
                        ? ['id' => 'ph'.count($this->writes), 'post_id' => '222_'.count($this->writes)]
                        : ['id' => 'ph'.count($this->writes)]);
                }

                if (str_ends_with($path, '/222/videos')) {
                    return Http::response(['id' => 'vd'.count($this->writes)]);
                }

                if (str_ends_with($path, '/222/feed')) {
                    return Http::response(['id' => '222_t'.count($this->writes)]);
                }

                if ($request->method() === 'DELETE') {
                    return Http::response(['success' => true]);
                }

                return Http::response(['error' => ['message' => 'unexpected write '.$path]], 400);
            }

            if (str_ends_with($path, '/111/scheduled_posts')) {
                return Http::response(['data' => [
                    ['id' => '111_s1', 'scheduled_publish_time' => $this->ts('2026-11-07 06:05'), 'attachments' => ['data' => [['media_type' => 'photo', 'type' => 'photo']]]],
                    ['id' => '111_s2', 'scheduled_publish_time' => $this->ts('2026-11-07 07:00'), 'attachments' => ['data' => [['media_type' => 'photo', 'type' => 'photo']]]],
                    ['id' => '111_s3', 'scheduled_publish_time' => $this->ts('2026-11-07 09:00'), 'attachments' => ['data' => [['media_type' => 'video', 'type' => 'video_inline']]]],
                    // นอกหน้าต่างวางแผน 24 ชม.
                    ['id' => '111_s4', 'scheduled_publish_time' => $this->ts('2026-11-08 09:00'), 'attachments' => ['data' => [['media_type' => 'photo', 'type' => 'photo']]]],
                ]]);
            }

            if (str_ends_with($path, '/111/published_posts')) {
                // คำขอ "ช่องเวลา" ไม่ขอ message · คำขอ "คลัง" ขอ message
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $fields = (string) ($query['fields'] ?? '');

                return Http::response(['data' => str_contains($fields, 'message') ? $library : []]);
            }

            if (preg_match('#/obj(\w+)$#', $path, $m)) {
                return Http::response(['images' => [
                    ['width' => 720, 'source' => "https://cdn.test/{$m[1]}_720.jpg"],
                    ['width' => 1536, 'source' => "https://cdn.test/{$m[1]}_full.jpg"],
                ]]);
            }

            // รูปที่ตั้งเวลาไว้ในสาขา → บอกรหัสโพสของมัน · โพสนั้นขึ้นหน้าเพจแล้ว
            if (preg_match('#/ph(\d+)$#', $path, $m)) {
                return Http::response(['id' => 'ph'.$m[1], 'page_story_id' => '222_st'.$m[1]]);
            }

            if (preg_match('#/222_st\d+$#', $path)) {
                return Http::response(['is_published' => true]);
            }

            if (str_ends_with($path, '/vid2')) {
                return Http::response(['source' => 'https://video.test/vid2.mp4']);
            }

            return Http::response(['error' => ['message' => 'unexpected GET '.$path, 'code' => 100]], 404);
        });
    }

    private function service(): FortuneRepostService
    {
        return app(FortuneRepostService::class);
    }

    private function writesTo(string $suffix): array
    {
        return array_values(array_filter($this->writes, fn (Request $r) => str_ends_with(parse_url($r->url(), PHP_URL_PATH), $suffix)));
    }

    public function test_schedules_branch_posts_thirty_minutes_after_main_slots_with_older_content(): void
    {
        $report = $this->service()->run($this->branch->fresh(), now: $this->now());

        $this->assertTrue($report['ok'], $report['message']);

        $rows = FortunePageRepost::orderBy('slot_at')->get();
        $this->assertCount(3, $rows, 'ต้นทางมี 3 ช่องใน 24 ชม. (06:05 07:00 09:00) — 08 พ.ย. 09:00 อยู่นอกหน้าต่าง');

        // ต้นทาง 06:05 รูป → สาขา 06:35 ได้รูปใหม่สุดที่ลงก่อนวันนี้
        $this->assertSame('2026-11-07 06:35', $rows[0]->slot_at->format('Y-m-d H:i'));
        $this->assertSame('111_y1', $rows[0]->source_post_id);
        $this->assertSame('photo', $rows[0]->media_type);
        $this->assertSame(FortunePageRepost::STATUS_SCHEDULED, $rows[0]->status);
        $this->assertSame('111_s1', $rows[0]->slot_source_post_id);

        // ต้นทาง 07:00 รูป → 07:30 ได้รูปถัดไป (ไม่ซ้ำใบเดิม)
        $this->assertSame('2026-11-07 07:30', $rows[1]->slot_at->format('Y-m-d H:i'));
        $this->assertSame('111_old', $rows[1]->source_post_id);

        // ต้นทาง 09:00 วิดีโอ → 09:30 ได้วิดีโอ
        $this->assertSame('2026-11-07 09:30', $rows[2]->slot_at->format('Y-m-d H:i'));
        $this->assertSame('111_y2', $rows[2]->source_post_id);
        $this->assertSame('video', $rows[2]->media_type);

        $photoPost = $this->writesTo('/222/photos')[0]->data();
        $this->assertSame('false', $photoPost['published']);
        $this->assertSame((string) $this->ts('2026-11-07 06:35'), $photoPost['scheduled_publish_time']);
        $this->assertSame('https://cdn.test/y1_full.jpg', $photoPost['url'], 'ต้องใช้รูปใหญ่สุด ไม่ใช่รูปย่อ 720px');
        $this->assertSame('แผ่เมตตาก่อนนอน ปิดวันด้วยใจที่นุ่มนวล', $photoPost['message']);
        $this->assertSame(self::BRANCH_TOKEN, $photoPost['access_token'], 'โพสลงสาขาต้องใช้ token ของสาขา');

        $videoPost = $this->writesTo('/222/videos')[0]->data();
        $this->assertSame('https://video.test/vid2.mp4', $videoPost['file_url']);
        $this->assertSame((string) $this->ts('2026-11-07 09:30'), $videoPost['scheduled_publish_time']);
    }

    public function test_never_reposts_today_main_content_or_date_bound_or_unsupported_posts(): void
    {
        $this->service()->run($this->branch->fresh(), now: $this->now());

        $used = FortunePageRepost::pluck('source_post_id')->all();

        foreach (['111_today', '111_horo', '111_dated', '111_album', '111_shared'] as $forbidden) {
            $this->assertNotContains($forbidden, $used);
        }
    }

    public function test_slot_too_close_to_now_is_skipped(): void
    {
        // 06:25 → ช่อง 06:35 เหลือ 10 นาที (Facebook ต้องการ ≥ 10 นาที + กันเวลา 2 นาที) ⇒ ข้าม
        $report = $this->service()->run($this->branch->fresh(), now: CarbonImmutable::parse('2026-11-07 06:25', 'Asia/Bangkok'));

        $this->assertTrue($report['ok']);
        $this->assertFalse(FortunePageRepost::where('slot_at', '2026-11-07 06:35:00')->exists());
    }

    public function test_running_twice_does_not_post_twice(): void
    {
        $this->service()->run($this->branch->fresh(), now: $this->now());
        $firstWrites = count($this->writes);

        $report = $this->service()->run($this->branch->fresh(), now: $this->now()->addMinutes(15));

        $this->assertSame($firstWrites, count($this->writes), 'รอบที่สองต้องไม่ยิงโพสซ้ำ');
        $this->assertSame(3, FortunePageRepost::count());
        $this->assertSame('ทุกช่องเวลามีโพสแล้ว', $report['message']);
    }

    public function test_dry_run_posts_nothing_and_works_while_switch_is_off(): void
    {
        $this->branch->update(['repost_enabled' => false]);

        $report = $this->service()->run($this->branch->fresh(), dryRun: true, force: true, now: $this->now());

        $this->assertTrue($report['ok']);
        $this->assertCount(3, $report['planned']);
        $this->assertSame([], $this->writes);
        $this->assertSame(0, FortunePageRepost::count());
    }

    public function test_command_page_option_picks_exactly_one_page(): void
    {
        // เคสจริงบน prod: มีเพจอื่นที่ id ตรงกับรหัสสาขา → --page ต้องได้แค่สาขา ไม่ใช่ทั้งคู่
        $decoy = FortunePage::create([
            'code' => 'other-page',
            'name' => 'เพจอื่นที่ไม่ใช่สาขา',
            'platform' => 'facebook',
            'external_page_id' => '333',
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->branch->update(['code' => (string) $decoy->id]);

        $this->artisan('fortune:repost-schedule', ['--page' => (string) $decoy->id, '--dry-run' => true])
            ->expectsOutputToContain($this->branch->name)
            ->doesntExpectOutputToContain($decoy->name)
            ->assertSuccessful();

        $this->artisan('fortune:repost-schedule', ['--page' => 'id:'.$decoy->id, '--dry-run' => true])
            ->expectsOutputToContain($decoy->name)
            ->doesntExpectOutputToContain($this->branch->name);

        $this->artisan('fortune:repost-schedule', ['--page' => 'ไม่มีเพจนี้', '--dry-run' => true])
            ->assertFailed();

        $this->assertSame([], $this->writes);
    }

    public function test_switch_off_means_no_posting(): void
    {
        $this->branch->update(['repost_enabled' => false]);

        $report = $this->service()->run($this->branch->fresh(), now: $this->now());

        $this->assertFalse($report['ok']);
        $this->assertSame([], $this->writes);
    }

    public function test_max_per_day_caps_posts(): void
    {
        $this->branch->update(['repost_max_per_day' => 1]);

        $this->service()->run($this->branch->fresh(), now: $this->now());

        $this->assertSame(1, FortunePageRepost::whereIn('status', FortunePageRepost::OCCUPYING)->count());
    }

    public function test_failed_post_tries_next_candidate_and_hides_token(): void
    {
        $this->photoFailures = 1;

        $this->service()->run($this->branch->fresh(), now: $this->now());

        $failed = FortunePageRepost::where('status', FortunePageRepost::STATUS_FAILED)->first();
        $this->assertNotNull($failed);
        $this->assertSame('111_y1', $failed->source_post_id);
        $this->assertStringNotContainsString(self::BRANCH_TOKEN, (string) $failed->error);

        // ช่อง 06:35 ต้องถูกเติมด้วยรูปถัดไปในรอบเดียวกัน
        $slot = FortunePageRepost::where('slot_at', '2026-11-07 06:35:00')
            ->where('status', FortunePageRepost::STATUS_SCHEDULED)
            ->first();
        $this->assertNotNull($slot);
        $this->assertSame('111_old', $slot->source_post_id);
    }

    public function test_timeout_keeps_slot_occupied_instead_of_posting_a_second_one(): void
    {
        // Facebook อาจรับโพสไปแล้วแต่ตอบไม่ทัน — ห้ามลองใบอื่นลงช่องเดิม ไม่งั้นขึ้น 2 โพสซ้อน
        $this->photoTimeouts = 1;

        $this->service()->run($this->branch->fresh(), now: $this->now());

        $first = FortunePageRepost::where('slot_at', '2026-11-07 06:35:00')->get();
        $this->assertCount(1, $first, 'ช่องที่หมดเวลาต้องมีแถวเดียว');
        $this->assertSame(FortunePageRepost::STATUS_SENDING, $first[0]->status);

        // รอบถัดไปต้องไม่เติมช่องนี้ซ้ำ
        $this->service()->run($this->branch->fresh(), now: $this->now()->addMinutes(15));
        $this->assertSame(1, FortunePageRepost::where('slot_at', '2026-11-07 06:35:00')->count());

        // ค้างเกิน 30 นาที → reconcile เปลี่ยนเป็น failed ให้แอดมินเห็น
        FortunePageRepost::where('status', FortunePageRepost::STATUS_SENDING)->update(['updated_at' => now()->subHour()]);
        $stats = $this->service()->reconcile($this->branch->fresh(), $this->now());
        $this->assertSame(1, $stats['failed']);
        $this->assertSame(FortunePageRepost::STATUS_FAILED, $first[0]->fresh()->status);
    }

    public function test_caption_suffix_and_media_filter(): void
    {
        $this->branch->update(['repost_settings' => [
            'caption_suffix' => 'แม่หมอจันทรา สาขาจังหวัดเลย',
            'media_types' => ['photo'],
        ]]);

        $this->service()->run($this->branch->fresh(), now: $this->now());

        $this->assertSame([], $this->writesTo('/222/videos'), 'ปิดวิดีโอไว้ ห้ามโพสวิดีโอ');
        $first = $this->writesTo('/222/photos')[0]->data();
        $this->assertSame("แผ่เมตตาก่อนนอน ปิดวันด้วยใจที่นุ่มนวล\n\nแม่หมอจันทรา สาขาจังหวัดเลย", $first['message']);
    }

    /**
     * เจอบน prod (2026-11-07): Facebook ไม่คืน post_id ตอนตั้งเวลารูป
     * → ต้องหารหัสโพสจากตัวรูปเอง ไม่งั้นรูปที่ขึ้นแล้วค้างสถานะ "ตั้งเวลา" ตลอดไป
     */
    public function test_reconcile_marks_photo_published_when_facebook_gave_no_post_id(): void
    {
        $this->photoReturnsPostId = false;

        $this->service()->run($this->branch->fresh(), now: $this->now());
        $row = FortunePageRepost::where('media_type', 'photo')->first();
        $this->assertNull($row->target_post_id, 'จำลองให้ตรงกับ Facebook จริง');

        $stats = $this->service()->reconcile($this->branch->fresh(), CarbonImmutable::parse('2026-11-07 10:00', 'Asia/Bangkok'));

        $row->refresh();
        $this->assertSame(FortunePageRepost::STATUS_PUBLISHED, $row->status);
        $this->assertSame('222_st'.substr($row->target_object_id, 2), $row->target_post_id);
        $this->assertGreaterThanOrEqual(1, $stats['published']);
    }

    public function test_cancel_deletes_scheduled_post_on_facebook(): void
    {
        $this->service()->run($this->branch->fresh(), now: $this->now());
        $row = FortunePageRepost::where('media_type', 'photo')->first();

        $result = $this->service()->cancel($row);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(FortunePageRepost::STATUS_CANCELLED, $row->fresh()->status);

        $deletes = array_values(array_filter($this->writes, fn (Request $r) => $r->method() === 'DELETE'));
        $this->assertCount(1, $deletes);
        $this->assertStringEndsWith('/'.$row->target_object_id, parse_url($deletes[0]->url(), PHP_URL_PATH));
    }

    public function test_admin_toggle_off_cancels_future_scheduled_posts(): void
    {
        $this->service()->run($this->branch->fresh(), now: $this->now());
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.fortune.reposts.toggle', $this->branch))
            ->assertSessionHas('success');

        $this->assertFalse($this->branch->fresh()->repost_enabled);
        $this->assertSame(0, FortunePageRepost::where('status', FortunePageRepost::STATUS_SCHEDULED)->count());
        $this->assertSame(3, FortunePageRepost::where('status', FortunePageRepost::STATUS_CANCELLED)->count());
    }

    public function test_admin_toggle_on_refuses_without_token(): void
    {
        $this->branch->update(['repost_enabled' => false, 'page_access_token' => null]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.fortune.reposts.toggle', $this->branch))
            ->assertSessionHas('error');

        $this->assertFalse($this->branch->fresh()->repost_enabled);
    }

    /**
     * 🛑 (2026-11-13) เพจที่เจ้าของสั่งหยุดโพสต์ — กดเปิดสวิตช์ต้องถูกปฏิเสธพร้อมเหตุผล
     * ปลดการหยุดแล้ว (ธงหาย) จึงเปิดได้ตามปกติ
     */
    public function test_admin_toggle_on_refuses_while_owner_pause_is_active(): void
    {
        $this->branch->update([
            'repost_enabled' => false,
            'settings_override' => [\App\Support\FacebookPagePostingGuard::PAUSED_KEY => true],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.fortune.reposts.toggle', $this->branch))
            ->assertSessionHas('error', fn ($message) => str_contains((string) $message, 'ถูกสั่งหยุดโพสต์'));

        $this->assertFalse($this->branch->fresh()->repost_enabled);

        $this->branch->update(['settings_override' => null]);

        $this->actingAs($admin)
            ->patch(route('admin.fortune.reposts.toggle', $this->branch))
            ->assertSessionHas('success');

        $this->assertTrue($this->branch->fresh()->repost_enabled);
    }

    public function test_admin_page_renders_and_saves_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.fortune.reposts.index'))
            ->assertOk()
            ->assertSee('รีโพสลงเพจสาขา')
            ->assertSee('จันทราพยากรณ์ จ.เลย - 5')
            ->assertSee('สาขา 5');

        $this->actingAs($admin)
            ->put(route('admin.fortune.reposts.update', $this->branch), [
                'repost_source_page_id' => '',
                'repost_offset_minutes' => 45,
                'repost_max_per_day' => 8,
                'pick_order' => 'random',
                'caption_suffix' => 'สาขาเลย',
                'exclude_keywords' => "ดวงรายวัน\nโปรโมชั่น",
                'media_types' => ['photo', 'video'],
                'lookback_days' => 14,
                'lookahead_hours' => 12,
            ])
            ->assertSessionHas('success');

        $fresh = $this->branch->fresh();
        $this->assertSame(45, $fresh->repost_offset_minutes);
        $this->assertSame(8, $fresh->repost_max_per_day);
        $this->assertSame('random', $fresh->repostConfig()['pick_order']);
        $this->assertSame(['ดวงรายวัน', 'โปรโมชั่น'], $fresh->repostConfig()['exclude_keywords']);

        // เพจต้นทางเป็นตัวเองไม่ได้
        $this->actingAs($admin)
            ->put(route('admin.fortune.reposts.update', $this->branch), [
                'repost_source_page_id' => $this->branch->id,
                'repost_offset_minutes' => 30,
                'repost_max_per_day' => 0,
                'pick_order' => 'newest',
                'media_types' => ['photo'],
                'lookback_days' => 30,
                'lookahead_hours' => 24,
            ])
            ->assertSessionHasErrors('repost_source_page_id');
    }

    public function test_admin_preview_returns_plan_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('admin.fortune.reposts.preview', $this->branch))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame([], $this->writes, 'ดูตัวอย่างต้องไม่โพสจริง');
    }

    public function test_branch_bot_knows_it_is_branch_five(): void
    {
        $this->assertSame('5', $this->branch->branchNumber());
        $this->assertNull($this->main->branchNumber(), 'ช่องหลักไม่มีเลขสาขา');

        $block = FortunePageContext::run($this->branch->fresh(), fn () => FortunePageIdentity::promptBlock());

        $this->assertStringContainsString('สาขา 5', $block);
        $this->assertStringContainsString('จันทราพยากรณ์ จ.เลย - 5', $block);
        $this->assertStringContainsString('https://www.facebook.com/222', $block);
        $this->assertStringContainsString('ห้ามแนะนำตัวว่าเป็น "Thaiprompt"', $block);

        $mainBlock = FortunePageContext::run($this->main->fresh(), fn () => FortunePageIdentity::promptBlock());
        $this->assertStringContainsString('ช่องหลัก', $mainBlock);
        $this->assertStringNotContainsString('สาขา 5', $mainBlock);
    }

    public function test_branch_number_from_name_or_override(): void
    {
        $page = new FortunePage(['code' => 'loei-branch', 'name' => 'แม่หมอจันทรา สาขา 12', 'is_default' => false]);
        $this->assertSame('12', $page->branchNumber());

        $page->settings_override = ['branch_no' => '7'];
        $this->assertSame('7', $page->branchNumber());

        $plain = new FortunePage(['code' => 'some-page', 'name' => 'เพจทดลอง', 'is_default' => false]);
        $this->assertNull($plain->branchNumber());
    }
}
