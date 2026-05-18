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
     * 🆕 (2026-05-07) รองรับ $purpose filter — เลือกเฉพาะ key ที่ตรง purpose
     *   เดิม: $purpose ไม่มี → ทุก rotation mode return key ทั่วไป (ละเลย purpose enum)
     *   ใหม่: $purpose='prediction'/'chat'/'free_card' → filter ผ่าน scopeForPurpose
     *         hierarchy: free_card → free_card+prediction+any+null
     *                    prediction → prediction+any+null
     *                    chat → chat+any+null
     *
     * @param  string  $provider  ชื่อ provider (grok, openai, etc.)
     * @param  string|null  $purpose  'prediction' | 'chat' | 'free_card' | null (= any)
     */
    public function getKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $settings = AiApiKeySetting::forProvider($provider);
        $mode = $settings->rotation_mode;

        $key = match ($mode) {
            'round_robin' => $this->getRoundRobinKey($provider, $purpose),
            'least_used' => $this->getLeastUsedKey($provider, $purpose),
            'priority' => $this->getPriorityKey($provider, $purpose),
            'random' => $this->getRandomKey($provider, $purpose),
            'failover' => $this->getFailoverKey($provider, $purpose),
            'smart' => $this->getSmartKey($provider, $purpose),
            default => $this->getRoundRobinKey($provider, $purpose),
        };

        // 🆕 (2026-05-07) ถ้าระบุ $purpose แต่ไม่เจอ key → fallback หา key ใดๆ ที่ available
        //   เหตุผล: ระบบต้องไม่หยุดถ้า admin ลืมตั้ง purpose key
        //   แต่ log warning เพื่อให้ admin รู้ว่าควรเพิ่ม dedicated key
        if (! $key && $purpose !== null) {
            Log::warning('AI API Key Pool: ไม่มี key ตรง purpose → fallback ใช้ key ทั่วไป', [
                'provider' => $provider,
                'purpose' => $purpose,
                'mode' => $mode,
            ]);
            $key = match ($mode) {
                'round_robin' => $this->getRoundRobinKey($provider, null),
                'least_used' => $this->getLeastUsedKey($provider, null),
                'priority' => $this->getPriorityKey($provider, null),
                'random' => $this->getRandomKey($provider, null),
                'failover' => $this->getFailoverKey($provider, null),
                'smart' => $this->getSmartKey($provider, null),
                default => $this->getRoundRobinKey($provider, null),
            };
        }

        if ($key) {
            Log::debug('AI API Key Pool: เลือก key', [
                'provider' => $provider,
                'mode' => $mode,
                'purpose' => $purpose,
                'key_id' => $key->id,
                'key_name' => $key->name,
                'key_purpose' => $key->purpose,
            ]);
        } else {
            Log::warning('AI API Key Pool: ไม่พบ key ที่พร้อมใช้งาน', [
                'provider' => $provider,
                'purpose' => $purpose,
                'mode' => $mode,
            ]);
        }

        return $key;
    }

    /**
     * ดึง API Key string สำหรับ provider
     *
     * @param  string|null  $purpose  filter ตาม purpose ของ key
     */
    public function getApiKey(string $provider, ?string $purpose = null): ?string
    {
        $key = $this->getKey($provider, $purpose);

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
     * 🆕 (2026-05-07) Helper — สร้าง base query: forProvider + available + (optional) forPurpose
     *   ใช้เป็น single source of truth สำหรับ rotation methods ทุกตัว
     */
    protected function baseQuery(string $provider, ?string $purpose = null)
    {
        $query = AiApiKey::forProvider($provider)->available();
        if ($purpose !== null) {
            $query->forPurpose($purpose);
        }

        return $query;
    }

    /**
     * Round Robin: วนตามลำดับ
     */
    protected function getRoundRobinKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $keys = $this->baseQuery($provider, $purpose)
            ->orderBy('id')
            ->get();

        if ($keys->isEmpty()) {
            return null;
        }

        // ดึง index ปัจจุบันจาก cache (per-purpose เพื่อกัน purpose ต่างกันแย่ง index เดียวกัน)
        $cacheKey = self::CACHE_PREFIX."rr_index_{$provider}_".($purpose ?? 'any');
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
    protected function getLeastUsedKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $key = $this->baseQuery($provider, $purpose)
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
    protected function getPriorityKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $key = $this->baseQuery($provider, $purpose)
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
    protected function getRandomKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $key = $this->baseQuery($provider, $purpose)
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
    protected function getFailoverKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        // พยายามใช้ key ที่ priority สูงสุดก่อน
        $key = $this->baseQuery($provider, $purpose)
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
     * 🆕 (2026-05-07) เพิ่ม purpose-aware scoring:
     *   - exact purpose match → score boost (ลด score เพื่อชนะ tie-break)
     *   - any/null purpose → no boost (ใช้ได้แต่ไม่พิเศษ)
     *
     * คำนวณ load score ต่อ key:
     * - in_flight × 100  (key กำลังถูกใช้ = หลีกเลี่ยง)
     * - rpm × 10          (ใช้บ่อย/นาที = กระจายออก)
     * - errors × 50       (error เยอะ = หลีกเลี่ยง)
     * - priority × -5     (priority สูง = ดีกว่า)
     * - purpose_match → -1000 (ตรง purpose = preferred)
     *
     * เลือก key score ต่ำสุด → ถ้าเท่ากัน เลือกตัวที่ไม่ได้ใช้นานสุด
     */
    protected function getSmartKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $keys = $this->baseQuery($provider, $purpose)->get();

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
            $score = $this->getKeyLoadScore($provider, $key, $purpose);

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
                'key_purpose' => $bestKey->purpose,
                'requested_purpose' => $purpose,
                'score' => $bestScore,
                'inflight' => $this->getKeyInflight($provider, $bestKey->id),
                'rpm' => $this->getKeyRpm($provider, $bestKey->id),
            ]);
        }

        return $bestKey;
    }

    /**
     * คำนวณ load score ของ key (ยิ่งต่ำยิ่งดี)
     *
     * 🆕 (2026-05-07) เพิ่ม purpose-match boost + priority weight
     *   exact match purpose → -1000 (preferred ชัดเจน)
     *   priority > 0 → -priority*5 (high priority ดีกว่า)
     */
    protected function getKeyLoadScore(string $provider, AiApiKey $key, ?string $requestedPurpose = null): int
    {
        $inflight = $this->getKeyInflight($provider, $key->id);
        $rpm = $this->getKeyRpm($provider, $key->id);
        $errors = $key->consecutive_errors ?? 0;
        $priority = max(0, (int) ($key->priority ?? 0));

        $score = ($inflight * 100) + ($rpm * 10) + ($errors * 50) - ($priority * 5);

        // 🎯 Purpose match boost — exact match ชนะ
        if ($requestedPurpose !== null && $key->purpose === $requestedPurpose) {
            $score -= 1000;
        }

        return $score;
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
     * 🆕 (2026-05-07) รองรับ $purpose param — ใช้ filter ก่อนเลือก key
     *
     * @param  string  $provider  ชื่อ provider
     * @param  string|null  $purpose  filter ตาม purpose ('prediction'/'chat'/'free_card')
     */
    public function acquireKey(string $provider, ?string $purpose = null): ?AiApiKey
    {
        $key = $this->getKey($provider, $purpose);

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
     * 🎯 (2026-05-13) Acquire key ข้าม provider — Pool เป็นคนเลือก provider เอง
     *
     * 🔄 (2026-05-13 v3) Cross-provider tier — ไม่แบ่ง provider ใน tier เดียวกัน
     *
     * 🆕 (2026-05-18 v4) **Purpose-first** — purpose เจาะจงชนะ 'any' เสมอ
     *
     * User spec (2026-05-18):
     *   "เน้น purpose ก่อน แม้ priority ต่ำกว่า
     *    เช็ค priority ที่ purpose เหมือนกันเท่านั้น ถึงจะมีน้ำหนัก"
     *
     * Logic — 3-axis (purpose ก่อน, priority รอง, mode สุดท้าย):
     *   AXIS 1: purpose specificity (สูงสุดก่อน)
     *           Tier 0 = exact purpose match (เช่น purpose='chat' เมื่อขอ 'chat')
     *           Tier 1 = 'any' (backup สุดท้าย — admin set แบบ general-purpose)
     *           Tier 2 = NULL (legacy / ไม่ระบุ — เก็บไว้กัน BC)
     *   AXIS 2: priority tier (DESC — สูงสุดก่อน) — ภายใน purpose-tier เดียวกัน
     *   AXIS 3: rotation_mode "global" ใน priority-tier เดียวกัน
     *           (จาก config('ai.cross_provider_rotation_mode', 'smart'))
     *
     * Health gate:
     *   - available() scope filter `last_test_passed_at IS NOT NULL`
     *     → admin ต้อง test key สำเร็จก่อน ระบบจึงจะเลือก key นั้น
     *
     * ตัวอย่าง (เมื่อขอ purpose='chat'):
     *   Pool:
     *     - openai_A: priority=100, purpose='any',  tested=true
     *     - openai_B: priority=80,  purpose='any',  tested=true
     *     - groq_C:   priority=50,  purpose='chat', tested=true ← exact match!
     *
     *   v3 (เก่า): tier 100 = [openai_A] → openai_A ชนะ ❌
     *   v4 (ใหม่): purpose-tier 0 [groq_C] → groq_C ชนะ ✅
     *              ถ้า groq_C ติด cooldown → fallback purpose-tier 1 [openai_A, openai_B]
     *
     * @param  string|null  $purpose  filter (prediction_deep / chat / sensitive / etc.)
     */
    public function acquireKeyAnyProvider(?string $purpose = null): ?AiApiKey
    {
        // 1. Query keys + purpose filter
        //    available() scope = is_active + not critical + not disabled + last_test_passed_at IS NOT NULL
        $query = AiApiKey::available();
        if ($purpose !== null && $purpose !== '') {
            $query->forPurpose($purpose);
        }

        $allKeys = $query->get();
        if ($allKeys->isEmpty()) {
            return null;
        }

        // 2. 🆕 (v4) Group by purpose specificity FIRST
        //    Tier 0 = exact match | Tier 1 = 'any' | Tier 2 = null/legacy
        //    หมายเหตุ: ถ้า $purpose=null (caller ไม่ระบุ) → ทุก key ถือเป็น tier เดียวกัน
        $purposeTiers = $allKeys->groupBy(function ($k) use ($purpose) {
            if ($purpose !== null && $purpose !== '' && $k->purpose === $purpose) {
                return 0; // exact match
            }
            if ($k->purpose === 'any') {
                return 1; // general-purpose backup
            }

            return 2; // null / legacy / fallback purposes ที่ผ่าน scopeForPurpose
        })->sortKeys();

        // 3. Global rotation mode สำหรับ cross-provider priority-tier
        $globalMode = (string) (config('ai.cross_provider_rotation_mode')
            ?? AiApiKeySetting::forProvider('*')->rotation_mode
            ?? 'smart');

        foreach ($purposeTiers as $pTier => $purposeTierKeys) {
            // 🆕 (v4) ใน purpose-tier เดียวกัน → group ตาม priority DESC
            //    🛡️ Cast priority เป็น int — กัน sortKeysDesc lexical sort
            $priorityTiers = $purposeTierKeys
                ->groupBy(fn ($k) => (int) $k->priority)
                ->sortKeysDesc(SORT_NUMERIC);

            foreach ($priorityTiers as $tierPriority => $tierKeys) {
                // 4. กรอง runtime guards ก่อนเลือก (cooldown / inflight / rpm)
                $eligible = $tierKeys->filter(function ($key) {
                    $provider = $key->provider;

                    if (Cache::has("pool:cooldown:{$provider}:{$key->id}")) {
                        return false;
                    }

                    $inflight = $this->getKeyInflight($provider, $key->id);
                    $inflightCap = (int) (config('ai.per_key_inflight_cap') ?? 10);
                    if ($inflight >= $inflightCap) {
                        return false;
                    }

                    $rpm = $this->getKeyRpm($provider, $key->id);
                    $rpmLimit = $key->rate_limit_per_minute ?? 60;
                    if ($rpmLimit > 0 && $rpm >= $rpmLimit) {
                        return false;
                    }

                    return true;
                })->values();

                if ($eligible->isEmpty()) {
                    continue; // priority-tier นี้หมดสิทธิ์ — ลง priority-tier ถัดไป
                }

                // 5. เลือก key จาก eligible pool ตาม global rotation mode
                //    ✅ Scope key — แยกตาม purpose-tier + priority-tier (กัน rotation pointer ปนกัน)
                $scopeKey = "p{$pTier}_pri{$tierPriority}_cross";
                $key = $this->selectKeyByMode($eligible, $globalMode, $scopeKey);

                if (! $key) {
                    continue;
                }

                // 6. Acquire — increment in-flight (atomic ผ่าน Cache::lock)
                $provider = $key->provider;
                $inflightKey = self::INFLIGHT_PREFIX."{$provider}:{$key->id}";
                $lockKey = "{$inflightKey}:lock";
                $lock = Cache::lock($lockKey, 5);
                try {
                    if ($lock->block(2)) {  // wait max 2s for lock
                        if (Cache::has($inflightKey)) {
                            Cache::increment($inflightKey);
                        } else {
                            Cache::put($inflightKey, 1, 30);
                        }
                    } else {
                        // lock fail — fallback non-atomic (likely ok ภายใต้ low contention)
                        if (Cache::has($inflightKey)) {
                            Cache::increment($inflightKey);
                        } else {
                            Cache::put($inflightKey, 1, 30);
                        }
                    }
                } finally {
                    optional($lock)->release();
                }

                // 🆕 (v4) Log purpose-tier label เพื่อ verify การทำงาน
                $tierLabel = match ($pTier) {
                    0 => 'exact',
                    1 => 'any',
                    default => 'null/legacy',
                };

                Log::debug('Pool: acquireKeyAnyProvider — picked (purpose-first v4)', [
                    'purpose_tier' => $pTier,
                    'purpose_tier_label' => $tierLabel,
                    'priority_tier' => $tierPriority,
                    'tier_size' => $eligible->count(),
                    'rotation_mode' => $globalMode,
                    'provider' => $provider,
                    'key_id' => $key->id,
                    'key_name' => $key->name,
                    'purpose_requested' => $purpose,
                    'key_purpose' => $key->purpose,
                ]);

                return $key;
            }
        }

        return null;
    }

    /**
     * 🎯 (2026-05-13) Select key ใน collection ตาม rotation mode
     *
     * Helper ของ acquireKeyAnyProvider — เลือก key ตาม mode
     *
     * @param  \Illuminate\Support\Collection<int,AiApiKey>  $keys  keys eligible
     * @param  string  $mode  rotation mode (round_robin/least_used/smart/etc.)
     * @param  string  $scopeKey  unique scope สำหรับ rotation pointer
     *                            (เช่น "tier_100_cross" หรือ "openai_p100")
     */
    protected function selectKeyByMode(\Illuminate\Support\Collection $keys, string $mode, string $scopeKey = 'default'): ?AiApiKey
    {
        if ($keys->isEmpty()) {
            return null;
        }
        if ($keys->count() === 1) {
            return $keys->first();
        }

        return match ($mode) {
            'round_robin' => $this->pickRoundRobinFromCollection($keys, $scopeKey),
            'least_used' => $keys->sortBy('tokens_used_today')->first(),
            'priority' => $keys->sortBy('id')->first(),  // ใน tier เดียวกัน → ใช้ id ASC (stable)
            'random' => $keys->random(),
            'failover' => $keys->sortBy('id')->first(),  // ใช้ตัวแรก, สำรองเมื่อ fail
            'smart' => $this->pickSmartFromCollection($keys),
            default => $keys->sortBy('tokens_used_today')->first(),  // default = least_used
        };
    }

    /**
     * Round-robin rotation pointer — ใช้ scopeKey เป็น cache namespace
     *
     * ตัวอย่าง scopeKey:
     *   - "tier_100_cross"  → cross-provider rotation ใน tier priority=100
     *   - "openai_p100"     → rotation เฉพาะ openai+priority=100 (legacy path)
     */
    protected function pickRoundRobinFromCollection(\Illuminate\Support\Collection $keys, string $scopeKey = 'default'): ?AiApiKey
    {
        if ($keys->isEmpty()) {
            return null;
        }

        $cacheKey = "pool:rr_pointer:{$scopeKey}";
        $idx = (int) Cache::get($cacheKey, 0);
        $sorted = $keys->sortBy('id')->values();
        $count = $sorted->count();
        $key = $sorted[$idx % $count];
        // Advance pointer (TTL 5 นาที — กัน lost rotation เมื่อ idle)
        Cache::put($cacheKey, ($idx + 1) % $count, 300);

        return $key;
    }

    /**
     * Smart selection — least errors + least tokens used + least in-flight
     */
    protected function pickSmartFromCollection(\Illuminate\Support\Collection $keys): ?AiApiKey
    {
        if ($keys->isEmpty()) {
            return null;
        }

        // Score แต่ละ key — สูง = ดีกว่า
        $best = null;
        $bestScore = -PHP_INT_MAX;
        foreach ($keys as $key) {
            $errors = (int) ($key->consecutive_errors ?? 0);
            $tokensUsed = (int) ($key->tokens_used_today ?? 0);
            $inflight = $this->getKeyInflight($key->provider, $key->id);
            // ยิ่ง errors/tokens/inflight น้อย → score สูง
            $score = -($errors * 1000 + intdiv($tokensUsed, 100) + $inflight * 10);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $key;
            }
        }

        return $best;
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
                'is_critical' => $key->is_critical ?? false,        // 🔴 (2026-05-01) Critical state
                'model' => $key->model,                              // 🎯 (2026-05-01) Per-key model
                'resolved_model' => $key->resolveModel(),            // model ที่จะใช้จริง
                'base_url' => $key->base_url,                        // 🌐 (2026-05-01) Per-key base URL
                'resolved_base_url' => $key->resolveBaseUrl(),       // base URL ที่จะใช้จริง
                'purpose' => $key->purpose,                          // 🌟 (2026-05-05) any/prediction/free_card/chat
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
                'error_check_attempts' => $key->error_check_attempts ?? 0,    // 🩺 (2026-05-01) 3-strikes counter
                'last_error' => $key->last_error,
                'last_error_at' => $key->last_error_at?->diffForHumans(),
                'disabled_until' => $key->disabled_until?->diffForHumans(),
                // 🩺 (2026-05-07) Auto-recheck status
                'last_recheck_at' => $key->last_recheck_at?->diffForHumans(),
                'recheck_failure_count' => $key->recheck_failure_count ?? 0,
                'next_recheck_at' => $key->next_recheck_at?->diffForHumans(),
                'auto_recovered_at' => $key->auto_recovered_at?->diffForHumans(),
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
