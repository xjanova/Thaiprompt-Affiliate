<?php

namespace Tests\Feature;

use App\Models\FortunePage;
use App\Models\FortuneWebFunnelEvent;
use App\Models\User;
use App\Services\Fortune\WebFunnelReport;
use App\Services\Fortune\WebFunnelTracker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 📈 (2026-11-14) ตัวนับเส้นทาง แชทเพจ FB → ห้องแม่หมอบนเว็บจันทรา
 *
 * เจ้าของถาม "คนกดเข้าห้องแม่หมอหรือเปล่า ทำไมไม่มียอดเลย" — ตัวนับเดิม (consumed_at ของ handoff)
 * เป็น 0 ตลอดตั้งแต่ 2026-11-04 · ไฟล์นี้ตรวจ WebFunnelTracker + WebFunnelReport + หน้าแอดมิน
 * (จุดบันทึกจริงตรวจในเทสต์ของแต่ละเส้น: FortuneWebHandoffTest / WebFortuneGatewayTest /
 *  JuntraServerChatApiTest / JuntraServerAffiliateApiTest)
 */
class FbWebFunnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-14 15:00', 'Asia/Bangkok'));
        Cache::flush();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function event(string $event, string $at, array $attrs = []): void
    {
        FortuneWebFunnelEvent::query()->insert(array_merge([
            'event' => $event,
            'created_at' => Carbon::parse($at, 'Asia/Bangkok'),
        ], $attrs));
    }

    // ============================================================
    // Tracker
    // ============================================================

    public function test_chat_is_counted_only_for_customers_from_the_fb_link_and_once_a_day(): void
    {
        WebFunnelTracker::chatted('900', 41);
        $this->assertSame(0, FortuneWebFunnelEvent::count(), 'ไม่เคยแตะลิงก์จากเพจ = ไม่นับ');

        // แตะลิงก์แล้ว → ล้างค่าลบที่แคชไว้ทันที ไม่ต้องรอ 10 นาที
        WebFunnelTracker::linkOpened('7001', 41, 4, true);
        WebFunnelTracker::chatted('900', 41);
        WebFunnelTracker::chatted('900', 41);

        $chat = FortuneWebFunnelEvent::where('event', FortuneWebFunnelEvent::CHATTED)->sole();
        $this->assertSame(41, $chat->thaiprompt_user_id);
        $this->assertSame(4, $chat->fortune_page_id);

        // วันใหม่ = นับใหม่
        Carbon::setTestNow(Carbon::parse('2026-11-15 09:00', 'Asia/Bangkok'));
        WebFunnelTracker::chatted('900', 41);
        $this->assertSame(2, FortuneWebFunnelEvent::where('event', FortuneWebFunnelEvent::CHATTED)->count());
    }

    public function test_juntra_account_wins_over_the_claimed_user_id(): void
    {
        if (! Schema::hasTable('juntra_accounts')) {
            $this->markTestSkipped('ไม่มีตาราง juntra_accounts');
        }
        $owner = User::factory()->create();
        \App\Models\JuntraAccount::query()->forceCreate(['juntra_user_id' => 901, 'user_id' => $owner->id]);
        WebFunnelTracker::linkOpened('7002', (int) $owner->id, null, false);

        // เว็บอ้าง tp อื่นที่เคยแตะลิงก์ — ต้องนับให้เจ้าของบัญชีจริง
        WebFunnelTracker::linkOpened('7003', 55, null, false);
        WebFunnelTracker::chatted('901', 55);

        $this->assertSame((int) $owner->id, FortuneWebFunnelEvent::where('event', 'chatted')->sole()->thaiprompt_user_id);
    }

    public function test_paid_counts_each_bill_once_even_after_the_cache_is_cleared(): void
    {
        WebFunnelTracker::linkOpened('7001', 41, 2, false);

        WebFunnelTracker::paid('900', 41, 5001, 39.0);
        Cache::flush();
        WebFunnelTracker::paid('900', 41, 5001, 39.0);
        WebFunnelTracker::paid('900', 41, 5002, 99.0, null, 6);

        $paid = FortuneWebFunnelEvent::where('event', 'paid')->orderBy('id')->get();
        $this->assertCount(2, $paid);
        $this->assertSame(2, $paid[0]->fortune_page_id, 'ไม่ระบุสาขา = สาขาที่แตะลิงก์');
        $this->assertSame(6, $paid[1]->fortune_page_id, 'สาขาของบิลชนะ');

        WebFunnelTracker::paid('999', 77, 5003, 39.0);
        $this->assertSame(2, FortuneWebFunnelEvent::where('event', 'paid')->count(), 'ลูกค้าเว็บทั่วไปไม่นับ');
    }

    public function test_tracker_never_throws_when_the_table_is_missing(): void
    {
        Schema::drop('fortune_web_funnel_events');

        WebFunnelTracker::boxSent('7001', 1, 'button:WEB_ROOM');
        WebFunnelTracker::linkOpened('7001', 41, 1, true);
        WebFunnelTracker::linkDead('7001', 1, 'used');
        WebFunnelTracker::chatted('900', 41);
        WebFunnelTracker::paid('900', 41, 1, 39.0);

        $this->assertFalse(Schema::hasTable('fortune_web_funnel_events'));
        $report = (new WebFunnelReport)->summary(CarbonImmutable::today(), CarbonImmutable::today());
        $this->assertFalse($report['ready']);
    }

    // ============================================================
    // Report
    // ============================================================

    private function seedTwoDays(): void
    {
        // 13 พ.ย. — คน A กดปุ่มเอง 2 ครั้ง แตะลิงก์ คุย จ่าย · คน B บอทส่งให้ ไม่แตะ · ลิงก์ตัน 1
        $this->event('box_sent', '2026-11-13 10:00', ['platform_user_id' => 'A', 'source' => 'button:WEB_ROOM', 'fortune_page_id' => 1]);
        $this->event('box_sent', '2026-11-13 10:05', ['platform_user_id' => 'A', 'source' => 'button:WEB_ROOM', 'fortune_page_id' => 1]);
        $this->event('box_sent', '2026-11-13 11:00', ['platform_user_id' => 'B', 'source' => 'gesture_emoji', 'fortune_page_id' => 1]);
        $this->event('link_opened', '2026-11-13 10:06', ['platform_user_id' => 'A', 'thaiprompt_user_id' => 1001, 'source' => 'new_account', 'fortune_page_id' => 1]);
        $this->event('link_dead', '2026-11-13 12:00', ['platform_user_id' => 'B', 'source' => 'used', 'fortune_page_id' => 1]);
        $this->event('chatted', '2026-11-13 10:10', ['thaiprompt_user_id' => 1001, 'juntra_user_ref' => '31', 'fortune_page_id' => 1]);
        $this->event('paid', '2026-11-13 10:30', ['thaiprompt_user_id' => 1001, 'juntra_user_ref' => '31', 'fortune_page_id' => 1, 'source' => 'bill:1', 'amount' => 39]);

        // 14 พ.ย. — สาขา 2: คน C ส่งข้อความในแชท แตะลิงก์ (บัญชีเดิม) ไม่คุย
        $this->event('box_sent', '2026-11-14 09:00', ['platform_user_id' => 'C', 'source' => 'web_only_gate', 'fortune_page_id' => 2]);
        $this->event('link_opened', '2026-11-14 09:02', ['platform_user_id' => 'C', 'thaiprompt_user_id' => 1002, 'source' => 'existing_account', 'fortune_page_id' => 2]);

        // นอกช่วง (12 พ.ย.) — ต้องไม่ถูกนับในช่วง 13-14
        $this->event('box_sent', '2026-11-12 23:59', ['platform_user_id' => 'Z', 'source' => 'button:WEB_ROOM', 'fortune_page_id' => 1]);
    }

    public function test_report_counts_people_per_step_daily_by_source_and_dead_reason(): void
    {
        $this->seedTwoDays();

        $r = (new WebFunnelReport)->summary(CarbonImmutable::parse('2026-11-13'), CarbonImmutable::parse('2026-11-14'));

        $people = array_column($r['funnel'], 'people', 'key');
        $this->assertSame(['box' => 3, 'pressed' => 1, 'opened' => 2, 'chatted' => 1, 'paid' => 1], $people);
        $this->assertSame(4, $r['totals']['boxes']);
        $this->assertSame(1, $r['totals']['bills']);
        $this->assertSame(39.0, $r['totals']['paid_amount']);
        $this->assertSame(1, $r['totals']['dead']);
        $this->assertStringContainsString('บัญชีใหม่ 1 คน', $r['funnel'][2]['hint']);

        // รายวัน — ใหม่สุดก่อน
        $this->assertSame(['2026-11-14', '2026-11-13'], array_column($r['daily'], 'date'));
        $this->assertSame(['date' => '2026-11-13', 'boxes' => 3, 'box_people' => 2, 'pressed' => 1, 'opened' => 1, 'dead' => 1, 'chatted' => 1, 'paid' => 1, 'amount' => 39.0], $r['daily'][1]);
        $this->assertSame(1, $r['daily'][0]['opened']);

        // จุดที่ส่ง + กี่คนเข้าเว็บได้
        $bySource = collect($r['sources'])->keyBy('source');
        $this->assertSame([2, 1, 1], [$bySource['button:WEB_ROOM']['boxes'], $bySource['button:WEB_ROOM']['people'], $bySource['button:WEB_ROOM']['opened']]);
        $this->assertSame(0, $bySource['gesture_emoji']['opened']);
        $this->assertSame('กดปุ่ม 🌙 เข้าห้องแม่หมอ', $bySource['button:WEB_ROOM']['label']);

        $this->assertSame([['reason' => 'used', 'label' => 'ลิงก์ถูกใช้ไปแล้ว (แตะซ้ำ)', 'count' => 1]], $r['dead']);
    }

    public function test_report_filters_by_branch(): void
    {
        $this->seedTwoDays();

        $r = (new WebFunnelReport)->summary(CarbonImmutable::parse('2026-11-13'), CarbonImmutable::parse('2026-11-14'), 2);

        $this->assertSame(['box' => 1, 'pressed' => 0, 'opened' => 1, 'chatted' => 0, 'paid' => 0], array_column($r['funnel'], 'people', 'key'));
        $this->assertSame([], $r['dead']);
    }

    public function test_source_labels_cover_prefixes(): void
    {
        $this->assertSame('มาจากลิงก์แนะนำ (m.me ref)', WebFunnelReport::sourceLabel('referral:abc'));
        $this->assertSame('กดปุ่ม MENU_X', WebFunnelReport::sourceLabel('button:MENU_X'));
        $this->assertSame('ส่งไลก์', WebFunnelReport::sourceLabel('gesture_like'));
        $this->assertSame('ส่ง wave', WebFunnelReport::sourceLabel('gesture_wave'));
        $this->assertSame('(ไม่ระบุ)', WebFunnelReport::sourceLabel(''));
    }

    // ============================================================
    // Admin page
    // ============================================================

    public function test_admin_page_renders_numbers_and_handles_custom_ranges(): void
    {
        $this->seedTwoDays();
        FortunePage::create(['code' => 'main', 'name' => 'แม่หมอจันทรา', 'platform' => 'facebook', 'external_page_id' => '111', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.fortune.web-funnel.index', ['from' => '2026-11-13', 'to' => '2026-11-14']))
            ->assertOk()
            ->assertSee('ยอดเข้าห้องแม่หมอ')
            ->assertSee('แตะลิงก์ เข้าเว็บได้')
            ->assertSee('กดปุ่ม 🌙 เข้าห้องแม่หมอ')
            ->assertSee('ลิงก์ถูกใช้ไปแล้ว (แตะซ้ำ)')
            ->assertSee('แม่หมอจันทรา')
            ->assertSee('39.00');

        // ใส่ช่วงกลับด้าน + เลยวันนี้ → สลับ/ตัดให้ ไม่ error
        $this->actingAs($admin)
            ->get(route('admin.fortune.web-funnel.index', ['from' => '2026-12-31', 'to' => '2026-11-13']))
            ->assertOk()
            ->assertSee('13/11/2026 – 14/11/2026', false);

        // ไม่มีข้อมูล → บอกว่าเริ่มเก็บเมื่อไร
        $this->actingAs($admin)
            ->get(route('admin.fortune.web-funnel.index', ['from' => '2026-10-01', 'to' => '2026-10-02']))
            ->assertOk()
            ->assertSee('ช่วงนี้ยังไม่มียอด');

        $this->actingAs($admin)->get(route('admin.fortune.web-funnel.index', ['days' => 5]))->assertSessionHasErrors('days');
    }

    public function test_non_admin_cannot_open_the_page(): void
    {
        $res = $this->actingAs(User::factory()->create())->get(route('admin.fortune.web-funnel.index'));

        $this->assertContains($res->status(), [302, 403], 'สมาชิกทั่วไปต้องเปิดหน้านี้ไม่ได้');
        $res->assertDontSee('เดินไปถึงขั้นไหน');
    }
}
