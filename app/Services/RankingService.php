<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rank;
use App\Models\RankPromotion;
use App\Models\UserRankProgress;
use App\Models\RankSetting;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankingService
{
    /**
     * Check and process auto promotions for all eligible users
     */
    public function processAutoPromotions(): array
    {
        $settings = RankSetting::get();

        if (!$settings->enable_ranking_system || !$settings->enable_auto_promotion) {
            return ['success' => false, 'message' => 'Auto promotion is disabled'];
        }

        $promoted = [];
        $users = User::whereNotNull('current_rank_id')->get();

        foreach ($users as $user) {
            $result = $this->checkAndPromoteUser($user);
            if ($result['promoted']) {
                $promoted[] = $result;
            }
        }

        return [
            'success' => true,
            'count' => count($promoted),
            'promotions' => $promoted
        ];
    }

    /**
     * Check and promote a specific user
     */
    public function checkAndPromoteUser(User $user): array
    {
        $settings = RankSetting::get();

        if (!$user->currentRank) {
            return ['promoted' => false, 'reason' => 'User has no current rank'];
        }

        $nextRank = $user->currentRank->next_rank;
        if (!$nextRank) {
            return ['promoted' => false, 'reason' => 'User is at highest rank'];
        }

        $eligibility = $nextRank->checkUserEligibility($user);

        if (!$eligibility['eligible']) {
            return [
                'promoted' => false,
                'reason' => 'Requirements not met',
                'eligibility' => $eligibility
            ];
        }

        // Create promotion request
        $promotion = new RankPromotion([
            'user_id' => $user->id,
            'from_rank_id' => $user->current_rank_id,
            'to_rank_id' => $nextRank->id,
            'promotion_type' => 'auto',
            'requirements_snapshot' => $eligibility['requirements'],
        ]);

        if ($settings->enable_manual_approval) {
            $promotion->status = 'pending';
            $promotion->save();

            // Notify admins
            $this->notifyAdminsForApproval($promotion);

            return [
                'promoted' => false,
                'pending_approval' => true,
                'promotion' => $promotion
            ];
        } else {
            $promotion->status = 'approved';
            $promotion->approved_at = now();
            $promotion->save();
            $promotion->activate();

            // Apply bonuses
            $this->applyRankBonuses($user, $nextRank);

            // Notify user
            $this->notifyUserPromotion($user, $nextRank);

            return [
                'promoted' => true,
                'promotion' => $promotion,
                'rank' => $nextRank
            ];
        }
    }

    /**
     * Calculate and update rank points for a user
     *
     * ใช้ข้อมูลรวมจากทั้ง Affiliate และ MLM systems
     */
    public function calculateRankPoints(User $user): int
    {
        $settings = RankSetting::get();
        $earningService = new \App\Services\UnifiedEarningService();

        $points = 0;

        // ใช้ unified earnings และ referrals จากทั้ง 2 ระบบ
        $totalReferrals = $earningService->getTotalReferrals($user);
        $totalEarnings = $earningService->getTotalEarnings($user);

        // Points from referrals (Affiliate + MLM)
        $points += $totalReferrals * $settings->points_per_referral;

        // Points from sales (Affiliate + MLM)
        $points += floor($totalEarnings * $settings->points_per_sale);

        // Points from active months
        $monthsActive = $user->created_at->diffInMonths(now());
        $points += $monthsActive * $settings->points_per_active_month;

        $user->rank_points = $points;
        $user->save();

        return $points;
    }

    /**
     * Update progress tracking for a user
     */
    public function updateUserProgress(User $user): void
    {
        $currentRank = $user->currentRank;

        if (!$currentRank) {
            // Get default rank and create progress
            $defaultRank = Rank::where('is_default', true)->first();
            if ($defaultRank) {
                $this->updateProgressForRank($user, $defaultRank);
            }
            return;
        }

        // Update progress for next rank
        $nextRank = $currentRank->next_rank;
        if ($nextRank) {
            $this->updateProgressForRank($user, $nextRank);
        }
    }

    /**
     * Update progress for a specific rank
     */
    private function updateProgressForRank(User $user, Rank $rank): void
    {
        $progress = UserRankProgress::firstOrCreate([
            'user_id' => $user->id,
            'target_rank_id' => $rank->id,
        ]);

        $progress->calculate();
    }

    /**
     * Request manual promotion
     */
    public function requestManualPromotion(User $user, Rank $targetRank): RankPromotion
    {
        $eligibility = $targetRank->checkUserEligibility($user);

        $promotion = RankPromotion::create([
            'user_id' => $user->id,
            'from_rank_id' => $user->current_rank_id,
            'to_rank_id' => $targetRank->id,
            'promotion_type' => 'manual',
            'status' => 'pending',
            'requirements_snapshot' => $eligibility['requirements'],
        ]);

        $this->notifyAdminsForApproval($promotion);

        return $promotion;
    }

    /**
     * Request rank purchase
     */
    public function requestRankPurchase(User $user, Rank $targetRank, float $amount, array $paymentInfo): RankPromotion
    {
        $settings = RankSetting::get();

        if (!$settings->enable_rank_purchase) {
            throw new \Exception('Rank purchase is disabled');
        }

        $promotion = RankPromotion::create([
            'user_id' => $user->id,
            'from_rank_id' => $user->current_rank_id,
            'to_rank_id' => $targetRank->id,
            'promotion_type' => 'purchase',
            'status' => $settings->purchase_requires_approval ? 'pending' : 'approved',
            'purchase_amount' => $amount,
            'payment_method' => $paymentInfo['method'] ?? null,
            'transaction_id' => $paymentInfo['transaction_id'] ?? null,
        ]);

        if ($settings->purchase_requires_approval) {
            $this->notifyAdminsForApproval($promotion);
        } else {
            $promotion->activate();
            $this->applyRankBonuses($user, $targetRank);
            $this->notifyUserPromotion($user, $targetRank);
        }

        return $promotion;
    }

    /**
     * Apply rank bonuses to user
     */
    private function applyRankBonuses(User $user, Rank $rank): void
    {
        $bonuses = $rank->activeBonuses()->where('auto_apply', true)->get();

        foreach ($bonuses as $bonus) {
            try {
                $bonus->applyToUser($user);
                Log::info("Applied bonus {$bonus->id} to user {$user->id}");
            } catch (\Exception $e) {
                Log::error("Failed to apply bonus: " . $e->getMessage());
            }
        }
    }

    /**
     * Notify user about promotion
     */
    private function notifyUserPromotion(User $user, Rank $rank): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'rank_promotion',
            'title' => __('Congratulations! Rank Promotion'),
            'title_th' => 'ยินดีด้วย! คุณได้รับการเลื่อนระดับ',
            'message' => __('You have been promoted to :rank', ['rank' => $rank->name]),
            'message_th' => "คุณได้เลื่อนขั้นเป็น {$rank->name_th}",
            'data' => json_encode([
                'rank_id' => $rank->id,
                'rank_name' => $rank->name,
                'rank_level' => $rank->level,
            ]),
        ]);
    }

    /**
     * Notify admins for approval
     */
    private function notifyAdminsForApproval(RankPromotion $promotion): void
    {
        $settings = RankSetting::get();
        $adminIds = $settings->approval_admins ?? [];

        if (empty($adminIds)) {
            // Notify all super admins
            $admins = User::where('is_super_admin', true)->get();
        } else {
            $admins = User::whereIn('id', $adminIds)->get();
        }

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'rank_approval_needed',
                'title' => __('Rank Promotion Approval Required'),
                'title_th' => 'ต้องการอนุมัติการเลื่อนระดับ',
                'message' => __('User :name requests promotion to :rank', [
                    'name' => $promotion->user->name,
                    'rank' => $promotion->toRank->name
                ]),
                'message_th' => "{$promotion->user->name} ขอเลื่อนระดับเป็น {$promotion->toRank->name_th}",
                'data' => json_encode([
                    'promotion_id' => $promotion->id,
                    'user_id' => $promotion->user_id,
                ]),
            ]);
        }
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(int $limit = 100): array
    {
        return User::with(['currentRank', 'affiliate'])
            ->whereNotNull('current_rank_id')
            ->orderByDesc('rank_points')
            ->orderBy('rank_updated_at')
            ->limit($limit)
            ->get()
            ->map(function($user, $index) {
                return [
                    'rank_position' => $index + 1,
                    'user' => $user,
                    'rank' => $user->currentRank,
                    'points' => $user->rank_points,
                    'earnings' => $user->affiliate->total_earnings ?? 0,
                ];
            })
            ->toArray();
    }
}
