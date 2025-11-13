<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXToken;
use App\Services\TPIX\CMCIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCMCPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = [60, 300, 900]; // Retry after 1min, 5min, 15min

    protected ?int $tokenId;

    /**
     * Create a new job instance.
     *
     * @param int|null $tokenId If null, sync all tokens
     */
    public function __construct(?int $tokenId = null)
    {
        $this->tokenId = $tokenId;
        $this->onQueue('tpix-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(CMCIntegrationService $cmcService): void
    {
        try {
            if ($this->tokenId) {
                // Sync single token
                $token = TPIXToken::find($this->tokenId);

                if (!$token || !$token->cmc_id) {
                    Log::warning("Token {$this->tokenId} not found or no CMC ID");
                    return;
                }

                Log::info("Syncing CMC price for token: {$token->symbol}");
                $cmcService->syncTPIXToken($token);

            } else {
                // Sync all tokens with CMC ID
                $tokens = TPIXToken::whereNotNull('cmc_id')
                    ->where('status', 'active')
                    ->get();

                Log::info("Syncing CMC prices for {$tokens->count()} tokens");

                foreach ($tokens as $token) {
                    try {
                        $cmcService->syncTPIXToken($token);

                        // Rate limiting - CMC allows 333 calls/day on free plan
                        usleep(300000); // Sleep 0.3 seconds between calls

                    } catch (\Exception $e) {
                        Log::error("Failed to sync token {$token->symbol}: " . $e->getMessage());
                        continue;
                    }
                }
            }

            Log::info('CMC price sync completed successfully');

        } catch (\Exception $e) {
            Log::error('CMC price sync failed: ' . $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CMC price sync job failed permanently: ' . $exception->getMessage());

        // Notify admin
        // You can dispatch a notification here
    }
}
