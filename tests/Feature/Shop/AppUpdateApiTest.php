<?php

namespace Tests\Feature\Shop;

use App\Models\SiteSetting;
use App\Services\AppDownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

/**
 * 📲 เช็คอัปเดตแอป GET /api/v1/app/update (ต้องใช้ MySQL)
 *
 * - public · เทียบ build ที่ติดตั้งกับ versionCode ของไฟล์ที่ปล่อย · บังคับอัปเดตด้วย --min-build
 * - ส่ง md5 ให้แอปตรวจไฟล์ (ไฟล์เก่าที่ไม่มี md5 ใน latest.json คำนวณให้) · ลิงก์ยึด APP_URL
 * - หลังบ้านปิดสวิตช์ APK = ไม่เสนออัปเดต
 */
#[Group('shop')]
class AppUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function fakeApk(int $bytes = 1_200_000): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'apk');
        file_put_contents($path, "PK\x03\x04".random_bytes($bytes - 4));
        $this->tmpFiles[] = $path;

        return $path;
    }

    public function test_nothing_published_means_no_update(): void
    {
        $this->getJson('/api/v1/app/update?platform=android&build=44')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.update_available', false)
            ->assertJsonPath('data.required', false)
            ->assertJsonPath('data.latest', null);
    }

    public function test_newer_build_is_offered_with_everything_the_app_needs_to_verify_it(): void
    {
        $source = $this->fakeApk();
        $apk = app(AppDownloadService::class)->publish($source, '3.388.0', 45, ['มีระบบอัปเดตในแอป', '  แก้ข้อความ   ยืนยันตัวตน '], null);

        $res = $this->getJson('/api/v1/app/update?platform=android&build=44')->assertOk();
        $res->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.required', false)
            ->assertJsonPath('data.current_build', 44)
            ->assertJsonPath('data.latest.version', '3.388.0')
            ->assertJsonPath('data.latest.version_code', 45)
            ->assertJsonPath('data.latest.size', filesize($source))
            ->assertJsonPath('data.latest.md5', md5_file($source))
            ->assertJsonPath('data.latest.sha256', hash_file('sha256', $source))
            ->assertJsonPath('data.latest.notes', ['มีระบบอัปเดตในแอป', 'แก้ข้อความ ยืนยันตัวตน']);

        // ลิงก์ไฟล์ยึด APP_URL (ไม่ใช่ host จาก request) และไม่ใช่ที่อื่นนอกเซิร์ฟเวอร์เรา
        $this->assertSame(
            rtrim((string) config('app.url'), '/').'/storage/app-downloads/ThaiPrompt-APP-3.388.0.apk?h='.substr($apk['sha256'], 0, 12),
            $res->json('data.latest.download_url')
        );
        $this->assertStringNotContainsStringIgnoringCase('github', (string) $res->getContent());

        // ติดตั้งตัวนี้อยู่แล้ว / ใหม่กว่า = ไม่มีอัปเดต
        $this->getJson('/api/v1/app/update?platform=android&build=45')->assertJsonPath('data.update_available', false);
        $this->getJson('/api/v1/app/update?platform=android&build=46')->assertJsonPath('data.update_available', false);
    }

    public function test_build_falls_back_to_the_app_build_header(): void
    {
        app(AppDownloadService::class)->publish($this->fakeApk(), '3.388.0', 45);

        $this->getJson('/api/v1/app/update', ['X-App-Build' => '44'])
            ->assertJsonPath('data.current_build', 44)
            ->assertJsonPath('data.update_available', true);

        // ไม่รู้ build เลย = ไม่เสนอ (ไม่ทำให้ทุกเครื่องเห็นว่าต้องอัปเดต)
        $this->getJson('/api/v1/app/update?build=abc')->assertJsonPath('data.update_available', false);
    }

    public function test_min_build_forces_the_update_only_for_older_installs(): void
    {
        app(AppDownloadService::class)->publish($this->fakeApk(), '3.388.0', 45, [], 45);

        $this->getJson('/api/v1/app/update?build=44')
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.min_supported_build', 45);
        $this->getJson('/api/v1/app/update?build=45')
            ->assertJsonPath('data.required', false)
            ->assertJsonPath('data.update_available', false);

        // ปล่อยตัวถัดไปโดยไม่ใส่ --min-build = ใช้ค่าเดิม
        app(AppDownloadService::class)->publish($this->fakeApk(), '3.389.0', 46);
        $this->getJson('/api/v1/app/update?build=44')->assertJsonPath('data.required', true);
        $this->getJson('/api/v1/app/update?build=45')
            ->assertJsonPath('data.required', false)
            ->assertJsonPath('data.update_available', true);
    }

    public function test_min_build_above_the_released_build_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        app(AppDownloadService::class)->publish($this->fakeApk(), '3.388.0', 45, [], 46);
    }

    public function test_admin_switch_off_hides_the_update(): void
    {
        app(AppDownloadService::class)->publish($this->fakeApk(), '3.388.0', 45);
        SiteSetting::getSetting()->update(['app_apk_enabled' => false]);
        Cache::forget('site_settings');

        $this->getJson('/api/v1/app/update?build=44')
            ->assertJsonPath('data.update_available', false)
            ->assertJsonPath('data.latest', null);
    }

    public function test_older_manifest_without_md5_gets_it_computed(): void
    {
        $source = $this->fakeApk();
        app(AppDownloadService::class)->publish($source, '3.387.0', 44);

        // latest.json ของรุ่นก่อนหน้าไม่มี md5
        $meta = json_decode((string) Storage::disk('public')->get('app-downloads/latest.json'), true);
        unset($meta['md5'], $meta['notes'], $meta['min_supported_build']);
        Storage::disk('public')->put('app-downloads/latest.json', json_encode($meta));

        $this->getJson('/api/v1/app/update?build=43')
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.latest.md5', md5_file($source))
            ->assertJsonPath('data.latest.notes', []);
    }

    public function test_publish_command_takes_notes_and_min_build(): void
    {
        $this->artisan('app:publish-apk', [
            'path' => $this->fakeApk(),
            '--app-version' => '3.388.0',
            '--version-code' => '45',
            '--notes' => ['อัปเดตในแอปพร้อมเกจ', 'แก้ข้อความ'],
            '--min-build' => '44',
        ])->assertSuccessful();

        $latest = app(AppDownloadService::class)->latest();
        $this->assertSame(['อัปเดตในแอปพร้อมเกจ', 'แก้ข้อความ'], $latest['notes']);
        $this->assertSame(44, $latest['min_supported_build']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $latest['md5']);

        $this->artisan('app:publish-apk', ['path' => $this->fakeApk(), '--app-version' => '3.389.0', '--version-code' => '46', '--min-build' => 'x'])
            ->assertFailed();
    }
}
