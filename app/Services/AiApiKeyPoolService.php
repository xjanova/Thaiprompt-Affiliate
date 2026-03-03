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
 * - smart: ⭐ Smart Load Balancing — กระจาย load อัจฉริยะ
 *          ติดตาม in-flight requests + requests/นาที ต่อ key
 *          เลือก key ที่มีภาระน้อยสุดเสมอ ไม่ซ้ำ key ที่กำลังถูกใช้
 */
class AiApiKeyPoolService
{
    /**
     * Cache prefix สำหรับ round robin index
     */
    private const CACHE_PREFIX = 'ai_api_key_pool_';

    /**
     * Cache prefix สำหรับ Smart Load Balancing
     */
    private const INFLIGHT_PREFIX = 'pool:inflight:';  // {provider}:{key_id} — TTL 30s

    private const RPM_PREFIX = 'pool:rpm:';            // {provider}:{key_id} — TTL 60s

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
            'smart' => $this->getSmartKey($provider),
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

    /**
     * ⭐ Smart Load Balancing: เลือก key ที่ภาระน้อยสุด
     *
     * คำนวณ load score ต่อ key:
     * - in_flight × 100  (key กำลังถูกใช้ = หลีกเลี่ยง)
     * - rpm × 10          (ใช้บ่อย/นาที = กระจายออก)
     * - errors × 50       (error เยอะ = หลีกเลี่ยง)
     *
     * เลือก key score ต่ำสุด → ถ้าเท่ากัน เลือกตัวที่ไม่ได้ใช้นานสุด
     */
    protected function getSmartKey(string $provider): ?AiApiKey
    {
        $keys = AiApiKey::forProvider($provider)
            ->available()
            ->get();

        if ($keys->isEmpty()) {
            return null;
        }

        // ⭐ กรอง keys ที่เกิน rate_limit_per_minute (ถ้ากำหนดไว้)
        $eligibleKeys = $keys->filter(function ($key) use ($provider) {
            if ($key->rate_limit_per_minute) {
                $rpm = $this->getKeyRpm($provider, $key->id);
                if ($rpm >= $key->rate_limit_per_minute) {
                    return false; // key นี้เต็มโควต้า/นาที
                }
            }

            return true;
        });

        // ถ้าทุก key เกิน limit → fallback ใช้ key ที่ rpm น้อยสุด
        if ($eligibleKeys->isEmpty()) {
            $eligibleKeys = $keys;
        }

        // คำนวณ load score และเลือก key ที่ดีที่สุด
        $bestKey = null;
        $bestScore = PHP_INT_MAX;
        $bestLastUsed = now(); // ใหม่สุด = ไม่ดี

        foreach ($eligibleKeys as $key) {
            $score = $this->getKeyLoadScore($provider, $key);

            // เลือก score ต่ำสุด, ถ้าเท่ากัน → เลือกตัวที่ไม่ได้ใช้นานสุด
            if ($score < $bestScore
                || ($score === $bestScore && ($key->last_used_at ?? now()->subYear()) < $bestLastUsed)) {
                $bestKey = $key;
                $bestScore = $score;
                $bestLastUsed = $key->last_used_at ?? now()->subYear();
            }
        }

        if ($bestKey) {
            $this->setLastUsedKey($provider, $bestKey);

            Log::debug('AI Pool Smart: เลือก key', [
                'key_id' => $bestKey->id,
                'key_name' => $bestKey->name,
                'score' => $bestScore,
                'inflight' => $this->getKeyInflight($provider, $bestKey->id),
                'rpm' => $this->getKeyRpm($provider, $bestKey->id),
            ]);
        }

        return $bestKey;
    }

    /**
     * คำนวณ load score ของ key (ยิ่งต่ำยิ่งดี)
     */
    protected function getKeyLoadScore(string $provider, AiApiKey $key): int
    {
        $inflight = $this->getKeyInflight($provider, $key->id);
        $rpm = $this->getKeyRpm($provider, $key->id);
        $errors = $key->consecutive_errors ?? 0;

        return ($inflight * 100) + ($rpm * 10) + ($errors * 50);
    }

    // ============================================================
    // ⭐ Smart Key Acquire / Release (In-Flight Tracking)
    // ============================================================

    /**
     * จอง key พร้อม in-flight tracking (ใช้แทน getKey สำหรับ smart mode)
     *
     * ❶ เลือก key ตาม rotation mode (รวม smart)
     * ❷ Increment in-flight counter (atomic)
     * ❸ คืน key — caller ต้องเรียก releaseKey() เมื่อเสร็จ!
     *
     * @param  string  $provider  ชื่อ provider
     * @return AiApiKey|null
     */
    public function acquireKey(string $provider): ?AiApiKey
    {
        $key = $this->getKey($provider);

        if ($key) {
            // ✅ Increment in-flight counter (TTL 30s ป้องกัน leaked)
            $inflightKey = self::INFLIGHT_PREFIX."{$provider}:{$key->id}";
            if (Cache::has($inflightKey)) {
                Cache::increment($inflightKey);
            } else {
                Cache::put($inflightKey, 1, 30);
            }
        }

        return $key;
    }

    /**
     * คืน key กลับ pool (ลด in-flight + บันทึก rpm)
     *
     * เรียกเมื่อ AI call เสร็จ (สำเร็จหรือล้มเหลว) — ควรอยู่ใน finally block
     *
     * @param  string  $provider  ชื่อ provider
     * @param  int|null  $keyId  ID ของ key ที่จะคืน (null = ใช้ last used)
     */
    public function releaseKey(string $provider, ?int $keyId = null): void
    {
        if (! $keyId) {
            $lastKey = $this->getLastUsedKey($provider);
            $keyId = $lastKey?->id;
        }

        if (! $keyId) {
            return;
        }

        // ✅ Decrement in-flight counter
        $inflightKey = self::INFLIGHT_PREFIX."{$provider}:{$keyId}";
        $current = (int) Cache::get($inflightKey, 0);
        if ($current > 1) {
            Cache::decrement($inflightKey);
        } else {
            Cache::forget($inflightKey);
        }

        // ✅ Increment requests per minute counter (TTL 60s)
        $rpmKey = self::RPM_PREFIX."{$provider}:{$keyId}";
        if (Cache::has($rpmKey)) {
            Cache::increment($rpmKey);
        } else {
            Cache::put($rpmKey, 1, 60);
        }
    }

    /**
     * ดึงจำนวน in-flight requests ของ key
     */
    public function getKeyInflight(string $provider, int $keyId): int
    {
        return (int) Cache::get(self::INFLIGHT_PREFIX."{$provider}:{$keyId}", 0);
    }

    /**
     * ดึงจำนวน requests per minute ของ key
     */
    public function getKeyRpm(string $provider, int $keyId): int
    {
        return (int) Cache::get(self::RPM_PREFIX."{$provider}:{$keyId}", 0);
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

        return $keys->map(function ($key) use ($provider) {
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
                // ⭐ Smart Load Balancing stats (real-time)
                'inflight' => $this->getKeyInflight($provider, $key->id),
                'rpm' => $this->getKeyRpm($provider, $key->id),
                'load_score' => $this->getKeyLoadScore($provider, $key),
                'rate_limit_per_minute' => $key->rate_limit_per_minute,
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
