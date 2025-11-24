<?php

namespace App\Observers;

use App\Models\AiRentalBudgetLimit;
use App\Services\AiRental\AuditService;
use App\Services\AiRental\MonitoringService;
use Illuminate\Support\Facades\Log;

/**
 * AI Rental Budget Limit Observer
 *
 * Observer นี้จะ auto-log ทุกการเปลี่ยนแปลงของ AiRentalBudgetLimit model
 * และสร้าง alerts เมื่อมีการเปลี่ยนแปลงสำคัญ
 */
class AiRentalBudgetLimitObserver
{
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
     * สร้าง observer instance
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
     * Handle the "creating" event
     *
     * เรียกก่อน budget limit จะถูกสร้าง
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    public function creating(AiRentalBudgetLimit $budget): void
    {
        Log::info('[AI Rental Observer] Budget limit creating', [
            'budget_name' => $budget->budget_name,
            'user_id' => $budget->user_id,
            'limit_amount' => $budget->limit_amount,
        ]);

        // Set default values
        if (empty($budget->current_spending)) {
            $budget->current_spending = 0;
        }

        if (empty($budget->warning_threshold)) {
            $budget->warning_threshold = 80; // Default 80%
        }

        // คำนวณ period_start และ period_end ถ้ายังไม่มี
        if (empty($budget->period_start)) {
            $budget->period_start = now();
        }

        if (empty($budget->period_end)) {
            $budget->period_end = $this->calculatePeriodEnd($budget);
        }
    }

    /**
     * Handle the "created" event
     *
     * เรียกหลังจาก budget limit ถูกสร้างแล้ว
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    public function created(AiRentalBudgetLimit $budget): void
    {
        Log::info('[AI Rental Observer] Budget limit created', [
            'budget_id' => $budget->id,
            'budget_name' => $budget->budget_name,
            'limit_amount' => $budget->limit_amount,
        ]);

        try {
            // บันทึก audit log
            $this->auditService->log(
                user: $budget->user,
                action: 'budget_limit.created',
                category: 'budget_management',
                description: "สร้าง budget limit ใหม่: {$budget->budget_name}",
                relatedModel: $budget,
                newValues: $budget->toArray(),
                metadata: [
                    'budget_id' => $budget->id,
                    'budget_name' => $budget->budget_name,
                    'budget_type' => $budget->budget_type,
                    'limit_amount' => $budget->limit_amount,
                    'period_type' => $budget->period_type,
                ],
                isSensitive: false,
                requiresReview: false,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );

            // สร้าง info alert
            $this->monitoringService->createAlert([
                'user_id' => $budget->user_id,
                'alert_type' => 'budget_created',
                'severity' => 'info',
                'title' => '💰 Budget Limit สร้างสำเร็จ',
                'message' => "Budget '{$budget->budget_name}' ถูกสร้างแล้ว",
                'description' => "วงเงิน: \${$budget->limit_amount} / {$budget->period_type}",
                'alert_data' => [
                    'budget_id' => $budget->id,
                    'budget_name' => $budget->budget_name,
                    'limit_amount' => $budget->limit_amount,
                ],
                'action_url' => route('admin.ai-rental.deployments.index'),
                'action_label' => 'ดู Budgets',
                'priority' => 5,
                'expires_at' => now()->addDays(7),
            ]);
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log budget creation', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "updating" event
     *
     * เรียกก่อน budget limit จะถูกอัพเดท
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    public function updating(AiRentalBudgetLimit $budget): void
    {
        // ตรวจสอบว่า current_spending เพิ่มขึ้นหรือไม่
        if ($budget->isDirty('current_spending')) {
            $oldSpending = $budget->getOriginal('current_spending');
            $newSpending = $budget->current_spending;

            Log::info('[AI Rental Observer] Budget spending changing', [
                'budget_id' => $budget->id,
                'old_spending' => $oldSpending,
                'new_spending' => $newSpending,
                'limit' => $budget->limit_amount,
            ]);

            // เก็บข้อมูลไว้สำหรับ updated() event
            $budget->_oldSpending = $oldSpending;
            $budget->_spendingDiff = $newSpending - $oldSpending;
        }

        // ตรวจสอบว่า limit_amount เปลี่ยนหรือไม่
        if ($budget->isDirty('limit_amount')) {
            $oldLimit = $budget->getOriginal('limit_amount');
            $newLimit = $budget->limit_amount;

            Log::info('[AI Rental Observer] Budget limit changing', [
                'budget_id' => $budget->id,
                'old_limit' => $oldLimit,
                'new_limit' => $newLimit,
            ]);

            $budget->_oldLimit = $oldLimit;
        }
    }

    /**
     * Handle the "updated" event
     *
     * เรียกหลังจาก budget limit ถูกอัพเดทแล้ว
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    public function updated(AiRentalBudgetLimit $budget): void
    {
        Log::info('[AI Rental Observer] Budget limit updated', [
            'budget_id' => $budget->id,
        ]);

        try {
            // ดึงข้อมูลที่เปลี่ยนแปลง
            $changes = $budget->getChanges();

            // บันทึก audit log
            $this->auditService->log(
                user: $budget->user,
                action: 'budget_limit.updated',
                category: 'budget_management',
                description: "อัพเดท budget limit: {$budget->budget_name}",
                relatedModel: $budget,
                oldValues: array_intersect_key(
                    $budget->getOriginal(),
                    $changes
                ),
                newValues: $changes,
                metadata: [
                    'budget_id' => $budget->id,
                    'budget_name' => $budget->budget_name,
                    'changed_fields' => array_keys($changes),
                    'spending_diff' => $budget->_spendingDiff ?? null,
                ],
                isSensitive: false,
                requiresReview: isset($changes['limit_amount']) || isset($changes['is_active']),
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );

            // ถ้า current_spending เปลี่ยน → ตรวจสอบ threshold
            if (isset($changes['current_spending'])) {
                $this->checkBudgetThreshold($budget);
            }

            // ถ้า limit_amount เปลี่ยน → สร้าง alert
            if (isset($changes['limit_amount']) && isset($budget->_oldLimit)) {
                $this->notifyLimitChanged($budget, $budget->_oldLimit, $changes['limit_amount']);
            }

            // ถ้า is_active เปลี่ยน
            if (isset($changes['is_active'])) {
                $this->notifyStatusChanged($budget, $changes['is_active']);
            }

            // ลบข้อมูลชั่วคราว
            unset($budget->_oldSpending, $budget->_spendingDiff, $budget->_oldLimit);
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log budget update', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "deleted" event
     *
     * เรียกหลังจาก budget limit ถูกลบ
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    public function deleted(AiRentalBudgetLimit $budget): void
    {
        Log::warning('[AI Rental Observer] Budget limit deleted', [
            'budget_id' => $budget->id,
            'budget_name' => $budget->budget_name,
        ]);

        try {
            // บันทึก audit log
            $this->auditService->log(
                user: $budget->user,
                action: 'budget_limit.deleted',
                category: 'budget_management',
                description: "ลบ budget limit: {$budget->budget_name}",
                relatedModel: $budget,
                oldValues: $budget->toArray(),
                metadata: [
                    'budget_id' => $budget->id,
                    'budget_name' => $budget->budget_name,
                    'final_spending' => $budget->current_spending,
                    'limit_amount' => $budget->limit_amount,
                ],
                isSensitive: false,
                requiresReview: true,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log budget deletion', [
                'budget_id' => $budget->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ตรวจสอบ budget threshold
     *
     * @param AiRentalBudgetLimit $budget
     * @return void
     */
    protected function checkBudgetThreshold(AiRentalBudgetLimit $budget): void
    {
        $percentage = ($budget->current_spending / $budget->limit_amount) * 100;

        // ตรวจสอบ threshold ต่างๆ
        $thresholds = [50, 75, 80, 90, 95, 100];

        foreach ($thresholds as $threshold) {
            // ถ้าเกิน threshold และยังไม่ได้แจ้งเตือน
            if ($percentage >= $threshold && !$budget->hasAlertedThreshold($threshold)) {
                $this->monitoringService->createCostWarningAlert(
                    $budget->user,
                    $budget,
                    $budget->current_spending,
                    $threshold
                );

                // บันทึกว่าแจ้งเตือนแล้ว
                $budget->markThresholdAlerted($threshold);

                Log::warning('[AI Rental Observer] Budget threshold exceeded', [
                    'budget_id' => $budget->id,
                    'threshold' => $threshold,
                    'percentage' => $percentage,
                ]);
            }
        }
    }

    /**
     * แจ้งเตือนเมื่อ limit เปลี่ยน
     *
     * @param AiRentalBudgetLimit $budget
     * @param float $oldLimit
     * @param float $newLimit
     * @return void
     */
    protected function notifyLimitChanged(AiRentalBudgetLimit $budget, float $oldLimit, float $newLimit): void
    {
        $change = $newLimit > $oldLimit ? 'เพิ่ม' : 'ลด';
        $diff = abs($newLimit - $oldLimit);

        $this->monitoringService->createAlert([
            'user_id' => $budget->user_id,
            'alert_type' => 'budget_limit_changed',
            'severity' => 'info',
            'title' => "💰 Budget Limit {$change}",
            'message' => "Budget '{$budget->budget_name}' วงเงิน{$change}จาก \${$oldLimit} → \${$newLimit}",
            'description' => "{$change}วงเงิน \${$diff}",
            'alert_data' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'old_limit' => $oldLimit,
                'new_limit' => $newLimit,
                'difference' => $diff,
            ],
            'priority' => 4,
        ]);
    }

    /**
     * แจ้งเตือนเมื่อ status เปลี่ยน
     *
     * @param AiRentalBudgetLimit $budget
     * @param bool $isActive
     * @return void
     */
    protected function notifyStatusChanged(AiRentalBudgetLimit $budget, bool $isActive): void
    {
        $status = $isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $emoji = $isActive ? '✅' : '⏸️';

        $this->monitoringService->createAlert([
            'user_id' => $budget->user_id,
            'alert_type' => 'budget_status_changed',
            'severity' => 'info',
            'title' => "{$emoji} Budget {$status}",
            'message' => "Budget '{$budget->budget_name}' ถูก{$status}แล้ว",
            'alert_data' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'is_active' => $isActive,
            ],
            'priority' => 5,
        ]);
    }

    /**
     * คำนวณ period_end จาก period_type
     *
     * @param AiRentalBudgetLimit $budget
     * @return \Carbon\Carbon
     */
    protected function calculatePeriodEnd(AiRentalBudgetLimit $budget): \Carbon\Carbon
    {
        $start = $budget->period_start ?? now();

        return match ($budget->period_type) {
            'daily' => $start->copy()->endOfDay(),
            'weekly' => $start->copy()->addWeek()->endOfDay(),
            'monthly' => $start->copy()->addMonth()->endOfDay(),
            'quarterly' => $start->copy()->addMonths(3)->endOfDay(),
            'yearly' => $start->copy()->addYear()->endOfDay(),
            default => $start->copy()->addMonth()->endOfDay(),
        };
    }
}
