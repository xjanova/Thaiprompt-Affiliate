<?php

namespace App\Services;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MlmCommissionService
{
    /**
     * Track rollup count per member ในแต่ละ transaction
     * เพื่อป้องกันคนเดียวได้ rollup มากเกินไป
     */
    protected array $rollupCountPerMember = [];

    /**
     * เก็บยอด commission ที่ถูก skip ไปยัง pool
     */
    protected float $pooledRollupAmount = 0;

    /**
     * Calculate and distribute commissions with roll-up logic
     *
     * Roll-up Logic ที่ปรับปรุงแล้ว:
     * 1. จำกัดจำนวน rollup ต่อคน (rollup_max_per_member)
     * 2. กระจายแบบต่างๆ (single/distributed/proportional)
     * 3. ส่ง excess ไป Pool Bonus แทน admin (rollup_to_pool_enabled)
     */
    public function calculateCommissionsWithRollup(MlmMember $member, float $pv, string $transactionType = 'purchase', $transactionId = null)
    {
        DB::beginTransaction();

        try {
            $commissions = [];

            // Reset tracking per transaction
            $this->rollupCountPerMember = [];
            $this->pooledRollupAmount = 0;

            // Get global settings
            $rollupEnabled = MlmGlobalSetting::get('rollup_enabled', false);
            $preventDuplicateRollup = MlmGlobalSetting::get('rollup_prevent_duplicate', true);
            $rollupMaxLevels = MlmGlobalSetting::get('rollup_max_levels', 10);

            // ใช้ unilevel_max_depth สำหรับจำนวนชั้นที่จ่าย commission
            // rollup_max_levels ใช้สำหรับระยะค้นหา rollup เท่านั้น
            $unilevelMaxDepth = MlmGlobalSetting::get('unilevel_max_depth', 10);

            // Calculate Unilevel commissions with roll-up
            if ($this->isUnilevelEnabled()) {
                $unilevelCommissions = $this->calculateUnilevelWithRollup(
                    $member,
                    $pv,
                    $transactionType,
                    $transactionId,
                    $rollupEnabled,
                    $preventDuplicateRollup,
                    $unilevelMaxDepth,
                    $rollupMaxLevels
                );
                $commissions = array_merge($commissions, $unilevelCommissions);
            }

            // Calculate Binary commissions (no roll-up for binary)
            if ($this->isBinaryEnabled()) {
                $binaryCommissions = $this->calculateBinaryCommissions($member, $pv, $transactionType, $transactionId);
                $commissions = array_merge($commissions, $binaryCommissions);
            }

            // แก้ Bug: Overpay Protection - ป้องกันจ่าย commission เกินสัดส่วนที่ตั้งค่าไว้
            $overpayProtection = MlmGlobalSetting::get('overpay_protection_enabled', false);
            if ($overpayProtection && !empty($commissions)) {
                $maxCommissionPercentage = (float) MlmGlobalSetting::get('max_commission_percentage', 40);
                $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);

                // คำนวณมูลค่าสูงสุดที่จ่ายได้ = PV × commissionPerPv × (maxPercentage / 100)
                $maxAllowedTotal = $pv * $commissionPerPv * ($maxCommissionPercentage / 100);
                $totalCommission = array_sum(array_column($commissions, 'commission_amount'));

                if ($totalCommission > $maxAllowedTotal && $maxAllowedTotal > 0) {
                    // ลดสัดส่วนทุก commission ให้รวมแล้วไม่เกิน cap
                    $ratio = $maxAllowedTotal / $totalCommission;

                    foreach ($commissions as &$comm) {
                        $comm['commission_amount'] = round($comm['commission_amount'] * $ratio, 2);
                    }
                    unset($comm);

                    Log::warning('Overpay protection triggered - commissions scaled down', [
                        'member_id' => $member->id,
                        'pv' => $pv,
                        'original_total' => $totalCommission,
                        'max_allowed' => $maxAllowedTotal,
                        'ratio' => $ratio,
                    ]);
                }
            }

            // Save all commissions
            foreach ($commissions as $commission) {
                MlmCommission::create($commission);
            }

            DB::commit();

            return [
                'success' => true,
                'commissions' => $commissions,
                // แก้ Bug #4: ใช้ key 'commission_amount' ที่ถูกต้อง (ไม่ใช่ 'amount')
                'total_amount' => array_sum(array_column($commissions, 'commission_amount')),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission calculation error', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate Unilevel commissions with roll-up/compression logic
     *
     * Roll-up Logic:
     * 1. ถ้าสมาชิกไม่ active (ไม่รักษายอด) → ข้ามไป upline ที่ active
     * 2. ป้องกัน duplicate rollup - คนที่ได้รับแล้วจะไม่ได้รับซ้ำ
     * 3. สุดท้ายจะ rollup ไปที่ Admin (ID 1) เท่านั้น
     * 4. บันทึก rollup_chain เพื่อติดตามที่มาที่ไป
     *
     * @param MlmMember $member สมาชิกผู้ซื้อ
     * @param float $pv จำนวน PV
     * @param string $transactionType ประเภทธุรกรรม
     * @param mixed $transactionId ID ของธุรกรรม
     * @param bool $rollupEnabled เปิดระบบ rollup หรือไม่
     * @param bool $preventDuplicateRollup ป้องกันจ่าย rollup ซ้ำ
     * @param int $maxLevels จำนวนชั้นสูงสุดที่จ่ายคอมมิชชั่น (unilevel_max_depth)
     * @param int $rollupSearchDepth ระยะค้นหา rollup สูงสุด (rollup_max_levels)
     */
    protected function calculateUnilevelWithRollup(
        MlmMember $member,
        float $pv,
        string $transactionType,
        $transactionId,
        bool $rollupEnabled,
        bool $preventDuplicateRollup,
        int $maxLevels,
        int $rollupSearchDepth = 10
    ) {
        $commissions = [];
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        if (empty($unilevelLevels)) {
            return $commissions;
        }

        // ดึงอัตราแปลง PV → บาท (แอดมินตั้งค่าได้)
        $commissionPerPv = (float) MlmGlobalSetting::get('commission_per_pv', 1);

        // แก้ Bug: แปลง transactionType เป็น source_type/source_id สำหรับ duplicate guard
        $sourceType = $this->resolveSourceType($transactionType);
        $sourceId = $transactionId;

        // ดึง Admin member (ID 1) สำหรับ final rollup
        $adminMember = MlmMember::find(1);

        $currentMember = $member;
        $currentLevel = 1;
        $rollupTracker = []; // Track who received roll-up commissions

        while ($currentLevel <= $maxLevels && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (!$sponsor) {
                break;
            }

            // Check if sponsor is active (maintaining volume)
            $isActive = MlmRetentionHelper::isMemberActive($sponsor);

            // Get level configuration
            $levelConfig = collect($unilevelLevels)->firstWhere('level', $currentLevel);

            if (!$levelConfig) {
                // แก้ Bug #23: ใช้ continue แทน break เพื่อให้ level ถัดไปยังทำงานได้
                $currentMember = $sponsor;
                $currentLevel++;
                continue;
            }

            $percentage = $levelConfig['percentage'] ?? 0;

            if ($percentage <= 0) {
                $currentMember = $sponsor;
                $currentLevel++;
                continue;
            }

            // คำนวณคอมมิชชั่น: PV × เปอร์เซ็นต์ × อัตราแปลง PV→บาท
            $commissionAmount = ($pv * $percentage) / 100 * $commissionPerPv;

            // แก้ Bug: เพิ่ม rank bonus_multiplier (ถ้า sponsor มี rank ที่มี multiplier)
            // ตรงกับ logic ใน MlmUnilevelService ที่ใช้ currentRank->bonus_multiplier
            // แก้ Warning: เพิ่ม cap สำหรับ bonus_multiplier ป้องกันค่าผิดปกติ
            if ($sponsor->user && $sponsor->user->current_rank_id) {
                $rank = $sponsor->user->currentRank;
                if ($rank && $rank->bonus_multiplier) {
                    $maxMultiplier = (float) MlmGlobalSetting::get('max_rank_bonus_multiplier', 5.0);
                    $multiplier = min($rank->bonus_multiplier, $maxMultiplier);
                    $commissionAmount *= $multiplier;
                }
            }

            // แก้ Bug #24: ใช้ !== null แทน falsy check (ค่า 0 = ห้ามจ่าย ต้องไม่ถูกข้ามไป)
            $maxPerLevel = MlmGlobalSetting::get('unilevel_max_commission_per_level', null);
            if ($maxPerLevel !== null && $commissionAmount > $maxPerLevel) {
                $commissionAmount = $maxPerLevel;
            }

            // ตรวจสอบสถานะ active ก่อนจ่าย commission
            // แก้ ROLLUP-1: คนไม่รักษายอดต้องถูกข้ามเสมอ ไม่ว่า rollup จะเปิดหรือปิด
            if (!$isActive) {
                if ($rollupEnabled) {
                    // Rollup เปิด: ม้วนขึ้นไปหาคน active ถัดไป
                    Log::info("Roll-up triggered for inactive member", [
                        'inactive_member_id' => $sponsor->id,
                        'level' => $currentLevel,
                        'amount' => $commissionAmount
                    ]);

                    // สร้าง rollup chain สำหรับติดตาม
                    $rollupChain = [];
                    $rollupChain[] = [
                        'member_id' => $sponsor->id,
                        'member_code' => $sponsor->member_code,
                        'level' => $currentLevel,
                        'reason' => 'inactive',
                        'tree_type' => 'unilevel',
                    ];

                    // แก้ ROLLUP-2: ใช้ $rollupSearchDepth สำหรับระยะค้นหา rollup
                    // แทน $maxLevels - $currentLevel ที่อาจจำกัดเกินไป
                    $rollupResult = $this->findNextActiveUplineWithChain(
                        $sponsor,
                        $rollupSearchDepth,
                        $rollupTracker,
                        $preventDuplicateRollup,
                        $adminMember
                    );

                    $rollupSponsor = $rollupResult['sponsor'];
                    $rollupChain = array_merge($rollupChain, $rollupResult['chain']);
                    $toPool = $rollupResult['to_pool'] ?? false;

                    if ($toPool) {
                        // ส่ง commission ไป Pool Bonus
                        $this->pooledRollupAmount += $commissionAmount;

                        // แก้ Bug #5: ใช้ admin member แทน null (NOT NULL columns)
                        $fallbackMemberId = $adminMember->id ?? $member->id;
                        $fallbackUserId = $adminMember->user_id ?? $member->user_id;

                        // บันทึก commission ที่ส่งไป pool (สำหรับ tracking)
                        $commissions[] = [
                            'mlm_member_id' => $fallbackMemberId,
                            'mlm_plan_id' => $member->mlm_plan_id,
                            'user_id' => $fallbackUserId,
                            'from_member_id' => $member->id,
                            // แก้ Bug: เพิ่ม source_type/source_id สำหรับ duplicate guard
                            'source_type' => $sourceType,
                            'source_id' => $sourceId,
                            'type' => 'pool_bonus',
                            'level' => $currentLevel,
                            'commission_amount' => $commissionAmount,
                            'pv_amount' => $pv,
                            'percentage' => $percentage,
                            'status' => 'pending',
                            'is_rollup' => true,
                            'rollup_from_member_id' => $sponsor->id,
                            'rollup_original_level' => $currentLevel,
                            'rollup_chain' => json_encode($rollupChain),
                            'tree_type' => 'pool',
                            'notes' => sprintf(
                                'Roll-up ส่งไป Pool Bonus จากสมาชิก #%s (ข้าม %d คน)',
                                $sponsor->member_code,
                                count($rollupChain)
                            ),
                            'calculation_details' => json_encode([
                                'pv' => $pv,
                                'percentage' => $percentage,
                                'level' => $currentLevel,
                                'rollup' => true,
                                'to_pool' => true,
                                'inactive_member' => $sponsor->member_code,
                                'rollup_chain_length' => count($rollupChain),
                            ]),
                            'created_at' => now(),
                        ];

                        Log::info("Roll-up commission sent to Pool Bonus", [
                            'amount' => $commissionAmount,
                            'rolled_from' => $sponsor->id,
                            'chain_length' => count($rollupChain),
                            'total_pooled' => $this->pooledRollupAmount,
                        ]);

                    } elseif ($rollupSponsor) {
                        // Mark this upline as having received roll-up
                        $rollupTracker[$rollupSponsor->id] = true;

                        $commissions[] = [
                            'mlm_member_id' => $rollupSponsor->id,
                            'mlm_plan_id' => $member->mlm_plan_id,
                            'user_id' => $rollupSponsor->user_id,
                            'from_member_id' => $member->id,
                            // แก้ Bug: เพิ่ม source_type/source_id สำหรับ duplicate guard
                            'source_type' => $sourceType,
                            'source_id' => $sourceId,
                            'type' => 'unilevel_rollup',
                            'level' => $currentLevel,
                            'commission_amount' => $commissionAmount,
                            'pv_amount' => $pv,
                            'percentage' => $percentage,
                            'status' => 'pending',
                            // ข้อมูล Roll-up tracking
                            'is_rollup' => true,
                            'rollup_from_member_id' => $sponsor->id,
                            'rollup_original_level' => $currentLevel,
                            'rollup_chain' => json_encode($rollupChain),
                            'tree_type' => 'unilevel',
                            'notes' => $this->buildRollupNotes($rollupChain, $sponsor),
                            'calculation_details' => json_encode([
                                'pv' => $pv,
                                'percentage' => $percentage,
                                'level' => $currentLevel,
                                'rollup' => true,
                                'inactive_member' => $sponsor->member_code,
                                'rollup_recipient' => $rollupSponsor->member_code,
                                'rollup_chain_length' => count($rollupChain),
                                'recipient_rollup_count' => $this->rollupCountPerMember[$rollupSponsor->id] ?? 0,
                            ]),
                            'created_at' => now(),
                        ];

                        Log::info("Roll-up commission created with chain tracking", [
                            'recipient_id' => $rollupSponsor->id,
                            'recipient_code' => $rollupSponsor->member_code,
                            'amount' => $commissionAmount,
                            'rolled_from' => $sponsor->id,
                            'chain_length' => count($rollupChain),
                            'recipient_rollup_count' => $this->rollupCountPerMember[$rollupSponsor->id] ?? 0,
                        ]);
                    }

                } else {
                    // แก้ ROLLUP-1: Rollup ปิด แต่สมาชิกไม่ active → ข้าม (ไม่จ่าย)
                    // คอมมิชชั่นของชั้นนี้จะหายไป เพราะไม่มีระบบ rollup ม้วนต่อ
                    Log::info("Skipped inactive member (rollup disabled)", [
                        'inactive_member_id' => $sponsor->id,
                        'level' => $currentLevel,
                        'skipped_amount' => $commissionAmount,
                    ]);
                }

            } else {
                // สมาชิก active → จ่ายคอมมิชชั่นปกติ
                $commissions[] = [
                    'mlm_member_id' => $sponsor->id,
                    'mlm_plan_id' => $member->mlm_plan_id,
                    'user_id' => $sponsor->user_id,
                    'from_member_id' => $member->id,
                    // แก้ Bug: เพิ่ม source_type/source_id สำหรับ duplicate guard
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    // แก้ Bug #4: ใช้ type ที่ถูกต้องตาม level
                    'type' => $currentLevel === 1 ? 'unilevel_direct' : 'unilevel_indirect',
                    'level' => $currentLevel,
                    'commission_amount' => $commissionAmount,
                    'pv_amount' => $pv,
                    'percentage' => $percentage,
                    'status' => 'pending',
                    // ไม่ใช่ rollup
                    'is_rollup' => false,
                    'rollup_from_member_id' => null,
                    'rollup_original_level' => null,
                    'rollup_chain' => null,
                    'tree_type' => 'unilevel',
                    'calculation_details' => json_encode([
                        'pv' => $pv,
                        'percentage' => $percentage,
                        'level' => $currentLevel,
                    ]),
                    'created_at' => now(),
                ];
            }

            $currentMember = $sponsor;
            $currentLevel++;
        }

        return $commissions;
    }

    /**
     * หา upline ที่ active ถัดไป พร้อมสร้าง chain tracking
     *
     * ปรับปรุงแล้ว:
     * 1. เช็ค rollup_max_per_member - ป้องกันคนเดียวได้มากเกินไป
     * 2. เช็ค rollup_to_pool_enabled - ส่งไป pool แทน admin
     * 3. กระจายแบบ distributed/proportional ถ้าตั้งค่าไว้
     *
     * @param MlmMember $member
     * @param int $maxLevelsToSearch
     * @param array $rollupTracker
     * @param bool $preventDuplicate
     * @param MlmMember|null $adminMember
     * @return array ['sponsor' => MlmMember|null, 'chain' => array, 'to_pool' => bool]
     */
    protected function findNextActiveUplineWithChain(
        MlmMember $member,
        int $maxLevelsToSearch,
        array $rollupTracker,
        bool $preventDuplicate,
        ?MlmMember $adminMember
    ): array {
        $chain = [];
        $currentMember = $member;
        $levelsSearched = 0;

        // ดึง settings ใหม่
        $maxPerMember = MlmGlobalSetting::get('rollup_max_per_member', 1);
        $toPoolEnabled = MlmGlobalSetting::get('rollup_to_pool_enabled', true);

        while ($levelsSearched < $maxLevelsToSearch && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (!$sponsor) {
                break;
            }

            $isActive = MlmRetentionHelper::isMemberActive($sponsor);
            $alreadyReceived = $preventDuplicate && isset($rollupTracker[$sponsor->id]);

            // ตรวจสอบว่าคนนี้ได้รับ rollup เกินกำหนดหรือยัง
            $memberRollupCount = $this->rollupCountPerMember[$sponsor->id] ?? 0;
            $exceedsMaxRollup = $memberRollupCount >= $maxPerMember;

            if ($isActive && !$alreadyReceived && !$exceedsMaxRollup) {
                // พบ upline ที่ active, ยังไม่ได้รับ rollup, และไม่เกินกำหนด
                // เพิ่ม count
                $this->rollupCountPerMember[$sponsor->id] = $memberRollupCount + 1;

                return [
                    'sponsor' => $sponsor,
                    'chain' => $chain,
                    'to_pool' => false,
                ];
            }

            // เหตุผลที่ข้าม
            $reason = 'inactive';
            if ($alreadyReceived) {
                $reason = 'already_received';
            } elseif ($exceedsMaxRollup) {
                $reason = 'exceeds_max_rollup';
            }

            // เพิ่มเข้า chain
            $chain[] = [
                'member_id' => $sponsor->id,
                'member_code' => $sponsor->member_code,
                'level' => $member->unilevel_level + $levelsSearched + 1,
                'reason' => $reason,
                'tree_type' => 'unilevel',
                'rollup_count' => $memberRollupCount,
            ];

            $currentMember = $sponsor;
            $levelsSearched++;
        }

        // ถ้าหาไม่เจอ upline ที่เหมาะสม
        // ตัดสินใจว่าจะส่งไป admin หรือ pool

        if ($toPoolEnabled) {
            // ส่งไป Pool Bonus แทน admin
            $chain[] = [
                'member_id' => null,
                'member_code' => 'POOL',
                'level' => 0,
                'reason' => 'sent_to_pool_bonus',
                'tree_type' => 'pool',
            ];

            Log::info('Rollup commission sent to Pool Bonus', [
                'original_member_id' => $member->id,
                'chain_length' => count($chain),
            ]);

            return [
                'sponsor' => null,
                'chain' => $chain,
                'to_pool' => true,
            ];
        }

        // ส่งไป Admin (fallback เดิม)
        if ($adminMember) {
            $adminRollupCount = $this->rollupCountPerMember[$adminMember->id] ?? 0;

            // Admin ได้ไม่จำกัด แต่ยังบันทึกไว้
            $this->rollupCountPerMember[$adminMember->id] = $adminRollupCount + 1;

            $chain[] = [
                'member_id' => $adminMember->id,
                'member_code' => $adminMember->member_code,
                'level' => 0,
                'reason' => 'final_rollup_to_admin',
                'tree_type' => 'unilevel',
            ];

            return [
                'sponsor' => $adminMember,
                'chain' => $chain,
                'to_pool' => false,
            ];
        }

        return [
            'sponsor' => null,
            'chain' => $chain,
            'to_pool' => false,
        ];
    }

    /**
     * สร้าง notes สำหรับ rollup commission
     *
     * @param array $chain
     * @param MlmMember $originalInactive
     * @return string
     */
    protected function buildRollupNotes(array $chain, MlmMember $originalInactive): string
    {
        $skippedCount = count($chain);
        $skippedCodes = array_map(fn($item) => $item['member_code'], $chain);

        return sprintf(
            "Roll-up จากสมาชิก #%s (ไม่ active) ข้าม %d คน: %s",
            $originalInactive->member_code,
            $skippedCount,
            implode(' → ', $skippedCodes)
        );
    }

    /**
     * ตรวจสอบว่าสมาชิก active หรือไม่ (delegate ไป MlmRetentionHelper)
     *
     * @deprecated ใช้ MlmRetentionHelper::isMemberActive() โดยตรงแทน
     */
    protected function isMemberActive(MlmMember $member): bool
    {
        return MlmRetentionHelper::isMemberActive($member);
    }

    /**
     * Find the next active upline member
     */
    protected function findNextActiveUpline(MlmMember $member, int $maxLevelsToSearch): ?MlmMember
    {
        $currentMember = $member;
        $levelsSearched = 0;

        while ($levelsSearched < $maxLevelsToSearch && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (!$sponsor) {
                return null;
            }

            if (MlmRetentionHelper::isMemberActive($sponsor)) {
                return $sponsor;
            }

            $currentMember = $sponsor;
            $levelsSearched++;
        }

        return null;
    }

    /**
     * Calculate Binary commissions (no roll-up for binary)
     *
     * เชื่อมต่อกับ MlmBinaryService เพื่อคำนวณ pair matching จริง:
     * 1. attributePvToBinaryLeg - อัพเดท left_leg_pv / right_leg_pv ขึ้นไปตาม binary tree
     * 2. calculateBinaryPairCommissions - จับคู่ขาซ้าย/ขวา แล้วสร้าง commission
     *
     * หมายเหตุ: MlmBinaryService สร้าง MlmCommission records โดยตรง (ไม่ return array)
     * ดังนั้น method นี้ return array ว่าง เพราะ commissions ถูก save แล้ว
     *
     * @param MlmMember $member สมาชิกผู้ซื้อ
     * @param float $pv จำนวน PV
     * @param string $transactionType ประเภทธุรกรรม ('order', 'purchase', etc.)
     * @param mixed $transactionId ID ของธุรกรรม (order_id)
     * @return array Commission records (ว่าง เพราะ MlmBinaryService save โดยตรง)
     */
    protected function calculateBinaryCommissions(MlmMember $member, float $pv, string $transactionType, $transactionId)
    {
        $binaryEnabled = MlmGlobalSetting::get('binary_enabled', false);

        if (!$binaryEnabled) {
            return [];
        }

        // แก้ Warning: รองรับ transactionType ทั้ง 'order' และ 'purchase' (เป็น Order เหมือนกัน)
        if (in_array($transactionType, ['order', 'purchase']) && $transactionId) {
            $order = Order::find($transactionId);

            if ($order) {
                $binaryService = new MlmBinaryService();
                $pvData = ['total_pv' => $pv];

                // MlmBinaryService จะ:
                // 1. attributePvToBinaryLeg() - อัพเดท left_leg_pv/right_leg_pv ขึ้นไปตาม tree
                // 2. calculateBinaryPairCommissions() - จับคู่ pair matching + สร้าง commission
                $binaryService->calculateBinaryCommissions($member, $order, $pvData);

                Log::info('Binary commissions calculated via MlmBinaryService', [
                    'member_id' => $member->id,
                    'order_id' => $order->id,
                    'pv' => $pv,
                ]);
            }
        } else {
            // Log เมื่อ transactionType ไม่ใช่ order/purchase เพื่อให้ admin ทราบ
            Log::info('Binary commission skipped - unsupported transaction type', [
                'member_id' => $member->id,
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'pv' => $pv,
            ]);
        }

        // MlmBinaryService สร้าง MlmCommission records โดยตรงแล้ว
        // ไม่ต้อง return ใน array เพราะจะถูก save ซ้ำใน calculateCommissionsWithRollup()
        return [];
    }

    /**
     * Check if Unilevel is enabled
     */
    protected function isUnilevelEnabled(): bool
    {
        return MlmGlobalSetting::get('unilevel_enabled', true);
    }

    /**
     * Check if Binary is enabled
     */
    protected function isBinaryEnabled(): bool
    {
        return MlmGlobalSetting::get('binary_enabled', false);
    }

    /**
     * คำนวณและจ่ายค่าแนะนำตรงสำหรับออเดอร์
     *
     * ค่าแนะนำตรงเป็นคนละส่วนกับ PV Commission ของ Unilevel/Binary:
     * - จ่ายให้ผู้แนะนำตรงจริงๆ (original_sponsor)
     * - ไม่ใช่ unilevel_sponsor ซึ่งอาจเปลี่ยนไปจากการ spillover
     *
     * @param Order $order ออเดอร์ที่ชำระแล้ว
     * @return MlmCommission|null
     */
    public function calculateDirectReferralBonus(Order $order): ?MlmCommission
    {
        $referralService = new MlmReferralBonusService();
        return $referralService->calculateReferralBonus($order);
    }

    /**
     * คำนวณค่าคอมมิชชันทั้งหมดสำหรับออเดอร์ (รวมค่าแนะนำตรง, Unilevel, Binary)
     *
     * เรียกใช้หลังจากออเดอร์ได้รับชำระเงินแล้ว
     *
     * @param Order $order ออเดอร์ที่ชำระแล้ว
     * @param array $pvData ข้อมูล PV ['total_pv' => float]
     * @return array ['direct_referral' => ?MlmCommission, 'unilevel' => array, 'binary' => array]
     */
    /**
     * แก้ Bug #14: ลบ nested transaction (calculateCommissionsWithRollup มี transaction ภายในแล้ว)
     * แก้ Bug #15: เพิ่ม duplicate commission guard ป้องกันการจ่ายซ้ำ
     */
    public function processOrderCommissions(Order $order, array $pvData): array
    {
        $result = [
            'direct_referral' => null,
            'unilevel' => [],
            'binary' => [],
        ];

        // หา MlmMember ของผู้ซื้อ
        $buyerMember = MlmMember::where('user_id', $order->user_id)->first();

        if (!$buyerMember) {
            Log::debug('Buyer is not MLM member, skipping commission calculation', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);
            return $result;
        }

        // แก้ Bug #15: ตรวจสอบว่าออเดอร์นี้คำนวณ commission ไปแล้วหรือยัง
        $existingCommission = MlmCommission::where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->whereIn('type', ['unilevel_direct', 'unilevel_indirect', 'unilevel_rollup', 'binary_pair'])
            ->exists();

        if ($existingCommission) {
            Log::warning('Order commissions already calculated, skipping', [
                'order_id' => $order->id,
            ]);
            return $result;
        }

        try {
            // แก้ Bug #14: ใช้ single transaction ไม่ซ้อน
            // calculateCommissionsWithRollup มี transaction ภายในแล้ว
            // ดังนั้นเราไม่ wrap อีกชั้น

            // 1. คำนวณค่าแนะนำตรง (Direct Referral Bonus)
            $result['direct_referral'] = $this->calculateDirectReferralBonus($order);

            // 2. คำนวณ Unilevel + Binary Commission (ถ้ามี PV)
            if (($pvData['total_pv'] ?? 0) > 0) {
                $pvCommissions = $this->calculateCommissionsWithRollup(
                    $buyerMember,
                    $pvData['total_pv'],
                    'order',
                    $order->id
                );

                if ($pvCommissions['success']) {
                    // แยก commissions ตามประเภท
                    foreach ($pvCommissions['commissions'] as $commission) {
                        $type = $commission['type'] ?? 'unknown';
                        if (str_starts_with($type, 'unilevel') || str_starts_with($type, 'pool')) {
                            $result['unilevel'][] = $commission;
                        } elseif (str_starts_with($type, 'binary')) {
                            $result['binary'][] = $commission;
                        }
                    }
                }
            }

            Log::info('Order commissions processed successfully', [
                'order_id' => $order->id,
                'has_direct_referral' => $result['direct_referral'] !== null,
                'unilevel_count' => count($result['unilevel']),
                'binary_count' => count($result['binary']),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process order commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * แปลง transactionType เป็น source_type (fully qualified class name)
     *
     * ใช้สำหรับบันทึกใน commission records เพื่อให้ duplicate guard ทำงานได้ถูกต้อง
     *
     * @param string $transactionType ประเภทธุรกรรม ('order', 'purchase', etc.)
     * @return string|null Fully qualified class name หรือ null
     */
    protected function resolveSourceType(string $transactionType): ?string
    {
        return match ($transactionType) {
            'order', 'purchase' => Order::class,
            default => null,
        };
    }

    /**
     * ดึงสถานะรักษายอดของสมาชิก (delegate ไป MlmRetentionHelper)
     *
     * @param MlmMember $member
     * @return array
     */
    public function getMemberRetentionStatus(MlmMember $member): array
    {
        return MlmRetentionHelper::getRetentionStatus($member);
    }
}
