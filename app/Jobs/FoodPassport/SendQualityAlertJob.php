<?php

namespace App\Jobs\FoodPassport;

use App\Models\QualityCheckpoint;
use App\Models\User;
use App\Notifications\FoodPassport\QualityCheckFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Quality Alert Job
 *
 * Sends notifications to stakeholders about quality checkpoint results
 */
class SendQualityAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public QualityCheckpoint $checkpoint,
        public string $alertType,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Sending quality alert', [
            'checkpoint_id' => $this->checkpoint->id,
            'alert_type' => $this->alertType,
        ]);

        $product = $this->checkpoint->foodProduct;
        if (!$product) {
            Log::warning('Quality alert: Product not found', [
                'checkpoint_id' => $this->checkpoint->id,
            ]);
            return;
        }

        // Notify farmer
        if ($product->farmer_id) {
            $farmer = User::find($product->farmer_id);
            if ($farmer) {
                $this->sendNotification($farmer, $this->alertType);
            }
        }

        // Notify all stakeholders
        $stakeholders = $product->stakeholders;
        foreach ($stakeholders as $stakeholder) {
            if ($stakeholder->user) {
                $this->sendNotification($stakeholder->user, $this->alertType);
            }
        }

        // Notify admins if critical
        if ($this->alertType === 'quality_failed') {
            $this->notifyAdmins();
        }

        Log::info('Quality alerts sent successfully', [
            'checkpoint_id' => $this->checkpoint->id,
            'recipients' => count($stakeholders) + 1,
        ]);
    }

    /**
     * Send notification to user.
     */
    protected function sendNotification(User $user, string $type): void
    {
        $notificationData = [
            'checkpoint_id' => $this->checkpoint->id,
            'product_id' => $this->checkpoint->food_product_id,
            'passport_id' => $this->checkpoint->foodProduct->food_passport_id ?? null,
            'type' => $this->checkpoint->checkpoint_type,
            'result' => $this->checkpoint->overall_result,
            'score' => $this->checkpoint->pass_score,
            'alert_type' => $type,
            ...$this->data,
        ];

        $user->notify(new QualityCheckFailedNotification($notificationData));
    }

    /**
     * Notify system administrators.
     */
    protected function notifyAdmins(): void
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        foreach ($admins as $admin) {
            $this->sendNotification($admin, 'quality_failed');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send quality alert', [
            'checkpoint_id' => $this->checkpoint->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
