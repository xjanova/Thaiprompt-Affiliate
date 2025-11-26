<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmUnilevelService - จัดการการคำนวณ Unilevel Commission
 *
 * ⚠️ IMPORTANT: Service นี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 */
class MlmUnilevelService
{
    /**
     * Calculate unilevel commissions
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function calculateUnilevelCommissions(MlmMember $member, Order $order, array $pvData)
    {
        // ใช้ Global Settings แทน per-plan settings
        $levels = MlmGlobalSetting::get('unilevel_levels', []);
        $maxDepth = MlmGlobalSetting::get('unilevel_max_depth', 10);
        $compressionEnabled = MlmGlobalSetting::get('unilevel_compression_enabled', true);
        $maxPerLevel = MlmGlobalSetting::get('unilevel_max_commission_per_level', null);

        if (empty($levels)) {
            return;
        }

        $plan = $member->plan;
        $currentMember = $member;
        $currentLevel = 0;

        // Traverse up the unilevel tree
        while ($currentMember && $currentLevel < $maxDepth && $currentLevel < count($levels)) {
            $sponsor = $currentMember->unilevelSponsor;

            if (!$sponsor) {
                break;
            }

            // Check if sponsor is qualified
            if (!$this->isQualifiedForCommission($sponsor, $currentLevel + 1)) {
                // Compression: skip inactive members if enabled
                if (!$compressionEnabled) {
                    $currentLevel++;
                }
                $currentMember = $sponsor;
                continue;
            }

            $levelConfig = $levels[$currentLevel];
            $percentage = $levelConfig['percentage'] ?? 0;

            if ($percentage > 0) {
                $commissionAmount = ($pvData['total_pv'] * $percentage) / 100;

                // Check rank multiplier if user has rank
                if ($sponsor->user->current_rank_id) {
                    $rank = $sponsor->user->rank;
                    if ($rank && $rank->bonus_multiplier) {
                        $commissionAmount *= $rank->bonus_multiplier;
                    }
                }

                // Apply max per level constraint
                if ($maxPerLevel && $commissionAmount > $maxPerLevel) {
                    $commissionAmount = $maxPerLevel;
                }

                // Create commission record
                MlmCommission::create([
                    'mlm_member_id' => $sponsor->id,
                    'mlm_plan_id' => $plan->id,
                    'user_id' => $sponsor->user_id,
                    'from_member_id' => $member->id,
                    'source_type' => 'App\\Models\\Order',
                    'source_id' => $order->id,
                    'type' => $currentLevel === 0 ? 'unilevel_direct' : 'unilevel_indirect',
                    'level' => $currentLevel + 1,
                    'pv_amount' => $pvData['total_pv'],
                    'sales_amount' => $order->total,
                    'commission_amount' => $commissionAmount,
                    'percentage' => $percentage,
                    'status' => 'pending',
                ]);

                // Update sponsor's team PV
                $sponsor->increment('total_team_pv', $pvData['total_pv']);
            }

            $currentLevel++;
            $currentMember = $sponsor;
        }
    }

    /**
     * Check if member is qualified for commission at a level
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    protected function isQualifiedForCommission(MlmMember $member, int $level)
    {
        // Check basic qualification
        if (!$member->is_qualified || $member->status !== 'active') {
            return false;
        }

        // Note: rank requirements ไม่ใช้ per-plan settings แล้ว
        // ถ้าต้องการ rank requirements ให้ใช้ Global Settings แทน

        return true;
    }

    /**
     * Get unilevel downline tree
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function getUnilevelTree(MlmMember $member, int $maxDepth = null)
    {
        $maxDepth = $maxDepth ?? MlmGlobalSetting::get('unilevel_max_depth', 10);

        return $this->buildUnilevelTreeRecursive($member, 0, $maxDepth);
    }

    /**
     * Build unilevel tree recursively
     */
    protected function buildUnilevelTreeRecursive(MlmMember $member, int $currentDepth, int $maxDepth)
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $children = $member->unilevelChildren()
            ->with(['user', 'plan'])
            ->get();

        $tree = [];

        foreach ($children as $child) {
            $tree[] = [
                'id' => $child->id,
                'user_id' => $child->user_id,
                'name' => $child->user->name,
                'member_code' => $child->member_code,
                'level' => $currentDepth + 1,
                'total_pv' => $child->total_pv,
                'total_team_pv' => $child->total_team_pv,
                'total_earnings' => $child->total_earnings,
                'direct_referrals' => $child->total_direct_referrals,
                'status' => $child->status,
                'children' => $this->buildUnilevelTreeRecursive($child, $currentDepth + 1, $maxDepth),
            ];
        }

        return $tree;
    }

    /**
     * Get unilevel statistics by level
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function getUnilevelStatsByLevel(MlmMember $member)
    {
        $maxDepth = MlmGlobalSetting::get('unilevel_max_depth', 10);
        $plan = $member->plan;

        $stats = [];

        for ($level = 1; $level <= $maxDepth; $level++) {
            $members = MlmMember::where('mlm_plan_id', $plan->id)
                ->where('unilevel_level', $level)
                ->where('unilevel_path', 'like', $member->id . '/%')
                ->get();

            $stats[$level] = [
                'count' => $members->count(),
                'total_pv' => $members->sum('total_pv'),
                'active_count' => $members->where('status', 'active')->count(),
            ];
        }

        return $stats;
    }

    /**
     * Calculate potential commission for preview
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    public function calculatePotentialCommission(MlmMember $member, $pvAmount)
    {
        $levels = MlmGlobalSetting::get('unilevel_levels', []);

        if (empty($levels) || !isset($levels[0])) {
            return 0;
        }

        $percentage = $levels[0]['percentage'] ?? 0;
        $commission = ($pvAmount * $percentage) / 100;

        // Apply rank multiplier if user has rank
        if ($member->user->current_rank_id) {
            $rank = $member->user->rank;
            if ($rank && $rank->bonus_multiplier) {
                $commission *= $rank->bonus_multiplier;
            }
        }

        return $commission;
    }
}
