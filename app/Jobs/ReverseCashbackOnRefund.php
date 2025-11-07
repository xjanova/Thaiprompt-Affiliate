<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReverseCashbackOnRefund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The order instance.
     */
    public Order $order;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $retryAfter = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Skip if cashback was not processed
            if (!$this->order->cashback_processed || $this->order->cashback_amount <= 0) {
                Log::info('No cashback to reverse for order', ['order_id' => $this->order->id]);
                return;
            }

            $walletService = new WalletService();
            $wallet = $walletService->getOrCreateWallet($this->order->user);

            // Deduct cashback from wallet
            $transaction = $walletService->withdraw(
                wallet: $wallet,
                amount: $this->order->cashback_amount,
                description: "ยกเลิก Cashback จากคำสั่งซื้อ #{$this->order->id} (Refund)",
                referenceType: 'order',
                referenceId: $this->order->id
            );

            // Mark cashback as reversed
            $this->order->update([
                'cashback_processed' => false,
                'cashback_reversed_at' => now(),
            ]);

            Log::info('Cashback reversed successfully', [
                'order_id' => $this->order->id,
                'cashback_amount' => $this->order->cashback_amount,
                'transaction_id' => $transaction->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reverse cashback', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Reverse cashback job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin
        // Notification::route('mail', config('mail.admin_email'))
        //     ->notify(new CashbackReversalFailed($this->order, $exception));
    }
}
