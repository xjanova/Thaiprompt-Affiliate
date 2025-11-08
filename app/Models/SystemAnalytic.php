<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'recorded_at',
        // System Performance
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'disk_free',
        'disk_total',
        // Application Metrics
        'active_users',
        'active_sessions',
        'request_rate',
        'avg_response_time',
        'p50_response_time',
        'p95_response_time',
        'p99_response_time',
        'error_count',
        'error_rate',
        // Database Metrics
        'db_connections_active',
        'db_connections_total',
        'db_avg_query_time',
        'db_slow_queries',
        'db_deadlocks',
        // Cache Metrics
        'cache_hits',
        'cache_misses',
        'cache_hit_rate',
        'cache_memory_used',
        'cache_memory_total',
        'cache_keys_count',
        // Queue Metrics
        'queue_pending',
        'queue_processing',
        'queue_failed',
        'queue_avg_wait_time',
        // Business Metrics
        'users_online',
        'new_users_today',
        'revenue_today',
        'transactions_today',
        'active_affiliates',
        // HTTP Metrics
        'http_2xx',
        'http_3xx',
        'http_4xx',
        'http_5xx',
        // Security Metrics
        'failed_logins',
        'blocked_ips',
        'auto_bans',
        'captcha_failures',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'cpu_usage' => 'decimal:2',
        'memory_usage' => 'decimal:2',
        'disk_usage' => 'decimal:2',
        'request_rate' => 'decimal:2',
        'avg_response_time' => 'decimal:2',
        'p50_response_time' => 'decimal:2',
        'p95_response_time' => 'decimal:2',
        'p99_response_time' => 'decimal:2',
        'error_rate' => 'decimal:2',
        'db_avg_query_time' => 'decimal:2',
        'cache_hit_rate' => 'decimal:2',
        'queue_avg_wait_time' => 'decimal:2',
        'revenue_today' => 'decimal:2',
    ];

    /**
     * Scope: Get metrics for a specific time range
     */
    public function scopeTimeRange($query, $start, $end = null)
    {
        $end = $end ?? now();
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    /**
     * Scope: Get recent metrics
     */
    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: Get today's metrics
     */
    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    /**
     * Get the latest snapshot
     */
    public static function getLatest()
    {
        return static::latest('recorded_at')->first();
    }

    /**
     * Get average metrics for a time period
     */
    public static function getAverages($start, $end = null)
    {
        $end = $end ?? now();

        return static::whereBetween('recorded_at', [$start, $end])
            ->selectRaw('
                AVG(cpu_usage) as avg_cpu,
                AVG(memory_usage) as avg_memory,
                AVG(request_rate) as avg_request_rate,
                AVG(avg_response_time) as avg_response_time,
                AVG(cache_hit_rate) as avg_cache_hit_rate,
                MAX(active_users) as peak_users,
                MAX(request_rate) as peak_request_rate,
                AVG(error_rate) as avg_error_rate
            ')
            ->first();
    }

    /**
     * Get system health status
     */
    public static function getHealthStatus()
    {
        $latest = static::getLatest();

        if (!$latest) {
            return [
                'status' => 'unknown',
                'message' => 'No metrics available',
                'color' => 'gray',
            ];
        }

        // Check critical thresholds
        $issues = [];

        if ($latest->cpu_usage > 80) {
            $issues[] = 'High CPU usage';
        }

        if ($latest->memory_usage > 85) {
            $issues[] = 'High memory usage';
        }

        if ($latest->disk_usage > 90) {
            $issues[] = 'Low disk space';
        }

        if ($latest->error_rate > 5) {
            $issues[] = 'High error rate';
        }

        if ($latest->avg_response_time > 1000) {
            $issues[] = 'Slow response time';
        }

        if ($latest->db_connections_active && $latest->db_connections_total) {
            $connectionUsage = ($latest->db_connections_active / $latest->db_connections_total) * 100;
            if ($connectionUsage > 80) {
                $issues[] = 'Database connection pool near limit';
            }
        }

        // Determine overall status
        if (empty($issues)) {
            return [
                'status' => 'healthy',
                'message' => 'All systems operational',
                'color' => 'green',
                'issues' => [],
            ];
        } elseif (count($issues) <= 2) {
            return [
                'status' => 'warning',
                'message' => 'Some systems need attention',
                'color' => 'yellow',
                'issues' => $issues,
            ];
        } else {
            return [
                'status' => 'critical',
                'message' => 'Multiple systems experiencing issues',
                'color' => 'red',
                'issues' => $issues,
            ];
        }
    }

    /**
     * Clean old metrics (retention policy)
     */
    public static function cleanOldMetrics($daysToKeep = 30)
    {
        return static::where('recorded_at', '<', now()->subDays($daysToKeep))->delete();
    }
}
