<?php

namespace App\Services\CloudProviders;

use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalCloudProvider;

/**
 * Cloud Provider Factory
 *
 * สร้าง instance ของ Cloud Provider ที่เหมาะสม
 */
class CloudProviderFactory
{
    /**
     * Provider mappings
     */
    protected static array $providers = [
        'runpod' => RunPodProvider::class,
        'vast-ai' => VastAiProvider::class,
        'vast.ai' => VastAiProvider::class,
        // เพิ่ม providers ใหม่ที่นี่
    ];

    /**
     * สร้าง Provider instance จาก Config
     *
     *
     * @throws \Exception
     */
    public static function create(AiRentalCloudConfig $config): CloudProviderInterface
    {
        $cloudProvider = $config->cloudProvider;

        return self::createFromProvider($cloudProvider);
    }

    /**
     * สร้าง Provider instance จาก CloudProvider model
     *
     *
     * @throws \Exception
     */
    public static function createFromProvider(AiRentalCloudProvider $cloudProvider): CloudProviderInterface
    {
        $slug = strtolower($cloudProvider->slug);

        // ค้นหา provider class
        if (! isset(self::$providers[$slug])) {
            throw new \Exception("Provider '{$cloudProvider->name}' is not supported yet");
        }

        $providerClass = self::$providers[$slug];

        return new $providerClass;
    }

    /**
     * เช็คว่า Provider รองรับหรือไม่
     */
    public static function isSupported(string $slug): bool
    {
        return isset(self::$providers[strtolower($slug)]);
    }

    /**
     * ดึงรายการ Providers ที่รองรับทั้งหมด
     */
    public static function getSupportedProviders(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Register provider ใหม่
     */
    public static function register(string $slug, string $class): void
    {
        self::$providers[strtolower($slug)] = $class;
    }
}
