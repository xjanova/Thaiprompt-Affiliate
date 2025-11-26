<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmBinaryService - จัดการการคำนวณ Binary Commission
 *
 * ⚠️ IMPORTANT: Service นี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 */
class MlmBinaryService
{
    /**
     * Calculate binary commissions (Pair matching)
     */
    public function calculateBinaryCommissions(MlmMember $member, Order $order, array $pvData)
    {
        $plan = $member->plan;

        if (!$plan || $plan->type === 'unilevel') {
            return;
        }

        // Attribute PV to the binary leg
        $this->attributePvToBinaryLeg($member, $pvData['total_pv']);

        // Calculate pair commissions for upline
        $this->calculateBinaryPairCommissions($member, $order, $pvData);
    }

    /**
     * Attribute PV to binary leg
     *
     * แก้ Bug #2: ลบการ increment total_pv ออก เพราะ MlmCalculationService ทำหน้าที่นี้แล้ว
     * เก็บเฉพาะการ traverse up binary tree เพื่ออัพเดท left_leg_pv / right_leg_pv
     */
    protected function attributePvToBinaryLeg(MlmMember $member, $pvAmount)
    {
        // Note: ไม่ต้อง increment total_pv ที่นี่แล้ว
        // เพราะ MlmCalculationService::processOrder() increment ไว้แล้ว (ป้องกันการนับซ้ำ)

        // Traverse up the binary tree and add to respective leg
        $currentMember = $member;

        while ($currentMember->binaryParent) {
            $parent = $currentMember->binaryParent;
            $position = $currentMember->binary_position;

            if ($position === 'left') {
                $parent->increment('left_leg_pv', $pvAmount);
            } else {
                $parent->increment('right_leg_pv', $pvAmount);
            }

            $currentMember = $parent;
        }
    }

    /**
     * Calculate binary pair commissions
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     */
    protected function calculateBinaryPairCommissions(MlmMember $member, Order $order, array $pvData)
    {
        $plan = $member->plan;

        // ดึงค่าจาก Global Settings
        $maxPairsPerDay = MlmGlobalSetting::get('binary_max_pairs_per_day', null);
        $commissionPerPair = MlmGlobalSetting::get('binary_pair_commission', 100);
        $maxCommissionPerDay = MlmGlobalSetting::get('binary_max_commission_per_day', null);
        $flushPercentage = MlmGlobalSetting::get('binary_flush_percentage', 100) / 100;

        // Traverse up the binary tree
        $currentMember = $member;

        while ($currentMember->binaryParent) {
            $parent = $currentMember->binaryParent;

            // แก้ Bug #3: ตรวจสอบและลบ carried PV ที่หมดอายุก่อนคำนวณ
            $parent->expireCarriedPv();

            // Check if parent is qualified
            if (!$parent->is_qualified || $parent->status !== 'active') {
                $currentMember = $parent;
                continue;
            }

            // Calculate pairs
            $leftPv = $parent->left_leg_pv + $parent->carried_left_pv;
            $rightPv = $parent->right_leg_pv + $parent->carried_right_pv;

            $weakerLeg = min($leftPv, $rightPv);
            $strongerLeg = max($leftPv, $rightPv);

            // Calculate how many pairs can be formed
            $pairsAvailable = floor($weakerLeg / $this->getPairRatio());

            if ($pairsAvailable > 0) {
                // Check daily limits
                $todayPairs = $this->getTodayPairsCount($parent);

                if ($maxPairsPerDay && $todayPairs >= $maxPairsPerDay) {
                    $currentMember = $parent;
                    continue;
                }

                $pairsToProcess = $pairsAvailable;
                if ($maxPairsPerDay) {
                    $pairsToProcess = min($pairsToProcess, $maxPairsPerDay - $todayPairs);
                }

                // Calculate commission
                $totalCommission = $pairsToProcess * $commissionPerPair;

                // Check daily commission limit
                $todayCommission = $this->getTodayCommissionAmount($parent);

                if ($maxCommissionPerDay && ($todayCommission + $totalCommission) > $maxCommissionPerDay) {
                    $totalCommission = $maxCommissionPerDay - $todayCommission;
                    $pairsToProcess = floor($totalCommission / $commissionPerPair);
                }

                if ($pairsToProcess > 0 && $totalCommission > 0) {
                    // Create commission record
                    MlmCommission::create([
                        'mlm_member_id' => $parent->id,
                        'mlm_plan_id' => $plan->id,
                        'user_id' => $parent->user_id,
                        'from_member_id' => $member->id,
                        'source_type' => 'App\\Models\\Order',
                        'source_id' => $order->id,
                        'type' => 'binary_pair',
                        'left_leg_pv' => $leftPv,
                        'right_leg_pv' => $rightPv,
                        'pairs_count' => $pairsToProcess,
                        'pv_amount' => $pairsToProcess * $this->getPairRatio(),
                        'sales_amount' => $order->total,
                        'commission_amount' => $totalCommission,
                        'status' => 'pending',
                    ]);

                    // Flush PV based on flush percentage
                    $pvFlushed = $pairsToProcess * $this->getPairRatio() * $flushPercentage;

                    // Update carried PV
                    if ($leftPv <= $rightPv) {
                        // Left is weaker
                        $parent->decrement('carried_left_pv', min($parent->carried_left_pv, $pvFlushed));
                    } else {
                        // Right is weaker
                        $parent->decrement('carried_right_pv', min($parent->carried_right_pv, $pvFlushed));
                    }

                    // แก้ Bug #3: Carry forward remaining PV พร้อม set expiry date
                    $remainingWeakPv = $weakerLeg - ($pairsToProcess * $this->getPairRatio());

                    if ($remainingWeakPv > 0) {
                        $leg = ($leftPv <= $rightPv) ? 'left' : 'right';
                        $parent->setCarriedPvExpiry($leg, $remainingWeakPv);
                    }
                }
            }

            $currentMember = $parent;
        }
    }

    /**
     * Get pair ratio based on pairing type
     * ใช้ค่าจาก Global Settings
     */
    protected function getPairRatio()
    {
        // 1:1 means 1 PV left + 1 PV right = 1 pair
        // 2:1 means 2 PV left + 1 PV right = 1 pair (or vice versa)
        $pairingType = MlmGlobalSetting::get('binary_pairing_type', '1:1');
        return $pairingType === '2:1' ? 2 : 1;
    }

    /**
     * Get today's pairs count
     */
    protected function getTodayPairsCount(MlmMember $member)
    {
        return MlmCommission::where('mlm_member_id', $member->id)
            ->where('type', 'binary_pair')
            ->whereDate('created_at', today())
            ->sum('pairs_count');
    }

    /**
     * Get today's commission amount
     */
    protected function getTodayCommissionAmount(MlmMember $member)
    {
        return MlmCommission::where('mlm_member_id', $member->id)
            ->where('type', 'binary_pair')
            ->whereDate('created_at', today())
            ->sum('commission_amount');
    }

    /**
     * Find placement position for new member (auto-placement)
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     *
     * รองรับ:
     * - binary_max_depth: ความลึกสูงสุดของ Binary Tree
     * - binary_max_width: จำนวนลูกสูงสุดต่อ node (2 = Binary, 3 = Ternary)
     */
    public function findPlacementPosition(MlmMember $sponsor, $preferredLeg = null)
    {
        $autoPlacementEnabled = MlmGlobalSetting::get('auto_placement_enabled', true);

        if (!$autoPlacementEnabled) {
            return ['parent_id' => $sponsor->id, 'position' => $preferredLeg ?? 'left'];
        }

        // ดึงค่า depth/width limits จาก Global Settings
        $maxDepth = MlmGlobalSetting::get('binary_max_depth', null);
        $maxWidth = MlmGlobalSetting::get('binary_max_width', 2);

        $placementType = MlmGlobalSetting::get('auto_placement_strategy', 'balanced');

        return match ($placementType) {
            'balanced' => $this->findBalancedPlacement($sponsor, $maxDepth, $maxWidth),
            'weak_leg' => $this->findWeakLegPlacement($sponsor, $maxDepth, $maxWidth),
            'fill_by_level', 'fill_level' => $this->findFillByLevelPlacement($sponsor, $maxDepth, $maxWidth),
            'left_first' => $this->findLeftToRightPlacement($sponsor, $maxDepth, $maxWidth),
            'right_first' => $this->findRightToLeftPlacement($sponsor, $maxDepth, $maxWidth),
            'strong_leg' => $this->findStrongLegPlacement($sponsor, $maxDepth, $maxWidth),
            'manual' => ['parent_id' => $sponsor->id, 'position' => $preferredLeg ?? 'left'],
            default => $this->findBalancedPlacement($sponsor, $maxDepth, $maxWidth),
        };
    }

    /**
     * นับ depth ของ member จาก root
     *
     * @param MlmMember $member
     * @return int
     */
    protected function getMemberDepth(MlmMember $member): int
    {
        // นับจาก binary_path หรือ traverse up
        if ($member->binary_path) {
            // นับจำนวน '/' ใน path (ยกเว้นตัวแรก)
            return substr_count($member->binary_path, '/');
        }

        // Fallback: traverse up the tree
        $depth = 0;
        $current = $member;
        while ($current->binaryParent) {
            $depth++;
            $current = $current->binaryParent;
            if ($depth > 100) break; // Safety limit
        }
        return $depth;
    }

    /**
     * นับจำนวน children ของ node
     *
     * @param MlmMember $member
     * @return int
     */
    protected function getChildrenCount(MlmMember $member): int
    {
        $count = 0;
        if ($member->binaryLeftChild) $count++;
        if ($member->binaryRightChild) $count++;
        return $count;
    }

    /**
     * ตรวจสอบว่าสามารถวาง child ที่ node นี้ได้หรือไม่
     *
     * @param MlmMember $parent
     * @param int|null $maxDepth
     * @param int $maxWidth
     * @return bool
     */
    protected function canPlaceChild(MlmMember $parent, ?int $maxDepth, int $maxWidth): bool
    {
        // ตรวจสอบ width limit
        if ($this->getChildrenCount($parent) >= $maxWidth) {
            return false;
        }

        // ตรวจสอบ depth limit (ถ้ามี)
        if ($maxDepth !== null) {
            $parentDepth = $this->getMemberDepth($parent);
            if ($parentDepth >= $maxDepth) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find right-to-left placement
     * รองรับ depth/width limits
     */
    protected function findRightToLeftPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ตรวจสอบว่าสามารถวางที่ node นี้ได้หรือไม่
        if (!$this->canPlaceChild($member, $maxDepth, $maxWidth)) {
            return null;
        }

        // Check if right is available (ถ้า maxWidth > 1)
        if ($maxWidth >= 2 && !$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Check if left is available
        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        // Recursively check right subtree first
        if ($member->binaryRightChild) {
            $rightPlacement = $this->findRightToLeftPlacement($member->binaryRightChild, $maxDepth, $maxWidth);
            if ($rightPlacement) {
                return $rightPlacement;
            }
        }

        // Then check left subtree
        if ($member->binaryLeftChild) {
            return $this->findRightToLeftPlacement($member->binaryLeftChild, $maxDepth, $maxWidth);
        }

        return null;
    }

    /**
     * Find strong leg placement
     * รองรับ depth/width limits
     */
    protected function findStrongLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ตรวจสอบว่าสามารถวางที่ node นี้ได้หรือไม่
        if (!$this->canPlaceChild($member, $maxDepth, $maxWidth)) {
            return null;
        }

        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if ($maxWidth >= 2 && !$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Find the leg with more PV (strong leg)
        if ($member->left_leg_pv >= $member->right_leg_pv) {
            return $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth);
        }

        return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth);
    }

    /**
     * Find left-to-right placement
     * รองรับ depth/width limits
     */
    protected function findLeftToRightPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ตรวจสอบว่าสามารถวางที่ node นี้ได้หรือไม่
        if (!$this->canPlaceChild($member, $maxDepth, $maxWidth)) {
            return null;
        }

        // Check if left is available
        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        // Check if right is available (ถ้า maxWidth > 1)
        if ($maxWidth >= 2 && !$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Recursively check left subtree first
        if ($member->binaryLeftChild) {
            $leftPlacement = $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth);
            if ($leftPlacement) {
                return $leftPlacement;
            }
        }

        // Then check right subtree
        if ($member->binaryRightChild) {
            return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth);
        }

        return null;
    }

    /**
     * Find balanced placement
     * รองรับ depth/width limits
     */
    protected function findBalancedPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ตรวจสอบว่าสามารถวางที่ node นี้ได้หรือไม่
        if (!$this->canPlaceChild($member, $maxDepth, $maxWidth)) {
            return null;
        }

        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if ($maxWidth >= 2 && !$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Find the leg with fewer members
        if ($member->left_leg_members <= $member->right_leg_members) {
            return $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth);
        }

        return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth);
    }

    /**
     * Find weak leg placement
     * รองรับ depth/width limits
     */
    protected function findWeakLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ตรวจสอบว่าสามารถวางที่ node นี้ได้หรือไม่
        if (!$this->canPlaceChild($member, $maxDepth, $maxWidth)) {
            return null;
        }

        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if ($maxWidth >= 2 && !$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Find the leg with less PV
        if ($member->left_leg_pv <= $member->right_leg_pv) {
            return $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth);
        }

        return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth);
    }

    /**
     * Find fill-by-level placement (BFS - เติมให้เต็มชั้น)
     *
     * เติมสมาชิกใหม่ในแต่ละชั้นให้เต็มก่อน แล้วค่อยไปชั้นถัดไป
     * ใช้ Breadth-First Search (BFS) algorithm
     *
     * รองรับ depth/width limits:
     * - binary_max_depth: ความลึกสูงสุดของ Binary Tree
     * - binary_max_width: จำนวนลูกสูงสุดต่อ node (2 = Binary, 3 = Ternary)
     *
     * @param MlmMember $member จุดเริ่มต้นของ tree
     * @param int|null $maxDepth ความลึกสูงสุด (null = ไม่จำกัด)
     * @param int $maxWidth จำนวนลูกสูงสุดต่อ node
     * @return array|null
     */
    protected function findFillByLevelPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ใช้ Queue สำหรับ BFS - เก็บ [member, currentDepth]
        $queue = collect([['member' => $member, 'depth' => 0]]);
        $visited = collect();

        while ($queue->isNotEmpty()) {
            $item = $queue->shift();
            $current = $item['member'];
            $currentDepth = $item['depth'];

            // ข้าม node ที่เคย visit แล้ว (ป้องกัน infinite loop)
            if ($visited->contains($current->id)) {
                continue;
            }
            $visited->push($current->id);

            // ตรวจสอบ depth limit
            if ($maxDepth !== null && $currentDepth >= $maxDepth) {
                continue;
            }

            // ตรวจสอบว่า left ว่างไหม
            if (!$current->binaryLeftChild) {
                return ['parent_id' => $current->id, 'position' => 'left'];
            }

            // ตรวจสอบว่า right ว่างไหม (ถ้า maxWidth >= 2)
            if ($maxWidth >= 2 && !$current->binaryRightChild) {
                return ['parent_id' => $current->id, 'position' => 'right'];
            }

            // สำหรับ maxWidth > 2 (Ternary หรือมากกว่า)
            // ตรวจสอบ additional children positions
            if ($maxWidth > 2) {
                $childCount = $this->getChildrenCount($current);
                if ($childCount < $maxWidth) {
                    // กำหนด position ตามลำดับ
                    $positions = ['left', 'right', 'center', 'far_left', 'far_right'];
                    for ($i = 0; $i < $maxWidth && $i < count($positions); $i++) {
                        $pos = $positions[$i];
                        // ตรวจสอบว่า position นี้ว่างหรือไม่
                        $hasChild = MlmMember::where('binary_parent_id', $current->id)
                            ->where('binary_position', $pos)
                            ->exists();
                        if (!$hasChild) {
                            return ['parent_id' => $current->id, 'position' => $pos];
                        }
                    }
                }
            }

            // เพิ่ม children เข้า queue (ไปชั้นถัดไป)
            if ($current->binaryLeftChild) {
                $queue->push(['member' => $current->binaryLeftChild, 'depth' => $currentDepth + 1]);
            }
            if ($current->binaryRightChild) {
                $queue->push(['member' => $current->binaryRightChild, 'depth' => $currentDepth + 1]);
            }
        }

        // Fallback: ถ้าไม่พบตำแหน่งว่าง (อาจเกิน depth limit)
        Log::warning('Binary tree placement failed - tree is full or depth limit reached', [
            'sponsor_id' => $member->id,
            'max_depth' => $maxDepth,
            'max_width' => $maxWidth,
        ]);

        return null;
    }

    /**
     * Get binary tree structure
     */
    public function getBinaryTree(MlmMember $member, int $maxDepth = 5)
    {
        return $this->buildBinaryTreeRecursive($member, 0, $maxDepth);
    }

    /**
     * Build binary tree recursively
     */
    protected function buildBinaryTreeRecursive(MlmMember $member, int $currentDepth, int $maxDepth)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        // Get retention status from commission service
        $commissionService = app(MlmCommissionService::class);
        $retentionData = $commissionService->getMemberRetentionStatus($member);

        $node = [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $member->user->name,
            'member_code' => $member->member_code,
            'position' => $member->binary_position,
            'depth' => $currentDepth,
            'total_pv' => $member->total_pv,
            'monthly_pv' => $retentionData['monthly_pv'],
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            'status' => $member->status,
            'retention_status' => $retentionData['status'],
            'direct_referrals' => $member->total_direct_referrals ?? 0,
            'left' => null,
            'right' => null,
        ];

        // Load children
        $leftChild = $member->binaryLeftChild()->with('user')->first();
        $rightChild = $member->binaryRightChild()->with('user')->first();

        if ($leftChild) {
            $node['left'] = $this->buildBinaryTreeRecursive($leftChild, $currentDepth + 1, $maxDepth);
        }

        if ($rightChild) {
            $node['right'] = $this->buildBinaryTreeRecursive($rightChild, $currentDepth + 1, $maxDepth);
        }

        return $node;
    }
}
