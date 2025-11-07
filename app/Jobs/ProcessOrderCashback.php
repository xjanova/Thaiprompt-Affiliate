<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderCashback implements ShouldQueue
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
            $cashbackService = new CashbackService(new WalletService());

            // Skip if cashback already processed
            if ($this->order->cashback_processed) {
                Log::info('Cashback already processed for order', ['order_id' => $this->order->id]);
                return;
            }

            // Process cashback
            $transaction = $cashbackService->processOrderCashback($this->order);

            if ($transaction) {
                Log::info('Cashback processed successfully via queue', [
                    'order_id' => $this->order->id,
                    'cashback_amount' => $transaction->amount,
                    'transaction_id' => $transaction->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process cashback via queue', [
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
        Log::error('Cashback job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin
        // Notification::route('mail', config('mail.admin_email'))
        //     ->notify(new CashbackProcessingFailed($this->order, $exception));
    }
}
