<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\KycAccessLog;
use App\Models\KycVerification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 แอปแอดมิน: คิวตรวจ AI eKYC — รายการ/รายละเอียดไม่หลุดเลขบัตรเต็ม · รูปต้องแนบ token + บันทึก PDPA ทุกครั้ง
 * · อนุมัติ/ปฏิเสธ/ขอถ่ายใหม่ผ่าน EkycService ตัวเดียวกับเว็บ · กดซ้ำปลอดภัย
 */
#[Group('admin-app')]
#[Group('ekyc')]
class EkycApprovalsTest extends TestCase
{
    use BuildsAdminAppWorld;
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
        $this->admin = $this->makeAdmin();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    /** ผู้ใช้ที่ AI ส่งเคสให้ตรวจ (ใบหน้าก้ำกึ่ง) — ผ่าน flow จริงของแอป */
    private function reviewCase(): KycVerification
    {
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.41, 'score' => 0.63]]));
        $user = $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');

        return KycVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_list_and_detail_show_ai_scores_and_never_the_full_id_number(): void
    {
        $kyc = $this->reviewCase();

        $this->actAs($this->admin);
        $list = $this->getJson('/api/admin/approvals/ekyc')->assertOk();
        $list->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $kyc->id)
            ->assertJsonPath('data.data.0.status', 'pending')
            ->assertJsonPath('data.data.0.document_type', 'thai_national_id')
            ->assertJsonPath('data.data.0.user.id', $kyc->user_id)
            ->assertJsonPath('data.data.0.flags.duplicate_id', false);
        $this->assertContains('BORDERLINE_MATCH', $list->json('data.data.0.reasons'));
        $this->assertContains('ใบหน้าคล้ายรูปบนบัตรระดับกลาง', $list->json('data.data.0.reason_texts'));
        $this->assertEqualsWithDelta(0.41, $list->json('data.data.0.ai.face_match'), 0.0001);
        $this->assertNotNull($list->json('data.data.0.waiting_minutes'));

        $detail = $this->getJson('/api/admin/approvals/ekyc/'.$kyc->id)->assertOk();
        $detail->assertJsonPath('data.card.name_th', 'นาย ณัฐ ใจงาม')
            ->assertJsonPath('data.card.id_masked', '1 2345 ••••• 12 1')
            ->assertJsonPath('data.image_requires_auth', true)
            ->assertJsonPath('data.actions.can_approve', true)
            ->assertJsonPath('data.duplicate_now', false);
        $this->assertSame(
            url('/api/admin/approvals/ekyc/'.$kyc->id.'/image/card_face'),
            collect($detail->json('data.images'))->firstWhere('kind', 'card_face')['url']
        );

        foreach ([$list, $detail] as $res) {
            $this->assertStringNotContainsString(self::VALID_ID, $res->getContent());
            $this->assertStringNotContainsString('id_number_encrypted', $res->getContent());
            $this->assertStringNotContainsString('best_frame_path', $res->getContent());
        }

        // อนุมัติแล้วหายจากคิวรอตรวจ แต่ดูได้ในกองอื่น
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/approve')->assertOk();
        $this->getJson('/api/admin/approvals/ekyc')->assertJsonPath('data.total', 0);
        $this->getJson('/api/admin/approvals/ekyc?status=approved')->assertJsonPath('data.total', 1);
    }

    public function test_image_needs_bearer_admin_streams_no_store_and_logs_every_view(): void
    {
        $kyc = $this->reviewCase();
        $owner = $kyc->user;

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/admin/approvals/ekyc/'.$kyc->id.'/image/card')->assertStatus(401);

        // เจ้าของข้อมูลเอง (token ปกติ) → ไม่ใช่แอดมิน
        $this->actAs($owner);
        $this->get('/api/admin/approvals/ekyc/'.$kyc->id.'/image/card')->assertStatus(403);
        $this->assertSame(0, KycAccessLog::count());

        $this->actAs($this->admin);
        foreach (['card', 'card_face', 'best_frame'] as $kind) {
            $res = $this->get('/api/admin/approvals/ekyc/'.$kyc->id.'/image/'.$kind, ['User-Agent' => 'ThaipromptAdmin/1.0']);
            $res->assertOk()->assertHeader('Content-Type', 'image/jpeg');
            $cache = (string) $res->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cache);
            $this->assertStringContainsString('private', $cache);
            $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($res->getContent())[2], "{$kind} ต้องถอดรหัสเป็น JPEG");
        }

        // PDPA: 1 แถวต่อการเปิดดู 1 รูป — ข้อมูลเดียวกับที่หน้าเว็บบันทึก
        $logs = KycAccessLog::where('kyc_verification_id', $kyc->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertSame(['card', 'card_face', 'best_frame'], $logs->pluck('kind')->all());
        $this->assertSame([$this->admin->id], $logs->pluck('viewer_id')->unique()->values()->all());
        $this->assertSame($owner->id, (int) $logs->first()->subject_user_id);
        $this->assertSame('ThaipromptAdmin/1.0', $logs->first()->user_agent);

        // ชนิดรูปแปลก → 404 · การเปิดดูโผล่ในรายละเอียด
        $this->get('/api/admin/approvals/ekyc/'.$kyc->id.'/image/selfie')->assertNotFound();
        $this->assertSame(3, KycAccessLog::count());
        $this->getJson('/api/admin/approvals/ekyc/'.$kyc->id)->assertJsonCount(3, 'data.recent_views');
    }

    public function test_approve_marks_user_verified_and_repeat_is_idempotent(): void
    {
        $kyc = $this->reviewCase();

        $this->actAs($this->admin);
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.already_decided', false)
            ->assertJsonPath('message', 'อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว');

        $kyc->refresh();
        $this->assertSame('approved', $kyc->status);
        $this->assertSame($this->admin->id, (int) $kyc->reviewed_by);
        $this->assertSame('approved', $kyc->user->fresh()->kyc_status);
        $this->assertSame('approved', Notification::where('user_id', $kyc->user_id)->where('type', 'kyc_result')->latest('id')->first()?->data['decision'] ?? null);

        // กดซ้ำ (เน็ตหลุดแล้วลองใหม่) → สำเร็จเฉย ๆ ไม่แจ้งซ้ำ
        $before = Notification::where('user_id', $kyc->user_id)->where('type', 'kyc_result')->count();
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/approve')
            ->assertOk()->assertJsonPath('data.already_decided', true);
        $this->assertSame($before, Notification::where('user_id', $kyc->user_id)->where('type', 'kyc_result')->count());

        // ตัดสินผลอื่นหลังอนุมัติแล้ว → 409
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/reject', ['reason' => 'ไม่ใช่เจ้าของบัตร'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'EKYC_ALREADY_DECIDED');
        $this->assertSame('approved', $kyc->fresh()->status);
    }

    public function test_reject_requires_reason_and_retake_lets_user_start_again(): void
    {
        $kyc = $this->reviewCase();

        $this->actAs($this->admin);
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/reject', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาระบุเหตุผลในการปฏิเสธ')
            ->assertJsonStructure(['errors' => ['reason']]);
        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/request-retake', ['reason' => str_repeat('ก', 501)])
            ->assertStatus(422);
        $this->assertSame('pending', $kyc->fresh()->status);

        $this->postJson('/api/admin/approvals/ekyc/'.$kyc->id.'/request-retake', ['reason' => 'ถ่ายในที่สว่างขึ้น'])
            ->assertOk()
            ->assertJsonPath('data.status', KycVerification::STATUS_RETAKE);
        $this->assertSame('not_submitted', $kyc->user->fresh()->kyc_status);

        // อีกเคส: ปฏิเสธ
        $other = $this->reviewCase();
        $this->actAs($this->admin);
        $this->postJson('/api/admin/approvals/ekyc/'.$other->id.'/reject', ['reason' => 'บัตรไม่ใช่ของเจ้าของบัญชี'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
        $this->assertSame('rejected', $other->fresh()->status);
        $this->assertSame('บัตรไม่ใช่ของเจ้าของบัญชี', $other->fresh()->rejection_reason);
        $this->assertSame('rejected', $other->user->fresh()->kyc_status);
    }

    public function test_manual_kyc_rows_and_drafts_are_not_exposed(): void
    {
        $user = User::factory()->create();
        $manual = KycVerification::create([
            'user_id' => $user->id,
            'id_card_image' => 'kyc/'.$user->id.'/id.jpg',
            'selfie_image' => 'kyc/'.$user->id.'/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actAs($this->admin);
        $this->getJson('/api/admin/approvals/ekyc')->assertJsonPath('data.total', 0);
        $this->getJson('/api/admin/approvals/ekyc/'.$manual->id)->assertNotFound()->assertJsonPath('error_code', 'NOT_FOUND');
        $this->postJson('/api/admin/approvals/ekyc/'.$manual->id.'/approve')->assertNotFound();
        $this->assertSame('pending', $manual->fresh()->status);
    }
}
