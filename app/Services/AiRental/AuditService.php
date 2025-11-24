<?php

namespace App\Services\AiRental;

use App\Models\AiRentalAuditLog;
use App\Models\AiRentalDeployment;
use App\Models\AiRentalCloudConfig;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Exception;

/**
 * Audit Service สำหรับ AI Rental System
 *
 * จัดการ audit logs และ activity tracking ทั้งระบบ
 */
class AuditService
{
    /**
     * บันทึก audit log แบบ generic
     *
     * @param array $data
     * @return AiRentalAuditLog
     */
    public function log(array $data): AiRentalAuditLog
    {
        try {
            return AiRentalAuditLog::log($data);
        } catch (Exception $e) {
            Log::error('Failed to create audit log: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * บันทึก deployment action
     *
     * @param string $action
     * @param AiRentalDeployment $deployment
     * @param array $data
     * @return AiRentalAuditLog
     */
    public function logDeploymentAction(string $action, AiRentalDeployment $deployment, array $data = []): AiRentalAuditLog
    {
        return AiRentalAuditLog::logDeploymentAction($action, $deployment, array_merge($data, [
            'action_type' => $this->getActionType($action),
            'severity' => $this->getSeverity($action),
        ]));
    }

    /**
     * บันทึก deployment creation
     *
     * @param AiRentalDeployment $deployment
     * @param array $requestData
     * @return AiRentalAuditLog
     */
    public function logDeploymentCreated(AiRentalDeployment $deployment, array $requestData = []): AiRentalAuditLog
    {
        return $this->logDeploymentAction('deployment.created', $deployment, [
            'description' => "สร้าง deployment '{$deployment->deployment_name}' สำเร็จ",
            'request_data' => $requestData,
            'changes_after' => $deployment->toArray(),
            'cost_after' => 0,
            'status' => 'success',
        ]);
    }

    /**
     * บันทึก deployment started
     *
     * @param AiRentalDeployment $deployment
     * @return AiRentalAuditLog
     */
    public function logDeploymentStarted(AiRentalDeployment $deployment): AiRentalAuditLog
    {
        return $this->logDeploymentAction('deployment.started', $deployment, [
            'description' => "เริ่ม deployment '{$deployment->deployment_name}'",
            'cost_before' => $deployment->total_cost,
            'severity' => 'medium',
        ]);
    }

    /**
     * บันทึก deployment stopped
     *
     * @param AiRentalDeployment $deployment
     * @param string $reason
     * @return AiRentalAuditLog
     */
    public function logDeploymentStopped(AiRentalDeployment $deployment, string $reason = 'manual'): AiRentalAuditLog
    {
        return $this->logDeploymentAction('deployment.stopped', $deployment, [
            'description' => "หยุด deployment '{$deployment->deployment_name}' (เหตุผล: {$reason})",
            'metadata' => [
                'reason' => $reason,
                'total_hours' => $deployment->total_hours,
                'total_cost' => $deployment->total_cost,
            ],
            'cost_after' => $deployment->total_cost,
            'cost_impact' => 0,
        ]);
    }

    /**
     * บันทึก deployment deleted
     *
     * @param AiRentalDeployment $deployment
     * @return AiRentalAuditLog
     */
    public function logDeploymentDeleted(AiRentalDeployment $deployment): AiRentalAuditLog
    {
        return $this->logDeploymentAction('deployment.deleted', $deployment, [
            'description' => "ลบ deployment '{$deployment->deployment_name}'",
            'changes_before' => $deployment->toArray(),
            'severity' => 'high',
            'is_sensitive' => true,
        ]);
    }

    /**
     * บันทึก deployment failed
     *
     * @param AiRentalDeployment $deployment
     * @param string $errorMessage
     * @return AiRentalAuditLog
     */
    public function logDeploymentFailed(AiRentalDeployment $deployment, string $errorMessage): AiRentalAuditLog
    {
        return $this->logDeploymentAction('deployment.failed', $deployment, [
            'description' => "Deployment '{$deployment->deployment_name}' ล้มเหลว",
            'error_message' => $errorMessage,
            'status' => 'failed',
            'severity' => 'high',
            'requires_review' => true,
        ]);
    }

    /**
     * บันทึก config action
     *
     * @param string $action
     * @param AiRentalCloudConfig $config
     * @param array $data
     * @return AiRentalAuditLog
     */
    public function logConfigAction(string $action, AiRentalCloudConfig $config, array $data = []): AiRentalAuditLog
    {
        return AiRentalAuditLog::logConfigAction($action, $config, array_merge($data, [
            'action_type' => $this->getActionType($action),
            'severity' => $this->getSeverity($action),
        ]));
    }

    /**
     * บันทึก config created
     *
     * @param AiRentalCloudConfig $config
     * @param array $requestData
     * @return AiRentalAuditLog
     */
    public function logConfigCreated(AiRentalCloudConfig $config, array $requestData = []): AiRentalAuditLog
    {
        // ซ่อน sensitive data (API keys)
        $safeRequestData = $requestData;
        unset($safeRequestData['api_key'], $safeRequestData['api_secret']);

        return $this->logConfigAction('config.created', $config, [
            'description' => "สร้าง configuration '{$config->config_name}' สำเร็จ",
            'request_data' => $safeRequestData,
            'changes_after' => $this->sanitizeConfigData($config->toArray()),
            'is_sensitive' => true,
            'severity' => 'medium',
        ]);
    }

    /**
     * บันทึก config updated
     *
     * @param AiRentalCloudConfig $config
     * @param array $changesBefore
     * @param array $changesAfter
     * @return AiRentalAuditLog
     */
    public function logConfigUpdated(AiRentalCloudConfig $config, array $changesBefore, array $changesAfter): AiRentalAuditLog
    {
        return $this->logConfigAction('config.updated', $config, [
            'description' => "อัพเดท configuration '{$config->config_name}'",
            'changes_before' => $this->sanitizeConfigData($changesBefore),
            'changes_after' => $this->sanitizeConfigData($changesAfter),
            'is_sensitive' => true,
            'severity' => 'medium',
        ]);
    }

    /**
     * บันทึก config deleted
     *
     * @param AiRentalCloudConfig $config
     * @return AiRentalAuditLog
     */
    public function logConfigDeleted(AiRentalCloudConfig $config): AiRentalAuditLog
    {
        return $this->logConfigAction('config.deleted', $config, [
            'description' => "ลบ configuration '{$config->config_name}'",
            'changes_before' => $this->sanitizeConfigData($config->toArray()),
            'is_sensitive' => true,
            'severity' => 'high',
            'requires_review' => true,
        ]);
    }

    /**
     * บันทึก budget action
     *
     * @param string $action
     * @param int $userId
     * @param array $data
     * @return AiRentalAuditLog
     */
    public function logBudgetAction(string $action, int $userId, array $data = []): AiRentalAuditLog
    {
        return $this->log(array_merge([
            'user_id' => $userId,
            'action' => $action,
            'action_category' => 'budget',
            'action_type' => $this->getActionType($action),
            'resource_type' => 'budget',
            'severity' => $this->getSeverity($action),
        ], $data));
    }

    /**
     * บันทึก budget exceeded
     *
     * @param int $userId
     * @param int $budgetId
     * @param string $budgetName
     * @param float $limitAmount
     * @param float $currentSpending
     * @return AiRentalAuditLog
     */
    public function logBudgetExceeded(int $userId, int $budgetId, string $budgetName, float $limitAmount, float $currentSpending): AiRentalAuditLog
    {
        $exceededBy = $currentSpending - $limitAmount;

        return $this->logBudgetAction('budget.exceeded', $userId, [
            'resource_id' => (string) $budgetId,
            'resource_name' => $budgetName,
            'description' => "งบประมาณ '{$budgetName}' เกิน limit \${$exceededBy}",
            'cost_before' => $limitAmount,
            'cost_after' => $currentSpending,
            'cost_impact' => $exceededBy,
            'metadata' => [
                'budget_id' => $budgetId,
                'limit_amount' => $limitAmount,
                'current_spending' => $currentSpending,
                'exceeded_by' => $exceededBy,
            ],
            'severity' => 'critical',
            'requires_review' => true,
        ]);
    }

    /**
     * บันทึก API call
     *
     * @param string $endpoint
     * @param string $method
     * @param array $requestData
     * @param array $responseData
     * @param int $statusCode
     * @param int $durationMs
     * @return AiRentalAuditLog
     */
    public function logApiCall(
        string $endpoint,
        string $method,
        array $requestData,
        array $responseData,
        int $statusCode,
        int $durationMs
    ): AiRentalAuditLog {
        $isSuccess = $statusCode >= 200 && $statusCode < 300;

        return $this->log([
            'user_id' => auth()->id(),
            'action' => 'api.called',
            'action_category' => 'api',
            'action_type' => 'access',
            'description' => "{$method} {$endpoint}",
            'http_method' => $method,
            'endpoint' => $endpoint,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'http_status_code' => $statusCode,
            'status' => $isSuccess ? 'success' : 'failed',
            'duration_ms' => $durationMs,
            'severity' => $isSuccess ? 'low' : 'medium',
        ]);
    }

    /**
     * บันทึก security event
     *
     * @param string $eventType
     * @param int|null $userId
     * @param array $data
     * @return AiRentalAuditLog
     */
    public function logSecurityEvent(string $eventType, ?int $userId, array $data = []): AiRentalAuditLog
    {
        return $this->log(array_merge([
            'user_id' => $userId,
            'action' => "security.{$eventType}",
            'action_category' => 'security',
            'action_type' => 'access',
            'severity' => 'high',
            'is_sensitive' => true,
            'requires_review' => true,
        ], $data));
    }

    /**
     * ดึง audit logs สำหรับ user พร้อมกรอง
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserLogs(int $userId, array $filters = [], int $perPage = 20)
    {
        $query = AiRentalAuditLog::forUser($userId)
            ->with(['deployment', 'cloudConfig'])
            ->recent();

        // กรองตาม action category
        if (isset($filters['category'])) {
            $query->category($filters['category']);
        }

        // กรองตาม action type
        if (isset($filters['action_type'])) {
            $query->actionType($filters['action_type']);
        }

        // กรองตาม status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // กรองตาม severity
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // กรองตาม resource type
        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        // กรองตาม date range
        if (isset($filters['date_from'])) {
            $query->where('action_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('action_at', '<=', $filters['date_to']);
        }

        // Search
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate($perPage);
    }

    /**
     * ดึง audit summary สำหรับ user
     *
     * @param int $userId
     * @return array
     */
    public function getAuditSummary(int $userId): array
    {
        $logs = AiRentalAuditLog::forUser($userId);

        return [
            'total_actions' => $logs->count(),
            'today' => $logs->whereDate('action_at', today())->count(),
            'this_week' => $logs->where('action_at', '>=', now()->startOfWeek())->count(),
            'this_month' => $logs->where('action_at', '>=', now()->startOfMonth())->count(),
            'by_category' => $logs->select('action_category')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('action_category')
                ->pluck('count', 'action_category')
                ->toArray(),
            'by_status' => $logs->select('status')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'failed_actions' => $logs->failed()->count(),
            'requires_review' => $logs->requiresReview()->count(),
            'sensitive_actions' => $logs->sensitive()->count(),
        ];
    }

    /**
     * ดึง recent activities สำหรับ deployment
     *
     * @param int $deploymentId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getDeploymentActivities(int $deploymentId, int $limit = 20)
    {
        return AiRentalAuditLog::where('deployment_id', $deploymentId)
            ->recent()
            ->limit($limit)
            ->get();
    }

    /**
     * Export audit logs สำหรับ compliance
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function exportAuditLogs(int $userId, array $filters = [])
    {
        $query = AiRentalAuditLog::forUser($userId)->recent();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('action_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('action_at', '<=', $filters['date_to']);
        }

        if (isset($filters['category'])) {
            $query->category($filters['category']);
        }

        return $query->get();
    }

    /**
     * ทำความสะอาด sensitive data จาก config
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeConfigData(array $data): array
    {
        $sanitized = $data;

        // ซ่อน API keys
        if (isset($sanitized['api_key'])) {
            $sanitized['api_key'] = '***HIDDEN***';
        }

        if (isset($sanitized['api_secret'])) {
            $sanitized['api_secret'] = '***HIDDEN***';
        }

        return $sanitized;
    }

    /**
     * กำหนด action type จาก action string
     *
     * @param string $action
     * @return string
     */
    protected function getActionType(string $action): string
    {
        if (str_contains($action, '.created')) {
            return 'create';
        } elseif (str_contains($action, '.updated')) {
            return 'update';
        } elseif (str_contains($action, '.deleted')) {
            return 'delete';
        } elseif (str_contains($action, '.started') || str_contains($action, '.stopped')) {
            return 'execute';
        } else {
            return 'access';
        }
    }

    /**
     * กำหนด severity จาก action
     *
     * @param string $action
     * @return string
     */
    protected function getSeverity(string $action): string
    {
        if (str_contains($action, '.deleted') || str_contains($action, '.failed') || str_contains($action, '.exceeded')) {
            return 'high';
        } elseif (str_contains($action, '.created') || str_contains($action, '.updated') || str_contains($action, '.started')) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Clean up old audit logs (ใช้ใน cron job)
     *
     * @param int $daysToKeep
     * @return int จำนวน logs ที่ลบ
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $count = AiRentalAuditLog::where('action_at', '<', $cutoffDate)
            ->where('requires_review', false)
            ->where('is_sensitive', false)
            ->delete();

        Log::info("Cleaned up {$count} old audit logs (older than {$daysToKeep} days)");

        return $count;
    }
}
