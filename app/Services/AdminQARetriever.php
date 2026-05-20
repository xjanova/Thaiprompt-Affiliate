<?php

namespace App\Services;

use App\Models\FortuneAdminQA;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AdminQARetriever
 *
 * ค้นหา admin Q&A pairs ที่คล้ายกับคำถามลูกค้า (cosine similarity top-K)
 *
 * Strategy:
 *   1. Embed customer query → float vector 768
 *   2. Pre-filter ตาม category (ถ้ามี) → กรอง subset ก่อน loop = เร็วขึ้น 5-10x
 *   3. Load rows ที่ active + has embedding + recent (≤ 6 เดือน)
 *   4. Cosine similarity per row → sort DESC → return top-K ที่ ≥ threshold
 *   5. Cache 5 นาที per (query, category) → query ซ้ำ → ไม่ต้อง embed/load ใหม่
 *
 * Performance:
 *   - กระจาย 8 category × <5k rows total → cosine loop เฉลี่ย ~600 rows = ~30ms PHP
 *   - >10k rows ต่อ category → ควรย้าย pgvector / Redis cache full set
 *
 * Failure handling:
 *   - คืน [] ถ้า embedding/DB fail (caller fall back ไป AI ปกติ)
 *   - ถ้า retrieve > 800ms → log warning (defensive alarm)
 */
class AdminQARetriever
{
    /**
     * Cache TTL (5 นาที) — query เดียวกัน customer ส่งซ้ำ ไม่ต้อง embed
     */
    private const QUERY_CACHE_TTL = 300;

    /**
     * Cache prefix
     */
    private const CACHE_PREFIX = 'admin_qa:retrieve:';

    /**
     * เก็บข้อมูลย้อนหลังกี่เดือน (ตัดทิ้ง rows เก่าตอน retrieve)
     *
     * ป้องกัน rows สะสมเยอะเกินจน cosine ช้า
     * Admin ลบ/disable เองได้ผ่าน UI ถ้าอยากเก็บนาน
     */
    private const RECENT_MONTHS = 6;

    /**
     * Threshold alarm — ถ้า retrieve ใช้เวลานานเกิน ms นี้ log warning
     */
    private const SLOW_RETRIEVE_THRESHOLD_MS = 800;

    protected GeminiEmbeddingService $embedding;

    public function __construct(?GeminiEmbeddingService $embedding = null)
    {
        $this->embedding = $embedding ?? new GeminiEmbeddingService;
    }

    /**
     * ค้นหา Q&A ที่คล้าย query → return top-K
     *
     * @param  string  $queryText  ข้อความค้นหา (customer message)
     * @param  int  $topK  จำนวน top results (default 3)
     * @param  float  $threshold  cosine ≥ ค่านี้ ถึงนับ (default 0.78)
     * @param  string|null  $category  pre-filter category (null = ไม่ filter)
     * @return array<int,array{qa:FortuneAdminQA,similarity:float}> [{qa, similarity}, ...]
     */
    public function searchSimilar(
        string $queryText,
        int $topK = 3,
        float $threshold = 0.78,
        ?string $category = null,
    ): array {
        $startTime = microtime(true);

        $queryText = trim($queryText);
        if ($queryText === '') {
            return [];
        }

        // 1) Cache check (query + topK + threshold + category เป็น key)
        $cacheKey = self::CACHE_PREFIX.hash('sha256', $queryText."|{$topK}|{$threshold}|".($category ?? 'all'));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $this->hydrateCached($cached);
        }

        // 2) Embed query
        $queryVector = $this->embedding->embed($queryText);
        if ($queryVector === null) {
            Log::debug('AdminQARetriever: embed query ล้มเหลว', [
                'query_preview' => mb_substr($queryText, 0, 50),
            ]);

            return [];
        }

        // 3) Load active + has embedding + recent + (optional) category
        try {
            $query = FortuneAdminQA::active()
                ->hasEmbedding()
                ->recent(self::RECENT_MONTHS);

            // 🏷 Pre-filter by category — เร็วขึ้น 5-10x เมื่อ data โต
            if ($category !== null && $category !== '') {
                $query->byCategory($category);
            }

            $rows = $query->get();
            if ($rows->isEmpty()) {
                $this->logIfSlow($startTime, 0, $category, 'empty_result');

                return [];
            }

            $scored = [];
            foreach ($rows as $row) {
                $emb = $row->q_embedding;
                if (! is_array($emb) || count($emb) !== GeminiEmbeddingService::DIMENSION) {
                    continue;
                }

                $sim = GeminiEmbeddingService::cosineSimilarity($queryVector, $emb);

                // ใช้ row-level threshold ถ้ามี ไม่งั้นใช้ caller threshold
                $rowThreshold = $row->similarity_threshold !== null
                    ? (float) $row->similarity_threshold
                    : $threshold;

                if ($sim >= $rowThreshold) {
                    $scored[] = ['qa' => $row, 'similarity' => $sim];
                }
            }

            // 4) Sort DESC by similarity → take topK
            usort($scored, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);
            $top = array_slice($scored, 0, $topK);

            // 5) Cache (เก็บแค่ id + similarity)
            $cacheable = array_map(fn ($r) => [
                'qa_id' => $r['qa']->id,
                'similarity' => $r['similarity'],
            ], $top);
            Cache::put($cacheKey, $cacheable, self::QUERY_CACHE_TTL);

            // 6) Record hit (เพิ่ม counter — non-critical)
            foreach ($top as $r) {
                try {
                    $r['qa']->recordHit();
                } catch (\Throwable $e) {
                    // ignore — non-critical
                }
            }

            // 7) Defensive alarm — log ถ้าช้าผิดปกติ
            $this->logIfSlow($startTime, $rows->count(), $category, 'success');

            return $top;
        } catch (\Throwable $e) {
            Log::warning('AdminQARetriever: search ล้มเหลว', [
                'error' => $e->getMessage(),
                'category' => $category,
            ]);

            return [];
        }
    }

    /**
     * Hydrate cached IDs → full QA records
     *
     * @param  array<int,array{qa_id:int,similarity:float}>  $cached
     * @return array<int,array{qa:FortuneAdminQA,similarity:float}>
     */
    protected function hydrateCached(array $cached): array
    {
        if (empty($cached)) {
            return [];
        }

        $ids = array_map(fn ($c) => $c['qa_id'] ?? null, $cached);
        $ids = array_filter($ids);
        if (empty($ids)) {
            return [];
        }

        $rows = FortuneAdminQA::whereIn('id', $ids)->get()->keyBy('id');
        $result = [];
        foreach ($cached as $c) {
            $qa = $rows->get($c['qa_id']);
            if ($qa && $qa->is_active) {
                $result[] = ['qa' => $qa, 'similarity' => (float) $c['similarity']];
            }
        }

        return $result;
    }

    /**
     * Defensive alarm — log warning ถ้า retrieve ช้าเกิน threshold
     */
    protected function logIfSlow(float $startTime, int $rowsScanned, ?string $category, string $status): void
    {
        $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);
        if ($elapsedMs >= self::SLOW_RETRIEVE_THRESHOLD_MS) {
            Log::warning('AdminQARetriever: slow retrieve', [
                'elapsed_ms' => $elapsedMs,
                'rows_scanned' => $rowsScanned,
                'category' => $category,
                'status' => $status,
                'threshold_ms' => self::SLOW_RETRIEVE_THRESHOLD_MS,
            ]);
        }
    }

    /**
     * Format top-K results → system prompt few-shot block
     *
     * Output:
     *   📚 ตัวอย่างคำตอบของแอดมินสำหรับคำถามคล้ายกัน:
     *
     *   Q: {q_text}
     *   A: {a_text}
     *   ...
     *
     * @param  array<int,array{qa:FortuneAdminQA,similarity:float}>  $results
     */
    public static function formatAsFewShot(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $lines = [];
        $lines[] = '📚 ตัวอย่างคำตอบของแอดมินสำหรับคำถามคล้ายกัน (อ้างอิงสไตล์และเนื้อหา แต่ปรับให้เหมาะกับลูกค้าปัจจุบัน):';
        $lines[] = '';

        foreach ($results as $idx => $r) {
            $num = $idx + 1;
            $q = mb_substr(trim((string) $r['qa']->q_text), 0, 300);
            $a = mb_substr(trim((string) $r['qa']->a_text), 0, 500);
            $sim = number_format($r['similarity'] * 100, 1);

            $lines[] = "ตัวอย่างที่ {$num} (ความคล้าย {$sim}%):";
            $lines[] = "Q: {$q}";
            $lines[] = "A: {$a}";
            $lines[] = '';
        }

        $lines[] = '⚠️ หมายเหตุ: ตอบในแนวสไตล์ของแอดมิน แต่อย่า copy ตรงๆ — ปรับให้เข้ากับคำถามและบริบทล่าสุด';

        return implode("\n", $lines);
    }
}
