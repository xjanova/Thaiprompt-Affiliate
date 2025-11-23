<?php

namespace App\Services;

use App\Models\LineFailedMessage;
use App\Models\LineErrorLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * LINE Message Analytics Service
 *
 * วิเคราะห์ข้อมูลสถิติการส่งข้อความ LINE แบบครบวงจร
 * ต่อยอดจาก LineSignupAnalyticsService
 *
 * Features:
 * - Message delivery statistics
 * - Error pattern analysis
 * - Recovery metrics
 * - User engagement tracking
 * - Performance trending
 * - Cache optimization (5-min TTL)
 *
 * @package App\Services
 */
class LineMessageAnalyticsService
{
    /**
     * Cache TTL (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * ดึงสถิติภาพรวมของระบบ
     *
     * @param string $period 'today', 'week', 'month', 'all'
     * @return array
     */
    public function getOverviewStatistics(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_overview_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            // นับข้อความทั้งหมด
            $totalMessages = LineFailedMessage::whereBetween('created_at', $dateRange)->count();

            // นับตาม status
            $succeeded = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->where('status', LineFailedMessage::STATUS_SUCCEEDED)
                ->count();

            $failed = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->whereIn('status', [
                    LineFailedMessage::STATUS_FAILED,
                    LineFailedMessage::STATUS_ABANDONED
                ])
                ->count();

            $pending = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->whereIn('status', [
                    LineFailedMessage::STATUS_PENDING,
                    LineFailedMessage::STATUS_RETRYING
                ])
                ->count();

            // คำนวณ success rate
            $successRate = $totalMessages > 0
                ? round(($succeeded / $totalMessages) * 100, 2)
                : 0;

            // คำนวณ average retry count
            $avgRetryCount = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->where('status', LineFailedMessage::STATUS_SUCCEEDED)
                ->avg('retry_count') ?? 0;

            // คำนวณ recovery rate
            $recoveryRate = $failed > 0
                ? round((LineErrorLog::whereBetween('occurred_at', $dateRange)
                    ->where('is_recovered', true)
                    ->count() / $failed) * 100, 2)
                : 0;

            return [
                'period' => $period,
                'date_range' => [
                    'from' => $dateRange[0]->format('Y-m-d H:i:s'),
                    'to' => $dateRange[1]->format('Y-m-d H:i:s'),
                ],
                'total_messages' => $totalMessages,
                'succeeded' => $succeeded,
                'failed' => $failed,
                'pending' => $pending,
                'success_rate' => $successRate,
                'failure_rate' => round(100 - $successRate, 2),
                'avg_retry_count' => round($avgRetryCount, 2),
                'recovery_rate' => $recoveryRate,
            ];
        });
    }

    /**
     * ดึงสถิติข้อความตามประเภท (text, flex, template, image)
     *
     * @param string $period
     * @return array
     */
    public function getMessageTypeStatistics(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_message_types_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $types = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->select('message_type', DB::raw('COUNT(*) as count'))
                ->groupBy('message_type')
                ->get()
                ->pluck('count', 'message_type')
                ->toArray();

            // คำนวณ percentage
            $total = array_sum($types);
            $result = [];

            foreach ($types as $type => $count) {
                $result[] = [
                    'type' => $type,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                ];
            }

            // เรียงตาม count มากไปน้อย
            usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

            return $result;
        });
    }

    /**
     * วิเคราะห์ error patterns
     *
     * @param string $period
     * @return array
     */
    public function getErrorPatternAnalysis(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_error_patterns_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            // Top 10 error types
            $errorTypes = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->whereNotNull('error_type')
                ->select('error_type', DB::raw('COUNT(*) as count'))
                ->groupBy('error_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $total = $errorTypes->sum('count');

            $result = [];
            foreach ($errorTypes as $error) {
                $result[] = [
                    'error_type' => $error->error_type,
                    'count' => $error->count,
                    'percentage' => $total > 0 ? round(($error->count / $total) * 100, 2) : 0,
                ];
            }

            return $result;
        });
    }

    /**
     * วิเคราะห์ recovery metrics
     *
     * @param string $period
     * @return array
     */
    public function getRecoveryMetrics(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_recovery_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            // นับ errors ที่ recover แล้ว
            $totalErrors = LineErrorLog::whereBetween('occurred_at', $dateRange)->count();
            $recoveredErrors = LineErrorLog::whereBetween('occurred_at', $dateRange)
                ->where('is_recovered', true)
                ->count();

            // คำนวณ average recovery time (seconds)
            $avgRecoveryTime = LineErrorLog::whereBetween('occurred_at', $dateRange)
                ->where('is_recovered', true)
                ->whereNotNull('recovery_time_seconds')
                ->avg('recovery_time_seconds') ?? 0;

            // Recovery methods breakdown
            $recoveryMethods = LineErrorLog::whereBetween('occurred_at', $dateRange)
                ->where('is_recovered', true)
                ->select('recovery_method', DB::raw('COUNT(*) as count'))
                ->groupBy('recovery_method')
                ->get()
                ->pluck('count', 'recovery_method')
                ->toArray();

            return [
                'total_errors' => $totalErrors,
                'recovered_errors' => $recoveredErrors,
                'unrecovered_errors' => $totalErrors - $recoveredErrors,
                'recovery_rate' => $totalErrors > 0
                    ? round(($recoveredErrors / $totalErrors) * 100, 2)
                    : 0,
                'avg_recovery_time_seconds' => round($avgRecoveryTime, 2),
                'avg_recovery_time_minutes' => round($avgRecoveryTime / 60, 2),
                'recovery_methods' => $recoveryMethods,
            ];
        });
    }

    /**
     * ดึงข้อมูล trending (สถิติรายวัน/รายชั่วโมง)
     *
     * @param string $period 'today', 'week', 'month'
     * @param string $interval 'hour', 'day'
     * @return array
     */
    public function getTrendingData(string $period = 'week', string $interval = 'day'): array
    {
        $cacheKey = "line_analytics_trending_{$period}_{$interval}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period, $interval) {
            $dateRange = $this->getDateRange($period);

            // เลือก format ตาม interval
            $dateFormat = $interval === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
            $groupBy = $interval === 'hour'
                ? DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as period")
                : DB::raw("DATE(created_at) as period");

            // ดึงข้อมูล trending
            $data = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->select(
                    $groupBy,
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "succeeded" THEN 1 ELSE 0 END) as succeeded'),
                    DB::raw('SUM(CASE WHEN status IN ("failed", "abandoned") THEN 1 ELSE 0 END) as failed'),
                    DB::raw('SUM(CASE WHEN status IN ("pending", "retrying") THEN 1 ELSE 0 END) as pending')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $result = [];
            foreach ($data as $row) {
                $successRate = $row->total > 0
                    ? round(($row->succeeded / $row->total) * 100, 2)
                    : 0;

                $result[] = [
                    'period' => $row->period,
                    'total' => $row->total,
                    'succeeded' => $row->succeeded,
                    'failed' => $row->failed,
                    'pending' => $row->pending,
                    'success_rate' => $successRate,
                ];
            }

            return $result;
        });
    }

    /**
     * ดึงสถิติ user engagement
     *
     * @param string $period
     * @return array
     */
    public function getUserEngagementMetrics(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_user_engagement_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            // นับ active LINE users (มี line_user_id และมีข้อความถูกส่ง)
            $activeUsers = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->distinct('line_user_id')
                ->count('line_user_id');

            // Messages per user average
            $totalMessages = LineFailedMessage::whereBetween('created_at', $dateRange)->count();
            $messagesPerUser = $activeUsers > 0
                ? round($totalMessages / $activeUsers, 2)
                : 0;

            // Top 10 users ที่รับข้อความมากสุด
            $topUsers = LineFailedMessage::whereBetween('created_at', $dateRange)
                ->select('line_user_id', DB::raw('COUNT(*) as message_count'))
                ->groupBy('line_user_id')
                ->orderByDesc('message_count')
                ->limit(10)
                ->get();

            return [
                'active_users' => $activeUsers,
                'total_messages' => $totalMessages,
                'messages_per_user' => $messagesPerUser,
                'top_users' => $topUsers->map(function ($user) {
                    return [
                        'line_user_id' => $user->line_user_id,
                        'message_count' => $user->message_count,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * ดึง error severity breakdown
     *
     * @param string $period
     * @return array
     */
    public function getErrorSeverityBreakdown(string $period = 'week'): array
    {
        $cacheKey = "line_analytics_error_severity_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            $severities = LineErrorLog::whereBetween('occurred_at', $dateRange)
                ->select('severity', DB::raw('COUNT(*) as count'))
                ->groupBy('severity')
                ->get();

            $total = $severities->sum('count');

            $result = [];
            foreach ($severities as $severity) {
                $result[] = [
                    'severity' => $severity->severity,
                    'count' => $severity->count,
                    'percentage' => $total > 0 ? round(($severity->count / $total) * 100, 2) : 0,
                ];
            }

            // เรียงตาม severity (critical > high > medium > low)
            $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            usort($result, function ($a, $b) use ($severityOrder) {
                return ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99);
            });

            return $result;
        });
    }

    /**
     * ดึงสถิติแบบรวม (สำหรับ dashboard หลัก)
     *
     * @param string $period
     * @return array
     */
    public function getDashboardSummary(string $period = 'week'): array
    {
        return [
            'overview' => $this->getOverviewStatistics($period),
            'message_types' => $this->getMessageTypeStatistics($period),
            'error_patterns' => $this->getErrorPatternAnalysis($period),
            'recovery_metrics' => $this->getRecoveryMetrics($period),
            'trending' => $this->getTrendingData($period, 'day'),
            'user_engagement' => $this->getUserEngagementMetrics($period),
            'error_severity' => $this->getErrorSeverityBreakdown($period),
        ];
    }

    /**
     * คำนวณ date range จาก period string
     *
     * @param string $period
     * @return array [Carbon, Carbon]
     */
    private function getDateRange(string $period): array
    {
        $end = Carbon::now();

        switch ($period) {
            case 'today':
                $start = Carbon::today();
                break;
            case 'week':
                $start = Carbon::now()->subDays(7);
                break;
            case 'month':
                $start = Carbon::now()->subDays(30);
                break;
            case 'all':
                $start = Carbon::create(2020, 1, 1); // หรือ first record date
                break;
            default:
                $start = Carbon::now()->subDays(7);
        }

        return [$start, $end];
    }

    /**
     * ล้าง cache analytics ทั้งหมด
     *
     * @return void
     */
    public function clearCache(): void
    {
        $periods = ['today', 'week', 'month', 'all'];
        $intervals = ['hour', 'day'];

        foreach ($periods as $period) {
            Cache::forget("line_analytics_overview_{$period}");
            Cache::forget("line_analytics_message_types_{$period}");
            Cache::forget("line_analytics_error_patterns_{$period}");
            Cache::forget("line_analytics_recovery_{$period}");
            Cache::forget("line_analytics_user_engagement_{$period}");
            Cache::forget("line_analytics_error_severity_{$period}");

            foreach ($intervals as $interval) {
                Cache::forget("line_analytics_trending_{$period}_{$interval}");
            }
        }
    }
}
