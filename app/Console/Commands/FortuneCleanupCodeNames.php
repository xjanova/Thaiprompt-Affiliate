<?php

namespace App\Console\Commands;

use App\Models\FortuneCommentEngagement;
use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * fortune:cleanup-code-names
 *
 * ล้างค่า facebook_user_name ที่เป็น code pattern (FACEBOOK-XXXXXX, LINE-XXXXXX)
 * ออกจาก DB — เกิดจากบั๊กเดิมที่ resolveCustomerName() fallback ลง code แล้ว persist
 *
 * Strategy:
 *   1. Scan FortuneReading + FortuneUserCredit ที่ name match pattern PLATFORM-XXXX
 *   2. ลอง resolve ชื่อจริงจาก historical (rows อื่นของ user เดียวกัน) ก่อน
 *   3. ถ้าหาไม่เจอ → set เป็น NULL (ให้ resolveCustomerName() fallback ไป "คุณ")
 *
 * รัน manual ครั้งเดียว — แล้ว resolveCustomerName() ที่แก้แล้วจะกัน code pattern หลุดอีก
 */
class FortuneCleanupCodeNames extends Command
{
    protected $signature = 'fortune:cleanup-code-names
        {--dry-run : แสดงจำนวนที่จะถูกแก้โดยไม่แก้จริง}';

    protected $description = 'ล้าง facebook_user_name ที่เป็น code pattern (FACEBOOK-XXXXXX) จาก DB';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $pattern = '^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$';

        $this->info('🧹 Cleanup code-pattern names');
        $this->info('Pattern: ' . $pattern);
        $this->info($dryRun ? 'DRY RUN — ไม่แก้จริง' : 'LIVE — จะอัปเดต DB');
        $this->newLine();

        // ───────────────────────────────────────────────
        // 1. FortuneReading
        // ───────────────────────────────────────────────
        $readings = FortuneReading::where('facebook_user_name', 'REGEXP', $pattern)
            ->orderBy('id')
            ->get(['id', 'facebook_user_name', 'facebook_user_id', 'platform_user_id', 'platform']);

        $this->info("FortuneReading: {$readings->count()} records ที่ name เป็น code");

        $readingFixed = 0;
        $readingNulled = 0;

        foreach ($readings as $reading) {
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            $realName = null;

            // ลอง historical lookup (rows อื่นของ user เดียวกัน)
            if (! empty($userId)) {
                $candidates = FortuneReading::where(function ($q) use ($userId) {
                    $q->where('facebook_user_id', $userId)
                        ->orWhere('platform_user_id', $userId);
                })
                    ->where('id', '!=', $reading->id)
                    ->whereNotNull('facebook_user_name')
                    ->where('facebook_user_name', '!=', '')
                    ->where('facebook_user_name', 'NOT REGEXP', $pattern)
                    ->latest('updated_at')
                    ->limit(5)
                    ->pluck('facebook_user_name');

                foreach ($candidates as $candidate) {
                    if (trim($candidate) !== '' && trim($candidate) !== 'คุณ') {
                        $realName = $candidate;
                        break;
                    }
                }
            }

            // ลอง credit
            if (! $realName && ! empty($userId)) {
                try {
                    $credit = FortuneUserCredit::findByUser($userId, $reading->platform ?? 'facebook');
                    if ($credit && ! empty($credit->facebook_user_name)
                        && ! preg_match("/{$pattern}/i", $credit->facebook_user_name)
                        && $credit->facebook_user_name !== 'คุณ') {
                        $realName = $credit->facebook_user_name;
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // 🆕 ลอง FortuneCommentEngagement.user_profile.name (มี name จาก comment payload เสมอ)
            if (! $realName && ! empty($userId)) {
                try {
                    $engagement = FortuneCommentEngagement::where('facebook_user_id', $userId)
                        ->whereNotNull('user_profile')
                        ->latest('engaged_at')
                        ->first();
                    if ($engagement) {
                        $profileName = data_get($engagement->user_profile, 'name');
                        if (! empty($profileName)
                            && ! preg_match("/{$pattern}/i", $profileName)
                            && $profileName !== 'คุณ') {
                            $realName = $profileName;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($realName) {
                if (! $dryRun) {
                    $reading->update(['facebook_user_name' => $realName]);
                }
                $readingFixed++;
            } else {
                if (! $dryRun) {
                    $reading->update(['facebook_user_name' => null]);
                }
                $readingNulled++;
            }
        }

        // ───────────────────────────────────────────────
        // 2. FortuneUserCredit
        // ───────────────────────────────────────────────
        $credits = FortuneUserCredit::where('facebook_user_name', 'REGEXP', $pattern)
            ->orderBy('id')
            ->get(['id', 'facebook_user_name', 'facebook_user_id', 'platform']);

        $this->info("FortuneUserCredit: {$credits->count()} records ที่ name เป็น code");

        $creditFixed = 0;
        $creditNulled = 0;

        foreach ($credits as $credit) {
            $realName = null;

            // หา reading ของ user คนนี้ที่ name เป็นชื่อจริง
            $candidate = FortuneReading::where(function ($q) use ($credit) {
                $q->where('facebook_user_id', $credit->facebook_user_id)
                    ->orWhere('platform_user_id', $credit->facebook_user_id);
            })
                ->whereNotNull('facebook_user_name')
                ->where('facebook_user_name', '!=', '')
                ->where('facebook_user_name', 'NOT REGEXP', $pattern)
                ->where('facebook_user_name', '!=', 'คุณ')
                ->latest('updated_at')
                ->value('facebook_user_name');

            if (! empty($candidate)) {
                $realName = $candidate;
            }

            // 🆕 ลอง FortuneCommentEngagement.user_profile.name
            if (! $realName) {
                try {
                    $engagement = FortuneCommentEngagement::where('facebook_user_id', $credit->facebook_user_id)
                        ->whereNotNull('user_profile')
                        ->latest('engaged_at')
                        ->first();
                    if ($engagement) {
                        $profileName = data_get($engagement->user_profile, 'name');
                        if (! empty($profileName)
                            && ! preg_match("/{$pattern}/i", $profileName)
                            && $profileName !== 'คุณ') {
                            $realName = $profileName;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($realName) {
                if (! $dryRun) {
                    $credit->update(['facebook_user_name' => $realName]);
                }
                $creditFixed++;
            } else {
                if (! $dryRun) {
                    $credit->update(['facebook_user_name' => null]);
                }
                $creditNulled++;
            }
        }

        // ───────────────────────────────────────────────
        // Summary
        // ───────────────────────────────────────────────
        $this->newLine();
        $this->table(
            ['Table', 'Recovered (real name)', 'Cleared to NULL'],
            [
                ['FortuneReading', $readingFixed, $readingNulled],
                ['FortuneUserCredit', $creditFixed, $creditNulled],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN: ไม่ได้แก้จริง — รันอีกครั้งโดยไม่ใส่ --dry-run เพื่อแก้');
        } else {
            $this->info('✅ Cleanup เสร็จสิ้น');
        }

        return self::SUCCESS;
    }
}
