<?php

namespace App\Services;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *
     * @param  float|null  $poolCollected  เงินที่ออเดอร์เก็บเข้า mlm_pool จริง (audit F7) — null = ใช้ budget จาก PV อย่างเดียว
     */
    public function calculateCommissionsWithRollup(MlmMember $member, float $pv, string $transactionType = 'purchase', $transactionId = null, bool $disableRollup = false, ?float $poolCollected = null)
    {
        DB::beginTransaction();

        try {
            $commissions = [];

            // Reset tracking per transaction
            $this->rollupCountPerMember = [];
            $this->pooledRollupAmount = 0;

            // Get global settings
            // ถ้า disableRollup = true → บังคับปิด rollup (ใช้สำหรับดูดวงที่ไม่ต้อง roll up)
            $rollupEnabled = $disableRollup ? false : MlmGlobalSetting::get('rollup_enabled', false);
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

            // ⚠️ Overpay Protection - ป้องกันจ่าย commission เกินสัดส่วนที่ตั้งค่าไว้
            // ใช้ OverpayProtectionService สำหรับตรวจสอบ runtime
            if (! empty($commissions)) {
                $totalCommission = array_sum(array_column($commissions, 'commission_amount'));

                // 🛡️ F7: ออเดอร์ที่รู้ยอดเงินเข้ากองจริง → เพดาน = min(budget จาก PV, เงินที่เก็บได้จริง)
                $overpayCheck = $poolCollected === null
                    ? OverpayProtectionService::checkRuntimeOverpay($pv, $totalCommission)
                    : $this->checkAgainstOrderBudget(OverpayProtectionService::orderBudget($pv, $poolCollected), $totalCommission);

                if (! $overpayCheck['allowed']) {
                    // ลดสัดส่วนทุก commission ให้รวมแล้วไม่เกิน cap
                    $ratio = $overpayCheck['ratio'];
                    // ทางเดิม (ไม่รู้เงินเข้ากอง) ratio ถูกปัดปกติ 4 ตำแหน่ง อาจปัดขึ้น → คิดใหม่แบบปัดลง
                    if ($poolCollected === null && $totalCommission > 0) {
                        $ratio = floor(min(1.0, (float) $overpayCheck['max_allowed'] / $totalCommission) * 10000) / 10000;
                    }

                    foreach ($commissions as &$comm) {
                        $originalAmount = $comm['commission_amount'];
                        // budget ของออเดอร์ (F7) ปัดลงเป็นสตางค์ — ผลรวมต้องไม่เกินเงินที่เก็บได้จริงแม้ 1 สตางค์
                        // ทั้งสองทางปัดลงเป็นสตางค์ (ปัดปกติทำให้ผลรวมเกินเพดานได้ 0.01–0.0x บาท)
                        $comm['commission_amount'] = floor(round($originalAmount * $ratio * 100, 6)) / 100;
                        $comm['calculation_details'] = json_encode(
                            array_merge(
                                json_decode($comm['calculation_details'] ?? '{}', true) ?? [],
                                [
                                    'overpay_protection' => true,
                                    'original_amount' => $originalAmount,
                                    'scale_ratio' => $ratio,
                                    'max_allowed' => $overpayCheck['max_allowed'],
                                ]
                            )
                        );
                    }
                    unset($comm);

                    Log::warning('Overpay protection triggered - commissions scaled down', [
                        'member_id' => $member->id,
                        'pv' => $pv,
                        'original_total' => $totalCommission,
                        'max_allowed' => $overpayCheck['max_allowed'],
                        'excess' => $overpayCheck['excess'],
                        'ratio' => $ratio,
                        'message' => $overpayCheck['message'],
                    ]);
                }
            }

            // Save all commissions
            foreach ($commissions as $commission) {
                MlmCommission::create($commission);
            }

            // Binary (ไม่มี roll-up) — คำนวณหลังบันทึก unilevel แล้ว
            // 🛡️ F1: binary ใช้ได้เฉพาะ budget ที่เหลือของออเดอร์ (หักค่าแนะนำ + unilevel ที่บันทึกแล้ว)
            //    เดิมสร้างแถวคู่ตรงๆ นอกตัวกันจ่ายเกิน จึงจ่าย 100,000 จากออเดอร์ที่เก็บเข้ากองได้ 500
            //    แถว binary ถูกบันทึกใน MlmBinaryService แล้ว จึงไม่รวมใน loop บันทึกด้านบน
            if ($this->isBinaryEnabled()) {
                $commissions = array_merge(
                    $commissions,
                    $this->calculateBinaryCommissions($member, $pv, $transactionType, $transactionId, $poolCollected)
                );
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
                'trace' => $e->getTraceAsString(),
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
     * 3. หาผู้รับไม่ได้ → เงินอยู่ใน mlm_pool เป็นแถว pooled ไม่มีเจ้าของ (F8 / M4 — เดิมไปที่ Admin ID 1)
     * 4. บันทึก rollup_chain เพื่อติดตามที่มาที่ไป
     *
     * @param  MlmMember  $member  สมาชิกผู้ซื้อ
     * @param  float  $pv  จำนวน PV
     * @param  string  $transactionType  ประเภทธุรกรรม
     * @param  mixed  $transactionId  ID ของธุรกรรม
     * @param  bool  $rollupEnabled  เปิดระบบ rollup หรือไม่
     * @param  bool  $preventDuplicateRollup  ป้องกันจ่าย rollup ซ้ำ
     * @param  int  $maxLevels  จำนวนชั้นสูงสุดที่จ่ายคอมมิชชั่น (unilevel_max_depth)
     * @param  int  $rollupSearchDepth  ระยะค้นหา rollup สูงสุด (rollup_max_levels)
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

        // 🛡️ F8 / M4: ไม่มี "admin member #1" ปลายทางแล้ว — เงินที่หาผู้รับไม่ได้อยู่ในกอง (แถว pooled ไม่มีเจ้าของ)
        //    เดิม MlmMember::find(1) ได้เงินเป็นค่าคอมที่จ่ายได้ และถ้าไม่มี member #1 ตกไปที่ผู้ซื้อเอง

        // 🛡️ F6: เพดานต่อชั้น — ค่าตั้ง NULL (prod) = ไม่จำกัด (เดิมอ่านเป็น 0 → unilevel จ่าย 0 ทุกชั้น)
        $maxPerLevel = MlmGlobalSetting::positiveCap('unilevel_max_commission_per_level');

        $currentMember = $member;
        $currentLevel = 1;
        $rollupTracker = []; // Track who received roll-up commissions

        while ($currentLevel <= $maxLevels && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (! $sponsor) {
                break;
            }

            // Check if sponsor is active (maintaining volume)
            $isActive = MlmRetentionHelper::isMemberActive($sponsor);

            // Get level configuration
            $levelConfig = collect($unilevelLevels)->firstWhere('level', $currentLevel);

            if (! $levelConfig) {
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

            if ($maxPerLevel !== null && $commissionAmount > $maxPerLevel) {
                $commissionAmount = $maxPerLevel;
            }

            // ตรวจสอบสถานะ active ก่อนจ่าย commission
            // แก้ ROLLUP-1: คนไม่รักษายอดต้องถูกข้ามเสมอ ไม่ว่า rollup จะเปิดหรือปิด
            if (! $isActive) {
                if ($rollupEnabled) {
                    // Rollup เปิด: ม้วนขึ้นไปหาคน active ถัดไป
                    Log::info('Roll-up triggered for inactive member', [
                        'inactive_member_id' => $sponsor->id,
                        'level' => $currentLevel,
                        'amount' => $commissionAmount,
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
                        $preventDuplicateRollup
                    );

                    $rollupSponsor = $rollupResult['sponsor'];
                    $rollupChain = array_merge($rollupChain, $rollupResult['chain']);
                    $toPool = $rollupResult['to_pool'] ?? false;

                    if ($toPool) {
                        // เงินชั้นนี้ไม่มีผู้รับ → อยู่ใน mlm_pool
                        $this->pooledRollupAmount += $commissionAmount;

                        // 🛡️ F8 / M4: บันทึกเป็น ledger ของกอง — ไม่มีเจ้าของ (null) และสถานะ pooled อนุมัติ/จ่ายไม่ได้
                        //    เดิมเป็นแถว pending ของ member #1 (ไม่มี member #1 = ของผู้ซื้อ) → กดจ่ายแล้วเงินกองเข้ากระเป๋าคน
                        //    แถวนี้ยังนับใน budget ของออเดอร์ (เงินถูกกันไว้ในกอง) และเป็นตัวกันคำนวณซ้ำ
                        $commissions[] = [
                            'mlm_member_id' => null,
                            'mlm_plan_id' => $member->mlm_plan_id,
                            'user_id' => null,
                            'from_member_id' => $member->id,
                            // แก้ Bug: เพิ่ม source_type/source_id สำหรับ duplicate guard
                            'source_type' => $sourceType,
                            'source_id' => $sourceId,
                            'type' => 'pool_bonus',
                            'level' => $currentLevel,
                            'commission_amount' => $commissionAmount,
                            'pv_amount' => $pv,
                            'percentage' => $percentage,
                            'status' => MlmCommission::STATUS_POOLED,
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

                        Log::info('Roll-up commission sent to Pool Bonus', [
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

                        Log::info('Roll-up commission created with chain tracking', [
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
                    Log::info('Skipped inactive member (rollup disabled)', [
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
     * 2. หาไม่เจอ → เงินอยู่ในกอง (to_pool) เสมอ
     *
     * 🛡️ F8 / M4: ลบปลายทาง "admin member #1" — เดิมเมื่อปิด rollup_to_pool_enabled เงินไปเป็น unilevel_rollup
     *    ที่จ่ายได้ของ member #1 · ตอนนี้ไม่ว่าสวิตช์จะเป็นอะไร เงินที่ไม่มีผู้รับอยู่ใน mlm_pool ไม่เข้ากระเป๋าคน
     *    (สวิตช์บอกแค่ว่าเงินนี้กันไว้ให้ Pool Bonus หรือเป็นรายได้บริษัท — บันทึกใน chain)
     *
     * @return array ['sponsor' => MlmMember|null, 'chain' => array, 'to_pool' => bool]
     */
    protected function findNextActiveUplineWithChain(
        MlmMember $member,
        int $maxLevelsToSearch,
        array $rollupTracker,
        bool $preventDuplicate
    ): array {
        $chain = [];
        $currentMember = $member;
        $levelsSearched = 0;

        // ดึง settings ใหม่
        $maxPerMember = MlmGlobalSetting::get('rollup_max_per_member', 1);
        $toPoolEnabled = MlmGlobalSetting::get('rollup_to_pool_enabled', true);

        while ($levelsSearched < $maxLevelsToSearch && $currentMember->sponsor_id) {
            $sponsor = $currentMember->sponsor;

            if (! $sponsor) {
                break;
            }

            $isActive = MlmRetentionHelper::isMemberActive($sponsor);
            $alreadyReceived = $preventDuplicate && isset($rollupTracker[$sponsor->id]);

            // ตรวจสอบว่าคนนี้ได้รับ rollup เกินกำหนดหรือยัง
            $memberRollupCount = $this->rollupCountPerMember[$sponsor->id] ?? 0;
            $exceedsMaxRollup = $memberRollupCount >= $maxPerMember;

            if ($isActive && ! $alreadyReceived && ! $exceedsMaxRollup) {
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

        // ถ้าหาไม่เจอ upline ที่เหมาะสม → เงินอยู่ในกอง (ไม่มี fallback ไป admin / ผู้ซื้อ)
        $chain[] = [
            'member_id' => null,
            'member_code' => 'POOL',
            'level' => 0,
            'reason' => $toPoolEnabled ? 'sent_to_pool_bonus' : 'kept_in_pool_no_recipient',
            'tree_type' => 'pool',
        ];

        Log::info('Rollup commission kept in MLM pool (no eligible recipient)', [
            'original_member_id' => $member->id,
            'chain_length' => count($chain),
            'reserved_for_pool_bonus' => (bool) $toPoolEnabled,
        ]);

        return [
            'sponsor' => null,
            'chain' => $chain,
            'to_pool' => true,
        ];
    }

    /**
     * สร้าง notes สำหรับ rollup commission
     */
    protected function buildRollupNotes(array $chain, MlmMember $originalInactive): string
    {
        $skippedCount = count($chain);
        $skippedCodes = array_map(fn ($item) => $item['member_code'], $chain);

        return sprintf(
            'Roll-up จากสมาชิก #%s (ไม่ active) ข้าม %d คน: %s',
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

            if (! $sponsor) {
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
     * 1. ล็อกแถว upline ในผัง binary แล้วลง PV ขา (เฉพาะคนที่ active)
     * 2. จับคู่ตาม pair unit + เพดานรายวัน + budget ที่เหลือของออเดอร์ แล้วสร้าง commission
     *
     * หมายเหตุ: MlmBinaryService บันทึก MlmCommission เองแล้ว — ค่าที่คืนเป็นสำเนา array
     * สำหรับรวมยอด/รายงานเท่านั้น ห้ามนำไป create ซ้ำ
     *
     * @param  MlmMember  $member  สมาชิกผู้ซื้อ
     * @param  float  $pv  จำนวน PV
     * @param  string  $transactionType  ประเภทธุรกรรม ('order', 'purchase', etc.)
     * @param  mixed  $transactionId  ID ของธุรกรรม (order_id)
     * @param  float|null  $poolCollected  เงินที่ออเดอร์เก็บเข้ากองจริง (F7) — null = budget จาก PV อย่างเดียว
     * @return array แถว binary_pair ที่บันทึกแล้ว (รูปแบบ array)
     */
    protected function calculateBinaryCommissions(MlmMember $member, float $pv, string $transactionType, $transactionId, ?float $poolCollected = null)
    {
        $binaryEnabled = MlmGlobalSetting::get('binary_enabled', false);

        if (! $binaryEnabled) {
            return [];
        }

        // แก้ Warning: รองรับ transactionType ทั้ง 'order' และ 'purchase' (เป็น Order เหมือนกัน)
        if (in_array($transactionType, ['order', 'purchase']) && $transactionId) {
            $order = Order::find($transactionId);

            if ($order) {
                // budget = null → MlmBinaryService คิดจาก budget ต่อออเดอร์ − คอมของออเดอร์ที่บันทึกแล้ว
                $pvData = ['total_pv' => $pv];
                if ($poolCollected !== null) {
                    $pvData['pv_amount_thb'] = $poolCollected;
                }
                $created = (new MlmBinaryService)->calculateBinaryCommissions($member, $order, $pvData);

                Log::info('Binary commissions calculated via MlmBinaryService', [
                    'member_id' => $member->id,
                    'order_id' => $order->id,
                    'pv' => $pv,
                    'pair_rows' => count($created),
                ]);

                return array_map(fn (MlmCommission $c) => $c->only([
                    'id', 'mlm_member_id', 'user_id', 'type', 'pairs_count', 'pv_amount', 'commission_amount', 'status',
                ]), $created);
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

        return [];
    }

    /**
     * เทียบยอดคอมกับ budget ของออเดอร์ (รูปแบบผลเหมือน OverpayProtectionService::checkRuntimeOverpay)
     *
     * budget 0 = ออเดอร์ไม่มีเงินเข้ากอง → ratio 0 (ไม่จ่ายจากเงินของออเดอร์อื่น)
     */
    protected function checkAgainstOrderBudget(float $budget, float $totalCommission): array
    {
        $allowed = $totalCommission <= $budget + 0.000001;
        $ratio = $totalCommission > 0 ? max(0.0, min(1.0, $budget / $totalCommission)) : 1.0;

        return [
            'allowed' => $allowed,
            'max_allowed' => round($budget, 2),
            'total_commission' => round($totalCommission, 2),
            'excess' => round(max(0.0, $totalCommission - $budget), 2),
            // ปัดลง 4 ตำแหน่ง — ปัดขึ้นอาจทำให้ผลรวมหลังคูณเกิน budget
            'ratio' => floor($ratio * 10000) / 10000,
            'message' => $allowed ? 'ไม่เกิน budget ของออเดอร์' : 'เกินเงินที่ออเดอร์เก็บเข้ากองจริง',
        ];
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
     * @param  Order  $order  ออเดอร์ที่ชำระแล้ว
     */
    public function calculateDirectReferralBonus(Order $order): ?MlmCommission
    {
        $referralService = new MlmReferralBonusService;

        return $referralService->calculateReferralBonus($order);
    }

    /**
     * คำนวณค่าคอมมิชชันทั้งหมดสำหรับออเดอร์ (รวมค่าแนะนำตรง, Unilevel, Binary)
     *
     * เรียกใช้หลังจากออเดอร์ได้รับชำระเงินแล้ว
     *
     * @param  Order  $order  ออเดอร์ที่ชำระแล้ว
     * @param  array  $pvData  ข้อมูล PV ['total_pv' => float]
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

        // 🪙 M15: ซื้อด้วย Coins ไม่ได้ PV/คอม — กันที่ engine ด้วย (ผู้เรียกบางรายส่ง PV ที่คิดเองมา)
        if ($order->isPaidWithCoins()) {
            Log::info('Order paid with coins earns no MLM commission (M15)', ['order_id' => $order->id]);

            return $result;
        }

        // หา MlmMember ของผู้ซื้อ
        $buyerMember = MlmMember::where('user_id', $order->user_id)->first();

        if (! $buyerMember) {
            Log::debug('Buyer is not MLM member, skipping commission calculation', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);

            return $result;
        }

        // แก้ Bug #15: ตรวจสอบว่าออเดอร์นี้คำนวณ commission ไปแล้วหรือยัง
        $existingCommission = MlmCommission::where('source_type', Order::class)
            ->where('source_id', $order->id)
            // 🛡️ F8: นับแถว pool_bonus (เงินกองของออเดอร์นี้) ด้วย — เดิมออเดอร์ที่ทุกแถวเป็นเงินกอง เรียกซ้ำได้ +128.40 ต่อครั้ง
            ->whereIn('type', ['unilevel_direct', 'unilevel_indirect', 'unilevel_rollup', 'binary_pair', 'pool_bonus'])
            ->exists();

        if ($existingCommission) {
            Log::warning('Order commissions already calculated, skipping', [
                'order_id' => $order->id,
            ]);

            return $result;
        }

        // 🛡️ budget ต่อออเดอร์ (audit F7) = min(PV × อัตรา × max %, เงินที่ออเดอร์เก็บเข้า mlm_pool จริง)
        //    pv_amount_thb มาจาก OrderDistributionService::pvData() — ผู้เรียกที่ไม่ส่งมา ใช้สูตร PV อย่างเดียว (แบบเดิม)
        $totalPv = round((float) ($pvData['total_pv'] ?? 0), 2);
        $poolCollected = array_key_exists('pv_amount_thb', $pvData) ? round((float) $pvData['pv_amount_thb'], 2) : null;
        $orderBudget = OverpayProtectionService::orderBudget($totalPv, $poolCollected);

        try {
            // แก้ Bug #14: ใช้ single transaction ไม่ซ้อน
            // calculateCommissionsWithRollup มี transaction ภายในแล้ว
            // ดังนั้นเราไม่ wrap อีกชั้น

            // 1. คำนวณค่าแนะนำตรง (Direct Referral Bonus)
            // 🐛 (2026-09-25) audit G12: แยก try ของค่าแนะนำออกมา — เดิมถ้าค่าแนะนำ insert ล้ม
            //    (type 'direct_referral' ไม่อยู่ใน enum) จะกระโดดไป catch ด้านล่าง ทำให้ unilevel/binary ไม่ถูกคำนวณเลย
            // 🛡️ F5 / M3: จ่ายเฉพาะออเดอร์ที่มี PV > 0 และไม่เกิน budget ของออเดอร์
            //    เดิมออเดอร์ PV 0 (เก็บเข้ากอง 0) ก็ได้ 100 บาท → พออนุมัติ+จ่าย เงินออกจากกองของออเดอร์อื่น
            try {
                if ($totalPv <= 0 || $orderBudget <= 0) {
                    Log::info('Direct referral bonus skipped: order has no PV or no commission budget', [
                        'order_id' => $order->id,
                        'total_pv' => $totalPv,
                        'pool_collected' => $poolCollected,
                        'order_budget' => $orderBudget,
                    ]);
                } else {
                    $result['direct_referral'] = (new MlmReferralBonusService)->calculateReferralBonus($order, $orderBudget);
                }
            } catch (\Throwable $referralError) {
                Log::error('Direct referral bonus failed, continuing with PV commissions', [
                    'order_id' => $order->id,
                    'error' => $referralError->getMessage(),
                ]);
                $result['direct_referral'] = null;
            }

            // 2. คำนวณ Unilevel + Binary Commission (ถ้ามี PV)
            if ($totalPv > 0) {
                $pvCommissions = $this->calculateCommissionsWithRollup(
                    $buyerMember,
                    $totalPv,
                    'order',
                    $order->id,
                    false,
                    $poolCollected
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

            // ⚠️ Overpay Protection รวม: ตรวจสอบ referral + unilevel + binary ทั้งหมด
            // calculateCommissionsWithRollup มี overpay check ภายใน แต่ไม่รวม referral
            // ลำดับสิทธิ์ใน budget: unilevel → ค่าแนะนำตรง → binary (binary ใช้ได้เฉพาะที่เหลือหลังสองอย่างแรก)
            if ($result['direct_referral'] !== null) {
                $referralAmount = (float) $result['direct_referral']->commission_amount;
                $unilevelTotal = array_sum(array_column($result['unilevel'], 'commission_amount'));
                $binaryTotal = array_sum(array_column($result['binary'], 'commission_amount'));
                $grandTotal = $referralAmount + $unilevelTotal + $binaryTotal;

                if ($grandTotal > $orderBudget + 0.000001) {
                    $allowedReferral = floor(round(max(0, $orderBudget - $unilevelTotal - $binaryTotal) * 100, 6)) / 100;
                    $originalReferral = $referralAmount;

                    if ($allowedReferral < $referralAmount) {
                        if ($allowedReferral <= 0) {
                            // budget หมดที่ unilevel แล้ว → ยกเลิกแถว (สิทธิ์ออเดอร์แรกคืนให้ออเดอร์ถัดไป) พร้อมเหตุผล
                            $result['direct_referral']->update([
                                'status' => 'cancelled',
                                'notes' => $result['direct_referral']->notes
                                    .' | ยกเลิก: budget ของออเดอร์ (฿'.number_format($orderBudget, 2).') ถูกใช้ครบแล้ว',
                            ]);
                        } else {
                            // อัปเดท referral commission ให้ไม่เกิน budget
                            $result['direct_referral']->update([
                                'commission_amount' => round($allowedReferral, 2),
                                'notes' => $result['direct_referral']->notes
                                    .' | Overpay scaled: ฿'.number_format($originalReferral, 2)
                                    .' → ฿'.number_format($allowedReferral, 2),
                            ]);
                        }
                        $result['direct_referral']->refresh();

                        Log::warning('Overpay protection: referral bonus scaled down', [
                            'order_id' => $order->id,
                            'original_referral' => $originalReferral,
                            'allowed_referral' => $allowedReferral,
                            'max_budget' => $orderBudget,
                            'grand_total_before' => $grandTotal,
                            'grand_total_after' => $allowedReferral + $unilevelTotal + $binaryTotal,
                        ]);
                    }
                }
            }

            Log::info('Order commissions processed successfully', [
                'order_id' => $order->id,
                'has_direct_referral' => $result['direct_referral'] !== null,
                'unilevel_count' => count($result['unilevel']),
                'binary_count' => count($result['binary']),
            ]);

        } catch (\Throwable $e) {
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
     * @param  string  $transactionType  ประเภทธุรกรรม ('order', 'purchase', etc.)
     * @return string|null Fully qualified class name หรือ null
     */
    protected function resolveSourceType(string $transactionType): ?string
    {
        return match ($transactionType) {
            'order', 'purchase' => Order::class,
            'fortune_reading' => \App\Models\FortuneReading::class,
            // 🛡️ F20: คอมจากการจองบริการ (ฝั่งลูกค้า + ฝั่ง PV ผู้ให้บริการ) — เดิมได้ null ทำให้ตัวกันซ้ำ/รายงานมองไม่เห็นที่มา
            'service_booking', 'service_pv' => \App\Models\ServiceBooking::class,
            default => null,
        };
    }

    /**
     * ดึงสถานะรักษายอดของสมาชิก (delegate ไป MlmRetentionHelper)
     */
    public function getMemberRetentionStatus(MlmMember $member): array
    {
        return MlmRetentionHelper::getRetentionStatus($member);
    }
}
