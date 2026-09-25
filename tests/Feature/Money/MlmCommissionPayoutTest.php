<?php

namespace Tests\Feature\Money;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\PlatformWallet;
use App\Models\WalletTransaction;
use App\Services\MlmCalculationService;

/**
 * จ่ายคอม MLM ที่อนุมัติแล้ว (audit G5)
 *
 * - จ่ายเข้า wallet ผ่าน WalletService (type = commission, balance_before ครบ) — เดิม QueryException ทุกครั้ง
 * - กดจ่ายซ้ำ = จ่ายครั้งเดียว
 * - กองทุน MLM ไม่พอ = ข้ามรายการนั้น (ไม่จ่ายเงินที่ไม่มีอยู่จริง)
 */
class MlmCommissionPayoutTest extends MoneyTestCase
{
    private function approvedCommission(float $amount): MlmCommission
    {
        $plan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        $earner = $this->makeUser('ผู้รับคอม');
        $member = MlmMember::create([
            'user_id' => $earner->id,
            'mlm_plan_id' => $plan->id,
            'member_code' => 'M'.uniqid(),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
        ]);

        return MlmCommission::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $plan->id,
            'user_id' => $earner->id,
            'type' => 'direct_referral',
            'level' => 1,
            'pv_amount' => 0,
            'sales_amount' => 1000,
            'commission_amount' => $amount,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function test_approved_commission_is_paid_once_with_wallet_service(): void
    {
        PlatformWallet::getMlmPoolWallet()->addFunds(500, 'test_funding');
        $commission = $this->approvedCommission(120);

        $service = new MlmCalculationService;
        $this->assertSame(1, $service->payApprovedCommissions([$commission->id]));
        $this->assertSame(0, $service->payApprovedCommissions([$commission->id]));

        $commission->refresh();
        $this->assertSame('paid', $commission->status);

        $tx = WalletTransaction::where('reference_type', MlmCommission::class)->where('reference_id', $commission->id)->sole();
        $this->assertSame('commission', $tx->type);
        $this->assertEqualsWithDelta(0.00, (float) $tx->balance_before, 0.001);
        $this->assertEqualsWithDelta(120.00, (float) $tx->balance_after, 0.001);
        $this->assertSame($tx->id, (int) $commission->wallet_transaction_id);
        $this->assertEqualsWithDelta(380.00, $this->platformBalance('mlm_pool'), 0.001);
    }

    public function test_commission_is_skipped_when_pool_is_insufficient(): void
    {
        $commission = $this->approvedCommission(120);

        $paid = (new MlmCalculationService)->payApprovedCommissions([$commission->id]);

        $this->assertSame(0, $paid);
        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('reference_type', MlmCommission::class)->where('reference_id', $commission->id)->count());
    }
}
