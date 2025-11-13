<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXToken;
use App\Notifications\TPIX\TokenDeployedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTokenDeployedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected int $tokenId;

    public function __construct(int $tokenId)
    {
        $this->tokenId = $tokenId;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $token = TPIXToken::with('creator')->find($this->tokenId);

            if (!$token || !$token->creator) {
                Log::error("Token or creator not found for notification: {$this->tokenId}");
                return;
            }

            $token->creator->notify(new TokenDeployedNotification($token));

            Log::info("Token deployed notification sent to user {$token->creator_id}");

        } catch (\Exception $e) {
            Log::error("Failed to send token deployed notification: " . $e->getMessage());
            throw $e;
        }
    }
}
