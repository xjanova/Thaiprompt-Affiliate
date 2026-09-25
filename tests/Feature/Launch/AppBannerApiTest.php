<?php

namespace Tests\Feature\Launch;

use App\Models\MobileBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API แบนเนอร์แคมเปญของแอป (GET /api/v1/banners) — ใช้ MySQL
 *
 * ครอบคลุม: แบนเนอร์เปิดตัวจาก data migration, กรองตามตารางเวลา/ตำแหน่ง/กลุ่มผู้ชม,
 * cache ถูกล้างเมื่อแอดมินแก้, นับคลิก/การแสดงผล (กันยิงซ้ำ), endpoint เดิมของแอปรุ่นเก่ายังใช้ได้
 */
class AppBannerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_launch_banners_are_seeded_once_with_absolute_images(): void
    {
        $this->assertSame(4, MobileBanner::whereNotNull('campaign_key')->where('campaign_key', 'like', 'launch-2026-%')->count());

        $home = $this->getJson('/api/v1/banners?placement=home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.placement', 'home')
            ->json('data');

        $launch = collect($home)->firstWhere('title', 'เปิดตัวตลาดสดไทยพร้อม');
        $this->assertNotNull($launch);
        $this->assertSame('ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย', $launch['subtitle']);
        $this->assertSame('screen', $launch['cta_type']);
        $this->assertSame('taladsod', $launch['cta_value']);
        $this->assertNull($launch['cta_url']);
        $this->assertMatchesRegularExpression('#^https?://.+/images/taladsod/banner-market\.webp$#', $launch['image_url']);
        $this->assertIsInt($launch['id']);
        $this->assertIsInt($launch['sort']);

        $merchant = $this->getJson('/api/v1/banners?placement=merchant')->assertOk()->json('data');
        $this->assertSame('เปิดร้านฟรี ไม่มีค่า GP ช่วงเปิดตัว', $merchant[0]['title']);
        $this->assertSame('url', $merchant[0]['cta_type']);
        $this->assertMatchesRegularExpression('#^https?://.+/taladsod/start/seller$#', $merchant[0]['cta_url']);

        $rider = $this->getJson('/api/v1/banners?placement=rider')->assertOk()->json('data');
        $this->assertSame('มาเป็นไรเดอร์ รับงานใกล้บ้าน', $rider[0]['title']);
        $this->assertSame('rider', $rider[0]['cta_value']);

        // รัน data migration ซ้ำไม่ใส่ซ้ำ และไม่ทับที่แอดมินแก้
        MobileBanner::where('campaign_key', 'launch-2026-rider-recruit')->update(['title' => 'แก้โดยแอดมิน']);
        $migration = require database_path('migrations/2026_09_26_133100_seed_launch_app_campaign_banners.php');
        $migration->up();
        $this->assertSame(4, MobileBanner::where('campaign_key', 'like', 'launch-2026-%')->count());
        $this->assertSame('แก้โดยแอดมิน', MobileBanner::where('campaign_key', 'launch-2026-rider-recruit')->value('title'));
    }

    public function test_only_active_banners_inside_schedule_are_returned_in_sort_order(): void
    {
        MobileBanner::query()->delete();

        $showingLate = $this->banner(['title' => 'แสดง ลำดับ 2', 'sort_order' => 2]);
        $showingFirst = $this->banner([
            'title' => 'แสดง ลำดับ 1',
            'sort_order' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        $this->banner(['title' => 'ยังไม่ถึงเวลา', 'start_date' => now()->addHour()]);
        $this->banner(['title' => 'หมดเวลาแล้ว', 'end_date' => now()->subMinute()]);
        $this->banner(['title' => 'ปิดอยู่', 'is_active' => false]);
        $this->banner(['title' => 'ตำแหน่งอื่น', 'position' => 'rider']);
        $riderOnly = $this->banner(['title' => 'เฉพาะไรเดอร์', 'audience' => 'rider', 'sort_order' => 3]);

        $all = $this->getJson('/api/v1/banners?placement=home')->assertOk()->json('data');
        $this->assertSame(
            [$showingFirst->id, $showingLate->id, $riderOnly->id],
            array_column($all, 'id')
        );

        $buyer = $this->getJson('/api/v1/banners?placement=home&audience=buyer')->assertOk()->json('data');
        $this->assertSame([$showingFirst->id, $showingLate->id], array_column($buyer, 'id'));

        $rider = $this->getJson('/api/v1/banners?placement=home&audience=rider')->assertOk()->json('data');
        $this->assertSame([$showingFirst->id, $showingLate->id, $riderOnly->id], array_column($rider, 'id'));

        // แบนเนอร์ที่ตั้งเวลาไว้ เริ่มแสดงเองเมื่อถึงเวลา (แม้ผู้สมัครถูก cache ไว้แล้ว)
        $this->travel(2)->hours();
        $later = $this->getJson('/api/v1/banners?placement=home')->assertOk()->json('data');
        $this->assertContains('ยังไม่ถึงเวลา', array_column($later, 'title'));
    }

    public function test_invalid_placement_or_audience_returns_422(): void
    {
        $this->getJson('/api/v1/banners?placement=moon')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->getJson('/api/v1/banners?placement=home&audience=alien')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        // ไม่ส่ง placement = home
        $this->getJson('/api/v1/banners')->assertOk()->assertJsonPath('meta.placement', 'home');
    }

    public function test_admin_changes_clear_the_cache_immediately(): void
    {
        MobileBanner::query()->delete();
        $banner = $this->banner(['title' => 'แบนเนอร์ที่จะถูกปิด']);

        $this->assertCount(1, $this->getJson('/api/v1/banners?placement=home')->json('data'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->postJson(route('admin.app-banners.toggle', $banner))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertCount(0, $this->getJson('/api/v1/banners?placement=home')->json('data'));
    }

    public function test_click_is_counted_once_per_ip_per_minute(): void
    {
        $banner = $this->banner(['title' => 'นับคลิก']);

        $this->postJson("/api/v1/banners/{$banner->id}/click")
            ->assertOk()
            ->assertJsonPath('data.counted', true);
        $this->postJson("/api/v1/banners/{$banner->id}/click")
            ->assertOk()
            ->assertJsonPath('data.counted', false);

        $this->assertSame(1, (int) $banner->fresh()->click_count);

        $this->postJson('/api/v1/banners/999999/click')
            ->assertStatus(404)
            ->assertJsonPath('code', 'BANNER_NOT_FOUND');
    }

    public function test_impressions_are_counted_and_validated(): void
    {
        $a = $this->banner(['title' => 'A']);
        $b = $this->banner(['title' => 'B']);

        $this->postJson('/api/v1/banners/impressions', ['ids' => [$a->id, $b->id, $a->id, 999999]])
            ->assertOk()
            ->assertJsonPath('data.counted', 2);

        $this->assertSame(1, (int) $a->fresh()->view_count);
        $this->assertSame(1, (int) $b->fresh()->view_count);

        $this->postJson('/api/v1/banners/impressions', ['ids' => []])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_legacy_mobile_endpoint_still_works_with_synced_links(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $data = $this->getJson('/api/v1/mobile/banners?position=home')->assertOk()->json('data');

        $launch = collect($data)->firstWhere('title', 'เปิดตัวตลาดสดไทยพร้อม');
        $this->assertNotNull($launch);
        $this->assertSame('/taladsod', $launch['link']);
        $this->assertSame('internal', $launch['linkType']);
        $this->assertStringStartsWith('http', $launch['image']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function banner(array $attributes = []): MobileBanner
    {
        $banner = new MobileBanner(array_merge([
            'title' => 'แบนเนอร์ทดสอบ',
            'image' => '/images/taladsod/banner-market.webp',
            'position' => 'home',
            'audience' => 'all',
            'is_active' => true,
            'sort_order' => 5,
            'cta_type' => 'screen',
            'cta_label' => 'ดูเลย',
            'cta_value' => 'taladsod',
        ], $attributes));
        $banner->syncLegacyLink();
        $banner->save();

        app(\App\Services\AppBannerService::class)->bumpVersion();

        return $banner;
    }
}
