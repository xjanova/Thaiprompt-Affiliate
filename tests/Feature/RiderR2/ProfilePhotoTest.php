<?php

namespace Tests\Feature\RiderR2;

use App\Http\Middleware\EnsureProfilePhoto;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Services\Media\ProfilePhotoService;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 📸 ไรเดอร์รอบ 2 — รูปโปรไฟล์ถ่ายสด + ลายน้ำ + ด่าน profile.photo (ต้องใช้ MySQL)
 *
 * ครอบคลุม:
 *   - อัปโหลด: ตรวจชนิดไฟล์จริง/ขนาด · เข้ารหัส JPEG ใหม่ ≤ 1024 px · EXIF/GPS หาย · เก็บบน private disk
 *   - คอลัมน์ profile_photo_* แก้ผ่าน mass assignment ไม่ได้
 *   - ถ่ายใหม่ → ลบไฟล์เก่า · กดซ้ำไม่เหลือไฟล์กำพร้า · ลิงก์เวอร์ชันเก่าเปิดไม่ได้
 *   - รูปลายน้ำ: ต้องมีลายเซ็น (ไม่มี/แก้ = 403) · ได้ JPEG · แคชต่อวัน · รหัสผู้ดูเป็น HMAC ไม่ใช่ id
 *   - ด่าน profile.photo: บังคับเฉพาะ X-App-Build ≥ 43 · ปิดรับงาน (offline) ไม่ถูกบล็อกเลย
 *   - ล้างแคชรายวัน profile-photo:purge-cache
 */
#[Group('rider-r2')]
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();

        config(['profile_photo.required' => true, 'profile_photo.min_build' => 43]);
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
    }

    // =====================================================
    // GET / POST /me/profile-photo
    // =====================================================

    public function test_status_without_photo(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/profile-photo')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.has_photo', false)
            ->assertJsonPath('data.taken_at', null)
            ->assertJsonPath('data.photo_url', null)
            ->assertJsonPath('data.required', true);
    }

    public function test_upload_reencodes_scales_and_strips_exif_gps(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $source = $this->jpegWithExif(1600, 1200);
        // ตรวจว่าไฟล์ต้นทางมี EXIF + GPS จริง (ไม่งั้นเทสต์ไม่ได้พิสูจน์อะไร)
        $exifBefore = @exif_read_data('data://image/jpeg;base64,'.base64_encode($source), null, true);
        $this->assertSame('TESTCAM', $exifBefore['IFD0']['Make'] ?? null);
        $this->assertArrayHasKey('GPS', $exifBefore);

        $response = $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('live.jpg', $source),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.has_photo', true)
            ->assertJsonPath('data.required', true);
        $this->assertNotNull($response->json('data.taken_at'));
        $this->assertStringContainsString('/api/v1/media/profile-photo/'.$user->id.'/', (string) $response->json('data.photo_url'));

        $user->refresh();
        $path = $user->profile_photo_private_path;
        $this->assertMatchesRegularExpression('#^profile-photos/'.$user->id.'/[0-9a-f-]{36}\.jpg$#', (string) $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringNotContainsString((string) $path, $response->getContent(), 'ห้ามคืน path ไฟล์ให้แอป');

        $stored = Storage::disk('local')->get($path);
        $info = getimagesizefromstring($stored);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        $this->assertSame(1024, max($info[0], $info[1]), 'ด้านยาวต้องถูกย่อเหลือ 1024 px');

        $exifAfter = @exif_read_data('data://image/jpeg;base64,'.base64_encode($stored), null, true);
        $this->assertTrue(
            $exifAfter === false || (! isset($exifAfter['GPS']) && ! isset($exifAfter['IFD0']['Make'])),
            'EXIF/GPS ต้องถูกตัดทิ้ง'
        );
        $this->assertStringNotContainsString('TESTCAM', $stored);
    }

    public function test_upload_rejects_non_images_and_oversized_files(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // ไม่ส่งไฟล์
        $this->post('/api/v1/me/profile-photo', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาถ่ายรูปโปรไฟล์');

        // ข้อความธรรมดาตั้งชื่อ .jpg
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('fake.jpg', str_repeat('not an image ', 200)),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // SVG (สคริปต์ฝังได้) ไม่รับ
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        // หัวไฟล์ JPEG แต่ข้างในเสีย → ถอดรหัสไม่ได้
        $broken = substr($this->jpeg(400, 400), 0, 300).str_repeat("\x00", 500);
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('broken.jpg', $broken),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // ใหญ่เกิน 8 MB
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        // เล็กเกินจนเห็นหน้าไม่ชัด
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('tiny.jpg', $this->jpeg(80, 80)),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PHOTO_TOO_SMALL');

        $this->assertNull($user->fresh()->profile_photo_private_path);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }

    public function test_png_and_webp_are_accepted_and_stored_as_jpeg(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $img = imagecreatetruecolor(500, 400);
        imagefill($img, 0, 0, imagecolorallocate($img, 30, 120, 200));
        ob_start();
        imagepng($img);
        $png = ob_get_clean();

        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('live.png', $png),
        ], ['Accept' => 'application/json'])->assertOk();

        $stored = Storage::disk('local')->get($user->fresh()->profile_photo_private_path);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($stored)[2]);
        $this->assertSame([500, 400], array_slice(getimagesizefromstring($stored), 0, 2), 'รูปเล็กกว่า 1024 ห้ามขยาย');

        if (function_exists('imagewebp')) {
            ob_start();
            imagewebp($img);
            $webp = ob_get_clean();

            $this->post('/api/v1/me/profile-photo', [
                'photo' => UploadedFile::fake()->createWithContent('live.webp', $webp),
            ], ['Accept' => 'application/json'])->assertOk();
        }
    }

    public function test_profile_photo_columns_are_not_mass_assignable_nor_serialized(): void
    {
        $user = User::factory()->create();

        $user->fill([
            'profile_photo_private_path' => 'profile-photos/1/hack.jpg',
            'profile_photo_taken_at' => now(),
        ]);
        $this->assertNull($user->profile_photo_private_path);
        $this->assertNull($user->profile_photo_taken_at);

        $user->update(['profile_photo_private_path' => '../../.env']);
        $this->assertNull($user->fresh()->profile_photo_private_path);

        // path ภายในต้องไม่หลุดไปกับ /me หรือ toArray()
        $user->forceFill(['profile_photo_private_path' => 'profile-photos/'.$user->id.'/a.jpg'])->save();
        $this->assertArrayNotHasKey('profile_photo_private_path', $user->fresh()->toArray());
    }

    public function test_reupload_deletes_previous_file_and_old_link_stops_working(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();
        Sanctum::actingAs($user);

        $this->uploadPhoto();
        $first = $user->fresh()->profile_photo_private_path;
        $oldUrl = app(ProfilePhotoService::class)->urlFor($user->fresh(), $viewer);
        $this->get($oldUrl)->assertOk();

        // กดถ่ายซ้ำ 2 ครั้ง (double tap) — ต้องเหลือไฟล์เดียว
        $this->uploadPhoto();
        $this->uploadPhoto();

        $latest = $user->fresh()->profile_photo_private_path;
        $this->assertNotSame($first, $latest);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($latest);
        $this->assertSame([$latest], Storage::disk('local')->allFiles('profile-photos/'.$user->id));

        // ลิงก์ของรูปเวอร์ชันเก่า (ลายเซ็นยังถูก) → 404
        $this->get($oldUrl)->assertNotFound();
    }

    // =====================================================
    // รูปพร้อมลายน้ำ (signed)
    // =====================================================

    public function test_watermarked_photo_requires_valid_signature_and_returns_jpeg(): void
    {
        [$subject, $viewer] = $this->subjectWithPhoto();
        $service = app(ProfilePhotoService::class);

        $url = $service->urlFor($subject, $viewer);
        $this->assertNotNull($url);
        $code = $service->viewerCode($viewer->id);
        $this->assertStringContainsString('/'.$code.'/', $url);
        $this->assertStringNotContainsString('/'.$viewer->id.'/', parse_url($url, PHP_URL_PATH) ?: '', 'path ต้องไม่มี user id ของผู้ดู');

        $response = $this->get($url);
        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        $body = $response->getContent();
        $this->assertStringStartsWith("\xFF\xD8", $body);
        $info = getimagesizefromstring($body);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);

        // ลายน้ำเปลี่ยนพิกเซลจริง (ไม่ใช่ส่งต้นฉบับออกไป)
        $original = Storage::disk('local')->get($subject->profile_photo_private_path);
        $this->assertNotSame($original, $body);
        $this->assertGreaterThan(500, $this->pixelDifference($original, $body), 'ต้องมีลายน้ำวาดทับ');

        // ไม่มีลายเซ็น
        $path = parse_url($url, PHP_URL_PATH);
        $this->get($path)->assertForbidden();

        // แก้รหัสผู้ดู / เวอร์ชัน / เจ้าของรูป (ลายเซ็นเดิม) → 403
        $otherCode = $service->viewerCode($viewer->id + 1);
        $this->get(str_replace('/'.$code.'/', '/'.$otherCode.'/', $url))->assertForbidden();

        $version = $service->photoVersion($subject);
        $this->get(str_replace('/'.$version, '/'.str_repeat('a', 12), $url))->assertForbidden();

        $this->get(str_replace('/profile-photo/'.$subject->id.'/', '/profile-photo/'.$viewer->id.'/', $url))->assertForbidden();

        // หมดอายุ
        $this->travel(2)->hours();
        $this->get($url)->assertForbidden();
    }

    public function test_render_is_cached_per_day_and_viewer(): void
    {
        [$subject, $viewer] = $this->subjectWithPhoto();
        $other = User::factory()->create();
        $service = app(ProfilePhotoService::class);

        $this->get($service->urlFor($subject, $viewer))->assertOk();
        $this->get($service->urlFor($subject, $other))->assertOk();

        $day = CarbonImmutable::now(ProfilePhotoService::TIMEZONE)->format('Ymd');
        $files = Storage::disk('local')->allFiles(ProfilePhotoService::CACHE_DIR.'/'.$day.'/'.$subject->id);
        $this->assertCount(2, $files, 'แคชแยกต่อผู้ดู');

        // เปิดซ้ำได้ไฟล์เดิมจากแคช
        $url = $service->urlFor($subject, $viewer);
        $first = $this->get($url)->getContent();
        $second = $this->get($url)->getContent();
        $this->assertSame($first, $second);
        $this->assertCount(2, Storage::disk('local')->allFiles(ProfilePhotoService::CACHE_DIR.'/'.$day.'/'.$subject->id));

        // URL คงที่ภายในช่วงเวลาเดียวกัน (แอปแคชรูปได้)
        $this->assertSame($url, $service->urlFor($subject, $viewer));
    }

    public function test_viewer_code_is_short_hmac_not_raw_id(): void
    {
        $service = app(ProfilePhotoService::class);

        $codes = [];
        foreach ([1, 2, 3, 10, 11, 12, 100, 1000] as $id) {
            $code = $service->viewerCode($id);
            $this->assertMatchesRegularExpression('/^['.ProfilePhotoService::CODE_ALPHABET.']{6}$/', $code);
            $this->assertSame($code, $service->viewerCode($id), 'รหัสต้องคงที่');
            $this->assertNotSame((string) $id, $code);
            $codes[] = $code;
        }
        $this->assertCount(count($codes), array_unique($codes));
        $this->assertSame(ProfilePhotoService::GUEST_CODE, $service->viewerCode(null));

        // เปลี่ยน app key → รหัสเปลี่ยน (เดาจาก id ไม่ได้ถ้าไม่รู้ key)
        $before = $service->viewerCode(42);
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->assertNotSame($before, $service->viewerCode(42));
    }

    public function test_legacy_avatar_is_visible_to_everyone_when_no_live_photo(): void
    {
        $service = app(ProfilePhotoService::class);

        $withLine = User::factory()->create(['line_picture_url' => 'https://profile.line-scdn.net/abc']);
        $other = User::factory()->create();

        // เจ้าของเห็นรูปเดิมของตัวเอง (หน้าโปรไฟล์)
        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($withLine, $withLine));

        // 🪪 (2026-10-04 · AI eKYC) เจ้าของสั่ง: รูปโปรไฟล์ = รูปที่ผู้ใช้เลือก ทุกคนเห็น
        //    ความน่าเชื่อถือมาจากป้าย "ยืนยันตัวตนแล้ว" ไม่ใช่จากรูป (ยกเลิกกติกา money-review M5 เดิม)
        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($withLine, $other));
        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($withLine, null));

        $nothing = User::factory()->create(['line_picture_url' => null, 'profile_picture' => null]);
        $this->assertNull($service->urlFor($nothing, $nothing), 'ไม่มีรูปเลย = null (ไม่ใช่รูปตัวอักษรอัตโนมัติ)');
        $this->assertNull($service->urlFor($nothing, $withLine));

        $this->get('/api/v1/media/profile-photo/'.$nothing->id.'/ABCDEF/'.str_repeat('a', 12))->assertForbidden();
    }

    public function test_account_deletion_removes_live_photo_and_watermarked_copies(): void
    {
        $user = User::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('secret-pass-123')]);
        $viewer = User::factory()->create();
        $service = app(ProfilePhotoService::class);

        $user = $service->store($user, UploadedFile::fake()->createWithContent('live.jpg', $this->jpeg(600, 800)));
        $path = $user->profile_photo_private_path;
        $url = $service->urlFor($user, $viewer);
        $this->get($url)->assertOk();
        $day = CarbonImmutable::now(ProfilePhotoService::TIMEZONE)->format('Ymd');
        $this->assertNotEmpty(Storage::disk('local')->allFiles(ProfilePhotoService::CACHE_DIR.'/'.$day.'/'.$user->id));

        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => \App\Services\AccountDeletionService::CONFIRM_TEXT,
            'password' => 'secret-pass-123',
        ])->assertOk();

        Storage::disk('local')->assertMissing($path);
        $this->assertSame([], Storage::disk('local')->allFiles(ProfilePhotoService::CACHE_DIR.'/'.$day.'/'.$user->id));
        $row = \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->first();
        $this->assertNull($row->profile_photo_private_path);
        $this->assertNull($row->profile_photo_taken_at);
        $this->get($url)->assertNotFound();
    }

    public function test_purge_command_removes_old_render_cache_only(): void
    {
        $disk = Storage::disk('local');
        $today = CarbonImmutable::now(ProfilePhotoService::TIMEZONE);

        $disk->put(ProfilePhotoService::CACHE_DIR.'/'.$today->format('Ymd').'/1/a.jpg', 'x');
        $disk->put(ProfilePhotoService::CACHE_DIR.'/'.$today->subDay()->format('Ymd').'/1/a.jpg', 'x');
        $disk->put(ProfilePhotoService::CACHE_DIR.'/'.$today->subDays(3)->format('Ymd').'/1/a.jpg', 'x');
        $disk->put('profile-photos/1/original.jpg', 'keep');

        $this->assertSame(0, Artisan::call('profile-photo:purge-cache', ['--keep-days' => 1]));

        $disk->assertExists(ProfilePhotoService::CACHE_DIR.'/'.$today->format('Ymd').'/1/a.jpg');
        $disk->assertExists(ProfilePhotoService::CACHE_DIR.'/'.$today->subDay()->format('Ymd').'/1/a.jpg');
        $disk->assertMissing(ProfilePhotoService::CACHE_DIR.'/'.$today->subDays(3)->format('Ymd').'/1/a.jpg');
        $disk->assertExists('profile-photos/1/original.jpg');
    }

    // =====================================================
    // ด่าน profile.photo
    // =====================================================

    public function test_middleware_enforced_only_for_new_app_builds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $routes = [
            '/api/v1/cart/checkout',
            '/api/v1/fresh-market/orders',
            '/api/v1/seller/application',
        ];

        foreach ($routes as $route) {
            // แอปรุ่นเก่า / ไม่ส่ง header / ค่าแปลก → ไม่ถูกบล็อกด้วยด่านรูป
            foreach ([[], ['X-App-Build' => '42'], ['X-App-Build' => '43abc']] as $headers) {
                $code = $this->postJson($route, [], $headers)->json('code');
                $this->assertNotSame(EnsureProfilePhoto::CODE, $code, "{$route} ต้องไม่บล็อกแอปรุ่นเก่า");
            }

            // แอปรุ่นใหม่ → 422 PROFILE_PHOTO_REQUIRED ภาษาไทย
            $this->postJson($route, [], ['X-App-Build' => '43'])
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('code', 'PROFILE_PHOTO_REQUIRED')
                ->assertJsonPath('message', 'กรุณาถ่ายรูปโปรไฟล์ก่อนใช้งานส่วนนี้');

            // แอปรุ่น eKYC (≥ 44) ไม่มีหน้าถ่ายรูปสดแล้ว → ด่านรูปไม่บังคับ (ใช้ด่าน eKYC แทน)
            foreach (['44', '57'] as $build) {
                $this->assertNotSame(EnsureProfilePhoto::CODE, $this->postJson($route, [], ['X-App-Build' => $build])->json('code'), "{$route} build {$build}");
            }
        }

        // ปิดฟีเจอร์ → ไม่บังคับ
        config(['profile_photo.required' => false]);
        $this->assertNotSame(EnsureProfilePhoto::CODE, $this->postJson('/api/v1/cart/checkout', [], ['X-App-Build' => '43'])->json('code'));
        config(['profile_photo.required' => true]);

        // ถ่ายรูปแล้ว → ผ่านด่าน
        $this->uploadPhoto();
        foreach ($routes as $route) {
            $this->assertNotSame(EnsureProfilePhoto::CODE, $this->postJson($route, [], ['X-App-Build' => '43'])->json('code'));
        }
    }

    public function test_rider_going_offline_is_never_blocked_but_online_requires_photo(): void
    {
        $rider = $this->makeRider();
        Sanctum::actingAs($rider->user);
        $headers = ['X-App-Build' => '43'];

        // เปิดรับงานโดยยังไม่มีรูป → 422
        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52], $headers)
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFILE_PHOTO_REQUIRED');

        // ปิดรับงานต้องได้เสมอ (ไรเดอร์ที่ online ค้างอยู่ก่อนอัปเดตแอป)
        $rider->forceFill(['availability' => 'online'])->save();
        $this->postJson('/api/v1/rider/availability', ['availability' => 'offline'], $headers)
            ->assertOk()
            ->assertJsonPath('data.availability', 'offline');
        $this->assertSame('offline', $rider->fresh()->availability);

        // แอปรุ่นเก่าเปิดรับงานได้ตามเดิม
        $this->assertNotSame(EnsureProfilePhoto::CODE, $this->postJson('/api/v1/rider/availability', [
            'availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52,
        ])->json('code'));

        // ถ่ายรูปแล้ว → เปิดรับงานด้วยแอปรุ่นใหม่ได้
        $this->uploadPhoto();
        $this->postJson('/api/v1/rider/availability', ['availability' => 'offline'], $headers)->assertOk();
        $this->assertNotSame(EnsureProfilePhoto::CODE, $this->postJson('/api/v1/rider/availability', [
            'availability' => 'online', 'latitude' => 13.73, 'longitude' => 100.52,
        ], $headers)->json('code'));
    }

    public function test_upload_endpoint_is_throttled(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $statuses = [];
        for ($i = 0; $i < 8; $i++) {
            $statuses[] = $this->post('/api/v1/me/profile-photo', [], ['Accept' => 'application/json'])->status();
        }

        $this->assertContains(429, $statuses, 'ส่งรูปถี่เกินต้องโดนจำกัด');
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function uploadPhoto(): void
    {
        $this->post('/api/v1/me/profile-photo', [
            'photo' => UploadedFile::fake()->createWithContent('live.jpg', $this->jpeg(900, 1200)),
        ], ['Accept' => 'application/json'])->assertOk();
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function subjectWithPhoto(): array
    {
        $subject = User::factory()->create();
        $viewer = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent('live.jpg', $this->jpeg(800, 1000));
        $subject = app(ProfilePhotoService::class)->store($subject, $file);

        return [$subject->fresh(), $viewer];
    }

    private function makeRider(): Rider
    {
        $user = User::factory()->create();

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => 13.73,
            'last_longitude' => 100.52,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }

    /**
     * JPEG ลายไล่สี (ให้ลายน้ำมีผลต่อพิกเซลวัดได้)
     */
    private function jpeg(int $width, int $height): string
    {
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y += 8) {
            $color = imagecolorallocate($img, (int) (40 + ($y / max(1, $height)) * 120), 90, 140);
            imagefilledrectangle($img, 0, $y, $width, $y + 7, $color);
        }

        ob_start();
        imagejpeg($img, null, 90);

        return (string) ob_get_clean();
    }

    /**
     * JPEG ที่ฝัง EXIF (Make=TESTCAM) + GPS (GPSLatitudeRef=N) ไว้ใน APP1
     */
    private function jpegWithExif(int $width, int $height): string
    {
        $jpeg = $this->jpeg($width, $height);

        // TIFF little-endian: IFD0 (Make + GPSInfo pointer) → ข้อความ Make → GPS IFD
        $tiff = 'II'.pack('v', 42).pack('V', 8);
        $tiff .= pack('v', 2);
        $tiff .= pack('vvVV', 0x010F, 2, 8, 38);          // Make → offset 38
        $tiff .= pack('vvVV', 0x8825, 4, 1, 46);          // GPSInfo → offset 46
        $tiff .= pack('V', 0);                             // ไม่มี IFD ถัดไป
        $tiff .= "TESTCAM\0";                              // offset 38..45
        $tiff .= pack('v', 1);                             // GPS IFD ที่ offset 46
        $tiff .= pack('vvV', 0x0001, 2, 2)."N\0\0\0";      // GPSLatitudeRef = "N"
        $tiff .= pack('V', 0);

        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    /**
     * จำนวนจุดสุ่มที่สีต่างกันชัดเจนระหว่างสองรูป (ขนาดเท่ากัน)
     */
    private function pixelDifference(string $a, string $b): int
    {
        $ia = imagecreatefromstring($a);
        $ib = imagecreatefromstring($b);
        $w = min(imagesx($ia), imagesx($ib));
        $h = min(imagesy($ia), imagesy($ib));

        $diff = 0;
        for ($y = 0; $y < $h; $y += 4) {
            for ($x = 0; $x < $w; $x += 4) {
                $ca = imagecolorat($ia, $x, $y);
                $cb = imagecolorat($ib, $x, $y);
                $d = abs((($ca >> 16) & 0xFF) - (($cb >> 16) & 0xFF))
                    + abs((($ca >> 8) & 0xFF) - (($cb >> 8) & 0xFF))
                    + abs(($ca & 0xFF) - ($cb & 0xFF));
                if ($d > 40) {
                    $diff++;
                }
            }
        }

        return $diff;
    }
}
