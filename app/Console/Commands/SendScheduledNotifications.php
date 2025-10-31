<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled notifications that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for scheduled notifications...');

        // Get all pending scheduled notifications that are due
        $notifications = Notification::pendingScheduled()->get();

        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to send.');
            return 0;
        }

        $count = 0;
        foreach ($notifications as $notification) {
            try {
                // Mark as sent
                $notification->markAsSent();
                $count++;

                $this->info("Sent notification ID {$notification->id} to user {$notification->user_id}");
            } catch (\Exception $e) {
                Log::error("Failed to send scheduled notification {$notification->id}: " . $e->getMessage());
                $this->error("Failed to send notification ID {$notification->id}");
            }
        }

        $this->info("Successfully sent {$count} scheduled notification(s).");
        return 0;
    }
}
