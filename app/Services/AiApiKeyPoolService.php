<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\AiApiKeySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI API Key Pool Service
 *
 * จัดการ Pool ของ API Keys สำหรับ AI Providers
 *
 * รองรับโหมดการวนใช้:
 * - round_robin: วนตามลำดับ
 * - least_used: ใช้ key ที่ใช้น้อยสุดก่อน
 * - priority: ตาม priority สูง → ต่ำ
 * - random: สุ่มเลือก
 * - failover: ใช้ตัวหลัก, สำรองเมื่อ error
 */
class AiApiKeyPoolService
{
    /**
     * Cache prefix สำหรับ round robin index
     */
    private const CACHE_PREFIX = 'ai_api_key_pool_';

    /**
     * ดึง API Key ที่พร้อมใช้งานสำหรับ provider
     *
     * @param  string  $provider  ชื่อ provider (grok, openai, etc.)
     */
    public function getKey(string $provider): ?AiApiKey
    {
        $settings = AiApiKeySetting::forProvider($provider);
        $mode = $settings->rotation_mode;

        $key = match ($mode) {
            'round_robin' => $this->getRoundRobinKey($provider),
            'least_used' => $this->getLeastUsedKey($provider),
            'priority' => $this->getPriorityKey($provider),
            'random' => $this->getRandomKey($provider),
            'failover' => $this->getFailoverKey($provider),
            default => $this->getRoundRobinKey($provider),
        };

        if ($key) {
            Log::debug('AI API Key Pool: เลือก key', [
                'provider' => $provider,
                'mode' => $mode,
                'key_id' => $key->id,
                'key_name' => $key->name,
            ]);
        } else {
            Log::warning('AI API Key Pool: ไม่พบ key ที่พร้อมใช้งาน', [
                'provider' => $provider,
                'mode' => $mode,
            ]);
        }

        return $key;
    }

    /**
     * ดึง API Key string สำหรับ provider
     */
    public function getApiKey(string $provider): ?string
    {
        $key = $this->getKey($provider);

        return $key?->api_key;
    }

    /**
     * บันทึกการใช้งาน tokens
     */
    public function recordUsage(
        string $provider,
        int $inputTokens,
        int $outputTokens,
        ?string $model = null,
        ?int $responseTimeMs = null,
        string $requestType = 'general'
    ): void {
        // ดึง key ที่เพิ่งใช้ (จาก cache หรือล่าสุด)
        $key = $this->getLastUsedKey($provider);

        if ($key) {
            $key->recordUsage($inputTokens, $outputTokens, $model, $responseTimeMs, $requestType);

            Log::debug('AI API Key Pool: บันทึกการใช้งาน', [
                'provider' => $provider,
                'key_id' => $key->id,
                'total_tokens' => $inputTokens + $outputTokens,
            ]);
        }
    }

    /**
     * บันทึก error
     */
    public function recordError(string $provider, string $errorMessage, ?string $model = null): void
    {
        $key = $this->getLastUsedKey($provider);

        if ($key) {
            $key->recordError($errorMessage, $model);

            Log::warning('AI API Key Pool: บันทึก error', [
                'provider' => $provider,
                'key_id' => $key->id,
                'error' => $errorMessage,
                'consecutive_errors' => $key->fresh()->consecutive_errors,
            ]);
        }
    }

    // ============================================================
    // Rotation Modes
    // ============================================================

    /**
     * Round Robin: วนตามลำดับ
     */
    protected function getRoundRobinKey(string $provider): ?AiApiKey
    {
        $keys = AiApiKey::forProvider($provider)
            ->available()
            ->orderBy('id')
            ->get();

        if ($keys->isEmpty()) {
            return null;
        }

        // ดึง index ปัจจุบันจาก cache
        $cacheKey = self::CACHE_PREFIX."rr_index_{$provider}";
        $currentIndex = Cache::get($cacheKey, 0);

        // วน index
        $key = $keys[$currentIndex % $keys->count()];

        // อัพเดท index สำหรับรอบถัดไป
        Cache::put($cacheKey, ($currentIndex + 1) % $keys->count(), now()->addDay());

        // เก็บ key ที่ใช้ล่าสุด
        $this->setLastUsedKey($provider, $key);

        return $key;
    }

    /**
     * Least Used: ใช้ key ที่ใช้น้อยสุดก่อน
     */
    protected function getLeastUsedKey(string $provider): ?AiApiKey
    {
        $key = AiApiKey::forProvider($provider)
            ->available()
            ->orderBy('tokens_used_today')
            ->first();

        if ($key) {
            $this->setLastUsedKey($provider, $key);
        }

        return $key;
    }

    /**
     * Priority: ตาม priority สูง → ต่ำ
     */
    protected function getPriorityKey(string $provider): ?AiApiKey
    {
        $key = AiApiKey::forProvider($provider)
            ->available()
            ->orderByDesc('priority')
            ->first();

        if ($key) {
            $this->setLastUsedKey($provider, $key);
        }

        return $key;
    }

    /**
     * Random: สุ่มเลือก
     */
    protected function getRandomKey(string $provider): ?AiApiKey
    {
        $key = AiApiKey::forProvider($provider)
            ->available()
            ->inRandomOrder()
            ->first();

        if ($key) {
            $this->setLastUsedKey($provider, $key);
        }

        return $key;
    }

    /**
     * Failover: ใช้ตัวหลัก (priority สูงสุด), สำรองเมื่อ error
     */
    protected function getFailoverKey(string $provider): ?AiApiKey
    {
        // พยายามใช้ key ที่ priority สูงสุดก่อน
        $key = AiApiKey::forProvider($provider)
            ->available()
            ->orderByDesc('priority')
            ->first();

        if ($key) {
            $this->setLastUsedKey($provider, $key);
        }

        return $key;
    }

    // ============================================================
    // Cache Management
    // ============================================================

    /**
     * เก็บ key ที่ใช้ล่าสุดใน cache
     */
    protected function setLastUsedKey(string $provider, AiApiKey $key): void
    {
        $cacheKey = self::CACHE_PREFIX."last_used_{$provider}";
        Cache::put($cacheKey, $key->id, now()->addMinutes(30));
    }

    /**
     * ดึง key ที่ใช้ล่าสุดจาก cache
     */
    protected function getLastUsedKey(string $provider): ?AiApiKey
    {
        $cacheKey = self::CACHE_PREFIX."last_used_{$provider}";
        $keyId = Cache::get($cacheKey);

        if ($keyId) {
            return AiApiKey::find($keyId);
        }

        // ถ้าไม่มีใน cache ให้ดึงจากที่ใช้ล่าสุด
        return AiApiKey::forProvider($provider)
            ->whereNotNull('last_used_at')
            ->orderByDesc('last_used_at')
            ->first();
    }

    // ============================================================
    // Statistics
    // ============================================================

    /**
     * ดึงสถิติรวมของ provider
     */
    public function getProviderStats(string $provider): array
    {
        return AiApiKey::getProviderStats($provider);
    }

    /**
     * ดึงสถิติรวมทุก providers
     */
    public function getAllProvidersStats(): array
    {
        $stats = [];

        foreach (AiApiKey::PROVIDERS as $provider => $name) {
            $providerStats = $this->getProviderStats($provider);

            // เฉพาะ provider ที่มี keys
            if ($providerStats['total_keys'] > 0) {
                $stats[$provider] = array_merge(
                    ['name' => $name],
                    $providerStats
                );
            }
        }

        return $stats;
    }

    /**
     * ดึง keys ทั้งหมดของ provider พร้อมสถิติ
     */
    public function getKeysWithStats(string $provider): array
    {
        $keys = AiApiKey::forProvider($provider)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        return $keys->map(function ($key) {
            return [
                'id' => $key->id,
                'name' => $key->name,
                'masked_key' => $key->masked_key,
                'is_active' => $key->is_active,
                'is_available' => $key->isAvailable(),
                'priority' => $key->priority,
                'tokens_used_today' => $key->tokens_used_today,
                'tokens_used_month' => $key->tokens_used_month,
                'tokens_used_total' => $key->tokens_used_total,
                'tokens_limit_daily' => $key->tokens_limit_daily,
                'tokens_limit_monthly' => $key->tokens_limit_monthly,
                'daily_usage_percent' => $key->daily_usage_percent,
                'monthly_usage_percent' => $key->monthly_usage_percent,
                'daily_tokens_remaining' => $key->daily_tokens_remaining,
                'monthly_tokens_remaining' => $key->monthly_tokens_remaining,
                'requests_today' => $key->requests_today,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
                'consecutive_errors' => $key->consecutive_errors,
                'last_error' => $key->last_error,
                'last_error_at' => $key->last_error_at?->diffForHumans(),
                'disabled_until' => $key->disabled_until?->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * ดึง dashboard data สำหรับแสดงผล
     */
    public function getDashboardData(): array
    {
        $allStats = $this->getAllProvidersStats();

        // สรุปรวม
        $totalKeys = 0;
        $activeKeys = 0;
        $availableKeys = 0;
        $totalTokensToday = 0;
        $totalTokensMonth = 0;
        $totalRequestsToday = 0;

        foreach ($allStats as $provider => $stats) {
            $totalKeys += $stats['total_keys'];
            $activeKeys += $stats['active_keys'];
            $availableKeys += $stats['available_keys'];
            $totalTokensToday += $stats['tokens_used_today'];
            $totalTokensMonth += $stats['tokens_used_month'];
            $totalRequestsToday += $stats['requests_today'];
        }

        return [
            'summary' => [
                'total_keys' => $totalKeys,
                'active_keys' => $activeKeys,
                'available_keys' => $availableKeys,
                'tokens_used_today' => $totalTokensToday,
                'tokens_used_month' => $totalTokensMonth,
                'requests_today' => $totalRequestsToday,
            ],
            'providers' => $allStats,
            'rotation_modes' => AiApiKey::ROTATION_MODES,
        ];
    }

    // ============================================================
    // Management
    // ============================================================

    /**
     * เพิ่ม API Key ใหม่
     */
    public function addKey(array $data): AiApiKey
    {
        return AiApiKey::create($data);
    }

    /**
     * อัพเดท API Key
     */
    public function updateKey(int $keyId, array $data): AiApiKey
    {
        $key = AiApiKey::findOrFail($keyId);
        $key->update($data);

        return $key->fresh();
    }

    /**
     * ลบ API Key
     */
    public function deleteKey(int $keyId): bool
    {
        $key = AiApiKey::findOrFail($keyId);

        return $key->delete();
    }

    /**
     * Enable/Disable key
     */
    public function toggleKey(int $keyId): AiApiKey
    {
        $key = AiApiKey::findOrFail($keyId);

        if ($key->is_active) {
            $key->disable();
        } else {
            $key->enable();
        }

        return $key->fresh();
    }

    /**
     * อัพเดท settings ของ provider
     */
    public function updateProviderSettings(string $provider, array $settings): AiApiKeySetting
    {
        return AiApiKeySetting::updateForProvider($provider, $settings);
    }

    /**
     * Reset counters ของทุก keys (สำหรับ daily/monthly reset)
     */
    public function resetDailyCounters(): int
    {
        return AiApiKey::query()->update([
            'tokens_used_today' => 0,
            'requests_today' => 0,
            'requests_minute' => 0,
            'tokens_reset_date' => now()->toDateString(),
        ]);
    }

    /**
     * Reset monthly counters
     */
    public function resetMonthlyCounters(): int
    {
        return AiApiKey::query()->update([
            'tokens_used_month' => 0,
        ]);
    }
}
