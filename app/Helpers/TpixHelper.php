<?php

namespace App\Helpers;

use App\Services\FoodPassportCacheService;
use Illuminate\Support\Facades\Cache;

/**
 * TPIX Helper Functions
 *
 * Utility functions for TPIX blockchain integration
 */
class TpixHelper
{
    /**
     * Format TPIX amount to readable string.
     */
    public static function formatTPIX(float $amount, int $decimals = 4): string
    {
        return number_format($amount, $decimals) . ' TPIX';
    }

    /**
     * Convert Wei to TPIX (18 decimals).
     */
    public static function weiToTPIX(string $wei): float
    {
        return (float) bcdiv($wei, bcpow('10', '18'), 18);
    }

    /**
     * Convert TPIX to Wei (18 decimals).
     */
    public static function tpixToWei(float $tpix): string
    {
        return bcmul((string) $tpix, bcpow('10', '18'), 0);
    }

    /**
     * Get current TPIX price in THB.
     */
    public static function getTPIXPrice(): float
    {
        $cacheService = app(FoodPassportCacheService::class);
        return $cacheService->cacheTPIXPrice();
    }

    /**
     * Convert TPIX to THB.
     */
    public static function tpixToTHB(float $tpix): float
    {
        $price = self::getTPIXPrice();
        return $tpix * $price;
    }

    /**
     * Convert THB to TPIX.
     */
    public static function thbToTPIX(float $thb): float
    {
        $price = self::getTPIXPrice();
        return $price > 0 ? $thb / $price : 0;
    }

    /**
     * Calculate gas fee in TPIX.
     */
    public static function calculateGasFee(int $gasLimit, ?float $gasPrice = null): float
    {
        $cacheService = app(FoodPassportCacheService::class);
        $gasPrice = $gasPrice ?? $cacheService->cacheTPIXGasPrice();

        return $gasLimit * $gasPrice;
    }

    /**
     * Calculate gas fee in THB.
     */
    public static function calculateGasFeeInTHB(int $gasLimit, ?float $gasPrice = null): float
    {
        $gasFeeTPIX = self::calculateGasFee($gasLimit, $gasPrice);
        return self::tpixToTHB($gasFeeTPIX);
    }

    /**
     * Get TPIX explorer URL for transaction.
     */
    public static function getTransactionUrl(string $txHash): string
    {
        $explorerUrl = config('tpix-blockchain.network.explorer_url');
        return "{$explorerUrl}/tx/{$txHash}";
    }

    /**
     * Get TPIX explorer URL for address.
     */
    public static function getAddressUrl(string $address): string
    {
        $explorerUrl = config('tpix-blockchain.network.explorer_url');
        return "{$explorerUrl}/address/{$address}";
    }

    /**
     * Get TPIX explorer URL for NFT.
     */
    public static function getNFTUrl(string $contractAddress, int $tokenId): string
    {
        $explorerUrl = config('tpix-blockchain.network.explorer_url');
        return "{$explorerUrl}/token/{$contractAddress}?a={$tokenId}";
    }

    /**
     * Validate TPIX wallet address.
     */
    public static function isValidAddress(string $address): bool
    {
        // Check if it's a valid Ethereum-style address (TPIX uses same format)
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Shorten address for display (0x1234...5678).
     */
    public static function shortenAddress(string $address, int $prefixLength = 6, int $suffixLength = 4): string
    {
        if (!self::isValidAddress($address)) {
            return $address;
        }

        $prefix = substr($address, 0, $prefixLength);
        $suffix = substr($address, -$suffixLength);

        return "{$prefix}...{$suffix}";
    }

    /**
     * Get gas limit for specific operation.
     */
    public static function getGasLimit(string $operation): int
    {
        $limits = config('tpix-blockchain.gas.limits', []);
        return $limits[$operation] ?? config('tpix-blockchain.gas.default_limit', 250000);
    }

    /**
     * Check if gas subsidy is enabled.
     */
    public static function isGasSubsidyEnabled(): bool
    {
        return config('tpix-blockchain.wallet.gas_subsidy_enabled', true);
    }

    /**
     * Get gas subsidy limit per user per day.
     */
    public static function getGasSubsidyLimit(): float
    {
        return (float) config('tpix-blockchain.wallet.subsidy_limit_per_user_daily', 100);
    }

    /**
     * Check if user has reached gas subsidy limit today.
     */
    public static function hasReachedGasSubsidyLimit(int $userId): bool
    {
        $key = "user:{$userId}:gas_subsidy:" . date('Y-m-d');
        $usedToday = Cache::get($key, 0);
        $limit = self::getGasSubsidyLimit();

        return $usedToday >= $limit;
    }

    /**
     * Record gas subsidy usage.
     */
    public static function recordGasSubsidyUsage(int $userId, float $amount): void
    {
        $key = "user:{$userId}:gas_subsidy:" . date('Y-m-d');
        $current = Cache::get($key, 0);
        $new = $current + $amount;

        // Cache until end of day
        $expiresAt = now()->endOfDay();
        Cache::put($key, $new, $expiresAt);
    }

    /**
     * Get remaining gas subsidy for today.
     */
    public static function getRemainingGasSubsidy(int $userId): float
    {
        $key = "user:{$userId}:gas_subsidy:" . date('Y-m-d');
        $usedToday = Cache::get($key, 0);
        $limit = self::getGasSubsidyLimit();

        return max(0, $limit - $usedToday);
    }

    /**
     * Get TPIX chain ID.
     */
    public static function getChainId(): int
    {
        return (int) config('tpix-blockchain.network.chain_id', 88888);
    }

    /**
     * Get TPIX network name.
     */
    public static function getNetworkName(): string
    {
        return config('tpix-blockchain.network.name', 'TPIX Chain');
    }

    /**
     * Get RPC URL.
     */
    public static function getRPCUrl(): string
    {
        return config('tpix-blockchain.network.rpc_url');
    }

    /**
     * Format carbon credit amount (TPCC).
     */
    public static function formatTPCC(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals) . ' TPCC';
    }

    /**
     * Get environmental impact of carbon credits.
     */
    public static function getEnvironmentalImpact(float $tpccAmount): array
    {
        // 1 TPCC = 1 ton CO2 equivalent
        $co2Tonnes = $tpccAmount;

        return [
            'co2_offset_tonnes' => round($co2Tonnes, 2),
            'trees_equivalent' => round($co2Tonnes * 50), // ~50 trees per tonne CO2/year
            'cars_off_road_days' => round($co2Tonnes * 250), // ~250 days per tonne
            'homes_powered_days' => round($co2Tonnes * 100), // ~100 days per tonne
        ];
    }
}
