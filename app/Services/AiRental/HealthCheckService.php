<?php

namespace App\Services\AiRental;

use App\Models\AiRentalHealthCheck;
use App\Models\AiRentalDeployment;
use App\Services\CloudProviders\CloudProviderFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Health Check Service สำหรับ AI Rental System
 *
 * จัดการการตรวจสอบสุขภาพของ deployments อัตโนมัติ
 */
class HealthCheckService
{
    /**
     * @var MonitoringService
     */
    protected MonitoringService $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * ทำ health check สำหรับ deployment
     *
     * @param AiRentalDeployment $deployment
     * @param array $options
     * @return AiRentalHealthCheck
     */
    public function checkDeploymentHealth(AiRentalDeployment $deployment, array $options = []): AiRentalHealthCheck
    {
        $startedAt = now();
        $checkType = $options['check_type'] ?? 'api';
        $checkUrl = $deployment->health_check_url ?? $deployment->api_endpoint;

        try {
            Log::info("Starting health check for deployment {$deployment->id}", [
                'deployment_id' => $deployment->id,
                'check_type' => $checkType,
                'check_url' => $checkUrl,
            ]);

            $result = match ($checkType) {
                'api' => $this->checkApiHealth($checkUrl, $options),
                'ping' => $this->checkPingHealth($checkUrl),
                'websocket' => $this->checkWebSocketHealth($checkUrl),
                default => $this->checkApiHealth($checkUrl, $options),
            };

            $completedAt = now();
            $durationMs = $startedAt->diffInMilliseconds($completedAt);

            // สร้าง health check record
            $healthCheck = AiRentalHealthCheck::create([
                'deployment_id' => $deployment->id,
                'user_id' => $deployment->user_id,
                'check_type' => $checkType,
                'check_url' => $checkUrl,
                'http_method' => $options['http_method'] ?? 'GET',
                'request_headers' => $options['headers'] ?? null,
                'request_body' => $options['body'] ?? null,
                'status' => $result['status'],
                'http_status_code' => $result['http_status_code'] ?? null,
                'response_time_ms' => $result['response_time_ms'] ?? null,
                'is_success' => $result['is_success'],
                'response_body' => $result['response_body'] ?? null,
                'error_message' => $result['error_message'] ?? null,
                'error_details' => $result['error_details'] ?? null,
                'cpu_usage' => $result['cpu_usage'] ?? null,
                'memory_usage' => $result['memory_usage'] ?? null,
                'gpu_usage' => $result['gpu_usage'] ?? null,
                'memory_used_mb' => $result['memory_used_mb'] ?? null,
                'memory_total_mb' => $result['memory_total_mb'] ?? null,
                'disk_usage' => $result['disk_usage'] ?? null,
                'active_connections' => $result['active_connections'] ?? null,
                'queue_length' => $result['queue_length'] ?? null,
                'thresholds' => $options['thresholds'] ?? null,
                'checked_at' => now(),
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'duration_ms' => $durationMs,
                'check_frequency' => $options['check_frequency'] ?? '5min',
                'metadata' => $options['metadata'] ?? null,
                'headers_received' => $result['headers'] ?? null,
            ]);

            // ตรวจสอบ thresholds
            if ($healthCheck->checkThresholds()) {
                $healthCheck->alert_triggered = true;
                $healthCheck->save();

                // ส่ง alert
                $this->handleThresholdExceeded($healthCheck, $deployment);
            }

            // ถ้า health check ล้มเหลว
            if (!$healthCheck->is_success) {
                $this->handleHealthCheckFailed($healthCheck, $deployment);
            }

            // คำนวณเวลา check ครั้งถัดไป
            $healthCheck->next_check_at = $this->calculateNextCheckTime($healthCheck->check_frequency);
            $healthCheck->save();

            return $healthCheck;
        } catch (Exception $e) {
            Log::error("Health check failed with exception: " . $e->getMessage(), [
                'deployment_id' => $deployment->id,
                'exception' => $e,
            ]);

            // สร้าง failed health check record
            return AiRentalHealthCheck::create([
                'deployment_id' => $deployment->id,
                'user_id' => $deployment->user_id,
                'check_type' => $checkType,
                'check_url' => $checkUrl,
                'http_method' => $options['http_method'] ?? 'GET',
                'status' => 'unknown',
                'is_success' => false,
                'error_message' => $e->getMessage(),
                'error_details' => $e->getTraceAsString(),
                'checked_at' => now(),
                'started_at' => $startedAt,
                'completed_at' => now(),
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
                'check_frequency' => $options['check_frequency'] ?? '5min',
                'next_check_at' => $this->calculateNextCheckTime($options['check_frequency'] ?? '5min'),
            ]);
        }
    }

    /**
     * ตรวจสอบ health ผ่าน API
     *
     * @param string $url
     * @param array $options
     * @return array
     */
    protected function checkApiHealth(string $url, array $options = []): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout($options['timeout'] ?? 10)
                ->withHeaders($options['headers'] ?? [])
                ->{$options['http_method'] ?? 'get'}($url, $options['body'] ?? []);

            $endTime = microtime(true);
            $responseTimeMs = ($endTime - $startTime) * 1000;

            $isSuccess = $response->successful();
            $statusCode = $response->status();

            // Parse response สำหรับ metrics (ถ้ามี)
            $body = $response->json() ?? [];

            return [
                'is_success' => $isSuccess,
                'status' => $this->determineHealthStatus($statusCode, $responseTimeMs),
                'http_status_code' => $statusCode,
                'response_time_ms' => round($responseTimeMs, 2),
                'response_body' => json_encode($body),
                'cpu_usage' => $body['metrics']['cpu_usage'] ?? null,
                'memory_usage' => $body['metrics']['memory_usage'] ?? null,
                'gpu_usage' => $body['metrics']['gpu_usage'] ?? null,
                'memory_used_mb' => $body['metrics']['memory_used_mb'] ?? null,
                'memory_total_mb' => $body['metrics']['memory_total_mb'] ?? null,
                'disk_usage' => $body['metrics']['disk_usage'] ?? null,
                'active_connections' => $body['metrics']['active_connections'] ?? null,
                'queue_length' => $body['metrics']['queue_length'] ?? null,
                'headers' => $response->headers(),
                'error_message' => $isSuccess ? null : "HTTP {$statusCode} error",
            ];
        } catch (Exception $e) {
            return [
                'is_success' => false,
                'status' => 'unhealthy',
                'error_message' => $e->getMessage(),
                'error_details' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * ตรวจสอบ health ผ่าน Ping
     *
     * @param string $url
     * @return array
     */
    protected function checkPingHealth(string $url): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout(5)->head($url);

            $endTime = microtime(true);
            $responseTimeMs = ($endTime - $startTime) * 1000;

            $isSuccess = $response->successful();

            return [
                'is_success' => $isSuccess,
                'status' => $isSuccess ? 'healthy' : 'unhealthy',
                'http_status_code' => $response->status(),
                'response_time_ms' => round($responseTimeMs, 2),
                'error_message' => $isSuccess ? null : "Ping failed with status {$response->status()}",
            ];
        } catch (Exception $e) {
            return [
                'is_success' => false,
                'status' => 'unhealthy',
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบ health ผ่าน WebSocket
     *
     * @param string $url
     * @return array
     */
    protected function checkWebSocketHealth(string $url): array
    {
        // TODO: Implement WebSocket health check
        // - Use WebSocket client library
        // - Test connection
        // - Send ping/pong

        return [
            'is_success' => false,
            'status' => 'unknown',
            'error_message' => 'WebSocket health check not implemented yet',
        ];
    }

    /**
     * กำหนด health status จาก status code และ response time
     *
     * @param int $statusCode
     * @param float $responseTimeMs
     * @return string
     */
    protected function determineHealthStatus(int $statusCode, float $responseTimeMs): string
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            // ตรวจสอบ response time
            if ($responseTimeMs < 500) {
                return 'healthy';
            } elseif ($responseTimeMs < 2000) {
                return 'degraded';
            } else {
                return 'unhealthy';
            }
        } elseif ($statusCode >= 500) {
            return 'unhealthy';
        } elseif ($statusCode >= 400) {
            return 'degraded';
        } else {
            return 'unknown';
        }
    }

    /**
     * จัดการเมื่อ health check ล้มเหลว
     *
     * @param AiRentalHealthCheck $healthCheck
     * @param AiRentalDeployment $deployment
     * @return void
     */
    protected function handleHealthCheckFailed(AiRentalHealthCheck $healthCheck, AiRentalDeployment $deployment): void
    {
        // ส่ง alert
        $alert = $this->monitoringService->createHealthCheckFailedAlert($deployment, [
            'error_message' => $healthCheck->error_message,
            'response_time_ms' => $healthCheck->response_time_ms,
            'status_code' => $healthCheck->http_status_code,
        ]);

        $healthCheck->alert_id = $alert->id;
        $healthCheck->save();

        // ลอง retry ถ้ายังไม่เกิน max_retries
        if ($healthCheck->shouldRetry()) {
            $healthCheck->incrementRetry();
            Log::info("Scheduling health check retry #{$healthCheck->retry_count}", [
                'health_check_id' => $healthCheck->id,
                'next_retry_at' => $healthCheck->next_retry_at,
            ]);
        } else {
            // ถึง max retries แล้ว ลอง auto-recovery
            if ($healthCheck->recovery_action !== 'none') {
                $healthCheck->attemptRecovery();
            }
        }
    }

    /**
     * จัดการเมื่อ threshold ถูกเกิน
     *
     * @param AiRentalHealthCheck $healthCheck
     * @param AiRentalDeployment $deployment
     * @return void
     */
    protected function handleThresholdExceeded(AiRentalHealthCheck $healthCheck, AiRentalDeployment $deployment): void
    {
        $thresholds = $healthCheck->thresholds ?? [];
        $exceededThresholds = [];

        if (isset($thresholds['cpu']) && $healthCheck->cpu_usage > $thresholds['cpu']) {
            $exceededThresholds[] = "CPU: {$healthCheck->cpu_usage}% (threshold: {$thresholds['cpu']}%)";
        }

        if (isset($thresholds['memory']) && $healthCheck->memory_usage > $thresholds['memory']) {
            $exceededThresholds[] = "Memory: {$healthCheck->memory_usage}% (threshold: {$thresholds['memory']}%)";
        }

        if (isset($thresholds['gpu']) && $healthCheck->gpu_usage > $thresholds['gpu']) {
            $exceededThresholds[] = "GPU: {$healthCheck->gpu_usage}% (threshold: {$thresholds['gpu']}%)";
        }

        if (!empty($exceededThresholds)) {
            $alert = $this->monitoringService->createAlert([
                'user_id' => $deployment->user_id,
                'deployment_id' => $deployment->id,
                'alert_type' => 'threshold_exceeded',
                'severity' => 'warning',
                'title' => '⚠️ ทรัพยากรเกิน Threshold',
                'message' => "Deployment '{$deployment->deployment_name}' ทรัพยากรเกิน threshold",
                'description' => implode(', ', $exceededThresholds),
                'alert_data' => [
                    'health_check_id' => $healthCheck->id,
                    'cpu_usage' => $healthCheck->cpu_usage,
                    'memory_usage' => $healthCheck->memory_usage,
                    'gpu_usage' => $healthCheck->gpu_usage,
                    'thresholds' => $thresholds,
                    'exceeded' => $exceededThresholds,
                ],
                'action_required' => 'ตรวจสอบการใช้ทรัพยากร',
                'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
                'action_label' => 'ดู Deployment',
                'priority' => 4,
            ]);

            $healthCheck->alert_id = $alert->id;
            $healthCheck->save();
        }
    }

    /**
     * ทำ health check สำหรับ deployments ทั้งหมดที่กำลัง running
     *
     * @return array
     */
    public function checkAllRunningDeployments(): array
    {
        $deployments = AiRentalDeployment::running()->get();

        $results = [
            'total' => $deployments->count(),
            'checked' => 0,
            'healthy' => 0,
            'degraded' => 0,
            'unhealthy' => 0,
            'failed' => 0,
        ];

        foreach ($deployments as $deployment) {
            try {
                $healthCheck = $this->checkDeploymentHealth($deployment);

                $results['checked']++;
                $results[strtolower($healthCheck->status)]++;

                // อัพเดท deployment metrics
                $deployment->avg_response_time_ms = $healthCheck->response_time_ms;
                $deployment->save();
            } catch (Exception $e) {
                $results['failed']++;
                Log::error("Failed to check deployment {$deployment->id}: " . $e->getMessage());
            }
        }

        Log::info("Checked {$results['checked']} running deployments", $results);

        return $results;
    }

    /**
     * ดึง health check history สำหรับ deployment
     *
     * @param int $deploymentId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getHealthCheckHistory(int $deploymentId, int $limit = 50)
    {
        return AiRentalHealthCheck::forDeployment($deploymentId)
            ->recent()
            ->limit($limit)
            ->get();
    }

    /**
     * คำนวณ uptime percentage
     *
     * @param int $deploymentId
     * @param int $hours
     * @return float
     */
    public function calculateUptime(int $deploymentId, int $hours = 24): float
    {
        return AiRentalHealthCheck::calculateUptime($deploymentId, $hours);
    }

    /**
     * คำนวณเวลา check ครั้งถัดไป
     *
     * @param string $frequency
     * @return \Carbon\Carbon
     */
    protected function calculateNextCheckTime(string $frequency): \Carbon\Carbon
    {
        return match ($frequency) {
            '1min' => now()->addMinute(),
            '5min' => now()->addMinutes(5),
            '15min' => now()->addMinutes(15),
            '30min' => now()->addMinutes(30),
            '1hour' => now()->addHour(),
            default => now()->addMinutes(5),
        };
    }

    /**
     * Schedule health checks (ใช้ใน cron job)
     *
     * @return int จำนวน checks ที่ทำ
     */
    public function runScheduledHealthChecks(): int
    {
        $deployments = AiRentalDeployment::running()
            ->whereDoesntHave('healthChecks', function ($query) {
                $query->where('next_check_at', '>', now());
            })
            ->get();

        $count = 0;
        foreach ($deployments as $deployment) {
            try {
                $this->checkDeploymentHealth($deployment);
                $count++;
            } catch (Exception $e) {
                Log::error("Scheduled health check failed for deployment {$deployment->id}: " . $e->getMessage());
            }
        }

        Log::info("Ran {$count} scheduled health checks");

        return $count;
    }
}
