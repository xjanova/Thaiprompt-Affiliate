<?php

namespace App\Jobs\FoodPassport;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendFoodPassportNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $notificationType,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::warning('User not found for notification', [
                    'user_id' => $this->userId,
                ]);
                return;
            }

            // Build notification message based on type
            $notification = $this->buildNotification();

            if ($notification) {
                // Send via database notification
                $user->notify($notification);

                // Could also send via email, SMS, LINE, etc.
                if ($this->shouldSendEmail($user)) {
                    // Send email notification
                }

                Log::info('Notification sent successfully', [
                    'user_id' => $this->userId,
                    'type' => $this->notificationType,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $this->userId,
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build notification instance based on type.
     */
    protected function buildNotification(): mixed
    {
        return match($this->notificationType) {
            'product_created' => new \App\Notifications\FoodPassport\ProductCreatedNotification($this->data),
            'journey_stage_completed' => new \App\Notifications\FoodPassport\StageCompletedNotification($this->data),
            'quality_failed' => new \App\Notifications\FoodPassport\QualityFailedNotification($this->data),
            'quality_excellent' => new \App\Notifications\FoodPassport\QualityExcellentNotification($this->data),
            'credit_issued' => new \App\Notifications\FoodPassport\CarbonCreditIssuedNotification($this->data),
            'credit_sold' => new \App\Notifications\FoodPassport\CarbonCreditSoldNotification($this->data),
            'credit_purchased' => new \App\Notifications\FoodPassport\CarbonCreditPurchasedNotification($this->data),
            'credit_retired' => new \App\Notifications\FoodPassport\CarbonCreditRetiredNotification($this->data),
            'credit_expired' => new \App\Notifications\FoodPassport\CarbonCreditExpiredNotification($this->data),
            default => null,
        };
    }

    /**
     * Check if email notification should be sent.
     */
    protected function shouldSendEmail(User $user): bool
    {
        // Check user preferences
        $emailTypes = [
            'quality_failed',
            'credit_issued',
            'credit_sold',
        ];

        return in_array($this->notificationType, $emailTypes)
            && ($user->notification_preferences['email'] ?? true);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendFoodPassportNotificationJob failed permanently', [
            'user_id' => $this->userId,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
