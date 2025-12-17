<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PostXAgent Health Check Service
 *
 * Service สำหรับตรวจสอบสถานะการเชื่อมต่อกับ PostXAgent AI Manager
 * รองรับ caching, real-time status, และ provider availability check
 *
 * Features:
 * - ตรวจสอบว่า PostXAgent online หรือไม่
 * - ตรวจสอบ AI providers ที่พร้อมใช้งาน
 * - ตรวจสอบ models ที่พร้อมใช้งาน
 * - Cache status เพื่อลด overhead
 */
class PostXAgentHealthCheck
{
    /**
     * PostXAgent API URL
     */
    protected string $apiUrl;

    /**
     * Cache TTL (seconds)
     */
    protected int $cacheTtl;

    /**
     * สร้าง instance
     */
    public function __construct()
    {
        $this->apiUrl = config('postxagent.api_url', 'http://localhost:8000/api/v1');
        $this->cacheTtl = config('postxagent.health_check.cache_ttl', 30);
    }

    /**
     * ตรวจสอบว่า PostXAgent พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!config('postxagent.enabled', false)) {
            return false;
        }

        $status = $this->getStatus();

        return $status['online'] ?? false;
    }

    /**
     * ดึงสถานะ PostXAgent (with caching)
     *
     * @param bool $fresh ถ้า true จะไม่ใช้ cache
     * @return array
     */
    public function getStatus(bool $fresh = false): array
    {
        $cacheKey = 'postxagent_health_status';

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->fetchStatus();
        });
    }

    /**
     * Fetch status จาก PostXAgent API โดยตรง
     *
     * @return array
     */
    protected function fetchStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders($this->getHeaders())
                ->get($this->apiUrl . '/status');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'online' => true,
                    'version' => $data['version'] ?? 'unknown',
                    'uptime' => $data['uptime'] ?? null,
                    'active_providers' => $data['active_providers'] ?? [],
                    'total_requests' => $data['total_requests'] ?? 0,
                    'last_request_at' => $data['last_request_at'] ?? null,
                    'memory_usage' => $data['memory_usage'] ?? null,
                    'cpu_usage' => $data['cpu_usage'] ?? null,
                    'checked_at' => now()->toDateTimeString(),
                ];
            }

            return $this->offlineStatus('HTTP Error: ' . $response->status());
        } catch (\Exception $e) {
            return $this->offlineStatus($e->getMessage());
        }
    }

    /**
     * สร้าง offline status response
     *
     * @param string $error
     * @return array
     */
    protected function offlineStatus(string $error): array
    {
        return [
            'online' => false,
            'error' => $error,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * ดึง available AI providers จาก PostXAgent
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        $cacheKey = 'postxagent_providers';

        return Cache::remember($cacheKey, $this->cacheTtl * 2, function () {
            try {
                $response = Http::timeout(10)
                    ->withHeaders($this->getHeaders())
                    ->get($this->apiUrl . '/ai/providers');

                if ($response->successful()) {
                    $providers = $response->json()['providers'] ?? [];

                    // เพิ่ม availability status
                    return array_map(function ($provider) {
                        return array_merge($provider, [
                            'is_available' => $this->checkProviderAvailability($provider['id'] ?? $provider['name']),
                        ]);
                    }, $providers);
                }

                return $this->getFallbackProviders();
            } catch (\Exception $e) {
                Log::warning('PostXAgent: Cannot fetch providers', ['error' => $e->getMessage()]);

                return $this->getFallbackProviders();
            }
        });
    }

    /**
     * ตรวจสอบว่า provider พร้อมใช้งานหรือไม่
     *
     * @param string $providerId
     * @return bool
     */
    public function checkProviderAvailability(string $providerId): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders($this->getHeaders())
                ->get($this->apiUrl . '/ai/providers/' . $providerId . '/status');

            if ($response->successful()) {
                return $response->json()['available'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ดึง available models สำหรับ provider
     *
     * @param string $providerId
     * @return array
     */
    public function getAvailableModels(string $providerId): array
    {
        $cacheKey = "postxagent_models_{$providerId}";

        return Cache::remember($cacheKey, $this->cacheTtl * 2, function () use ($providerId) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders($this->getHeaders())
                    ->get($this->apiUrl . '/ai/providers/' . $providerId . '/models');

                if ($response->successful()) {
                    return $response->json()['models'] ?? [];
                }

                return $this->getFallbackModels($providerId);
            } catch (\Exception $e) {
                Log::warning('PostXAgent: Cannot fetch models', [
                    'provider' => $providerId,
                    'error' => $e->getMessage(),
                ]);

                return $this->getFallbackModels($providerId);
            }
        });
    }

    /**
     * ดึง ready-to-use providers และ models
     *
     * @return array
     */
    public function getReadyProviders(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $providers = $this->getAvailableProviders();
        $readyProviders = [];

        foreach ($providers as $provider) {
            if ($provider['is_available'] ?? false) {
                $models = $this->getAvailableModels($provider['id'] ?? $provider['name']);
                $activeModels = array_filter($models, fn($m) => ($m['is_active'] ?? true));

                if (!empty($activeModels)) {
                    $readyProviders[] = [
                        'provider' => $provider,
                        'models' => array_values($activeModels),
                    ];
                }
            }
        }

        return $readyProviders;
    }

    /**
     * เลือก provider และ model ที่ดีที่สุดที่พร้อมใช้งาน
     *
     * Priority:
     * 1. Ollama (local, free)
     * 2. DeepSeek (cheap)
     * 3. GPT-3.5 (reliable)
     * 4. Claude Haiku (fast)
     *
     * @return array|null
     */
    public function selectBestAvailableProvider(): ?array
    {
        $readyProviders = $this->getReadyProviders();

        if (empty($readyProviders)) {
            return null;
        }

        // Priority order
        $priorityOrder = ['ollama', 'deepseek', 'openai', 'anthropic', 'gemini'];

        foreach ($priorityOrder as $preferred) {
            foreach ($readyProviders as $ready) {
                $providerName = strtolower($ready['provider']['name'] ?? '');

                if (str_contains($providerName, $preferred)) {
                    // เลือก model แรกที่ available
                    $model = $ready['models'][0] ?? null;

                    if ($model) {
                        return [
                            'provider' => $ready['provider'],
                            'model' => $model,
                        ];
                    }
                }
            }
        }

        // ถ้าไม่ตรงกับ priority ใช้ตัวแรก
        $first = $readyProviders[0] ?? null;

        if ($first && !empty($first['models'])) {
            return [
                'provider' => $first['provider'],
                'model' => $first['models'][0],
            ];
        }

        return null;
    }

    /**
     * Clear all PostXAgent related cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('postxagent_health_status');
        Cache::forget('postxagent_providers');

        // Clear model caches
        $providers = config('postxagent.available_providers', []);
        foreach (array_keys($providers) as $providerId) {
            Cache::forget("postxagent_models_{$providerId}");
        }
    }

    /**
     * Fallback providers จาก config
     *
     * @return array
     */
    protected function getFallbackProviders(): array
    {
        $configProviders = config('postxagent.available_providers', []);
        $providers = [];

        foreach ($configProviders as $id => $data) {
            $providers[] = [
                'id' => $id,
                'name' => $data['name'] ?? $id,
                'description' => $data['description'] ?? '',
                'is_free' => $data['is_free'] ?? false,
                'requires_api_key' => $data['requires_api_key'] ?? true,
                'is_available' => false, // ไม่รู้สถานะจริง
            ];
        }

        return $providers;
    }

    /**
     * Fallback models จาก config
     *
     * @param string $providerId
     * @return array
     */
    protected function getFallbackModels(string $providerId): array
    {
        $providers = config('postxagent.available_providers', []);
        $models = $providers[$providerId]['models'] ?? [];

        return array_map(function ($modelId) {
            return [
                'id' => $modelId,
                'name' => $modelId,
                'is_active' => true,
            ];
        }, $models);
    }

    /**
     * สร้าง headers
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Source' => 'thaiprompt-affiliate',
        ];

        $authConfig = config('postxagent.auth', []);

        if (!empty($authConfig['api_key'])) {
            $headers['X-API-Key'] = $authConfig['api_key'];
        }

        return $headers;
    }
}
