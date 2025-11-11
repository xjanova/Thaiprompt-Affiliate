<?php

namespace App\Services\AiGen;

use App\Models\AiGenProvider;
use Illuminate\Support\Facades\Http;

abstract class BaseAiGenProvider
{
    protected AiGenProvider $provider;
    protected array $config = [];

    public function __construct(AiGenProvider $provider)
    {
        $this->provider = $provider;
        $this->loadConfig();
    }

    /**
     * Load provider configuration.
     */
    protected function loadConfig(): void
    {
        $configs = $this->provider->configs;
        foreach ($configs as $config) {
            $this->config[$config->config_key] = $config->is_encrypted
                ? $config->decrypted_value
                : $config->config_value;
        }
    }

    /**
     * Get config value.
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Generate image.
     */
    abstract public function generateImage(string $prompt, array $parameters = []): array;

    /**
     * Generate video.
     */
    abstract public function generateVideo(string $prompt, array $parameters = []): array;

    /**
     * Check generation status.
     */
    abstract public function checkStatus(string $generationId): array;

    /**
     * Get result by ID.
     */
    abstract public function getResult(string $generationId): array;

    /**
     * Validate provider is properly configured.
     */
    abstract public function isConfigured(): bool;

    /**
     * Get supported features.
     */
    public function getSupportedFeatures(): array
    {
        return $this->provider->supported_features ?? [];
    }

    /**
     * Check if provider supports a feature.
     */
    public function supports(string $feature): bool
    {
        return $this->provider->supportsFeature($feature);
    }

    /**
     * Make HTTP request with error handling.
     */
    protected function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->{$method}($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 0,
            ];
        }
    }

    /**
     * Parse error response.
     */
    protected function parseError($response): string
    {
        if (is_array($response) && isset($response['error'])) {
            if (is_string($response['error'])) {
                return $response['error'];
            }
            if (is_array($response['error']) && isset($response['error']['message'])) {
                return $response['error']['message'];
            }
        }
        return 'Unknown error occurred';
    }
}
