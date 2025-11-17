<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     */
    protected function calculateBinaryPairCommissions(MlmMember $member, Order $order, array $pvData)
    {
        $plan = $member->plan;

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
            $pairsAvailable = floor($weakerLeg / $this->getPairRatio($plan));

            if ($pairsAvailable > 0) {
                // Check daily limits
                $todayPairs = $this->getTodayPairsCount($parent);
                $maxPairsPerDay = $plan->binary_max_pairs_per_day;

                if ($maxPairsPerDay && $todayPairs >= $maxPairsPerDay) {
                    $currentMember = $parent;
                    continue;
                }

                $pairsToProcess = $pairsAvailable;
                if ($maxPairsPerDay) {
                    $pairsToProcess = min($pairsToProcess, $maxPairsPerDay - $todayPairs);
                }

                // Calculate commission
                $commissionPerPair = $plan->binary_pair_commission;
                $totalCommission = $pairsToProcess * $commissionPerPair;

                // Check daily commission limit
                $todayCommission = $this->getTodayCommissionAmount($parent);
                $maxCommissionPerDay = $plan->binary_max_commission_per_day;

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
                        'pv_amount' => $pairsToProcess * $this->getPairRatio($plan),
                        'sales_amount' => $order->total,
                        'commission_amount' => $totalCommission,
                        'status' => 'pending',
                    ]);

                    // Flush PV based on flush percentage
                    $flushPercentage = $plan->binary_flush_percentage / 100;
                    $pvFlushed = $pairsToProcess * $this->getPairRatio($plan) * $flushPercentage;

                    // Update carried PV
                    if ($leftPv <= $rightPv) {
                        // Left is weaker
                        $parent->decrement('carried_left_pv', min($parent->carried_left_pv, $pvFlushed));
                    } else {
                        // Right is weaker
                        $parent->decrement('carried_right_pv', min($parent->carried_right_pv, $pvFlushed));
                    }

                    // แก้ Bug #3: Carry forward remaining PV พร้อม set expiry date
                    $remainingWeakPv = $weakerLeg - ($pairsToProcess * $this->getPairRatio($plan));

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
     */
    protected function getPairRatio($plan)
    {
        // 1:1 means 1 PV left + 1 PV right = 1 pair
        // 2:1 means 2 PV left + 1 PV right = 1 pair (or vice versa)
        return $plan->binary_pairing_type === '2:1' ? 2 : 1;
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
     */
    public function findPlacementPosition(MlmMember $sponsor, $preferredLeg = null)
    {
        $plan = $sponsor->plan;

        if (!$plan->auto_placement) {
            return ['parent_id' => $sponsor->id, 'position' => $preferredLeg ?? 'left'];
        }

        $placementType = $plan->auto_placement_type;

        return match ($placementType) {
            'balanced' => $this->findBalancedPlacement($sponsor),
            'weak_leg' => $this->findWeakLegPlacement($sponsor),
            'fill_by_level' => $this->findFillByLevelPlacement($sponsor),
            default => $this->findLeftToRightPlacement($sponsor),
        };
    }

    /**
     * Find left-to-right placement
     */
    protected function findLeftToRightPlacement(MlmMember $member)
    {
        // Check if left is available
        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        // Check if right is available
        if (!$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Recursively check left subtree first
        $leftPlacement = $this->findLeftToRightPlacement($member->binaryLeftChild);
        if ($leftPlacement) {
            return $leftPlacement;
        }

        // Then check right subtree
        return $this->findLeftToRightPlacement($member->binaryRightChild);
    }

    /**
     * Find balanced placement
     */
    protected function findBalancedPlacement(MlmMember $member)
    {
        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if (!$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Find the leg with fewer members
        if ($member->left_leg_members <= $member->right_leg_members) {
            return $this->findLeftToRightPlacement($member->binaryLeftChild);
        }

        return $this->findLeftToRightPlacement($member->binaryRightChild);
    }

    /**
     * Find weak leg placement
     */
    protected function findWeakLegPlacement(MlmMember $member)
    {
        if (!$member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if (!$member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        // Find the leg with less PV
        if ($member->left_leg_pv <= $member->right_leg_pv) {
            return $this->findLeftToRightPlacement($member->binaryLeftChild);
        }

        return $this->findLeftToRightPlacement($member->binaryRightChild);
    }

    /**
     * Find fill-by-level placement (BFS - เติมให้เต็มชั้น)
     *
     * เติมสมาชิกใหม่ในแต่ละชั้นให้เต็มก่อน แล้วค่อยไปชั้นถัดไป
     * ใช้ Breadth-First Search (BFS) algorithm
     */
    protected function findFillByLevelPlacement(MlmMember $member)
    {
        // ใช้ Queue สำหรับ BFS
        $queue = collect([$member]);
        $visited = collect();

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();

            // ข้าม node ที่เคย visit แล้ว (ป้องกัน infinite loop)
            if ($visited->contains($current->id)) {
                continue;
            }
            $visited->push($current->id);

            // ตรวจสอบว่า left ว่างไหม
            if (!$current->binaryLeftChild) {
                return ['parent_id' => $current->id, 'position' => 'left'];
            }

            // ตรวจสอบว่า right ว่างไหม
            if (!$current->binaryRightChild) {
                return ['parent_id' => $current->id, 'position' => 'right'];
            }

            // เพิ่ม children เข้า queue (ไปชั้นถัดไป)
            if ($current->binaryLeftChild) {
                $queue->push($current->binaryLeftChild);
            }
            if ($current->binaryRightChild) {
                $queue->push($current->binaryRightChild);
            }
        }

        // Fallback (ไม่ควรเกิด แต่เผื่อ edge case)
        return ['parent_id' => $member->id, 'position' => 'left'];
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
