<?php

namespace App\Listeners\FoodPassport;

use App\Events\FoodPassport\CarbonCreditTradedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateMarketplaceStatisticsListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CarbonCreditTradedEvent $event): void
    {
        try {
            $credit = $event->credit;

            // Update daily trading volume
            $date = now()->format('Y-m-d');
            $key = "carbon_marketplace:volume:{$date}";

            $volume = Cache::get($key, ['credits' => 0, 'value' => 0]);
            $volume['credits'] += $credit->credit_amount;
            $volume['value'] += $credit->trade_price;

            Cache::put($key, $volume, now()->addDays(30));

            // Update marketplace statistics
            $statsKey = 'carbon_marketplace:stats';
            $stats = Cache::get($statsKey, [
                'total_trades' => 0,
                'total_volume' => 0,
                'total_value' => 0,
                'average_price' => 0,
            ]);

            $stats['total_trades']++;
            $stats['total_volume'] += $credit->credit_amount;
            $stats['total_value'] += $credit->trade_price;
            $stats['average_price'] = $stats['total_value'] / $stats['total_volume'];

            Cache::put($statsKey, $stats, now()->addHours(24));

            // Update user trading stats
            $this->updateUserStats($event->seller->id, 'sold', $credit);
            $this->updateUserStats($event->buyer->id, 'bought', $credit);

            Log::info('Marketplace statistics updated', [
                'credit_id' => $credit->id,
                'daily_volume' => $volume,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update marketplace statistics', [
                'credit_id' => $event->credit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update user trading statistics.
     */
    protected function updateUserStats(int $userId, string $type, $credit): void
    {
        $key = "user:{$userId}:carbon_trades:{$type}";

        $stats = Cache::get($key, ['count' => 0, 'volume' => 0, 'value' => 0]);
        $stats['count']++;
        $stats['volume'] += $credit->credit_amount;
        $stats['value'] += $credit->trade_price;

        Cache::put($key, $stats, now()->addDays(30));
    }
}
