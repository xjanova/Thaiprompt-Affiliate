<?php

namespace App\Services;

use App\Models\SystemAnalytic;
use App\Models\User;
use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\PaymentTransaction;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AnalyticsService
{
    /**
     * Collect all system metrics and save snapshot
     */
    public function collectMetrics(): SystemAnalytic
    {
        $metrics = [
            'recorded_at' => now(),
        ];

        // Collect all metric categories
        $metrics = array_merge($metrics, $this->getSystemMetrics());
        $metrics = array_merge($metrics, $this->getApplicationMetrics());
        $metrics = array_merge($metrics, $this->getDatabaseMetrics());
        $metrics = array_merge($metrics, $this->getCacheMetrics());
        $metrics = array_merge($metrics, $this->getQueueMetrics());
        $metrics = array_merge($metrics, $this->getBusinessMetrics());
        $metrics = array_merge($metrics, $this->getSecurityMetrics());

        return SystemAnalytic::create($metrics);
    }

    /**
     * Get system performance metrics (CPU, Memory, Disk)
     */
    protected function getSystemMetrics(): array
    {
        $metrics = [];

        // CPU Usage (Linux)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuCount = $this->getCpuCount();
            $metrics['cpu_usage'] = round(($load[0] / $cpuCount) * 100, 2);
        }

        // Memory Usage
        if (PHP_OS_FAMILY === 'Linux') {
            $memInfo = $this->getMemoryInfo();
            if ($memInfo) {
                $metrics['memory_usage'] = $memInfo['usage_percent'];
            }
        } else {
            // Fallback: Use PHP memory
            $metrics['memory_usage'] = round((memory_get_usage(true) / memory_get_peak_usage(true)) * 100, 2);
        }

        // Disk Usage
        $diskInfo = $this->getDiskInfo();
        if ($diskInfo) {
            $metrics['disk_usage'] = $diskInfo['usage_percent'];
            $metrics['disk_free'] = $diskInfo['free'];
            $metrics['disk_total'] = $diskInfo['total'];
        }

        return $metrics;
    }

    /**
     * Get application metrics
     */
    protected function getApplicationMetrics(): array
    {
        $metrics = [];

        // Active users (logged in within last 15 minutes)
        $metrics['active_users'] = $this->getActiveUsersCount();

        // Active sessions
        $metrics['active_sessions'] = $this->getActiveSessionsCount();

        // Request rate and response time (from cache if available)
        $requestMetrics = $this->getRequestMetrics();
        $metrics['request_rate'] = $requestMetrics['rate'] ?? 0;
        $metrics['avg_response_time'] = $requestMetrics['avg_time'] ?? null;
        $metrics['p50_response_time'] = $requestMetrics['p50'] ?? null;
        $metrics['p95_response_time'] = $requestMetrics['p95'] ?? null;
        $metrics['p99_response_time'] = $requestMetrics['p99'] ?? null;

        // Error metrics (from logs or cache)
        $errorMetrics = $this->getErrorMetrics();
        $metrics['error_count'] = $errorMetrics['count'] ?? 0;
        $metrics['error_rate'] = $errorMetrics['rate'] ?? 0;
        $metrics['http_2xx'] = $errorMetrics['2xx'] ?? 0;
        $metrics['http_3xx'] = $errorMetrics['3xx'] ?? 0;
        $metrics['http_4xx'] = $errorMetrics['4xx'] ?? 0;
        $metrics['http_5xx'] = $errorMetrics['5xx'] ?? 0;

        return $metrics;
    }

    /**
     * Get database metrics
     */
    protected function getDatabaseMetrics(): array
    {
        $metrics = [];

        try {
            // MySQL process list
            $processes = DB::select('SHOW PROCESSLIST');
            $metrics['db_connections_active'] = count($processes);

            // MySQL max connections
            $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"');
            if (!empty($maxConnections)) {
                $metrics['db_connections_total'] = (int) $maxConnections[0]->Value;
            }

            // Slow queries
            $slowQueries = DB::select('SHOW GLOBAL STATUS LIKE "Slow_queries"');
            if (!empty($slowQueries)) {
                $currentSlowQueries = (int) $slowQueries[0]->Value;
                $previousSlowQueries = Cache::get('analytics.slow_queries', 0);
                $metrics['db_slow_queries'] = max(0, $currentSlowQueries - $previousSlowQueries);
                Cache::put('analytics.slow_queries', $currentSlowQueries, 3600);
            }

            // Average query time (estimate from slow query log or profiling)
            // This would need query profiling enabled
            $metrics['db_avg_query_time'] = null;

            // Deadlocks (InnoDB specific)
            try {
                $innodbStatus = DB::select('SHOW ENGINE INNODB STATUS')[0] ?? null;
                if ($innodbStatus) {
                    // Parse deadlock count from status (simplified)
                    $metrics['db_deadlocks'] = 0;
                }
            } catch (\Exception $e) {
                $metrics['db_deadlocks'] = 0;
            }
        } catch (\Exception $e) {
            // Database metrics unavailable
            $metrics['db_connections_active'] = null;
            $metrics['db_connections_total'] = null;
            $metrics['db_slow_queries'] = 0;
            $metrics['db_deadlocks'] = 0;
        }

        return $metrics;
    }

    /**
     * Get cache metrics (Redis)
     */
    protected function getCacheMetrics(): array
    {
        $metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_hit_rate' => null,
            'cache_memory_used' => null,
            'cache_memory_total' => null,
            'cache_keys_count' => null,
        ];

        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();

                // Get Redis INFO
                $info = $redis->info();

                // Keyspace hits/misses
                if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                    $currentHits = (int) $info['keyspace_hits'];
                    $currentMisses = (int) $info['keyspace_misses'];

                    $previousHits = Cache::get('analytics.cache_hits', 0);
                    $previousMisses = Cache::get('analytics.cache_misses', 0);

                    $metrics['cache_hits'] = max(0, $currentHits - $previousHits);
                    $metrics['cache_misses'] = max(0, $currentMisses - $previousMisses);

                    $totalOps = $metrics['cache_hits'] + $metrics['cache_misses'];
                    if ($totalOps > 0) {
                        $metrics['cache_hit_rate'] = round(($metrics['cache_hits'] / $totalOps) * 100, 2);
                    }

                    Cache::put('analytics.cache_hits', $currentHits, 3600);
                    Cache::put('analytics.cache_misses', $currentMisses, 3600);
                }

                // Memory usage
                if (isset($info['used_memory'])) {
                    $metrics['cache_memory_used'] = (int) $info['used_memory'];
                }

                if (isset($info['maxmemory'])) {
                    $metrics['cache_memory_total'] = (int) $info['maxmemory'];
                }

                // Total keys count
                $metrics['cache_keys_count'] = (int) $redis->dbsize();
            }
        } catch (\Exception $e) {
            // Redis not available, keep null values
        }

        return $metrics;
    }

    /**
     * Get queue metrics
     */
    protected function getQueueMetrics(): array
    {
        $metrics = [
            'queue_pending' => 0,
            'queue_processing' => 0,
            'queue_failed' => 0,
            'queue_avg_wait_time' => null,
        ];

        try {
            // Get queue size from database (if using database queue)
            if (config('queue.default') === 'database') {
                $metrics['queue_pending'] = DB::table('jobs')->where('reserved_at', null)->count();
                $metrics['queue_processing'] = DB::table('jobs')->where('reserved_at', '!=', null)->count();
                $metrics['queue_failed'] = DB::table('failed_jobs')->whereDate('failed_at', today())->count();
            }

            // Redis queue
            if (config('queue.default') === 'redis') {
                try {
                    $redis = Redis::connection();
                    $queueName = 'queues:default';
                    $metrics['queue_pending'] = $redis->llen($queueName);
                } catch (\Exception $e) {
                    // Redis queue not available
                }
            }
        } catch (\Exception $e) {
            // Queue metrics unavailable
        }

        return $metrics;
    }

    /**
     * Get business metrics
     */
    protected function getBusinessMetrics(): array
    {
        return [
            // Users currently online (active in last 15 min)
            'users_online' => $this->getActiveUsersCount(),

            // New user registrations today
            'new_users_today' => User::whereDate('created_at', today())->count(),

            // Revenue today (paid commissions + transactions)
            'revenue_today' => $this->getTodayRevenue(),

            // Transactions processed today
            'transactions_today' => PaymentTransaction::whereDate('created_at', today())
                ->where('status', 'completed')
                ->count(),

            // Active MLM members
            'active_affiliates' => MlmMember::where('is_qualified', true)->count(),
        ];
    }

    /**
     * Get security metrics
     */
    protected function getSecurityMetrics(): array
    {
        return [
            // Failed login attempts (last hour)
            'failed_logins' => SecurityLog::where('event_type', 'failed_login')
                ->where('created_at', '>=', now()->subHour())
                ->count(),

            // Currently blocked IPs
            'blocked_ips' => BlockedIp::where('expires_at', '>', now())
                ->orWhereNull('expires_at')
                ->count(),

            // Auto-bans triggered today
            'auto_bans' => BlockedIp::whereDate('created_at', today())
                ->where('reason', 'like', '%auto-ban%')
                ->count(),

            // CAPTCHA failures (last hour)
            'captcha_failures' => SecurityLog::where('event_type', 'captcha_failed')
                ->where('created_at', '>=', now()->subHour())
                ->count(),
        ];
    }

    /**
     * Helper: Get CPU count
     */
    protected function getCpuCount(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $cpuInfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuInfo) {
                preg_match_all('/^processor/m', $cpuInfo, $matches);
                return count($matches[0]) ?: 1;
            }
        }
        return 1;
    }

    /**
     * Helper: Get memory info (Linux)
     */
    protected function getMemoryInfo(): ?array
    {
        if (!file_exists('/proc/meminfo')) {
            return null;
        }

        $memInfo = @file_get_contents('/proc/meminfo');
        if (!$memInfo) {
            return null;
        }

        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available);

        if (empty($total) || empty($available)) {
            return null;
        }

        $totalMem = (int) $total[1];
        $availableMem = (int) $available[1];
        $usedMem = $totalMem - $availableMem;

        return [
            'total' => $totalMem * 1024,
            'used' => $usedMem * 1024,
            'available' => $availableMem * 1024,
            'usage_percent' => round(($usedMem / $totalMem) * 100, 2),
        ];
    }

    /**
     * Helper: Get disk info
     */
    protected function getDiskInfo(): ?array
    {
        $path = base_path();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if (!$total || !$free) {
            return null;
        }

        $used = $total - $free;

        return [
            'total' => $total,
            'free' => $free,
            'used' => $used,
            'usage_percent' => round(($used / $total) * 100, 2),
        ];
    }

    /**
     * Helper: Get active users count
     */
    protected function getActiveUsersCount(): int
    {
        // Users who have activity in the last 15 minutes
        // This could be tracked via sessions or a last_activity column
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(15)->timestamp)
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id');
        } catch (\Exception $e) {
            // Fallback: estimate from sessions table
            return 0;
        }
    }

    /**
     * Helper: Get active sessions count
     */
    protected function getActiveSessionsCount(): int
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(120)->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Get request metrics
     * This would typically come from application performance monitoring
     * For now, we'll use cached values that should be populated by middleware
     */
    protected function getRequestMetrics(): array
    {
        return [
            'rate' => Cache::get('analytics.request_rate', 0),
            'avg_time' => Cache::get('analytics.avg_response_time'),
            'p50' => Cache::get('analytics.p50_response_time'),
            'p95' => Cache::get('analytics.p95_response_time'),
            'p99' => Cache::get('analytics.p99_response_time'),
        ];
    }

    /**
     * Helper: Get error metrics
     */
    protected function getErrorMetrics(): array
    {
        return [
            'count' => Cache::get('analytics.error_count', 0),
            'rate' => Cache::get('analytics.error_rate', 0),
            '2xx' => Cache::get('analytics.http_2xx', 0),
            '3xx' => Cache::get('analytics.http_3xx', 0),
            '4xx' => Cache::get('analytics.http_4xx', 0),
            '5xx' => Cache::get('analytics.http_5xx', 0),
        ];
    }

    /**
     * Helper: Get today's revenue
     */
    protected function getTodayRevenue(): float
    {
        $commissions = MlmCommission::whereDate('created_at', today())
            ->where('status', 'paid')
            ->sum('commission_amount');

        $transactions = PaymentTransaction::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('amount');

        return $commissions + $transactions;
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getDashboardSummary(): array
    {
        $latest = SystemAnalytic::getLatest();
        $last24h = SystemAnalytic::recent(1440)->get(); // 24 hours

        return [
            'current' => $latest,
            'health' => SystemAnalytic::getHealthStatus(),
            'averages_24h' => SystemAnalytic::getAverages(now()->subDay()),
            'peak_users_24h' => $last24h->max('active_users'),
            'peak_requests_24h' => $last24h->max('request_rate'),
            'total_requests_24h' => $last24h->sum('http_2xx') + $last24h->sum('http_3xx') +
                                   $last24h->sum('http_4xx') + $last24h->sum('http_5xx'),
        ];
    }
}
