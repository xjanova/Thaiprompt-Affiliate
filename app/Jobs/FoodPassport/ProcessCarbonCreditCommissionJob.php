<?php

namespace App\Jobs\FoodPassport;

use App\Models\CarbonCredit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessCarbonCreditCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CarbonCredit $credit,
        public float $commissionAmount
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            // Record platform commission
            $this->recordPlatformCommission();

            // Distribute to seller (95% of trade price)
            $sellerAmount = $this->credit->trade_price - $this->commissionAmount;
            $this->creditSellerWallet($sellerAmount);

            // Debit from buyer
            $this->debitBuyerWallet($this->credit->trade_price);

            DB::commit();

            Log::info('Carbon credit commission processed', [
                'credit_id' => $this->credit->id,
                'trade_price' => $this->credit->trade_price,
                'commission' => $this->commissionAmount,
                'seller_amount' => $sellerAmount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process carbon credit commission', [
                'credit_id' => $this->credit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record platform commission.
     */
    protected function recordPlatformCommission(): void
    {
        // Create commission record
        DB::table('carbon_credit_commissions')->insert([
            'carbon_credit_id' => $this->credit->id,
            'seller_id' => $this->credit->user_id,
            'buyer_id' => $this->credit->traded_to_user_id,
            'trade_price' => $this->credit->trade_price,
            'commission_amount' => $this->commissionAmount,
            'commission_percentage' => 5.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Platform commission recorded', [
            'amount' => $this->commissionAmount,
        ]);
    }

    /**
     * Credit seller's wallet.
     */
    protected function creditSellerWallet(float $amount): void
    {
        $seller = $this->credit->user;

        // Add to wallet balance
        DB::table('wallet_transactions')->insert([
            'user_id' => $seller->id,
            'type' => 'carbon_credit_sale',
            'amount' => $amount,
            'description' => "Sale of carbon credit #{$this->credit->id}",
            'reference_type' => 'carbon_credit',
            'reference_id' => $this->credit->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Seller wallet credited', [
            'seller_id' => $seller->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Debit buyer's wallet.
     */
    protected function debitBuyerWallet(float $amount): void
    {
        $buyer = $this->credit->tradedToUser;

        // Deduct from wallet balance
        DB::table('wallet_transactions')->insert([
            'user_id' => $buyer->id,
            'type' => 'carbon_credit_purchase',
            'amount' => -$amount,
            'description' => "Purchase of carbon credit #{$this->credit->id}",
            'reference_type' => 'carbon_credit',
            'reference_id' => $this->credit->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Buyer wallet debited', [
            'buyer_id' => $buyer->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessCarbonCreditCommissionJob failed permanently', [
            'credit_id' => $this->credit->id,
            'error' => $exception->getMessage(),
        ]);

        // Could send admin alert here
    }
}
