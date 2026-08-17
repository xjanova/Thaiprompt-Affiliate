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
     * 🆕 (2026-05-23 Phase 2) Per-key spacing สำหรับ chat-family purposes
     *
     * วัตถุประสงค์: บังคับเว้นระยะระหว่างใช้ key เดิม → กระจาย load
     * → ทุก key ถูกใช้สลับกัน (ตายช้าที่สุด)
     *
     * spacing = 60 / rpm_limit × CHAT_SPACING_FACTOR วินาที
     *   เช่น Groq RPM=28 → 60/28 × 2.0 = 4.3 วินาที/คีย์
     *   เช่น Gemini Flash-Lite RPM=14 → 60/14 × 2.0 = 8.6 วินาที/คีย์
     *
     * Apply เฉพาะ chat-family (chat, chat_paid) ไม่กระทบ prediction
     * (prediction ใช้เวลา 30-60s อยู่แล้ว throughput ต่ำ ไม่ต้อง space)
     */
    private const CHAT_SPACING_PREFIX = 'pool:chat_spacing:'; // {provider}:{key_id} — TTL = spacing seconds

    private const CHAT_SPACING_FACTOR = 2.0;

    private const CHAT_FAMILY_PURPOSES = ['chat', 'chat_paid'];

    /**
     * 🏢 (2026-05-23 Phase 4) Groq organization-level cooldown
     *
     * Groq นับ rate limit per ORGANIZATION ไม่ใช่ per key
     * ถ้า admin ใช้ Groq หลาย key ในองค์กรเดียวกัน → 1 key ติด 429 = ทั้ง org เต็ม
     *
     * ค้นพบ 2026-05-23: 10 Groq keys = แค่ 7 องค์กร (3 key × 2 orgs)
     * Pool เลือก key A 429 → fallback key B (org เดียวกัน) → 429 ทันที → loop
     *
     * Fix: parse org_id จาก error message → ban ทั้ง org 60s
     */
    private const GROQ_ORG_BAN_PREFIX = 'pool:groq_org_ban:';  // {org_id} — TTL = retry_after

    /**
     * 🪙 (2026-05-23 Phase 5) TPM (Tokens Per Minute) accumulator
     *
     * Track tokens ที่ key ใช้ใน 60s ที่ผ่านมา → กัน TPM ทะลุ provider limit
     *
     * Threshold: ถ้า current TPM >= 90% ของ limit → skip key (preemptive)
     * เพราะ next call อาจใช้เพิ่ม 10-20% → ทะลุ
     *
     * เก็บ Cache::put(TTL=60s) → reset อัตโนมัติทุก 60s
     */
    private const TPM_PREFIX = 'pool:tpm:';  // {provider}:{key_id} — TTL 60s

    private const TPM_SAFETY_THRESHOLD = 0.9;  // skip ถ้า used >= 90% limit

    /**
     * 🆕 (v5.1 — 2026-05-19) Strict purposes ที่ห้ามถูกใช้กับ generic call (caller=null)
     *
     *   เหตุผล: admin ตั้ง purpose นี้ = สงวน strictly (ไม่ fallback)
     *     - 'sensitive' → Pro model (gpt-5.5, claude-opus) แพง 5-15x → เผา quota
     *     - 'tts' → schema คนละแบบ (text-to-speech endpoint) → call fail
     *
     *   ใช้ใน acquireKeyAnyProvider($purpose=null) — exclude ออกก่อน tier sort
     *   admin ที่อยาก force ใช้ key นี้ → เรียก acquireKey($provider, 'sensitive') ตรง
     */
    // 💙 (2026-05-23) เพิ่ม chat_paid — caller=null ห้ามใช้ paid chat key (เผา quota)
    //    caller='chat' เท่านั้นที่ acquireKeyAnyProvider จะ promote chat_paid เป็น Tier 3
    private const STRICT_PURPOSES_BLOCKED_FOR_GENERIC = ['sensitive', 'tts', 'chat_paid'];

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
     *
     * 🪪 (2026-08-17) $context = ตัวตนของลูกค้า/ใบดูดวงที่ call นี้ให้บริการ
     *   คีย์ที่รองรับ: reading_id, user_id, fb_user_id, customer_name
     *   (ดู AiApiKey::customerContextColumns() — คีย์อื่นถูกทิ้ง ไม่ mass-assign)
     *   ต้องส่งต่อลง model ไม่งั้น log row ได้ reading_id = NULL
     *   → วัดต้นทุน AI ต่อ 1 ใบดูดวงไม่ได้
     *
     * @param  array<string,mixed>|null  $context  ตัวตนลูกค้า (optional)
     */
    public function recordUsage(
        string $provider,
        int $inputTokens,
        int $outputTokens,
        ?string $model = null,
        ?int $responseTimeMs = null,
        string $requestType = 'general',
        ?array $context = null
    ): void {
        // ดึง key ที่เพิ่งใช้ (จาก cache หรือล่าสุด)
        $key = $this->getLastUsedKey($provider);

        if ($key) {
            $key->recordUsage($inputTokens, $outputTokens, $model, $responseTimeMs, $requestType, $context);

            Log::debug('AI API Key Pool: บันทึกการใช้งาน', [
                'provider' => $provider,
                'key_id' => $key->id,
                'total_tokens' => $inputTokens + $outputTokens,
                'reading_id' => $context['reading_id'] ?? null,
            ]);
        }
    }

    /**
     * บันทึก error
     *
     * @param  array<string,mixed>|null  $context  ตัวตนลูกค้า (คีย์เดียวกับ recordUsage)
     */
    public function recordError(string $provider, string $errorMessage, ?string $model = null, ?array $context = null): void
    {
        $key = $this->getLastUsedKey($provider);

        if ($key) {
            $key->recordError($errorMessage, $model, $context);

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

        // ⭐ กรอง keys ที่เกิน rate_limit_per_minute (ใช้ smart default ถ้า admin ไม่ตั้ง)
        //   🎯 (2026-05-22) ใช้ getEffectiveRpmLimit() — model-aware free tier default
        //     - admin set N → ใช้ N
        //     - admin set 0 → unlimited (ข้าม check)
        //     - admin set NULL → smart default ตาม provider+model (ป้องกัน over-call)
        $eligibleKeys = $keys->filter(function ($key) use ($provider) {
            $rpmLimit = $key->getEffectiveRpmLimit();
            if ($rpmLimit > 0) {
                $rpm = $this->getKeyRpm($provider, $key->id);
                if ($rpm >= $rpmLimit) {
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
     * 🚫 (2026-05-23 v6) **BAN 'any' purpose** — User spec:
     *   "เอา purpose any ออกไปเลย — มันเป็นสาเหตุที่ทำให้ มั่วกันเยอะ"
     *   Pool จะ filter key purpose='any' ออกทั้งหมด (skip ก่อน group tier)
     *   key purpose='any' ที่เหลือใน DB = legacy/deleted ไม่ถูกใช้
     *
     * User spec (2026-05-18):
     *   "เน้น purpose ก่อน แม้ priority ต่ำกว่า
     *    เช็ค priority ที่ purpose เหมือนกันเท่านั้น ถึงจะมีน้ำหนัก"
     *
     * Logic — 3-axis (purpose ก่อน, priority รอง, mode สุดท้าย):
     *   AXIS 1: purpose specificity (สูงสุดก่อน)
     *           Tier 0 = exact purpose match (เช่น purpose='chat' เมื่อขอ 'chat')
     *           Tier 2 = NULL (legacy / ไม่ระบุ — เก็บไว้กัน BC)
     *           🚫 'any' tier ถูกลบออกแล้ว (2026-05-23)
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
        } else {
            // 🆕 (v5.1 — 2026-05-19) caller=null → exclude strict-reserved purposes
            //   admin ตั้ง purpose นี้ = สงวน strictly (ไม่ fallback) — ห้ามใช้กับ generic call
            //     - 'sensitive' → Pro model แพง 5-15x → เผา OpenAI quota
            //     - 'tts' → schema คนละแบบ (text-to-speech) → call fail
            //   admin ที่อยาก force → เรียก acquireKey($provider, 'sensitive') ตรง
            $query->whereNotIn('purpose', self::STRICT_PURPOSES_BLOCKED_FOR_GENERIC);
        }

        // 🚫 (2026-05-23) BAN purpose='any' — User spec: "เอา purpose any ออกไป"
        //   ถ้ามี key purpose='any' หลุดมาในผลลัพธ์ (legacy data) → reject ก่อน group tier
        //   เหตุผล: 'any' = รูรั่ว — caller ที่ specific purpose หมด/429 จะ fallback มา
        //   จุดนี้ทำให้ admin งง: "ตั้ง OpenAI ใช้แค่ Celtic 99 แต่ token รั่วไป Deep"
        $allKeys = $query->get()->reject(fn ($k) => $k->purpose === 'any');

        if ($allKeys->isEmpty()) {
            return null;
        }

        // 2. 🆕 (v5 — 2026-05-19) Group by purpose specificity FIRST
        //    User rule: "key ที่ตั้ง chat ต้องมาก่อน any" — specific purpose ชนะ 'any' เสมอ
        //
        //    🚫 (v6 — 2026-05-23) ลบ 'any' tier ทิ้ง — User: "เอา purpose any ออกไปเลย"
        //
        //    Caller ระบุ purpose (เช่น 'chat'):
        //      Tier 0 = exact match (purpose='chat')
        //      Tier 2 = null/legacy  (legacy keys ไม่มี purpose)
        //
        //    Caller ไม่ระบุ purpose (purpose=null):
        //      Tier 1 = specific (purpose != null)  ← Groq 'chat' / OpenAI 'sensitive' ชนะ
        //      Tier 3 = null/legacy                  ← key เก่าไม่มี purpose
        //      เหตุผล: ถ้า admin ลงทุนตั้ง purpose เจาะจง = ตั้งใจสงวน
        $purposeTiers = $allKeys->groupBy(function ($k) use ($purpose) {
            $callerHasPurpose = ($purpose !== null && $purpose !== '');
            $keyPurpose = $k->purpose;
            $keyIsNull = ($keyPurpose === null || $keyPurpose === '');
            $keyIsSpecific = ! $keyIsNull;

            if ($callerHasPurpose) {
                // Caller ระบุ — exact ก่อน, null/legacy สุดท้าย
                if ($keyPurpose === $purpose) {
                    return 0; // exact match
                }

                // 💙 (2026-05-23) chat_paid = Tier 3 last-resort fallback
                //    เมื่อ caller='chat' + free chat (Tier 0) + null (Tier 2) หมด
                //    ระบบจึงจะถึง chat_paid (paid chat key สีฟ้า)
                //    กัน paid quota เผาไปกับ chitchat ลูกค้าเดินผ่าน
                if ($purpose === 'chat' && $keyPurpose === 'chat_paid') {
                    return 3; // last resort — paid chat
                }

                return 2; // null / legacy
            }

            // Caller ไม่ระบุ — specific ชนะ null/legacy
            if ($keyIsSpecific) {
                return 1; // เจาะจง purpose (chat/prediction/sensitive/etc.) มาก่อน
            }

            return 3; // null / legacy สุดท้าย
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

            // 💬 (2026-05-23 Phase 2) Detect chat-family request — เปิด spacing + LRU
            //   chat-family = caller='chat' / 'chat_paid' (ห้าม spam key เดิม)
            //   prediction/sensitive/tts ไม่ space เพราะ throughput ต่ำอยู่แล้ว
            $isChatFamily = in_array($purpose, self::CHAT_FAMILY_PURPOSES, true);

            foreach ($priorityTiers as $tierPriority => $tierKeys) {
                // 4. กรอง runtime guards ก่อนเลือก (cooldown / inflight / rpm / spacing)
                $eligible = $tierKeys->filter(function ($key) use ($isChatFamily) {
                    $provider = $key->provider;

                    if (Cache::has("pool:cooldown:{$provider}:{$key->id}")) {
                        return false;
                    }

                    // 🏢 (Phase 4) Groq org-level ban — กัน rotate ภายใน org เดียวกัน
                    //   Groq นับ rate limit per org ไม่ใช่ per key
                    //   ถ้า key A ติด 429 → key B/C ใน org เดียวกัน = ติดด้วย
                    //   pool รู้ org_id ของแต่ละ key ผ่าน metadata.groq_org_id (auto-learn)
                    if ($provider === 'groq') {
                        $orgId = $key->metadata['groq_org_id'] ?? null;
                        if ($this->isGroqOrgBanned($orgId)) {
                            return false;
                        }
                    }

                    // 💬 (Phase 2) spacing — chat-family เท่านั้น
                    //   หลังใช้ key A → ต้องรอ 60/rpm × 2.0 วินาที ก่อนใช้ A ซ้ำ
                    //   ช่วงนี้ ไป key B, C, D, ... → หมุนเวียนยุติธรรม
                    if ($isChatFamily && $this->isChatSpaced($provider, $key->id)) {
                        return false;
                    }

                    $inflight = $this->getKeyInflight($provider, $key->id);
                    $inflightCap = (int) (config('ai.per_key_inflight_cap') ?? 10);
                    if ($inflight >= $inflightCap) {
                        return false;
                    }

                    $rpm = $this->getKeyRpm($provider, $key->id);
                    // 🎯 (2026-05-22) ใช้ smart default แทน hardcoded 60
                    //   เดิม: ?? 60 → free Groq (จริง 30) ยิงเกิน → 429 → drift ไป paid
                    //   ใหม่: getEffectiveRpmLimit() = admin set || model-aware default
                    //     - Gemini 2.5 Flash → 9 (จริง 10)
                    //     - Groq → 28 (จริง 30)
                    //     - Paid: admin ต้องตั้งเอง (เช่น 1000) — default คือ free
                    $rpmLimit = $key->getEffectiveRpmLimit();
                    if ($rpmLimit > 0 && $rpm >= $rpmLimit) {
                        return false;
                    }

                    // 🪙 (Phase 5) TPM check — กัน Groq llama-3.3-70b ทะลุ 6000 tokens/min
                    //   ดู MODEL_TPM_FREE_TIER. skip ถ้า used >= 90% limit (preemptive)
                    //   พบ 2026-05-23: avg/call=7946 → 1 call ทะลุ TPM 6000 ของ llama-3.3-70b
                    //   แก้: เปลี่ยน model เป็น llama-3.1-8b-instant (TPM 30000) ที่ admin UI
                    if ($this->isTpmSaturated($key)) {
                        return false;
                    }

                    return true;
                })->values();

                if ($eligible->isEmpty()) {
                    continue; // priority-tier นี้หมดสิทธิ์ — ลง priority-tier ถัดไป
                }

                // 💬 (2026-05-23 Phase 2) LRU sort สำหรับ chat-family
                //   เรียง key ตาม last_used_at ASC → คีย์พักนานสุดมาก่อน
                //   → กระจายการใช้ยุติธรรม + กัน thundering herd
                //   key ที่ไม่เคยใช้ (last_used_at=null) มาก่อนสุด (priority แรก)
                if ($isChatFamily) {
                    $eligible = $eligible->sortBy(function ($k) {
                        // null = ไม่เคยใช้ → sort ขึ้นก่อน (timestamp 0)
                        return $k->last_used_at ? $k->last_used_at->timestamp : 0;
                    })->values();
                }

                // 5. เลือก key จาก eligible pool ตาม global rotation mode
                //    ✅ Scope key — แยกตาม purpose-tier + priority-tier (กัน rotation pointer ปนกัน)
                //    💬 (Phase 2) chat-family — eligible ถูก sort LRU แล้ว → pick top
                $scopeKey = "p{$pTier}_pri{$tierPriority}_cross";
                $key = $isChatFamily
                    ? $eligible->first()
                    : $this->selectKeyByMode($eligible, $globalMode, $scopeKey);

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

                // 🩹 (2026-05-23 Phase 3) Pre-call RPM increment — กัน thundering herd
                //   นับทันทีหลัง acquire สำเร็จ (ก่อนยิง API)
                //   ทั้ง success และ fail (429) จะถูก count → ระบบเห็น load จริง
                //   → ครั้งถัดไป pool จะเลือก key อื่นที่ RPM ยังต่ำ
                $this->incrementKeyRpm($provider, $key->id);

                // 💬 (2026-05-23 Phase 2) Set chat-family spacing — บังคับเว้นระยะก่อนใช้ key เดิม
                //   ตัวอย่าง: Groq RPM=28 → spacing 4.3s/key
                //   ตอน acquire ครั้งถัดไป (ภายใน window): key นี้ถูก skip → ระบบไป key อื่น
                //   → หมุนเวียนยุติธรรม / ไม่ thundering herd
                //   Tier 3 (chat_paid) ก็ space เหมือนกัน (กัน paid quota เผาเร็ว)
                if ($isChatFamily) {
                    $this->setChatSpacing($provider, $key->id, $key->getEffectiveRpmLimit());
                }

                // 🆕 (v6 — 2026-05-23) Log purpose-tier label — 'any' tier ลบแล้ว
                //   Caller specified  : 0=exact, 2=null/legacy, 3=chat_paid (last resort)
                //   Caller null       : 1=specific, 3=null/legacy
                $callerSpecified = ($purpose !== null && $purpose !== '');
                $tierLabel = $callerSpecified
                    ? match ($pTier) {
                        0 => 'exact',
                        3 => 'chat_paid (LAST RESORT)',
                        default => 'null/legacy',
                    }
                : match ($pTier) {
                    1 => 'specific',
                    default => 'null/legacy',
                };

                Log::debug('Pool: acquireKeyAnyProvider — picked (purpose-first v5)', [
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

        // 🩹 (2026-05-23 Phase 3) RPM increment ย้ายไป pre-call ใน acquireKeyAnyProvider
        //   เพื่อให้ count ทั้ง success+fail (เดิม count เฉพาะ success → fail loop)
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

    /**
     * 🩹 (2026-05-23 Phase 3) Pre-call RPM increment — กัน thundering herd
     *
     * เดิม: RPM increment อยู่ใน releaseKey (post-success) เท่านั้น
     *   → fail (429) ไม่ count → ระบบมองว่า key ยังว่าง → เลือกซ้ำ → 429 loop
     *
     * ใหม่: increment ทันทีหลัง pick key (ก่อนยิง API)
     *   → ทั้ง success และ fail ถูก track
     *   → ระบบเห็น load จริง → กระจายไป key อื่น
     */
    public function incrementKeyRpm(string $provider, int $keyId): void
    {
        $rpmKey = self::RPM_PREFIX."{$provider}:{$keyId}";
        if (Cache::has($rpmKey)) {
            Cache::increment($rpmKey);
        } else {
            Cache::put($rpmKey, 1, 60);
        }
    }

    /**
     * 💬 (2026-05-23 Phase 2) ตั้ง spacing cooldown สำหรับ chat-family key
     *
     * เรียกหลัง acquire สำเร็จ — บังคับเว้นระยะก่อนใช้ key เดิมซ้ำ
     * → กระจาย load สม่ำเสมอ ทุก key ถูกหมุนเวียน
     *
     * spacing = 60 / rpm × 2.0 วินาที (ผู้ใช้เลือก factor 2.0 = ใจดี ยืดอายุสุด)
     *
     * @param  int  $rpmLimit  RPM limit ของ key (จาก getEffectiveRpmLimit)
     */
    public function setChatSpacing(string $provider, int $keyId, int $rpmLimit): void
    {
        if ($rpmLimit <= 0) {
            return; // unlimited → ไม่ space
        }

        $spacingSeconds = (int) ceil(60.0 / $rpmLimit * self::CHAT_SPACING_FACTOR);
        $spacingSeconds = max(1, $spacingSeconds); // อย่างน้อย 1 วินาที

        Cache::put(self::CHAT_SPACING_PREFIX."{$provider}:{$keyId}", 1, $spacingSeconds);
    }

    /**
     * 💬 (2026-05-23 Phase 2) ตรวจว่า key อยู่ใน spacing window หรือไม่
     */
    public function isChatSpaced(string $provider, int $keyId): bool
    {
        return Cache::has(self::CHAT_SPACING_PREFIX."{$provider}:{$keyId}");
    }

    /**
     * 🏢 (2026-05-23 Phase 4) Extract Groq organization ID จาก error message
     *
     * ตัวอย่าง error:
     *   "Rate limit reached for model `llama-3.3-70b-versatile`
     *    in organization `org_01khzabedsegztw5mffmz4y` ..."
     *
     * @return string|null  org_id (เช่น "org_01khzabedsegztw5mffmz4y") หรือ null ถ้าไม่พบ
     */
    public function extractGroqOrgId(string $errorMessage): ?string
    {
        if (preg_match('/org_[a-z0-9]+/i', $errorMessage, $m)) {
            return strtolower($m[0]);
        }

        return null;
    }

    /**
     * 🏢 (2026-05-23 Phase 4) เรียนรู้ + บันทึก groq_org_id ของ key (ลง metadata JSON)
     *
     * เรียกตอน key Groq โดน 429 ครั้งแรก — เก็บ org_id ลง metadata
     * ครั้งถัดไป pool รู้ทันทีว่า key นี้อยู่ org ไหน → check org ban ก่อน
     */
    public function learnGroqOrgId(AiApiKey $key, string $errorMessage): ?string
    {
        if ($key->provider !== 'groq') {
            return null;
        }

        // ถ้า metadata มี org_id แล้ว ใช้ค่าเดิม (กัน update ซ้ำซาก)
        $metadata = $key->metadata ?? [];
        if (! empty($metadata['groq_org_id'])) {
            return $metadata['groq_org_id'];
        }

        $orgId = $this->extractGroqOrgId($errorMessage);
        if ($orgId === null) {
            return null;
        }

        // Save to metadata (silent — fail ไม่ block flow)
        try {
            $metadata['groq_org_id'] = $orgId;
            $key->update(['metadata' => $metadata]);
        } catch (\Throwable $e) {
            Log::warning('Pool: learnGroqOrgId update failed', [
                'key_id' => $key->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $orgId;
    }

    /**
     * 🏢 (2026-05-23 Phase 4) Ban Groq organization (ทุก key ใน org นี้)
     *
     * @param  int  $seconds  TTL ของ ban (default 60s ตาม Groq rate limit window)
     */
    public function banGroqOrg(string $orgId, int $seconds = 60): void
    {
        Cache::put(self::GROQ_ORG_BAN_PREFIX.$orgId, 1, $seconds);
    }

    /**
     * 🏢 (2026-05-23 Phase 4) ตรวจว่า org อยู่ใน ban หรือไม่
     */
    public function isGroqOrgBanned(?string $orgId): bool
    {
        if (! $orgId) {
            return false;
        }

        return Cache::has(self::GROQ_ORG_BAN_PREFIX.$orgId);
    }

    /**
     * 🪙 (2026-05-23 Phase 5) ดึง TPM accumulator ของ key (60s window)
     */
    public function getKeyTpm(string $provider, int $keyId): int
    {
        return (int) Cache::get(self::TPM_PREFIX."{$provider}:{$keyId}", 0);
    }

    /**
     * 🪙 (2026-05-23 Phase 5) เพิ่ม tokens ใน TPM accumulator (post-call)
     *
     * เรียกหลัง API call สำเร็จ — accumulate tokens ที่ใช้จริง
     * Window 60s — Cache TTL จะ reset อัตโนมัติ
     */
    public function incrementKeyTpm(string $provider, int $keyId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $tpmKey = self::TPM_PREFIX."{$provider}:{$keyId}";
        if (Cache::has($tpmKey)) {
            Cache::increment($tpmKey, $tokens);
        } else {
            Cache::put($tpmKey, $tokens, 60);
        }
    }

    /**
     * 🪙 (2026-05-23 Phase 5) ตรวจว่า key ใกล้เต็ม TPM แล้วหรือยัง
     *
     * ใช้ใน eligible filter — ถ้า used >= 90% limit → skip (preemptive)
     */
    public function isTpmSaturated(AiApiKey $key): bool
    {
        $limit = $key->getEffectiveTpmLimit();
        if ($limit <= 0) {
            return false; // unlimited
        }

        $used = $this->getKeyTpm($key->provider, $key->id);
        $threshold = (int) ($limit * self::TPM_SAFETY_THRESHOLD);

        return $used >= $threshold;
    }

    /**
     * 🪙 (2026-05-24 Phase 6) ตรวจว่า request นี้จะทะลุ TPM ของ key หรือไม่
     *
     * ต่าง isTpmSaturated() ตรงที่เช็คขนาด request ที่กำลังจะส่งด้วย:
     *   ถ้า (used + estimatedRequestTokens) > limit → return true → caller skip key นี้
     *
     * เคสที่เจอ 2026-05-24: pool's isTpmSaturated() filter ที่ 90% ของ limit ผิด (limit ใส่ผิด)
     *   8b-instant ใส่ 30000 (จริง 6000) → never saturated → request 700-tokens slip through
     *   → Groq เห็น used 5500 + this_request 700 = 6200 > 6000 → 413 reject
     *
     * แก้: caller ที่รู้ขนาด request ของตัวเอง (เช่น input_chars / 3 + max_completion)
     *      เรียก method นี้ก่อน → pool คืนค่า true ถ้าจะไม่ผ่าน
     *
     * @param  int  $estimatedRequestTokens  ประมาณการ input_tokens + max_completion_tokens
     */
    public function requestExceedsTpm(AiApiKey $key, int $estimatedRequestTokens): bool
    {
        $limit = $key->getEffectiveTpmLimit();
        if ($limit <= 0) {
            return false; // unlimited
        }

        if ($estimatedRequestTokens <= 0) {
            // ไม่รู้ขนาด — fallback to accumulated check เดิม
            return $this->isTpmSaturated($key);
        }

        // กัน request เดี่ยวที่ใหญ่กว่า limit เลย (ต่อให้ usage = 0)
        if ($estimatedRequestTokens > $limit) {
            return true;
        }

        $used = $this->getKeyTpm($key->provider, $key->id);
        // ใช้ safety 95% สำหรับ per-request check (looser กว่า saturated 90%)
        //   เหตุผล: ถ้าเข้มไป จะ filter ทิ้งคีย์ที่ยังมีที่ว่างพอ → starvation
        $safeLimit = (int) ($limit * 0.95);

        return ($used + $estimatedRequestTokens) >= $safeLimit;
    }

    /**
     * 🪙 (2026-05-24 Phase 6) Mark key TPM-saturated ทันที (manual override)
     *
     * เรียกเมื่อได้ 413 จริงๆ จาก API → fill accumulator ให้ทะลุ limit
     * → ครั้งถัดไป isTpmSaturated() = true → key ถูก skip 60s
     *
     * ใช้เมื่อ caller จับ 413 หรือ error "Request too large" ได้แล้ว
     */
    public function markTpmSaturated(AiApiKey $key, int $seconds = 60): void
    {
        $limit = $key->getEffectiveTpmLimit();
        if ($limit <= 0) {
            $limit = 30000; // fallback ถ้า unlimited (impossible 413 case)
        }

        $tpmKey = self::TPM_PREFIX."{$key->provider}:{$key->id}";
        Cache::put($tpmKey, $limit + 1, $seconds); // +1 เพื่อให้ทะลุ threshold แน่ๆ

        Log::info('Pool: markTpmSaturated', [
            'key_id' => $key->id,
            'provider' => $key->provider,
            'model' => $key->model,
            'limit' => $limit,
            'cooldown_sec' => $seconds,
        ]);
    }

    /**
     * 🪙 (2026-05-24 Phase 6) ประมาณการ token จาก text
     *
     * Heuristic (รวดเร็ว ไม่ต้อง tokenizer):
     *   - Thai: 1 token ≈ 2 chars
     *   - English/code: 1 token ≈ 4 chars
     *
     * ใช้ค่ากลาง 3 chars/token + 10% buffer (safer over-estimate)
     */
    public static function estimateTokens(string $text): int
    {
        $chars = mb_strlen($text);

        return (int) ceil($chars / 3 * 1.1);
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
