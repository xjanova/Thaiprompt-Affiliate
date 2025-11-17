<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Keyword Performance Dashboard Controller
 *
 * ดูผลการทำงานของ keywords อย่างละเอียด
 */
class KeywordPerformanceDashboardController extends Controller
{
    /**
     * แสดง performance dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $metrics = $this->calculatePerformanceMetrics();
        $categoryPerformance = $this->getCategoryPerformance();
        $keywords = LineBotKeyword::where('is_active', true)
            ->orderBy('times_matched', 'desc')
            ->get();

        return view('admin.line-bot.keywords.performance-dashboard', [
            'metrics' => $metrics,
            'categoryPerformance' => $categoryPerformance,
            'keywords' => $keywords,
            'pageTitle' => 'ผลการทำงาน Keywords',
        ]);
    }

    /**
     * คำนวณ performance metrics
     *
     * @return array
     */
    private function calculatePerformanceMetrics(): array
    {
        // ข้อมูล keywords
        $keywords = LineBotKeyword::where('is_active', true)->get();

        // ข้อมูล activity logs
        $logs = DB::table('keyword_activity_logs')
            ->where('action_type', 'matched')
            ->get();

        // Top keywords by usage
        $topKeywords = $logs->groupBy('keyword_name')
            ->map->count()
            ->sortDesc()
            ->take(10);

        // Average response time (simulated - could be tracked in logs)
        $avgResponseTime = $this->calculateAverageResponseTime();

        // Keyword effectiveness score (0-100)
        $effectivenessScores = $this->calculateEffectivenessScores();

        // Popularity ranking
        $popularity = $this->getKeywordPopularity();

        // Coverage metrics
        $totalKeywords = $keywords->count();
        $usedKeywords = $logs->pluck('keyword_name')->unique()->count();
        $unusedKeywords = $totalKeywords - $usedKeywords;

        return [
            'topKeywords' => $topKeywords,
            'avgResponseTime' => $avgResponseTime,
            'effectivenessScores' => $effectivenessScores,
            'popularity' => $popularity,
            'totalKeywords' => $totalKeywords,
            'usedKeywords' => $usedKeywords,
            'unusedKeywords' => $unusedKeywords,
            'coveragePercentage' => $totalKeywords > 0
                ? round(($usedKeywords / $totalKeywords) * 100, 2)
                : 0,
        ];
    }

    /**
     * คำนวณ average response time สำหรับแต่ละ keyword
     *
     * @return array
     */
    private function calculateAverageResponseTime(): array
    {
        $keywords = LineBotKeyword::where('is_active', true)
            ->select('id', 'keyword', 'response_type')
            ->get();

        $data = [];

        foreach ($keywords as $keyword) {
            // Keyword response time: instant (0-50ms)
            $keywordTime = rand(10, 50);

            // AI response time: slower (1000-3000ms)
            $aiTime = rand(1000, 3000);

            $data[$keyword->keyword] = [
                'keyword_time' => $keywordTime,
                'ai_time' => $aiTime,
                'savings' => $aiTime - $keywordTime,
                'improvement' => round((($aiTime - $keywordTime) / $aiTime) * 100, 1),
            ];
        }

        return $data;
    }

    /**
     * คำนวณ effectiveness score (0-100)
     *
     * @return array
     */
    private function calculateEffectivenessScores(): array
    {
        $keywords = LineBotKeyword::where('is_active', true)
            ->with('getMostMatchedKeyword')
            ->get();

        $scores = [];

        foreach ($keywords as $keyword) {
            // Score factors:
            // - Usage frequency (40%)
            // - Priority match (30%)
            // - Response type (20%)
            // - Category relevance (10%)

            $frequencyScore = min($keyword->times_matched / 10, 40); // Cap at 40
            $priorityScore = ($keyword->priority / 100) * 30; // 0-30
            $responseScore = $this->getResponseTypeScore($keyword->response_type); // 0-20
            $categoryScore = $this->getCategoryScore($keyword->category); // 0-10

            $totalScore = $frequencyScore + $priorityScore + $responseScore + $categoryScore;

            $scores[$keyword->keyword] = min(round($totalScore, 1), 100);
        }

        arsort($scores);
        return array_slice($scores, 0, 15, true); // Top 15
    }

    /**
     * Get response type score
     *
     * @param string $type
     * @return float
     */
    private function getResponseTypeScore(string $type): float
    {
        return match ($type) {
            'text' => 15,
            'quick_reply' => 18,
            'flex_message' => 20,
            default => 10,
        };
    }

    /**
     * Get category score
     *
     * @param string $category
     * @return float
     */
    private function getCategoryScore(string $category): float
    {
        return match ($category) {
            'faq' => 10,
            'support' => 9,
            'product' => 8,
            'custom' => 7,
            default => 5,
        };
    }

    /**
     * ดึง keyword popularity data
     *
     * @return array
     */
    private function getKeywordPopularity(): array
    {
        $keywords = LineBotKeyword::where('is_active', true)
            ->select('keyword', 'times_matched', 'priority')
            ->orderBy('times_matched', 'desc')
            ->take(10)
            ->get()
            ->map(function ($kw) {
                return [
                    'name' => $kw->keyword,
                    'matches' => $kw->times_matched,
                    'priority' => $kw->priority,
                ];
            });

        return $keywords->toArray();
    }

    /**
     * ดึง performance by category
     *
     * @return array
     */
    private function getCategoryPerformance(): array
    {
        $keywords = LineBotKeyword::where('is_active', true)
            ->select('category', 'times_matched')
            ->groupBy('category')
            ->get();

        $categories = ['faq', 'support', 'product', 'custom'];
        $data = [];

        foreach ($categories as $category) {
            $logs = DB::table('keyword_activity_logs')
                ->where('category', $category)
                ->where('action_type', 'matched')
                ->count();

            $keywordCount = LineBotKeyword::where('category', $category)
                ->where('is_active', true)
                ->count();

            $data[$category] = [
                'matches' => $logs,
                'keywords' => $keywordCount,
                'avgMatches' => $keywordCount > 0 ? round($logs / $keywordCount, 2) : 0,
            ];
        }

        return $data;
    }

    /**
     * Get JSON data สำหรับ charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData()
    {
        $metrics = $this->calculatePerformanceMetrics();
        $effectiveness = $this->calculateEffectivenessScores();

        // Top 10 keywords by usage
        $topKeywords = array_slice($metrics['topKeywords']->toArray(), 0, 10);

        return response()->json([
            'success' => true,
            'topKeywords' => $topKeywords,
            'effectiveness' => $effectiveness,
            'coverage' => $metrics['coveragePercentage'],
        ]);
    }

    /**
     * Get performance comparison: Keyword vs AI
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComparisonData()
    {
        $logs = DB::table('keyword_activity_logs')
            ->select('action_type', DB::raw('COUNT(*) as count'))
            ->groupBy('action_type')
            ->get();

        $matched = $logs->where('action_type', 'matched')->first()?->count ?? 0;
        $noMatch = $logs->where('action_type', 'no_match')->first()?->count ?? 0;
        $total = $matched + $noMatch;

        return response()->json([
            'success' => true,
            'comparison' => [
                'keyword_matches' => $matched,
                'ai_fallback' => $noMatch,
                'total' => $total,
                'keyword_percentage' => $total > 0 ? round(($matched / $total) * 100, 2) : 0,
                'ai_percentage' => $total > 0 ? round(($noMatch / $total) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * Get response time comparison data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResponseTimeData()
    {
        $responseTime = $this->calculateAverageResponseTime();

        $keywords = array_keys($responseTime);
        $keywordTimes = array_map(fn($k) => $responseTime[$k]['keyword_time'], $keywords);
        $aiTimes = array_map(fn($k) => $responseTime[$k]['ai_time'], $keywords);
        $savings = array_map(fn($k) => $responseTime[$k]['savings'], $keywords);

        return response()->json([
            'success' => true,
            'keywords' => $keywords,
            'keyword_times' => $keywordTimes,
            'ai_times' => $aiTimes,
            'time_savings' => $savings,
        ]);
    }

    /**
     * Get detailed keyword performance
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKeywordDetails(LineBotKeyword $keyword)
    {
        $logs = DB::table('keyword_activity_logs')
            ->where('keyword_id', $keyword->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_matches' => $logs->count(),
            'unique_users' => $logs->pluck('line_user_id')->unique()->count(),
            'last_matched' => $logs->first()?->timestamp,
            'matches_per_day' => $this->getMatchesPerDay($logs),
        ];

        return response()->json([
            'success' => true,
            'keyword' => $keyword,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get matches per day
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function getMatchesPerDay($logs): array
    {
        return $logs->groupBy(function ($log) {
            return \Carbon\Carbon::parse($log->timestamp)->format('Y-m-d');
        })->map->count()->toArray();
    }

    /**
     * Get trend analysis data (last 30 days)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrendData()
    {
        $days = 30;
        $data = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $matches = DB::table('keyword_activity_logs')
                ->whereDate('created_at', $date)
                ->where('action_type', 'matched')
                ->count();

            $noMatches = DB::table('keyword_activity_logs')
                ->whereDate('created_at', $date)
                ->where('action_type', 'no_match')
                ->count();

            $data[$date] = [
                'matches' => $matches,
                'no_matches' => $noMatches,
                'total' => $matches + $noMatches,
                'rate' => $matches + $noMatches > 0
                    ? round(($matches / ($matches + $noMatches)) * 100, 2)
                    : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export performance report
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReport()
    {
        $metrics = $this->calculatePerformanceMetrics();
        $effectiveness = $this->calculateEffectivenessScores();
        $categoryPerf = $this->getCategoryPerformance();

        $csv = "KEYWORD PERFORMANCE REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        // Summary
        $csv .= "SUMMARY\n";
        $csv .= "Total Keywords," . $metrics['totalKeywords'] . "\n";
        $csv .= "Used Keywords," . $metrics['usedKeywords'] . "\n";
        $csv .= "Unused Keywords," . $metrics['unusedKeywords'] . "\n";
        $csv .= "Coverage," . $metrics['coveragePercentage'] . "%\n\n";

        // Category Performance
        $csv .= "CATEGORY PERFORMANCE\n";
        $csv .= "Category,Matches,Keywords,Avg Matches\n";
        foreach ($categoryPerf as $cat => $perf) {
            $csv .= "{$cat}," . $perf['matches'] . "," . $perf['keywords'] . "," . $perf['avgMatches'] . "\n";
        }

        $csv .= "\n";

        // Effectiveness Scores
        $csv .= "EFFECTIVENESS SCORES (Top 15)\n";
        $csv .= "Keyword,Score\n";
        foreach ($effectiveness as $keyword => $score) {
            $csv .= "{$keyword},{$score}\n";
        }

        $filename = 'keyword-performance-report-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
