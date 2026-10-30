<?php

namespace Tests\Feature\VideoAutomation;

use App\Models\User;
use App\Models\VideoAutoJob;
use App\Models\VideoAutoJobLog;
use App\Models\VideoAutoPlatform;
use App\Models\VideoAutoProject;
use App\Models\VideoAutoPublishHistory;
use App\Models\VideoAutoSchedule;
use App\Models\VideoAutoTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 🎬 หลังบ้าน Video Automation — ทุกหน้าต้องเรนเดอร์ได้ ทั้งตอนยังไม่มีข้อมูลและตอนมีข้อมูลแล้ว
 *
 * กันบั๊ก Undefined constant VideoAutoTemplate::GENRES / ::MOODS ที่วิวอ้างชื่อผิด
 * (ค่าคงที่จริงคือ MUSIC_GENRES / MUSIC_MOODS)
 */
class VideoAutomationAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * หน้าที่ไม่ต้องมี id — เปิดได้ตั้งแต่ระบบยังว่าง
     *
     * @return array<int, string>
     */
    private function listPages(): array
    {
        return [
            'admin.video-automation.dashboard',
            'admin.video-automation.settings',
            'admin.video-automation.platforms',
            'admin.video-automation.templates.index',
            'admin.video-automation.templates.create',
            'admin.video-automation.projects.index',
            'admin.video-automation.projects.create',
            'admin.video-automation.jobs.index',
            'admin.video-automation.schedules',
            'admin.video-automation.publish-history',
            'admin.video-automation.documentation',
        ];
    }

    public function test_every_page_renders_when_empty(): void
    {
        foreach ($this->listPages() as $name) {
            $this->actingAs($this->admin)->get(route($name))->assertOk();
        }
    }

    /**
     * URI + method เดียวกันลงทะเบียนสองครั้ง ตัวหลังทับตัวแรกและชื่อตัวแรกหายไปเงียบๆ
     * เมนูทุกรายการต้องชี้ไปที่ route ที่มีอยู่จริง ไม่อย่างนั้นเมนูจะถูกซ่อน
     */
    public function test_menu_items_point_at_registered_routes(): void
    {
        $menu = collect(config('menus.admin'))->firstWhere('id', 'video-automation');
        $this->assertNotNull($menu, 'ไม่พบเมนู video-automation ใน config/menus.php');

        $routes = collect($menu['submenu'])->pluck('route')->filter();
        $this->assertNotEmpty($routes);

        foreach ($routes as $name) {
            $this->assertTrue(Route::has($name), "เมนูชี้ route ที่ไม่มีอยู่: {$name}");
        }
    }

    public function test_every_page_renders_with_data_and_shows_genre_and_mood_labels(): void
    {
        $template = VideoAutoTemplate::create([
            'name' => 'เทมเพลตโลไฟ',
            'slug' => 'lofi-template',
            'music_genre' => 'lofi',
            'music_mood' => 'calm',
            'image_style' => 'anime',
            'transition_type' => 'fade',
            'is_active' => true,
        ]);

        $project = VideoAutoProject::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'โปรเจกต์ทดสอบ',
            'template_id' => $template->id,
            'created_by' => $this->admin->id,
        ]);

        $job = VideoAutoJob::create([
            'uuid' => (string) Str::uuid(),
            'project_id' => $project->id,
            'job_type' => 'full_pipeline',
        ]);

        // video_auto_job_logs ไม่มี created_at — มีแค่ logged_at
        VideoAutoJobLog::create([
            'job_id' => $job->id,
            'project_id' => $project->id,
            'level' => 'info',
            'message' => 'เริ่มสร้างเพลงแล้ว',
        ]);

        $schedule = VideoAutoSchedule::create([
            'name' => 'ทุกวันตอนเช้า',
            'template_id' => $template->id,
        ]);

        VideoAutoPlatform::create([
            'platform_type' => 'youtube',
            'name' => 'YouTube หลัก',
        ]);

        $history = VideoAutoPublishHistory::create([
            'uuid' => (string) Str::uuid(),
            'project_id' => $project->id,
            'job_id' => $job->id,
            'schedule_id' => $schedule->id,
            'platform' => 'youtube',
            'title' => 'เพลงโลไฟ',
            'music_genre' => 'lofi',
            'status' => 'published',
            'published_at' => now(),
            'views_count' => 12345,
            'likes_count' => 678,
            'comments_count' => 90,
            'shares_count' => 12,
        ]);

        foreach ($this->listPages() as $name) {
            $this->actingAs($this->admin)->get(route($name))->assertOk();
        }

        // ป้ายแนวเพลง/อารมณ์ต้องมาจาก MUSIC_GENRES / MUSIC_MOODS
        $this->actingAs($this->admin)->get(route('admin.video-automation.templates.index', ['genre' => 'lofi']))
            ->assertOk()
            ->assertSee(VideoAutoTemplate::MUSIC_GENRES['lofi'])
            ->assertSee('เทมเพลตโลไฟ');

        $this->actingAs($this->admin)->get(route('admin.video-automation.templates.create'))
            ->assertOk()
            ->assertSee(VideoAutoTemplate::MUSIC_MOODS['calm']);

        $this->actingAs($this->admin)->get(route('admin.video-automation.templates.edit', $template->id))
            ->assertOk()
            ->assertSee('<option value="lofi" selected', false)
            ->assertSee('<option value="calm" selected', false);

        $this->actingAs($this->admin)->get(route('admin.video-automation.projects.create'))
            ->assertOk()
            ->assertSee(VideoAutoTemplate::MUSIC_GENRES['lofi']);

        $this->actingAs($this->admin)->get(route('admin.video-automation.projects.show', $project->id))
            ->assertOk()
            ->assertSee(VideoAutoTemplate::MUSIC_GENRES['lofi'])
            ->assertSee(VideoAutoTemplate::IMAGE_STYLES['anime'], false)
            ->assertSee('เริ่มสร้างเพลงแล้ว');

        $this->actingAs($this->admin)->get(route('admin.video-automation.schedules.edit', $schedule->id))->assertOk();
        // ยอด engagement อยู่ในคอลัมน์ *_count (ไม่ใช่ views/likes)
        $this->actingAs($this->admin)->get(route('admin.video-automation.publish-history.show', $history->id))
            ->assertOk()
            ->assertSee('12,345')
            ->assertSee('678');

        $this->actingAs($this->admin)->get(route('admin.video-automation.publish-history', ['search' => 'โลไฟ']))
            ->assertOk()
            ->assertSee('12,345');

        $stats = $this->actingAs($this->admin)->getJson(route('admin.video-automation.publish-history.stats'))
            ->assertOk();
        $this->assertEquals(12345, $stats->json('data.stats.total_views'));
        $this->assertEquals(12, $stats->json('data.stats.total_shares'));
    }
}
