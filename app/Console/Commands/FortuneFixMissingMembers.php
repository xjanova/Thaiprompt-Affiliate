<?php

namespace App\Console\Commands;

use App\Helpers\MlmRetentionHelper;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneCommissionService;
use App\Services\MlmBinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * แก้ไขข้อมูลย้อนหลัง — สร้าง MlmMember ให้คนที่จ่ายเงินดูดวงแต่ยังไม่มี
 * + จ่ายคอมมิชชั่นย้อนหลังที่ตกหล่น
 *
 * Logic:
 * 1. ถ้ามี FortuneReferral เชื่อมอยู่ → อยู่ใต้คนเชิญ
 * 2. ถ้าไม่มี referral → อยู่ใต้ admin (User ID 1)
 */
class FortuneFixMissingMembers extends Command
{
    protected $signature = 'fortune:fix-missing-members
                            {--dry-run : แสดงผลอย่างเดียว ไม่แก้ข้อมูลจริง}
                            {--with-commission : จ่ายคอมมิชชั่นย้อนหลังด้วย}';

    protected $description = 'สร้าง MlmMember ให้คนที่จ่ายเงินดูดวงแต่ยังไม่มี + จ่ายคอมย้อนหลัง';

    private int $membersCreated = 0;

    private int $commissionsDistributed = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $withCommission = $this->option('with-commission');

        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   🔧 Fortune Fix Missing Members                 ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN — ไม่แก้ข้อมูลจริง');
        }

        $this->newLine();

        // ===== STEP 1: หา Users ที่จ่ายเงินดูดวงแล้วแต่ไม่มี MlmMember =====
        $this->info('━━━ STEP 1: หา Users ที่จ่ายดูดวงแล้วแต่ยังไม่มี MlmMember ━━━');

        $usersWithoutMember = $this->findUsersWithoutMlmMember();

        if ($usersWithoutMember->isEmpty()) {
            $this->info('  ✅ ทุกคนมี MlmMember ครบแล้ว!');
        } else {
            $this->warn("  พบ {$usersWithoutMember->count()} คน ที่ยังไม่มี MlmMember");

            $rows = [];
            foreach ($usersWithoutMember as $user) {
                $readings = FortuneReading::where('user_id', $user->id)->where('is_paid', true)->count();
                $referral = $this->findReferralForUser($user);
                $sponsorName = $referral ? ($referral->referrerUser?->name ?? '?') : 'Admin (default)';

                $rows[] = [
                    $user->id,
                    mb_substr($user->name, 0, 20),
                    $readings,
                    $referral ? "#{$referral->id}" : '-',
                    mb_substr($sponsorName, 0, 20),
                ];
            }

            $this->table(['User ID', 'ชื่อ', 'จ่ายแล้ว(ครั้ง)', 'Referral', 'จะอยู่ใต้'], $rows);
        }

        $this->newLine();

        // ===== STEP 2: สร้าง MlmMember =====
        $this->info('━━━ STEP 2: สร้าง MlmMember ━━━');

        foreach ($usersWithoutMember as $user) {
            $this->createMlmMemberForUser($user, $dryRun);
        }

        $this->info("  สร้าง MlmMember: {$this->membersCreated} คน");
        $this->newLine();

        // ===== STEP 3: จ่ายคอมมิชชั่นย้อนหลัง =====
        if ($withCommission) {
            $this->info('━━━ STEP 3: จ่ายคอมมิชชั่นย้อนหลัง ━━━');
            $this->distributeRetroactiveCommissions($dryRun);
            $this->info("  จ่ายคอมมิชชั่น: {$this->commissionsDistributed} รายการ");
        }

        // ===== สรุป =====
        $this->newLine();
        $this->info('════════════════════════════════════════════════════');
        $this->info("  ✅ เสร็จสิ้น — สร้าง Member: {$this->membersCreated} | คอม: {$this->commissionsDistributed}");
        if ($dryRun) {
            $this->warn('  ⚠️  DRY RUN — ไม่ได้แก้ข้อมูลจริง ให้รันอีกครั้งโดยไม่ใส่ --dry-run');
        }
        $this->info('════════════════════════════════════════════════════');

        return 0;
    }

    /**
     * หา Users ที่จ่ายเงินดูดวงแล้วแต่ไม่มี MlmMember
     */
    private function findUsersWithoutMlmMember()
    {
        // หา user_id ทั้งหมดจาก FortuneReading ที่จ่ายเงินแล้ว
        $userIds = FortuneReading::whereNotNull('user_id')
            ->where('is_paid', true)
            ->distinct()
            ->pluck('user_id');

        // กรองเอาเฉพาะคนที่ไม่มี MlmMember
        $memberUserIds = MlmMember::whereIn('user_id', $userIds)->pluck('user_id');
        $missingIds = $userIds->diff($memberUserIds);

        return User::whereIn('id', $missingIds)->get();
    }

    /**
     * หา FortuneReferral ที่เชื่อมกับ User
     * ค้นหาจาก referred_user_id หรือ referred_line_user_id
     */
    private function findReferralForUser(User $user): ?FortuneReferral
    {
        // ค้นหาจาก referred_user_id
        $referral = FortuneReferral::where('referred_user_id', $user->id)
            ->whereNotNull('referrer_user_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($referral) {
            return $referral;
        }

        // ค้นหาจาก referred_line_user_id
        if ($user->line_user_id) {
            $referral = FortuneReferral::where('referred_line_user_id', $user->line_user_id)
                ->whereNotNull('referrer_user_id')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $referral;
    }

    /**
     * สร้าง MlmMember ให้ User
     * ถ้ามี referral → ใต้คนเชิญ, ถ้าไม่มี → ใต้ admin
     */
    private function createMlmMemberForUser(User $user, bool $dryRun): void
    {
        // เช็คว่ามี MlmMember แล้วหรือยัง (double check)
        if (MlmMember::where('user_id', $user->id)->exists()) {
            return;
        }

        // หา sponsor จาก referral หรือ fallback admin
        $referral = $this->findReferralForUser($user);
        $sponsor = null;

        if ($referral && $referral->referrer_user_id) {
            // มี referral → หา sponsor MlmMember ของคนเชิญ
            $sponsorUser = User::find($referral->referrer_user_id);
            if ($sponsorUser) {
                $sponsor = MlmMember::where('user_id', $sponsorUser->id)->first();

                // ถ้าคนเชิญก็ยังไม่มี MlmMember → สร้างให้คนเชิญก่อน (ใต้ admin)
                if (! $sponsor) {
                    $this->info("  → คนเชิญ {$sponsorUser->name} (ID:{$sponsorUser->id}) ก็ยังไม่มี MlmMember — สร้างให้ก่อน");

                    if (! $dryRun) {
                        $sponsor = $this->doCreateMlmMember($sponsorUser, $this->getAdminSponsor());

                        if ($sponsor) {
                            $this->membersCreated++;
                            $this->info("    ✅ สร้าง MlmMember ให้คนเชิญ: {$sponsorUser->name} → ใต้ Admin");
                        }
                    } else {
                        $this->info("    [DRY RUN] จะสร้าง MlmMember ให้คนเชิญ: {$sponsorUser->name} → ใต้ Admin");
                    }
                }
            }
        }

        // ถ้าหา sponsor ไม่ได้ → ใช้ admin
        if (! $sponsor) {
            $sponsor = $this->getAdminSponsor();
        }

        if (! $sponsor) {
            $this->error("  ❌ ไม่พบ Admin MlmMember — ข้าม {$user->name}");

            return;
        }

        $sponsorName = $sponsor->user?->name ?? "Member#{$sponsor->id}";

        if ($dryRun) {
            $this->info("  [DRY RUN] จะสร้าง MlmMember: {$user->name} (ID:{$user->id}) → ใต้ {$sponsorName}");

            return;
        }

        $member = $this->doCreateMlmMember($user, $sponsor);

        if ($member) {
            $this->membersCreated++;
            $this->info("  ✅ สร้าง MlmMember: {$user->name} (ID:{$user->id}) → ใต้ {$sponsorName} | Code: {$member->member_code}");

            // Mark referral as converted ถ้ามี
            if ($referral) {
                $referral->markAsConverted($user);
            }
        }
    }

    /**
     * สร้าง MlmMember จริง (ใช้ logic เดียวกับ FortuneAffiliateService::createMlmMember)
     */
    private function doCreateMlmMember(User $user, MlmMember $sponsor): ?MlmMember
    {
        try {
            // เช็คซ้ำ
            $existing = MlmMember::where('user_id', $user->id)->first();
            if ($existing) {
                return $existing;
            }

            $defaultPlan = MlmPlan::where('is_default', true)->first();
            if (! $defaultPlan) {
                $this->error('  ❌ ไม่พบ default MLM plan');

                return null;
            }

            $binaryService = app(MlmBinaryService::class);
            $placement = null;
            try {
                $placement = $binaryService->findPlacementPosition($sponsor);
            } catch (\Exception $e) {
                // binary placement อาจ error — ใช้ fallback
            }

            $binaryParentId = $placement['parent_id'] ?? $sponsor->id;
            $binaryPosition = $placement['position'] ?? 'left';

            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $defaultPlan->id,
                'unilevel_sponsor_id' => $sponsor->id,
                'unilevel_level' => ($sponsor->unilevel_level ?? 0) + 1,
                'unilevel_path' => trim(($sponsor->unilevel_path ?? '').'/'.$sponsor->id, '/'),
                'original_sponsor_id' => $sponsor->id,
                'binary_sponsor_id' => $sponsor->id,
                'binary_parent_id' => $binaryParentId,
                'binary_position' => $binaryPosition,
                'status' => 'active',
                'joined_at' => now(),
                'member_code' => MlmMember::generateMemberCode(),
                'is_qualified' => true,
            ]);

            $sponsor->increment('total_direct_referrals');

            $binaryParent = MlmMember::find($binaryParentId);
            if ($binaryParent) {
                if ($binaryPosition === 'left') {
                    $binaryParent->increment('left_leg_members');
                } else {
                    $binaryParent->increment('right_leg_members');
                }
            }

            // สร้าง Wallet ทันที
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
            );

            Log::info('FortuneFixMissingMembers: สร้าง MlmMember + Wallet สำเร็จ', [
                'user_id' => $user->id,
                'member_id' => $member->id,
                'sponsor_id' => $sponsor->id,
            ]);

            return $member;

        } catch (\Exception $e) {
            $this->error("  ❌ สร้าง MlmMember ล้มเหลว: {$user->name} — {$e->getMessage()}");
            Log::error('FortuneFixMissingMembers: error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * หา Admin MlmMember (Super Admin user_id = 1)
     * ถ้ายังไม่มี → สร้างให้เป็น root ของ MLM tree
     */
    private function getAdminSponsor(): ?MlmMember
    {
        $superAdmin = User::find(1);
        if (! $superAdmin) {
            $this->error('  ❌ ไม่พบ Super Admin (User ID 1)');

            return null;
        }

        $member = MlmMember::where('user_id', $superAdmin->id)->first();
        if ($member) {
            return $member;
        }

        // สร้าง Admin MlmMember เป็น root
        $this->warn('  ⚠️ Admin ยังไม่มี MlmMember — กำลังสร้างเป็น root...');

        $defaultPlan = MlmPlan::where('is_default', true)->first();
        if (! $defaultPlan) {
            $this->error('  ❌ ไม่พบ default MLM plan');

            return null;
        }

        // สร้าง member_code ที่ไม่ซ้ำ (ADMIN-0001 อาจถูกใช้ไปแล้ว)
        $memberCode = 'ADMIN-0001';
        if (MlmMember::where('member_code', $memberCode)->exists()) {
            $memberCode = 'ROOT-ADMIN-' . $superAdmin->id;
        }

        $member = MlmMember::create([
            'user_id' => $superAdmin->id,
            'mlm_plan_id' => $defaultPlan->id,
            'unilevel_sponsor_id' => null,
            'unilevel_level' => 0,
            'unilevel_path' => '',
            'original_sponsor_id' => null,
            'binary_sponsor_id' => null,
            'binary_parent_id' => null,
            'binary_position' => null,
            'status' => 'active',
            'joined_at' => now(),
            'member_code' => $memberCode,
            'is_qualified' => true,
        ]);

        $this->info("  ✅ สร้าง Admin MlmMember สำเร็จ: {$member->member_code} (ID: {$member->id})");

        return $member;
    }

    /**
     * จ่ายคอมมิชชั่นย้อนหลังสำหรับ readings ที่จ่ายเงินแล้วแต่ยังไม่มี FortuneCommission
     *
     * ⚠️ RETROACTIVE FIX: ข้าม active check เพราะเป็นการแก้ข้อมูลย้อนหลัง
     * sponsor อาจไม่ผ่าน retention check ในปัจจุบัน แต่สมควรได้รับคอมมิชชั่นจาก reading ที่เกิดขึ้นก่อนหน้า
     */
    private function distributeRetroactiveCommissions(bool $dryRun): void
    {
        $settings = FortuneTellingSetting::getSettings();

        if (! $settings->isFortuneAffiliateEnabled()) {
            $this->warn('  ⚠️ fortune_affiliate_enabled = false — ข้ามคอมมิชชั่น');

            return;
        }

        // หา readings ที่จ่ายเงินแล้ว มี user_id แต่ยังไม่มี FortuneCommission
        $readings = FortuneReading::whereNotNull('user_id')
            ->where('is_paid', true)
            ->where(function ($q) {
                $q->where('amount_paid', '>', 0);
            })
            ->whereNotIn('id', FortuneCommission::select('fortune_reading_id'))
            ->get();

        if ($readings->isEmpty()) {
            $this->info('  ✅ ไม่มี reading ที่ค้างจ่ายคอมมิชชั่น');

            return;
        }

        $this->warn("  พบ {$readings->count()} readings ที่ยังไม่จ่ายคอมมิชชั่น");

        $readingPrice = (float) ($settings->fortune_reading_price ?? 39);

        foreach ($readings as $reading) {
            $mlmMember = MlmMember::where('user_id', $reading->user_id)->first();

            if (! $mlmMember) {
                $this->warn("    Reading #{$reading->id}: user #{$reading->user_id} ยังไม่มี MlmMember — ข้าม");

                continue;
            }

            if (! $mlmMember->unilevel_sponsor_id) {
                $this->warn("    Reading #{$reading->id}: member #{$mlmMember->id} ไม่มี sponsor — ข้าม");

                continue;
            }

            $sponsor = MlmMember::with('user')->find($mlmMember->unilevel_sponsor_id);
            if (! $sponsor || ! $sponsor->user) {
                $this->warn("    Reading #{$reading->id}: sponsor ไม่พบ — ข้าม");

                continue;
            }

            $sponsorName = $sponsor->user->name;

            if ($dryRun) {
                $this->info("    [DRY RUN] Reading #{$reading->id}: จ่ายคอม → L1 sponsor: {$sponsorName}");

                continue;
            }

            try {
                // จ่าย L1 ตรง (ข้าม active check — retroactive fix)
                // 📦 (2026-07-15) ส่ง reading_type ด้วย — ไม่งั้นโหมดแยกแพคเกจจะจ่ายชดเชยผิดอัตรา
                $l1Amount = $settings->getFortuneLevel1Amount($readingPrice, $reading->reading_type);
                if ($l1Amount > 0) {
                    $commissionService = app(FortuneCommissionService::class);
                    $commissionService->forceCreateCommission(
                        $reading, $sponsor, $mlmMember, 1, $l1Amount, $readingPrice, $settings
                    );
                    $this->commissionsDistributed++;
                    $this->info("    ✅ Reading #{$reading->id}: L1 จ่าย {$l1Amount} บาท → {$sponsorName}");
                }

                // จ่าย L2 (ถ้ามี grandparent + แพคเกจนี้เปิดชั้นหลาน)
                if ($sponsor->unilevel_sponsor_id && $settings->isFortuneLevel2Enabled($reading->reading_type)) {
                    $grandparent = MlmMember::with('user')->find($sponsor->unilevel_sponsor_id);
                    if ($grandparent && $grandparent->user) {
                        $l2Amount = $settings->getFortuneLevel2Amount($readingPrice, $reading->reading_type);
                        if ($l2Amount > 0) {
                            $commissionService = app(FortuneCommissionService::class);
                            $commissionService->forceCreateCommission(
                                $reading, $grandparent, $mlmMember, 2, $l2Amount, $readingPrice, $settings
                            );
                            $this->commissionsDistributed++;
                            $this->info("    ✅ Reading #{$reading->id}: L2 จ่าย {$l2Amount} บาท → {$grandparent->user->name}");
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("    ❌ Reading #{$reading->id}: error — {$e->getMessage()}");
            }
        }
    }
}
