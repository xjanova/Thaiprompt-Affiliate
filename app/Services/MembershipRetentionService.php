<?php

namespace App\Services;

use App\Models\User;
use App\Models\MembershipRetentionStatus;
use App\Models\MembershipRetentionHistory;
use App\Models\MembershipRetentionTransaction;
use App\Models\MembershipRetentionRepair;
use App\Models\MembershipRetentionAdvanceRenewal;
use App\Models\MembershipRetentionSetting;
use App\Models\Commission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipRetentionService
{
    /**
     * Initialize retention status for a user
     */
    public function initializeUser(User $user): MembershipRetentionStatus
    {
        $minimumPoints = $this->getSetting('minimum_points_per_month', 1000);
        $now = Carbon::now();
        $periodStart = $now->startOfMonth()->copy();
        $periodEnd = $now->endOfMonth()->copy();

        return MembershipRetentionStatus::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'active',
                'current_points' => 0,
                'required_points' => $minimumPoints,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'next_renewal_date' => $periodEnd,
                'consecutive_months' => 0,
                'last_active_date' => $now,
            ]
        );
    }

    /**
     * Add points to user's retention
     */
    public function addPoints(
        User $user,
        float $points,
        string $transactionType,
        ?int $commissionId = null,
        ?string $description = null,
        ?array $metadata = null
    ): MembershipRetentionTransaction {
        $status = $this->getOrCreateStatus($user);
        $periodMonth = Carbon::now()->format('Y-m');

        // Create transaction
        $transaction = MembershipRetentionTransaction::create([
            'user_id' => $user->id,
            'commission_id' => $commissionId,
            'transaction_type' => $transactionType,
            'points' => $points,
            'period_month' => $periodMonth,
            'description' => $description,
            'metadata' => $metadata,
        ]);

        // Update current points
        $status->current_points += $points;
        $status->last_active_date = Carbon::now();
        $status->save();

        // Check if reached required points
        $this->checkAndUpdatePeriodStatus($user);

        return $transaction;
    }

    /**
     * Get or create retention status
     */
    public function getOrCreateStatus(User $user): MembershipRetentionStatus
    {
        $status = MembershipRetentionStatus::where('user_id', $user->id)->first();

        if (!$status) {
            $status = $this->initializeUser($user);
        }

        return $status;
    }

    /**
     * Check and update period status
     */
    public function checkAndUpdatePeriodStatus(User $user): void
    {
        $status = $this->getOrCreateStatus($user);
        $periodMonth = Carbon::now()->format('Y-m');

        // Get or create history record
        $history = MembershipRetentionHistory::firstOrCreate(
            [
                'user_id' => $user->id,
                'period_month' => $periodMonth,
            ],
            [
                'required_points' => $status->required_points,
                'earned_points' => 0,
                'status' => 'pending',
                'period_start' => $status->period_start,
                'period_end' => $status->period_end,
            ]
        );

        // Update earned points
        $history->earned_points = $status->current_points;

        // Check if achieved
        if ($status->current_points >= $status->required_points && $history->status !== 'achieved') {
            $history->status = 'achieved';
            $history->achieved_at = Carbon::now();
            $status->consecutive_months++;
            $status->status = 'active';
        }

        $history->save();
        $status->save();
    }

    /**
     * Process monthly renewal
     */
    public function processMonthlyRenewal(): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $lastMonth = Carbon::now()->subMonth();
        $periodMonth = $lastMonth->format('Y-m');

        // Get all active users
        $activeStatuses = MembershipRetentionStatus::active()->get();

        foreach ($activeStatuses as $status) {
            try {
                $results['processed']++;

                // Get history for last month
                $history = MembershipRetentionHistory::where('user_id', $status->user_id)
                    ->where('period_month', $periodMonth)
                    ->first();

                if ($history) {
                    // Check if achieved
                    if ($history->earned_points >= $history->required_points) {
                        $history->status = 'achieved';
                        $history->achieved_at = Carbon::now();
                        $results['succeeded']++;
                    } else {
                        // Check if user has advance renewal
                        $hasAdvanceRenewal = MembershipRetentionAdvanceRenewal::where('user_id', $status->user_id)
                            ->active()
                            ->exists();

                        if (!$hasAdvanceRenewal) {
                            $history->status = 'failed';
                            $status->status = 'expired';
                            $status->consecutive_months = 0;
                            $results['failed']++;
                        } else {
                            // Protected by advance renewal
                            $history->status = 'achieved';
                            $history->achieved_at = Carbon::now();
                            $history->notes = 'Protected by advance renewal';
                            $results['succeeded']++;
                        }
                    }

                    $history->save();
                }

                // Reset for new month
                $now = Carbon::now();
                $status->current_points = 0;
                $status->period_start = $now->startOfMonth()->copy();
                $status->period_end = $now->endOfMonth()->copy();
                $status->next_renewal_date = $status->period_end;
                $status->save();

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $status->user_id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Membership renewal error for user ' . $status->user_id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Calculate repair cost
     */
    public function calculateRepairCost(User $user, string $repairPeriodMonth): float
    {
        $history = MembershipRetentionHistory::where('user_id', $user->id)
            ->where('period_month', $repairPeriodMonth)
            ->first();

        if (!$history) {
            return 0;
        }

        $pointsNeeded = max(0, $history->required_points - $history->earned_points);
        $costPerPoint = $this->getSetting('repair_cost_per_point', 1.5);

        return $pointsNeeded * $costPerPoint;
    }

    /**
     * Process repair payment
     */
    public function processRepair(User $user, string $repairPeriodMonth, float $amount): MembershipRetentionRepair
    {
        DB::beginTransaction();

        try {
            $history = MembershipRetentionHistory::where('user_id', $user->id)
                ->where('period_month', $repairPeriodMonth)
                ->firstOrFail();

            $pointsNeeded = max(0, $history->required_points - $history->earned_points);
            $repairCost = $this->calculateRepairCost($user, $repairPeriodMonth);

            // Create repair record
            $repair = MembershipRetentionRepair::create([
                'user_id' => $user->id,
                'repair_period_month' => $repairPeriodMonth,
                'points_needed' => $pointsNeeded,
                'repair_cost' => $repairCost,
                'payment_status' => 'paid',
                'repaired_at' => Carbon::now(),
                'notes' => "Repaired {$pointsNeeded} points for {$repairCost}",
            ]);

            // Update history
            $history->earned_points = $history->required_points;
            $history->status = 'achieved';
            $history->achieved_at = Carbon::now();
            $history->notes = 'Repaired via payment';
            $history->save();

            // Reactivate user if expired
            $status = MembershipRetentionStatus::where('user_id', $user->id)->first();
            if ($status && $status->status === 'expired') {
                $status->status = 'active';
                $status->save();
            }

            DB::commit();

            return $repair;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate advance renewal cost
     */
    public function calculateAdvanceRenewalCost(int $months): float
    {
        $minimumPoints = $this->getSetting('minimum_points_per_month', 1000);
        $discount = $this->getSetting('advance_renewal_discount', 0.9);
        $costPerPoint = 1; // Base cost per point

        return $minimumPoints * $costPerPoint * $months * $discount;
    }

    /**
     * Process advance renewal
     */
    public function processAdvanceRenewal(User $user, int $months, float $amount): MembershipRetentionAdvanceRenewal
    {
        DB::beginTransaction();

        try {
            $cost = $this->calculateAdvanceRenewalCost($months);
            $validFrom = Carbon::now();
            $validUntil = Carbon::now()->addMonths($months);

            $renewal = MembershipRetentionAdvanceRenewal::create([
                'user_id' => $user->id,
                'months_advance' => $months,
                'total_cost' => $cost,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'payment_status' => 'paid',
                'paid_at' => Carbon::now(),
                'notes' => "Advance renewal for {$months} months",
            ]);

            DB::commit();

            return $renewal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if user can earn commission
     */
    public function canEarnCommission(User $user): bool
    {
        // Check if system is enabled
        if (!$this->getSetting('enable_retention_system', true)) {
            return true;
        }

        // Check if blocking is enabled
        if (!$this->getSetting('block_commission_if_expired', true)) {
            return true;
        }

        $status = MembershipRetentionStatus::where('user_id', $user->id)->first();

        if (!$status) {
            return true; // New user, allow commission
        }

        return $status->status === 'active';
    }

    /**
     * Get expiring users
     */
    public function getExpiringUsers(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return MembershipRetentionStatus::expiringSoon($days)
            ->with('user')
            ->get();
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(User $user): array
    {
        $status = $this->getOrCreateStatus($user);

        $historyStats = MembershipRetentionHistory::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_periods,
                SUM(CASE WHEN status = "achieved" THEN 1 ELSE 0 END) as achieved_count,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count
            ')
            ->first();

        $repairs = MembershipRetentionRepair::where('user_id', $user->id)
            ->paid()
            ->count();

        $advanceRenewals = MembershipRetentionAdvanceRenewal::where('user_id', $user->id)
            ->paid()
            ->count();

        return [
            'status' => $status->status,
            'current_points' => $status->current_points,
            'required_points' => $status->required_points,
            'points_percentage' => $status->getPointsPercentage(),
            'remaining_points' => $status->getRemainingPoints(),
            'days_remaining' => $status->daysRemaining(),
            'health_color' => $status->getHealthColor(),
            'health_percentage' => $status->getHealthPercentage(),
            'consecutive_months' => $status->consecutive_months,
            'total_periods' => $historyStats->total_periods ?? 0,
            'achieved_count' => $historyStats->achieved_count ?? 0,
            'failed_count' => $historyStats->failed_count ?? 0,
            'repair_count' => $repairs,
            'advance_renewal_count' => $advanceRenewals,
            'next_renewal_date' => $status->next_renewal_date?->format('Y-m-d'),
        ];
    }

    /**
     * Get setting value
     */
    private function getSetting(string $key, $default = null)
    {
        return MembershipRetentionSetting::get($key, $default);
    }

    /**
     * Expire inactive users
     */
    public function expireInactiveUsers(): array
    {
        $results = [
            'processed' => 0,
            'expired' => 0,
        ];

        $gracePeriodDays = $this->getSetting('grace_period_days', 3);
        $targetDate = Carbon::today()->subDays($gracePeriodDays);

        $expiredStatuses = MembershipRetentionStatus::active()
            ->whereNotNull('next_renewal_date')
            ->where('next_renewal_date', '<', $targetDate)
            ->get();

        foreach ($expiredStatuses as $status) {
            $results['processed']++;

            // Check if user has advance renewal
            $hasAdvanceRenewal = MembershipRetentionAdvanceRenewal::where('user_id', $status->user_id)
                ->active()
                ->exists();

            if (!$hasAdvanceRenewal) {
                $status->status = 'expired';
                $status->consecutive_months = 0;
                $status->save();
                $results['expired']++;
            }
        }

        return $results;
    }
}
