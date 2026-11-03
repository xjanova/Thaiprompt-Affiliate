<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * F25 (M7): ดึงค่าแนะนำดูดวงคืน (void) ห้ามทำให้กระเป๋าติดลบ
 *
 * - ดึงเท่าที่ยอดในกระเป๋ามี ส่วนที่ขาดบันทึกเป็น WalletDebt (แบบเดียวกับ MlmCommissionClawbackService)
 * - รายการในกระเป๋าใช้ type 'fee' แบบเดียวกับการดึงคืนคอม MLM ไม่ใช่ 'withdrawal' (ไม่ใช่การถอนเงิน)
 */
class FortuneClawbackDebtTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected User $sponsorUser;

    protected User $grandUser;

    protected FortuneReading $bill;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->setUpFortuneChain();

        [$this->grandUser, $grand] = $this->makeChainMember();
        [$this->sponsorUser, $sponsor] = $this->makeChainMember($grand);
        [$buyerUser, $buyer] = $this->makeChainMember($sponsor);

        $this->bill = $this->makeBill($buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 99.83);
        app(FortuneCommissionService::class)->distributeCommissions($this->bill, $buyer, $this->settings);
    }

    private function setBalance(int $userId, float $balance): void
    {
        Wallet::where('user_id', $userId)->update(['balance' => $balance]);
    }

    public function test_void_after_sponsor_withdrew_creates_debt_instead_of_negative_balance(): void
    {
        $this->setBalance($this->sponsorUser->id, 0);   // S ถอนออกหมดแล้ว

        $this->assertTrue($this->bill->fresh()->voidApproval('คืนเงิน', null)['ok']);

        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id), 'กระเป๋าห้ามติดลบ');
        $debt = WalletDebt::where('user_id', $this->sponsorUser->id)->first();
        $this->assertNotNull($debt, 'ส่วนที่ดึงไม่ได้ต้องเป็นหนี้');
        $this->assertEquals(10.0, (float) $debt->remaining_amount);
        $this->assertSame(WalletDebt::STATUS_ACTIVE, $debt->status);

        // G ยังมียอด → ดึงคืนเต็ม ไม่มีหนี้
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));
        $this->assertSame(0, WalletDebt::where('user_id', $this->grandUser->id)->count());
    }

    public function test_partial_balance_is_taken_and_the_rest_becomes_debt(): void
    {
        $this->setBalance($this->sponsorUser->id, 4);

        $this->bill->fresh()->voidApproval('คืนเงิน', null);

        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertEquals(6.0, (float) WalletDebt::where('user_id', $this->sponsorUser->id)->value('remaining_amount'));
        $claw = WalletTransaction::where('user_id', $this->sponsorUser->id)
            ->where('reference_type', FortuneCommission::class)
            ->latest('id')->first();
        $this->assertEquals(4.0, (float) $claw->amount);
        $this->assertEquals(0.0, (float) $claw->balance_after);
    }

    public function test_clawback_wallet_transaction_uses_fee_type_not_withdrawal(): void
    {
        $this->bill->fresh()->voidApproval('คืนเงิน', null);

        $types = WalletTransaction::whereIn('user_id', [$this->sponsorUser->id, $this->grandUser->id])
            ->where('metadata->mode', 'fortune_commission_reversal')
            ->pluck('type')->unique()->values()->all();

        $this->assertSame(['fee'], $types);
        $this->assertSame(0, WalletDebt::count(), 'มียอดพอ ไม่ต้องมีหนี้');
    }

    public function test_second_void_does_not_create_more_debt(): void
    {
        $this->setBalance($this->sponsorUser->id, 0);
        $this->bill->fresh()->voidApproval('คืนเงิน', null);
        $this->bill->fresh()->voidApproval('กดซ้ำ', null);

        $this->assertSame(1, WalletDebt::where('user_id', $this->sponsorUser->id)->count());
    }
}
