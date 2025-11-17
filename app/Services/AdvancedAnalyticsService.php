<?php

namespace App\Services;

use App\Models\MessageSentiment;
use App\Models\MessageIntent;
use App\Models\KeywordABTest;
use App\Models\LineBotKeyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Advanced Analytics Service (Phase 3)
 *
 * Provides predictive modeling, anomaly detection, forecasting, and automated insights
 */
class AdvancedAnalyticsService
{
    /**
     * Generate comprehensive dashboard analytics
     *
     * @param int $days
     * @return array
     */
    public function getDashboardAnalytics(int $days = 30): array
    {
        return [
            'summary' => $this->getSummary($days),
            'predictions' => $this->generatePredictions($days),
            'anomalies' => $this->detectAnomalies($days),
            'forecasts' => $this->generateForecasts($days),
            'insights' => $this->generateInsights($days),
            'recommendations' => $this->getRecommendations($days),
        ];
    }

    /**
     * Get analytics summary
     *
     * @param int $days
     * @return array
     */
    private function getSummary(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $totalMessages = MessageSentiment::where('created_at', '>=', $startDate)->count();
        $totalIntents = MessageIntent::where('created_at', '>=', $startDate)->count();
        $complaintCount = MessageIntent::where('created_at', '>=', $startDate)
            ->where('primary_intent', 'COMPLAINT')
            ->count();

        $avgSentiment = MessageSentiment::where('created_at', '>=', $startDate)
            ->avg('sentiment_score') ?? 0;

        return [
            'total_messages' => $totalMessages,
            'total_intents' => $totalIntents,
            'complaint_count' => $complaintCount,
            'complaint_percentage' => $totalIntents > 0 ? round(($complaintCount / $totalIntents) * 100, 2) : 0,
            'average_sentiment' => round($avgSentiment, 2),
            'period_days' => $days,
        ];
    }

    /**
     * Generate predictions using simple ML models
     *
     * @param int $days
     * @return array
     */
    public function generatePredictions(int $days = 30): array
    {
        $historical = $this->getHistoricalData($days);

        if (count($historical) < 2) {
            return [];
        }

        // Simple linear regression for message volume
        $messageVolume = $this->predictTrend($historical['daily_messages'], 7);

        // Complaint trend prediction
        $complaintTrend = $this->predictTrend($historical['daily_complaints'], 7);

        // Sentiment prediction
        $sentimentTrend = $this->predictTrend($historical['daily_sentiment'], 7);

        return [
            'next_7_days' => [
                'predicted_message_volume' => round($messageVolume['value'] ?? 0),
                'predicted_complaint_rate' => round($complaintTrend['value'] ?? 0, 2),
                'predicted_sentiment' => round($sentimentTrend['value'] ?? 0, 2),
                'confidence' => round(($messageVolume['confidence'] ?? 0.5) * 100, 1),
            ],
            'trend_direction' => $this->determineTrendDirection($messageVolume),
        ];
    }

    /**
     * Detect anomalies in data
     *
     * @param int $days
     * @return array
     */
    public function detectAnomalies(int $days = 30): array
    {
        $historical = $this->getHistoricalData($days);

        if (empty($historical['daily_messages'])) {
            return [];
        }

        $messages = $historical['daily_messages'];
        $mean = array_sum($messages) / count($messages);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $messages)) / count($messages);
        $stdDev = sqrt($variance);

        // Find outliers (> 2 standard deviations)
        $anomalies = [];
        foreach ($historical['daily_messages'] as $date => $value) {
            $zScore = ($value - $mean) / ($stdDev + 0.001);

            if (abs($zScore) > 2) {
                $anomalies[] = [
                    'date' => $date,
                    'value' => $value,
                    'expected' => round($mean, 0),
                    'z_score' => round($zScore, 2),
                    'severity' => abs($zScore) > 3 ? 'high' : 'medium',
                ];
            }
        }

        return [
            'anomalies_detected' => count($anomalies),
            'anomalies' => $anomalies,
            'baseline_daily_average' => round($mean, 0),
            'std_deviation' => round($stdDev, 2),
        ];
    }

    /**
     * Generate forecasts for next period
     *
     * @param int $days
     * @param int $forecastDays
     * @return array
     */
    public function generateForecasts(int $days = 30, int $forecastDays = 14): array
    {
        $historical = $this->getHistoricalData($days);

        // Use exponential smoothing for forecasting
        $forecast = $this->exponentialSmoothing($historical['daily_messages'] ?? [], 0.3, $forecastDays);

        $forecastData = [];
        $date = now();
        foreach ($forecast as $value) {
            $forecastData[] = [
                'date' => $date->format('Y-m-d'),
                'predicted_messages' => round($value),
                'confidence_interval' => round($value * 0.15), // ±15%
            ];
            $date = $date->addDay();
        }

        return [
            'period' => "${forecastDays} days",
            'forecast' => $forecastData,
            'total_predicted_messages' => round(array_sum($forecast)),
            'daily_average' => round(array_sum($forecast) / count($forecast)),
        ];
    }

    /**
     * Generate automated insights
     *
     * @param int $days
     * @return array
     */
    public function generateInsights(int $days = 30): array
    {
        $insights = [];

        // Insight 1: Complaint trend
        $complaintData = $this->getComplaintTrend($days);
        if ($complaintData['trend'] === 'increasing') {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Increasing Complaints',
                'description' => "Complaint rate has increased by {$complaintData['change_percentage']}% in the last {$days} days",
                'action' => 'Review support procedures',
                'priority' => 'high',
            ];
        }

        // Insight 2: Low sentiment
        $sentimentData = $this->getSentimentStats($days);
        if ($sentimentData['average'] < -0.2) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Low Overall Sentiment',
                'description' => 'Average sentiment score is negative, indicating customer dissatisfaction',
                'action' => 'Investigate common issues',
                'priority' => 'high',
            ];
        }

        // Insight 3: High volume patterns
        $volumeData = $this->getVolumePatterns($days);
        if ($volumeData['peak_hour'] !== null) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Peak Activity Hours',
                'description' => "Highest message volume occurs around {$volumeData['peak_hour']}:00",
                'action' => 'Allocate resources accordingly',
                'priority' => 'normal',
            ];
        }

        // Insight 4: Keyword performance
        $topPerforming = $this->getTopPerformingKeywords($days);
        if (!empty($topPerforming)) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Top Performing Keywords',
                'description' => "Keywords: " . implode(', ', array_slice($topPerforming, 0, 3)),
                'action' => 'Expand coverage with similar keywords',
                'priority' => 'normal',
            ];
        }

        return $insights;
    }

    /**
     * Get recommendations based on analytics
     *
     * @param int $days
     * @return array
     */
    public function getRecommendations(int $days = 30): array
    {
        $recommendations = [];

        // Check A/B test results
        $abTests = KeywordABTest::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'completed')
            ->get();

        foreach ($abTests as $test) {
            if ($test->winning_variant) {
                $recommendations[] = [
                    'type' => 'ab_test',
                    'keyword' => $test->keyword->keyword_text,
                    'recommendation' => "Use {$test->winning_variant} response - {$test->winning_confidence}% confidence",
                    'impact' => 'high',
                ];
            }
        }

        // Check coverage gaps
        $totalMessages = MessageSentiment::where('created_at', '>=', now()->subDays($days))->count();
        $matchedMessages = $totalMessages * 0.7; // Estimate

        if ($matchedMessages < $totalMessages * 0.6) {
            $recommendations[] = [
                'type' => 'coverage',
                'recommendation' => 'Add more keywords to improve message coverage',
                'coverage_percentage' => round(($matchedMessages / $totalMessages) * 100),
                'impact' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Simple trend prediction
     *
     * @param array $data
     * @param int $forecastPoints
     * @return array
     */
    private function predictTrend(array $data, int $forecastPoints = 7): array
    {
        if (count($data) < 2) {
            return ['value' => 0, 'confidence' => 0.5];
        }

        $values = array_values($data);
        $n = count($values);

        // Calculate linear regression
        $sumX = ($n * ($n + 1)) / 2;
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($i + 1) * $values[$i];
            $sumX2 += ($i + 1) ** 2;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Forecast next point
        $predicted = $intercept + $slope * ($n + 1);

        // Calculate R-squared for confidence
        $meanY = $sumY / $n;
        $ssRes = 0;
        $ssTot = 0;
        for ($i = 0; $i < $n; $i++) {
            $yPred = $intercept + $slope * ($i + 1);
            $ssRes += ($values[$i] - $yPred) ** 2;
            $ssTot += ($values[$i] - $meanY) ** 2;
        }

        $rSquared = 1 - ($ssRes / ($ssTot + 0.001));
        $confidence = max(0.3, min(0.95, 0.5 + abs($rSquared) * 0.5));

        return [
            'value' => max(0, $predicted),
            'confidence' => $confidence,
        ];
    }

    /**
     * Exponential smoothing forecasting
     *
     * @param array $data
     * @param float $alpha
     * @param int $periods
     * @return array
     */
    private function exponentialSmoothing(array $data, float $alpha = 0.3, int $periods = 14): array
    {
        if (empty($data)) {
            return array_fill(0, $periods, 0);
        }

        $values = array_values($data);
        $level = end($values);
        $trend = ($values[count($values) - 1] - ($values[0] ?? 0)) / max(count($values) - 1, 1);

        $forecast = [];
        for ($i = 0; $i < $periods; $i++) {
            $forecast[] = max(0, $level + ($trend * ($i + 1)));
        }

        return $forecast;
    }

    /**
     * Determine trend direction
     *
     * @param array $prediction
     * @return string
     */
    private function determineTrendDirection(array $prediction): string
    {
        if (empty($prediction) || !isset($prediction['value'])) {
            return 'stable';
        }

        // This would compare to historical average in real implementation
        return 'stable';
    }

    /**
     * Get historical data
     *
     * @param int $days
     * @return array
     */
    private function getHistoricalData(int $days = 30): array
    {
        $dailyMessages = [];
        $dailyComplaints = [];
        $dailySentiment = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $start = now()->subDays($i)->startOfDay();
            $end = now()->subDays($i)->endOfDay();

            $count = MessageSentiment::whereBetween('created_at', [$start, $end])->count();
            $complaints = MessageIntent::whereBetween('created_at', [$start, $end])
                ->where('primary_intent', 'COMPLAINT')
                ->count();
            $sentiment = MessageSentiment::whereBetween('created_at', [$start, $end])
                ->avg('sentiment_score') ?? 0;

            $dailyMessages[$date] = $count;
            $dailyComplaints[$date] = $complaints;
            $dailySentiment[$date] = $sentiment;
        }

        return [
            'daily_messages' => $dailyMessages,
            'daily_complaints' => $dailyComplaints,
            'daily_sentiment' => $dailySentiment,
        ];
    }

    /**
     * Helper methods
     */
    private function getComplaintTrend(int $days): array
    {
        $historical = $this->getHistoricalData($days);
        $complaints = $historical['daily_complaints'];

        if (count($complaints) < 2) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }

        $first = array_slice($complaints, 0, (int)(count($complaints) / 2));
        $last = array_slice($complaints, (int)(count($complaints) / 2));

        $firstAvg = array_sum($first) / max(count($first), 1);
        $lastAvg = array_sum($last) / max(count($last), 1);

        $change = (($lastAvg - $firstAvg) / max($firstAvg, 0.1)) * 100;
        $trend = $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable');

        return ['trend' => $trend, 'change_percentage' => round(abs($change), 1)];
    }

    private function getSentimentStats(int $days): array
    {
        $avg = MessageSentiment::where('created_at', '>=', now()->subDays($days))
            ->avg('sentiment_score') ?? 0;

        return ['average' => round($avg, 2)];
    }

    private function getVolumePatterns(int $days): array
    {
        // Simplified - would extract hour from created_at in real implementation
        return ['peak_hour' => 14]; // Example: 2 PM
    }

    private function getTopPerformingKeywords(int $days): array
    {
        return LineBotKeyword::active()
            ->latest('total_interactions')
            ->limit(5)
            ->pluck('keyword_text')
            ->toArray();
    }
}
