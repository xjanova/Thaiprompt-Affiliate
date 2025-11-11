<?php

namespace App\Services\AiGen;

use App\Models\AiGenProvider;

class AiGenProviderFactory
{
    /**
     * Create provider instance based on slug.
     */
    public static function create(string $providerSlug): ?BaseAiGenProvider
    {
        $provider = AiGenProvider::where('slug', $providerSlug)
            ->where('is_active', true)
            ->first();

        if (!$provider) {
            return null;
        }

        return self::createFromModel($provider);
    }

    /**
     * Create provider instance from model.
     */
    public static function createFromModel(AiGenProvider $provider): ?BaseAiGenProvider
    {
        $className = self::getProviderClassName($provider->slug);

        if (!class_exists($className)) {
            return null;
        }

        return new $className($provider);
    }

    /**
     * Get provider class name from slug.
     */
    protected static function getProviderClassName(string $slug): string
    {
        // Convert slug to class name (e.g., 'freepik' -> 'FreepikProvider')
        $className = str_replace('-', '', ucwords($slug, '-'));
        return __NAMESPACE__ . '\\' . $className . 'Provider';
    }

    /**
     * Get all available providers.
     */
    public static function getAvailableProviders(): array
    {
        $providers = AiGenProvider::active()->get();
        $available = [];

        foreach ($providers as $provider) {
            $instance = self::createFromModel($provider);
            if ($instance && $instance->isConfigured()) {
                $available[] = [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'slug' => $provider->slug,
                    'type' => $provider->type,
                    'features' => $provider->supported_features,
                ];
            }
        }

        return $available;
    }

    /**
     * Check if a provider is available and configured.
     */
    public static function isAvailable(string $providerSlug): bool
    {
        $instance = self::create($providerSlug);
        return $instance && $instance->isConfigured();
    }
}
