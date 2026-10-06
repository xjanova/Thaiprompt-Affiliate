<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\MlmCommission;
use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\Concerns\BuildsApprovalsWorld;
use Tests\Feature\Money\MoneyTestCase;

/**
 * 💰 แอปแอดมิน: คอมมิชชัน MLM — อนุมัติ/จ่ายผ่าน MlmCalculationService ตัวเดียวกับเว็บ · จ่ายครั้งเดียว · กองทุนไม่พอ = ไม่จ่าย
 */
#[Group('admin-app')]
class MlmCommissionsApprovalsTest extends MoneyTestCase
{
    use BuildsAdminAppWorld;
    use BuildsApprovalsWorld;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_list_pending_with_summary_oldest_first(): void
    {
        $earner = $this->makeUser('ผู้รับคอม');
        $this->travelTo(now()->subHours(3));
        $old = $this->makeCommission($earner, 'pending', 100);
        $this->travelBack();
        $new = $this->makeCommission($earner, 'pending', 50.25);
        $this->makeCommission($earner, 'approved', 70);
        $this->makeCommission($earner, 'paid', 999);

        $this->actAs($this->makeAdmin());
        $res = $this->getJson('/api/admin/approvals/mlm-commissions')->assertOk();
        $res->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.id', $old->id)
            ->assertJsonPath('data.data.1.id', $new->id)
            ->assertJsonPath('data.data.0.amount_thb', 100.0)
            ->assertJsonPath('data.data.0.recipient.name', 'ผู้รับคอม')
            ->assertJsonPath('data.data.0.actions.can_approve', true)
            ->assertJsonPath('data.summary.pending_count', 2)
            ->assertJsonPath('data.summary.pending_amount_thb', 150.25)
            ->assertJsonPath('data.summary.approved_count', 1)
            ->assertJsonPath('data.summary.approved_amount_thb', 70.0);
        $this->assertGreaterThanOrEqual(179, $res->json('data.data.0.waiting_minutes'));

        $this->getJson('/api/admin/approvals/mlm-commissions?status=approved')->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/approvals/mlm-commissions?status=nope')->assertStatus(422);
    }

    public function test_approve_then_pay_moves_money_once_and_guards_order(): void
    {
        $earner = $this->makeUser('ผู้รับคอม');
        $commission = $this->makeCommission($earner, 'pending', 120);

        $this->actAs($this->makeAdmin());

        // จ่ายก่อนอนุมัติไม่ได้
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/pay')
            ->assertStatus(409)->assertJsonPath('error_code', 'NOT_APPROVED');
        $this->assertSame('pending', $commission->fresh()->status);

        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.already', false);
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/approve')
            ->assertOk()->assertJsonPath('data.already', true);

        // กองทุน MLM ว่าง → จ่ายไม่ได้ ไม่มีเงินขยับ
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/pay')
            ->assertStatus(409)->assertJsonPath('error_code', 'PAY_FAILED');
        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('reference_type', MlmCommission::class)->where('reference_id', $commission->id)->count());

        PlatformWallet::getMlmPoolWallet()->addFunds(500, 'test_funding');

        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/pay')
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.already', false);

        $this->assertSame('paid', $commission->fresh()->status);
        $this->assertEqualsWithDelta(120.0, $this->walletBalance($earner), 0.001);
        $this->assertEqualsWithDelta(380.0, $this->platformBalance('mlm_pool'), 0.001);

        // กดจ่ายซ้ำ → สำเร็จเฉย ๆ จ่ายครั้งเดียว
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/pay')
            ->assertOk()->assertJsonPath('data.already', true);
        $this->assertSame(1, WalletTransaction::where('reference_type', MlmCommission::class)->where('reference_id', $commission->id)->count());
        $this->assertEqualsWithDelta(120.0, $this->walletBalance($earner), 0.001);

        // ปฏิเสธไปแล้วอนุมัติไม่ได้
        $rejected = $this->makeCommission($earner, 'rejected', 10);
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$rejected->id.'/approve')
            ->assertStatus(409)->assertJsonPath('error_code', 'INVALID_STATUS');
    }

    public function test_members_cannot_approve_or_pay(): void
    {
        PlatformWallet::getMlmPoolWallet()->addFunds(500, 'test_funding');
        $commission = $this->makeCommission($this->makeUser(), 'approved', 120);

        $this->actAs(User::factory()->create(['role' => 'user']));
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/pay')->assertStatus(403);
        $this->postJson('/api/admin/approvals/mlm-commissions/'.$commission->id.'/approve')->assertStatus(403);

        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertEqualsWithDelta(500.0, $this->platformBalance('mlm_pool'), 0.001);
    }
}
