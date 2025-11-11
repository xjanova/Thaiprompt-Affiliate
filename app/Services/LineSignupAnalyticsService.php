<?php

namespace App\Services;

use App\Models\MlmProspect;
use App\Models\User;
use App\Models\LineLoginLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * LINE Signup Analytics Service
 *
 * Provides comprehensive analytics and insights for LINE OA signup process:
 * - Conversion funnel analysis
 * - Time-to-complete metrics
 * - Dropout analysis
 * - Performance tracking
 * - Sponsor leaderboard
 * - Real-time statistics
 */
class LineSignupAnalyticsService
{
    /**
     * Cache TTL for analytics (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Get overall signup statistics
     *
     * @param string $period 'today', 'week', 'month', 'all'
     * @return array
     */
    public function getOverallStats(string $period = 'all'): array
    {
        $cacheKey = "line_analytics:overall:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateFilter = $this->getDateFilter($period);

            $stats = [
                'total_invitations' => MlmProspect::when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))->count(),
                'total_clicked' => MlmProspect::whereNotNull('clicked_at')
                    ->when($dateFilter, fn($q) => $q->where('clicked_at', '>=', $dateFilter))->count(),
                'total_in_progress' => MlmProspect::where('status', 'in_progress')
                    ->when($dateFilter, fn($q) => $q->where('conversation_started_at', '>=', $dateFilter))->count(),
                'total_completed' => MlmProspect::where('status', 'completed')
                    ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter))->count(),
                'total_expired' => MlmProspect::where('conversation_expired', true)
                    ->when($dateFilter, fn($q) => $q->where('conversation_updated_at', '>=', $dateFilter))->count(),
            ];

            // Calculate conversion rates
            $stats['click_rate'] = $stats['total_invitations'] > 0
                ? round(($stats['total_clicked'] / $stats['total_invitations']) * 100, 2)
                : 0;

            $stats['start_rate'] = $stats['total_clicked'] > 0
                ? round((($stats['total_in_progress'] + $stats['total_completed']) / $stats['total_clicked']) * 100, 2)
                : 0;

            $stats['completion_rate'] = $stats['total_in_progress'] + $stats['total_completed'] > 0
                ? round(($stats['total_completed'] / ($stats['total_in_progress'] + $stats['total_completed'] + $stats['total_expired'])) * 100, 2)
                : 0;

            $stats['overall_conversion'] = $stats['total_invitations'] > 0
                ? round(($stats['total_completed'] / $stats['total_invitations']) * 100, 2)
                : 0;

            return $stats;
        });
    }

    /**
     * Get conversion funnel data
     *
     * @param string $period
     * @return array
     */
    public function getConversionFunnel(string $period = 'all'): array
    {
        $cacheKey = "line_analytics:funnel:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateFilter = $this->getDateFilter($period);

            $funnel = [
                ['stage' => 'Invited', 'count' => 0, 'percentage' => 100],
                ['stage' => 'Clicked', 'count' => 0, 'percentage' => 0],
                ['stage' => 'Started', 'count' => 0, 'percentage' => 0],
                ['stage' => 'Completed', 'count' => 0, 'percentage' => 0],
            ];

            $total = MlmProspect::when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))->count();
            $clicked = MlmProspect::whereNotNull('clicked_at')
                ->when($dateFilter, fn($q) => $q->where('clicked_at', '>=', $dateFilter))->count();
            $started = MlmProspect::whereNotNull('conversation_started_at')
                ->when($dateFilter, fn($q) => $q->where('conversation_started_at', '>=', $dateFilter))->count();
            $completed = MlmProspect::where('status', 'completed')
                ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter))->count();

            $funnel[0]['count'] = $total;
            $funnel[1]['count'] = $clicked;
            $funnel[1]['percentage'] = $total > 0 ? round(($clicked / $total) * 100, 1) : 0;
            $funnel[2]['count'] = $started;
            $funnel[2]['percentage'] = $total > 0 ? round(($started / $total) * 100, 1) : 0;
            $funnel[3]['count'] = $completed;
            $funnel[3]['percentage'] = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            return $funnel;
        });
    }

    /**
     * Get dropout analysis by step
     *
     * @return array
     */
    public function getDropoutAnalysis(): array
    {
        $cacheKey = "line_analytics:dropout";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $dropouts = MlmProspect::where('conversation_expired', true)
                ->whereNotNull('conversation_step')
                ->select('conversation_step', DB::raw('count(*) as count'))
                ->groupBy('conversation_step')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'step' => $item->conversation_step,
                        'count' => $item->count,
                        'percentage' => 0, // Will be calculated
                    ];
                })->toArray();

            $totalDropouts = array_sum(array_column($dropouts, 'count'));

            // Calculate percentages
            foreach ($dropouts as &$dropout) {
                $dropout['percentage'] = $totalDropouts > 0
                    ? round(($dropout['count'] / $totalDropouts) * 100, 1)
                    : 0;
            }

            return $dropouts;
        });
    }

    /**
     * Get average time to complete
     *
     * @param string $period
     * @return array
     */
    public function getTimeToComplete(string $period = 'all'): array
    {
        $cacheKey = "line_analytics:time_to_complete:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateFilter = $this->getDateFilter($period);

            $query = MlmProspect::where('status', 'completed')
                ->whereNotNull('conversation_started_at')
                ->whereNotNull('registered_at')
                ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter));

            $avgSeconds = $query->selectRaw('AVG(TIMESTAMPDIFF(SECOND, conversation_started_at, registered_at)) as avg')
                ->value('avg');

            $minSeconds = $query->selectRaw('MIN(TIMESTAMPDIFF(SECOND, conversation_started_at, registered_at)) as min')
                ->value('min');

            $maxSeconds = $query->selectRaw('MAX(TIMESTAMPDIFF(SECOND, conversation_started_at, registered_at)) as max')
                ->value('max');

            return [
                'average_seconds' => round($avgSeconds ?? 0),
                'average_minutes' => round(($avgSeconds ?? 0) / 60, 1),
                'min_seconds' => $minSeconds ?? 0,
                'max_seconds' => $maxSeconds ?? 0,
                'median_seconds' => $this->calculateMedianTime($period),
            ];
        });
    }

    /**
     * Get signup trends (daily/hourly)
     *
     * @param string $period 'today', 'week', 'month'
     * @param string $groupBy 'hour' or 'day'
     * @return array
     */
    public function getSignupTrends(string $period = 'week', string $groupBy = 'day'): array
    {
        $cacheKey = "line_analytics:trends:{$period}:{$groupBy}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period, $groupBy) {
            $dateFilter = $this->getDateFilter($period);

            $dateFormat = $groupBy === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';

            $trends = MlmProspect::where('status', 'completed')
                ->where('registered_at', '>=', $dateFilter)
                ->select(DB::raw("DATE_FORMAT(registered_at, '{$dateFormat}') as period"), DB::raw('count(*) as count'))
                ->groupBy('period')
                ->orderBy('period', 'asc')
                ->get()
                ->map(function ($item) use ($groupBy) {
                    return [
                        'period' => $groupBy === 'hour'
                            ? Carbon::parse($item->period)->format('M d, H:00')
                            : Carbon::parse($item->period)->format('M d'),
                        'count' => $item->count,
                    ];
                })->toArray();

            return $trends;
        });
    }

    /**
     * Get sponsor leaderboard
     *
     * @param string $period
     * @param int $limit
     * @return array
     */
    public function getSponsorLeaderboard(string $period = 'month', int $limit = 10): array
    {
        $cacheKey = "line_analytics:leaderboard:{$period}:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period, $limit) {
            $dateFilter = $this->getDateFilter($period);

            $leaderboard = MlmProspect::where('status', 'completed')
                ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter))
                ->select('sponsor_user_id', DB::raw('count(*) as signups'))
                ->groupBy('sponsor_user_id')
                ->orderBy('signups', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $user = User::find($item->sponsor_user_id);
                    return [
                        'sponsor_id' => $item->sponsor_user_id,
                        'sponsor_name' => $user->name ?? 'Unknown',
                        'signups' => $item->signups,
                        'avatar' => $user->line_picture_url ?? null,
                    ];
                })->toArray();

            return $leaderboard;
        });
    }

    /**
     * Get conversation statistics
     *
     * @return array
     */
    public function getConversationStats(): array
    {
        $cacheKey = "line_analytics:conversation_stats";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'avg_messages' => MlmProspect::where('status', 'completed')
                    ->avg('conversation_message_count') ?? 0,
                'avg_retry_count' => MlmProspect::where('status', 'completed')
                    ->avg('conversation_retry_count') ?? 0,
                'avg_progress' => MlmProspect::whereIn('status', ['in_progress', 'pending'])
                    ->avg('conversation_progress_percent') ?? 0,
            ];
        });
    }

    /**
     * Get real-time active conversations
     *
     * @return array
     */
    public function getActiveConversations(): array
    {
        return [
            'total_active' => MlmProspect::where('status', 'in_progress')
                ->where('conversation_expired', false)
                ->count(),
            'last_hour' => MlmProspect::where('status', 'in_progress')
                ->where('conversation_expired', false)
                ->where('conversation_updated_at', '>=', now()->subHour())
                ->count(),
            'last_5_min' => MlmProspect::where('status', 'in_progress')
                ->where('conversation_expired', false)
                ->where('conversation_updated_at', '>=', now()->subMinutes(5))
                ->count(),
        ];
    }

    /**
     * Get comprehensive dashboard data
     *
     * @param string $period
     * @return array
     */
    public function getDashboardData(string $period = 'month'): array
    {
        return [
            'overview' => $this->getOverallStats($period),
            'funnel' => $this->getConversionFunnel($period),
            'dropout' => $this->getDropoutAnalysis(),
            'time_metrics' => $this->getTimeToComplete($period),
            'trends' => $this->getSignupTrends($period),
            'leaderboard' => $this->getSponsorLeaderboard($period),
            'conversation_stats' => $this->getConversationStats(),
            'active' => $this->getActiveConversations(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get sponsor-specific analytics
     *
     * @param int $sponsorId
     * @param string $period
     * @return array
     */
    public function getSponsorAnalytics(int $sponsorId, string $period = 'month'): array
    {
        $dateFilter = $this->getDateFilter($period);

        $prospects = MlmProspect::where('sponsor_user_id', $sponsorId)
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter));

        $total = $prospects->count();
        $completed = $prospects->where('status', 'completed')->count();
        $inProgress = $prospects->where('status', 'in_progress')->count();
        $expired = $prospects->where('conversation_expired', true)->count();

        return [
            'total_invitations' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'expired' => $expired,
            'conversion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'recent_signups' => MlmProspect::where('sponsor_user_id', $sponsorId)
                ->where('status', 'completed')
                ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter))
                ->with('registeredUser:id,name,email,created_at')
                ->orderBy('registered_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'name' => $p->registeredUser->name ?? 'N/A',
                    'email' => $p->registeredUser->email ?? 'N/A',
                    'registered_at' => $p->registered_at->diffForHumans(),
                ]),
        ];
    }

    /**
     * Calculate median completion time
     *
     * @param string $period
     * @return float
     */
    private function calculateMedianTime(string $period): float
    {
        $dateFilter = $this->getDateFilter($period);

        $times = MlmProspect::where('status', 'completed')
            ->whereNotNull('conversation_started_at')
            ->whereNotNull('registered_at')
            ->when($dateFilter, fn($q) => $q->where('registered_at', '>=', $dateFilter))
            ->selectRaw('TIMESTAMPDIFF(SECOND, conversation_started_at, registered_at) as duration')
            ->pluck('duration')
            ->sort()
            ->values();

        if ($times->isEmpty()) {
            return 0;
        }

        $count = $times->count();
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($times[$middle - 1] + $times[$middle]) / 2;
        }

        return $times[$middle];
    }

    /**
     * Get date filter based on period
     *
     * @param string $period
     * @return Carbon|null
     */
    private function getDateFilter(string $period): ?Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => null,
        };
    }

    /**
     * Clear analytics cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $patterns = [
            'line_analytics:overall:*',
            'line_analytics:funnel:*',
            'line_analytics:dropout',
            'line_analytics:time_to_complete:*',
            'line_analytics:trends:*',
            'line_analytics:leaderboard:*',
            'line_analytics:conversation_stats',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
