<?php

namespace App\Listeners\AiRental;

use App\Events\AiRental\DeploymentCreated;
use App\Services\AiRental\MonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Listener: Notify User Of Deployment Created
 *
 * ฟัง event DeploymentCreated และส่งการแจ้งเตือนให้ผู้ใช้
 * รวมถึงสร้าง welcome alert และบันทึก log
 */
class NotifyUserOfDeploymentCreated implements ShouldQueue
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
    public $backoff = 5;

    /**
     * Monitoring Service
     *
     * @var MonitoringService
     */
    protected MonitoringService $monitoringService;

    /**
     * สร้าง listener instance
     *
     * @param MonitoringService $monitoringService
     * @return void
     */
    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * จัดการ event
     *
     * @param DeploymentCreated $event
     * @return void
     */
    public function handle(DeploymentCreated $event): void
    {
        $deployment = $event->getDeployment();
        $user = $event->getUser();

        try {
            Log::info('[AI Rental] Processing deployment created notification', [
                'deployment_id' => $deployment->id,
                'user_id' => $user->id,
            ]);

            // 1. สร้าง welcome alert
            $this->createWelcomeAlert($deployment);

            // 2. ส่ง email notification (ถ้า user เปิดไว้)
            if ($this->shouldSendEmail($user)) {
                $this->sendEmailNotification($user, $deployment);
            }

            // 3. ส่ง push notification (ถ้า user เปิดไว้)
            if ($this->shouldSendPush($user)) {
                $this->sendPushNotification($user, $deployment);
            }

            // 4. บันทึก analytics
            $this->trackDeploymentCreation($deployment, $event->getMetadata());

            Log::info('[AI Rental] Deployment created notification sent successfully', [
                'deployment_id' => $deployment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[AI Rental] Failed to send deployment created notification', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw เพื่อให้ queue ลอง retry
            throw $e;
        }
    }

    /**
     * สร้าง welcome alert
     *
     * @param \App\Models\AiRentalDeployment $deployment
     * @return void
     */
    protected function createWelcomeAlert($deployment): void
    {
        $this->monitoringService->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'deployment_created',
            'severity' => 'info',
            'title' => '🎉 Deployment สร้างสำเร็จ!',
            'message' => "Deployment '{$deployment->deployment_name}' กำลังเริ่มต้น",
            'description' => "ระบบกำลัง provision GPU instance สำหรับคุณ โปรดรอสักครู่...",
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->deployment_name,
                'provider' => $deployment->provider,
                'instance_type' => $deployment->instance_type,
                'cost_per_hour' => $deployment->cost_per_hour,
                'estimated_ready_time' => now()->addMinutes(5)->format('H:i'),
            ],
            'action_required' => null,
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ดู Deployment',
            'priority' => 5,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * ส่ง email notification
     *
     * @param \App\Models\User $user
     * @param \App\Models\AiRentalDeployment $deployment
     * @return void
     */
    protected function sendEmailNotification($user, $deployment): void
    {
        // TODO: Implement email notification
        // Notification::send($user, new DeploymentCreatedNotification($deployment));

        Log::info('[AI Rental] Email notification sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'deployment_id' => $deployment->id,
        ]);
    }

    /**
     * ส่ง push notification
     *
     * @param \App\Models\User $user
     * @param \App\Models\AiRentalDeployment $deployment
     * @return void
     */
    protected function sendPushNotification($user, $deployment): void
    {
        // TODO: Implement push notification
        // - Firebase Cloud Messaging
        // - OneSignal
        // - Pusher Beams

        Log::info('[AI Rental] Push notification sent', [
            'user_id' => $user->id,
            'deployment_id' => $deployment->id,
        ]);
    }

    /**
     * บันทึก analytics
     *
     * @param \App\Models\AiRentalDeployment $deployment
     * @param array $metadata
     * @return void
     */
    protected function trackDeploymentCreation($deployment, array $metadata): void
    {
        // TODO: Send to analytics service
        // - Google Analytics
        // - Mixpanel
        // - Custom analytics

        Log::info('[AI Rental] Deployment creation tracked', [
            'deployment_id' => $deployment->id,
            'provider' => $deployment->provider,
            'instance_type' => $deployment->instance_type,
            'metadata' => $metadata,
        ]);
    }

    /**
     * ตรวจสอบว่าควรส่ง email หรือไม่
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function shouldSendEmail($user): bool
    {
        // ตรวจสอบ user preferences
        // TODO: Implement user notification preferences
        return true;
    }

    /**
     * ตรวจสอบว่าควรส่ง push notification หรือไม่
     *
     * @param \App\Models\User $user
     * @return bool
     */
    protected function shouldSendPush($user): bool
    {
        // ตรวจสอบ user preferences
        // TODO: Implement user notification preferences
        return false; // Disable push by default
    }

    /**
     * จัดการเมื่อ job ล้มเหลว
     *
     * @param DeploymentCreated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(DeploymentCreated $event, \Throwable $exception): void
    {
        $deployment = $event->getDeployment();

        Log::error('[AI Rental] Failed to process deployment created notification after retries', [
            'deployment_id' => $deployment->id,
            'error' => $exception->getMessage(),
            'tries' => $this->tries,
        ]);

        // สร้าง alert แจ้งเตือน admin
        $this->monitoringService->createAlert([
            'user_id' => 1, // Admin user
            'deployment_id' => $deployment->id,
            'alert_type' => 'system_error',
            'severity' => 'error',
            'title' => '⚠️ Listener ล้มเหลว',
            'message' => 'NotifyUserOfDeploymentCreated listener failed',
            'description' => $exception->getMessage(),
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'exception' => $exception->getMessage(),
            ],
            'priority' => 2,
        ]);
    }
}
