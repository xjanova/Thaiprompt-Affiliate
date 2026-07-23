<?php

namespace App\Services;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmGenealogy;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
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

            if (! $sponsor) {
                throw new \Exception('Sponsor not found');
            }

            $plan = $sponsor->plan;

            // Create member
            // แก้ Bug #8: เพิ่ม original_sponsor_id เพื่อติดตามผู้แนะนำเดิม
            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $planId,
                'original_sponsor_id' => $unilevelSponsorId,
                'unilevel_sponsor_id' => $unilevelSponsorId,
                'unilevel_level' => $sponsor->unilevel_level + 1,
                'unilevel_path' => $sponsor->unilevel_path.'/'.$sponsor->id,
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
     * Rebuild genealogy (closure table) ของ member + downline ทั้ง subtree
     *
     * ใช้หลังย้ายทีม (team transfer) — ลบ genealogy เดิมของแต่ละ node แล้วสร้างใหม่
     * ตาม pointer ปัจจุบัน (unilevel_sponsor_id / binary_parent_id)
     *
     * @param  MlmMember  $member  รากของ subtree ที่ย้าย
     * @param  int  $maxNodes  เพดานจำนวน node กัน runaway
     * @return int จำนวน node ที่ rebuild แล้ว
     */
    public function rebuildGenealogyForSubtree(MlmMember $member, int $maxNodes = 2000): int
    {
        $processed = 0;
        $queue = [$member->id];
        $visited = [];

        while (! empty($queue) && $processed < $maxNodes) {
            $currentId = array_shift($queue);

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            $node = MlmMember::find($currentId);
            if (! $node) {
                continue;
            }

            // ลบ genealogy เดิมของ node นี้ (ทั้ง unilevel และ binary)
            MlmGenealogy::where('mlm_member_id', $node->id)->delete();

            // สร้างใหม่ตาม pointer ปัจจุบัน (ล้าง relation cache ก่อน กันชี้ค่าเก่า)
            $node->unsetRelation('unilevelSponsor');
            $node->unsetRelation('binaryParent');

            if ($node->unilevel_sponsor_id) {
                $this->buildUnilevelGenealogy($node);
            }
            if ($node->binary_parent_id) {
                $this->buildBinaryGenealogy($node);
            }

            $processed++;

            // เดินต่อทั้งลูก unilevel และลูก binary (union กันตกหล่น)
            foreach (MlmMember::where('unilevel_sponsor_id', $node->id)->pluck('id') as $childId) {
                $queue[] = $childId;
            }
            foreach (MlmMember::where('binary_parent_id', $node->id)->pluck('id') as $childId) {
                $queue[] = $childId;
            }
        }

        return $processed;
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
        // 🐛 Fix 2026-07-24: ใช้ placeNewMember (ศูนย์กลาง — มี fallback BFS + retry กัน race
        // + อัพเดทสถิติ parent/upline ครบในตัว) แทนโค้ด placement เดิมที่ไม่มี fallback
        $binaryService = new MlmBinaryService;
        $result = $binaryService->placeNewMember($member, $sponsor, $preferredPosition);

        if (! $result) {
            Log::warning('Binary placement failed - no position found (หลัง fallback)', [
                'member_id' => $member->id,
                'sponsor_id' => $sponsor->id,
                'max_depth' => MlmGlobalSetting::get('binary_max_depth'),
                'max_width' => MlmGlobalSetting::get('binary_max_width', 2),
            ]);

            return $member;
        }

        // Build binary genealogy (closure table) หลังวางสำเร็จ
        $member->refresh();
        $this->buildBinaryGenealogy($member);

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
            $binaryService = new MlmBinaryService;

            return $binaryService->getBinaryTree($member, $maxDepth);
        }

        $unilevelService = new MlmUnilevelService;

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

    /**
     * ดึงข้อมูลสายเลือด (Bloodline) ของสมาชิก
     * แสดงเส้นทางจาก root (บริษัท/สมาชิกแรก) ลงมาถึงสมาชิกที่ระบุ
     *
     * @param  MlmMember  $member  สมาชิกที่ต้องการดูสายเลือด
     * @param  string  $treeType  ประเภทผัง ('unilevel' หรือ 'binary')
     * @return array ข้อมูลสายเลือดเป็น tree structure
     */
    public function getBloodlineData(MlmMember $member, $treeType = 'unilevel')
    {
        // ดึงรายชื่อ ancestors ทั้งหมดเรียงจาก root ลงมาถึงสมาชิก
        $ancestors = $this->getAncestorChain($member, $treeType);

        // สร้าง tree structure จากบนลงล่าง
        return $this->buildBloodlineTree($ancestors, $member, $treeType);
    }

    /**
     * ดึง ancestors chain จาก root ลงมาถึงสมาชิก
     */
    protected function getAncestorChain(MlmMember $member, $treeType)
    {
        $ancestors = [];

        if ($treeType === 'binary') {
            // เดินขึ้นไป binary parent
            $current = $member->binaryParent;
            while ($current) {
                array_unshift($ancestors, $current);
                $current = $current->binaryParent;
            }
        } else {
            // เดินขึ้นไป unilevel sponsor
            $current = $member->unilevelSponsor;
            while ($current) {
                array_unshift($ancestors, $current);
                $current = $current->unilevelSponsor;
            }
        }

        return $ancestors;
    }

    /**
     * สร้าง tree structure สำหรับ Bloodline
     */
    protected function buildBloodlineTree(array $ancestors, MlmMember $targetMember, $treeType)
    {
        // ถ้าไม่มี ancestors แสดงว่าเป็น root member
        if (empty($ancestors)) {
            return $this->formatMemberNode($targetMember, $treeType, true);
        }

        // สร้าง tree จาก root ลงมา
        $root = array_shift($ancestors);
        $rootNode = $this->formatMemberNode($root, $treeType, false);

        // สร้าง chain ของ nodes
        $currentNode = &$rootNode;
        foreach ($ancestors as $ancestor) {
            $childNode = $this->formatMemberNode($ancestor, $treeType, false);
            $currentNode['children'] = [$childNode];
            $currentNode = &$currentNode['children'][0];
        }

        // เพิ่ม target member เป็น leaf node สุดท้าย
        $targetNode = $this->formatMemberNode($targetMember, $treeType, true);
        $currentNode['children'] = [$targetNode];

        return $rootNode;
    }

    /**
     * Format member data สำหรับ node ใน Bloodline tree
     */
    protected function formatMemberNode(MlmMember $member, $treeType, $isTarget = false)
    {
        $user = $member->user;

        // ดึง retention status จาก Helper (ไม่ใช้ $member->retention_status ซึ่งไม่ใช่ DB field)
        $retentionData = MlmRetentionHelper::getRetentionStatus($member);

        return [
            'id' => $member->id,
            'name' => $user->name ?? 'Unknown',
            'member_code' => $member->member_code,
            'label' => $user->name ?? 'Unknown',
            'subtitle' => $member->member_code,
            'total_pv' => $member->total_pv ?? 0,
            'monthly_pv' => $retentionData['monthly_pv'] ?? ($member->monthly_pv ?? 0),
            'direct_referrals' => $member->total_direct_referrals ?? 0,
            'total_team_members' => $member->total_team_members ?? 0,
            'retention_status' => $retentionData['status'] ?? 'active',
            'status' => $member->status ?? 'active',
            // แก้ Bug #9: MlmMember ไม่มี rank relationship → ใช้ user->currentRank
            'rank_name' => $member->user->currentRank->name ?? null,
            'left_pv' => $member->left_leg_pv ?? 0,
            'right_pv' => $member->right_leg_pv ?? 0,
            'is_target' => $isTarget, // สมาชิกที่ต้องการดูสายเลือด
            'position' => $treeType === 'binary' ? $member->binary_position : null,
            'children' => [],
        ];
    }
}
