<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🌟 FortuneSensitivityDetector (2026-05-07)
 *
 * ตรวจจับว่าข้อความของลูกค้าเข้าข่าย "บริบทละเอียดอ่อน" หรือไม่
 *
 * Detection modes:
 *   - 'heuristic'    → regex/keyword เท่านั้น (เร็ว ฟรี deterministic)
 *   - 'hybrid'       → heuristic + Groq classifier (default)
 *   - 'hybrid_with_brain' → +ObsidianX history lookup (Phase 3)
 *
 * Outputs:
 *   - is_sensitive: ควรใช้ Pro model
 *   - is_offtopic: ถามนอกเรื่องดูดวง (สำหรับ strike counter)
 *   - mood_level: 1-5 (5=ก้าวร้าวสุด)
 *   - complexity: 1-5 (5=ซับซ้อนสุด)
 *   - reasons: array ของ trigger ที่จับได้
 *   - detection_used: 'heuristic' / 'classifier' / 'hybrid'
 *
 * Hybrid logic:
 *   - heuristic confidence ≥ 80 → mark sensitive (skip classifier — เซฟ token)
 *   - heuristic confidence == 0 + ข้อความสั้น → mark not-sensitive (skip)
 *   - else → call Groq classifier (1 call เพิ่ม ~50-150ms)
 */
class FortuneSensitivityDetector
{
    /**
     * Cache key สำหรับ classifier result (dedupe ข้อความเหมือนกัน 5 นาที)
     */
    protected const CLASSIFIER_CACHE_TTL = 300;

    /**
     * Threshold สำหรับ heuristic confidence — เกินนี้ skip classifier
     */
    protected const HEURISTIC_HIGH_CONFIDENCE = 80;

    /**
     * ความยาวข้อความสั้นที่ skip classifier ถ้า heuristic ไม่จับ
     */
    protected const SHORT_MESSAGE_LEN = 25;

    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจจับว่าข้อความเข้าข่าย sensitive หรือ off-topic
     *
     * @param  string  $message  ข้อความจากผู้ใช้
     * @param  array  $context  ['user_id' => ..., 'history' => [...], 'has_active_paid_reading' => bool, 'channel_context' => 'chat'/'paid_prediction'/'celtic']
     * @return array detection result
     */
    public function detect(string $message, array $context = []): array
    {
        $mode = $this->settings->sensitive_detection_mode ?? 'hybrid';

        // Default result
        $result = [
            'is_sensitive' => false,
            'is_offtopic' => false,
            'confidence' => 0,
            'mood_level' => 1,
            'complexity' => 1,
            'reasons' => [],
            'detection_used' => 'heuristic',
        ];

        // Step 1: Heuristic — เร็ว, deterministic
        $heuristic = $this->runHeuristic($message, $context);
        $result = array_merge($result, $heuristic);

        // ถ้าเป็น heuristic-only mode → จบ
        if ($mode === 'heuristic') {
            return $result;
        }

        // Step 2: Hybrid logic — ตัดสินใจว่าจะเรียก classifier ไหม

        // High confidence → skip classifier (ประหยัด token)
        if ($heuristic['confidence'] >= self::HEURISTIC_HIGH_CONFIDENCE) {
            $result['detection_used'] = 'heuristic';

            return $result;
        }

        // ข้อความสั้นและ heuristic ไม่จับเลย → skip
        if ($heuristic['confidence'] === 0 && mb_strlen($message) < self::SHORT_MESSAGE_LEN) {
            $result['detection_used'] = 'heuristic';

            return $result;
        }

        // Borderline → call classifier
        try {
            $classifier = $this->runClassifier($message, $context);

            // Merge: ถ้า classifier เห็นว่า sensitive → เปิดธง
            if ($classifier['is_sensitive']) {
                $result['is_sensitive'] = true;
                $result['reasons'][] = 'classifier:'.($classifier['reason'] ?? 'detected');
            }
            if ($classifier['is_offtopic']) {
                $result['is_offtopic'] = true;
                $result['reasons'][] = 'classifier:offtopic';
            }
            $result['mood_level'] = max($result['mood_level'], $classifier['mood_level'] ?? 1);
            $result['complexity'] = max($result['complexity'], $classifier['complexity'] ?? 1);
            $result['confidence'] = max($result['confidence'], $classifier['confidence'] ?? 50);
            $result['detection_used'] = $heuristic['confidence'] > 0 ? 'hybrid' : 'classifier';
        } catch (Exception $e) {
            // 🛑 (review L11) — ทุก exception จาก classifier (HTTP/timeout/network) → record fail
            //   ที่ไม่ใช่ "circuit open" message (ไม่งั้นจะเลื่อน TTL ตลอด)
            if (! str_contains($e->getMessage(), 'circuit breaker open')) {
                $this->recordClassifierFailure();
            }
            Log::warning('FortuneSensitivityDetector: classifier ล้มเหลว — ใช้ heuristic อย่างเดียว', [
                'error' => $e->getMessage(),
                'message_len' => mb_strlen($message),
            ]);
        }

        return $result;
    }

    /**
     * 🔍 Heuristic detection — regex/keyword
     *
     * @return array ['is_sensitive', 'is_offtopic', 'confidence' => 0-100, 'mood_level', 'complexity', 'reasons']
     */
    protected function runHeuristic(string $message, array $context = []): array
    {
        $msgLower = mb_strtolower(trim($message));
        $reasons = [];
        $confidenceScore = 0;
        $moodLevel = 1;
        $complexity = 1;
        $isOfftopic = false;

        // 1. คำหยาบ / ก้าวร้าว — น้ำหนัก 50
        $keywords = $this->settings->getSensitiveKeywords();
        foreach ($keywords as $kw) {
            if (mb_stripos($msgLower, mb_strtolower($kw)) !== false) {
                $reasons[] = "keyword:{$kw}";
                $confidenceScore += 50;
                $moodLevel = max($moodLevel, 4);
                break; // 1 keyword พอ
            }
        }

        // 2. หัวข้อหนัก (ตาย/ป่วย/หย่า) — น้ำหนัก 40
        $topics = $this->settings->getSensitiveTopics();
        foreach ($topics as $topic) {
            if (mb_stripos($msgLower, mb_strtolower($topic)) !== false) {
                $reasons[] = "topic:{$topic}";
                $confidenceScore += 40;
                $complexity = max($complexity, 4);
                break;
            }
        }

        // 3. ข้อความยาว + คำถามหลายข้อ (≥150 chars + ≥2 ?) — น้ำหนัก 25
        $msgLen = mb_strlen($message);
        $questionCount = substr_count($message, '?') + substr_count($message, '?');
        if ($msgLen >= 150 && $questionCount >= 2) {
            $reasons[] = 'long_complex';
            $confidenceScore += 25;
            $complexity = max($complexity, 4);
        }

        // 4. คำถามซ้ำใน history (3+ คำถามที่ tone เดียวกัน) — น้ำหนัก 20
        $history = $context['history'] ?? [];
        if (! empty($history)) {
            $userMessages = array_filter($history, fn ($m) => ($m['role'] ?? '') === 'user');
            if (count($userMessages) >= 3) {
                // ดู turn ล่าสุด 3 turns — ถ้ามีคำหยาบหรือคำถามที่ tone ลบ → repeat angry
                $recentNegative = 0;
                $recentMessages = array_slice(array_values($userMessages), -3);
                foreach ($recentMessages as $m) {
                    $content = mb_strtolower($m['content'] ?? '');
                    foreach ($keywords as $kw) {
                        if (mb_stripos($content, mb_strtolower($kw)) !== false) {
                            $recentNegative++;
                            break;
                        }
                    }
                }
                if ($recentNegative >= 2) {
                    $reasons[] = 'repeated_negative';
                    $confidenceScore += 20;
                    $moodLevel = max($moodLevel, 4);
                }
            }
        }

        // 5. Off-topic detection (ใช้ heuristic อย่างหยาบ — classifier จะแม่นกว่า)
        // ถ้าไม่มีคำที่เกี่ยวกับดูดวงเลย + ไม่มี trigger ของ sensitive
        $fortuneKeywords = ['ดวง', 'ทำนาย', 'ไพ่', 'หมอ', 'ราศี', 'ฤกษ์', 'เลข', 'ฝัน', 'ความรัก', 'การงาน', 'การเงิน', 'สุขภาพ', 'อนาคต', 'จันทรา'];
        $hasFortuneKeyword = false;
        foreach ($fortuneKeywords as $fk) {
            if (mb_stripos($msgLower, $fk) !== false) {
                $hasFortuneKeyword = true;
                break;
            }
        }

        // ข้อความยาวเกิน 30 ตัว + ไม่มีคำดูดวง + ไม่มี trigger → อาจ off-topic
        if (! $hasFortuneKeyword && $msgLen > 30 && empty($reasons)) {
            $isOfftopic = true; // tentative — classifier จะตัดสินสุดท้าย
            $reasons[] = 'maybe_offtopic';
        }

        $confidenceScore = min(100, $confidenceScore);

        return [
            'is_sensitive' => $confidenceScore >= 50,
            'is_offtopic' => $isOfftopic,
            'confidence' => $confidenceScore,
            'mood_level' => $moodLevel,
            'complexity' => $complexity,
            'reasons' => $reasons,
        ];
    }

    /**
     * 🛑 (2026-05-07 review L11) Circuit breaker constants
     */
    protected const CIRCUIT_BREAKER_KEY = 'fortune:classifier:fails';

    protected const CIRCUIT_BREAKER_THRESHOLD = 3;     // fails ใน window → trip

    protected const CIRCUIT_BREAKER_WINDOW_SEC = 60;   // นับ fails ใน window นี้

    protected const CIRCUIT_BREAKER_OPEN_SEC = 300;    // 5 นาที skip classifier

    /**
     * 🛑 เช็คว่า circuit breaker เปิดอยู่ (skip classifier)
     */
    protected function isCircuitOpen(): bool
    {
        return (bool) Cache::get(self::CIRCUIT_BREAKER_KEY.':open', false);
    }

    /**
     * 🛑 บันทึก classifier failure — ถ้าเกิน threshold ใน window → trip circuit
     */
    protected function recordClassifierFailure(): void
    {
        $key = self::CIRCUIT_BREAKER_KEY.':count';
        Cache::add($key, 0, self::CIRCUIT_BREAKER_WINDOW_SEC);
        $count = Cache::increment($key, 1);

        if ($count >= self::CIRCUIT_BREAKER_THRESHOLD) {
            Cache::put(self::CIRCUIT_BREAKER_KEY.':open', true, self::CIRCUIT_BREAKER_OPEN_SEC);
            Log::warning('Fortune Classifier: circuit breaker OPEN (skip ใน '.(self::CIRCUIT_BREAKER_OPEN_SEC / 60).' นาที)', [
                'fails_in_window' => $count,
            ]);
            // reset counter หลัง trip
            Cache::forget($key);
        }
    }

    /**
     * 🤖 Groq classifier — รวม mood + complexity + offtopic ใน 1 call
     *
     * ใช้ llama-3.1-8b-instant (default) — ฟรี เร็ว ~100ms
     *
     * 🛑 (2026-05-07 review L11) — circuit breaker:
     *   ถ้า classifier fail >= 3 ครั้งใน 60s → skip ทันทีในรอบถัดไป (5 นาที)
     *   ป้องกัน Groq outage ทำให้ chat แต่ละ message รอนาน
     *
     * @return array ['is_sensitive', 'is_offtopic', 'mood_level', 'complexity', 'confidence', 'reason']
     *
     * @throws Exception
     */
    protected function runClassifier(string $message, array $context = []): array
    {
        // 🛑 Circuit breaker check
        if ($this->isCircuitOpen()) {
            throw new Exception('Classifier circuit breaker open — skip');
        }

        // Cache classifier result 5 นาที (dedupe เคสคนพิมพ์ซ้ำ)
        $hash = hash('sha256', mb_strtolower(trim($message)));
        $cacheKey = "fortune:sensitive:classifier:{$hash}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $provider = $this->settings->sensitive_classifier_provider ?? 'groq';
        // 🎯 (2026-05-24) Default ใช้ llama-3.3-70b-versatile (TPM 12000) ดีกว่า 8b-instant (TPM 6000)
        //    ลด 413 "Request too large" ที่เกิดเมื่อหลาย classifier call พร้อมกัน
        $model = $this->settings->sensitive_classifier_model ?? 'llama-3.3-70b-versatile';

        $endpoint = match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => throw new Exception("Provider {$provider} ไม่รองรับ classifier"),
        };

        $systemPrompt = <<<'PROMPT'
You are a classifier analyzing customer messages to a Thai fortune-telling bot.

Output strict JSON only — no prose.

Schema:
{
  "mood_level": 1-5,        // 1=calm, 5=hostile/abusive
  "complexity": 1-5,        // 1=simple, 5=very complex
  "is_offtopic": true|false, // true if NOT about fortune/horoscope/tarot
  "reason": "<2-5 word tag, lowercase, snake_case>"
}

Rules:
- Profanity/abuse → mood_level >= 4
- Multi-question / contradiction / sensitive topics (death, divorce, illness) → complexity >= 4
- Greetings, small talk, gratitude → is_offtopic=false (still on-topic for fortune chat)
- Asking about products, weather, math, coding → is_offtopic=true
- Lao language → analyze same way
PROMPT;

        $payload = [
            'model' => $model,
            'temperature' => 0.0,
            'max_tokens' => 150,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Classify: \"{$message}\""],
            ],
        ];

        // 🪙 (2026-05-24 Phase 6) Pre-flight: ประมาณ token + filter key ที่ใกล้ทะลุ TPM
        //   เดิม: detector ขอ key 1 ตัวจาก pool → ส่ง request → 413 → exception
        //   ใหม่: loop max 3 keys, mark TPM saturated เมื่อ 413, ลอง key ถัดไป
        $estimatedTokens = \App\Services\AiApiKeyPoolService::estimateTokens($systemPrompt)
            + \App\Services\AiApiKeyPoolService::estimateTokens($message)
            + 150;  // + max_tokens

        $pool = new \App\Services\AiApiKeyPoolService;
        $response = null;
        $lastError = null;
        $triedKeyIds = [];

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $keyObj = $this->acquireClassifierKey($pool, $provider, $estimatedTokens, $triedKeyIds);
            if (! $keyObj) {
                // ไม่มี key เหลือ — fall back to legacy single-key
                $apiKey = $this->getClassifierApiKey($provider);
                if (empty($apiKey)) {
                    throw new Exception("Classifier API key หายไปสำหรับ provider={$provider} (attempt {$attempt})");
                }
                $response = Http::withToken($apiKey)->timeout(5)->retry(1, 200)->post($endpoint, $payload);
                break;
            }

            $triedKeyIds[] = $keyObj->id;
            $response = Http::withToken($keyObj->api_key)->timeout(5)->retry(1, 200)->post($endpoint, $payload);

            // 413/429 → mark TPM saturated + try next key
            if (! $response->successful() && in_array($response->status(), [413, 429], true)) {
                $errMsg = "HTTP {$response->status()}: ".mb_substr($response->body(), 0, 200);
                Log::warning('Classifier: key TPM/rate-limit hit, rotating', [
                    'key_id' => $keyObj->id,
                    'status' => $response->status(),
                    'attempt' => $attempt,
                ]);
                $pool->markTpmSaturated($keyObj, 60);
                $keyObj->recordSmartError($errMsg, $model, true, 60);
                $lastError = $errMsg;
                $response = null;

                continue;
            }
            break;
        }

        if ($response === null || ! $response->successful()) {
            $this->recordClassifierFailure();
            $statusInfo = $response ? "HTTP {$response->status()}: ".$response->body() : ($lastError ?? 'no response');
            throw new Exception("Classifier {$statusInfo}");
        }

        $body = $response->json();
        $raw = $body['choices'][0]['message']['content'] ?? '';
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new Exception('Classifier ตอบไม่ใช่ JSON: '.mb_substr($raw, 0, 200));
        }

        $mood = (int) max(1, min(5, $decoded['mood_level'] ?? 1));
        $complexity = (int) max(1, min(5, $decoded['complexity'] ?? 1));
        $isOfftopic = (bool) ($decoded['is_offtopic'] ?? false);
        $reason = (string) ($decoded['reason'] ?? 'classifier');

        // Sensitive ถ้า mood>=4 หรือ complexity>=4
        $isSensitive = $mood >= 4 || $complexity >= 4;

        // Confidence — เอา max(mood, complexity) * 20 = 0-100
        $confidence = max($mood, $complexity) * 20;

        $result = [
            'is_sensitive' => $isSensitive,
            'is_offtopic' => $isOfftopic,
            'mood_level' => $mood,
            'complexity' => $complexity,
            'confidence' => $confidence,
            'reason' => $reason,
        ];

        Cache::put($cacheKey, $result, self::CLASSIFIER_CACHE_TTL);

        return $result;
    }

    /**
     * ดึง API key สำหรับ classifier
     *
     * Priority:
     *   1. AiApiKey pool (purpose='chat' เท่านั้น) — มี rotation/quota
     *      🚫 (2026-05-23) ลบ 'any' fallback — Pool ไม่ pick 'any' แล้ว
     *   2. settings->chat_ai_api_key (ถ้าเป็น Groq อยู่แล้ว)
     */
    protected function getClassifierApiKey(string $provider): ?string
    {
        // ลอง pool ก่อน
        try {
            $pool = new \App\Services\AiApiKeyPoolService;
            $key = $pool->acquireKey($provider, 'chat');
            if ($key) {
                // คืน key ทันที (ไม่ hold inflight) เพื่อไม่ block prediction
                $pool->releaseKey($provider, $key->id);

                return $key->api_key;
            }
        } catch (Exception $e) {
            // pool ใช้ไม่ได้ — fallback
        }

        // Fallback: settings chat_ai_api_key (ถ้าเป็น provider เดียวกัน)
        if (($this->settings->chat_ai_provider ?? null) === $provider) {
            return $this->settings->getChatAIApiKey();
        }

        return null;
    }

    /**
     * 🪙 (2026-05-24 Phase 6) ขอ key จาก pool พร้อม TPM pre-flight check
     *
     * - skip key ที่อยู่ใน triedKeyIds (กัน loop ใช้ key เดิม)
     * - skip key ที่ TPM ใกล้เต็มเมื่อบวก estimatedTokens
     *
     * @param  array<int>  $triedKeyIds
     */
    protected function acquireClassifierKey(
        \App\Services\AiApiKeyPoolService $pool,
        string $provider,
        int $estimatedTokens,
        array $triedKeyIds = []
    ): ?\App\Models\AiApiKey {
        try {
            // ขอหลาย key มากกว่า 1 เผื่อหลีกเลี่ยงตัวที่ TPM ใกล้เต็ม
            for ($i = 0; $i < 5; $i++) {
                $key = $pool->acquireKey($provider, 'chat');
                if (! $key) {
                    return null;
                }
                $pool->releaseKey($provider, $key->id);

                if (in_array($key->id, $triedKeyIds, true)) {
                    continue; // ลองตัวอื่น
                }

                // 🪙 pre-flight TPM check
                if ($pool->requestExceedsTpm($key, $estimatedTokens)) {
                    Log::info('Classifier: skip key — request would exceed TPM', [
                        'key_id' => $key->id,
                        'estimated_tokens' => $estimatedTokens,
                    ]);

                    continue;
                }

                return $key;
            }
        } catch (Exception $e) {
            Log::warning('Classifier: acquireClassifierKey failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
