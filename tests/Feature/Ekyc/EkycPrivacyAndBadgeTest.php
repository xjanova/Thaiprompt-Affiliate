<?php

namespace Tests\Feature\Ekyc;

use App\Models\KycVerification;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\Ekyc\EkycService;
use App\Services\Media\ProfilePhotoService;
use App\Services\Rider\RiderSocialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 รูปโปรไฟล์ = รูปที่ผู้ใช้เลือก (ทุกคนเห็น) · ป้าย verified ใน PersonCard · ลบบัญชีแล้วรูป KYC หาย
 */
#[Group('ekyc')]
class EkycPrivacyAndBadgeTest extends TestCase
{
    use BuildsEkycFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
        $this->configureEkyc();
    }

    public function test_chosen_avatar_is_visible_to_everyone(): void
    {
        $service = app(ProfilePhotoService::class);
        $subject = User::factory()->create(['line_picture_url' => 'https://profile.line-scdn.net/abc']);
        $viewer = User::factory()->create();

        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($subject, $subject));
        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($subject, $viewer), 'คนอื่นต้องเห็นรูปที่ผู้ใช้เลือก');
        $this->assertSame('https://profile.line-scdn.net/abc', $service->urlFor($subject, null));

        $nothing = User::factory()->create(['line_picture_url' => null, 'profile_picture' => null]);
        $this->assertNull($service->urlFor($nothing, $viewer), 'ไม่มีรูป = null (ไม่ใช่รูปตัวอักษรอัตโนมัติ)');
    }

    public function test_new_avatar_upload_replaces_an_old_live_photo(): void
    {
        $service = app(ProfilePhotoService::class);
        $subject = User::factory()->create(['line_picture_url' => null, 'profile_picture' => null]);
        $viewer = User::factory()->create();

        // ผู้ใช้เคยถ่ายรูปสดไว้ตอนรอบ 2 (ตอนที่ยังบังคับ)
        Storage::disk('local')->put('profile-photos/'.$subject->id.'/old.jpg', 'jpeg-bytes');
        $subject->forceFill(['profile_photo_private_path' => 'profile-photos/'.$subject->id.'/old.jpg', 'profile_photo_taken_at' => now()])->save();
        $this->assertStringContainsString('/media/profile-photo/', (string) $service->urlFor($subject->fresh(), $viewer));

        Sanctum::actingAs($subject);
        $png = \Illuminate\Http\UploadedFile::fake()->image('cat.png', 400, 400);
        $this->postJson('/api/v1/profile/avatar', ['avatar' => $png])->assertOk();

        $fresh = $subject->fresh();
        $this->assertNull($fresh->profile_photo_private_path, 'รูปถ่ายสดเก่าต้องเลิกใช้');
        Storage::disk('local')->assertMissing('profile-photos/'.$subject->id.'/old.jpg');
        $url = (string) $service->urlFor($fresh, $viewer);
        // (ใน test ไฟล์อยู่บน disk ปลอม → accessor profile_picture_url ตกไป ui-avatars → urlFor คืน null ได้ บน prod ได้ลิงก์ /storage/avatars/...)
        $this->assertStringNotContainsString('/media/profile-photo/', $url, 'คนอื่นต้องเห็นอวาตาร์ใหม่ ไม่ใช่รูปถ่ายสดเก่า');
        $this->assertNotNull($fresh->profile_picture, 'บันทึกอวาตาร์ใหม่แล้ว');
    }

    public function test_person_cards_and_profile_payloads_carry_verified_flag(): void
    {
        $verifiedRider = $this->makeRider();
        $verifiedRider->user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();
        $plainRider = $this->makeRider();
        $viewer = User::factory()->create();

        $social = app(RiderSocialService::class);
        $card = $social->personCard($verifiedRider->fresh('user'), $viewer);
        $this->assertTrue($card['verified']);
        $this->assertFalse($social->personCard($plainRider->fresh('user'), $viewer)['verified']);

        $this->assertTrue(EkycService::badge($verifiedRider->user->fresh()));
        $this->assertFalse(EkycService::badge(null));

        Sanctum::actingAs($verifiedRider->user->fresh());
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.verified', true);
        $this->getJson('/api/v1/profile')->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.kyc_status', 'approved');

        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.verified', false);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.kyc_status', 'none');
    }

    public function test_account_deletion_removes_all_ekyc_files_and_rows(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser(['password' => Hash::make('secret-pass-123')]);
        $this->runFullFlow()->assertJsonPath('data.decision', 'approved');
        // รอบที่ทำค้างอีกรอบไม่ได้ (ยืนยันแล้ว) — วางไฟล์ค้างจำลองไว้ในโฟลเดอร์ของผู้ใช้
        Storage::disk('local')->put('ekyc/'.$user->id.'/orphan-session/card-x.enc', 'x');

        $kyc = KycVerification::where('user_id', $user->id)->firstOrFail();
        $paths = [$kyc->id_card_image, $kyc->card_face_path, $kyc->best_frame_path];
        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => 'secret-pass-123',
        ])->assertOk();

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
        $this->assertSame([], Storage::disk('local')->allFiles('ekyc/'.$user->id));
        $this->assertSame(0, KycVerification::where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('kyc_verifications')->where('id_number_hash', EkycService::idHash(self::VALID_ID))->count(), 'เลขบัตรใช้ยืนยันบัญชีใหม่ได้หลังลบบัญชี');
    }
}
