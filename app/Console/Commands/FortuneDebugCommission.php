<?php

namespace App\Console\Commands;

use App\Helpers\MlmRetentionHelper;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmProspect;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

/**
 * ตรวจสอบปัญหาคอมมิชชั่นดูดวง — ดู flow ทั้งหมดว่าตรงไหนขาด
 *
 * ใช้ตรวจสอบเมื่อ:
 * - ผู้เชิญไม่ได้รับค่าคอม หลังเพื่อนจ่ายเงินดูดวง
 * - ต้องการดูว่า referral จับคู่ถูกต้องหรือไม่
 * - ต้องการตรวจสอบสถานะ active ของ sponsor
 */
class FortuneDebugCommission extends Command
{
    protected $signature = 'fortune:debug-commission
                            {--sponsor= : ชื่อ/email/ID ของผู้เชิญ (sponsor)}
                            {--friend= : ชื่อ/email/ID ของเพื่อนที่ถูกเชิญ}
                            {--all : แสดงข้อมูลทั้งหมด ไม่จำกัด sponsor}
                            {--recent=24 : ตรวจสอบข้อมูลย้อนหลังกี่ชั่วโมง}';

    protected $description = 'ตรวจสอบปัญหาคอมมิชชั่นดูดวง — ดู flow ทั้งหมดว่าตรงไหนขาด';

    public function handle(): int
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   🔮 Fortune Commission Debug Tool               ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        // ===== STEP 1: ตรวจ Settings =====
        $this->checkSettings();

        // ===== STEP 2: ตรวจ Sponsor =====
        $sponsor = $this->findSponsor();

        // ===== STEP 3: ตรวจ Friend (ผู้ถูกเชิญ) =====
        $friend = $this->findFriend();

        // ===== STEP 4: ตรวจ FortuneReferrals =====
        $this->checkReferrals($sponsor, $friend);

        // ===== STEP 5: ตรวจ MlmProspects =====
        $this->checkProspects($sponsor);

        // ===== STEP 6: ตรวจ FortuneReadings ล่าสุด =====
        $this->checkRecentReadings($friend);

        // ===== STEP 7: ตรวจ FortuneCommissions =====
        $this->checkCommissions($sponsor, $friend);

        // ===== STEP 8: ตรวจ Wallet Transactions =====
        $this->checkWalletTransactions($sponsor);

        // ===== STEP 9: สรุป =====
        $this->newLine();
        $this->info('════════════════════════════════════════════════════');
        $this->info('  ✅ ตรวจสอบเสร็จสิ้น — ดูผลด้านบน');
        $this->info('════════════════════════════════════════════════════');

        return 0;
    }

    /**
     * ตรวจสอบ Settings ที่จำเป็นทั้งหมด
     */
    private function checkSettings(): void
    {
        $this->info('━━━ STEP 1: ตรวจสอบ Settings ━━━');

        $settings = FortuneTellingSetting::getSettings();
        $mlmSettings = MlmGlobalSetting::first();

        $checks = [];

        // fortune_affiliate_enabled
        $affiliateEnabled = $settings->fortune_affiliate_enabled ?? false;
        $checks[] = [
            'fortune_affiliate_enabled',
            $affiliateEnabled ? 'true' : 'FALSE ❌',
            $affiliateEnabled ? '✅ เปิด' : '❌ ปิดอยู่! คอมมิชชั่นจะไม่ถูกสร้าง',
        ];

        // fortune_auto_register_enabled
        $autoRegEnabled = $settings->fortune_auto_register_enabled ?? false;
        $checks[] = [
            'fortune_auto_register_enabled',
            $autoRegEnabled ? 'true' : 'FALSE ❌',
            $autoRegEnabled ? '✅ เปิด' : '❌ ปิด! จะไม่สร้าง User/MLM อัตโนมัติ',
        ];

        // fortune_commission_mode
        $mode = $settings->fortune_commission_mode ?? 'static';
        $checks[] = [
            'fortune_commission_mode',
            $mode,
            $mode === 'static' ? '✅ Static (จำนวนคงที่)' : '✅ PV (ตาม Point Value)',
        ];

        // commission amounts
        if ($mode === 'static') {
            $staticAmount = $settings->fortune_static_commission_amount ?? 0;
            $checks[] = [
                'fortune_static_commission_amount',
                $staticAmount,
                $staticAmount > 0 ? "✅ {$staticAmount} บาท" : '❌ = 0! คอมมิชชั่น = 0',
            ];
        }

        // Level 1
        $l1Type = $settings->fortune_level1_commission_type ?? 'fixed';
        $l1Amount = $settings->fortune_level1_commission_amount ?? 0;
        $checks[] = [
            'Level 1 Commission',
            "{$l1Amount} ({$l1Type})",
            $l1Amount > 0 ? '✅' : '❌ = 0',
        ];

        // Level 2
        $l2Enabled = $settings->fortune_level2_enabled ?? false;
        $l2Amount = $settings->fortune_level2_commission_amount ?? 0;
        $checks[] = [
            'Level 2 Commission',
            $l2Enabled ? "{$l2Amount} ({$settings->fortune_level2_commission_type ?? 'fixed'})" : 'DISABLED',
            $l2Enabled ? '✅ เปิด' : '⚠️ ปิด',
        ];

        // LINE bot basic ID
        $basicId = $settings->line_bot_basic_id ?? null;
        $checks[] = [
            'line_bot_basic_id',
            $basicId ?? 'NULL',
            $basicId ? "✅ @{$basicId}" : '❌ ยังไม่ตั้งค่า!',
        ];

        // MLM retention
        $retentionEnabled = MlmGlobalSetting::get('volume_retention_enabled', true);
        $requiredPv = MlmGlobalSetting::get('volume_retention_monthly_pv', 100);
        $graceDays = MlmGlobalSetting::get('volume_retention_grace_days', 7);
        $checks[] = [
            'volume_retention_enabled',
            $retentionEnabled ? "true (PV={$requiredPv}, grace={$graceDays}d)" : 'false',
            $retentionEnabled ? "⚠️ เปิด! Sponsor ต้องมี PV >= {$requiredPv}/เดือน" : '✅ ปิด (ทุกคน active)',
        ];

        $this->table(['Setting', 'ค่า', 'สถานะ'], $checks);
        $this->newLine();
    }

    /**
     * หา Sponsor จากชื่อ/email/ID
     */
    private function findSponsor(): ?User
    {
        $keyword = $this->option('sponsor');
        if (! $keyword) {
            return null;
        }

        $this->info("━━━ STEP 2: ค้นหา Sponsor: \"{$keyword}\" ━━━");

        $user = $this->findUser($keyword);
        if (! $user) {
            $this->error("❌ ไม่พบ User ที่ชื่อ/email/ID = \"{$keyword}\"");

            return null;
        }

        $this->info("👤 พบ: {$user->name} (ID: {$user->id}, email: {$user->email})");

        // ตรวจ MLM Member
        $member = MlmMember::where('user_id', $user->id)->first();
        if (! $member) {
            $this->error('  ❌ ไม่มี MlmMember! จะไม่ได้รับ commission');

            return $user;
        }

        $this->info("  MLM Member ID: {$member->id} | Status: {$member->status} | Qualified: ".($member->is_qualified ? 'Yes' : 'No'));

        // ตรวจ Active Status
        $isActive = MlmRetentionHelper::isMemberActive($member);
        $retentionStatus = MlmRetentionHelper::getRetentionStatus($member);

        if ($isActive) {
            $this->info("  ✅ Active — PV เดือนนี้: {$retentionStatus['monthly_pv']}/{$retentionStatus['required_pv']}");
        } else {
            $this->error("  ❌ ไม่ Active! — PV เดือนนี้: {$retentionStatus['monthly_pv']}/{$retentionStatus['required_pv']}");
            $this->error("     สถานะ: {$retentionStatus['status']} | วันนับจากเคลื่อนไหวล่าสุด: {$retentionStatus['days_since_last_purchase']} วัน (grace: {$retentionStatus['grace_days']} วัน)");
            $this->warn('  ⚠️ นี่อาจเป็นสาเหตุที่คอมมิชชั่นไม่เข้า!');
        }

        // ตรวจ Sponsor ของ Member นี้
        if ($member->unilevel_sponsor_id) {
            $parentSponsor = MlmMember::with('user')->find($member->unilevel_sponsor_id);
            if ($parentSponsor && $parentSponsor->user) {
                $this->info("  Upline: {$parentSponsor->user->name} (Member ID: {$parentSponsor->id})");
            }
        }

        $this->newLine();

        return $user;
    }

    /**
     * หา Friend (ผู้ถูกเชิญ)
     */
    private function findFriend(): ?User
    {
        $keyword = $this->option('friend');
        if (! $keyword) {
            return null;
        }

        $this->info("━━━ STEP 3: ค้นหา Friend (ผู้ถูกเชิญ): \"{$keyword}\" ━━━");

        $user = $this->findUser($keyword);
        if (! $user) {
            $this->warn("⚠️ ไม่พบ User ที่ชื่อ/email/ID = \"{$keyword}\"");
            $this->warn('  → อาจยังไม่ได้สมัครสมาชิก (auto-register อาจล้มเหลว)');

            return null;
        }

        $this->info("👤 พบ: {$user->name} (ID: {$user->id})");

        $member = MlmMember::where('user_id', $user->id)->first();
        if ($member) {
            $this->info("  MLM Member ID: {$member->id} | Sponsor ID: {$member->unilevel_sponsor_id}");
            $sponsorMember = $member->unilevel_sponsor_id ? MlmMember::with('user')->find($member->unilevel_sponsor_id) : null;
            if ($sponsorMember && $sponsorMember->user) {
                $this->info("  → อยู่ใต้สายงาน: {$sponsorMember->user->name} (Member: {$sponsorMember->id})");
            }
        } else {
            $this->error('  ❌ ไม่มี MlmMember! auto-register อาจล้มเหลว');
        }

        $this->newLine();

        return $user;
    }

    /**
     * ตรวจสอบ FortuneReferrals
     */
    private function checkReferrals(?User $sponsor, ?User $friend): void
    {
        $hours = (int) $this->option('recent');
        $this->info("━━━ STEP 4: ตรวจสอบ FortuneReferrals (ย้อนหลัง {$hours} ชม.) ━━━");

        $query = FortuneReferral::query()
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc');

        if ($sponsor) {
            $query->where('referrer_user_id', $sponsor->id);
        }

        $referrals = $query->limit(20)->get();

        if ($referrals->isEmpty()) {
            $this->warn("  ⚠️ ไม่พบ FortuneReferral ในช่วง {$hours} ชม.");
            $this->warn('  → Sponsor อาจไม่ได้สร้างลิงก์ผ่านระบบใหม่ (deep link)');
            $this->warn('  → หรืออาจเป็นลิงก์เก่า (ก่อน deep link update)');
            $this->newLine();

            // ลองดูแบบไม่จำกัดเวลา
            $allReferrals = FortuneReferral::query();
            if ($sponsor) {
                $allReferrals->where('referrer_user_id', $sponsor->id);
            }
            $count = $allReferrals->count();
            $this->info("  → FortuneReferral ทั้งหมดของ sponsor: {$count} รายการ");
            if ($count > 0) {
                $latest = $allReferrals->orderBy('created_at', 'desc')->first();
                $this->info("    ล่าสุด: ID={$latest->id} | Status={$latest->status} | สร้างเมื่อ {$latest->created_at}");
            }

            return;
        }

        $rows = [];
        foreach ($referrals as $r) {
            $referrerName = $r->referrerUser?->name ?? '?';
            $referredName = $r->referredUser?->name ?? '-';
            $rows[] = [
                $r->id,
                mb_substr($referrerName, 0, 15),
                $r->status,
                $r->referred_line_user_id ? mb_substr($r->referred_line_user_id, 0, 15).'...' : '-',
                $referredName,
                $r->mlm_prospect_id ?? '-',
                $r->expires_at?->format('d/m H:i') ?? '-',
                $r->created_at->format('d/m H:i'),
            ];
        }

        $this->table(
            ['ID', 'ผู้เชิญ', 'Status', 'LINE ID เพื่อน', 'User เพื่อน', 'Prospect', 'หมดอายุ', 'สร้างเมื่อ'],
            $rows
        );

        // ตรวจจุดที่อาจเป็นปัญหา
        foreach ($referrals as $r) {
            if ($r->status === 'pending') {
                $this->warn("  ⚠️ Referral #{$r->id}: ยังเป็น pending — เพื่อนยังไม่ได้กด deep link หรือส่ง ref_{token}");
            }
            if ($r->status === 'followed' && ! $r->referred_user_id) {
                $this->warn("  ⚠️ Referral #{$r->id}: followed แล้ว แต่ยังไม่ converted — เพื่อนยังไม่จ่ายเงิน หรือ auto-register ล้มเหลว");
            }
            if ($r->isExpired()) {
                $this->error("  ❌ Referral #{$r->id}: หมดอายุแล้ว");
            }
        }

        $this->newLine();
    }

    /**
     * ตรวจสอบ MlmProspects
     */
    private function checkProspects(?User $sponsor): void
    {
        if (! $sponsor) {
            return;
        }

        $this->info('━━━ STEP 5: ตรวจสอบ MlmProspects ━━━');

        $member = MlmMember::where('user_id', $sponsor->id)->first();
        if (! $member) {
            $this->warn('  ⚠️ Sponsor ไม่มี MlmMember — ข้าม');

            return;
        }

        $prospects = MlmProspect::where('sponsor_mlm_member_id', $member->id)
            ->with('fortuneReferral')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($prospects->isEmpty()) {
            $this->warn('  ⚠️ ไม่พบ MlmProspect ของ sponsor นี้');

            return;
        }

        $rows = [];
        foreach ($prospects as $p) {
            $fr = $p->fortuneReferral;
            $rows[] = [
                $p->id,
                $p->status,
                $p->line_display_name ?? ($p->line_user_id ? mb_substr($p->line_user_id, 0, 15).'...' : '-'),
                $fr ? "#{$fr->id} ({$fr->status})" : '-',
                $p->is_locked ? 'Yes' : 'No',
                $p->created_at->format('d/m H:i'),
            ];
        }

        $this->table(['ID', 'Status', 'LINE Name', 'FortuneReferral', 'Locked', 'สร้างเมื่อ'], $rows);
        $this->newLine();
    }

    /**
     * ตรวจสอบ FortuneReadings ล่าสุดของ friend
     */
    private function checkRecentReadings(?User $friend): void
    {
        $hours = (int) $this->option('recent');
        $this->info("━━━ STEP 6: ตรวจสอบ FortuneReadings (ย้อนหลัง {$hours} ชม.) ━━━");

        $query = FortuneReading::query()
            ->where('created_at', '>=', now()->subHours($hours))
            ->where('is_paid', true)
            ->orderBy('created_at', 'desc');

        if ($friend) {
            // ค้นหาทั้ง user_id และ platform_user_id
            $query->where(function ($q) use ($friend) {
                $q->where('user_id', $friend->id);
                if ($friend->line_id) {
                    $q->orWhere('platform_user_id', $friend->line_id);
                }
            });
        }

        $readings = $query->limit(10)->get();

        if ($readings->isEmpty()) {
            $this->warn("  ⚠️ ไม่พบ FortuneReading ที่ชำระเงินแล้ว ในช่วง {$hours} ชม.");
            if ($friend) {
                // ดูแบบไม่จำกัดเวลา
                $allCount = FortuneReading::where('user_id', $friend->id)->where('is_paid', true)->count();
                $this->info("  → FortuneReading (paid) ทั้งหมดของ friend: {$allCount}");
            }

            return;
        }

        $rows = [];
        foreach ($readings as $r) {
            $userName = $r->user?->name ?? '-';
            $hasCommission = FortuneCommission::where('fortune_reading_id', $r->id)->exists();
            $rows[] = [
                $r->id,
                mb_substr($userName, 0, 15),
                $r->user_id ?? '❌ NULL',
                number_format($r->amount_paid ?? 0),
                $r->is_paid ? '✅' : '❌',
                $r->conversation_status ?? '-',
                $hasCommission ? '✅ มี' : '❌ ไม่มี',
                $r->paid_at?->format('d/m H:i') ?? '-',
            ];
        }

        $this->table(
            ['ID', 'User', 'user_id', 'จ่าย(บาท)', 'Paid', 'Conv.Status', 'Commission?', 'จ่ายเมื่อ'],
            $rows
        );

        // ตรวจจุดที่เป็นปัญหา
        foreach ($readings as $r) {
            if (! $r->user_id) {
                $this->error("  ❌ Reading #{$r->id}: user_id = NULL! auto-register ไม่ทำงาน → คอมมิชชั่นไม่ถูกสร้าง");
            }

            $hasCommission = FortuneCommission::where('fortune_reading_id', $r->id)->exists();
            if ($r->user_id && ! $hasCommission) {
                // มี user_id แต่ไม่มี commission — ตรวจเพิ่มเติม
                $mlmMember = MlmMember::where('user_id', $r->user_id)->first();
                if (! $mlmMember) {
                    $this->error("  ❌ Reading #{$r->id}: User #{$r->user_id} ไม่มี MlmMember → คอมมิชชั่นไม่ถูกสร้าง");
                } elseif (! $mlmMember->unilevel_sponsor_id) {
                    $this->error("  ❌ Reading #{$r->id}: MlmMember #{$mlmMember->id} ไม่มี sponsor → ไม่มีใครรับคอม");
                } else {
                    $sponsor = MlmMember::find($mlmMember->unilevel_sponsor_id);
                    if ($sponsor) {
                        $isActive = MlmRetentionHelper::isMemberActive($sponsor);
                        if (! $isActive) {
                            $this->error("  ❌ Reading #{$r->id}: Sponsor (Member #{$sponsor->id}) ไม่ Active → คอมถูกข้าม");
                        } else {
                            $this->warn("  ⚠️ Reading #{$r->id}: ทุกอย่างดูปกติ แต่ไม่มี commission — ตรวจ log เพิ่มเติม");
                        }
                    }
                }
            }
        }

        $this->newLine();
    }

    /**
     * ตรวจสอบ FortuneCommissions
     */
    private function checkCommissions(?User $sponsor, ?User $friend): void
    {
        $hours = (int) $this->option('recent');
        $this->info("━━━ STEP 7: ตรวจสอบ FortuneCommissions (ย้อนหลัง {$hours} ชม.) ━━━");

        $query = FortuneCommission::query()
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc');

        if ($sponsor) {
            $query->where('user_id', $sponsor->id);
        }

        $commissions = $query->limit(20)->get();

        if ($commissions->isEmpty()) {
            $this->warn("  ⚠️ ไม่พบ FortuneCommission ในช่วง {$hours} ชม.");
            if ($sponsor) {
                $allCount = FortuneCommission::where('user_id', $sponsor->id)->count();
                $this->info("  → FortuneCommission ทั้งหมดของ sponsor: {$allCount}");
            }

            return;
        }

        $rows = [];
        foreach ($commissions as $c) {
            $receiverName = $c->user?->name ?? '?';
            $fromName = $c->fromUser?->name ?? '?';
            $rows[] = [
                $c->id,
                mb_substr($receiverName, 0, 12),
                mb_substr($fromName, 0, 12),
                "L{$c->level}",
                number_format($c->amount, 2),
                $c->status,
                $c->fortune_reading_id,
                $c->created_at->format('d/m H:i'),
            ];
        }

        $this->table(
            ['ID', 'ผู้รับ', 'จาก', 'Level', 'จำนวน', 'Status', 'Reading', 'เมื่อ'],
            $rows
        );
        $this->newLine();
    }

    /**
     * ตรวจสอบ Wallet Transactions
     */
    private function checkWalletTransactions(?User $sponsor): void
    {
        if (! $sponsor) {
            return;
        }

        $hours = (int) $this->option('recent');
        $this->info("━━━ STEP 8: ตรวจสอบ Wallet Transactions (ย้อนหลัง {$hours} ชม.) ━━━");

        $wallet = $sponsor->wallet;
        if (! $wallet) {
            $this->warn('  ⚠️ Sponsor ไม่มี Wallet');

            return;
        }

        $this->info("  💰 ยอดเงินใน Wallet: {$wallet->balance} {$wallet->currency ?? 'THB'}");

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($transactions->isEmpty()) {
            $this->warn("  ⚠️ ไม่พบ Transaction ในช่วง {$hours} ชม.");

            return;
        }

        $rows = [];
        foreach ($transactions as $t) {
            $rows[] = [
                $t->id,
                $t->type,
                ($t->amount >= 0 ? '+' : '').number_format($t->amount, 2),
                mb_substr($t->description ?? '-', 0, 30),
                $t->created_at->format('d/m H:i'),
            ];
        }

        $this->table(['ID', 'Type', 'จำนวน', 'รายละเอียด', 'เมื่อ'], $rows);
        $this->newLine();
    }

    /**
     * ค้นหา User จากชื่อ, email, หรือ ID
     */
    private function findUser(string $keyword): ?User
    {
        // ลอง ID ก่อน
        if (is_numeric($keyword)) {
            $user = User::find($keyword);
            if ($user) {
                return $user;
            }
        }

        // ลอง exact match กับ email
        $user = User::where('email', $keyword)->first();
        if ($user) {
            return $user;
        }

        // ลอง LIKE กับชื่อ
        $user = User::where('name', 'LIKE', "%{$keyword}%")->first();
        if ($user) {
            return $user;
        }

        // ลอง LINE display name
        $user = User::where('line_display_name', 'LIKE', "%{$keyword}%")->first();

        return $user;
    }
}
