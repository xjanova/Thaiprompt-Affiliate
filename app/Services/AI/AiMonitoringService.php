<?php

namespace App\Services\AI;

use App\Models\AiUsageLog;
use App\Models\AiConversation;
use App\Models\AiBotProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AiMonitoringService
{
    private LocalAiManager $localAiManager;

    public function __construct()
    {
        $this->localAiManager = new LocalAiManager();
    }

    /**
     * ดึง metrics ทั้งหมดสำหรับ real-time monitoring
     */
    public function getAllMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'requests' => $this->getRequestMetrics(),
            'conversations' => $this->getConversationMetrics(),
            'models' => $this->getModelMetrics(),
            'providers' => $this->getProviderMetrics(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * ดึง System Metrics (CPU, RAM, GPU)
     */
    public function getSystemMetrics(): array
    {
        $cacheKey = 'ai_monitoring:system_metrics';

        return Cache::remember($cacheKey, 5, function () {
            $status = $this->localAiManager->getStatus();

            if (!$status['running']) {
                return [
                    'status' => 'stopped',
                    'cpu_percent' => 0,
                    'memory_gb' => 0,
                    'memory_percent' => 0,
                    'gpu' => null,
                ];
            }

            $resourceUsage = $status['resource_usage'] ?? [];

            return [
                'status' => 'running',
                'uptime' => $status['uptime'],
                'cpu_percent' => $resourceUsage['cpu_percent'] ?? 0,
                'memory_gb' => $resourceUsage['memory_gb'] ?? 0,
                'memory_mb' => $resourceUsage['memory_mb'] ?? 0,
                'memory_percent' => $resourceUsage['memory_percent'] ?? 0,
                'gpu' => $resourceUsage['gpu'] ?? null,
                'loaded_models' => $this->localAiManager->getLoadedModels(),
            ];
        });
    }

    /**
     * ดึง Request Metrics
     */
    public function getRequestMetrics(int $minutes = 60): array
    {
        $startTime = now()->subMinutes($minutes);

        // Total requests
        $totalRequests = AiUsageLog::where('created_at', '>=', $startTime)->count();

        // Successful vs Failed
        $successfulRequests = AiUsageLog::where('created_at', '>=', $startTime)
            ->where('status', 'success')
            ->count();

        $failedRequests = AiUsageLog::where('created_at', '>=', $startTime)
            ->where('status', 'error')
            ->count();

        // Average response time
        $avgResponseTime = AiUsageLog::where('created_at', '>=', $startTime)
            ->where('status', 'success')
            ->avg('response_time_ms');

        // Requests per minute (ในช่วง 5 นาทีล่าสุด)
        $recentRequests = AiUsageLog::where('created_at', '>=', now()->subMinutes(5))
            ->count();
        $requestsPerMinute = round($recentRequests / 5, 2);

        // Total tokens used
        $totalTokens = AiUsageLog::where('created_at', '>=', $startTime)
            ->sum('total_tokens');

        // Total cost
        $totalCost = AiUsageLog::where('created_at', '>=', $startTime)
            ->sum('cost');

        return [
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $failedRequests,
            'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            'avg_response_time_ms' => round($avgResponseTime ?? 0, 2),
            'requests_per_minute' => $requestsPerMinute,
            'total_tokens' => $totalTokens,
            'total_cost' => round($totalCost, 4),
            'period_minutes' => $minutes,
        ];
    }

    /**
     * ดึง Conversation Metrics
     */
    public function getConversationMetrics(): array
    {
        // Active conversations (มีข้อความใน 1 ชั่วโมงล่าสุด)
        $activeConversations = AiConversation::where('last_message_at', '>=', now()->subHour())
            ->where('status', 'active')
            ->count();

        // Total conversations today
        $conversationsToday = AiConversation::whereDate('started_at', today())
            ->count();

        // Total messages today
        $messagesToday = AiConversation::whereDate('started_at', today())
            ->sum('total_messages');

        // Average messages per conversation
        $avgMessagesPerConv = AiConversation::whereDate('started_at', today())
            ->avg('total_messages');

        // Top active conversations
        $topConversations = AiConversation::where('last_message_at', '>=', now()->subHour())
            ->orderBy('total_messages', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'total_messages', 'last_message_at'])
            ->toArray();

        return [
            'active_conversations' => $activeConversations,
            'conversations_today' => $conversationsToday,
            'messages_today' => $messagesToday,
            'avg_messages_per_conversation' => round($avgMessagesPerConv ?? 0, 1),
            'top_conversations' => $topConversations,
        ];
    }

    /**
     * ดึง Model Metrics
     */
    public function getModelMetrics(): array
    {
        // Usage by model (ใน 24 ชั่วโมงล่าสุด)
        $modelUsage = AiUsageLog::select(
            'model_id',
            DB::raw('COUNT(*) as request_count'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('AVG(response_time_ms) as avg_response_time'),
            DB::raw('SUM(cost) as total_cost')
        )
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('model_id')
            ->with('model:id,display_name,model_identifier')
            ->get()
            ->map(function ($item) {
                return [
                    'model_id' => $item->model_id,
                    'model_name' => $item->model->display_name ?? 'Unknown',
                    'model_identifier' => $item->model->model_identifier ?? 'unknown',
                    'request_count' => $item->request_count,
                    'total_tokens' => $item->total_tokens,
                    'avg_response_time_ms' => round($item->avg_response_time, 2),
                    'total_cost' => round($item->total_cost, 4),
                ];
            })
            ->toArray();

        return [
            'model_usage' => $modelUsage,
            'most_used_model' => !empty($modelUsage) ? $modelUsage[0] : null,
        ];
    }

    /**
     * ดึง Provider Metrics
     */
    public function getProviderMetrics(): array
    {
        // Usage by provider (ใน 24 ชั่วโมงล่าสุด)
        $providerUsage = AiUsageLog::select(
            'provider_id',
            DB::raw('COUNT(*) as request_count'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('AVG(response_time_ms) as avg_response_time'),
            DB::raw('SUM(cost) as total_cost')
        )
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('provider_id')
            ->with('provider:id,display_name,provider_type')
            ->get()
            ->map(function ($item) {
                return [
                    'provider_id' => $item->provider_id,
                    'provider_name' => $item->provider->display_name ?? 'Unknown',
                    'provider_type' => $item->provider->provider_type ?? 'unknown',
                    'request_count' => $item->request_count,
                    'total_tokens' => $item->total_tokens,
                    'avg_response_time_ms' => round($item->avg_response_time, 2),
                    'total_cost' => round($item->total_cost, 4),
                ];
            })
            ->toArray();

        return [
            'provider_usage' => $providerUsage,
        ];
    }

    /**
     * ดึง Time Series Data สำหรับกราฟ
     */
    public function getTimeSeriesData(string $metric, int $hours = 24, int $intervalMinutes = 30): array
    {
        $startTime = now()->subHours($hours);
        $intervals = [];

        // สร้าง intervals
        for ($i = 0; $i < ($hours * 60 / $intervalMinutes); $i++) {
            $intervalStart = $startTime->copy()->addMinutes($i * $intervalMinutes);
            $intervalEnd = $intervalStart->copy()->addMinutes($intervalMinutes);

            $intervals[] = [
                'start' => $intervalStart,
                'end' => $intervalEnd,
                'label' => $intervalStart->format('H:i'),
            ];
        }

        $data = [];

        switch ($metric) {
            case 'requests':
                $data = $this->getRequestsTimeSeries($intervals);
                break;

            case 'response_time':
                $data = $this->getResponseTimeTimeSeries($intervals);
                break;

            case 'tokens':
                $data = $this->getTokensTimeSeries($intervals);
                break;

            case 'cost':
                $data = $this->getCostTimeSeries($intervals);
                break;

            case 'cpu':
            case 'memory':
            case 'gpu':
                // สำหรับ system metrics ต้องเก็บข้อมูลใน cache หรือ database แบบ time-series
                $data = $this->getSystemResourceTimeSeries($metric, $intervals);
                break;
        }

        return [
            'metric' => $metric,
            'hours' => $hours,
            'interval_minutes' => $intervalMinutes,
            'labels' => array_column($intervals, 'label'),
            'data' => $data,
        ];
    }

    /**
     * Request time series
     */
    private function getRequestsTimeSeries(array $intervals): array
    {
        $data = [];

        foreach ($intervals as $interval) {
            $count = AiUsageLog::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->count();

            $data[] = $count;
        }

        return $data;
    }

    /**
     * Response time time series
     */
    private function getResponseTimeTimeSeries(array $intervals): array
    {
        $data = [];

        foreach ($intervals as $interval) {
            $avg = AiUsageLog::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->where('status', 'success')
                ->avg('response_time_ms');

            $data[] = round($avg ?? 0, 2);
        }

        return $data;
    }

    /**
     * Tokens time series
     */
    private function getTokensTimeSeries(array $intervals): array
    {
        $data = [];

        foreach ($intervals as $interval) {
            $total = AiUsageLog::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->sum('total_tokens');

            $data[] = $total;
        }

        return $data;
    }

    /**
     * Cost time series
     */
    private function getCostTimeSeries(array $intervals): array
    {
        $data = [];

        foreach ($intervals as $interval) {
            $total = AiUsageLog::whereBetween('created_at', [$interval['start'], $interval['end']])
                ->sum('cost');

            $data[] = round($total, 4);
        }

        return $data;
    }

    /**
     * System resource time series (จาก cache)
     */
    private function getSystemResourceTimeSeries(string $metric, array $intervals): array
    {
        // ดึงจาก cache ที่เก็บไว้
        $cacheKey = "ai_monitoring:timeseries:{$metric}";
        $cachedData = Cache::get($cacheKey, []);

        // ถ้าไม่มีข้อมูลใน cache ให้ return 0 ทั้งหมด
        if (empty($cachedData)) {
            return array_fill(0, count($intervals), 0);
        }

        $data = [];

        foreach ($intervals as $interval) {
            // หาค่าที่ใกล้เคียงกับ interval นี้
            $value = 0;

            foreach ($cachedData as $timestamp => $cachedValue) {
                if ($timestamp >= $interval['start']->timestamp && $timestamp < $interval['end']->timestamp) {
                    $value = $cachedValue;
                    break;
                }
            }

            $data[] = $value;
        }

        return $data;
    }

    /**
     * บันทึก system metrics ลง cache สำหรับ time series
     */
    public function recordSystemMetrics(): void
    {
        $metrics = $this->getSystemMetrics();

        if ($metrics['status'] !== 'running') {
            return;
        }

        $timestamp = now()->timestamp;

        // บันทึก CPU
        $this->appendToTimeSeries('cpu', $timestamp, $metrics['cpu_percent']);

        // บันทึก Memory
        $this->appendToTimeSeries('memory', $timestamp, $metrics['memory_percent']);

        // บันทึก GPU (ถ้ามี)
        if ($metrics['gpu'] && !empty($metrics['gpu']['gpus'])) {
            $gpuUtil = $metrics['gpu']['gpus'][0]['utilization_percent'];
            $this->appendToTimeSeries('gpu', $timestamp, $gpuUtil);
        }
    }

    /**
     * เพิ่มข้อมูลลง time series cache
     */
    private function appendToTimeSeries(string $metric, int $timestamp, $value): void
    {
        $cacheKey = "ai_monitoring:timeseries:{$metric}";
        $data = Cache::get($cacheKey, []);

        // เพิ่มข้อมูลใหม่
        $data[$timestamp] = $value;

        // เก็บข้อมูลแค่ 24 ชั่วโมงล่าสุด
        $cutoff = now()->subHours(24)->timestamp;
        $data = array_filter($data, function ($ts) use ($cutoff) {
            return $ts >= $cutoff;
        }, ARRAY_FILTER_USE_KEY);

        // บันทึกกลับไปที่ cache (เก็บ 25 ชั่วโมง เผื่อ)
        Cache::put($cacheKey, $data, now()->addHours(25));
    }

    /**
     * ดึงสถิติสรุปสำหรับ Dashboard
     */
    public function getDashboardSummary(): array
    {
        $system = $this->getSystemMetrics();
        $requests = $this->getRequestMetrics(60);
        $conversations = $this->getConversationMetrics();

        return [
            'system_status' => $system['status'],
            'cpu_usage' => $system['cpu_percent'],
            'memory_usage' => $system['memory_percent'],
            'gpu_usage' => $system['gpu']['gpus'][0]['utilization_percent'] ?? 0,
            'active_conversations' => $conversations['active_conversations'],
            'requests_per_minute' => $requests['requests_per_minute'],
            'success_rate' => $requests['success_rate'],
            'avg_response_time' => $requests['avg_response_time_ms'],
            'total_requests_hour' => $requests['total_requests'],
            'total_cost_hour' => $requests['total_cost'],
        ];
    }
}
