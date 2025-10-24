<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletService();
    }

    /** @test */
    public function it_can_create_wallet_for_user()
    {
        $user = User::factory()->create();

        $wallet = $this->walletService->createWallet($user);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals($user->id, $wallet->user_id);
    }

    /** @test */
    public function it_can_credit_wallet()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);

        $wallet->credit(500, 'commission', 'Test commission');

        $this->assertEquals(500, $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 500,
            'transaction_type' => 'commission'
        ]);
    }

    /** @test */
    public function it_can_debit_wallet()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 1000]);

        $wallet->debit(300, 'withdrawal', 'Test withdrawal');

        $this->assertEquals(700, $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 300,
            'transaction_type' => 'withdrawal'
        ]);
    }

    /** @test */
    public function it_checks_sufficient_balance()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 500]);

        $this->assertTrue($wallet->hasSufficientBalance(300));
        $this->assertFalse($wallet->hasSufficientBalance(600));
    }

    /** @test */
    public function it_can_request_withdrawal()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 1000]);

        $bankDetails = [
            'bank_name' => 'SCB',
            'account_number' => '1234567890',
            'account_name' => 'Test User'
        ];

        $withdrawal = $this->walletService->requestWithdrawal($user, 500, $bankDetails);

        $this->assertInstanceOf(Withdrawal::class, $withdrawal);
        $this->assertEquals(500, $withdrawal->amount);
        $this->assertEquals('pending', $withdrawal->status);
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'amount' => 500,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_calculates_withdrawal_fee_correctly()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 1000]);

        $bankDetails = [
            'bank_name' => 'SCB',
            'account_number' => '1234567890',
            'account_name' => 'Test User'
        ];

        $withdrawal = $this->walletService->requestWithdrawal($user, 1000, $bankDetails);

        // Fee should be 2% or 10 THB minimum
        $expectedFee = max(20, 10); // 2% of 1000 = 20
        $this->assertEquals($expectedFee, $withdrawal->fee);
        $this->assertEquals(980, $withdrawal->net_amount);
    }

    /** @test */
    public function it_can_approve_withdrawal()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 1000]);

        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => 500,
            'fee' => 10,
            'net_amount' => 490,
            'status' => 'pending'
        ]);

        $this->walletService->approveWithdrawal($withdrawal, $admin);

        $this->assertEquals('completed', $withdrawal->fresh()->status);
        $this->assertEquals(500, $wallet->fresh()->balance); // 1000 - 500 = 500
    }

    /** @test */
    public function it_can_reject_withdrawal()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 1000]);

        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => 500,
            'status' => 'pending'
        ]);

        $this->walletService->rejectWithdrawal($withdrawal, $admin, 'Insufficient documents');

        $this->assertEquals('rejected', $withdrawal->fresh()->status);
        $this->assertEquals(1000, $wallet->fresh()->balance); // Balance unchanged
    }

    /** @test */
    public function it_gets_transaction_history()
    {
        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);

        $wallet->credit(100, 'commission', 'Test 1');
        $wallet->credit(200, 'bonus', 'Test 2');
        $wallet->debit(50, 'withdrawal', 'Test 3');

        $history = $this->walletService->getTransactionHistory($user, 10);

        $this->assertCount(3, $history);
    }

    /** @test */
    public function it_prevents_withdrawal_with_insufficient_balance()
    {
        $this->expectException(\Exception::class);

        $user = User::factory()->create();
        $wallet = $this->walletService->createWallet($user);
        $wallet->update(['balance' => 100]);

        $bankDetails = [
            'bank_name' => 'SCB',
            'account_number' => '1234567890',
            'account_name' => 'Test User'
        ];

        $this->walletService->requestWithdrawal($user, 500, $bankDetails);
    }
}
