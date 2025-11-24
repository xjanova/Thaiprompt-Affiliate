<?php

namespace App\Services\AiRental;

use App\Models\AiRentalBudgetLimit;
use App\Models\AiRentalDeployment;
use App\Models\AiRentalCloudConfig;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

/**
 * Budget Service สำหรับ AI Rental System
 *
 * จัดการ budgets, spending tracking และ cost control
 */
class BudgetService
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
     * สร้าง budget limit ใหม่
     *
     * @param array $data
     * @return AiRentalBudgetLimit
     */
    public function createBudget(array $data): AiRentalBudgetLimit
    {
        try {
            DB::beginTransaction();

            $budget = AiRentalBudgetLimit::create([
                'user_id' => $data['user_id'],
                'cloud_config_id' => $data['cloud_config_id'] ?? null,
                'deployment_id' => $data['deployment_id'] ?? null,
                'budget_name' => $data['budget_name'],
                'description' => $data['description'] ?? null,
                'budget_type' => $data['budget_type'] ?? 'monthly',
                'limit_amount' => $data['limit_amount'],
                'current_spending' => 0,
                'warning_threshold_1' => $data['warning_threshold_1'] ?? 50,
                'warning_threshold_2' => $data['warning_threshold_2'] ?? 75,
                'warning_threshold_3' => $data['warning_threshold_3'] ?? 90,
                'auto_stop_at_limit' => $data['auto_stop_at_limit'] ?? false,
                'auto_notify_email' => $data['auto_notify_email'] ?? true,
                'auto_notify_push' => $data['auto_notify_push'] ?? true,
                'is_active' => $data['is_active'] ?? true,
                'status' => 'active',
                'allow_rollover' => $data['allow_rollover'] ?? false,
                'notification_emails' => $data['notification_emails'] ?? null,
                'webhook_urls' => $data['webhook_urls'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // ตั้งค่า period
            $budget->period_start = now();
            $budget->setPeriod();
            $budget->save();

            DB::commit();

            Log::info("Budget limit created: {$budget->budget_name}", [
                'budget_id' => $budget->id,
                'limit_amount' => $budget->limit_amount,
                'budget_type' => $budget->budget_type,
            ]);

            return $budget;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create budget limit: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * อัพเดท budget
     *
     * @param AiRentalBudgetLimit $budget
     * @param array $data
     * @return AiRentalBudgetLimit
     */
    public function updateBudget(AiRentalBudgetLimit $budget, array $data): AiRentalBudgetLimit
    {
        try {
            $budget->update($data);

            Log::info("Budget limit updated: {$budget->budget_name}", [
                'budget_id' => $budget->id,
            ]);

            return $budget->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update budget limit: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * บันทึกค่าใช้จ่ายจาก deployment
     *
     * @param AiRentalDeployment $deployment
     * @param float $cost
     * @return void
     */
    public function trackDeploymentCost(AiRentalDeployment $deployment, float $cost): void
    {
        try {
            // หา budget limits ที่เกี่ยวข้อง
            $budgets = $this->getApplicableBudgets($deployment);

            foreach ($budgets as $budget) {
                $this->addSpending($budget, $cost, $deployment);
            }
        } catch (Exception $e) {
            Log::error('Failed to track deployment cost: ' . $e->getMessage(), [
                'deployment_id' => $deployment->id,
                'cost' => $cost,
            ]);
        }
    }

    /**
     * เพิ่มค่าใช้จ่ายให้ budget
     *
     * @param AiRentalBudgetLimit $budget
     * @param float $amount
     * @param AiRentalDeployment|null $deployment
     * @return void
     */
    public function addSpending(AiRentalBudgetLimit $budget, float $amount, ?AiRentalDeployment $deployment = null): void
    {
        try {
            DB::beginTransaction();

            $oldSpending = $budget->current_spending;
            $oldPercentage = $budget->getSpendingPercentage();

            // เพิ่มค่าใช้จ่าย
            $budget->addSpending($amount);

            $newPercentage = $budget->getSpendingPercentage();

            // ตรวจสอบ thresholds และส่ง alerts
            $this->checkThresholdsAndAlert($budget, $oldPercentage, $newPercentage, $deployment);

            // ตรวจสอบว่าเกิน limit หรือยัง
            if ($budget->isExceeded() && $budget->status !== 'exceeded') {
                $this->handleBudgetExceeded($budget, $deployment);
            }

            DB::commit();

            Log::info("Budget spending updated", [
                'budget_id' => $budget->id,
                'old_spending' => $oldSpending,
                'new_spending' => $budget->current_spending,
                'amount_added' => $amount,
                'percentage' => round($newPercentage, 2),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to add spending: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ตรวจสอบ thresholds และส่ง alerts
     *
     * @param AiRentalBudgetLimit $budget
     * @param float $oldPercentage
     * @param float $newPercentage
     * @param AiRentalDeployment|null $deployment
     * @return void
     */
    protected function checkThresholdsAndAlert(
        AiRentalBudgetLimit $budget,
        float $oldPercentage,
        float $newPercentage,
        ?AiRentalDeployment $deployment
    ): void {
        $user = $budget->user;

        // Threshold 1
        if ($newPercentage >= $budget->warning_threshold_1 && !$budget->threshold_1_triggered) {
            $this->monitoringService->createCostWarningAlert(
                $user,
                $budget,
                $budget->current_spending,
                $budget->warning_threshold_1
            );
        }

        // Threshold 2
        if ($newPercentage >= $budget->warning_threshold_2 && !$budget->threshold_2_triggered) {
            $this->monitoringService->createCostWarningAlert(
                $user,
                $budget,
                $budget->current_spending,
                $budget->warning_threshold_2
            );
        }

        // Threshold 3
        if ($newPercentage >= $budget->warning_threshold_3 && !$budget->threshold_3_triggered) {
            $this->monitoringService->createCostWarningAlert(
                $user,
                $budget,
                $budget->current_spending,
                $budget->warning_threshold_3
            );
        }
    }

    /**
     * จัดการเมื่อ budget เกิน limit
     *
     * @param AiRentalBudgetLimit $budget
     * @param AiRentalDeployment|null $deployment
     * @return void
     */
    protected function handleBudgetExceeded(AiRentalBudgetLimit $budget, ?AiRentalDeployment $deployment): void
    {
        // ส่ง critical alert
        $this->monitoringService->createAlert([
            'user_id' => $budget->user_id,
            'deployment_id' => $deployment->id ?? null,
            'alert_type' => 'budget_exceeded',
            'severity' => 'critical',
            'title' => '🚨 งบประมาณเกิน Limit!',
            'message' => "งบประมาณ '{$budget->budget_name}' เกิน limit แล้ว!",
            'description' => "ค่าใช้จ่ายปัจจุบัน: \${$budget->current_spending} (Limit: \${$budget->limit_amount})",
            'alert_data' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'limit_amount' => $budget->limit_amount,
                'current_spending' => $budget->current_spending,
                'exceeded_by' => $budget->current_spending - $budget->limit_amount,
            ],
            'action_required' => 'ตรวจสอบและหยุด deployments',
            'action_url' => route('admin.ai-rental.deployments.index'),
            'action_label' => 'จัดการ Deployments',
            'priority' => 1,
            'cost_at_alert' => $budget->current_spending,
            'budget_limit' => $budget->limit_amount,
        ]);

        // Auto-stop deployments ถ้าเปิดไว้
        if ($budget->auto_stop_at_limit) {
            $this->autoStopDeployments($budget, $deployment);
        }

        Log::warning("Budget exceeded", [
            'budget_id' => $budget->id,
            'budget_name' => $budget->budget_name,
            'limit_amount' => $budget->limit_amount,
            'current_spending' => $budget->current_spending,
        ]);
    }

    /**
     * หยุด deployments อัตโนมัติเมื่อเกิน budget
     *
     * @param AiRentalBudgetLimit $budget
     * @param AiRentalDeployment|null $currentDeployment
     * @return int จำนวน deployments ที่หยุด
     */
    protected function autoStopDeployments(AiRentalBudgetLimit $budget, ?AiRentalDeployment $currentDeployment): int
    {
        $query = AiRentalDeployment::where('user_id', $budget->user_id)
            ->running();

        // ถ้าเป็น budget สำหรับ config เฉพาะ
        if ($budget->cloud_config_id) {
            $query->where('cloud_config_id', $budget->cloud_config_id);
        }

        // ถ้าเป็น budget สำหรับ deployment เฉพาะ
        if ($budget->deployment_id) {
            $query->where('id', $budget->deployment_id);
        }

        $deployments = $query->get();
        $stopped = 0;

        foreach ($deployments as $deployment) {
            try {
                // TODO: เรียก cloud provider API เพื่อหยุด deployment จริงๆ
                // $provider = CloudProviderFactory::create($deployment->cloudConfig);
                // $provider->stopInstance($deployment->cloudConfig, $deployment->deployment_id);

                $deployment->status = 'stopped';
                $deployment->stopped_at = now();
                $deployment->save();

                $stopped++;

                Log::info("Auto-stopped deployment due to budget exceeded", [
                    'deployment_id' => $deployment->id,
                    'budget_id' => $budget->id,
                ]);
            } catch (Exception $e) {
                Log::error("Failed to auto-stop deployment: " . $e->getMessage(), [
                    'deployment_id' => $deployment->id,
                ]);
            }
        }

        return $stopped;
    }

    /**
     * หา budget limits ที่เกี่ยวข้องกับ deployment
     *
     * @param AiRentalDeployment $deployment
     * @return \Illuminate\Support\Collection
     */
    protected function getApplicableBudgets(AiRentalDeployment $deployment)
    {
        return AiRentalBudgetLimit::where('user_id', $deployment->user_id)
            ->active()
            ->where(function ($query) use ($deployment) {
                $query->where(function ($q) use ($deployment) {
                    // Budget เฉพาะ deployment นี้
                    $q->where('deployment_id', $deployment->id);
                })->orWhere(function ($q) use ($deployment) {
                    // Budget เฉพาะ config นี้
                    $q->where('cloud_config_id', $deployment->cloud_config_id)
                        ->whereNull('deployment_id');
                })->orWhere(function ($q) {
                    // Budget แบบ global (ไม่มี config_id และ deployment_id)
                    $q->whereNull('cloud_config_id')
                        ->whereNull('deployment_id');
                });
            })
            ->get();
    }

    /**
     * รีเซ็ต budget ตาม period
     *
     * @param AiRentalBudgetLimit $budget
     * @return bool
     */
    public function resetBudget(AiRentalBudgetLimit $budget): bool
    {
        try {
            $budget->reset();

            Log::info("Budget reset", [
                'budget_id' => $budget->id,
                'budget_type' => $budget->budget_type,
                'rollover_amount' => $budget->rollover_amount,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to reset budget: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * รีเซ็ต budgets ที่หมดอายุ period (ใช้ใน cron job)
     *
     * @return int จำนวน budgets ที่รีเซ็ต
     */
    public function resetExpiredBudgets(): int
    {
        $budgets = AiRentalBudgetLimit::active()
            ->whereNotNull('period_end')
            ->where('period_end', '<', now())
            ->get();

        $count = 0;
        foreach ($budgets as $budget) {
            if ($this->resetBudget($budget)) {
                $count++;
            }
        }

        Log::info("Reset {$count} expired budgets");

        return $count;
    }

    /**
     * ดึง budget summary สำหรับ user
     *
     * @param int $userId
     * @return array
     */
    public function getBudgetSummary(int $userId): array
    {
        $budgets = AiRentalBudgetLimit::forUser($userId);

        $totalLimit = $budgets->sum('limit_amount');
        $totalSpending = $budgets->sum('current_spending');

        return [
            'total_budgets' => $budgets->count(),
            'active_budgets' => $budgets->active()->count(),
            'exceeded_budgets' => $budgets->exceeded()->count(),
            'total_limit' => $totalLimit,
            'total_spending' => $totalSpending,
            'total_remaining' => $totalLimit - $totalSpending,
            'overall_percentage' => $totalLimit > 0 ? ($totalSpending / $totalLimit) * 100 : 0,
            'by_type' => $budgets->select('budget_type')
                ->selectRaw('COUNT(*) as count, SUM(limit_amount) as total_limit, SUM(current_spending) as total_spending')
                ->groupBy('budget_type')
                ->get()
                ->keyBy('budget_type')
                ->toArray(),
        ];
    }

    /**
     * คำนวณ projected spending
     *
     * @param int $userId
     * @param string $budgetType
     * @return float
     */
    public function calculateProjectedSpending(int $userId, string $budgetType = 'monthly'): float
    {
        // ดึง spending history
        $deployments = AiRentalDeployment::where('user_id', $userId)
            ->where('created_at', '>=', $this->getStartOfPeriod($budgetType))
            ->get();

        if ($deployments->isEmpty()) {
            return 0;
        }

        $totalSpending = $deployments->sum('total_cost');
        $daysInPeriod = $this->getDaysInPeriod($budgetType);
        $daysPassed = now()->diffInDays($this->getStartOfPeriod($budgetType)) + 1;

        // คำนวณ average spending per day
        $avgSpendingPerDay = $totalSpending / $daysPassed;

        // Project สำหรับทั้ง period
        return $avgSpendingPerDay * $daysInPeriod;
    }

    /**
     * ดึงวันเริ่มต้นของ period
     *
     * @param string $budgetType
     * @return Carbon
     */
    protected function getStartOfPeriod(string $budgetType): Carbon
    {
        return match ($budgetType) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };
    }

    /**
     * ดึงจำนวนวันใน period
     *
     * @param string $budgetType
     * @return int
     */
    protected function getDaysInPeriod(string $budgetType): int
    {
        return match ($budgetType) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => now()->daysInMonth,
            default => 30,
        };
    }
}
