<?php

namespace App\Services;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\Order;
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

        if (! $plan || $plan->type === 'unilevel') {
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
     *
     * แก้ไข Bug #1: Reset left_leg_pv/right_leg_pv หลัง pair matching (ป้องกัน double-counting)
     * แก้ไข Bug #2: Carry forward PV ของขาแข็ง (stronger leg) ไม่ใช่ขาอ่อน
     * แก้ไข Bug #3: แก้สูตร pair ratio สำหรับ 2:1 pairing
     * แก้ไข Bug #22: เพิ่ม depth limit ป้องกัน infinite loop
     */
    protected function calculateBinaryPairCommissions(MlmMember $member, Order $order, array $pvData)
    {
        $plan = $member->plan;

        // ดึงค่าจาก Global Settings
        $maxPairsPerDay = MlmGlobalSetting::get('binary_max_pairs_per_day', null);
        $commissionPerPair = MlmGlobalSetting::get('binary_pair_commission', 100);
        $maxCommissionPerDay = MlmGlobalSetting::get('binary_max_commission_per_day', null);
        $flushPercentage = MlmGlobalSetting::get('binary_flush_percentage', 100) / 100;
        $pairingType = MlmGlobalSetting::get('binary_pairing_type', '1:1');

        // Traverse up the binary tree
        $currentMember = $member;
        $traverseDepth = 0;
        $maxTraverseDepth = 100; // Safety limit ป้องกัน infinite loop
        $visited = []; // ป้องกัน circular reference

        while ($currentMember->binaryParent && $traverseDepth < $maxTraverseDepth) {
            $parent = $currentMember->binaryParent;

            // ป้องกัน circular reference
            if (isset($visited[$parent->id])) {
                Log::warning('Circular reference detected in binary tree', [
                    'member_id' => $member->id,
                    'parent_id' => $parent->id,
                ]);
                break;
            }
            $visited[$parent->id] = true;
            $traverseDepth++;

            // ตรวจสอบและลบ carried PV ที่หมดอายุก่อนคำนวณ
            $parent->expireCarriedPv();

            // แก้ RET-1: ใช้ MlmRetentionHelper ตรวจสอบ PV รักษายอดจริง
            // แทน static field (is_qualified/status) ที่ไม่สะท้อน PV เดือนปัจจุบัน
            if (! MlmRetentionHelper::isMemberActive($parent)) {
                $currentMember = $parent;

                continue;
            }

            // Calculate pairs - รวม leg PV + carried PV
            $leftPv = $parent->left_leg_pv + $parent->carried_left_pv;
            $rightPv = $parent->right_leg_pv + $parent->carried_right_pv;

            $weakerLeg = min($leftPv, $rightPv);
            $strongerLeg = max($leftPv, $rightPv);

            // แก้ Bug #3: คำนวณ pairs ตาม pairing type ที่ถูกต้อง
            $pairsAvailable = $this->calculatePairsAvailable($weakerLeg, $strongerLeg, $pairingType);

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
                    // คำนวณ PV ที่ใช้ไปจากแต่ละขา
                    $pvConsumed = $this->calculatePvConsumed($pairsToProcess, $pairingType, $leftPv, $rightPv);

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
                        'pv_amount' => $pvConsumed['weak_consumed'] + $pvConsumed['strong_consumed'],
                        'sales_amount' => $order->total_amount,
                        'commission_amount' => $totalCommission,
                        'status' => 'pending',
                    ]);

                    // แก้ Bug #1: Reset/flush PV ที่ถูกใช้ไปจากทั้งสองขา
                    $leftConsumed = ($leftPv <= $rightPv)
                        ? $pvConsumed['weak_consumed']
                        : $pvConsumed['strong_consumed'];
                    $rightConsumed = ($rightPv <= $leftPv)
                        ? $pvConsumed['weak_consumed']
                        : $pvConsumed['strong_consumed'];

                    // Apply flush percentage
                    $leftFlushed = $leftConsumed * $flushPercentage;
                    $rightFlushed = $rightConsumed * $flushPercentage;

                    // ลด leg PV ก่อน แล้วค่อยลด carried PV
                    $this->flushLegPv($parent, 'left', $leftFlushed);
                    $this->flushLegPv($parent, 'right', $rightFlushed);

                    // แก้ Bug #2: Carry forward PV ที่เหลือของขาแข็ง (stronger leg)
                    $strongerLegRemaining = $strongerLeg - $pvConsumed['strong_consumed'];
                    if ($strongerLegRemaining > 0) {
                        $strongLegSide = ($leftPv >= $rightPv) ? 'left' : 'right';
                        $parent->setCarriedPvExpiry($strongLegSide, $strongerLegRemaining);
                    }
                }
            }

            $currentMember = $parent;
        }
    }

    /**
     * คำนวณจำนวน pairs ที่สามารถจับคู่ได้ตาม pairing type
     *
     * สำหรับ 1:1: pairs = weakerLeg (1 PV จากแต่ละขา = 1 pair)
     * สำหรับ 2:1: pairs = min(weakerLeg, floor(strongerLeg / 2))
     *            (1 PV จากขาอ่อน + 2 PV จากขาแข็ง = 1 pair)
     *
     * @param  float  $weakerLeg  PV ของขาอ่อน
     * @param  float  $strongerLeg  PV ของขาแข็ง
     * @param  string  $pairingType  ประเภท pairing ('1:1' หรือ '2:1')
     * @return int จำนวน pairs
     */
    protected function calculatePairsAvailable(float $weakerLeg, float $strongerLeg, string $pairingType): int
    {
        if ($pairingType === '2:1') {
            // 2:1 = ใช้ 2 PV จากขาแข็ง + 1 PV จากขาอ่อน ต่อ 1 pair
            return (int) min($weakerLeg, floor($strongerLeg / 2));
        }

        // 1:1 = ใช้ 1 PV จากแต่ละขา ต่อ 1 pair
        return (int) $weakerLeg;
    }

    /**
     * คำนวณ PV ที่ถูกใช้ไปจากแต่ละขาหลังจาก pair matching
     *
     * @param  int  $pairs  จำนวน pairs ที่จับคู่
     * @param  string  $pairingType  ประเภท pairing
     * @param  float  $leftPv  PV ขาซ้าย
     * @param  float  $rightPv  PV ขาขวา
     * @return array ['weak_consumed' => float, 'strong_consumed' => float]
     */
    protected function calculatePvConsumed(int $pairs, string $pairingType, float $leftPv, float $rightPv): array
    {
        if ($pairingType === '2:1') {
            // ขาอ่อนใช้ 1 PV ต่อ pair, ขาแข็งใช้ 2 PV ต่อ pair
            return [
                'weak_consumed' => $pairs * 1,
                'strong_consumed' => $pairs * 2,
            ];
        }

        // 1:1 - ทั้งสองขาใช้ 1 PV ต่อ pair
        return [
            'weak_consumed' => $pairs * 1,
            'strong_consumed' => $pairs * 1,
        ];
    }

    /**
     * ลด PV จาก leg (ลด leg_pv ก่อน ถ้าไม่พอค่อยลด carried_pv)
     *
     * @param  string  $side  'left' หรือ 'right'
     * @param  float  $amount  จำนวน PV ที่ต้องลด
     */
    protected function flushLegPv(MlmMember $parent, string $side, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $legPvField = "{$side}_leg_pv";
        $carriedPvField = "carried_{$side}_pv";

        $legPv = (float) $parent->$legPvField;
        $carriedPv = (float) $parent->$carriedPvField;

        if ($legPv >= $amount) {
            // ลดจาก leg PV พอทั้งหมด
            $parent->decrement($legPvField, $amount);
        } else {
            // ลด leg PV ให้หมด แล้วลดส่วนที่เหลือจาก carried PV
            $remaining = $amount - $legPv;
            $parent->update([$legPvField => 0]);
            $parent->decrement($carriedPvField, min($carriedPv, $remaining));
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

        if (! $autoPlacementEnabled) {
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
     * วางสมาชิกใหม่ลง Binary tree แบบปลอดภัย — ศูนย์กลางสำหรับทุก join path
     *
     * 🐛 Fix 2026-07-24: เดิมแต่ละ join path (เว็บ/แอดมิน/Fortune/FreshMarket/mobile)
     * เขียน placement + fallback เองคนละแบบ → บางทางไม่มี fallback (สมาชิกหลุดผัง)
     * บางทาง fallback วางใต้ sponsor ตรงทั้งที่ slot เต็ม (ตำแหน่งซ้ำ)
     *
     * ความสามารถ:
     * - หาตำแหน่งตาม strategy → ถ้าไม่ได้ (null/depth เต็ม/exception) → BFS หา slot ว่างจริง
     * - อัพเดท member (parent/position/path) + สถิติขา parent + team counts ของ upline
     * - กัน race: retry สูงสุด 3 ครั้งเมื่อชน unique constraint (สอง registration แย่ง slot เดียวกัน)
     *
     * @param  MlmMember  $member  สมาชิกที่ยังไม่ถูกวาง (binary_parent_id ต้องยัง null)
     * @param  MlmMember  $sponsor  ผู้แนะนำ (จุดเริ่มค้นหาตำแหน่ง)
     * @param  string|null  $preferredLeg  ขาที่ต้องการ (สำหรับ strategy 'manual')
     * @return array|null ['parent_id' => int, 'position' => string] ที่วางสำเร็จ หรือ null ถ้าวางไม่ได้
     */
    public function placeNewMember(MlmMember $member, MlmMember $sponsor, ?string $preferredLeg = null): ?array
    {
        // เมื่อ slot ที่ strategy เลือกถูกแย่ง/เต็ม → รอบถัดไปบังคับใช้ fallback BFS
        // (สำคัญกับ strategy 'manual' และกรณีปิด auto_placement ที่คืน slot ตายตัวเดิมทุกรอบ)
        $useFallback = false;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            // ดึง sponsor สดใหม่ทุกรอบ กัน relation cache ชี้ slot ที่เพิ่งถูกคนอื่นแย่งไป
            $freshSponsor = $sponsor->fresh();
            if ($freshSponsor) {
                $sponsor = $freshSponsor;
            }

            $placement = null;

            if (! $useFallback) {
                try {
                    $placement = $this->findPlacementPosition($sponsor, $preferredLeg);
                } catch (\Throwable $e) {
                    Log::warning('Binary placement ล้มเหลว — จะใช้ fallback BFS', [
                        'member_id' => $member->id,
                        'sponsor_id' => $sponsor->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback: BFS หา slot ว่างจริงใต้ sponsor (ไม่สน depth cap — กันสมาชิกหลุดผัง)
            if (! is_array($placement) || ! isset($placement['parent_id'])) {
                $placement = $this->findFallbackPosition($sponsor);
            }

            if (! $placement) {
                return null;
            }

            $parent = MlmMember::find($placement['parent_id']);
            if (! $parent) {
                $useFallback = true;

                continue;
            }

            $position = $placement['position'] ?? 'left';

            // ตรวจว่า slot ยังว่างจริงก่อนเขียน (unique index เป็น backstop สุดท้าย)
            $slotTaken = MlmMember::where('binary_parent_id', $parent->id)
                ->where('binary_position', $position)
                ->where('id', '!=', $member->id)
                ->exists();

            if ($slotTaken) {
                $useFallback = true; // slot จาก strategy ใช้ไม่ได้ — รอบหน้าหา slot ว่างจริงแทน

                continue;
            }

            try {
                $member->update([
                    'binary_parent_id' => $parent->id,
                    'binary_position' => $position,
                    'binary_path' => ($parent->binary_path ?? '').'/'.$position,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = duplicate key (ชนกับ registration อื่นพร้อมกัน) → หาตำแหน่งใหม่
                if (($e->errorInfo[0] ?? '') === '23000') {
                    $useFallback = true;

                    continue;
                }
                throw $e;
            }

            // อัพเดทสถิติขาของ parent + team counts ของ upline ทั้งสาย
            $parent->increment($position === 'left' ? 'left_leg_members' : 'right_leg_members');
            $this->incrementUplineTeamCounts($member);

            Log::info('Binary placement สำเร็จ', [
                'member_id' => $member->id,
                'parent_id' => $parent->id,
                'position' => $position,
                'attempt' => $attempt + 1,
            ]);

            return ['parent_id' => $parent->id, 'position' => $position];
        }

        Log::error('Binary placement ล้มเหลวหลัง retry ครบ — member ยังไม่ถูกวางในผัง', [
            'member_id' => $member->id,
            'sponsor_id' => $sponsor->id,
        ]);

        return null;
    }

    /**
     * BFS หา slot ว่างจริงใต้ sponsor โดยไม่สน depth cap
     *
     * ใช้เป็น fallback เมื่อ strategy ปกติหาตำแหน่งไม่ได้ (เช่น binary_max_depth เต็ม)
     * หลัก: การให้สมาชิกอยู่ในผังลึกเกิน cap ดีกว่าหลุดผังไปเลย
     * (ห้าม fallback วางใต้ sponsor ตรงๆ — slot อาจเต็มแล้ว จะได้ตำแหน่งซ้ำ)
     *
     * @return array|null ['parent_id' => int, 'position' => string] หรือ null
     */
    public function findFallbackPosition(MlmMember $sponsor): ?array
    {
        $queue = [$sponsor->id];
        $visited = [];
        $scanned = 0;

        while (! empty($queue) && $scanned < 5000) {
            $currentId = array_shift($queue);

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;
            $scanned++;

            $children = MlmMember::where('binary_parent_id', $currentId)
                ->whereIn('binary_position', ['left', 'right'])
                ->get(['id', 'binary_position']);

            if (! $children->contains(fn ($c) => $c->binary_position === 'left')) {
                return ['parent_id' => $currentId, 'position' => 'left'];
            }

            if (! $children->contains(fn ($c) => $c->binary_position === 'right')) {
                return ['parent_id' => $currentId, 'position' => 'right'];
            }

            foreach ($children as $child) {
                $queue[] = $child->id;
            }
        }

        return null;
    }

    /**
     * เพิ่ม total_team_members ให้ upline ทั้งสาย binary ของสมาชิกใหม่
     *
     * 🐛 แทนที่ updateBinaryUplineTeamCounts เดิมใน RegisterController ที่มีบั๊ก
     * (safety check เทียบ id หลัง assign ทำให้ break หลังวนแค่รอบเดียว)
     */
    protected function incrementUplineTeamCounts(MlmMember $member): void
    {
        $visited = [];
        $current = $member;

        for ($i = 0; $i < 100; $i++) {
            $parentId = $current->binary_parent_id;

            if (! $parentId || isset($visited[$parentId])) {
                break; // ถึงราก หรือเจอ cycle
            }
            $visited[$parentId] = true;

            $parent = MlmMember::find($parentId);
            if (! $parent) {
                break;
            }

            $parent->increment('total_team_members');
            $current = $parent;
        }
    }

    /**
     * นับ depth ของ member จาก root
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
            if ($depth > 100) {
                break;
            } // Safety limit
        }

        return $depth;
    }

    /**
     * นับจำนวน children ของ node
     */
    protected function getChildrenCount(MlmMember $member): int
    {
        $count = 0;
        if ($member->binaryLeftChild) {
            $count++;
        }
        if ($member->binaryRightChild) {
            $count++;
        }

        return $count;
    }

    /**
     * ตรวจสอบว่าสามารถวาง child ที่ node นี้ได้หรือไม่
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
     * ตรวจ depth limit + คืน depth ปัจจุบันของ node
     *
     * 🐛 Fix 2026-06-12: เดิมทุก DFS strategy เช็ค canPlaceChild() ที่บรรทัดแรก
     * → node ที่มีลูกครบ (width เต็ม) ถูก return null ทันที โค้ด recursion ลง subtree
     * กลายเป็น dead code → sponsor ที่มีลูกครบ 2 ข้างวางสมาชิกใหม่ไม่ได้ตลอดกาล
     * → MlmMember ไม่ถูกสร้าง → คอมมิชชั่นดูดวงไม่จ่ายทั้งระบบ
     *
     * หลักที่ถูก: width เต็ม = วางตรง node นี้ไม่ได้ แต่ subtree ข้างล่างยังวางได้
     * มีแค่ depth limit เท่านั้นที่หยุดการค้นทั้งสาขา
     *
     * @param  MlmMember  $member  node ที่กำลังตรวจ
     * @param  int|null  $maxDepth  ความลึกสูงสุด (null = ไม่จำกัด)
     * @param  int|null  $currentDepth  depth ที่ thread ลงมาจาก recursion (null = คำนวณใหม่)
     * @return array{exceeded: bool, depth: int|null} exceeded = ลึกเกิน limit แล้ว
     */
    protected function resolveDepthGuard(MlmMember $member, ?int $maxDepth, ?int $currentDepth): array
    {
        if ($maxDepth === null) {
            return ['exceeded' => false, 'depth' => null];
        }

        $depth = $currentDepth ?? $this->getMemberDepth($member);

        return ['exceeded' => $depth >= $maxDepth, 'depth' => $depth];
    }

    /**
     * หาช่องว่างที่วางตรง node นี้ได้เลย (ถ้า width ยังไม่เต็ม)
     *
     * @return array|null ['parent_id' => ..., 'position' => ...] หรือ null ถ้า node เต็ม
     */
    protected function findDirectSlot(MlmMember $member, int $maxWidth): ?array
    {
        if ($this->getChildrenCount($member) >= $maxWidth) {
            return null;
        }

        if (! $member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if ($maxWidth >= 2 && ! $member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        return null;
    }

    /**
     * Find right-to-left placement
     * รองรับ depth/width limits
     */
    protected function findRightToLeftPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง (ขวาก่อน)
        if ($this->getChildrenCount($member) < $maxWidth) {
            if ($maxWidth >= 2 && ! $member->binaryRightChild) {
                return ['parent_id' => $member->id, 'position' => 'right'];
            }
            if (! $member->binaryLeftChild) {
                return ['parent_id' => $member->id, 'position' => 'left'];
            }
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // node เต็ม → ค้นใน subtree ขวาก่อน แล้วค่อยซ้าย
        if ($member->binaryRightChild) {
            $rightPlacement = $this->findRightToLeftPlacement($member->binaryRightChild, $maxDepth, $maxWidth, $childDepth);
            if ($rightPlacement) {
                return $rightPlacement;
            }
        }

        if ($member->binaryLeftChild) {
            return $this->findRightToLeftPlacement($member->binaryLeftChild, $maxDepth, $maxWidth, $childDepth);
        }

        return null;
    }

    /**
     * Find strong leg placement
     * รองรับ depth/width limits
     */
    protected function findStrongLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่ PV มากกว่าก่อน (strong leg) — ถ้าขานั้นเต็มให้ลองอีกขา
        $strongFirst = $member->left_leg_pv >= $member->right_leg_pv;
        $legs = $strongFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
    }

    /**
     * Find left-to-right placement
     * รองรับ depth/width limits
     */
    protected function findLeftToRightPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง (ซ้ายก่อน)
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // node เต็ม → ค้นใน subtree ซ้ายก่อน แล้วค่อยขวา
        if ($member->binaryLeftChild) {
            $leftPlacement = $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth, $childDepth);
            if ($leftPlacement) {
                return $leftPlacement;
            }
        }

        if ($member->binaryRightChild) {
            return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth, $childDepth);
        }

        return null;
    }

    /**
     * Find balanced placement
     * รองรับ depth/width limits
     */
    protected function findBalancedPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่สมาชิกน้อยกว่าก่อน — ถ้าขานั้นเต็มให้ลองอีกขา
        $leftFirst = $member->left_leg_members <= $member->right_leg_members;
        $legs = $leftFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
    }

    /**
     * Find weak leg placement
     * รองรับ depth/width limits
     */
    protected function findWeakLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่ PV น้อยกว่าก่อน (weak leg) — ถ้าขานั้นเต็มให้ลองอีกขา
        $weakFirst = $member->left_leg_pv <= $member->right_leg_pv;
        $legs = $weakFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
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
     * @param  MlmMember  $member  จุดเริ่มต้นของ tree
     * @param  int|null  $maxDepth  ความลึกสูงสุด (null = ไม่จำกัด)
     * @param  int  $maxWidth  จำนวนลูกสูงสุดต่อ node
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
            if (! $current->binaryLeftChild) {
                return ['parent_id' => $current->id, 'position' => 'left'];
            }

            // ตรวจสอบว่า right ว่างไหม (ถ้า maxWidth >= 2)
            if ($maxWidth >= 2 && ! $current->binaryRightChild) {
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
                        if (! $hasChild) {
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

        // ดึงข้อมูลเพิ่มเติมสำหรับ v3 genealogy tree
        // ⚠️ MlmMember ไม่มี relation rank() — ต้องอ่าน rank ผ่าน user->currentRank เท่านั้น
        $user = $member->user;
        $rank = $user?->currentRank;

        // คำนวณ retention วันหมดอายุ
        $retentionExpiresAt = $retentionData['expires_at'] ?? null;
        $retentionDaysLeft = null;
        if ($retentionExpiresAt) {
            $retentionDaysLeft = max(0, (int) now()->diffInDays($retentionExpiresAt, false));
        }

        $node = [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $user->name ?? 'Unknown',
            'member_code' => $member->member_code,
            'position' => $member->binary_position,
            'depth' => $currentDepth,
            // ข้อมูลใหม่ v3
            'avatar_url' => $user->profile_picture_url ?? null,
            'email' => $user->email ?? null,
            'rank_name' => $rank->name ?? null,
            'rank_color' => $rank->color ?? '#6B7280',
            'joined_at' => $member->created_at?->format('Y-m-d'),
            'retention_expires_at' => $retentionExpiresAt,
            'retention_days_left' => $retentionDaysLeft,
            'is_qualified' => (bool) ($member->is_qualified ?? false),
            // ข้อมูลเดิม
            'total_pv' => $member->total_pv,
            'monthly_pv' => $retentionData['monthly_pv'],
            // left/right_leg_pv = ยอดคงเหลือหลังจับคู่ (ไม่ใช่ยอดสะสมทั้งหมด)
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            // carried PV = PV ขาแข็งที่ยกยอดไปรอบถัดไป — ต้องแสดงคู่กันไม่งั้นตัวเลขในผัง
            // ไม่ตรงกับที่ engine ใช้จับคู่จริง
            'carried_left_pv' => (float) ($member->carried_left_pv ?? 0),
            'carried_right_pv' => (float) ($member->carried_right_pv ?? 0),
            'status' => $member->status,
            'retention_status' => $retentionData['status'],
            'direct_referrals' => $member->total_direct_referrals ?? 0,
            'left' => null,
            'right' => null,
        ];

        // โหลด children (eager load user+currentRank สำหรับ v3)
        // ⚠️ ห้ามใส่ 'rank' ตรงๆ — MlmMember ไม่มี relation นี้ จะโยน RelationNotFoundException
        $leftChild = $member->binaryLeftChild()->with(['user.currentRank'])->first();
        $rightChild = $member->binaryRightChild()->with(['user.currentRank'])->first();

        if ($leftChild) {
            $node['left'] = $this->buildBinaryTreeRecursive($leftChild, $currentDepth + 1, $maxDepth);
        }

        if ($rightChild) {
            $node['right'] = $this->buildBinaryTreeRecursive($rightChild, $currentDepth + 1, $maxDepth);
        }

        return $node;
    }
}
