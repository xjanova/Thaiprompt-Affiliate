<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Concerns\BuildsApprovalsWorld;
use Tests\TestCase;

/**
 * 🛵 แอปแอดมิน: ใบสมัครไรเดอร์ + เอกสาร — ทุกการกระทำวิ่งผ่าน Admin\RiderController ของเว็บตัวเดิม
 */
#[Group('admin-app')]
class RiderApplicationsApprovalsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use BuildsApprovalsWorld;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        $this->admin = $this->makeAdmin();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_list_masks_pii_and_detail_links_documents_through_authenticated_route(): void
    {
        $rider = $this->makeRiderApplicant();
        $this->makeRiderApplicant(['status' => 'approved']);

        $this->actAs($this->admin);
        $res = $this->getJson('/api/admin/approvals/riders')->assertOk();
        $res->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $rider->id)
            ->assertJsonPath('data.data.0.id_card_masked', '123xxxxxxx121')
            ->assertJsonPath('data.data.0.phone_masked', '089•••5432')
            ->assertJsonPath('data.data.0.documents_complete', true);
        $this->assertStringNotContainsString('1234567890121', $res->getContent());
        $this->assertStringNotContainsString('0898765432', $res->getContent());

        $detail = $this->getJson('/api/admin/approvals/riders/'.$rider->id)->assertOk();
        $detail->assertJsonPath('data.actions.can_approve', true)
            ->assertJsonPath('data.vehicle.plate', '1กข 1234');
        $doc = collect($detail->json('data.documents'))->firstWhere('type', 'id_card');
        $this->assertSame(url('/api/admin/approvals/riders/'.$rider->id.'/document/id_card'), $doc['url']);
        $this->assertTrue($doc['requires_auth']);
        $this->assertStringNotContainsString('riders/'.$rider->id.'/id_card', str_replace('\/', '/', $detail->getContent()), 'ห้ามหลุด path ไฟล์ส่วนตัว');

        // สตรีมเอกสาร: แนบ token แอดมิน · no-store
        $file = $this->get('/api/admin/approvals/riders/'.$rider->id.'/document/id_card');
        $file->assertOk();
        $this->assertStringContainsString('no-store', (string) $file->headers->get('Cache-Control'));
        $this->assertSame('fake-image-id_card', $file->streamedContent());

        // ไม่มีไฟล์ → 404 envelope
        $rider->forceFill(['profile_image' => null])->save();
        $this->get('/api/admin/approvals/riders/'.$rider->id.'/document/profile')
            ->assertNotFound()->assertJsonPath('error_code', 'DOCUMENT_NOT_FOUND');

        $this->app['auth']->forgetGuards();
        $this->get('/api/admin/approvals/riders/'.$rider->id.'/document/id_card', ['Accept' => 'application/json'])->assertStatus(401);
    }

    public function test_approve_requires_complete_documents_then_approves(): void
    {
        $rider = $this->makeRiderApplicant([], withDocuments: false);

        $this->actAs($this->admin);
        $this->postJson('/api/admin/approvals/riders/'.$rider->id.'/approve')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'DOCUMENTS_INCOMPLETE');
        $this->assertSame('pending', $rider->fresh()->status);

        $complete = $this->makeRiderApplicant();
        $this->postJson('/api/admin/approvals/riders/'.$complete->id.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'อนุมัติไรเดอร์เรียบร้อย');

        $fresh = $complete->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame((int) $this->admin->id, (int) $fresh->approved_by);

        // กดซ้ำ = สำเร็จ (เว็บ: "อนุมัติอยู่แล้ว")
        $this->postJson('/api/admin/approvals/riders/'.$complete->id.'/approve')->assertOk();
    }

    public function test_reject_validates_reason_and_repeat_is_idempotent(): void
    {
        $rider = $this->makeRiderApplicant();

        $this->actAs($this->admin);
        $this->postJson('/api/admin/approvals/riders/'.$rider->id.'/reject', ['reason' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาระบุเหตุผลให้ชัดเจน');
        $this->assertSame('pending', $rider->fresh()->status);

        $this->postJson('/api/admin/approvals/riders/'.$rider->id.'/reject', ['reason' => 'รูปบัตรไม่ชัด'])
            ->assertOk();
        $this->assertSame('rejected', $rider->fresh()->status);
        $this->assertSame('รูปบัตรไม่ชัด', $rider->fresh()->rejection_reason);

        $this->postJson('/api/admin/approvals/riders/'.$rider->id.'/reject', ['reason' => 'รูปบัตรไม่ชัด'])
            ->assertOk()->assertJsonPath('data.already_decided', true);

        // ไรเดอร์ที่อนุมัติแล้วปฏิเสธไม่ได้ (ต้องใช้ระงับ)
        $approved = $this->makeRiderApplicant(['status' => 'approved']);
        $this->postJson('/api/admin/approvals/riders/'.$approved->id.'/reject', ['reason' => 'ไม่ผ่าน'])
            ->assertStatus(409)->assertJsonPath('error_code', 'INVALID_STATUS');
        $this->assertSame('approved', $approved->fresh()->status);
    }

    public function test_documents_changed_queue_and_mark_reviewed(): void
    {
        $rider = $this->makeRiderApplicant(['status' => 'approved', 'documents_changed_at' => now()->subMinutes(15)]);

        $this->actAs($this->admin);
        $this->getJson('/api/admin/approvals/riders?status=documents_changed')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $rider->id);

        $this->postJson('/api/admin/approvals/riders/'.$rider->id.'/documents-reviewed')
            ->assertOk()->assertJsonPath('message', 'บันทึกว่าตรวจเอกสารแล้ว');
        $this->assertNull($rider->fresh()->documents_changed_at);
        $this->getJson('/api/admin/approvals/riders?status=documents_changed')->assertJsonPath('data.total', 0);
    }
}
