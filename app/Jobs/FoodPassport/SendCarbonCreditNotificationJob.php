<?php

namespace App\Jobs\FoodPassport;

use App\Models\CarbonCredit;
use App\Models\User;
use App\Notifications\FoodPassport\CarbonCreditIssuedNotification;
use App\Notifications\FoodPassport\CarbonCreditTradedNotification;
use App\Notifications\FoodPassport\CarbonCreditRetiredNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Carbon Credit Notification Job
 *
 * Sends notifications for carbon credit events
 */
class SendCarbonCreditNotificationJob implements ShouldQueue
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
        public CarbonCredit $credit,
        public string $notificationType,
        public array $data = [],
        public ?int $recipientUserId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Sending carbon credit notification', [
            'credit_id' => $this->credit->id,
            'type' => $this->notificationType,
        ]);

        // Determine recipient
        $userId = $this->recipientUserId ?? $this->credit->user_id;
        $user = User::find($userId);

        if (!$user) {
            Log::warning('Cannot send notification: User not found', [
                'user_id' => $userId,
            ]);
            return;
        }

        // Send appropriate notification based on type
        match($this->notificationType) {
            'credit_issued' => $this->sendCreditIssuedNotification($user),
            'credit_sold' => $this->sendCreditTradedNotification($user, 'sold'),
            'credit_purchased' => $this->sendCreditTradedNotification($user, 'purchased'),
            'credit_retired' => $this->sendCreditRetiredNotification($user),
            'credit_expired' => $this->sendCreditExpiredNotification($user),
            default => Log::warning('Unknown notification type', ['type' => $this->notificationType]),
        };

        Log::info('Carbon credit notification sent', [
            'credit_id' => $this->credit->id,
            'user_id' => $user->id,
            'type' => $this->notificationType,
        ]);
    }

    /**
     * Send credit issued notification.
     */
    protected function sendCreditIssuedNotification(User $user): void
    {
        $notificationData = [
            'credit_id' => $this->credit->id,
            'credit_number' => $this->credit->credit_number,
            'amount' => $this->credit->credit_amount,
            'value' => $this->credit->total_value,
            'tier' => $this->data['tier'] ?? 'Bronze',
            'issued_date' => $this->credit->issued_date?->format('d/m/Y H:i'),
            'expiry_date' => $this->credit->expiry_date?->format('d/m/Y'),
            'blockchain_hash' => $this->credit->blockchain_hash,
            'explorer_url' => $this->credit->blockchain_hash
                ? \App\Helpers\TpixHelper::getTransactionUrl($this->credit->blockchain_hash)
                : null,
        ];

        $user->notify(new CarbonCreditIssuedNotification($notificationData));
    }

    /**
     * Send credit traded notification.
     */
    protected function sendCreditTradedNotification(User $user, string $side): void
    {
        $notificationData = [
            'credit_id' => $this->credit->id,
            'credit_number' => $this->credit->credit_number,
            'amount' => $this->credit->credit_amount,
            'trade_price' => $this->credit->trade_price,
            'side' => $side, // 'sold' or 'purchased'
            'commission' => $this->data['commission'] ?? 0,
            'net_amount' => $this->data['net_amount'] ?? $this->credit->trade_price,
            'counterparty_name' => $side === 'sold'
                ? $this->data['buyer_name'] ?? 'Unknown'
                : $this->data['seller_name'] ?? 'Unknown',
            'traded_date' => $this->credit->traded_date?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'blockchain_hash' => $this->credit->trade_tx_hash ?? $this->credit->blockchain_hash,
        ];

        $user->notify(new CarbonCreditTradedNotification($notificationData));
    }

    /**
     * Send credit retired notification.
     */
    protected function sendCreditRetiredNotification(User $user): void
    {
        $notificationData = [
            'credit_id' => $this->credit->id,
            'credit_number' => $this->credit->credit_number,
            'amount' => $this->credit->credit_amount,
            'retired_date' => now()->format('d/m/Y H:i'),
            'environmental_impact' => $this->data['environmental_impact'] ?? [],
            'blockchain_hash' => $this->credit->retirement_tx_hash ?? $this->credit->blockchain_hash,
        ];

        $user->notify(new CarbonCreditRetiredNotification($notificationData));
    }

    /**
     * Send credit expired notification.
     */
    protected function sendCreditExpiredNotification(User $user): void
    {
        // Similar to retired notification but different message
        $notificationData = [
            'credit_id' => $this->credit->id,
            'credit_number' => $this->credit->credit_number,
            'amount' => $this->credit->credit_amount,
            'expiry_date' => $this->credit->expiry_date?->format('d/m/Y'),
        ];

        // Could create separate notification class or reuse existing
        $user->notify(new CarbonCreditRetiredNotification($notificationData));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send carbon credit notification', [
            'credit_id' => $this->credit->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
