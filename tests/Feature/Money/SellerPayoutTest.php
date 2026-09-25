<?php

namespace Tests\Feature\Money;

use App\Models\EarningsLedger;
use App\Models\PaymentMethod;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\OrderDistributionService;
use App\Services\SellerPayoutService;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

/**
 * จ่ายรายได้ผู้ขายจริง (audit SELLER-01 / SELLER-02 / G18 / CC-06)
 *
 * - รายได้จ่ายหลังส่งของถึง + ครบ holding days เท่านั้น
 * - จ่ายครั้งเดียว (idempotent) และหักหนี้ค้างก่อนโอน
 * - ถอนเงิน/ยกเลิกถอนผ่าน WithdrawalService (ยกเลิกซ้ำคืนเงินครั้งเดียว)
 * - API ถอนเงินของแอป: ตั้ง PIN → เพิ่มบัญชี → ถอน
 */
class SellerPayoutTest extends MoneyTestCase
{
    private function distributedOrder(float $price = 1000, array $orderOverrides = []): array
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $order = $this->makePaidOrder($buyer, [[$this->makeProduct($seller, $price), 1]], 0, $orderOverrides);
        app(OrderDistributionService::class)->processOrderDistribution($order);

        return [$seller, $buyer, $order->fresh()];
    }

    public function test_earnings_are_not_released_before_delivery(): void
    {
        [$seller, , $order] = $this->distributedOrder();
        $order->forceFill(['status' => 'processing'])->saveQuietly();

        $result = app(SellerPayoutService::class)->releaseEligible();

        $this->assertSame(0, $result['credited']);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($seller), 0.001);
        $this->assertSame(EarningsLedger::STATUS_PENDING, EarningsLedger::where('source_id', $order->id)->where('source_type', 'Order')->sole()->status);
    }

    public function test_earnings_wait_for_holding_days_after_delivery(): void
    {
        [$seller, , $order] = $this->distributedOrder();
        $order->forceFill(['status' => 'delivered', 'delivered_at' => now()->subDay()])->saveQuietly();

        $this->assertSame(0, app(SellerPayoutService::class)->releaseEligible()['credited']);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($seller), 0.001);
    }

    public function test_delivered_order_is_paid_to_seller_wallet_once(): void
    {
        [$seller, , $order] = $this->distributedOrder();
        $order->forceFill(['status' => 'completed', 'delivered_at' => now()->subDays(4)])->saveQuietly();

        $service = app(SellerPayoutService::class);
        $first = $service->releaseEligible();
        $ledger = EarningsLedger::where('source_id', $order->id)->where('source_type', 'Order')->sole();
        $second = $service->releaseLedger((int) $ledger->id);

        $this->assertSame(1, $first['credited']);
        $this->assertSame('skipped', $second['status']);
        $this->assertEqualsWithDelta(900.00, $this->walletBalance($seller), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', SellerPayoutService::WALLET_REFERENCE_TYPE)
            ->where('reference_id', $ledger->id)
            ->count());

        $ledger->refresh();
        $this->assertSame(EarningsLedger::STATUS_PAID, $ledger->status);
        $this->assertNotNull($ledger->wallet_transaction_id);
        $this->assertEqualsWithDelta(0.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);
    }

    public function test_release_command_pays_eligible_earnings(): void
    {
        [$seller, , $order] = $this->distributedOrder(500);
        $order->forceFill(['status' => 'delivered', 'delivered_at' => now()->subDays(10)])->saveQuietly();

        Artisan::call('earnings:release-pending', ['--limit' => 10]);

        $this->assertEqualsWithDelta(450.00, $this->walletBalance($seller), 0.001);
    }

    public function test_outstanding_debt_is_collected_from_payout(): void
    {
        [$seller, , $order] = $this->distributedOrder();
        WalletDebt::createDebt((int) $seller->id, 100, 'SellerClawback', 999999, 'หนี้เดิมจากการคืนเงิน', null, 1);
        $order->forceFill(['status' => 'delivered', 'delivered_at' => now()->subDays(4)])->saveQuietly();

        app(SellerPayoutService::class)->releaseEligible();

        $this->assertEqualsWithDelta(800.00, $this->walletBalance($seller), 0.001);
        $this->assertEqualsWithDelta(100.00, $this->platformBalance('refund_pool'), 0.001);
        $this->assertSame(WalletDebt::STATUS_PAID, WalletDebt::where('user_id', $seller->id)->sole()->status);

        $ledger = EarningsLedger::where('source_id', $order->id)->where('source_type', 'Order')->sole();
        $this->assertEqualsWithDelta(100.00, (float) $ledger->debt_deduction, 0.001);
        $this->assertEqualsWithDelta(800.00, (float) $ledger->net_amount, 0.001);
    }

    public function test_seller_withdraw_and_double_cancel_refunds_once(): void
    {
        $seller = $this->makeUser('ผู้ขายถอนเงิน', 1000);
        $seller->forceFill(['kyc_status' => 'approved'])->save();
        $method = PaymentMethod::create([
            'user_id' => $seller->id,
            'type' => 'bank_transfer',
            'name' => 'กสิกร',
            'account_name' => 'ผู้ขาย ทดสอบ',
            'account_number' => '1234567890',
            'bank_name' => 'กสิกรไทย',
            'is_active' => true,
            'is_default' => true,
        ]);

        $service = app(WithdrawalService::class);
        $request = $service->createWithdrawalRequest($seller->fresh(), 300, $method->id, 'ถอนรายได้', null);
        $this->assertEqualsWithDelta(700.00, $this->walletBalance($seller), 0.001);

        $service->cancelWithdrawal($request, $seller);
        try {
            $service->cancelWithdrawal(WithdrawalRequest::find($request->id), $seller);
        } catch (\Exception $e) {
            // ยกเลิกซ้ำต้องถูกปฏิเสธ
        }

        $this->assertEqualsWithDelta(1000.00, $this->walletBalance($seller), 0.001);
        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_app_withdraw_flow_requires_pin_and_bank_account(): void
    {
        $user = $this->makeUser('ไรเดอร์ถอนเงิน', 1500);
        $user->forceFill(['kyc_status' => 'approved'])->save();
        Sanctum::actingAs($user->fresh());

        // ยังไม่ตั้ง PIN → ถอนไม่ได้
        $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500, 'pin' => '482913'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PIN_NOT_SET');

        $this->postJson('/api/v1/wallet/pin', ['pin' => '482913', 'pin_confirmation' => '482913'])
            ->assertOk()
            ->assertJsonPath('data.has_pin', true);

        // ยังไม่มีบัญชีรับเงิน
        $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500, 'pin' => '482913'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAYMENT_METHOD_REQUIRED');

        $account = $this->postJson('/api/v1/wallet/bank-accounts', [
            'type' => 'bank_transfer',
            'account_name' => 'ไรเดอร์ ทดสอบ',
            'account_number' => '987-6-54321-0',
            'bank_name' => 'ไทยพาณิชย์',
            'pin' => '482913',
        ])->assertCreated()->json('data');
        $this->assertTrue($account['is_default']);
        $this->assertSame('3210', $account['account_last4']);

        // PIN ผิด
        $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500, 'pin' => '000001', 'payment_method' => 'bank'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'INVALID_PIN');

        $response = $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500, 'pin' => '482913', 'payment_method' => 'bank'])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertSame('pending', $response->json('data.status'));
        $this->assertEqualsWithDelta(500.00, (float) $response->json('data.amount'), 0.001);
        $this->assertIsNumeric($response->json('data.net_amount'));
        $this->assertEqualsWithDelta(1000.00, $this->walletBalance($user), 0.001);

        $id = $response->json('data.id');
        $this->postJson("/api/v1/wallet/withdrawals/{$id}/cancel")->assertOk();
        $this->postJson("/api/v1/wallet/withdrawals/{$id}/cancel")->assertStatus(409);
        $this->assertEqualsWithDelta(1500.00, $this->walletBalance($user), 0.001);
    }
}
