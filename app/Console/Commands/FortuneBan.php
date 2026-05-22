<?php

namespace App\Console\Commands;

use App\Services\FortuneBanService;
use Illuminate\Console\Command;

/**
 * 🚫 แบน user (ห้ามบอทคุยด้วย)
 *
 * Use cases:
 * - ลูกค้าก่อกวน/สแปม/คุกคามบอท
 * - แบนล่วงหน้าก่อน user เริ่มทัก
 *
 * Usage:
 *   php artisan fortune:ban facebook 28046354201620818 --reason="สแปม"
 *   php artisan fortune:ban facebook 28046354201620818 --minutes=1440 --reason="ลองคุก 1 วัน"
 *   php artisan fortune:ban line U1234abcd --permanent
 *
 * Unban:
 *   php artisan fortune:ban facebook 28046354201620818 --unban
 */
class FortuneBan extends Command
{
    protected $signature = 'fortune:ban
                            {platform : facebook | line}
                            {user_id : PSID (FB) หรือ LINE userId}
                            {--minutes= : ระยะเวลาแบน (นาที). ไม่ใส่ = ถาวร}
                            {--permanent : บังคับให้ถาวร (อ่านง่ายกว่า --minutes ว่างเปล่า)}
                            {--reason= : เหตุผลที่แบน (audit)}
                            {--name= : ชื่อที่แสดง (snapshot)}
                            {--unban : ปลดแบนแทน}';

    protected $description = 'แบน/ปลดแบน user ไม่ให้บอทคุยด้วย (แอดมินยังคุยผ่าน Page Inbox ได้)';

    public function handle(FortuneBanService $banService): int
    {
        $platform = strtolower(trim($this->argument('platform')));
        $userId = trim($this->argument('user_id'));

        if (! in_array($platform, ['facebook', 'line'], true)) {
            $this->error("Platform ต้องเป็น 'facebook' หรือ 'line' (ได้รับ: {$platform})");

            return self::FAILURE;
        }

        if ($userId === '') {
            $this->error('user_id ห้ามว่าง');

            return self::FAILURE;
        }

        // โหมด unban
        if ($this->option('unban')) {
            $ok = $banService->unban($platform, $userId);
            if ($ok) {
                $this->info("✨ ปลดแบนเรียบร้อย — {$platform}:{$userId}");

                return self::SUCCESS;
            }

            $this->warn("ไม่พบ ban record — {$platform}:{$userId}");

            return self::FAILURE;
        }

        // โหมด ban
        $minutes = $this->option('minutes');
        $permanent = $this->option('permanent');

        // --permanent override --minutes
        if ($permanent) {
            $minutes = null;
        } elseif ($minutes !== null) {
            $minutes = (int) $minutes;
            if ($minutes <= 0) {
                $this->error('--minutes ต้องเป็นจำนวนเต็มบวก หรือใช้ --permanent แทน');

                return self::FAILURE;
            }
        }

        $ban = $banService->ban(
            platform: $platform,
            platformUserId: $userId,
            minutes: $minutes,
            reason: $this->option('reason'),
            displayName: $this->option('name'),
        );

        $this->info('🚫 แบนเรียบร้อย');
        $this->table(
            ['Field', 'Value'],
            [
                ['Ban ID', $ban->id],
                ['Platform', $ban->platform],
                ['User ID', $ban->platform_user_id],
                ['ระยะเวลา', $ban->isPermanent() ? 'ถาวร' : $ban->remainingHumanReadable()],
                ['banned_until', $ban->banned_until?->toDateTimeString() ?? '(NULL = ถาวร)'],
                ['เหตุผล', $ban->reason ?? '-'],
            ]
        );

        return self::SUCCESS;
    }
}
