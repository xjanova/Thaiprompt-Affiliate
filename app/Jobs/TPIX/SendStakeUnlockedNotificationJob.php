<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXStake;
use App\Notifications\TPIX\StakeUnlockedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendStakeUnlockedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected int $stakeId;

    public function __construct(int $stakeId)
    {
        $this->stakeId = $stakeId;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $stake = TPIXStake::with(['user', 'pool'])->find($this->stakeId);

            if (!$stake || !$stake->user) {
                Log::error("Stake or user not found for notification: {$this->stakeId}");
                return;
            }

            $stake->user->notify(new StakeUnlockedNotification($stake));

            Log::info("Stake unlocked notification sent to user {$stake->user_id}");

        } catch (\Exception $e) {
            Log::error("Failed to send stake unlocked notification: " . $e->getMessage());
            throw $e;
        }
    }
}
