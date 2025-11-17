<?php

namespace App\Services;

use App\Models\KeywordABTest;
use App\Models\KeywordABTestResult;
use App\Models\KeywordABTestVariant;
use App\Models\LineBotKeyword;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keyword A/B Test Service
 *
 * จัดการการทดสอบ A/B สำหรับคีย์เวิร์ด
 */
class KeywordABTestService
{
    /**
     * สร้าง A/B test ใหม่
     *
     * @param array $data ข้อมูล test
     * @return KeywordABTest
     *
     * @throws \Exception
     */
    public function createTest(array $data): KeywordABTest
    {
        return DB::transaction(function () use ($data) {
            // สร้าง A/B test
            $test = KeywordABTest::create([
                'keyword_id' => $data['keyword_id'],
                'test_name' => $data['test_name'],
                'description' => $data['description'] ?? null,
                'status' => 'planning',
                'variant_a_percentage' => $data['variant_a_percentage'] ?? 50,
                'variant_b_percentage' => $data['variant_b_percentage'] ?? 50,
                'winning_criterion' => $data['winning_criterion'] ?? 'conversion_rate',
                'minimum_samples' => $data['minimum_samples'] ?? 100,
                'config' => $data['config'] ?? null,
            ]);

            // สร้าง variants
            KeywordABTestVariant::create([
                'ab_test_id' => $test->id,
                'variant_type' => 'variant_a',
                'response_text' => $data['variant_a']['response_text'],
                'trigger_words' => $data['variant_a']['trigger_words'] ?? null,
                'response_type' => $data['variant_a']['response_type'] ?? 'text',
                'description' => $data['variant_a']['description'] ?? null,
                'additional_data' => $data['variant_a']['additional_data'] ?? null,
            ]);

            KeywordABTestVariant::create([
                'ab_test_id' => $test->id,
                'variant_type' => 'variant_b',
                'response_text' => $data['variant_b']['response_text'],
                'trigger_words' => $data['variant_b']['trigger_words'] ?? null,
                'response_type' => $data['variant_b']['response_type'] ?? 'text',
                'description' => $data['variant_b']['description'] ?? null,
                'additional_data' => $data['variant_b']['additional_data'] ?? null,
            ]);

            // Log creation
            activity()
                ->performedOn($test)
                ->log('สร้าง A/B test ใหม่: ' . $test->test_name);

            return $test->fresh(['variants']);
        });
    }

    /**
     * เริ่มทดสอบ
     *
     * @param KeywordABTest $test
     * @param \Carbon\Carbon|null $startTime
     * @return KeywordABTest
     */
    public function startTest(KeywordABTest $test, $startTime = null): KeywordABTest
    {
        $test->update([
            'status' => 'active',
            'started_at' => $startTime ?? now(),
        ]);

        activity()
            ->performedOn($test)
            ->log('เริ่มการทดสอบ: ' . $test->test_name);

        return $test;
    }

    /**
     * หยุดทดสอบ
     *
     * @param KeywordABTest $test
     * @param string $reason เหตุผล
     * @return KeywordABTest
     */
    public function pauseTest(KeywordABTest $test, string $reason = ''): KeywordABTest
    {
        $test->update(['status' => 'paused']);

        activity()
            ->performedOn($test)
            ->log('หยุดการทดสอบ' . ($reason ? ': ' . $reason : ''));

        return $test;
    }

    /**
     * จบการทดสอบ
     *
     * @param KeywordABTest $test
     * @return KeywordABTest
     */
    public function completeTest(KeywordABTest $test): KeywordABTest
    {
        return DB::transaction(function () use ($test) {
            // วิเคราะห์ผลลัพธ์
            $results = $this->analyzeResults($test);

            $test->update([
                'status' => 'completed',
                'ended_at' => now(),
                'winner' => $results['winner'],
                'winner_confidence' => $results['winner_confidence'],
                'results' => $results,
            ]);

            activity()
                ->performedOn($test)
                ->log('จบการทดสอบ - ผู้ชนะ: ' . ($results['winner'] ?? 'ไม่ได้กำหนด'));

            return $test;
        });
    }

    /**
     * เลือกตัวแปร (variant routing)
     *
     * เลือก variant A หรือ B โดยอิงจากเปอร์เซ็นต์
     *
     * @param KeywordABTest $test
     * @return string variant_a หรือ variant_b
     */
    public function selectVariant(KeywordABTest $test): string
    {
        $random = rand(1, 100);

        return $random <= $test->variant_a_percentage ? 'variant_a' : 'variant_b';
    }

    /**
     * บันทึกผลการทดสอบ
     *
     * @param KeywordABTest $test
     * @param array $data ข้อมูลผล
     * @return KeywordABTestResult
     */
    public function recordResult(KeywordABTest $test, array $data): KeywordABTestResult
    {
        $result = KeywordABTestResult::create([
            'ab_test_id' => $test->id,
            'variant_id' => $data['variant_id'],
            'line_user_id' => $data['line_user_id'],
            'user_message' => $data['user_message'],
            'variant_served' => $data['variant_served'],
            'response_time' => $data['response_time'] ?? 0,
            'matched' => $data['matched'] ?? true,
            'interacted' => $data['interacted'] ?? true,
            'satisfaction' => $data['satisfaction'] ?? null,
            'user_feedback' => $data['user_feedback'] ?? null,
            'context' => $data['context'] ?? null,
        ]);

        // Refresh variant metrics
        $variant = KeywordABTestVariant::find($data['variant_id']);
        if ($variant) {
            $variant->refreshMetrics();
        }

        return $result;
    }

    /**
     * วิเคราะห์ผลลัพธ์และกำหนด winner
     *
     * @param KeywordABTest $test
     * @return array
     */
    public function analyzeResults(KeywordABTest $test): array
    {
        $variantA = $test->variantA();
        $variantB = $test->variantB();

        if (!$variantA || !$variantB) {
            return [
                'winner' => null,
                'winner_confidence' => 0,
                'reason' => 'ไม่มีข้อมูลเพียงพอ',
            ];
        }

        // Refresh metrics
        $variantA->refreshMetrics();
        $variantB->refreshMetrics();

        // เลือก metric ที่จะเปรียบเทียบ
        $criterion = $test->winning_criterion;
        $metricA = $this->getMetricValue($variantA, $criterion);
        $metricB = $this->getMetricValue($variantB, $criterion);

        // คำนวณความแตกต่างและ confidence
        $winner = $metricA > $metricB ? 'variant_a' : 'variant_b';
        $difference = abs($metricA - $metricB);
        $confidence = $this->calculateStatisticalConfidence(
            $test,
            $variantA,
            $variantB,
            $criterion
        );

        return [
            'winner' => $winner,
            'winner_confidence' => $confidence,
            'criterion' => $criterion,
            'variant_a_score' => round($metricA, 2),
            'variant_b_score' => round($metricB, 2),
            'difference' => round($difference, 2),
            'reason' => $this->getWinnerReason($criterion, $winner, $difference),
            'has_significance' => $confidence >= 95,
            'sufficient_samples' => $test->hasSufficientSamples(),
        ];
    }

    /**
     * ได้ค่า metric สำหรับ variant
     *
     * @param KeywordABTestVariant $variant
     * @param string $criterion เกณฑ์
     * @return float
     */
    private function getMetricValue(KeywordABTestVariant $variant, string $criterion): float
    {
        return match ($criterion) {
            'conversion_rate' => $variant->conversion_rate,
            'response_time' => $variant->avg_response_time, // Lower is better
            'satisfaction' => $variant->satisfaction_score,
            'interaction_rate' => $variant->interactions > 0
                ? ($variant->interactions / $variant->impressions) * 100
                : 0,
            default => $variant->conversion_rate,
        };
    }

    /**
     * คำนวณความมั่นใจทางสถิติ (Chi-square test)
     *
     * @param KeywordABTest $test
     * @param KeywordABTestVariant $variantA
     * @param KeywordABTestVariant $variantB
     * @param string $criterion
     * @return float
     */
    private function calculateStatisticalConfidence(
        KeywordABTest $test,
        KeywordABTestVariant $variantA,
        KeywordABTestVariant $variantB,
        string $criterion
    ): float {
        // สำหรับจุดประสงค์นี้ ใช้สูตร conversion rate ที่เรียบง่าย
        if ($criterion !== 'conversion_rate') {
            return 75; // Default confidence for other metrics
        }

        $conversionsA = $variantA->interactions;
        $trialsA = $variantA->impressions;
        $conversionsB = $variantB->interactions;
        $trialsB = $variantB->impressions;

        if ($trialsA == 0 || $trialsB == 0) {
            return 0;
        }

        // Simplified chi-square test
        $rateA = $conversionsA / $trialsA;
        $rateB = $conversionsB / $trialsB;
        $pooled = ($conversionsA + $conversionsB) / ($trialsA + $trialsB);

        $se = sqrt($pooled * (1 - $pooled) * (1 / $trialsA + 1 / $trialsB));

        if ($se == 0) {
            return 0;
        }

        $z = abs($rateA - $rateB) / $se;

        // Convert z-score to confidence (approximate)
        // z=1.96 = 95% confidence
        $confidence = min(($z / 1.96) * 95, 99.9);

        return round($confidence, 2);
    }

    /**
     * ได้ reason text สำหรับผู้ชนะ
     *
     * @param string $criterion
     * @param string $winner
     * @param float $difference
     * @return string
     */
    private function getWinnerReason(string $criterion, string $winner, float $difference): string
    {
        $variantLabel = $winner === 'variant_a' ? 'Variant A' : 'Variant B';

        return match ($criterion) {
            'conversion_rate' => "$variantLabel ชนะด้วย conversion rate สูงกว่า $difference%",
            'response_time' => "$variantLabel ชนะด้วย response time ต่ำกว่า {$difference}ms",
            'satisfaction' => "$variantLabel ชนะด้วย satisfaction score สูงกว่า $difference คะแนน",
            'interaction_rate' => "$variantLabel ชนะด้วย interaction rate สูงกว่า $difference%",
            default => "$variantLabel ชนะในการทดสอบนี้",
        };
    }

    /**
     * ได้สรุปผล A/B test
     *
     * @param KeywordABTest $test
     * @return array
     */
    public function getTestSummary(KeywordABTest $test): array
    {
        $test->load(['keyword', 'variants', 'results_records']);

        $variantA = $test->variantA();
        $variantB = $test->variantB();

        $totalInteractions = $test->results_records()->count();
        $duration = $test->started_at && $test->ended_at
            ? $test->ended_at->diffInDays($test->started_at)
            : ($test->started_at ? now()->diffInDays($test->started_at) : 0);

        return [
            'test_id' => $test->id,
            'test_name' => $test->test_name,
            'keyword' => $test->keyword?->keyword,
            'status' => $test->status,
            'duration_days' => $duration,
            'total_interactions' => $totalInteractions,
            'variant_a' => $variantA ? $variantA->getDetailedStats() : null,
            'variant_b' => $variantB ? $variantB->getDetailedStats() : null,
            'winner' => $test->winner,
            'winner_confidence' => $test->winner_confidence,
            'results' => $test->results,
            'started_at' => $test->started_at?->toIso8601String(),
            'ended_at' => $test->ended_at?->toIso8601String(),
        ];
    }

    /**
     * ได้รายการทดสอบทั้งหมด
     *
     * @param int $limit
     * @return Collection
     */
    public function getAllTests(int $limit = 20): Collection
    {
        return KeywordABTest::with(['keyword', 'variants'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($test) => $this->getTestSummary($test));
    }

    /**
     * ได้ active tests เท่านั้น
     *
     * @return Collection
     */
    public function getActiveTests(): Collection
    {
        return KeywordABTest::active()
            ->with(['keyword', 'variants'])
            ->get()
            ->map(fn ($test) => $this->getTestSummary($test));
    }

    /**
     * ได้ completed tests เท่านั้น
     *
     * @return Collection
     */
    public function getCompletedTests(): Collection
    {
        return KeywordABTest::completed()
            ->with(['keyword', 'variants'])
            ->get()
            ->map(fn ($test) => $this->getTestSummary($test));
    }

    /**
     * ได้สถิติสำหรับ dashboard
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $totalTests = KeywordABTest::count();
        $activeTests = KeywordABTest::active()->count();
        $completedTests = KeywordABTest::completed()->count();
        $totalResults = KeywordABTestResult::count();

        // หาผู้ชนะที่พบบ่อยที่สุด
        $topWinner = KeywordABTest::completed()
            ->whereNotNull('winner')
            ->select('winner')
            ->groupBy('winner')
            ->selectRaw('count(*) as count')
            ->orderByDesc('count')
            ->first();

        // หา avg confidence
        $avgConfidence = KeywordABTest::completed()
            ->whereNotNull('winner_confidence')
            ->avg('winner_confidence');

        // หา most tested keyword
        $mostTestedKeyword = KeywordABTest::select('keyword_id')
            ->groupBy('keyword_id')
            ->selectRaw('count(*) as test_count')
            ->orderByDesc('test_count')
            ->first();

        return [
            'total_tests' => $totalTests,
            'active_tests' => $activeTests,
            'completed_tests' => $completedTests,
            'total_interactions' => $totalResults,
            'avg_confidence' => round($avgConfidence ?? 0, 2),
            'most_common_winner' => $topWinner?->winner,
            'keywords_tested' => KeywordABTest::distinct('keyword_id')->count(),
        ];
    }

    /**
     * ได้ recommendations สำหรับการปรับปรุง
     *
     * @return array
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        // ตรวจสอบ tests ที่ยาวนานมากโดยไม่มี winner
        $longRunningTests = KeywordABTest::active()
            ->whereNull('winner')
            ->where('started_at', '<', now()->subDays(30))
            ->count();

        if ($longRunningTests > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "$longRunningTests tests ยังไม่มี winner หลังจาก 30 วัน",
                'action' => 'ตรวจสอบและจบการทดสอบ',
            ];
        }

        // ตรวจสอบ tests ที่ไม่มี samples เพียงพอ
        $insufficientSamples = KeywordABTest::active()
            ->get()
            ->filter(fn ($test) => !$test->hasSufficientSamples())
            ->count();

        if ($insufficientSamples > 0) {
            $recommendations[] = [
                'type' => 'info',
                'message' => "$insufficientSamples tests ยังไม่มี samples เพียงพอ",
                'action' => 'ให้เวลาเพิ่มเติมหรือเพิ่ม minimum_samples',
            ];
        }

        // ตรวจสอบ keywords ที่ได้ improvement แล้ว
        $improvementCandidates = KeywordABTest::completed()
            ->whereNotNull('winner')
            ->where('winner_confidence', '>=', 95)
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        if ($improvementCandidates > 0) {
            $recommendations[] = [
                'type' => 'success',
                'message' => "$improvementCandidates tests ผ่านการทดสอบแล้ว",
                'action' => 'นำไปใช้ variant ผู้ชนะ',
            ];
        }

        return $recommendations;
    }

    /**
     * Apply winner variant ให้เป็น keyword หลัก
     *
     * @param KeywordABTest $test
     * @return LineBotKeyword
     *
     * @throws \Exception
     */
    public function applyWinner(KeywordABTest $test): LineBotKeyword
    {
        if (!$test->winner) {
            throw new \Exception('ยังไม่มี winner สำหรับ test นี้');
        }

        return DB::transaction(function () use ($test) {
            $winnerVariant = $test->winner === 'variant_a'
                ? $test->variantA()
                : $test->variantB();

            $keyword = $test->keyword;
            $keyword->update([
                'response_text' => $winnerVariant->response_text,
                'response_type' => $winnerVariant->response_type,
            ]);

            // Log the change
            activity()
                ->performedOn($keyword)
                ->log('นำไปใช้ ' . $winnerVariant->getVariantLabel() . ' จาก A/B test: ' . $test->test_name);

            return $keyword;
        });
    }
}
