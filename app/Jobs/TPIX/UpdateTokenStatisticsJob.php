<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\TPIXTokenTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTokenStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected ?int $tokenId;

    /**
     * Create a new job instance.
     *
     * @param int|null $tokenId If null, update all tokens
     */
    public function __construct(?int $tokenId = null)
    {
        $this->tokenId = $tokenId;
        $this->onQueue('statistics');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting token statistics update');

            $tokens = $this->tokenId
                ? TPIXToken::where('id', $this->tokenId)->get()
                : TPIXToken::where('status', 'active')->get();

            $count = 0;

            foreach ($tokens as $token) {
                try {
                    $this->updateTokenStatistics($token);
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to update statistics for token {$token->id}: " . $e->getMessage());
                    continue;
                }
            }

            Log::info("Updated statistics for {$count} tokens");

        } catch (\Exception $e) {
            Log::error('Failed to update token statistics: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update statistics for a specific token
     */
    protected function updateTokenStatistics(TPIXToken $token): void
    {
        // Count holders
        $holdersCount = TPIXTokenBalance::where('token_id', $token->id)
            ->where('balance', '>', 0)
            ->count();

        // Count transactions (24h)
        $txCount24h = TPIXTokenTransfer::where('token_id', $token->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('status', 'confirmed')
            ->count();

        // Calculate volume (24h)
        $volume24h = TPIXTokenTransfer::where('token_id', $token->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('status', 'confirmed')
            ->sum('amount');

        // Calculate 24h price change
        $priceChange24h = $this->calculatePriceChange($token, 24);
        $priceChange7d = $this->calculatePriceChange($token, 168); // 7 days

        // Calculate market cap (if price is set)
        $marketCap = null;
        if ($token->current_price_tpix) {
            $marketCap = bcmul($token->total_supply, $token->current_price_tpix, 2);
        }

        // Update token
        $token->update([
            'holders_count' => $holdersCount,
            'tx_count_24h' => $txCount24h,
            'volume_24h' => $volume24h,
            'price_change_24h' => $priceChange24h,
            'price_change_7d' => $priceChange7d,
            'market_cap' => $marketCap,
            'last_stats_update' => now(),
        ]);

        Log::info("Updated statistics for token {$token->symbol}: {$holdersCount} holders, {$txCount24h} txs");
    }

    /**
     * Calculate price change percentage
     */
    protected function calculatePriceChange(TPIXToken $token, int $hours): ?float
    {
        // This would query historical price data
        // For now, return a random value for demonstration
        // In production, implement actual price history tracking

        return null; // Implement actual calculation
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Token statistics update failed permanently: ' . $exception->getMessage());
    }
}
