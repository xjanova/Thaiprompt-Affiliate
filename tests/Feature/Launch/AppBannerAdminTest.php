<?php

namespace Tests\Feature\Launch;

use App\Models\MobileBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RunsDataMigrations;
use Tests\TestCase;

/**
 * หน้าแอดมิน "แบนเนอร์แคมเปญแอป" (admin.app-banners.*) — ใช้ MySQL
 *
 * ครอบคลุม: หน้า V4 เปิดได้ (รายการ/สร้าง/แก้ไข), อัปโหลดรูปลง public disk, ตรวจ CTA/ตารางเวลา,
 * แทนรูปแล้วลบไฟล์เก่า, เปิด/ปิด, เลื่อนลำดับ, ลบ, เส้นทางหน้าเดิม redirect มาหน้าใหม่
 */
class AppBannerAdminTest extends TestCase
{
    use RefreshDatabase;
    use RunsDataMigrations;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Cache::flush();
        // แบนเนอร์เปิดตัวมาจาก data migration — CI โหลด schema dump ที่ข้ามมันไป ต้องใส่เอง
        $this->runDataMigration('2026_09_26_133100_seed_launch_app_campaign_banners.php');
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_pages_render_with_v4_layout(): void
    {
        $banner = MobileBanner::where('campaign_key', 'launch-2026-taladsod-home')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.app-banners.index'))
            ->assertOk()
            ->assertSee('tp-card', false)
            ->assertSee('แบนเนอร์แคมเปญแอป')
            ->assertSee('เปิดตัวตลาดสดไทยพร้อม')
            ->assertSee('ตัวอย่างในแอป');

        $this->actingAs($this->admin)->get(route('admin.app-banners.index', ['placement' => 'rider']))
            ->assertOk()
            ->assertSee('มาเป็นไรเดอร์ รับงานใกล้บ้าน');

        $this->actingAs($this->admin)->get(route('admin.app-banners.create', ['placement' => 'merchant']))
            ->assertOk()
            ->assertSee('tp-card', false)
            ->assertSee('สร้างแบนเนอร์ใหม่');

        $this->actingAs($this->admin)->get(route('admin.app-banners.edit', $banner))
            ->assertOk()
            ->assertSee('แก้ไขแบนเนอร์')
            ->assertSee('ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย');
    }

    public function test_member_cannot_manage_banners(): void
    {
        $member = User::factory()->create(['role' => 'user']);

        $this->assertNotSame(200, $this->actingAs($member)->get(route('admin.app-banners.index'))->getStatusCode());
    }

    public function test_legacy_routes_redirect_to_new_pages(): void
    {
        $banner = MobileBanner::firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.mobile-app.banners.index'))
            ->assertRedirect(route('admin.app-banners.index'));
        $this->actingAs($this->admin)->get(route('admin.mobile-app.banners.create'))
            ->assertRedirect(route('admin.app-banners.create'));
        $this->actingAs($this->admin)->get(route('admin.mobile-app.banners.edit', $banner))
            ->assertRedirect(route('admin.app-banners.edit', $banner));
    }

    public function test_store_uploads_image_and_syncs_legacy_link(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.app-banners.store'), [
                'title' => 'ลดค่าส่งสุดสัปดาห์',
                'subtitle' => 'สั่งตลาดสด ส่งฟรี 3 กม.แรก',
                'image' => UploadedFile::fake()->image('promo.jpg', 1200, 600),
                'placement' => 'taladsod',
                'audience' => 'buyer',
                'cta_type' => 'url',
                'cta_label' => 'ดูโปร',
                'cta_value' => '/taladsod',
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.app-banners.index', ['placement' => 'taladsod']))
            ->assertSessionHas('success');

        $banner = MobileBanner::where('title', 'ลดค่าส่งสุดสัปดาห์')->firstOrFail();
        $this->assertStringStartsWith('/storage/app-banners/', $banner->image);
        Storage::disk('public')->assertExists(substr($banner->image, strlen('/storage/')));
        $this->assertSame('taladsod', $banner->position);
        $this->assertSame('buyer', $banner->audience);
        $this->assertSame('external', $banner->link_type);
        $this->assertStringEndsWith('/taladsod', (string) $banner->link);
        $this->assertSame('scheduled', $banner->scheduleState());
        $this->assertSame($this->admin->id, (int) $banner->created_by);
        // ต่อท้ายลำดับของตำแหน่งนั้น
        $this->assertGreaterThan(1, (int) $banner->sort_order);
    }

    public function test_store_validates_image_cta_and_schedule(): void
    {
        $before = MobileBanner::count();

        $this->actingAs($this->admin)
            ->from(route('admin.app-banners.create'))
            ->post(route('admin.app-banners.store'), [
                'title' => '',
                'placement' => 'moon',
                'audience' => 'all',
                'cta_type' => 'screen',
                'cta_label' => '',
                'cta_value' => 'Bad Screen!',
                'starts_at' => '2026-12-10T10:00',
                'ends_at' => '2026-12-01T10:00',
            ])
            ->assertRedirect(route('admin.app-banners.create'))
            ->assertSessionHasErrors(['title', 'image', 'placement', 'cta_label', 'cta_value', 'ends_at']);

        $this->actingAs($this->admin)
            ->from(route('admin.app-banners.create'))
            ->post(route('admin.app-banners.store'), [
                'title' => 'ลิงก์อันตราย',
                'image_path' => '//evil.example.com/x.png',
                'placement' => 'home',
                'audience' => 'all',
                'cta_type' => 'url',
                'cta_label' => 'ไป',
                'cta_value' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors(['image_path', 'cta_value']);

        $this->assertSame($before, MobileBanner::count());
    }

    public function test_update_replaces_image_and_deletes_the_old_upload(): void
    {
        $this->actingAs($this->admin)->post(route('admin.app-banners.store'), [
            'title' => 'รูปเดิม',
            'image' => UploadedFile::fake()->image('old.jpg', 1200, 600),
            'placement' => 'home',
            'audience' => 'all',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $banner = MobileBanner::where('title', 'รูปเดิม')->firstOrFail();
        $oldPath = substr($banner->image, strlen('/storage/'));
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($this->admin)->put(route('admin.app-banners.update', $banner), [
            'title' => 'รูปใหม่',
            'image' => UploadedFile::fake()->image('new.png', 1200, 600),
            'placement' => 'home',
            'audience' => 'rider',
            'cta_type' => 'screen',
            'cta_label' => 'สมัคร',
            'cta_value' => 'rider',
            'is_active' => '0',
        ])->assertRedirect(route('admin.app-banners.index', ['placement' => 'home']));

        $banner->refresh();
        $this->assertSame('รูปใหม่', $banner->title);
        $this->assertFalse((bool) $banner->is_active);
        $this->assertSame('/rider', $banner->link);
        $this->assertSame('internal', $banner->link_type);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists(substr($banner->image, strlen('/storage/')));

        // แก้ไขโดยไม่ส่งรูป → ใช้รูปเดิม
        $image = $banner->image;
        $this->actingAs($this->admin)->put(route('admin.app-banners.update', $banner), [
            'title' => 'รูปใหม่ แก้ข้อความ',
            'placement' => 'home',
            'audience' => 'all',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame($image, $banner->fresh()->image);
    }

    public function test_toggle_move_and_destroy(): void
    {
        MobileBanner::where('position', 'home')->delete();

        $first = MobileBanner::create(['title' => 'หนึ่ง', 'image' => '/images/taladsod/banner-market.webp', 'position' => 'home', 'sort_order' => 1]);
        $second = MobileBanner::create(['title' => 'สอง', 'image' => '/images/taladsod/banner-rider.webp', 'position' => 'home', 'sort_order' => 2]);

        $this->actingAs($this->admin)
            ->from(route('admin.app-banners.index'))
            ->post(route('admin.app-banners.toggle', $first))
            ->assertRedirect(route('admin.app-banners.index'));
        $this->assertFalse((bool) $first->fresh()->is_active);

        $this->actingAs($this->admin)
            ->from(route('admin.app-banners.index'))
            ->post(route('admin.app-banners.move', $second), ['direction' => 'up'])
            ->assertRedirect(route('admin.app-banners.index'));
        $this->assertSame(1, (int) $second->fresh()->sort_order);
        $this->assertSame(2, (int) $first->fresh()->sort_order);

        $this->actingAs($this->admin)
            ->postJson(route('admin.app-banners.move', $second), ['direction' => 'sideways'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson(route('admin.app-banners.reorder'), ['placement' => 'home', 'ids' => [$first->id, $second->id]])
            ->assertOk();
        $this->assertSame(1, (int) $first->fresh()->sort_order);

        $this->actingAs($this->admin)
            ->delete(route('admin.app-banners.destroy', $first))
            ->assertRedirect(route('admin.app-banners.index', ['placement' => 'home']));
        $this->assertNull(MobileBanner::find($first->id));
    }
}
