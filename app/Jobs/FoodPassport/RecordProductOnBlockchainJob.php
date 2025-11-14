<?php

namespace App\Jobs\FoodPassport;

use App\Models\FoodProduct;
use App\Services\BlockchainRecordService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecordProductOnBlockchainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FoodProduct $product
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BlockchainRecordService $blockchain): void
    {
        try {
            // Skip if already recorded
            if ($this->product->blockchain_hash) {
                Log::info('Product already recorded on blockchain', [
                    'product_id' => $this->product->id,
                    'hash' => $this->product->blockchain_hash,
                ]);
                return;
            }

            // Record on blockchain
            $hash = $blockchain->recordProductOnChain($this->product);

            // Update product
            $this->product->blockchain_hash = $hash;
            $this->product->ipfs_hash = $blockchain->getIpfsHash($this->product);
            $this->product->saveQuietly();

            Log::info('Product recorded on blockchain successfully', [
                'product_id' => $this->product->id,
                'blockchain_hash' => $hash,
                'network' => config('food-passport.blockchain.default_network'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record product on blockchain', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // If it's a network error, we can retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RecordProductOnBlockchainJob failed permanently', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
        ]);

        // Could send admin notification here
    }
}
