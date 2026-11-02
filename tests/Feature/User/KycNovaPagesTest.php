<?php

namespace Tests\Feature\User;

use App\Models\KycVerification;
use App\Models\User;
use App\Services\Kyc\KycDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * หน้ายืนยันตัวตนของสมาชิกธีมโนวาเต็ม (/user/kyc, /user/kyc/create, /user/kyc/{id})
 */
class KycNovaPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Storage::fake('local');
        Storage::fake('public');
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'not_submitted'], $attributes));
    }

    /**
     * คำขอแบบเดิมที่มีรูปจริงบน private disk
     */
    private function kyc(User $owner, array $attributes = []): KycVerification
    {
        $dir = app(KycDocumentStorage::class)->directoryFor($owner->id);
        Storage::disk('local')->put($dir.'/card.webp', 'CARD');
        Storage::disk('local')->put($dir.'/selfie.webp', 'SELFIE');

        return KycVerification::create(array_merge([
            'user_id' => $owner->id,
            'id_card_image' => $dir.'/card.webp',
            'selfie_image' => $dir.'/selfie.webp',
            'status' => 'pending',
            'submitted_at' => now(),
        ], $attributes));
    }

    /**
     * รูป JPEG ที่ผ่านด่านตรวจคุณภาพของ OCR (≥10KB และความละเอียดพอ) — รูป fake เปล่าเล็กเกินจนไม่ถึงขั้นเรียก OCR
     */
    private function noisyJpeg(string $name): UploadedFile
    {
        $image = imagecreatetruecolor(1000, 640);
        for ($i = 0; $i < 6000; $i++) {
            $color = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));
            imagefilledrectangle($image, random_int(0, 999), random_int(0, 639), random_int(0, 999), random_int(0, 639), $color);
        }
        $path = tempnam(sys_get_temp_dir(), 'kyc').'.jpg';
        imagejpeg($image, $path, 95);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringContainsString('nova-member.css', $html);
    }

    public function test_member_without_kyc_is_invited_to_start(): void
    {
        $html = $this->actingAs($this->member())->get(route('user.kyc.index'))
            ->assertOk()
            ->assertViewIs('user.kyc.index')
            ->assertSee('คุณยังไม่ได้ยืนยันตัวตน')
            ->assertSee(route('user.kyc.create'), false)
            ->getContent();

        $this->assertFullNova($html);
    }

    public function test_pending_request_shows_waiting_status_without_a_resubmit_button(): void
    {
        $user = $this->member();
        $kyc = $this->kyc($user);

        $this->actingAs($user)->get(route('user.kyc.index'))
            ->assertOk()
            ->assertSee('เอกสารอยู่ระหว่างการตรวจสอบ')
            ->assertSee(route('user.kyc.show', $kyc), false)
            ->assertDontSee('ส่งเอกสารใหม่');

        // KycController::index sync สถานะให้ users.kyc_status
        $this->assertSame('pending', $user->fresh()->kyc_status);
    }

    public function test_approved_member_sees_verified_state(): void
    {
        $user = $this->member(['kyc_status' => 'approved', 'kyc_verified_at' => now()]);
        $this->kyc($user, ['status' => 'approved', 'reviewed_at' => now()]);

        $this->actingAs($user)->get(route('user.kyc.index'))
            ->assertOk()
            ->assertSee('บัญชีได้รับการยืนยันแล้ว')
            ->assertDontSee(route('user.kyc.create'), false);
    }

    public function test_rejection_reason_is_shown_escaped_with_a_resubmit_link(): void
    {
        $user = $this->member();
        $kyc = $this->kyc($user, ['status' => 'rejected', 'rejection_reason' => 'รูปเบลอ <script>alert(1)</script>']);

        foreach ([route('user.kyc.index'), route('user.kyc.show', $kyc)] as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('รูปเบลอ')
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertSee(route('user.kyc.create'), false);
        }
    }

    public function test_expired_member_can_submit_again(): void
    {
        $this->actingAs($this->member(['kyc_status' => 'expired']))->get(route('user.kyc.index'))
            ->assertOk()
            ->assertSee('การยืนยันตัวตนหมดอายุ')
            ->assertSee(route('user.kyc.create'), false);
    }

    public function test_create_page_is_a_nova_upload_form(): void
    {
        $html = $this->actingAs($this->member())->get(route('user.kyc.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="id_card_image"', false)
            ->assertSee('name="selfie_image"', false)
            ->assertSee(route('user.kyc.store'), false)
            ->getContent();

        $this->assertFullNova($html);
    }

    public function test_failed_upload_tells_the_member_to_pick_both_photos_again(): void
    {
        $user = $this->member();

        $this->actingAs($user)->from(route('user.kyc.create'))
            ->post(route('user.kyc.store'), [])
            ->assertRedirect(route('user.kyc.create'))
            ->assertSessionHasErrors(['id_card_image', 'selfie_image']);

        $this->actingAs($user)->get(route('user.kyc.create'))
            ->assertOk()
            ->assertSee('กรุณาอัปโหลดรูปภาพบัตรประชาชน')
            ->assertSee('กรุณาเลือกรูปทั้ง 2 รูปใหม่อีกครั้ง');
    }

    public function test_member_never_sees_the_admin_hint_when_ocr_is_not_configured(): void
    {
        // เทสต์ไม่มีคีย์ Google Vision → OCR ตอบ OCR_NOT_CONFIGURED พร้อมคำแนะนำให้ "ตั้งค่า API Key" ซึ่งเป็นงานของแอดมิน
        $user = $this->member();

        $this->actingAs($user)->post(route('user.kyc.store'), [
            'id_card_image' => $this->noisyJpeg('card.jpg'),
            'selfie_image' => $this->noisyJpeg('selfie.jpg'),
        ])->assertRedirect(route('user.kyc.index'))
            ->assertSessionHas('success')
            ->assertSessionMissing('ocr_error');

        $this->actingAs($user)->get(route('user.kyc.index'))
            ->assertOk()
            ->assertSee('ส่งคำขอยืนยันตัวตนเรียบร้อยแล้ว')
            ->assertDontSee('API Key');
    }

    public function test_pending_member_is_sent_back_from_the_form(): void
    {
        $user = $this->member();
        $this->kyc($user);

        $this->actingAs($user)->get(route('user.kyc.create'))->assertRedirect(route('user.kyc.index'));
        $this->actingAs($user)->get(route('user.kyc.index'))->assertSee('คุณมีคำขอยืนยันตัวตนที่กำลังรออนุมัติอยู่แล้ว');
    }

    public function test_detail_page_shows_private_image_routes_in_nova(): void
    {
        $user = $this->member();
        $kyc = $this->kyc($user);

        $html = $this->actingAs($user)->get(route('user.kyc.show', $kyc))
            ->assertOk()
            ->assertSee(route('user.kyc.image', [$kyc, 'id_card']), false)
            ->assertSee(route('user.kyc.image', [$kyc, 'selfie']), false)
            ->assertDontSee('storage/kyc', false)
            ->assertDontSee($kyc->id_card_image, false)
            ->getContent();

        $this->assertFullNova($html);
    }

    public function test_missing_file_asks_for_a_new_submission(): void
    {
        $user = $this->member();
        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'id_card_image' => 'kyc-private/'.$user->id.'/gone.webp',
            'selfie_image' => 'kyc-private/'.$user->id.'/gone2.webp',
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)->get(route('user.kyc.show', $kyc))
            ->assertOk()
            ->assertSee('ไม่พบไฟล์รูปภาพ')
            ->assertDontSee('gone.webp', false);
    }

    public function test_ai_ekyc_request_explains_why_there_are_no_photos(): void
    {
        $user = $this->member();
        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'method' => KycVerification::METHOD_EKYC,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)->get(route('user.kyc.show', $kyc))
            ->assertOk()
            ->assertSee('ยืนยันผ่านระบบ AI eKYC')
            ->assertDontSee('ไม่พบไฟล์รูปภาพ');
    }

    public function test_other_members_request_is_forbidden(): void
    {
        $kyc = $this->kyc($this->member());

        $this->actingAs($this->member())->get(route('user.kyc.show', $kyc))->assertForbidden();
    }
}
