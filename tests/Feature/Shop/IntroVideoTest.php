<?php

namespace Tests\Feature\Shop;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🎬 (2026-09-27) คลิปแนะนำแอป (น้องพร้อม) — แอปเปิด /app/intro ใน WebView ครั้งเดียวต่อ version
 *
 * owner: "ทำคลิปวิดีโอแนะนำแอป ไว้สำหรับคนล็อกอินเข้าครั้งแรก" + "นำคลิปไปไว้หน้าเว็บแรกด้วย"
 * แอปอ่านค่าจาก GET /api/v1/settings → data.intro_video แล้วเปิด page_url
 */
class IntroVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_settings_api_tells_the_app_which_intro_to_play(): void
    {
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.intro_video.enabled', true)
            ->assertJsonPath('data.intro_video.version', 'v1')
            ->assertJsonPath('data.intro_video.page_url', route('app.intro'));
    }

    public function test_admin_can_switch_the_intro_off_or_bump_its_version(): void
    {
        Setting::set('intro_video_enabled', '0');
        Setting::set('intro_video_version', 'v2');
        Cache::flush();

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.intro_video.enabled', false)
            ->assertJsonPath('data.intro_video.version', 'v2');
    }

    public function test_player_page_plays_the_stored_clip_and_reports_back_to_the_app(): void
    {
        $html = $this->get('/app/intro')->assertOk()->getContent();

        $this->assertStringContainsString('storage/videos/intro/thaiprompt-intro-v1.mp4', $html);
        $this->assertStringContainsString('playsinline', $html);
        $this->assertStringContainsString("post('ended')", $html);
    }

    public function test_player_page_follows_the_configured_clip(): void
    {
        Setting::set('intro_video_url', 'https://cdn.example.test/intro-v2.mp4');
        Cache::flush();

        $this->get('/app/intro')->assertOk()->assertSee('https://cdn.example.test/intro-v2.mp4', false);
    }
}
