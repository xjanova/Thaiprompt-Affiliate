<?php

namespace App\Services\TPIX;

use App\Models\TPIXToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TokenCacheService
{
    const CACHE_TTL_SHORT = 60; // 1 minute
    const CACHE_TTL_MEDIUM = 300; // 5 minutes
    const CACHE_TTL_LONG = 3600; // 1 hour

    /**
     * Get token with caching
     */
    public function getToken(int $tokenId): ?TPIXToken
    {
        $key = "token:{$tokenId}";

        return Cache::remember($key, self::CACHE_TTL_MEDIUM, function () use ($tokenId) {
            return TPIXToken::with(['creator'])->find($tokenId);
        });
    }

    /**
     * Get token list with caching
     */
    public function getTokenList(array $filters = []): array
    {
        $key = 'token_list:' . md5(json_encode($filters));

        return Cache::remember($key, self::CACHE_TTL_SHORT, function () use ($filters) {
            return TPIXToken::where('status', 'active')
                ->where('is_listed', true)
                ->limit($filters['limit'] ?? 20)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get token price with caching
     */
    public function getTokenPrice(int $tokenId): ?string
    {
        $key = "token_price:{$tokenId}";

        return Cache::remember($key, self::CACHE_TTL_SHORT, function () use ($tokenId) {
            return TPIXToken::find($tokenId)?->current_price_tpix;
        });
    }

    /**
     * Get user portfolio with caching
     */
    public function getUserPortfolio(int $userId): array
    {
        $key = "portfolio:{$userId}";

        return Cache::remember($key, self::CACHE_TTL_SHORT, function () use ($userId) {
            // Calculate portfolio value
            $balances = \App\Models\TPIXTokenBalance::where('user_id', $userId)
                ->with('token')
                ->where('balance', '>', 0)
                ->get();

            $totalValue = 0;
            $tokens = [];

            foreach ($balances as $balance) {
                $tokenValue = bcmul($balance->balance, $balance->token->current_price_tpix ?? '0', 8);
                $totalValue = bcadd($totalValue, $tokenValue, 8);

                $tokens[] = [
                    'token_id' => $balance->token_id,
                    'symbol' => $balance->token->symbol,
                    'balance' => $balance->balance,
                    'value' => $tokenValue,
                ];
            }

            return [
                'total_value' => $totalValue,
                'tokens' => $tokens,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Invalidate token cache
     */
    public function invalidateToken(int $tokenId): void
    {
        Cache::forget("token:{$tokenId}");
        Cache::forget("token_price:{$tokenId}");

        // Also clear list caches
        $this->clearListCaches();
    }

    /**
     * Invalidate user portfolio cache
     */
    public function invalidateUserPortfolio(int $userId): void
    {
        Cache::forget("portfolio:{$userId}");
    }

    /**
     * Clear all token list caches
     */
    public function clearListCaches(): void
    {
        Cache::tags(['token_lists'])->flush();
    }

    /**
     * Warm up cache for popular tokens
     */
    public function warmUpCache(): void
    {
        // Cache top 50 tokens
        $tokens = TPIXToken::where('status', 'active')
            ->orderBy('market_cap', 'desc')
            ->limit(50)
            ->get();

        foreach ($tokens as $token) {
            $this->getToken($token->id);
            $this->getTokenPrice($token->id);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'redis_info' => Redis::info(),
            'cache_size' => Cache::get('cache_size', 0),
        ];
    }
}
