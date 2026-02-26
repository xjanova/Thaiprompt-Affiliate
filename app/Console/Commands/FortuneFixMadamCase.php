<?php

namespace App\Console\Commands;

use App\Models\FortuneCommission;
use App\Models\MlmMember;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

/**
 * แก้ไข case เฉพาะ: ย้าย THสินอุดม (user 128) ไปใต้ MADAM Glory2324 (user 125)
 * + แก้คอมมิชชั่น Reading #174 ให้ไปที่ MADAM แทน Admin
 */
class FortuneFixMadamCase extends Command
{
    protected $signature = 'fortune:fix-madam-case {--dry-run : แสดงผลอย่างเดียว}';

    protected $description = 'ย้าย THสินอุดม ไปใต้ MADAM + แก้คอมมิชชั่น Reading #174';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔧 Fix MADAM Glory2324 Case');
        $this->info('================================');

        // หา members
        $admin = MlmMember::find(77);
        $madam = MlmMember::find(82);
        $friend = MlmMember::find(85);

        if (! $admin || ! $madam || ! $friend) {
            $this->error('❌ ไม่พบ MlmMember ที่ต้องการ (77, 82, 85)');

            return 1;
        }

        $this->info("Admin:  ID={$admin->id} user={$admin->user_id} code={$admin->member_code}");
        $this->info("MADAM:  ID={$madam->id} user={$madam->user_id} code={$madam->member_code}");
        $this->info("Friend: ID={$friend->id} user={$friend->user_id} code={$friend->member_code} sponsor={$friend->unilevel_sponsor_id}");

        // === STEP 1: ย้ายสายงาน ===
        $this->newLine();
        $this->info('━━━ STEP 1: ย้ายสายงาน THสินอุดม → ใต้ MADAM ━━━');

        if ($friend->unilevel_sponsor_id == $madam->id) {
            $this->info('  ✅ อยู่ใต้ MADAM อยู่แล้ว — ข้าม');
        } else {
            $this->info("  ก่อนแก้: sponsor={$friend->unilevel_sponsor_id} level={$friend->unilevel_level} path={$friend->unilevel_path}");

            if (! $dryRun) {
                $friend->update([
                    'unilevel_sponsor_id' => $madam->id,
                    'original_sponsor_id' => $madam->id,
                    'binary_sponsor_id' => $madam->id,
                    'binary_parent_id' => $madam->id,
                    'unilevel_level' => $madam->unilevel_level + 1,
                    'unilevel_path' => trim($madam->unilevel_path.'/'.$madam->id, '/'),
                ]);

                // อัพเดทนับ referral
                $admin->decrement('total_direct_referrals');
                $madam->increment('total_direct_referrals');

                $friend->refresh();
                $this->info("  ✅ หลังแก้: sponsor={$friend->unilevel_sponsor_id} level={$friend->unilevel_level} path={$friend->unilevel_path}");
            } else {
                $this->warn('  [DRY RUN] จะย้าย sponsor จาก 77 → 82');
            }
        }

        // === STEP 2: แก้ Commission ===
        $this->newLine();
        $this->info('━━━ STEP 2: แก้ Commission Reading #174 ━━━');

        $comm = FortuneCommission::where('fortune_reading_id', 174)->where('level', 1)->first();

        if (! $comm) {
            $this->error('  ❌ ไม่พบ commission สำหรับ Reading #174');

            return 1;
        }

        $this->info("  Commission #{$comm->id}: user_id={$comm->user_id} mlm_member_id={$comm->mlm_member_id} amount={$comm->amount}");

        if ($comm->user_id == 125) {
            $this->info('  ✅ Commission อยู่ที่ MADAM อยู่แล้ว — ข้าม');
        } else {
            if (! $dryRun) {
                $oldUserId = $comm->user_id;
                $comm->update([
                    'user_id' => 125,
                    'mlm_member_id' => 82,
                ]);
                $this->info("  ✅ แก้ commission #{$comm->id}: user_id {$oldUserId} → 125 (MADAM)");
            } else {
                $this->warn("  [DRY RUN] จะแก้ commission #{$comm->id} จาก user {$comm->user_id} → 125");
            }
        }

        // === STEP 3: แก้ Wallet ===
        $this->newLine();
        $this->info('━━━ STEP 3: แก้ Wallet (หัก Admin +10 → MADAM) ━━━');

        $wtId = $comm->wallet_transaction_id;
        $wt = $wtId ? WalletTransaction::find($wtId) : null;

        if (! $wt) {
            $this->warn('  ⚠️ ไม่พบ WalletTransaction — ข้าม wallet fix');
        } else {
            $this->info("  WalletTransaction #{$wt->id}: wallet_id={$wt->wallet_id} amount={$wt->amount}");

            if (! $dryRun) {
                // หัก Admin
                $adminWallet = Wallet::where('user_id', 1)->first();
                if ($adminWallet && $adminWallet->balance >= 10) {
                    $adminWallet->decrement('balance', 10);
                    $this->info("  ✅ หัก Admin wallet: balance = {$adminWallet->fresh()->balance}");
                } else {
                    $this->warn('  ⚠️ Admin wallet balance < 10 — ไม่หัก');
                }

                // เพิ่ม MADAM
                $madamWallet = Wallet::firstOrCreate(
                    ['user_id' => 125],
                    ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
                );
                $madamWallet->increment('balance', 10);
                $this->info("  ✅ เพิ่ม MADAM wallet: balance = {$madamWallet->fresh()->balance}");

                // แก้ WalletTransaction
                $wt->update([
                    'wallet_id' => $madamWallet->id,
                    'user_id' => 125,
                ]);
                $this->info("  ✅ แก้ WalletTransaction #{$wt->id} → wallet MADAM");
            } else {
                $this->warn('  [DRY RUN] จะย้าย wallet transaction จาก Admin → MADAM');
            }
        }

        $this->newLine();
        $this->info('================================');
        $this->info('✅ เสร็จสิ้น!');

        return 0;
    }
}
