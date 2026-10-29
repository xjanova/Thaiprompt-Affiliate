<?php

namespace Tests\Feature;

use App\Models\KycAccessLog;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\Kyc\KycDocumentStorage;
use App\Services\LineKycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 🔒 รูปเอกสาร KYC แบบเดิม (บัตร/คู่บัตร) อยู่บน private disk และเปิดได้ผ่าน route ที่ตรวจสิทธิ์เท่านั้น (2026-10-29)
 *
 * เดิมเก็บบน public disk → ใครได้ URL /storage/kyc/… ก็เปิดบัตรประชาชนลูกค้าได้
 */
class KycPrivateImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        // กันหลุดไปเรียก Google Vision / LINE push จริง
        Http::fake();
    }

    /**
     * สมาชิกทั่วไป (role user — เข้าพื้นที่ /user ได้)
     */
    private function member(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /**
     * คำขอ KYC แบบเดิมที่มีรูปจริงบน private disk
     */
    private function privateKyc(User $owner, string $status = 'pending'): KycVerification
    {
        $dir = app(KycDocumentStorage::class)->directoryFor($owner->id);
        Storage::disk('local')->put($dir.'/card.webp', 'CARD-BYTES');
        Storage::disk('local')->put($dir.'/selfie.webp', 'SELFIE-BYTES');

        return KycVerification::create([
            'user_id' => $owner->id,
            'id_card_image' => $dir.'/card.webp',
            'selfie_image' => $dir.'/selfie.webp',
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    /**
     * คำขอเก่าที่รูปยังค้างบน public disk (ก่อนรันคำสั่งย้าย)
     */
    private function legacyKyc(User $owner, string $card = 'kyc/id-cards/legacy-card.webp', string $selfie = 'kyc/selfies/legacy-selfie.webp'): KycVerification
    {
        Storage::disk('public')->put($card, 'LEGACY-CARD');
        Storage::disk('public')->put($selfie, 'LEGACY-SELFIE');

        return KycVerification::create([
            'user_id' => $owner->id,
            'id_card_image' => $card,
            'selfie_image' => $selfie,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    private function assertPrivateImageResponse($response, string $expectedBody): void
    {
        $response->assertOk();
        $this->assertSame($expectedBody, $response->streamedContent());
        $cache = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_web_submission_stores_images_on_private_disk_only(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('user.kyc.store'), [
            'id_card_image' => UploadedFile::fake()->image('บัตร สมชาย.jpg', 900, 560),
            'selfie_image' => UploadedFile::fake()->image('selfie.png', 600, 800),
        ])->assertRedirect(route('user.kyc.index'));

        $kyc = KycVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $prefix = 'kyc-private/'.$user->id.'/';

        $this->assertStringStartsWith($prefix, $kyc->id_card_image);
        $this->assertStringStartsWith($prefix, $kyc->selfie_image);
        Storage::disk('local')->assertExists([$kyc->id_card_image, $kyc->selfie_image]);
        // ไม่มีอะไรเหลือบน public disk (รวมไฟล์ชั่วคราวของ OCR)
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles('kyc-temp'));

        $this->assertSame(route('user.kyc.image', [$kyc, 'id_card']), $kyc->idCardImageUrl());
        $this->assertSame(route('admin.kyc.image', [$kyc, 'selfie']), $kyc->selfieImageUrl('admin'));

        $res = $this->actingAs($user)->get(route('user.kyc.image', [$kyc, 'id_card']));
        $res->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
    }

    public function test_owner_can_view_own_images_on_status_page(): void
    {
        $owner = $this->member();
        $kyc = $this->privateKyc($owner);

        $this->assertPrivateImageResponse($this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'id_card'])), 'CARD-BYTES');
        $this->assertPrivateImageResponse($this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'selfie'])), 'SELFIE-BYTES');

        $this->actingAs($owner)->get(route('user.kyc.show', $kyc))
            ->assertOk()
            ->assertSee(route('user.kyc.image', [$kyc, 'id_card']), false)
            ->assertSee(route('user.kyc.image', [$kyc, 'selfie']), false)
            ->assertDontSee('storage/kyc', false)
            ->assertDontSee($kyc->id_card_image, false);
    }

    public function test_other_member_gets_404_and_guest_is_redirected(): void
    {
        $owner = $this->member();
        $kyc = $this->privateKyc($owner);
        $other = $this->member();

        $this->actingAs($other)->get(route('user.kyc.image', [$kyc, 'id_card']))->assertNotFound();
        $this->actingAs($other)->get(route('user.kyc.image', [$kyc, 'selfie']))->assertNotFound();
        // หลังบ้านแอดมิน: สมาชิกทั่วไปเข้าไม่ได้
        $this->actingAs($other)->getJson(route('admin.kyc.image', [$kyc, 'id_card']))->assertForbidden();
        // เลขคำขอที่ไม่มีอยู่ = 404 เหมือนกัน (เดาเลขไม่ได้ว่ามีหรือไม่มี)
        $this->actingAs($other)->get('/user/kyc/999999/image/id_card')->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->get(route('user.kyc.image', [$kyc, 'id_card']))->assertRedirect(route('login'));
        $this->getJson(route('user.kyc.image', [$kyc, 'id_card']))->assertUnauthorized();
        // signed route ที่ไม่มีลายเซ็น
        $this->get(route('api.v1.kyc.image', [$kyc, 'id_card']))->assertForbidden();

        $this->assertSame(0, KycAccessLog::count());
    }

    public function test_admin_views_through_admin_route_and_every_view_is_logged(): void
    {
        $owner = $this->member();
        $kyc = $this->privateKyc($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertPrivateImageResponse($this->actingAs($admin)->get(route('admin.kyc.image', [$kyc, 'id_card'])), 'CARD-BYTES');
        $this->assertPrivateImageResponse($this->actingAs($admin)->get(route('admin.kyc.image', [$kyc, 'selfie'])), 'SELFIE-BYTES');

        $this->assertSame(['id_card', 'selfie'], KycAccessLog::where('kyc_verification_id', $kyc->id)->where('viewer_id', $admin->id)->orderBy('kind')->pluck('kind')->all());
        $this->assertSame($owner->id, KycAccessLog::first()->subject_user_id);

        // ชนิดของ eKYC บนแถวแบบเดิม = 404 · ทาง /user ของคนอื่น (แม้เป็นแอดมิน) = 404
        $this->actingAs($admin)->get(route('admin.kyc.image', [$kyc, 'card']))->assertNotFound();
        $this->actingAs($admin)->get(route('user.kyc.image', [$kyc, 'id_card']))->assertNotFound();

        // หน้าตรวจของแอดมินชี้รูปไป route ที่ตรวจสิทธิ์
        $this->actingAs($admin)->get(route('admin.kyc.show', $kyc))
            ->assertOk()
            ->assertSee(route('admin.kyc.image', [$kyc, 'id_card']), false)
            ->assertSee(route('admin.kyc.image', [$kyc, 'selfie']), false)
            ->assertDontSee('storage/kyc', false);

        // ไฟล์หาย → 404 และไม่นับเป็นการเปิดดู
        Storage::disk('local')->delete($kyc->id_card_image);
        $before = KycAccessLog::count();
        $this->actingAs($admin)->get(route('admin.kyc.image', [$kyc, 'id_card']))->assertNotFound();
        $this->assertSame($before, KycAccessLog::count());
    }

    public function test_legacy_public_files_still_display_during_rollout(): void
    {
        $owner = $this->member();
        $kyc = $this->legacyKyc($owner);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($kyc->hasIdCardImage());
        $this->assertSame(route('user.kyc.image', [$kyc, 'id_card']), $kyc->idCardImageUrl());
        $this->assertPrivateImageResponse($this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'id_card'])), 'LEGACY-CARD');
        $this->assertPrivateImageResponse($this->actingAs($admin)->get(route('admin.kyc.image', [$kyc, 'selfie'])), 'LEGACY-SELFIE');
    }

    public function test_paths_outside_kyc_folders_are_never_served_from_private_disk(): void
    {
        $owner = $this->member();
        // ไฟล์ลับอื่นใน storage/app (เช่น credentials) ห้ามเปิดผ่าน route นี้ แม้พาธในแถวจะชี้ไป
        Storage::disk('local')->put('google-credentials.json', '{"secret":true}');
        $kyc = KycVerification::create([
            'user_id' => $owner->id,
            'id_card_image' => 'google-credentials.json',
            'selfie_image' => 'kyc-private/../google-credentials.json',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'id_card']))->assertNotFound();
        $this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'selfie']))->assertNotFound();
        $this->assertNull($kyc->idCardImageUrl());
    }

    public function test_ekyc_rows_are_not_served_by_document_routes(): void
    {
        $owner = $this->member();
        Storage::disk('local')->put('ekyc/'.$owner->id.'/s1/card-x.enc', 'ENCRYPTED');
        $kyc = KycVerification::create([
            'user_id' => $owner->id,
            'method' => KycVerification::METHOD_EKYC,
            'id_card_image' => 'ekyc/'.$owner->id.'/s1/card-x.enc',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->get(route('user.kyc.image', [$kyc, 'id_card']))->assertNotFound();
        $this->assertNull($kyc->idCardImageUrl());
        $this->assertFalse($kyc->hasIdCardImage());
    }

    public function test_mobile_upload_returns_signed_urls_that_open_without_token(): void
    {
        $user = $this->member();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/v1/kyc/upload', [
            'image' => UploadedFile::fake()->image('card.jpg', 900, 560),
            'type' => 'id_card',
        ])->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['kycId', 'type', 'imageUrl', 'hasIdCard', 'hasSelfie', 'idCardUrl', 'selfieUrl', 'canSubmit']])
            ->assertJsonPath('data.hasIdCard', true)
            ->assertJsonPath('data.selfieUrl', null);

        $kyc = KycVerification::findOrFail($res->json('data.kycId'));
        $firstPath = $kyc->id_card_image;
        $this->assertStringStartsWith('kyc-private/'.$user->id.'/', $firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $url = (string) $res->json('data.idCardUrl');
        $this->assertSame($url, $res->json('data.imageUrl'));
        $this->assertStringContainsString('/api/v1/kyc/image/'.$kyc->id.'/id_card', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringNotContainsString('/storage/', $url);

        // <img> ในแอปเปิดได้โดยไม่ต้องแนบ token
        $this->app['auth']->forgetGuards();
        $file = $this->get($url);
        $file->assertOk();
        $this->assertStringContainsString('no-store', (string) $file->headers->get('Cache-Control'));

        // แก้ชนิดในลิงก์ = ลายเซ็นไม่ตรง · หมดอายุ = เปิดไม่ได้
        $this->get(str_replace('/id_card?', '/selfie?', $url))->assertForbidden();
        $this->travel(KycDocumentStorage::SIGNED_URL_MINUTES + 1)->minutes();
        $this->get($url)->assertForbidden();
        $this->travelBack();

        // ส่งรูปบัตรใหม่ → รูปเดิมถูกลบ (ไม่ค้างเป็นสำเนาบัตรกำพร้า)
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/kyc/upload', [
            'image' => UploadedFile::fake()->image('card2.jpg', 900, 560),
            'type' => 'id_card',
        ])->assertOk();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($kyc->fresh()->id_card_image);
    }

    public function test_mobile_submit_stores_images_on_private_disk(): void
    {
        $user = $this->member();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/v1/kyc/submit', [
            'id_card_image' => UploadedFile::fake()->image('card.jpg', 900, 560),
            'selfie_image' => UploadedFile::fake()->image('selfie.jpg', 600, 800),
        ])->assertCreated();

        $kyc = KycVerification::findOrFail($res->json('data.kycId'));
        $this->assertStringStartsWith('kyc-private/'.$user->id.'/', $kyc->id_card_image);
        $this->assertStringStartsWith('kyc-private/'.$user->id.'/', $kyc->selfie_image);
        Storage::disk('local')->assertExists([$kyc->id_card_image, $kyc->selfie_image]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_line_kyc_images_are_saved_on_private_disk(): void
    {
        $user = $this->member();
        $service = app(LineKycService::class);
        $save = new \ReflectionMethod($service, 'saveImage');

        $result = $save->invoke($service, 'JPEG-BYTES', $user->id, 'id_card');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('kyc-private/'.$user->id.'/id_card_', $result['path']);
        $this->assertArrayNotHasKey('url', $result);
        Storage::disk('local')->assertExists($result['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deleting_request_removes_private_and_legacy_files(): void
    {
        $owner = $this->member();
        $private = $this->privateKyc($owner);
        $legacy = $this->legacyKyc($owner);

        $private->delete();
        $legacy->delete();

        Storage::disk('local')->assertMissing([$private->id_card_image, $private->selfie_image]);
        Storage::disk('public')->assertMissing([$legacy->id_card_image, $legacy->selfie_image]);
    }

    public function test_move_command_moves_legacy_files_and_updates_paths(): void
    {
        $owner = $this->member();
        $kyc = $this->legacyKyc($owner, 'kyc/id-cards/a.webp', 'kyc/'.$owner->id.'/selfie_abc.jpg');
        $missing = KycVerification::create([
            'user_id' => $owner->id,
            'id_card_image' => 'kyc/id-cards/gone.webp',
            'status' => 'rejected',
        ]);
        Storage::disk('local')->put('ekyc/'.$owner->id.'/s1/card.enc', 'ENC');
        $ekyc = KycVerification::create([
            'user_id' => $owner->id,
            'method' => KycVerification::METHOD_EKYC,
            'id_card_image' => 'ekyc/'.$owner->id.'/s1/card.enc',
            'status' => 'approved',
        ]);
        DB::table('kyc_verifications')->where('id', $kyc->id)->update(['updated_at' => '2026-01-02 03:04:05']);

        // dry-run ไม่แตะอะไร
        $this->artisan('kyc:move-images-private', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('kyc/id-cards/a.webp', $kyc->fresh()->id_card_image);
        Storage::disk('public')->assertExists('kyc/id-cards/a.webp');

        $this->artisan('kyc:move-images-private')->assertSuccessful();

        $moved = $kyc->fresh();
        $this->assertMatchesRegularExpression('#^kyc-private/'.$owner->id.'/id_card-[A-Za-z0-9]{40}\.webp$#', $moved->id_card_image);
        $this->assertMatchesRegularExpression('#^kyc-private/'.$owner->id.'/selfie-[A-Za-z0-9]{40}\.jpg$#', $moved->selfie_image);
        $this->assertSame('LEGACY-CARD', Storage::disk('local')->get($moved->id_card_image));
        $this->assertSame('LEGACY-SELFIE', Storage::disk('local')->get($moved->selfie_image));
        Storage::disk('public')->assertMissing(['kyc/id-cards/a.webp', 'kyc/'.$owner->id.'/selfie_abc.jpg']);
        // งานเก็บไฟล์ ไม่ใช่การแก้คำขอ — updated_at คงเดิม
        $this->assertSame('2026-01-02 03:04:05', $moved->updated_at->format('Y-m-d H:i:s'));

        // ไฟล์หายไปก่อนแล้ว = ไม่แตะแถว · แถว eKYC ไม่แตะ
        $this->assertSame('kyc/id-cards/gone.webp', $missing->fresh()->id_card_image);
        $this->assertSame('ekyc/'.$owner->id.'/s1/card.enc', $ekyc->fresh()->id_card_image);

        // เจ้าของยังเปิดดูได้หลังย้าย
        $this->assertPrivateImageResponse($this->actingAs($owner)->get(route('user.kyc.image', [$moved, 'id_card'])), 'LEGACY-CARD');

        // รันซ้ำ = ไม่เปลี่ยนอะไร (idempotent)
        $this->artisan('kyc:move-images-private')->assertSuccessful();
        $this->assertSame($moved->id_card_image, $kyc->fresh()->id_card_image);
        $this->assertSame($moved->selfie_image, $kyc->fresh()->selfie_image);
        $this->assertCount(2, Storage::disk('local')->allFiles('kyc-private/'.$owner->id));
    }

    public function test_move_command_keeps_shared_legacy_file_until_every_row_moved(): void
    {
        $owner = $this->member();
        Storage::disk('public')->put('kyc/'.$owner->id.'/id_card_shared.jpg', 'SHARED');
        $first = KycVerification::create(['user_id' => $owner->id, 'id_card_image' => 'kyc/'.$owner->id.'/id_card_shared.jpg', 'status' => 'rejected']);
        $second = KycVerification::create(['user_id' => $owner->id, 'id_card_image' => 'kyc/'.$owner->id.'/id_card_shared.jpg', 'status' => 'pending']);

        $this->artisan('kyc:move-images-private', ['--chunk' => 1])->assertSuccessful();

        foreach ([$first, $second] as $row) {
            $path = $row->fresh()->id_card_image;
            $this->assertStringStartsWith('kyc-private/'.$owner->id.'/', $path);
            $this->assertSame('SHARED', Storage::disk('local')->get($path));
        }
        Storage::disk('public')->assertMissing('kyc/'.$owner->id.'/id_card_shared.jpg');
    }

    public function test_move_command_moves_orphans_only_with_flag(): void
    {
        $owner = $this->member();
        $kyc = $this->legacyKyc($owner);
        Storage::disk('public')->put('kyc/'.$owner->id.'/selfie_replaced.jpg', 'OLD-SELFIE');
        Storage::disk('public')->put('kyc-temp/ocr-left-behind.png', 'OCR-TEMP');
        Storage::disk('public')->put('branding/logo.png', 'NOT-KYC');

        $this->artisan('kyc:move-images-private')->assertSuccessful();
        Storage::disk('public')->assertExists(['kyc/'.$owner->id.'/selfie_replaced.jpg', 'kyc-temp/ocr-left-behind.png']);

        $this->artisan('kyc:move-images-private', ['--orphans' => true])->assertSuccessful();

        // ไม่เหลือไฟล์ KYC บน public disk เลย · ไฟล์ที่ไม่ใช่ KYC ไม่ถูกแตะ
        $this->assertSame([], Storage::disk('public')->allFiles('kyc'));
        $this->assertSame([], Storage::disk('public')->allFiles('kyc-temp'));
        Storage::disk('public')->assertExists('branding/logo.png');

        $userOrphans = Storage::disk('local')->files('kyc-private/'.$owner->id);
        $this->assertCount(3, $userOrphans, 'รูปบัตร + คู่บัตรที่ย้ายแล้ว + ไฟล์กำพร้าของผู้ใช้');
        $this->assertCount(1, array_filter($userOrphans, fn ($f) => str_contains($f, '/orphan-')));
        $unowned = Storage::disk('local')->files('kyc-private/orphans');
        $this->assertCount(1, $unowned);
        $this->assertSame('OCR-TEMP', Storage::disk('local')->get($unowned[0]));

        // แถวยังเปิดรูปได้
        $this->assertStringStartsWith('kyc-private/', $kyc->fresh()->id_card_image);
    }
}
