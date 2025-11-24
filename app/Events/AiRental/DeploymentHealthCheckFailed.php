<?php

namespace App\Events\AiRental;

use App\Models\AiRentalDeployment;
use App\Models\AiRentalHealthCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Deployment Health Check Failed
 *
 * Event นี้จะถูก dispatch เมื่อ health check ล้มเหลว
 * ใช้สำหรับ:
 * - สร้าง alert แจ้งเตือนผู้ใช้
 * - ลอง auto-recovery (restart, scale up, etc.)
 * - บันทึก incident log
 * - Update uptime metrics
 * - Trigger webhook notifications
 */
class DeploymentHealthCheckFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Deployment ที่ health check ล้มเหลว
     *
     * @var AiRentalDeployment
     */
    public AiRentalDeployment $deployment;

    /**
     * Health check record
     *
     * @var AiRentalHealthCheck
     */
    public AiRentalHealthCheck $healthCheck;

    /**
     * จำนวนครั้งที่ล้มเหลวติดต่อกัน
     *
     * @var int
     */
    public int $consecutiveFailures;

    /**
     * ข้อมูลเพิ่มเติม
     *
     * @var array
     */
    public array $metadata;

    /**
     * สร้าง event instance
     *
     * @param AiRentalDeployment $deployment
     * @param AiRentalHealthCheck $healthCheck
     * @param int $consecutiveFailures จำนวนครั้งที่ล้มเหลวติดต่อกัน
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return void
     *
     * @example
     * event(new DeploymentHealthCheckFailed(
     *     $deployment,
     *     $healthCheck,
     *     3,
     *     ['alert_sent' => true]
     * ));
     */
    public function __construct(
        AiRentalDeployment $deployment,
        AiRentalHealthCheck $healthCheck,
        int $consecutiveFailures = 1,
        array $metadata = []
    ) {
        $this->deployment = $deployment;
        $this->healthCheck = $healthCheck;
        $this->consecutiveFailures = $consecutiveFailures;
        $this->metadata = array_merge([
            'failed_at' => now()->toIso8601String(),
            'detected_by' => 'health_check_service',
        ], $metadata);
    }

    /**
     * ตรวจสอบว่าควร trigger alert หรือไม่
     *
     * @return bool
     */
    public function shouldTriggerAlert(): bool
    {
        // Alert เมื่อล้มเหลวติดต่อกัน >= 3 ครั้ง
        return $this->consecutiveFailures >= 3;
    }

    /**
     * ตรวจสอบว่าควรลอง auto-recovery หรือไม่
     *
     * @return bool
     */
    public function shouldAttemptRecovery(): bool
    {
        // ลอง recovery เมื่อล้มเหลวติดต่อกัน >= 2 ครั้ง
        // และยังไม่เกิน max retry attempts
        return $this->consecutiveFailures >= 2
            && $this->consecutiveFailures <= 5;
    }

    /**
     * ตรวจสอบว่าควร escalate (แจ้งเตือน critical) หรือไม่
     *
     * @return bool
     */
    public function shouldEscalate(): bool
    {
        // Escalate เมื่อล้มเหลวติดต่อกัน >= 5 ครั้ง
        return $this->consecutiveFailures >= 5;
    }

    /**
     * ดึง severity level
     *
     * @return string warning|error|critical
     */
    public function getSeverity(): string
    {
        if ($this->consecutiveFailures >= 5) {
            return 'critical';
        } elseif ($this->consecutiveFailures >= 3) {
            return 'error';
        } else {
            return 'warning';
        }
    }

    /**
     * ดึงข้อมูล error
     *
     * @return array
     */
    public function getErrorDetails(): array
    {
        return [
            'error_message' => $this->healthCheck->error_message,
            'http_status_code' => $this->healthCheck->http_status_code,
            'response_time_ms' => $this->healthCheck->response_time_ms,
            'check_url' => $this->healthCheck->check_url,
            'status' => $this->healthCheck->status,
        ];
    }

    /**
     * แนะนำ recovery action
     *
     * @return string
     */
    public function getRecommendedAction(): string
    {
        if ($this->consecutiveFailures >= 5) {
            return 'manual_intervention_required';
        } elseif ($this->consecutiveFailures >= 3) {
            return 'auto_restart';
        } else {
            return 'monitor';
        }
    }

    /**
     * ประมาณ downtime duration
     *
     * @return int จำนวนนาที
     */
    public function estimateDowntimeMinutes(): int
    {
        // ประมาณจาก check frequency (default: 5 min)
        $checkFrequency = 5;
        return $this->consecutiveFailures * $checkFrequency;
    }

    /**
     * แปลง event เป็น array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'event' => 'deployment.health_check_failed',
            'deployment_id' => $this->deployment->id,
            'deployment_name' => $this->deployment->deployment_name,
            'user_id' => $this->deployment->user_id,
            'health_check_id' => $this->healthCheck->id,
            'consecutive_failures' => $this->consecutiveFailures,
            'should_trigger_alert' => $this->shouldTriggerAlert(),
            'should_attempt_recovery' => $this->shouldAttemptRecovery(),
            'should_escalate' => $this->shouldEscalate(),
            'severity' => $this->getSeverity(),
            'error_details' => $this->getErrorDetails(),
            'recommended_action' => $this->getRecommendedAction(),
            'estimated_downtime_minutes' => $this->estimateDowntimeMinutes(),
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
