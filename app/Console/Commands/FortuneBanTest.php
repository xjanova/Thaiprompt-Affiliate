<?php

namespace App\Console\Commands;

use App\Services\FortuneBanService;
use Illuminate\Console\Command;

/**
 * 🧪 ทดสอบ ban guard end-to-end โดยไม่ต้องรอ user ทักจริง
 *
 * Usage:
 *   php artisan fortune:ban-test facebook 28046354201620818
 *
 * จะ simulate:
 * 1. เช็ค isBanned() → ควร return true
 * 2. เช็ค shouldNotify() → ครั้งแรก = true, ครั้งที่ 2 ใน cooldown = false
 * 3. recordNotification() → notify_count + last_notified_at อัพเดท
 * 4. attempt_count +1 ทุกครั้งที่ shouldNotify ถูกเรียก
 */
class FortuneBanTest extends Command
{
    protected $signature = 'fortune:ban-test
                            {platform : facebook | line}
                            {user_id : PSID หรือ LINE userId}';

    protected $description = 'ทดสอบ ban guard end-to-end (verify Service ทำงานปกติ)';

    public function handle(FortuneBanService $banService): int
    {
        $platform = $this->argument('platform');
        $userId = $this->argument('user_id');

        $this->info("🧪 ทดสอบ ban guard สำหรับ {$platform}:{$userId}");
        $this->newLine();

        // Round 1
        $this->info('▶ ROUND 1 (ทัก ครั้งแรก)');
        $ban = $banService->getActiveBan($platform, $userId);
        if (! $ban) {
            $this->error('❌ ไม่มี ban record — ต้องแบนก่อน: php artisan fortune:ban ...');
            return self::FAILURE;
        }
        $this->line('  isBanned: YES');
        $this->line('  permanent: ' . ($ban->isPermanent() ? 'true' : 'false'));
        $shouldNotify1 = $banService->shouldNotify($ban);
        $this->line('  shouldNotify: ' . ($shouldNotify1 ? 'YES (ส่งข้อความ)' : 'NO (เงียบ)'));
        if ($shouldNotify1) {
            $this->line('  → ข้อความที่จะส่ง:');
            $this->line('  ─────────────────────');
            foreach (explode("\n", $banService->buildBanReplyMessage($ban)) as $line) {
                $this->line('    ' . $line);
            }
            $this->line('  ─────────────────────');
            $banService->recordNotification($ban);
        }
        $ban->refresh();
        $this->line('  attempt_count: ' . $ban->attempt_count);
        $this->line('  notify_count: ' . $ban->notify_count);
        $this->newLine();

        // Round 2 (ใน cooldown)
        $this->info('▶ ROUND 2 (ทัก ครั้งที่ 2 ทันที — ใน cooldown 1 ชม.)');
        $ban = $banService->getActiveBan($platform, $userId);
        $shouldNotify2 = $banService->shouldNotify($ban);
        $this->line('  shouldNotify: ' . ($shouldNotify2 ? 'YES' : 'NO (เงียบ — anti-spam ✅)'));
        $ban->refresh();
        $this->line('  attempt_count: ' . $ban->attempt_count . ' (เพิ่ม +1 แม้ไม่ส่ง)');
        $this->line('  notify_count: ' . $ban->notify_count . ' (ไม่เพิ่ม — เงียบ)');
        $this->newLine();

        // Round 3
        $this->info('▶ ROUND 3 (ทัก ครั้งที่ 3)');
        $ban = $banService->getActiveBan($platform, $userId);
        $shouldNotify3 = $banService->shouldNotify($ban);
        $this->line('  shouldNotify: ' . ($shouldNotify3 ? 'YES' : 'NO (เงียบ)'));
        $ban->refresh();
        $this->line('  attempt_count: ' . $ban->attempt_count);
        $this->newLine();

        $this->info('✅ Test เสร็จสมบูรณ์');
        $this->table(
            ['Field', 'Value'],
            [
                ['Ban ID', $ban->id],
                ['Platform', $ban->platform],
                ['User ID', $ban->platform_user_id],
                ['Status', $ban->isPermanent() ? '🔒 ถาวร' : $ban->remainingHumanReadable()],
                ['Total ครั้งที่ทัก (attempt)', $ban->attempt_count],
                ['Total ครั้งที่ตอบ (notify)', $ban->notify_count],
                ['Anti-spam working', ($shouldNotify1 && ! $shouldNotify2) ? '✅ YES' : '❌ NO'],
            ]
        );

        return self::SUCCESS;
    }
}
