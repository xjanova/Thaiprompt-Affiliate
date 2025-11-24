<?php

namespace App\Listeners\AiRental;

use App\Events\AiRental\DeploymentHealthCheckFailed;
use App\Services\AiRental\AuditService;
use App\Services\AiRental\MonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Handle Health Check Failure
 *
 * ฟัง event DeploymentHealthCheckFailed และจัดการอัตโนมัติ:
 * - สร้าง alert แจ้งเตือน
 * - ลอง auto-recovery
 * - บันทึก incident log
 * - Escalate ถ้าจำเป็น
 */
class HandleHealthCheckFailure implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * จำนวนครั้งที่จะลอง retry ถ้าล้มเหลว
     *
     * @var int
     */
    public $tries = 2;

    /**
     * จำนวนวินาทีก่อน retry
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Audit Service
     *
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * Monitoring Service
     *
     * @var MonitoringService
     */
    protected MonitoringService $monitoringService;

    /**
     * สร้าง listener instance
     *
     * @param AuditService $auditService
     * @param MonitoringService $monitoringService
     * @return void
     */
    public function __construct(
        AuditService $auditService,
        MonitoringService $monitoringService
    ) {
        $this->auditService = $auditService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * จัดการ event
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    public function handle(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;
        $healthCheck = $event->healthCheck;
        $failures = $event->consecutiveFailures;

        try {
            Log::warning('[AI Rental] Handling health check failure', [
                'deployment_id' => $deployment->id,
                'health_check_id' => $healthCheck->id,
                'consecutive_failures' => $failures,
                'severity' => $event->getSeverity(),
            ]);

            // 1. บันทึก incident log
            $this->createIncidentLog($event);

            // 2. สร้าง alert (ถ้าจำเป็น)
            if ($event->shouldTriggerAlert()) {
                $this->createHealthCheckAlert($event);
            }

            // 3. ลอง auto-recovery (ถ้าเงื่อนไขเหมาะสม)
            if ($event->shouldAttemptRecovery()) {
                $this->attemptAutoRecovery($event);
            }

            // 4. Escalate (ถ้าร้ายแรง)
            if ($event->shouldEscalate()) {
                $this->escalateToAdmin($event);
            }

            // 5. Update deployment health status
            $this->updateDeploymentHealthStatus($event);

            Log::info('[AI Rental] Health check failure handled', [
                'deployment_id' => $deployment->id,
                'consecutive_failures' => $failures,
            ]);
        } catch (\Exception $e) {
            Log::error('[AI Rental] Failed to handle health check failure', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * บันทึก incident log
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    protected function createIncidentLog(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;
        $healthCheck = $event->healthCheck;
        $errorDetails = $event->getErrorDetails();

        $this->auditService->log(
            user: $deployment->user,
            action: 'deployment.health_check_failed',
            category: 'incident',
            description: "Deployment '{$deployment->deployment_name}' health check ล้มเหลวครั้งที่ {$event->consecutiveFailures}",
            relatedModel: $deployment,
            metadata: [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->deployment_name,
                'health_check_id' => $healthCheck->id,
                'consecutive_failures' => $event->consecutiveFailures,
                'error_details' => $errorDetails,
                'severity' => $event->getSeverity(),
                'recommended_action' => $event->getRecommendedAction(),
                'estimated_downtime_minutes' => $event->estimateDowntimeMinutes(),
            ],
            isSensitive: false,
            requiresReview: $event->shouldEscalate(),
            ipAddress: null,
            userAgent: 'health_check_service'
        );
    }

    /**
     * สร้าง health check alert
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    protected function createHealthCheckAlert(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;
        $severity = $event->getSeverity();
        $errorDetails = $event->getErrorDetails();

        $title = $this->getAlertTitle($event);
        $message = $this->getAlertMessage($event);

        $this->monitoringService->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'health_check_failed',
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'description' => $errorDetails['error_message'] ?? 'Unknown error',
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->deployment_name,
                'consecutive_failures' => $event->consecutiveFailures,
                'error_details' => $errorDetails,
                'recommended_action' => $event->getRecommendedAction(),
                'estimated_downtime' => $event->estimateDowntimeMinutes() . ' นาที',
            ],
            'action_required' => $this->getActionRequired($event),
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ตรวจสอบ Deployment',
            'priority' => $this->getPriority($severity),
            'error_count' => $event->consecutiveFailures,
        ]);

        Log::info('[AI Rental] Health check alert created', [
            'deployment_id' => $deployment->id,
            'severity' => $severity,
        ]);
    }

    /**
     * ลอง auto-recovery
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    protected function attemptAutoRecovery(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;
        $action = $event->getRecommendedAction();

        Log::info('[AI Rental] Attempting auto-recovery', [
            'deployment_id' => $deployment->id,
            'action' => $action,
            'consecutive_failures' => $event->consecutiveFailures,
        ]);

        // TODO: Implement auto-recovery actions
        // - auto_restart: Restart deployment
        // - scale_up: Increase resources
        // - failover: Switch to backup instance

        switch ($action) {
            case 'auto_restart':
                $this->scheduleRestart($deployment);
                break;

            case 'monitor':
                // Just monitor, no action
                Log::info('[AI Rental] Monitoring deployment, no recovery needed yet');
                break;

            case 'manual_intervention_required':
                // Already handled by escalation
                break;
        }
    }

    /**
     * Schedule deployment restart
     *
     * @param \App\Models\AiRentalDeployment $deployment
     * @return void
     */
    protected function scheduleRestart($deployment): void
    {
        Log::info('[AI Rental] Scheduling deployment restart', [
            'deployment_id' => $deployment->id,
        ]);

        // TODO: Implement restart logic via cloud provider API
        // dispatch(new RestartDeployment($deployment))->delay(now()->addMinutes(1));

        // สร้าง alert แจ้ง user
        $this->monitoringService->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'auto_recovery',
            'severity' => 'warning',
            'title' => '🔄 Auto-Recovery กำลังทำงาน',
            'message' => "ระบบกำลังพยายาม restart deployment '{$deployment->deployment_name}' อัตโนมัติ",
            'description' => 'เนื่องจาก health check ล้มเหลวติดต่อกัน',
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ดูสถานะ',
            'priority' => 3,
        ]);
    }

    /**
     * Escalate to admin
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    protected function escalateToAdmin(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;

        Log::critical('[AI Rental] Escalating health check failure to admin', [
            'deployment_id' => $deployment->id,
            'consecutive_failures' => $event->consecutiveFailures,
        ]);

        // สร้าง critical alert สำหรับ admin
        $this->monitoringService->createAlert([
            'user_id' => 1, // Admin user ID
            'deployment_id' => $deployment->id,
            'alert_type' => 'critical_failure',
            'severity' => 'critical',
            'title' => '🚨 CRITICAL: Deployment Health Check ล้มเหลวหลายครั้ง',
            'message' => "Deployment '{$deployment->deployment_name}' (ID: {$deployment->id}) health check ล้มเหลว {$event->consecutiveFailures} ครั้งติดต่อกัน",
            'description' => 'ต้องการการแทรกแซงจาก admin ทันที',
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'user_id' => $deployment->user_id,
                'consecutive_failures' => $event->consecutiveFailures,
                'downtime_estimate' => $event->estimateDowntimeMinutes() . ' นาที',
                'error_details' => $event->getErrorDetails(),
            ],
            'action_required' => 'ตรวจสอบและแก้ไขทันที',
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'แก้ไขเดี๋ยวนี้',
            'priority' => 1, // Highest priority
            'notification_channels' => ['email', 'push', 'webhook'],
        ]);

        // แจ้ง user ด้วย
        $this->monitoringService->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'critical_failure',
            'severity' => 'critical',
            'title' => '🚨 Deployment ใช้งานไม่ได้',
            'message' => "Deployment '{$deployment->deployment_name}' ไม่สามารถเข้าถึงได้มากกว่า {$event->estimateDowntimeMinutes()} นาที",
            'description' => 'ทีมงานกำลังดำเนินการแก้ไข',
            'action_required' => 'รอการแก้ไขจากทีมงาน',
            'priority' => 1,
        ]);
    }

    /**
     * Update deployment health status
     *
     * @param DeploymentHealthCheckFailed $event
     * @return void
     */
    protected function updateDeploymentHealthStatus(DeploymentHealthCheckFailed $event): void
    {
        $deployment = $event->deployment;

        $deployment->health_status = $event->getSeverity();
        $deployment->last_health_check_at = now();
        $deployment->consecutive_health_failures = $event->consecutiveFailures;
        $deployment->save();

        Log::info('[AI Rental] Deployment health status updated', [
            'deployment_id' => $deployment->id,
            'health_status' => $deployment->health_status,
        ]);
    }

    /**
     * ดึง alert title
     *
     * @param DeploymentHealthCheckFailed $event
     * @return string
     */
    protected function getAlertTitle(DeploymentHealthCheckFailed $event): string
    {
        $severity = $event->getSeverity();

        return match ($severity) {
            'critical' => '🚨 CRITICAL: Deployment ไม่ตอบสนอง',
            'error' => '❌ Deployment Health Check ล้มเหลว',
            'warning' => '⚠️ Deployment อาจมีปัญหา',
            default => '⚕️ Health Check ล้มเหลว',
        };
    }

    /**
     * ดึง alert message
     *
     * @param DeploymentHealthCheckFailed $event
     * @return string
     */
    protected function getAlertMessage(DeploymentHealthCheckFailed $event): string
    {
        $deployment = $event->deployment;
        $failures = $event->consecutiveFailures;

        return "Deployment '{$deployment->deployment_name}' health check ล้มเหลว {$failures} ครั้งติดต่อกัน";
    }

    /**
     * ดึง action required
     *
     * @param DeploymentHealthCheckFailed $event
     * @return string
     */
    protected function getActionRequired(DeploymentHealthCheckFailed $event): string
    {
        $action = $event->getRecommendedAction();

        return match ($action) {
            'auto_restart' => 'ระบบกำลังพยายาม restart อัตโนมัติ',
            'monitor' => 'กำลังตรวจสอบ',
            'manual_intervention_required' => 'ต้องแก้ไขด้วยมือ',
            default => 'ตรวจสอบสถานะ',
        };
    }

    /**
     * ดึง priority จาก severity
     *
     * @param string $severity
     * @return int
     */
    protected function getPriority(string $severity): int
    {
        return match ($severity) {
            'critical' => 1,
            'error' => 2,
            'warning' => 4,
            default => 5,
        };
    }

    /**
     * จัดการเมื่อ job ล้มเหลว
     *
     * @param DeploymentHealthCheckFailed $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(DeploymentHealthCheckFailed $event, \Throwable $exception): void
    {
        Log::error('[AI Rental] Failed to handle health check failure after retries', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);

        // สร้าง critical alert สำหรับ admin
        $this->monitoringService->createAlert([
            'user_id' => 1, // Admin
            'deployment_id' => $event->deployment->id,
            'alert_type' => 'system_error',
            'severity' => 'critical',
            'title' => '⚠️ Listener ล้มเหลว',
            'message' => 'HandleHealthCheckFailure listener failed',
            'description' => $exception->getMessage(),
            'priority' => 1,
        ]);
    }
}
