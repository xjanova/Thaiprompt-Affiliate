<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiEmbeddingService
 *
 * Wrapper เรียก Gemini text-embedding-004 API → return float vector (768-dim)
 *
 * Use case:
 *   - RAG admin Q&A — embed question text เก็บใน DB
 *   - Retrieve — embed customer query → cosine vs stored embeddings
 *
 * Cost: text-embedding-004 ฟรี (Gemini free tier 1,500 RPM)
 *
 * Strategy:
 *   1. ใช้ Pool key — purpose='embedding' ก่อน, fallback 'any'
 *   2. Cache 1 ชั่วโมง per text hash (กัน embed ซ้ำ)
 *   3. Failure ไม่ throw — return null ให้ caller handle
 *
 * @see https://ai.google.dev/api/embeddings#method:-models.embedcontent
 */
class GeminiEmbeddingService
{
    /**
     * Model name
     */
    public const MODEL = 'text-embedding-004';

    /**
     * Dimension ของ embedding (768)
     */
    public const DIMENSION = 768;

    /**
     * Cache TTL (1 ชั่วโมง) — text เดิม embed แล้วไม่ต้องทำซ้ำ
     */
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * HTTP timeout (วินาที)
     */
    private const HTTP_TIMEOUT = 15;

    /**
     * Cache prefix
     */
    private const CACHE_PREFIX = 'gemini:embedding:';

    protected AiApiKeyPoolService $pool;

    public function __construct(?AiApiKeyPoolService $pool = null)
    {
        $this->pool = $pool ?? new AiApiKeyPoolService;
    }

    /**
     * Embed ข้อความ → float vector 768-dim
     *
     * @param  string  $text  ข้อความที่จะ embed
     * @return array<int,float>|null vector 768 floats หรือ null ถ้าล้มเหลว
     */
    public function embed(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // 1) ลอง cache ก่อน (text เดิม → ไม่ต้อง API call)
        $cacheKey = self::CACHE_PREFIX.hash('sha256', $text);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && count($cached) === self::DIMENSION) {
            return $cached;
        }

        // 2) ดึง Gemini key จาก Pool — ลอง embedding purpose ก่อน → fallback 'any'
        $apiKey = $this->acquireGeminiKey();
        if (! $apiKey) {
            Log::warning('GeminiEmbeddingService: ไม่มี Gemini key พร้อมใช้ใน Pool', [
                'text_preview' => mb_substr($text, 0, 50),
            ]);

            return null;
        }

        // 3) เรียก Gemini embedContent endpoint
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                .self::MODEL
                .':embedContent?key='.$apiKey;

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->post($url, [
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                ]);

            if (! $response->successful()) {
                $body = $response->json();
                $err = $body['error']['message'] ?? $response->body();
                Log::warning('GeminiEmbeddingService: API error', [
                    'status' => $response->status(),
                    'error' => mb_substr((string) $err, 0, 200),
                ]);

                return null;
            }

            $data = $response->json();
            $vector = $data['embedding']['values'] ?? null;

            if (! is_array($vector) || count($vector) !== self::DIMENSION) {
                Log::warning('GeminiEmbeddingService: vector รูปแบบผิด', [
                    'dimension' => is_array($vector) ? count($vector) : null,
                ]);

                return null;
            }

            // Cast เป็น float ทุกตัว
            $vector = array_map('floatval', $vector);

            // 4) Cache + return
            Cache::put($cacheKey, $vector, self::CACHE_TTL_SECONDS);

            return $vector;
        } catch (Exception $e) {
            Log::warning('GeminiEmbeddingService: ล้มเหลว', [
                'error' => $e->getMessage(),
                'text_preview' => mb_substr($text, 0, 50),
            ]);

            return null;
        }
    }

    /**
     * Cosine similarity ระหว่าง 2 vectors
     *
     * @param  array<int,float>  $a
     * @param  array<int,float>  $b
     * @return float -1 ถึง 1 (1 = เหมือนเด๊ะ, 0 = ไม่เกี่ยว, -1 = ตรงข้าม)
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $av = (float) $a[$i];
            $bv = (float) $b[$i];
            $dot += $av * $bv;
            $normA += $av * $av;
            $normB += $bv * $bv;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * ดึง Gemini API key จาก Pool
     *
     * 🚫 (2026-05-23) ลบ 'any' fallback ออกแล้ว — User spec: "เอา purpose any ออกไปเลย"
     *    เหตุผล: purpose='any' เป็นรูรั่ว — caller ใดๆ ที่ purpose specific หมด/429
     *    จะ fallback ไป key 'any' (เช่น OpenAI key 'any' priority สูง) → เผา quota แพง
     *
     * Order ใหม่: purpose='embedding' ก่อน → null (legacy generic) เท่านั้น
     */
    protected function acquireGeminiKey(): ?string
    {
        // 1) ลอง embedding purpose ก่อน (admin ตั้ง key เจาะจง)
        $key = $this->pool->acquireKey('gemini', 'embedding');
        if ($key) {
            return $key->api_key;
        }

        // 2) Last resort — generic (caller=null → Pool จะเลือก specific tier 1 ก่อน)
        //    Note: acquireKeyAnyProvider(null) จะ exclude sensitive/tts ตาม v5.1
        $key = $this->pool->acquireKey('gemini', null);

        return $key?->api_key;
    }
}
