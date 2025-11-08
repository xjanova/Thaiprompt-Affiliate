<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAnalytic;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the analytics dashboard
     */
    public function index()
    {
        $summary = $this->analyticsService->getDashboardSummary();

        return view('admin.analytics.index', compact('summary'));
    }

    /**
     * Get real-time metrics data (AJAX)
     */
    public function realtime()
    {
        $latest = SystemAnalytic::getLatest();
        $health = SystemAnalytic::getHealthStatus();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $latest,
                'health' => $health,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get historical data for charts
     */
    public function historical(Request $request)
    {
        $period = $request->input('period', '24h'); // 24h, 7d, 30d

        $start = match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $metrics = SystemAnalytic::where('recorded_at', '>=', $start)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'start' => $start->toIso8601String(),
                'end' => now()->toIso8601String(),
                'metrics' => $metrics,
            ],
        ]);
    }

    /**
     * Get system performance data
     */
    public function performance(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'cpu_usage', 'memory_usage', 'disk_usage', 'avg_response_time']);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get database metrics
     */
    public function database(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get([
                'recorded_at',
                'db_connections_active',
                'db_connections_total',
                'db_avg_query_time',
                'db_slow_queries',
            ]);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get cache metrics
     */
    public function cache(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get([
                'recorded_at',
                'cache_hits',
                'cache_misses',
                'cache_hit_rate',
                'cache_memory_used',
                'cache_keys_count',
            ]);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get traffic metrics
     */
    public function traffic(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get([
                'recorded_at',
                'active_users',
                'active_sessions',
                'request_rate',
                'http_2xx',
                'http_3xx',
                'http_4xx',
                'http_5xx',
            ]);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get business metrics
     */
    public function business(Request $request)
    {
        $days = $request->input('days', 30);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get([
                'recorded_at',
                'users_online',
                'new_users_today',
                'revenue_today',
                'transactions_today',
                'active_affiliates',
            ]);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get security metrics
     */
    public function security(Request $request)
    {
        $hours = $request->input('hours', 24);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get([
                'recorded_at',
                'failed_logins',
                'blocked_ips',
                'auto_bans',
                'captcha_failures',
            ]);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Manually trigger metrics collection
     */
    public function collect()
    {
        try {
            $metric = $this->analyticsService->collectMetrics();

            return response()->json([
                'success' => true,
                'message' => 'Metrics collected successfully',
                'data' => $metric,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to collect metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system capacity report
     */
    public function capacity()
    {
        $latest = SystemAnalytic::getLatest();
        $peak24h = SystemAnalytic::recent(1440)->get();

        $report = [
            'current_load' => [
                'active_users' => $latest->active_users ?? 0,
                'request_rate' => $latest->request_rate ?? 0,
                'cpu_usage' => $latest->cpu_usage ?? 0,
                'memory_usage' => $latest->memory_usage ?? 0,
                'db_connections' => $latest->db_connections_active ?? 0,
            ],
            'peak_24h' => [
                'active_users' => $peak24h->max('active_users'),
                'request_rate' => $peak24h->max('request_rate'),
                'cpu_usage' => $peak24h->max('cpu_usage'),
                'memory_usage' => $peak24h->max('memory_usage'),
            ],
            'capacity_estimates' => [
                'max_users_current_config' => 2000,
                'max_requests_per_second' => 500,
                'database_connection_limit' => $latest->db_connections_total ?? 151,
            ],
            'recommendations' => $this->getCapacityRecommendations($latest),
        ];

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get capacity recommendations based on current metrics
     */
    protected function getCapacityRecommendations($latest): array
    {
        $recommendations = [];

        if ($latest->cpu_usage > 70) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'CPU usage is high. Consider adding more workers or scaling horizontally.',
            ];
        }

        if ($latest->memory_usage > 80) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'Memory usage is high. Review memory leaks or increase server RAM.',
            ];
        }

        if ($latest->db_connections_total && $latest->db_connections_active) {
            $connectionUsage = ($latest->db_connections_active / $latest->db_connections_total) * 100;
            if ($connectionUsage > 70) {
                $recommendations[] = [
                    'severity' => 'medium',
                    'message' => 'Database connection pool is at ' . round($connectionUsage) . '%. Consider adding read replicas or increasing pool size.',
                ];
            }
        }

        if ($latest->cache_hit_rate !== null && $latest->cache_hit_rate < 70) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => 'Cache hit rate is low (' . $latest->cache_hit_rate . '%). Review caching strategy.',
            ];
        }

        if ($latest->avg_response_time > 500) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'Average response time is ' . $latest->avg_response_time . 'ms. Optimize slow queries and add caching.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'severity' => 'info',
                'message' => 'System is performing well. No immediate optimizations needed.',
            ];
        }

        return $recommendations;
    }

    /**
     * Export analytics data as CSV
     */
    public function export(Request $request)
    {
        $days = $request->input('days', 7);

        $metrics = SystemAnalytic::where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        $filename = 'system-analytics-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($metrics) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Recorded At',
                'Active Users',
                'Request Rate',
                'CPU %',
                'Memory %',
                'Disk %',
                'Avg Response Time',
                'Cache Hit Rate',
                'DB Connections',
                'Error Rate',
            ]);

            // Data rows
            foreach ($metrics as $metric) {
                fputcsv($file, [
                    $metric->recorded_at,
                    $metric->active_users,
                    $metric->request_rate,
                    $metric->cpu_usage,
                    $metric->memory_usage,
                    $metric->disk_usage,
                    $metric->avg_response_time,
                    $metric->cache_hit_rate,
                    $metric->db_connections_active,
                    $metric->error_rate,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
