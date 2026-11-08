<?php

namespace App\Console\Commands;

use App\Services\Backup\R2BackupService;
use App\Services\TelegramAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 🗄️ สำรองข้อมูลขึ้น Cloudflare R2 (เข้ารหัส — เปิดได้ด้วยลูกกุญแจของเจ้าของเท่านั้น)
 *
 * ตารางเวลา (routes/console.php):
 *   backup:r2 db    ทุกวัน 02:00     → 7 ช่องตามวันในสัปดาห์ เขียนทับวน
 *   backup:r2 site  วันที่ 10, 25 02:00 → 2 ช่อง (day10/day25) เขียนทับทุกเดือน
 *
 * สั่งมือ:
 *   php artisan backup:r2 check          ← ทดสอบ R2 + แม่กุญแจ (หลังตั้งค่า .env ครั้งแรก)
 *   php artisan backup:r2 list           ← ดูไฟล์บน R2 + ผลรอบล่าสุด
 *   php artisan backup:r2 db --local     ← ทดสอบสร้างไฟล์ ไม่อัปโหลด
 *
 * ล้มเหลว → Telegram แจ้งแอดมิน (ฟรี ไม่กินโควตา LINE push)
 * คู่มือกู้คืน: docs/BACKUP_R2.md
 */
class BackupToR2 extends Command
{
    protected $signature = 'backup:r2
        {action : db = ฐานข้อมูล · site = ทั้งเว็บ (ไฟล์+ฐานข้อมูล) · list = ดูไฟล์บน R2 · check = ทดสอบการตั้งค่า}
        {--slot= : (site) บังคับชื่อช่อง เช่น day10 / day25}
        {--local : ทดสอบ — สร้างไฟล์ไว้ในเครื่อง ไม่อัปโหลด}';

    protected $description = 'สำรองฐานข้อมูล/ทั้งเว็บ ขึ้น Cloudflare R2 แบบเข้ารหัส';

    public function handle(R2BackupService $backup): int
    {
        $action = (string) $this->argument('action');
        $backup->setLogger(fn (string $m) => $this->line($m));

        if (! in_array($action, ['db', 'site', 'list', 'check'], true)) {
            $this->error("ไม่รู้จัก action: {$action} (ใช้ db / site / list / check)");

            return self::INVALID;
        }

        if (in_array($action, ['db', 'site'], true) && ! config('backup.enabled')) {
            $this->warn('ปิดการสำรองอยู่ (BACKUP_R2_ENABLED=false) — ข้าม');

            return self::SUCCESS;
        }

        try {
            return match ($action) {
                'db' => $this->runDb($backup),
                'site' => $this->runSite($backup),
                'list' => $this->runList($backup),
                'check' => $this->runCheck($backup),
            };
        } catch (Throwable $e) {
            $this->error('❌ '.$e->getMessage());

            if (in_array($action, ['db', 'site'], true) && ! $this->option('local')) {
                $this->reportFailure($backup, $action, $e);
            }

            return self::FAILURE;
        }
    }

    protected function runDb(R2BackupService $backup): int
    {
        $r = $backup->backupDatabase((bool) $this->option('local'));

        $this->info("✅ สำรองฐานข้อมูลเสร็จ — {$r['key']} · {$backup->human($r['size'])} · {$r['seconds']} วิ");
        Log::info('backup:r2 db สำเร็จ', $r);

        return self::SUCCESS;
    }

    protected function runSite(R2BackupService $backup): int
    {
        $slot = $this->option('slot') ? (string) $this->option('slot') : null;
        $r = $backup->backupSite($slot, (bool) $this->option('local'));

        $this->info("✅ สำรองทั้งเว็บเสร็จ — ช่อง {$r['slot']} · {$backup->human($r['size'])} · {$r['seconds']} วิ");
        Log::info('backup:r2 site สำเร็จ', $r);

        return self::SUCCESS;
    }

    protected function runList(R2BackupService $backup): int
    {
        foreach (['db' => 'ฐานข้อมูลรายวัน', 'site' => 'ทั้งเว็บ'] as $kind => $label) {
            $last = $backup->lastResult($kind);
            $text = $last === null ? 'ยังไม่เคยรัน'
                : (($last['ok'] ?? false) ? '✅ '.$last['at'] : '❌ '.$last['at'].' — '.($last['error'] ?? ''));
            $this->line("รอบล่าสุด {$label}: {$text}");
        }
        $this->newLine();

        $rows = $backup->listBackups();
        if ($rows === []) {
            $this->warn('ยังไม่มีไฟล์สำรองบน R2');

            return self::SUCCESS;
        }

        $this->table(['ไฟล์', 'ขนาด', 'เวลา (ไทย)'], array_map(fn ($r) => [
            $r['key'],
            $backup->human($r['size']),
            date('Y-m-d H:i', $r['modified']),
        ], $rows));

        $total = array_sum(array_column($rows, 'size'));
        $this->line('รวม '.count($rows).' ไฟล์ · '.$backup->human($total));

        return self::SUCCESS;
    }

    protected function runCheck(R2BackupService $backup): int
    {
        $r = $backup->checkConnection();

        $this->info("✅ เขียน/อ่าน/ลบ บน R2 ได้ — bucket {$r['bucket']} / {$r['prefix']}");
        $this->line("ลายนิ้วมือแม่กุญแจ: {$r['key_fingerprint']} (ต้องตรงกับที่ decrypt-backup.sh พิมพ์จากลูกกุญแจ)");

        return self::SUCCESS;
    }

    /**
     * แจ้งแอดมินทาง Telegram — กันรัว 20 ชม. (สำรองวันละครั้ง ล้มซ้ำวันถัดไปก็เตือนอีก)
     */
    protected function reportFailure(R2BackupService $backup, string $action, Throwable $e): void
    {
        $backup->rememberFailure($action, $e->getMessage());
        Log::error("backup:r2 {$action} ล้มเหลว", ['error' => $e->getMessage()]);

        $label = $action === 'db' ? 'ฐานข้อมูลรายวัน' : 'ทั้งเว็บ (10/25)';
        app(TelegramAlertService::class)->send(
            "🗄️ สำรองข้อมูล{$label} ขึ้น R2 ล้มเหลว\n\n"
            .mb_substr($e->getMessage(), 0, 400)."\n\n"
            .'ไฟล์สำรองชุดเก่ายังอยู่ (ไม่ถูกเขียนทับ) — ตรวจด้วย php artisan backup:r2 list',
            "backup-r2-fail-{$action}",
            20 * 60
        );
    }
}
