<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXReferralReward;
use App\Notifications\TPIX\ReferralRewardNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRewardNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected int $rewardId;

    public function __construct(int $rewardId)
    {
        $this->rewardId = $rewardId;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $reward = TPIXReferralReward::with(['user', 'token'])->find($this->rewardId);

            if (!$reward || !$reward->user) {
                Log::error("Reward or user not found for notification: {$this->rewardId}");
                return;
            }

            $reward->user->notify(new ReferralRewardNotification($reward));

            Log::info("Referral reward notification sent to user {$reward->user_id}");

        } catch (\Exception $e) {
            Log::error("Failed to send reward notification: " . $e->getMessage());
            throw $e;
        }
    }
}
