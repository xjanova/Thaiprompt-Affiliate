<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use Exception;

class MarketplaceFactory
{
    /**
     * Create marketplace service instance based on platform
     *
     * @param MarketplaceAccount $account
     * @return MarketplaceApiInterface
     * @throws Exception
     */
    public static function create(MarketplaceAccount $account): MarketplaceApiInterface
    {
        return self::createByPlatform($account->platform, $account);
    }

    /**
     * Create marketplace service by platform
     *
     * @param MarketplacePlatform $platform
     * @param MarketplaceAccount $account
     * @return MarketplaceApiInterface
     * @throws Exception
     */
    public static function createByPlatform(MarketplacePlatform $platform, MarketplaceAccount $account): MarketplaceApiInterface
    {
        return match ($platform->slug) {
            'lazada' => new LazadaApiService($account),
            'tiktok' => new TikTokApiService($account),
            'shopee' => new ShopeeApiService($account),
            default => throw new Exception("Unsupported marketplace platform: {$platform->slug}"),
        };
    }

    /**
     * Get service class name for platform
     *
     * @param string $platformSlug
     * @return string
     * @throws Exception
     */
    public static function getServiceClass(string $platformSlug): string
    {
        return match ($platformSlug) {
            'lazada' => LazadaApiService::class,
            'tiktok' => TikTokApiService::class,
            'shopee' => ShopeeApiService::class,
            default => throw new Exception("Unsupported marketplace platform: {$platformSlug}"),
        };
    }
}
