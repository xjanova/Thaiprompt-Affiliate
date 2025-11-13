<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXStake;
use App\Models\TPIXStakingPool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateStakingRewardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected ?int $poolId;

    /**
     * Create a new job instance.
     *
     * @param int|null $poolId If null, update all pools
     */
    public function __construct(?int $poolId = null)
    {
        $this->poolId = $poolId;
        $this->onQueue('staking');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting staking rewards update');

            $pools = $this->poolId
                ? TPIXStakingPool::where('id', $this->poolId)->get()
                : TPIXStakingPool::where('status', 'active')->get();

            $totalUpdated = 0;

            foreach ($pools as $pool) {
                $updated = $this->updatePoolRewards($pool);
                $totalUpdated += $updated;
            }

            Log::info("Updated rewards for {$totalUpdated} stakes across {$pools->count()} pools");

        } catch (\Exception $e) {
            Log::error('Failed to update staking rewards: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update rewards for all active stakes in a pool
     */
    protected function updatePoolRewards(TPIXStakingPool $pool): int
    {
        $stakes = TPIXStake::where('pool_id', $pool->id)
            ->where('status', 'active')
            ->get();

        $count = 0;

        foreach ($stakes as $stake) {
            try {
                // Calculate current rewards
                $stake->updateRewards();

                // Check if stake is unlocked and send notification
                if ($stake->isUnlocked() && !$stake->unlock_notified_at) {
                    dispatch(new \App\Jobs\TPIX\SendStakeUnlockedNotificationJob($stake->id));
                    $stake->update(['unlock_notified_at' => now()]);
                }

                $count++;

            } catch (\Exception $e) {
                Log::error("Failed to update stake {$stake->id}: " . $e->getMessage());
                continue;
            }
        }

        // Update pool statistics
        $pool->update([
            'pending_rewards' => $stakes->sum('rewards_pending'),
        ]);

        return $count;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Staking rewards update failed permanently: ' . $exception->getMessage());
    }
}
