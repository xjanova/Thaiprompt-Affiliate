<?php

namespace App\Services\TrendDetection;

use App\Models\TrendData;
use App\Models\TrendKeyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KeywordAnalyzerService
{
    protected array $stopWords = [
        'the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 'in', 'with', 'to', 'for', 'of', 'as', 'by',
        'ที่', 'การ', 'ใน', 'และ', 'ของ', 'เป็น', 'ได้', 'มี', 'จาก', 'ว่า', 'ไป', 'มา', 'นี้', 'นั้น', 'เพื่อ', 'กับ', 'ก็',
        'ให้', 'ถ้า', 'เพราะ', 'แต่', 'หรือ', 'โดย', 'ซึ่ง', 'ตั้ง', 'อยู่', 'อย่าง', 'ไว้', 'แล้ว', 'ยัง', 'คือ', 'ด้วย',
    ];

    protected int $minKeywordLength = 2;
    protected int $maxKeywordLength = 50;

    /**
     * Extract keywords from trend data
     */
    public function extractKeywords(TrendData $trendData): array
    {
        $text = $this->prepareText($trendData);

        // Extract words
        $words = $this->tokenize($text);

        // Filter and clean
        $keywords = $this->filterKeywords($words);

        // Calculate TF-IDF
        $keywordsWithWeight = $this->calculateTFIDF($keywords, $trendData);

        // Store keywords
        $this->storeKeywords($trendData, $keywordsWithWeight);

        return $keywordsWithWeight;
    }

    /**
     * Prepare text for analysis
     */
    protected function prepareText(TrendData $trendData): string
    {
        $text = implode(' ', array_filter([
            $trendData->title,
            $trendData->content,
        ]));

        // Remove HTML tags
        $text = strip_tags($text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Tokenize text into words
     */
    protected function tokenize(string $text): array
    {
        // Split by whitespace and punctuation
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Filter and clean keywords
     */
    protected function filterKeywords(array $words): array
    {
        $keywords = [];

        foreach ($words as $word) {
            $word = mb_strtolower(trim($word));

            // Skip if too short or too long
            $length = mb_strlen($word);
            if ($length < $this->minKeywordLength || $length > $this->maxKeywordLength) {
                continue;
            }

            // Skip stop words
            if (in_array($word, $this->stopWords)) {
                continue;
            }

            // Skip numbers only
            if (is_numeric($word)) {
                continue;
            }

            $keywords[] = $word;
        }

        return $keywords;
    }

    /**
     * Calculate TF-IDF for keywords
     */
    protected function calculateTFIDF(array $keywords, TrendData $trendData): array
    {
        // Calculate term frequency
        $termFrequency = array_count_values($keywords);
        $maxFreq = max($termFrequency);

        // Get total documents
        $totalDocs = TrendData::count();

        $keywordsWithWeight = [];

        foreach ($termFrequency as $term => $freq) {
            // Normalized TF
            $tf = $freq / $maxFreq;

            // Document frequency (how many documents contain this term)
            $df = TrendData::whereJsonContains('extracted_keywords', $term)->count();
            $df = max(1, $df); // Avoid division by zero

            // IDF
            $idf = log($totalDocs / $df);

            // TF-IDF
            $weight = round($tf * $idf, 4);

            $keywordsWithWeight[$term] = [
                'keyword' => $term,
                'weight' => $weight,
                'frequency' => $freq,
            ];
        }

        // Sort by weight descending
        usort($keywordsWithWeight, function ($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });

        return $keywordsWithWeight;
    }

    /**
     * Store keywords in database
     */
    protected function storeKeywords(TrendData $trendData, array $keywordsWithWeight): void
    {
        DB::beginTransaction();

        try {
            $extractedKeywords = [];

            foreach ($keywordsWithWeight as $item) {
                $keyword = TrendKeyword::findOrCreateKeyword($item['keyword']);

                // Increment frequency
                $keyword->incrementFrequency($item['frequency']);

                // Attach to trend data with weight
                $trendData->keywords()->syncWithoutDetaching([
                    $keyword->id => ['weight' => $item['weight']]
                ]);

                $extractedKeywords[] = $item['keyword'];
            }

            // Store extracted keywords in trend data
            $trendData->update([
                'extracted_keywords' => $extractedKeywords,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Analyze all unanalyzed trend data
     */
    public function analyzeUnprocessed(): array
    {
        $unprocessed = TrendData::whereNull('extracted_keywords')
            ->orWhereJsonLength('extracted_keywords', 0)
            ->limit(100)
            ->get();

        $results = [];

        foreach ($unprocessed as $trendData) {
            try {
                $keywords = $this->extractKeywords($trendData);
                $results[] = [
                    'id' => $trendData->id,
                    'success' => true,
                    'keyword_count' => count($keywords),
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $trendData->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Update trend scores for all keywords
     */
    public function updateTrendScores(): void
    {
        $keywords = TrendKeyword::all();

        foreach ($keywords as $keyword) {
            $keyword->updateTrendMetrics();
        }
    }

    /**
     * Get trending keywords
     */
    public function getTrendingKeywords(int $limit = 50, int $minScore = 10): array
    {
        return TrendKeyword::trending($minScore)
            ->limit($limit)
            ->get()
            ->map(function ($keyword) {
                return [
                    'keyword' => $keyword->keyword,
                    'trend_score' => $keyword->trend_score,
                    'frequency' => $keyword->frequency,
                    'growth_rate' => $keyword->growth_rate,
                    'last_seen' => $keyword->last_seen_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Find related keywords using co-occurrence
     */
    public function findRelatedKeywords(TrendKeyword $keyword, int $limit = 10): array
    {
        // Get trend data that contains this keyword
        $trendDataIds = $keyword->trendData()->pluck('trend_data.id');

        // Find other keywords that appear in the same trend data
        $related = DB::table('trend_data_keyword')
            ->select('trend_keyword_id', DB::raw('COUNT(*) as co_occurrence'))
            ->whereIn('trend_data_id', $trendDataIds)
            ->where('trend_keyword_id', '!=', $keyword->id)
            ->groupBy('trend_keyword_id')
            ->orderBy('co_occurrence', 'desc')
            ->limit($limit)
            ->get();

        $relatedKeywords = [];

        foreach ($related as $item) {
            $relatedKeyword = TrendKeyword::find($item->trend_keyword_id);
            if ($relatedKeyword) {
                $relatedKeywords[] = [
                    'keyword' => $relatedKeyword->keyword,
                    'co_occurrence' => $item->co_occurrence,
                    'trend_score' => $relatedKeyword->trend_score,
                ];
            }
        }

        // Update related keywords in the original keyword
        $keyword->update([
            'related_keywords' => collect($relatedKeywords)->pluck('keyword')->toArray(),
        ]);

        return $relatedKeywords;
    }

    /**
     * Detect emerging keywords (new keywords with high growth)
     */
    public function detectEmergingKeywords(int $hoursBack = 24, int $limit = 20): array
    {
        return TrendKeyword::where('first_seen_at', '>=', now()->subHours($hoursBack))
            ->growing(50) // At least 50% growth
            ->orderBy('growth_rate', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($keyword) {
                return [
                    'keyword' => $keyword->keyword,
                    'trend_score' => $keyword->trend_score,
                    'growth_rate' => $keyword->growth_rate,
                    'frequency' => $keyword->frequency,
                    'age_hours' => now()->diffInHours($keyword->first_seen_at),
                ];
            })
            ->toArray();
    }
}
