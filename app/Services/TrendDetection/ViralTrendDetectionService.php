<?php

namespace App\Services\TrendDetection;

use App\Models\TrendKeyword;
use App\Models\ViralTrend;
use App\Models\TrendData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViralTrendDetectionService
{
    protected float $viralThreshold = 100.0;
    protected int $minMentions = 5;
    protected float $minVelocity = 2.0; // mentions per hour

    /**
     * Detect viral trends from keywords
     */
    public function detectViralTrends(): array
    {
        $detectedTrends = [];

        // Get trending keywords
        $trendingKeywords = TrendKeyword::trending(50)
            ->where('frequency', '>=', $this->minMentions)
            ->get();

        foreach ($trendingKeywords as $keyword) {
            // Check if already exists as viral trend
            $existingTrend = ViralTrend::where('trend_keyword_id', $keyword->id)
                ->where('status', '!=', 'declined')
                ->first();

            if ($existingTrend) {
                // Update existing trend
                $this->updateViralTrend($existingTrend, $keyword);
                $detectedTrends[] = $existingTrend;
            } else {
                // Check if qualifies as viral
                if ($this->qualifiesAsViral($keyword)) {
                    $trend = $this->createViralTrend($keyword);
                    $detectedTrends[] = $trend;
                }
            }
        }

        return $detectedTrends;
    }

    /**
     * Check if keyword qualifies as viral
     */
    protected function qualifiesAsViral(TrendKeyword $keyword): bool
    {
        // Calculate velocity (mentions per hour)
        $hoursSinceFirst = max(1, now()->diffInHours($keyword->first_seen_at));
        $velocity = $keyword->frequency / $hoursSinceFirst;

        return $keyword->trend_score >= $this->viralThreshold &&
               $keyword->frequency >= $this->minMentions &&
               $velocity >= $this->minVelocity;
    }

    /**
     * Create new viral trend
     */
    protected function createViralTrend(TrendKeyword $keyword): ViralTrend
    {
        // Get trend data for this keyword
        $trendData = $keyword->trendData()
            ->orderBy('viral_score', 'desc')
            ->limit(10)
            ->get();

        // Aggregate metrics
        $totalMentions = $keyword->frequency;
        $totalEngagement = $trendData->sum('total_engagement');
        $sources = $trendData->pluck('trend_source_id')->unique()->values()->toArray();

        // Create title and description
        $topPost = $trendData->first();
        $title = $this->generateTrendTitle($keyword, $topPost);
        $description = $this->generateTrendDescription($keyword, $trendData);

        $trend = ViralTrend::create([
            'trend_keyword_id' => $keyword->id,
            'title' => $title,
            'description' => $description,
            'viral_score' => $keyword->trend_score,
            'total_mentions' => $totalMentions,
            'total_engagement' => $totalEngagement,
            'velocity' => $keyword->frequency / max(1, now()->diffInHours($keyword->first_seen_at)),
            'status' => 'rising',
            'started_at' => $keyword->first_seen_at,
            'sources' => $sources,
            'analytics' => $this->generateAnalytics($keyword, $trendData),
        ]);

        // Create initial snapshot
        $trend->addSnapshot([
            'mention_count' => $totalMentions,
            'engagement_count' => $totalEngagement,
            'sentiment_score' => 0,
        ]);

        Log::info("New viral trend detected: {$trend->title}");

        return $trend;
    }

    /**
     * Update existing viral trend
     */
    protected function updateViralTrend(ViralTrend $trend, TrendKeyword $keyword): void
    {
        $trendData = $keyword->trendData()
            ->orderBy('viral_score', 'desc')
            ->limit(10)
            ->get();

        $totalMentions = $keyword->frequency;
        $totalEngagement = $trendData->sum('total_engagement');

        $trend->update([
            'viral_score' => $keyword->trend_score,
            'total_mentions' => $totalMentions,
            'total_engagement' => $totalEngagement,
            'analytics' => $this->generateAnalytics($keyword, $trendData),
        ]);

        // Update status
        $trend->updateStatus();

        // Add new snapshot
        $trend->addSnapshot([
            'mention_count' => $totalMentions,
            'engagement_count' => $totalEngagement,
            'sentiment_score' => 0,
        ]);
    }

    /**
     * Generate trend title
     */
    protected function generateTrendTitle(TrendKeyword $keyword, ?TrendData $topPost): string
    {
        if ($topPost && $topPost->title) {
            // Extract relevant part of title
            $title = Str::limit($topPost->title, 100);
        } else {
            $title = "Trending: " . ucfirst($keyword->keyword);
        }

        return $title;
    }

    /**
     * Generate trend description
     */
    protected function generateTrendDescription(TrendKeyword $keyword, $trendData): string
    {
        $mentions = $keyword->frequency;
        $growthRate = $keyword->growth_rate;
        $sources = $trendData->pluck('source.name')->unique()->implode(', ');

        return "พบการพูดถึง '{$keyword->keyword}' จำนวน {$mentions} ครั้ง " .
               "เติบโตขึ้น {$growthRate}% " .
               "จากแหล่งข้อมูล: {$sources}";
    }

    /**
     * Generate analytics data
     */
    protected function generateAnalytics(TrendKeyword $keyword, $trendData): array
    {
        return [
            'keyword_stats' => [
                'frequency' => $keyword->frequency,
                'trend_score' => $keyword->trend_score,
                'growth_rate' => $keyword->growth_rate,
            ],
            'top_posts' => $trendData->take(5)->map(function ($data) {
                return [
                    'title' => $data->title,
                    'url' => $data->url,
                    'viral_score' => $data->viral_score,
                    'engagement' => $data->total_engagement,
                ];
            })->toArray(),
            'source_distribution' => $trendData
                ->groupBy('trend_source_id')
                ->map(function ($group) {
                    return [
                        'source' => $group->first()->source->name ?? 'Unknown',
                        'count' => $group->count(),
                    ];
                })
                ->values()
                ->toArray(),
            'time_distribution' => $this->getTimeDistribution($trendData),
        ];
    }

    /**
     * Get time distribution of mentions
     */
    protected function getTimeDistribution($trendData): array
    {
        $distribution = [];

        for ($i = 0; $i < 24; $i++) {
            $hourStart = now()->subHours(24 - $i)->startOfHour();
            $hourEnd = $hourStart->copy()->endOfHour();

            $count = $trendData->whereBetween('scraped_at', [$hourStart, $hourEnd])->count();

            $distribution[] = [
                'hour' => $hourStart->format('H:00'),
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Get trending dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'rising_trends' => $this->getRisingTrends(),
            'top_keywords' => $this->getTopKeywords(),
            'trending_now' => $this->getTrendingNow(),
            'analytics_summary' => $this->getAnalyticsSummary(),
        ];
    }

    /**
     * Get rising trends
     */
    protected function getRisingTrends(int $limit = 10): array
    {
        return ViralTrend::rising()
            ->limit($limit)
            ->with('keyword')
            ->get()
            ->map(function ($trend) {
                return [
                    'id' => $trend->id,
                    'title' => $trend->title,
                    'keyword' => $trend->keyword->keyword,
                    'viral_score' => $trend->viral_score,
                    'velocity' => $trend->velocity,
                    'total_mentions' => $trend->total_mentions,
                    'status' => $trend->status,
                    'age_hours' => $trend->age_in_hours,
                ];
            })
            ->toArray();
    }

    /**
     * Get top keywords
     */
    protected function getTopKeywords(int $limit = 20): array
    {
        return TrendKeyword::trending()
            ->limit($limit)
            ->get()
            ->map(function ($keyword) {
                return [
                    'keyword' => $keyword->keyword,
                    'trend_score' => $keyword->trend_score,
                    'frequency' => $keyword->frequency,
                    'growth_rate' => $keyword->growth_rate,
                ];
            })
            ->toArray();
    }

    /**
     * Get currently trending
     */
    protected function getTrendingNow(int $hours = 6): array
    {
        return ViralTrend::active()
            ->where('started_at', '>=', now()->subHours($hours))
            ->with('keyword')
            ->get()
            ->map(function ($trend) {
                return [
                    'id' => $trend->id,
                    'title' => $trend->title,
                    'keyword' => $trend->keyword->keyword,
                    'viral_score' => $trend->viral_score,
                    'status' => $trend->status,
                    'status_color' => $trend->status_color,
                ];
            })
            ->toArray();
    }

    /**
     * Get analytics summary
     */
    protected function getAnalyticsSummary(): array
    {
        $last24h = now()->subHours(24);

        return [
            'total_trends' => ViralTrend::count(),
            'active_trends' => ViralTrend::active()->count(),
            'rising_trends' => ViralTrend::rising()->count(),
            'new_trends_24h' => ViralTrend::where('started_at', '>=', $last24h)->count(),
            'total_keywords' => TrendKeyword::count(),
            'trending_keywords' => TrendKeyword::trending()->count(),
            'total_data_points' => TrendData::count(),
            'new_data_24h' => TrendData::where('scraped_at', '>=', $last24h)->count(),
        ];
    }

    /**
     * Get chart data for visualization
     */
    public function getChartData(ViralTrend $trend): array
    {
        $snapshots = $trend->analyticsSnapshots()
            ->orderBy('snapshot_at')
            ->get();

        return [
            'labels' => $snapshots->pluck('snapshot_at')->map(function ($date) {
                return $date->format('M d H:i');
            })->toArray(),
            'mentions' => $snapshots->pluck('mention_count')->toArray(),
            'engagement' => $snapshots->pluck('engagement_count')->toArray(),
            'sentiment' => $snapshots->pluck('sentiment_score')->toArray(),
        ];
    }
}
