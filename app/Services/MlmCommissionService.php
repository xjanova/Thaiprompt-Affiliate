<?php

namespace App\Services;

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
     * Calculate and distribute commissions with roll-up logic
     */
    public function calculateCommissionsWithRollup(MlmMember $member, float $pv, string $transactionType = 'purchase', $transactionId = null)
    {
        DB::beginTransaction();

        try {
            $commissions = [];

            // Get global settings
            $rollupEnabled = MlmGlobalSetting::get('rollup_enabled', false);
            $preventDuplicateRollup = MlmGlobalSetting::get('rollup_prevent_duplicate', true);
            $rollupMaxLevels = MlmGlobalSetting::get('rollup_max_levels', 10);

            // Calculate Unilevel commissions with roll-up
            if ($this->isUnilevelEnabled()) {
                $unilevelCommissions = $this->calculateUnilevelWithRollup(
                    $member,
                    $pv,
                    $transactionType,
                    $transactionId,
                    $rollupEnabled,
                    $preventDuplicateRollup,
                    $rollupMaxLevels
                );
                $commissions = array_merge($commissions, $unilevelCommissions);
            }

            // Calculate Binary commissions (no roll-up for binary)
            if ($this->isBinaryEnabled()) {
                $binaryCommissions = $this->calculateBinaryCommissions($member, $pv, $transactionType, $transactionId);
                $commissions = array_merge($commissions, $binaryCommissions);
            }

            // Save all commissions
            foreach ($commissions as $commission) {
                MlmCommission::create($commission);
            }

            DB::commit();

            return [
                'success' => true,
                'commissions' => $commissions,
                'total_amount' => array_sum(array_column($commissions, 'amount')),
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
     */
    protected function calculateUnilevelWithRollup(
        MlmMember $member,
        float $pv,
        string $transactionType,
        $transactionId,
        bool $rollupEnabled,
        bool $preventDuplicateRollup,
        int $maxLevels
    ) {
        $commissions = [];
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        if (empty($unilevelLevels)) {
            return $commissions;
        }

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
            $isActive = $this->isMemberActive($sponsor);

            // Get level configuration
            $levelConfig = collect($unilevelLevels)->firstWhere('level', $currentLevel);

            if (!$levelConfig) {
                break;
            }

            $percentage = $levelConfig['percentage'] ?? 0;

            if ($percentage <= 0) {
                $currentMember = $sponsor;
                $currentLevel++;
                continue;
            }

            // Calculate commission amount
            $commissionAmount = ($pv * $percentage) / 100;

            // Apply constraints
            $maxPerLevel = MlmGlobalSetting::get('unilevel_max_commission_per_level', null);
            if ($maxPerLevel && $commissionAmount > $maxPerLevel) {
                $commissionAmount = $maxPerLevel;
            }

            // Roll-up logic
            if ($rollupEnabled && !$isActive) {
                // Member is inactive, apply roll-up
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

                // Find next active upline พร้อมสร้าง chain
                $rollupResult = $this->findNextActiveUplineWithChain(
                    $sponsor,
                    $maxLevels - $currentLevel,
                    $rollupTracker,
                    $preventDuplicateRollup,
                    $adminMember
                );

                $rollupSponsor = $rollupResult['sponsor'];
                $rollupChain = array_merge($rollupChain, $rollupResult['chain']);

                if ($rollupSponsor) {
                    // Mark this upline as having received roll-up
                    $rollupTracker[$rollupSponsor->id] = true;

                    $commissions[] = [
                        'mlm_member_id' => $rollupSponsor->id,
                        'mlm_plan_id' => $member->mlm_plan_id,
                        'user_id' => $rollupSponsor->user_id,
                        'from_member_id' => $member->id,
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
                        ]),
                        'created_at' => now(),
                    ];

                    Log::info("Roll-up commission created with chain tracking", [
                        'recipient_id' => $rollupSponsor->id,
                        'recipient_code' => $rollupSponsor->member_code,
                        'amount' => $commissionAmount,
                        'rolled_from' => $sponsor->id,
                        'chain_length' => count($rollupChain),
                    ]);
                }

            } else {
                // Normal commission (member is active)
                $commissions[] = [
                    'mlm_member_id' => $sponsor->id,
                    'mlm_plan_id' => $member->mlm_plan_id,
                    'user_id' => $sponsor->user_id,
                    'from_member_id' => $member->id,
                    'type' => 'unilevel',
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
     * @param MlmMember $member
     * @param int $maxLevelsToSearch
     * @param array $rollupTracker
     * @param bool $preventDuplicate
     * @param MlmMember|null $adminMember
     * @return array ['sponsor' => MlmMember|null, 'chain' => array]
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

        while ($levelsSearched < $maxLevelsToSearch && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (!$sponsor) {
                break;
            }

            $isActive = $this->isMemberActive($sponsor);
            $alreadyReceived = $preventDuplicate && isset($rollupTracker[$sponsor->id]);

            if ($isActive && !$alreadyReceived) {
                // พบ upline ที่ active และยังไม่ได้รับ rollup
                return ['sponsor' => $sponsor, 'chain' => $chain];
            }

            // เพิ่มเข้า chain
            $chain[] = [
                'member_id' => $sponsor->id,
                'member_code' => $sponsor->member_code,
                'level' => $member->unilevel_level + $levelsSearched + 1,
                'reason' => $alreadyReceived ? 'already_received' : 'inactive',
                'tree_type' => 'unilevel',
            ];

            $currentMember = $sponsor;
            $levelsSearched++;
        }

        // ถ้าหาไม่เจอ → rollup ไป Admin (ID 1) ถ้ามี
        if ($adminMember && !isset($rollupTracker[$adminMember->id])) {
            $chain[] = [
                'member_id' => $adminMember->id,
                'member_code' => $adminMember->member_code,
                'level' => 0,
                'reason' => 'final_rollup_to_admin',
                'tree_type' => 'unilevel',
            ];
            return ['sponsor' => $adminMember, 'chain' => $chain];
        }

        return ['sponsor' => null, 'chain' => $chain];
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
     * Check if a member is active (maintaining monthly volume)
     */
    protected function isMemberActive(MlmMember $member): bool
    {
        $requiredMonthlyPv = MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $graceDays = MlmGlobalSetting::get('volume_retention_grace_days', 7);

        // Get member's PV for current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyPv = $member->commissions()
            ->where('created_at', '>=', $startOfMonth)
            ->sum('pv_amount');

        // Check if member meets monthly requirement
        if ($monthlyPv >= $requiredMonthlyPv) {
            return true;
        }

        // Check if member is in grace period
        $lastCommissionDate = $member->commissions()
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        if ($lastCommissionDate) {
            $daysSinceLastCommission = Carbon::parse($lastCommissionDate)->diffInDays(now());

            if ($daysSinceLastCommission <= $graceDays) {
                return true; // In grace period
            }
        }

        return false;
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

            if ($this->isMemberActive($sponsor)) {
                return $sponsor;
            }

            $currentMember = $sponsor;
            $levelsSearched++;
        }

        return null;
    }

    /**
     * Calculate Binary commissions (no roll-up for binary)
     */
    protected function calculateBinaryCommissions(MlmMember $member, float $pv, string $transactionType, $transactionId)
    {
        // Binary commission calculation logic
        // This is simplified - implement your full binary logic here
        $commissions = [];

        $binaryEnabled = MlmGlobalSetting::get('binary_enabled', false);

        if (!$binaryEnabled) {
            return $commissions;
        }

        // Add binary logic here (matching, pairing, etc.)
        // This is a placeholder

        return $commissions;
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

        try {
            DB::beginTransaction();

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
                        if (str_starts_with($type, 'unilevel')) {
                            $result['unilevel'][] = $commission;
                        } elseif (str_starts_with($type, 'binary')) {
                            $result['binary'][] = $commission;
                        }
                    }
                }
            }

            DB::commit();

            Log::info('Order commissions processed successfully', [
                'order_id' => $order->id,
                'has_direct_referral' => $result['direct_referral'] !== null,
                'unilevel_count' => count($result['unilevel']),
                'binary_count' => count($result['binary']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process order commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Get member's retention status
     */
    public function getMemberRetentionStatus(MlmMember $member): array
    {
        $requiredMonthlyPv = MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $graceDays = MlmGlobalSetting::get('volume_retention_grace_days', 7);

        $startOfMonth = Carbon::now()->startOfMonth();
        $monthlyPv = $member->commissions()
            ->where('created_at', '>=', $startOfMonth)
            ->sum('pv_amount');

        $lastCommissionDate = $member->commissions()
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        $daysSinceLastCommission = $lastCommissionDate
            ? Carbon::parse($lastCommissionDate)->diffInDays(now())
            : null;

        $status = 'active';
        $color = 'green';

        if ($monthlyPv < $requiredMonthlyPv) {
            if ($daysSinceLastCommission && $daysSinceLastCommission <= $graceDays) {
                $status = 'grace_period';
                $color = 'yellow';
            } else {
                $status = 'inactive';
                $color = 'red';
            }
        }

        return [
            'status' => $status,
            'color' => $color,
            'monthly_pv' => $monthlyPv,
            'required_pv' => $requiredMonthlyPv,
            'days_since_last_commission' => $daysSinceLastCommission,
            'grace_days' => $graceDays,
            'is_active' => $this->isMemberActive($member),
        ];
    }
}
