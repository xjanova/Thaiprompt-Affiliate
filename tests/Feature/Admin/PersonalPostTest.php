<?php

namespace Tests\Feature\Admin;

use App\Jobs\PreparePersonalPostsJob;
use App\Models\AiApiKey;
use App\Models\PersonalPostDraft;
use App\Models\User;
use App\Services\MenuService;
use App\Services\PersonalPost\PersonalPostPrepService;
use App\Services\PersonalPost\TrendSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ทดสอบระบบเตรียมโพสต์เฟซบุ๊กส่วนตัว (เมนู · หน้า admin · เตรียมโพสต์ · ปุ่มต่างๆ)
 */
class PersonalPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * ฟีดปลอม: เทรนด์ 3 อัน (1 อันเป็นข่าวอุบัติเหตุ) + ข่าวเทค Atom 2 + RSS 2
     */
    protected function fakeFeeds(): void
    {
        $trends = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:ht="https://trends.google.com/trending/rss" version="2.0"><channel>
<item><title>disney+</title><ht:approx_traffic>200+</ht:approx_traffic><pubDate>Tue, 10 Nov 2026 21:20:00 -0800</pubDate>
  <ht:news_item><ht:news_item_title>Disney+ Brings 3D to Meta Quest</ht:news_item_title><ht:news_item_url>https://example.com/disney</ht:news_item_url><ht:news_item_source>Disney</ht:news_item_source></ht:news_item>
</item>
<item><title>อุบัติเหตุรถชน</title><ht:approx_traffic>500+</ht:approx_traffic><pubDate>Tue, 10 Nov 2026 21:00:00 -0800</pubDate></item>
<item><title>ticketmaster</title><ht:approx_traffic>100+</ht:approx_traffic><pubDate>Tue, 10 Nov 2026 20:00:00 -0800</pubDate></item>
</channel></rss>
XML;
        $atom = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
<entry><title>GTA 6 เปิดให้พรีโหลดแล้ว</title><link rel="alternate" href="https://example.com/gta6"/><published>2026-11-11T04:09:54Z</published><summary>เปิดพรีโหลดไฟล์</summary></entry>
<entry><title>ข่าวเก่ามาก</title><link href="https://example.com/old"/><published>2026-10-01T00:00:00Z</published></entry>
</feed>
XML;
        $rss = <<<'XML'
<?xml version="1.0"?><rss version="2.0"><channel>
<item><title>AMD เปิดตัว EPYC รุ่นใหม่ 256 คอร์</title><link>https://example.com/amd</link><pubDate>Wed, 11 Nov 2026 03:00:00 +0000</pubDate><description>&lt;p&gt;AMD EPYC&lt;/p&gt;</description></item>
</channel></rss>
XML;
        // ภาพปลอมขนาดพอ (>1000 ไบต์) ให้ผ่านด่านไฟล์เสีย
        $png = base64_encode(str_repeat('P', 2048));

        Http::fake([
            'trends.google.com/*' => Http::response($trends, 200),
            'www.blognone.com/*' => Http::response($atom, 200),
            'www.droidsans.com/*' => Http::response($rss, 200),
            'techcrunch.com/*' => Http::response('', 500),
            'www.theverge.com/*' => Http::response('', 500),
            'feeds.arstechnica.com/*' => Http::response('', 500),
            'api.openai.com/*' => Http::response(['data' => [['b64_json' => $png]]], 200),
        ]);
    }

    /**
     * service ที่ AI ข้อความถูกแทนด้วยคำตอบตายตัว (ไม่ยิง AI จริง)
     */
    protected function serviceWithFakeAi(?callable $responder = null): PersonalPostPrepService
    {
        $responder ??= function (string $prompt, string $context) {
            return match ($context) {
                // ข่าวอุบัติเหตุถูกตัดก่อนถึง AI → ลำดับ: 0 disney · 1 ticketmaster · 2 GTA · 3 AMD
                'personal_post:pick' => "```json\n".'{"picks":[{"index":2,"topic":"GTA 6 พรีโหลดแล้ว"},{"index":0,"topic":"Disney+ 3D บน Quest"},{"index":3,"topic":"AMD EPYC 256 คอร์"},{"index":1,"topic":"Ticketmaster"}]}'."\n```",
                'personal_post:image_prompt' => 'A futuristic gaming console glowing on a desk with a big download progress bar, neon purple lights',
                default => "🎮 ข่าวใหญ่วันนี้!\n\nเรื่องนี้กำลังมาแรงมาก เล่าให้ฟังแบบสั้นๆ ว่าเกิดอะไรขึ้นบ้าง และสำคัญกับเรายังไง ทั้งคนเล่นเกมและคนทั่วไปที่ติดตามข่าวเทคโนโลยี\n\nใครรออยู่บ้าง คอมเมนต์บอกกันหน่อย 👇\n\n#ข่าวเทค #เกม",
            };
        };

        return new class(app(TrendSourceService::class), $responder) extends PersonalPostPrepService
        {
            public function __construct(TrendSourceService $s, private $responder)
            {
                parent::__construct($s);
            }

            protected function generateText(string $prompt, string $context): array
            {
                return [($this->responder)($prompt, $context), 'fake'];
            }
        };
    }

    protected function openAiKey(): void
    {
        AiApiKey::create([
            'name' => 'test openai',
            'provider' => 'openai',
            'api_key' => 'sk-test',
            'is_active' => true,
            'priority' => 10,
        ])->forceFill(['last_test_passed_at' => now()])->save();
    }

    public function test_menu_item_is_first_and_facebook_blue(): void
    {
        $menu = app(MenuService::class)->getMenuForRole('admin', $this->admin());

        $this->assertSame('personal-posts', $menu[0]['id'] ?? null, 'เมนูโพสต์ส่วนตัวต้องอยู่บนสุด');
        $this->assertSame('#1877F2', $menu[0]['accent'] ?? null);
        $this->assertStringContainsString('/admin/personal-posts', (string) ($menu[0]['url'] ?? ''));
    }

    public function test_v3_menu_item_renders_accent_only_for_safe_hex(): void
    {
        $ok = \Illuminate\Support\Facades\Blade::render(
            '<x-menu.pinnable-menu-item menuKey="personal-posts" label="โพสต์" icon="fab fa-facebook" url="/admin/personal-posts" accent="#1877F2" />'
        );
        $this->assertStringContainsString('background: #1877F2', $ok);

        $bad = \Illuminate\Support\Facades\Blade::render(
            '<x-menu.pinnable-menu-item menuKey="x" label="x" url="/x" accent="red;}</style><script>alert(1)</script>" />'
        );
        $this->assertStringNotContainsString('<script>alert(1)</script>', $bad);
        $this->assertStringNotContainsString('background: red', $bad);
    }

    public function test_guest_and_non_admin_cannot_open_page(): void
    {
        $this->get(route('admin.personal-posts.index'))->assertRedirect();

        $member = User::factory()->create(['role' => 'user']);
        // middleware role ของโปรเจกต์ redirect สมาชิกออก (ไม่ใช่ 403) — สำคัญคือห้ามเห็นหน้า
        $this->actingAs($member)->get(route('admin.personal-posts.index'))
            ->assertRedirect()
            ->assertDontSee('โพสต์เฟซบุ๊กส่วนตัว');
    }

    public function test_empty_state_shows_four_empty_slots(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.personal-posts.index'))
            ->assertOk()
            ->assertSee('โพสต์เฟซบุ๊กส่วนตัว')
            ->assertSee('ช่องที่ 4 ยังว่าง');
    }

    public function test_prepare_day_builds_four_ready_posts_with_images(): void
    {
        $this->fakeFeeds();
        $this->openAiKey();

        $stats = $this->serviceWithFakeAi()->prepareDay(now());

        $this->assertSame(4, $stats['ready']);
        $drafts = PersonalPostDraft::orderBy('slot')->get();
        $this->assertCount(4, $drafts);
        $this->assertSame('GTA 6 พรีโหลดแล้ว', $drafts[0]->topic);
        $this->assertSame('tech_news', $drafts[0]->source_type);
        $this->assertSame('https://example.com/gta6', $drafts[0]->source_url);
        foreach ($drafts as $d) {
            $this->assertSame(PersonalPostDraft::STATUS_READY, $d->status);
            $this->assertNotEmpty($d->caption);
            $this->assertStringStartsWith('openai:gpt-image-2', (string) $d->image_provider);
            $this->assertFileExists(storage_path('app/public/'.$d->image_path));
            @unlink(storage_path('app/public/'.$d->image_path));
        }
        // ข่าวเก่ากว่า 36 ชม. ต้องไม่ถูกดึงมา
        $this->assertFalse($drafts->contains('source_title', 'ข่าวเก่ามาก'));
    }

    public function test_prepare_day_keeps_posted_slot_and_falls_back_when_ai_fails(): void
    {
        $this->fakeFeeds();
        PersonalPostDraft::create([
            'post_date' => now()->toDateString(), 'slot' => 1, 'source_type' => 'tech_news',
            'topic' => 'โพสต์ไปแล้ว', 'source_title' => 'GTA 6 เปิดให้พรีโหลดแล้ว',
            'caption' => 'ข้อความเดิม', 'status' => PersonalPostDraft::STATUS_POSTED, 'posted_at' => now(),
        ]);

        // AI เลือกหัวข้อตอบมั่ว → ต้องใช้ลำดับตายตัว (ข่าวเทคครึ่งหนึ่ง) · ไม่มีคีย์ภาพ → ยังได้บทความ
        $service = $this->serviceWithFakeAi(fn ($p, $c) => $c === 'personal_post:pick'
            ? 'ไม่รู้'
            : str_repeat('บทความทดสอบยาวพอสมควร ', 12));
        $stats = $service->prepareDay(now(), force: true);

        $this->assertSame(1, $stats['skipped']);
        $posted = PersonalPostDraft::where('slot', 1)->first();
        $this->assertSame('ข้อความเดิม', $posted->caption, 'ช่องที่โพสต์แล้วห้ามถูกเขียนทับ');

        $others = PersonalPostDraft::where('slot', '>', 1)->get();
        $this->assertCount(3, $others);
        $this->assertNotContains('GTA 6 เปิดให้พรีโหลดแล้ว', $others->pluck('source_title')->all(), 'หัวข้อที่โพสต์แล้วห้ามซ้ำ');
        $this->assertNotContains('อุบัติเหตุรถชน', $others->pluck('source_title')->all(), 'ข่าวร้ายต้องไม่ถูกเลือกแม้ AI ล้ม');
        $this->assertGreaterThanOrEqual(1, $others->where('source_type', 'tech_news')->count());
        foreach ($others as $d) {
            $this->assertSame(PersonalPostDraft::STATUS_READY, $d->status);
            $this->assertNotNull($d->error, 'ภาพล้มต้องบอกเหตุผล');
        }
    }

    public function test_prepare_button_dispatches_once_and_blocks_double_click(): void
    {
        Bus::fake([PreparePersonalPostsJob::class]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.personal-posts.prepare'), ['date' => '2026-11-11'])
            ->assertRedirect(route('admin.personal-posts.index', ['date' => '2026-11-11']))
            ->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.personal-posts.prepare'), ['date' => '2026-11-11'])
            ->assertSessionHas('error');

        Bus::assertDispatchedAfterResponseTimes(PreparePersonalPostsJob::class, 1);
        Cache::forget(PreparePersonalPostsJob::lockKey('2026-11-11'));
    }

    public function test_regenerate_refuses_while_generating(): void
    {
        Bus::fake([PreparePersonalPostsJob::class]);
        $draft = PersonalPostDraft::create([
            'post_date' => now()->toDateString(), 'slot' => 2, 'caption' => 'x', 'status' => PersonalPostDraft::STATUS_GENERATING,
        ]);

        $this->actingAs($this->admin())->post(route('admin.personal-posts.regenerate', $draft), ['mode' => 'image'])
            ->assertSessionHas('error');
        Bus::assertNothingDispatched();
    }

    public function test_caption_edit_posted_toggle_and_validation(): void
    {
        $admin = $this->admin();
        $draft = PersonalPostDraft::create([
            'post_date' => now()->toDateString(), 'slot' => 3, 'status' => PersonalPostDraft::STATUS_FAILED,
        ]);

        $this->actingAs($admin)->post(route('admin.personal-posts.caption', $draft), ['caption' => ''])
            ->assertSessionHasErrors('caption');

        $this->actingAs($admin)->post(route('admin.personal-posts.caption', $draft), ['caption' => 'เขียนเองเลย'])
            ->assertSessionHas('success');
        $this->assertSame(PersonalPostDraft::STATUS_READY, $draft->fresh()->status);

        $this->actingAs($admin)->post(route('admin.personal-posts.posted', $draft));
        $postedAt = $draft->fresh()->posted_at;
        $this->assertSame(PersonalPostDraft::STATUS_POSTED, $draft->fresh()->status);

        // กดซ้ำ → เวลาโพสต์เดิม
        Carbon::setTestNow('2026-11-11 09:00:00');
        $this->actingAs($admin)->post(route('admin.personal-posts.posted', $draft));
        $this->assertEquals($postedAt, $draft->fresh()->posted_at);

        $this->actingAs($admin)->post(route('admin.personal-posts.posted', $draft), ['undo' => 1]);
        $this->assertSame(PersonalPostDraft::STATUS_READY, $draft->fresh()->status);
        $this->assertNull($draft->fresh()->posted_at);
    }

    public function test_image_download_rejects_paths_outside_folder(): void
    {
        $draft = PersonalPostDraft::create([
            'post_date' => now()->toDateString(), 'slot' => 4, 'caption' => 'x',
            'status' => PersonalPostDraft::STATUS_READY, 'image_path' => '../../.env',
        ]);

        $this->actingAs($this->admin())->get(route('admin.personal-posts.image', $draft))->assertNotFound();
    }

    public function test_settings_reject_non_facebook_url(): void
    {
        $this->actingAs($this->admin())->post(route('admin.personal-posts.settings'), [
            'profile_url' => 'https://evil.example.com/profile',
            'text_provider' => 'gemini',
            'image_model' => 'gpt-image-2',
            'enabled' => 1,
        ])->assertSessionHasErrors('profile_url');
    }
}
