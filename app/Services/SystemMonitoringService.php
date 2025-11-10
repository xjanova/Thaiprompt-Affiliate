<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis as RedisClient;

class SystemMonitoringService
{
    /**
     * Get real-time system metrics
     */
    public function getRealtimeMetrics(): array
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'connections' => $this->getActiveConnections(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get CPU usage percentage
     */
    public function getCpuUsage(): array
    {
        $load = sys_getloadavg();
        $cpuCount = $this->getCpuCount();

        // Calculate CPU usage percentage
        $usage = ($load[0] / $cpuCount) * 100;
        $usage = min(100, max(0, $usage)); // Clamp between 0-100

        return [
            'percentage' => round($usage, 2),
            'load_1min' => round($load[0], 2),
            'load_5min' => round($load[1], 2),
            'load_15min' => round($load[2], 2),
            'cpu_cores' => $cpuCount,
            'status' => $this->getStatusLevel($usage),
        ];
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(): array
    {
        $memInfo = $this->parseMemInfo();

        $total = $memInfo['MemTotal'] ?? 1;
        $free = $memInfo['MemFree'] ?? 0;
        $available = $memInfo['MemAvailable'] ?? $free;
        $buffers = $memInfo['Buffers'] ?? 0;
        $cached = $memInfo['Cached'] ?? 0;

        $used = $total - $available;
        $percentage = ($used / $total) * 100;

        return [
            'percentage' => round($percentage, 2),
            'total' => $this->formatBytes($total * 1024),
            'used' => $this->formatBytes($used * 1024),
            'free' => $this->formatBytes($available * 1024),
            'buffers' => $this->formatBytes($buffers * 1024),
            'cached' => $this->formatBytes($cached * 1024),
            'total_bytes' => $total * 1024,
            'used_bytes' => $used * 1024,
            'status' => $this->getStatusLevel($percentage),
        ];
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(): array
    {
        $path = base_path();

        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $percentage = ($used / $total) * 100;

        return [
            'percentage' => round($percentage, 2),
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'total_bytes' => $total,
            'used_bytes' => $used,
            'status' => $this->getStatusLevel($percentage),
        ];
    }

    /**
     * Get active connections (sessions + database)
     */
    public function getActiveConnections(): array
    {
        $sessions = $this->getActiveSessions();
        $dbConnections = $this->getDatabaseConnections();

        return [
            'sessions' => $sessions,
            'database' => $dbConnections,
            'total' => $sessions + $dbConnections,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get active sessions count
     */
    protected function getActiveSessions(): int
    {
        try {
            // For file-based sessions
            if (config('session.driver') === 'file') {
                $sessionPath = storage_path('framework/sessions');
                if (!is_dir($sessionPath)) {
                    return 0;
                }

                // Count files modified in last 30 minutes (active sessions)
                $count = 0;
                $cutoff = time() - 1800; // 30 minutes

                foreach (glob($sessionPath . '/*') as $file) {
                    if (filemtime($file) > $cutoff) {
                        $count++;
                    }
                }

                return $count;
            }

            // For database sessions
            if (config('session.driver') === 'database') {
                return DB::table(config('session.table', 'sessions'))
                    ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                    ->count();
            }

            // For Redis sessions
            if (config('session.driver') === 'redis') {
                try {
                    $keys = RedisClient::keys('laravel_session:*');
                    return count($keys);
                } catch (\Exception $e) {
                    return 0;
                }
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get database connections
     */
    protected function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS WHERE variable_name = 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get database metrics
     */
    public function getDatabaseMetrics(): array
    {
        try {
            $metrics = [];

            // Get database size
            $dbName = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT
                    SUM(data_length + index_length) as size,
                    COUNT(*) as tables
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$dbName]);

            $metrics['size'] = $this->formatBytes($result[0]->size ?? 0);
            $metrics['tables'] = $result[0]->tables ?? 0;

            // Get query statistics
            $stats = DB::select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Queries', 'Slow_queries', 'Uptime')");
            foreach ($stats as $stat) {
                if ($stat->Variable_name === 'Queries') {
                    $metrics['total_queries'] = (int)$stat->Value;
                } elseif ($stat->Variable_name === 'Slow_queries') {
                    $metrics['slow_queries'] = (int)$stat->Value;
                } elseif ($stat->Variable_name === 'Uptime') {
                    $metrics['uptime_seconds'] = (int)$stat->Value;
                }
            }

            // Calculate queries per second
            if (isset($metrics['total_queries']) && isset($metrics['uptime_seconds']) && $metrics['uptime_seconds'] > 0) {
                $metrics['queries_per_second'] = round($metrics['total_queries'] / $metrics['uptime_seconds'], 2);
            }

            return $metrics;
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to fetch database metrics',
                'size' => '0 B',
                'tables' => 0,
            ];
        }
    }

    /**
     * Get cache metrics
     */
    public function getCacheMetrics(): array
    {
        try {
            $driver = config('cache.default');

            if ($driver === 'redis') {
                try {
                    $info = RedisClient::info();

                    return [
                        'driver' => 'redis',
                        'status' => 'connected',
                        'memory_used' => $this->formatBytes($info['used_memory'] ?? 0),
                        'keys' => $info['db0']['keys'] ?? 0,
                        'hits' => $info['keyspace_hits'] ?? 0,
                        'misses' => $info['keyspace_misses'] ?? 0,
                        'hit_rate' => $this->calculateHitRate($info['keyspace_hits'] ?? 0, $info['keyspace_misses'] ?? 0),
                    ];
                } catch (\Exception $e) {
                    return [
                        'driver' => 'redis',
                        'status' => 'disconnected',
                        'error' => 'Redis not available',
                    ];
                }
            }

            return [
                'driver' => $driver,
                'status' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
            ];
        }
    }

    /**
     * Get historical metrics for charts (last 60 data points)
     */
    public function getHistoricalMetrics(): array
    {
        $key = 'system_metrics_history';
        $history = Cache::get($key, []);

        // Keep only last 60 points (1 minute at 1 point per second)
        if (count($history) > 60) {
            $history = array_slice($history, -60);
        }

        return $history;
    }

    /**
     * Store current metrics in history
     */
    public function storeMetricsHistory(): void
    {
        $key = 'system_metrics_history';
        $history = Cache::get($key, []);

        $metrics = $this->getRealtimeMetrics();

        $history[] = [
            'timestamp' => now()->format('H:i:s'),
            'cpu' => $metrics['cpu']['percentage'],
            'memory' => $metrics['memory']['percentage'],
            'connections' => $metrics['connections']['total'],
        ];

        // Keep only last 60 points
        if (count($history) > 60) {
            $history = array_slice($history, -60);
        }

        Cache::put($key, $history, now()->addMinutes(5));
    }

    /**
     * Parse /proc/meminfo
     */
    protected function parseMemInfo(): array
    {
        $memInfo = [];

        if (!file_exists('/proc/meminfo')) {
            return $memInfo;
        }

        $lines = file('/proc/meminfo');
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $memInfo[$matches[1]] = (int)$matches[2];
            }
        }

        return $memInfo;
    }

    /**
     * Get CPU count
     */
    protected function getCpuCount(): int
    {
        if (!file_exists('/proc/cpuinfo')) {
            return 1;
        }

        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);

        return count($matches[0]) ?: 1;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate($hits, $misses): float
    {
        $total = $hits + $misses;
        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Get status level based on percentage
     */
    protected function getStatusLevel($percentage): string
    {
        if ($percentage < 50) {
            return 'good';
        } elseif ($percentage < 75) {
            return 'warning';
        } elseif ($percentage < 90) {
            return 'critical';
        }

        return 'danger';
    }

    /**
     * Get network statistics
     */
    public function getNetworkStats(): array
    {
        try {
            $stats = [];

            if (file_exists('/proc/net/dev')) {
                $lines = file('/proc/net/dev');

                foreach ($lines as $line) {
                    if (preg_match('/^\s*(eth\d+|ens\d+|enp\d+s\d+):\s*(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $line, $matches)) {
                        $interface = $matches[1];
                        $rxBytes = (int)$matches[2];
                        $txBytes = (int)$matches[3];

                        $stats[] = [
                            'interface' => $interface,
                            'received' => $this->formatBytes($rxBytes),
                            'transmitted' => $this->formatBytes($txBytes),
                            'total' => $this->formatBytes($rxBytes + $txBytes),
                        ];
                    }
                }
            }

            return $stats;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get PHP/Laravel info
     */
    public function getApplicationInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
        ];
    }
}
