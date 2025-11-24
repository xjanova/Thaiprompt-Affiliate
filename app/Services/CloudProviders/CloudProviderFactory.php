<?php

namespace App\Services\CloudProviders;

use App\Models\AiRentalCloudProvider;
use App\Models\AiRentalCloudConfig;

/**
 * Cloud Provider Factory
 *
 * สร้าง instance ของ Cloud Provider ที่เหมาะสม
 */
class CloudProviderFactory
{
    /**
     * Provider mappings
     *
     * @var array
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
     * @param AiRentalCloudConfig $config
     * @return CloudProviderInterface
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
     * @param AiRentalCloudProvider $cloudProvider
     * @return CloudProviderInterface
     *
     * @throws \Exception
     */
    public static function createFromProvider(AiRentalCloudProvider $cloudProvider): CloudProviderInterface
    {
        $slug = strtolower($cloudProvider->slug);

        // ค้นหา provider class
        if (!isset(self::$providers[$slug])) {
            throw new \Exception("Provider '{$cloudProvider->name}' is not supported yet");
        }

        $providerClass = self::$providers[$slug];

        return new $providerClass();
    }

    /**
     * เช็คว่า Provider รองรับหรือไม่
     *
     * @param string $slug
     * @return bool
     */
    public static function isSupported(string $slug): bool
    {
        return isset(self::$providers[strtolower($slug)]);
    }

    /**
     * ดึงรายการ Providers ที่รองรับทั้งหมด
     *
     * @return array
     */
    public static function getSupportedProviders(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Register provider ใหม่
     *
     * @param string $slug
     * @param string $class
     * @return void
     */
    public static function register(string $slug, string $class): void
    {
        self::$providers[strtolower($slug)] = $class;
    }
}
