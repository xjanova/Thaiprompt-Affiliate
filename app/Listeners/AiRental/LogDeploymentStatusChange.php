<?php

namespace App\Listeners\AiRental;

use App\Events\AiRental\DeploymentStatusChanged;
use App\Services\AiRental\AuditService;
use App\Services\AiRental\MonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Log Deployment Status Change
 *
 * ฟัง event DeploymentStatusChanged และบันทึก audit log
 * รวมถึงส่ง notification และ update analytics
 */
class LogDeploymentStatusChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * จำนวนครั้งที่จะลอง retry ถ้าล้มเหลว
     *
     * @var int
     */
    public $tries = 3;

    /**
     * จำนวนวินาทีก่อน retry
     *
     * @var int
     */
    public $backoff = 3;

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
     * @param DeploymentStatusChanged $event
     * @return void
     */
    public function handle(DeploymentStatusChanged $event): void
    {
        $deployment = $event->deployment;

        try {
            Log::info('[AI Rental] Logging deployment status change', [
                'deployment_id' => $deployment->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);

            // 1. บันทึก audit log
            $this->createAuditLog($event);

            // 2. ส่ง notification ถ้าจำเป็น
            if ($event->shouldNotifyUser()) {
                $this->sendStatusChangeNotification($event);
            }

            // 3. Update metrics
            $this->updateMetrics($event);

            // 4. จัดการเฉพาะ status
            $this->handleSpecificStatus($event);

            Log::info('[AI Rental] Deployment status change logged successfully', [
                'deployment_id' => $deployment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AI Rental] Failed to log deployment status change', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * สร้าง audit log
     *
     * @param DeploymentStatusChanged $event
     * @return void
     */
    protected function createAuditLog(DeploymentStatusChanged $event): void
    {
        $this->auditService->log(
            user: $event->deployment->user,
            action: 'deployment.status_changed',
            category: 'deployment_lifecycle',
            description: "Deployment '{$event->deployment->deployment_name}' status เปลี่ยนจาก {$event->oldStatus} → {$event->newStatus}",
            relatedModel: $event->deployment,
            oldValues: ['status' => $event->oldStatus],
            newValues: ['status' => $event->newStatus],
            metadata: [
                'deployment_id' => $event->deployment->id,
                'deployment_name' => $event->deployment->deployment_name,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'reason' => $event->reason,
                'severity' => $event->getSeverity(),
                'should_stop_billing' => $event->shouldStopBilling(),
                'changed_by' => $event->metadata['changed_by'] ?? 'system',
            ],
            isSensitive: false,
            requiresReview: in_array($event->newStatus, ['failed', 'terminated']),
            ipAddress: request()->ip() ?? null,
            userAgent: request()->userAgent() ?? null
        );
    }

    /**
     * ส่ง notification
     *
     * @param DeploymentStatusChanged $event
     * @return void
     */
    protected function sendStatusChangeNotification(DeploymentStatusChanged $event): void
    {
        $deployment = $event->deployment;
        $severity = $event->getSeverity();

        // สร้าง alert message ตาม status
        $title = $this->getAlertTitle($event->newStatus);
        $message = $this->getAlertMessage($event);

        $this->monitoringService->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'deployment_status_changed',
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'description' => $event->reason,
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->deployment_name,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'provider' => $deployment->provider,
                'instance_type' => $deployment->instance_type,
            ],
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ดู Deployment',
            'priority' => $this->getPriority($severity),
            'expires_at' => now()->addHours(48),
        ]);
    }

    /**
     * Update metrics
     *
     * @param DeploymentStatusChanged $event
     * @return void
     */
    protected function updateMetrics(DeploymentStatusChanged $event): void
    {
        $deployment = $event->deployment;

        // บันทึก timestamp สำคัญ
        if ($event->newStatus === 'running') {
            $deployment->started_at = now();
        } elseif (in_array($event->newStatus, ['terminated', 'stopped', 'failed'])) {
            $deployment->terminated_at = now();

            // คำนวณ total runtime
            if ($deployment->started_at) {
                $runtimeMinutes = $deployment->started_at->diffInMinutes(now());
                $deployment->total_runtime_minutes = $runtimeMinutes;
            }
        }

        $deployment->save();

        Log::info('[AI Rental] Deployment metrics updated', [
            'deployment_id' => $deployment->id,
            'status' => $event->newStatus,
        ]);
    }

    /**
     * จัดการเฉพาะ status
     *
     * @param DeploymentStatusChanged $event
     * @return void
     */
    protected function handleSpecificStatus(DeploymentStatusChanged $event): void
    {
        $deployment = $event->deployment;

        // หยุด billing ถ้าจำเป็น
        if ($event->shouldStopBilling()) {
            Log::info('[AI Rental] Stopping billing for deployment', [
                'deployment_id' => $deployment->id,
                'status' => $event->newStatus,
            ]);

            // TODO: Implement billing stop logic
            // $this->billingService->stopBilling($deployment);
        }

        // เริ่ม health check schedule ถ้า deployment running
        if ($event->isDeploymentReady()) {
            Log::info('[AI Rental] Deployment is ready, starting health checks', [
                'deployment_id' => $deployment->id,
            ]);

            // TODO: Schedule first health check
            // dispatch(new ScheduleHealthCheck($deployment))->delay(now()->addMinutes(2));
        }

        // ลบ resources ถ้า terminated
        if ($event->isTerminated()) {
            Log::info('[AI Rental] Deployment terminated, cleaning up resources', [
                'deployment_id' => $deployment->id,
            ]);

            // TODO: Cleanup cloud resources
            // dispatch(new CleanupDeploymentResources($deployment))->delay(now()->addMinutes(5));
        }
    }

    /**
     * ดึง alert title
     *
     * @param string $status
     * @return string
     */
    protected function getAlertTitle(string $status): string
    {
        return match ($status) {
            'running' => '✅ Deployment พร้อมใช้งาน',
            'stopped' => '⏸️ Deployment หยุดชั่วคราว',
            'failed' => '❌ Deployment ล้มเหลว',
            'terminated' => '🛑 Deployment ถูกยกเลิก',
            default => '🔄 Deployment Status เปลี่ยนแปลง',
        };
    }

    /**
     * ดึง alert message
     *
     * @param DeploymentStatusChanged $event
     * @return string
     */
    protected function getAlertMessage(DeploymentStatusChanged $event): string
    {
        $deployment = $event->deployment;

        return match ($event->newStatus) {
            'running' => "Deployment '{$deployment->deployment_name}' พร้อมให้ใช้งานแล้ว! 🎉",
            'stopped' => "Deployment '{$deployment->deployment_name}' ถูกหยุดชั่วคราว",
            'failed' => "Deployment '{$deployment->deployment_name}' เกิดข้อผิดพลาด",
            'terminated' => "Deployment '{$deployment->deployment_name}' ถูกยกเลิกแล้ว",
            default => "Deployment '{$deployment->deployment_name}' status เปลี่ยนเป็น {$event->newStatus}",
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
            'info' => 5,
            'warning' => 3,
            'error' => 2,
            default => 5,
        };
    }

    /**
     * จัดการเมื่อ job ล้มเหลว
     *
     * @param DeploymentStatusChanged $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(DeploymentStatusChanged $event, \Throwable $exception): void
    {
        Log::error('[AI Rental] Failed to log deployment status change after retries', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
