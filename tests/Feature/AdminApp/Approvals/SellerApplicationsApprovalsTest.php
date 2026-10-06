<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Concerns\BuildsApprovalsWorld;
use Tests\TestCase;

/**
 * 🏪 แอปแอดมิน: คำขอเปิดร้านค้า — อนุมัติ/ปฏิเสธผ่าน SellerApplicationService ตัวเดียวกับหลังบ้านเว็บ
 */
#[Group('admin-app')]
class SellerApplicationsApprovalsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use BuildsApprovalsWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_list_and_detail_mask_tax_id_and_phone(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'name' => 'ป้าแดง']);
        $store = $this->makePendingStore($owner);
        $this->makePendingStore(null, ['status' => 'active']);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/approvals/seller-applications')->assertOk();
        $res->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $store->id)
            ->assertJsonPath('data.data.0.owner.name', 'ป้าแดง')
            ->assertJsonPath('data.data.0.tax_id_masked', '010•••••••456')
            ->assertJsonPath('data.data.0.phone_masked', '081•••5678')
            ->assertJsonPath('data.data.0.business_type_label', 'นิติบุคคล');
        $this->assertStringNotContainsString('0105555123456', $res->getContent());
        $this->assertStringNotContainsString($owner->email, $res->getContent());

        $this->getJson('/api/admin/approvals/seller-applications/'.$store->id)
            ->assertOk()
            ->assertJsonPath('data.owner_detail.role', 'user')
            ->assertJsonPath('data.actions.can_approve', true)
            ->assertJsonPath('data.address.state', 'กรุงเทพมหานคร');

        $this->getJson('/api/admin/approvals/seller-applications?status=all')->assertJsonPath('data.total', 2);
        $this->getJson('/api/admin/approvals/seller-applications/999999')->assertNotFound();
    }

    public function test_approve_converts_owner_to_seller_notifies_and_repeat_is_idempotent(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $store = $this->makePendingStore($owner);

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.already_decided', false);

        $this->assertSame('active', $store->fresh()->status);
        $this->assertTrue((bool) $store->fresh()->is_active);
        $this->assertSame('seller', $owner->fresh()->role);
        $this->assertTrue(Notification::where('user_id', $owner->id)->where('type', 'seller_application')->exists());

        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/approve')
            ->assertOk()->assertJsonPath('data.already_decided', true);

        // ร้านที่อนุมัติแล้วปฏิเสธไม่ได้
        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/reject', ['reason' => 'ข้อมูลไม่ครบ'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ALREADY_PROCESSED');
        $this->assertSame('active', $store->fresh()->status);
    }

    public function test_approve_refuses_suspended_owner_and_unconvertible_roles(): void
    {
        $suspended = User::factory()->create(['role' => 'user']);
        $suspended->forceFill(['blocked_at' => now()])->save();
        $store = $this->makePendingStore($suspended);

        $provider = User::factory()->create(['role' => 'provider']);
        $store2 = $this->makePendingStore($provider);

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/approve')
            ->assertStatus(409)->assertJsonPath('error_code', 'OWNER_SUSPENDED');
        $this->postJson('/api/admin/approvals/seller-applications/'.$store2->id.'/approve')
            ->assertStatus(409)->assertJsonPath('error_code', 'ROLE_NOT_CONVERTIBLE');

        $this->assertSame('pending', $store->fresh()->status);
        $this->assertSame('provider', $provider->fresh()->role);
    }

    public function test_reject_requires_reason_stores_it_and_repeat_is_idempotent(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $store = $this->makePendingStore($owner);

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/reject', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'กรุณาระบุเหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็นข้อความนี้)');
        $this->assertSame('pending', $store->fresh()->status);

        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/reject', ['reason' => '  ที่อยู่ไม่ครบ  '])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
        $this->assertSame('ที่อยู่ไม่ครบ', $store->fresh()->suspension_reason);
        $this->assertSame('user', $owner->fresh()->role);
        $this->assertTrue(Notification::where('user_id', $owner->id)->where('type', 'seller_application')->exists());

        $this->postJson('/api/admin/approvals/seller-applications/'.$store->id.'/reject', ['reason' => 'ซ้ำ'])
            ->assertOk()->assertJsonPath('data.already_decided', true);
        $this->assertSame('ที่อยู่ไม่ครบ', $store->fresh()->suspension_reason);

        $this->getJson('/api/admin/approvals/seller-applications?status=closed')
            ->assertJsonPath('data.data.0.rejection_reason', 'ที่อยู่ไม่ครบ');
    }
}
