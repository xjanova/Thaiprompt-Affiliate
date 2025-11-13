<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXReferralReward;
use App\Models\TPIXTokenBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeReferralRewardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected int $referralUseId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $referralUseId)
    {
        $this->referralUseId = $referralUseId;
        $this->onQueue('rewards');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Distributing referral rewards for use ID: {$this->referralUseId}");

            // Get pending rewards for this referral use
            $rewards = TPIXReferralReward::where('referral_use_id', $this->referralUseId)
                ->where('status', 'pending')
                ->get();

            if ($rewards->isEmpty()) {
                Log::info("No pending rewards found for referral use {$this->referralUseId}");
                return;
            }

            DB::beginTransaction();

            foreach ($rewards as $reward) {
                try {
                    // Get or create token balance for user
                    $balance = TPIXTokenBalance::firstOrCreate(
                        [
                            'token_id' => $reward->token_id,
                            'user_id' => $reward->user_id,
                        ],
                        [
                            'balance' => 0,
                            'available_balance' => 0,
                        ]
                    );

                    // Add reward to balance
                    $balance->increment('balance', $reward->amount);
                    $balance->increment('available_balance', $reward->amount);

                    // Generate mock transaction hash
                    $txHash = '0x' . bin2hex(random_bytes(32));

                    // Mark reward as paid
                    $reward->markAsPaid($txHash);

                    Log::info("Reward {$reward->id} distributed: {$reward->amount} tokens to user {$reward->user_id}");

                    // Dispatch notification
                    dispatch(new \App\Jobs\TPIX\SendRewardNotificationJob($reward->id));

                } catch (\Exception $e) {
                    Log::error("Failed to distribute reward {$reward->id}: " . $e->getMessage());
                    $reward->markAsFailed($e->getMessage());
                    continue;
                }
            }

            DB::commit();

            Log::info("Successfully distributed {$rewards->count()} rewards");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to distribute referral rewards: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Referral reward distribution failed permanently: " . $exception->getMessage());

        // Mark all pending rewards as failed
        TPIXReferralReward::where('referral_use_id', $this->referralUseId)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'metadata' => ['error' => $exception->getMessage()],
            ]);
    }
}
