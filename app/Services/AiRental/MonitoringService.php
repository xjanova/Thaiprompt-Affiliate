<?php

namespace App\Services\AiRental;

use App\Models\AiRentalAlert;
use App\Models\AiRentalDeployment;
use App\Models\AiRentalBudgetLimit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Exception;

/**
 * Monitoring Service สำหรับ AI Rental System
 *
 * จัดการ alerts, notifications และ monitoring ทั้งระบบ
 */
class MonitoringService
{
    /**
     * สร้าง alert ใหม่
     *
     * @param array $data
     * @return AiRentalAlert
     */
    public function createAlert(array $data): AiRentalAlert
    {
        try {
            $alert = AiRentalAlert::create([
                'user_id' => $data['user_id'],
                'deployment_id' => $data['deployment_id'] ?? null,
                'alert_type' => $data['alert_type'],
                'severity' => $data['severity'] ?? 'info',
                'title' => $data['title'],
                'message' => $data['message'],
                'description' => $data['description'] ?? null,
                'trigger_conditions' => $data['trigger_conditions'] ?? null,
                'alert_data' => $data['alert_data'] ?? null,
                'action_required' => $data['action_required'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
                'priority' => $data['priority'] ?? 5,
                'notification_channels' => $data['notification_channels'] ?? ['email'],
                'cost_at_alert' => $data['cost_at_alert'] ?? null,
                'budget_limit' => $data['budget_limit'] ?? null,
                'error_count' => $data['error_count'] ?? null,
                'response_time_ms' => $data['response_time_ms'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // ส่ง notification อัตโนมัติ (ถ้าไม่ระบุ auto_send เป็น false)
            if (!isset($data['auto_send']) || $data['auto_send'] === true) {
                $this->sendAlert($alert);
            }

            Log::info("Alert created: {$alert->alert_type} for user {$alert->user_id}", [
                'alert_id' => $alert->id,
                'severity' => $alert->severity,
            ]);

            return $alert;
        } catch (Exception $e) {
            Log::error('Failed to create alert: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * ส่ง alert notification
     *
     * @param AiRentalAlert $alert
     * @return bool
     */
    public function sendAlert(AiRentalAlert $alert): bool
    {
        try {
            $channels = $alert->notification_channels ?? ['email'];

            // ส่งผ่าน Email
            if (in_array('email', $channels) && !$alert->email_sent) {
                $this->sendEmailAlert($alert);
                $alert->email_sent = true;
            }

            // ส่งผ่าน Push Notification
            if (in_array('push', $channels) && !$alert->push_sent) {
                $this->sendPushAlert($alert);
                $alert->push_sent = true;
            }

            // ส่งผ่าน Webhook
            if (in_array('webhook', $channels) && !$alert->webhook_sent) {
                $this->sendWebhookAlert($alert);
                $alert->webhook_sent = true;
            }

            // อัพเดทสถานะ
            $alert->sent_at = now();
            $alert->status = 'sent';
            $alert->save();

            Log::info("Alert sent: {$alert->id} via " . implode(', ', $channels));

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send alert: ' . $e->getMessage(), [
                'alert_id' => $alert->id,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * ส่ง email alert
     *
     * @param AiRentalAlert $alert
     * @return void
     */
    protected function sendEmailAlert(AiRentalAlert $alert): void
    {
        try {
            $user = $alert->user;

            // TODO: สร้าง Mailable class สำหรับ alert emails
            // Mail::to($user->email)->send(new AlertNotification($alert));

            Log::info("Email alert sent to {$user->email}", [
                'alert_id' => $alert->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send email alert: ' . $e->getMessage());
        }
    }

    /**
     * ส่ง push notification alert
     *
     * @param AiRentalAlert $alert
     * @return void
     */
    protected function sendPushAlert(AiRentalAlert $alert): void
    {
        try {
            $user = $alert->user;

            // TODO: Integrate with push notification service
            // - Firebase Cloud Messaging
            // - OneSignal
            // - Pusher Beams

            Log::info("Push alert sent to user {$user->id}", [
                'alert_id' => $alert->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send push alert: ' . $e->getMessage());
        }
    }

    /**
     * ส่ง webhook alert
     *
     * @param AiRentalAlert $alert
     * @return void
     */
    protected function sendWebhookAlert(AiRentalAlert $alert): void
    {
        try {
            // TODO: Get webhook URLs from user settings
            // TODO: Send POST request to webhook URLs

            Log::info("Webhook alert sent", [
                'alert_id' => $alert->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send webhook alert: ' . $e->getMessage());
        }
    }

    /**
     * สร้าง cost warning alert
     *
     * @param User $user
     * @param AiRentalBudgetLimit $budget
     * @param float $currentSpending
     * @param int $threshold
     * @return AiRentalAlert
     */
    public function createCostWarningAlert(
        User $user,
        AiRentalBudgetLimit $budget,
        float $currentSpending,
        int $threshold
    ): AiRentalAlert {
        $percentage = ($currentSpending / $budget->limit_amount) * 100;

        return $this->createAlert([
            'user_id' => $user->id,
            'alert_type' => 'cost_warning',
            'severity' => $this->getCostWarningSeverity($threshold),
            'title' => "⚠️ งบประมาณใกล้เกิน {$threshold}%",
            'message' => "งบประมาณ '{$budget->budget_name}' ใช้ไปแล้ว {$percentage}% (\${$currentSpending} จาก \${$budget->limit_amount})",
            'description' => "คุณได้ใช้งบประมาณไปแล้ว {$percentage}% ซึ่งเกินเกณฑ์เตือนที่ {$threshold}%",
            'trigger_conditions' => [
                'threshold' => $threshold,
                'percentage' => $percentage,
            ],
            'alert_data' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'limit_amount' => $budget->limit_amount,
                'current_spending' => $currentSpending,
                'percentage' => round($percentage, 2),
                'remaining' => $budget->limit_amount - $currentSpending,
            ],
            'action_required' => 'ตรวจสอบการใช้งานหรือปรับ budget',
            'action_url' => route('admin.ai-rental.deployments.index'),
            'action_label' => 'ดู Deployments',
            'priority' => $this->getCostWarningPriority($threshold),
            'cost_at_alert' => $currentSpending,
            'budget_limit' => $budget->limit_amount,
        ]);
    }

    /**
     * สร้าง deployment failed alert
     *
     * @param AiRentalDeployment $deployment
     * @param string $errorMessage
     * @return AiRentalAlert
     */
    public function createDeploymentFailedAlert(
        AiRentalDeployment $deployment,
        string $errorMessage
    ): AiRentalAlert {
        return $this->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'deployment_failed',
            'severity' => 'error',
            'title' => "❌ Deployment ล้มเหลว",
            'message' => "Deployment '{$deployment->deployment_name}' เกิดข้อผิดพลาด",
            'description' => $errorMessage,
            'alert_data' => [
                'deployment_id' => $deployment->id,
                'deployment_name' => $deployment->deployment_name,
                'error_message' => $errorMessage,
                'cost_per_hour' => $deployment->cost_per_hour,
            ],
            'action_required' => 'ตรวจสอบและแก้ไข deployment',
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ดู Deployment',
            'priority' => 3,
            'error_count' => 1,
        ]);
    }

    /**
     * สร้าง health check failed alert
     *
     * @param AiRentalDeployment $deployment
     * @param array $healthData
     * @return AiRentalAlert
     */
    public function createHealthCheckFailedAlert(
        AiRentalDeployment $deployment,
        array $healthData
    ): AiRentalAlert {
        return $this->createAlert([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'alert_type' => 'health_check_failed',
            'severity' => 'warning',
            'title' => "⚕️ Health Check ล้มเหลว",
            'message' => "Deployment '{$deployment->deployment_name}' health check ล้มเหลว",
            'description' => $healthData['error_message'] ?? 'Unknown error',
            'alert_data' => $healthData,
            'action_required' => 'ตรวจสอบสถานะ deployment',
            'action_url' => route('admin.ai-rental.deployments.show', $deployment->id),
            'action_label' => 'ดู Deployment',
            'priority' => 4,
            'response_time_ms' => $healthData['response_time_ms'] ?? null,
        ]);
    }

    /**
     * ดึง alerts สำหรับ user (พร้อมกรอง)
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserAlerts(int $userId, array $filters = [], int $perPage = 15)
    {
        $query = AiRentalAlert::forUser($userId)
            ->with(['deployment'])
            ->orderBy('created_at', 'desc');

        // กรองตาม severity
        if (isset($filters['severity'])) {
            $query->severity($filters['severity']);
        }

        // กรองตาม status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // กรองตาม alert_type
        if (isset($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        // เฉพาะที่ยังไม่อ่าน
        if (isset($filters['unread']) && $filters['unread']) {
            $query->unread();
        }

        // เฉพาะที่ active
        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * ทำเครื่องหมายหลาย alerts เป็นอ่านแล้ว
     *
     * @param array $alertIds
     * @param int $userId
     * @return int จำนวน alerts ที่อัพเดท
     */
    public function markAlertsAsRead(array $alertIds, int $userId): int
    {
        return AiRentalAlert::whereIn('id', $alertIds)
            ->forUser($userId)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
    }

    /**
     * Resolve alert
     *
     * @param int $alertId
     * @param int $userId
     * @param string|null $note
     * @return bool
     */
    public function resolveAlert(int $alertId, int $userId, ?string $note = null): bool
    {
        $alert = AiRentalAlert::forUser($userId)->find($alertId);

        if (!$alert) {
            return false;
        }

        return $alert->markAsResolved($note);
    }

    /**
     * Auto-resolve expired alerts
     *
     * @return int จำนวน alerts ที่ auto-resolve
     */
    public function autoResolveExpiredAlerts(): int
    {
        $count = AiRentalAlert::where('expires_at', '<', now())
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'status' => 'resolved',
                'auto_resolved' => true,
                'resolution_note' => 'Auto-resolved (expired)',
            ]);

        Log::info("Auto-resolved {$count} expired alerts");

        return $count;
    }

    /**
     * ดึง alert summary สำหรับ user
     *
     * @param int $userId
     * @return array
     */
    public function getAlertSummary(int $userId): array
    {
        $alerts = AiRentalAlert::forUser($userId);

        return [
            'total' => $alerts->count(),
            'unread' => $alerts->unread()->count(),
            'critical' => $alerts->severity('critical')->count(),
            'error' => $alerts->severity('error')->count(),
            'warning' => $alerts->severity('warning')->count(),
            'unresolved' => $alerts->unresolved()->count(),
            'by_type' => $alerts->select('alert_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('alert_type')
                ->pluck('count', 'alert_type')
                ->toArray(),
        ];
    }

    /**
     * คำนวณ severity สำหรับ cost warning
     *
     * @param int $threshold
     * @return string
     */
    protected function getCostWarningSeverity(int $threshold): string
    {
        if ($threshold >= 90) {
            return 'critical';
        } elseif ($threshold >= 75) {
            return 'error';
        } elseif ($threshold >= 50) {
            return 'warning';
        }
        return 'info';
    }

    /**
     * คำนวณ priority สำหรับ cost warning
     *
     * @param int $threshold
     * @return int
     */
    protected function getCostWarningPriority(int $threshold): int
    {
        if ($threshold >= 90) {
            return 1; // Highest
        } elseif ($threshold >= 75) {
            return 2;
        } elseif ($threshold >= 50) {
            return 3;
        }
        return 5; // Normal
    }
}
