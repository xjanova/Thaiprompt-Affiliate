<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmGenealogy;
use App\Models\MlmGlobalSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MlmGenealogyService
{
    /**
     * Register a new member in the MLM system
     */
    public function registerMember(User $user, $planId, $unilevelSponsorId, $binarySponsorId = null, $binaryPosition = null)
    {
        DB::beginTransaction();

        try {
            $sponsor = MlmMember::find($unilevelSponsorId);
            $binarySponsor = $binarySponsorId ? MlmMember::find($binarySponsorId) : $sponsor;

            if (!$sponsor) {
                throw new \Exception('Sponsor not found');
            }

            $plan = $sponsor->plan;

            // Create member
            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $planId,
                'unilevel_sponsor_id' => $unilevelSponsorId,
                'unilevel_level' => $sponsor->unilevel_level + 1,
                'unilevel_path' => $sponsor->unilevel_path . '/' . $sponsor->id,
                'binary_sponsor_id' => $binarySponsor->id,
                'member_code' => MlmMember::generateMemberCode(),
                'joined_at' => now(),
                'status' => 'active',
            ]);

            // Build unilevel genealogy
            $this->buildUnilevelGenealogy($member);

            // Handle binary placement
            if ($plan->type === 'binary' || $plan->type === 'hybrid') {
                $this->placeBinaryMember($member, $binarySponsor, $binaryPosition);
            }

            // Update sponsor's referral count
            $sponsor->increment('total_direct_referrals');

            // Update all upline team counts
            $this->updateUplineTeamCounts($member);

            DB::commit();

            return $member;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Build unilevel genealogy records
     */
    protected function buildUnilevelGenealogy(MlmMember $member)
    {
        $currentAncestor = $member->unilevelSponsor;
        $depth = 1;

        while ($currentAncestor) {
            MlmGenealogy::create([
                'mlm_member_id' => $member->id,
                'ancestor_id' => $currentAncestor->id,
                'mlm_plan_id' => $member->mlm_plan_id,
                'tree_type' => 'unilevel',
                'depth' => $depth,
                'path' => $member->unilevel_path,
            ]);

            $currentAncestor = $currentAncestor->unilevelSponsor;
            $depth++;

            if ($depth > 50) {
                break; // Safety limit
            }
        }
    }

    /**
     * Place member in binary tree
     *
     * ใช้ algorithm ตาม auto_placement_strategy ที่ตั้งค่าไว้:
     * - fill_level: เรียงเต็มชั้นก่อนไปชั้นถัดไป (BFS)
     * - balanced: วางในขาที่มีสมาชิกน้อยกว่า
     * - weak_leg: วางในขาที่มี PV น้อยกว่า
     * - left_first: วางซ้ายก่อนขวา
     * - right_first: วางขวาก่อนซ้าย
     *
     * รองรับ depth/width limits จาก Global Settings:
     * - binary_max_depth: ความลึกสูงสุด
     * - binary_max_width: จำนวนลูกสูงสุดต่อ node
     */
    protected function placeBinaryMember(MlmMember $member, MlmMember $sponsor, $preferredPosition = null)
    {
        $binaryService = new MlmBinaryService();
        $placement = $binaryService->findPlacementPosition($sponsor, $preferredPosition);

        // ตรวจสอบว่าหาตำแหน่งได้หรือไม่
        if (!$placement || !isset($placement['parent_id'])) {
            Log::warning('Binary placement failed - no position found', [
                'member_id' => $member->id,
                'sponsor_id' => $sponsor->id,
                'max_depth' => MlmGlobalSetting::get('binary_max_depth'),
                'max_width' => MlmGlobalSetting::get('binary_max_width', 2),
            ]);
            return $member;
        }

        $parent = MlmMember::find($placement['parent_id']);

        if (!$parent) {
            Log::warning('Binary placement failed - parent not found', [
                'member_id' => $member->id,
                'parent_id' => $placement['parent_id'],
            ]);
            return $member;
        }

        $position = $placement['position'];

        // Update member
        $member->update([
            'binary_parent_id' => $parent->id,
            'binary_position' => $position,
            'binary_path' => ($parent->binary_path ?? '') . '/' . $position,
        ]);

        // Build binary genealogy
        $this->buildBinaryGenealogy($member);

        // Update parent's leg count
        if ($position === 'left') {
            $parent->increment('left_leg_members');
        } else {
            $parent->increment('right_leg_members');
        }

        Log::info('Binary placement successful', [
            'member_id' => $member->id,
            'parent_id' => $parent->id,
            'position' => $position,
            'binary_path' => $member->binary_path,
        ]);

        return $member;
    }

    /**
     * Build binary genealogy records
     */
    protected function buildBinaryGenealogy(MlmMember $member)
    {
        $currentAncestor = $member->binaryParent;
        $depth = 1;
        $leg = $member->binary_position;

        while ($currentAncestor) {
            MlmGenealogy::create([
                'mlm_member_id' => $member->id,
                'ancestor_id' => $currentAncestor->id,
                'mlm_plan_id' => $member->mlm_plan_id,
                'tree_type' => 'binary',
                'depth' => $depth,
                'leg' => $leg,
                'path' => $member->binary_path,
            ]);

            // Continue up with parent's position
            $leg = $currentAncestor->binary_position;
            $currentAncestor = $currentAncestor->binaryParent;
            $depth++;

            if ($depth > 50) {
                break; // Safety limit
            }
        }
    }

    /**
     * Update upline team counts
     */
    protected function updateUplineTeamCounts(MlmMember $member)
    {
        // Update unilevel upline
        $ancestors = MlmGenealogy::where('mlm_member_id', $member->id)
            ->where('tree_type', 'unilevel')
            ->get();

        foreach ($ancestors as $genealogy) {
            $ancestor = $genealogy->ancestor;
            $ancestor->increment('total_team_members');
        }
    }

    /**
     * Get genealogy tree data for visualization
     */
    public function getTreeData(MlmMember $member, $treeType = 'unilevel', $maxDepth = 5)
    {
        if ($treeType === 'binary') {
            $binaryService = new MlmBinaryService();
            return $binaryService->getBinaryTree($member, $maxDepth);
        }

        $unilevelService = new MlmUnilevelService();
        return $unilevelService->getUnilevelTree($member, $maxDepth);
    }

    /**
     * Get member's position in tree
     */
    public function getMemberPosition(MlmMember $member, $treeType = 'unilevel')
    {
        if ($treeType === 'binary') {
            return [
                'parent' => $member->binaryParent,
                'position' => $member->binary_position,
                'path' => $member->binary_path,
                'left_child' => $member->binaryLeftChild,
                'right_child' => $member->binaryRightChild,
                'depth' => substr_count($member->binary_path ?? '', '/'),
            ];
        }

        return [
            'sponsor' => $member->unilevelSponsor,
            'level' => $member->unilevel_level,
            'path' => $member->unilevel_path,
            'children' => $member->unilevelChildren,
        ];
    }

    /**
     * Search for a member in the tree
     */
    public function searchMemberInTree(MlmMember $root, $searchTerm, $treeType = 'unilevel')
    {
        $query = MlmGenealogy::where('ancestor_id', $root->id)
            ->where('tree_type', $treeType)
            ->whereHas('member.user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->with(['member.user'])
            ->get();

        return $query->map(function ($genealogy) {
            return [
                'member' => $genealogy->member,
                'depth' => $genealogy->depth,
                'path' => $genealogy->path,
            ];
        });
    }
}
