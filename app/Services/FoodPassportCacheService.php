<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\CarbonCredit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Food Passport Cache Service
 *
 * Optimizes performance with strategic caching for TPIX blockchain queries
 * and frequently accessed data.
 */
class FoodPassportCacheService
{
    // Cache TTLs (in seconds)
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 1800;    // 30 minutes
    const TTL_LONG = 3600;      // 1 hour
    const TTL_DAY = 86400;      // 24 hours
    const TTL_WEEK = 604800;    // 7 days

    /**
     * Cache product details with relationships.
     */
    public function cacheProductDetails(FoodProduct $product): array
    {
        $key = "food_product:details:{$product->id}";

        return Cache::remember($key, self::TTL_MEDIUM, function () use ($product) {
            $product->load([
                'journeyStages',
                'qualityCheckpoints',
                'carbonRecords',
                'certifications',
                'stakeholders',
            ]);

            return [
                'product' => $product->toArray(),
                'carbon_breakdown' => $product->getCarbonBreakdown(),
                'average_quality' => $product->getAverageQualityScoreAttribute(),
                'current_stage' => $product->currentStage()?->toArray(),
            ];
        });
    }

    /**
     * Cache product by passport ID.
     */
    public function cacheProductByPassportId(string $passportId): ?FoodProduct
    {
        $key = "food_product:passport:{$passportId}";

        return Cache::remember($key, self::TTL_LONG, function () use ($passportId) {
            return FoodProduct::where('food_passport_id', $passportId)->first();
        });
    }

    /**
     * Cache carbon marketplace data.
     */
    public function cacheMarketplaceData(): array
    {
        $key = 'carbon_marketplace:data';

        return Cache::remember($key, self::TTL_SHORT, function () {
            return [
                'total_credits' => CarbonCredit::where('status', 'active')
                    ->where('tradeable', true)
                    ->sum('credit_amount'),

                'total_value' => CarbonCredit::where('status', 'active')
                    ->where('tradeable', true)
                    ->sum('current_value'),

                'average_price' => CarbonCredit::where('status', 'active')
                    ->where('tradeable', true)
                    ->avg('credit_value_per_ton'),

                'total_listings' => CarbonCredit::where('status', 'active')
                    ->where('tradeable', true)
                    ->count(),

                'today_trades' => CarbonCredit::where('status', 'traded')
                    ->whereDate('traded_at', today())
                    ->count(),
            ];
        });
    }

    /**
     * Cache user's carbon credits.
     */
    public function cacheUserCarbonCredits(int $userId): array
    {
        $key = "user:{$userId}:carbon_credits";

        return Cache::remember($key, self::TTL_MEDIUM, function () use ($userId) {
            $credits = CarbonCredit::where('user_id', $userId)
                ->where('status', 'active')
                ->get();

            return [
                'total_credits' => $credits->sum('credit_amount'),
                'total_value' => $credits->sum('current_value'),
                'credits_count' => $credits->count(),
                'credits' => $credits->toArray(),
            ];
        });
    }

    /**
     * Cache product quality history.
     */
    public function cacheProductQualityHistory(int $productId): array
    {
        $key = "food_product:{$productId}:quality_history";

        return Cache::remember($key, self::TTL_LONG, function () use ($productId) {
            $product = FoodProduct::find($productId);

            if (!$product) {
                return [];
            }

            $checkpoints = $product->qualityCheckpoints()
                ->orderBy('checked_at', 'desc')
                ->get();

            return [
                'total_checks' => $checkpoints->count(),
                'passed' => $checkpoints->where('overall_result', 'pass')->count(),
                'failed' => $checkpoints->where('overall_result', 'fail')->count(),
                'average_score' => $checkpoints->avg('pass_score'),
                'recent_checkpoints' => $checkpoints->take(10)->toArray(),
            ];
        });
    }

    /**
     * Cache TPIX price (for gas calculations).
     */
    public function cacheTPIXPrice(): float
    {
        $key = 'tpix:price:thb';

        return Cache::remember($key, self::TTL_SHORT, function () {
            // Get from CryptoCurrency model
            $tpix = \App\Models\CryptoCurrency::where('code', 'TPIX')->first();

            if ($tpix && $tpix->manual_price_thb) {
                return (float) $tpix->manual_price_thb;
            }

            // Fallback to config
            return 3.50; // 1 TPIX = 3.50 THB
        });
    }

    /**
     * Cache blockchain explorer URL for TPIX.
     */
    public function cacheTPIXExplorerUrl(): string
    {
        $key = 'tpix:explorer_url';

        return Cache::remember($key, self::TTL_DAY, function () {
            return config('tpix-blockchain.network.explorer_url', 'https://explorer.tpix.network');
        });
    }

    /**
     * Cache gas price for TPIX transactions.
     */
    public function cacheTPIXGasPrice(): float
    {
        $key = 'tpix:gas_price';

        return Cache::remember($key, self::TTL_SHORT, function () {
            return config('tpix-blockchain.gas.price_in_tpix', 0.0001);
        });
    }

    /**
     * Invalidate product cache.
     */
    public function invalidateProductCache(int $productId, ?string $passportId = null): void
    {
        Cache::forget("food_product:details:{$productId}");
        Cache::forget("food_product:{$productId}:quality_history");

        if ($passportId) {
            Cache::forget("food_product:passport:{$passportId}");
        }

        Log::info('Product cache invalidated', ['product_id' => $productId]);
    }

    /**
     * Invalidate marketplace cache.
     */
    public function invalidateMarketplaceCache(): void
    {
        Cache::forget('carbon_marketplace:data');
        Log::info('Marketplace cache invalidated');
    }

    /**
     * Invalidate user carbon credits cache.
     */
    public function invalidateUserCarbonCache(int $userId): void
    {
        Cache::forget("user:{$userId}:carbon_credits");
        Log::info('User carbon credits cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Cache statistics for dashboard.
     */
    public function cacheDashboardStats(): array
    {
        $key = 'food_passport:dashboard_stats';

        return Cache::remember($key, self::TTL_MEDIUM, function () {
            return [
                'total_products' => FoodProduct::count(),
                'active_products' => FoodProduct::where('quality_status', 'passed')->count(),
                'total_carbon_credits' => CarbonCredit::where('status', 'active')->sum('credit_amount'),
                'total_certifications' => \App\Models\FoodCertification::where('status', 'active')->count(),
                'total_scans_today' => \App\Models\ConsumerScan::whereDate('scanned_at', today())->count(),
            ];
        });
    }

    /**
     * Warm up cache for frequently accessed data.
     */
    public function warmUpCache(): void
    {
        Log::info('Warming up Food Passport cache...');

        // Cache marketplace data
        $this->cacheMarketplaceData();

        // Cache dashboard stats
        $this->cacheDashboardStats();

        // Cache TPIX price
        $this->cacheTPIXPrice();

        // Cache gas price
        $this->cacheTPIXGasPrice();

        Log::info('Food Passport cache warmed up successfully');
    }

    /**
     * Clear all Food Passport caches.
     */
    public function clearAllCache(): void
    {
        $keys = [
            'carbon_marketplace:data',
            'food_passport:dashboard_stats',
            'tpix:price:thb',
            'tpix:gas_price',
            'tpix:explorer_url',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Also clear pattern-based caches
        Cache::tags(['food_passport'])->flush();

        Log::info('All Food Passport caches cleared');
    }
}
