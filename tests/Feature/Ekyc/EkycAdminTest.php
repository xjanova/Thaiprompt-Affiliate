<?php

namespace Tests\Feature\Ekyc;

use App\Models\KycAccessLog;
use App\Models\KycVerification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 หลังบ้าน eKYC — หน้าตรวจ · รูปถอดรหัสเฉพาะแอดมิน + บันทึกการเปิดดู (PDPA) · อนุมัติ/ปฏิเสธ/ขอถ่ายใหม่
 */
#[Group('ekyc')]
class EkycAdminTest extends TestCase
{
    use BuildsEkycFixtures;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
        $this->configureEkyc();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * ผู้ใช้ที่ AI ส่งเคสให้ตรวจ (ใบหน้าก้ำกึ่ง)
     */
    private function reviewCase(): KycVerification
    {
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.41, 'score' => 0.63]]));
        $user = $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');

        return KycVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_image_route_requires_admin_and_logs_every_view(): void
    {
        $kyc = $this->reviewCase();
        $owner = $kyc->user;

        // ไม่ล็อกอิน (ล้าง guard ที่เทสต์ตั้งไว้) → ไปหน้า login
        $this->app['auth']->forgetGuards();
        $this->get(route('admin.kyc.image', [$kyc, 'card']))->assertRedirect();

        // เจ้าของข้อมูลเอง / ผู้ใช้ทั่วไป → ไม่ได้
        $this->actingAs($owner)->get(route('admin.kyc.image', [$kyc, 'card']))->assertRedirect();
        $this->actingAs(User::factory()->create())->getJson(route('admin.kyc.image', [$kyc, 'best_frame']))->assertForbidden();
        $this->assertSame(0, KycAccessLog::count());

        foreach (['card', 'card_face', 'best_frame'] as $kind) {
            $res = $this->actingAs($this->admin)->get(route('admin.kyc.image', [$kyc, $kind]));
            $res->assertOk()->assertHeader('Content-Type', 'image/jpeg');
            $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
            $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($res->getContent())[2], "{$kind} ต้องถอดรหัสเป็น JPEG");
        }

        $this->assertSame(3, KycAccessLog::where('kyc_verification_id', $kyc->id)->where('viewer_id', $this->admin->id)->count());
        $this->assertSame(['best_frame', 'card', 'card_face'], KycAccessLog::orderBy('kind')->pluck('kind')->all());
        $this->assertSame($owner->id, KycAccessLog::first()->subject_user_id);

        // ชนิดรูปแปลก → 404
        $this->actingAs($this->admin)->get('/admin/kyc/'.$kyc->id.'/image/selfie')->assertNotFound();
    }

    public function test_admin_pages_render_with_ai_scores_and_filter(): void
    {
        $kyc = $this->reviewCase();

        $this->actingAs($this->admin)->get(route('admin.kyc.show', $kyc))
            ->assertOk()
            ->assertSee('ตรวจยืนยันตัวตน')
            ->assertSee('ใบหน้าตรงกับบัตร')
            ->assertSee('ใบหน้าคล้ายรูปบนบัตรระดับกลาง')
            ->assertSee('นาย ณัฐ ใจงาม')
            ->assertSee('1 2345 ••••• 12 1')
            ->assertSee('12 ม.ค. 2538')
            ->assertSee(route('admin.kyc.image', [$kyc, 'card_face']), false)
            ->assertSee(route('admin.kyc.request-retake', $kyc), false)
            ->assertDontSee(self::VALID_ID);

        $this->actingAs($this->admin)->get(route('admin.kyc.index', ['ai' => 'review']))
            ->assertOk()
            ->assertSee('AI ส่งตรวจ')
            ->assertSee($kyc->user->email);

        // รอบที่ผู้ใช้ทำค้าง (draft) ไม่โผล่ในรายการ
        $this->fakeAi();
        $drafter = $this->ekycUser(['email' => 'drafter-ekyc@example.test']);
        $this->openSession();
        $this->actingAs($this->admin)->get(route('admin.kyc.index'))->assertOk()->assertDontSee('drafter-ekyc@example.test');
    }

    public function test_admin_approve_marks_user_verified_and_notifies(): void
    {
        $kyc = $this->reviewCase();

        $this->actingAs($this->admin)->post(route('admin.kyc.approve', $kyc))->assertRedirect();

        $kyc->refresh();
        $this->assertSame('approved', $kyc->status);
        $this->assertSame($this->admin->id, $kyc->reviewed_by);
        $this->assertSame('approved', $kyc->user->fresh()->kyc_status);
        $this->assertNotNull($kyc->user->fresh()->kyc_verified_at);

        $note = Notification::where('user_id', $kyc->user_id)->where('type', 'kyc_result')->latest('id')->first();
        $this->assertSame('approved', $note?->data['decision'] ?? null);
        // ไม่ส่งแจ้งเตือนแบบเดิม (type kyc) ซ้ำ
        $this->assertFalse(Notification::where('user_id', $kyc->user_id)->where('type', 'kyc')->exists());

        // ตัดสินแล้ว กดซ้ำไม่ได้
        $this->actingAs($this->admin)->post(route('admin.kyc.reject', $kyc), ['rejection_reason' => 'x'])->assertSessionHas('error');
        $this->assertSame('approved', $kyc->fresh()->status);
    }

    public function test_admin_reject_requires_reason_and_sets_rejected(): void
    {
        $kyc = $this->reviewCase();

        $this->actingAs($this->admin)->post(route('admin.kyc.reject', $kyc), [])->assertSessionHasErrors('rejection_reason');
        $this->actingAs($this->admin)->post(route('admin.kyc.reject', $kyc), ['rejection_reason' => 'บัตรไม่ใช่ของเจ้าของบัญชี'])->assertRedirect();

        $this->assertSame('rejected', $kyc->fresh()->status);
        $this->assertSame('rejected', $kyc->user->fresh()->kyc_status);

        Sanctum::actingAs($kyc->user->fresh());
        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.kyc_status', 'rejected')
            ->assertJsonPath('data.last_decision', 'rejected')
            ->assertJsonPath('data.message', 'บัตรไม่ใช่ของเจ้าของบัญชี');
    }

    public function test_admin_request_retake_lets_user_start_again(): void
    {
        $kyc = $this->reviewCase();

        $this->actingAs($this->admin)->post(route('admin.kyc.request-retake', $kyc), ['retake_note' => 'ถ่ายในที่สว่างขึ้น'])->assertRedirect();

        $kyc->refresh();
        $this->assertSame(KycVerification::STATUS_RETAKE, $kyc->status);
        $this->assertSame('not_submitted', $kyc->user->fresh()->kyc_status);
        $note = Notification::where('user_id', $kyc->user_id)->where('type', 'kyc_result')->latest('id')->first();
        $this->assertSame('retake', $note?->data['decision'] ?? null);

        Sanctum::actingAs($kyc->user->fresh());
        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.can_start', true)
            ->assertJsonPath('data.last_decision', 'retake')
            ->assertJsonPath('data.message', 'ถ่ายในที่สว่างขึ้น');
        $this->startSession()->assertCreated();
    }

    public function test_manual_kyc_submission_now_notifies_admins(): void
    {
        $user = User::factory()->create();

        // เดิม notifyAdminNewKyc query คอลัมน์ users.is_admin ที่ไม่มี → พังเงียบ แอดมินไม่เคยได้แจ้งเตือน
        KycVerification::create([
            'user_id' => $user->id,
            'id_card_image' => 'kyc/'.$user->id.'/id.jpg',
            'selfie_image' => 'kyc/'.$user->id.'/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->assertTrue(Notification::where('user_id', $this->admin->id)->where('type', 'kyc')->exists());
    }
}
