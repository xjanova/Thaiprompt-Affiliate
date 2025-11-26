<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmUnilevelService - จัดการการคำนวณ Unilevel Commission และ Placement
 *
 * ⚠️ IMPORTANT: Service นี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 *
 * รองรับ:
 * - unilevel_max_depth: ความลึกสูงสุด
 * - unilevel_max_width: จำนวนลูกตรงสูงสุดต่อสมาชิก
 * - unilevel_auto_spillover: ล้นอัตโนมัติเมื่อเกินความกว้าง
 */
class MlmUnilevelService
{
    /**
     * หาตำแหน่งสำหรับสมาชิกใหม่ใน Unilevel Tree
     *
     * ถ้า sponsor มีลูกตรงถึง max_width แล้ว จะหาตำแหน่งใน subtree
     * โดยใช้ BFS (เติมเต็มชั้นก่อนไปชั้นถัดไป)
     *
     * @param MlmMember $sponsor ผู้แนะนำ (sponsor)
     * @return array ['sponsor_id' => int, 'level' => int, 'path' => string]|null
     */
    public function findUnilevelPlacement(MlmMember $sponsor): ?array
    {
        $maxWidth = MlmGlobalSetting::get('unilevel_max_width', null);
        $maxDepth = MlmGlobalSetting::get('unilevel_max_depth', 10);
        $autoSpillover = MlmGlobalSetting::get('unilevel_auto_spillover', true);

        // ถ้าไม่จำกัด width หรือ sponsor ยังไม่เต็ม → วางเป็นลูกตรงของ sponsor
        if ($maxWidth === null || $this->getDirectChildrenCount($sponsor) < $maxWidth) {
            return $this->createPlacementResult($sponsor);
        }

        // ถ้าปิด auto spillover → คืนค่า null (ไม่สามารถวางได้)
        if (!$autoSpillover) {
            Log::warning('Unilevel placement failed - width limit reached and spillover disabled', [
                'sponsor_id' => $sponsor->id,
                'max_width' => $maxWidth,
            ]);
            return null;
        }

        // ใช้ BFS หาตำแหน่งในชั้นถัดไป
        return $this->findSpilloverPlacement($sponsor, $maxWidth, $maxDepth);
    }

    /**
     * หาตำแหน่ง spillover ด้วย BFS (เติมเต็มชั้นก่อนไปชั้นถัดไป)
     *
     * @param MlmMember $sponsor
     * @param int $maxWidth
     * @param int $maxDepth
     * @return array|null
     */
    protected function findSpilloverPlacement(MlmMember $sponsor, int $maxWidth, int $maxDepth): ?array
    {
        // ใช้ Queue สำหรับ BFS - เก็บ [member, currentDepth]
        $queue = collect([['member' => $sponsor, 'depth' => $sponsor->unilevel_level]]);
        $visited = collect();

        while ($queue->isNotEmpty()) {
            $item = $queue->shift();
            $current = $item['member'];
            $currentDepth = $item['depth'];

            // ข้าม node ที่เคย visit แล้ว
            if ($visited->contains($current->id)) {
                continue;
            }
            $visited->push($current->id);

            // ตรวจสอบ depth limit (depth ของลูกจะเป็น currentDepth + 1)
            if ($currentDepth >= $maxDepth) {
                continue;
            }

            // ตรวจสอบว่า node นี้ยังมีที่ว่างไหม
            $childCount = $this->getDirectChildrenCount($current);
            if ($childCount < $maxWidth) {
                return $this->createPlacementResult($current);
            }

            // เพิ่ม children เข้า queue (ไปชั้นถัดไป)
            $children = $current->unilevelChildren()->orderBy('id')->get();
            foreach ($children as $child) {
                $queue->push(['member' => $child, 'depth' => $currentDepth + 1]);
            }
        }

        // ไม่พบตำแหน่งว่าง (tree เต็มหรือเกิน depth limit)
        Log::warning('Unilevel spillover placement failed - tree is full or depth limit reached', [
            'sponsor_id' => $sponsor->id,
            'max_width' => $maxWidth,
            'max_depth' => $maxDepth,
        ]);

        return null;
    }

    /**
     * นับจำนวนลูกตรงของ member
     *
     * @param MlmMember $member
     * @return int
     */
    protected function getDirectChildrenCount(MlmMember $member): int
    {
        return MlmMember::where('unilevel_sponsor_id', $member->id)->count();
    }

    /**
     * สร้างผลลัพธ์ placement
     *
     * @param MlmMember $targetSponsor
     * @return array
     */
    protected function createPlacementResult(MlmMember $targetSponsor): array
    {
        return [
            'sponsor_id' => $targetSponsor->id,
            'level' => $targetSponsor->unilevel_level + 1,
            'path' => $targetSponsor->unilevel_path . '/' . $targetSponsor->id,
        ];
    }

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
     *
     * @param MlmMember $member สมาชิกที่ต้องการดูสายงาน
     * @param int|null $maxDepth ความลึกสูงสุด
     * @return array ข้อมูล node รวม root member พร้อม children
     */
    public function getUnilevelTree(MlmMember $member, int $maxDepth = null)
    {
        $maxDepth = $maxDepth ?? MlmGlobalSetting::get('unilevel_max_depth', 10);

        // Return root member พร้อม children (เหมือน Binary tree format)
        return $this->buildUnilevelNodeWithChildren($member, 0, $maxDepth);
    }

    /**
     * Build unilevel tree recursively - รวม root member พร้อม children
     *
     * @param MlmMember $member
     * @param int $currentDepth
     * @param int $maxDepth
     * @return array
     */
    protected function buildUnilevelNodeWithChildren(MlmMember $member, int $currentDepth, int $maxDepth)
    {
        // ดึง retention status
        $commissionService = app(MlmCommissionService::class);
        $retentionData = $commissionService->getMemberRetentionStatus($member);

        // สร้าง node ของ member นี้
        $node = [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $member->user->name ?? 'Unknown',
            'member_code' => $member->member_code,
            'level' => $currentDepth,
            'total_pv' => $member->total_pv,
            'monthly_pv' => $retentionData['monthly_pv'] ?? 0,
            'total_team_pv' => $member->total_team_pv,
            'total_earnings' => $member->total_earnings,
            'direct_referrals' => $member->total_direct_referrals,
            'total_team_members' => $member->total_team_members,
            'status' => $member->status,
            'retention_status' => $retentionData['status'] ?? 'active',
            'children' => [],
        ];

        // หยุด recursion เมื่อถึง max depth
        if ($currentDepth >= $maxDepth) {
            return $node;
        }

        // โหลด children
        $children = $member->unilevelChildren()
            ->with(['user', 'plan'])
            ->get();

        foreach ($children as $child) {
            $node['children'][] = $this->buildUnilevelNodeWithChildren($child, $currentDepth + 1, $maxDepth);
        }

        return $node;
    }

    /**
     * Build unilevel tree recursively (Legacy - แค่ children)
     * @deprecated ใช้ buildUnilevelNodeWithChildren แทน
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
                'name' => $child->user->name ?? 'Unknown',
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
