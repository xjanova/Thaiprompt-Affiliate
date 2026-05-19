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
 *   2. Load active Q&A rows ที่มี embedding (ทั้งหมด)
 *   3. Cosine similarity per row → sort DESC → return top-K ที่ ≥ threshold
 *   4. Cache 5 นาที per query (ลูกค้าพิมพ์ซ้ำ → ไม่ต้อง embed ใหม่)
 *
 * Performance:
 *   - <10k rows: ~50ms PHP (acceptable)
 *   - >10k rows: ควรย้ายไป pgvector หรือ external vector store
 *
 * Failure handling: คืน [] ถ้า embedding/DB fail (caller fall back ไป AI ปกติ)
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
     * @return array<int,array{qa:FortuneAdminQA,similarity:float}> [{qa, similarity}, ...]
     */
    public function searchSimilar(string $queryText, int $topK = 3, float $threshold = 0.78): array
    {
        $queryText = trim($queryText);
        if ($queryText === '') {
            return [];
        }

        // 1) Cache check (query + topK + threshold เป็น key)
        $cacheKey = self::CACHE_PREFIX.hash('sha256', $queryText."|{$topK}|{$threshold}");
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

        // 3) Load active rows + cosine similarity
        try {
            $rows = FortuneAdminQA::active()->hasEmbedding()->get();
            if ($rows->isEmpty()) {
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

            // 6) Record hit (เพิ่ม counter — ทำใน background ไม่ block)
            foreach ($top as $r) {
                try {
                    $r['qa']->recordHit();
                } catch (\Throwable $e) {
                    // ignore — non-critical
                }
            }

            return $top;
        } catch (\Throwable $e) {
            Log::warning('AdminQARetriever: search ล้มเหลว', [
                'error' => $e->getMessage(),
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
