<?php

namespace Tests\Feature\Shop;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AppDownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

/**
 * โหลดแอป Thai Prompt APP (APK) ผ่านเซิร์ฟเวอร์เรา (ต้องใช้ MySQL)
 *
 * - /app/download ส่งต่อไปไฟล์นิ่งใน storage (ไม่สตรีมผ่าน PHP) · ยังไม่มีไฟล์ = กลับหน้าแรก
 * - app:publish-apk ตรวจว่าเป็น APK จริง เก็บไว้แค่ตัวล่าสุด + ตัวก่อนหน้า
 * - ปุ่มหน้าแรกโชว์เมื่อมีไฟล์ และหลังบ้านปิดสวิตช์ APK ได้
 */
#[Group('shop')]
class AppDownloadTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> ไฟล์ชั่วคราวที่ต้องลบตอนจบ */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Cache::flush();

        // หน้าแรก (/) ต้องมี super admin ไม่งั้นเด้งไปหน้าติดตั้ง
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true, 'role' => 'admin'])->save();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * ไฟล์ APK ปลอม (หัวไฟล์ zip + ขนาดเกิน 1 MB ตามที่ตัวตรวจต้องการ)
     */
    private function fakeApk(string $head = "PK\x03\x04", int $bytes = 1_200_000): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'apk');
        file_put_contents($path, $head.random_bytes($bytes - strlen($head)));
        $this->tmpFiles[] = $path;

        return $path;
    }

    public function test_download_goes_back_home_when_no_apk_is_published(): void
    {
        $this->get(route('app.download'))->assertRedirect(url('/').'#nv-app');
    }

    public function test_published_apk_is_served_as_a_static_file(): void
    {
        $apk = app(AppDownloadService::class)->publish($this->fakeApk(), '3.384.0', 41);

        $this->assertSame('ThaiPrompt-APP-3.384.0.apk', $apk['file']);
        $this->assertSame(41, $apk['version_code']);
        Storage::disk('public')->assertExists('app-downloads/ThaiPrompt-APP-3.384.0.apk');
        Storage::disk('public')->assertExists('app-downloads/latest.json');

        // ลิงก์ยึด APP_URL (ไม่ใช้ host จาก request ที่ปลอมผ่าน X-Forwarded-Host ได้)
        $this->get(route('app.download'))
            ->assertRedirect(rtrim(config('app.url'), '/').'/storage/app-downloads/ThaiPrompt-APP-3.384.0.apk?h='.substr($apk['sha256'], 0, 12));
    }

    public function test_publish_keeps_only_the_current_and_previous_apk(): void
    {
        app(AppDownloadService::class)->publish($this->fakeApk(), '1.0.0');
        app(AppDownloadService::class)->publish($this->fakeApk(), '1.0.1');
        app(AppDownloadService::class)->publish($this->fakeApk(), '1.0.2');

        $files = collect(Storage::disk('public')->files('app-downloads'))->map(fn ($f) => basename($f))->sort()->values()->all();

        $this->assertSame(['ThaiPrompt-APP-1.0.1.apk', 'ThaiPrompt-APP-1.0.2.apk', 'latest.json'], $files);
        $this->assertSame('1.0.2', app(AppDownloadService::class)->latest()['version']);
    }

    public function test_publish_rejects_files_that_are_not_an_apk(): void
    {
        $this->expectException(RuntimeException::class);

        app(AppDownloadService::class)->publish($this->fakeApk('<!DOCTYPE html>'), '1.0.0');
    }

    public function test_publish_rejects_a_bad_version_string(): void
    {
        $this->expectException(RuntimeException::class);

        app(AppDownloadService::class)->publish($this->fakeApk(), '../../evil');
    }

    public function test_publish_command(): void
    {
        $this->artisan('app:publish-apk', ['path' => $this->fakeApk(), '--app-version' => '2.0.0', '--version-code' => '7'])
            ->assertSuccessful();
        Storage::disk('public')->assertExists('app-downloads/ThaiPrompt-APP-2.0.0.apk');

        // ไม่ระบุเวอร์ชัน = ไม่ทำอะไร
        $this->artisan('app:publish-apk', ['path' => $this->fakeApk()])->assertFailed();
    }

    public function test_home_download_button_follows_the_published_apk_and_admin_switch(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('app.download'), $html, 'ยังไม่มีไฟล์ ต้องไม่มีปุ่มโหลด');
        $this->assertStringContainsString('เร็วๆ นี้บน Google Play', $html);

        app(AppDownloadService::class)->publish($this->fakeApk(), '3.384.0', 41);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString(route('app.download'), $html);
        $this->assertStringContainsString('ดาวน์โหลดแอป Android', $html);
        $this->assertStringContainsString('เวอร์ชัน 3.384.0', $html);
        $this->assertStringContainsString('class="nv-qr"', $html, 'คอมต้องมี QR ให้สแกนโหลดบนมือถือ');

        // หลังบ้านปิด "ดาวน์โหลด APK" → ปุ่มหาย และลิงก์ตรงก็ไม่แจกไฟล์
        SiteSetting::getSetting()->update(['app_apk_enabled' => false]);
        Cache::forget('site_settings');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('app.download'), $html);
        $this->get(route('app.download'))->assertRedirect(url('/').'#nv-app');
    }

    public function test_admin_switches_control_the_play_button_and_the_app_section(): void
    {
        $setting = SiteSetting::getSetting();
        $setting->update(['app_playstore_url' => 'https://play.google.com/store/apps/details?id=com.thaiprompt.affiliate', 'app_playstore_enabled' => false]);
        Cache::forget('site_settings');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('play.google.com', $html, 'ปิดสวิตช์ Play Store แล้วปุ่มต้องไม่โผล่');

        $setting->update(['app_playstore_enabled' => true]);
        Cache::forget('site_settings');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('https://play.google.com/store/apps/details?id=com.thaiprompt.affiliate', $html);

        // ปิด "แสดงส่วนดาวน์โหลดแอปในหน้าแรก" → แถบแอปและการ์ดแอปบนฮีโร่หายทั้งคู่
        $setting->update(['app_download_enabled' => false]);
        Cache::forget('site_settings');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('id="nv-app"', $html);
        $this->assertStringNotContainsString('href="#nv-app"', $html);
        $this->assertStringNotContainsString('play.google.com', $html);
    }
}
