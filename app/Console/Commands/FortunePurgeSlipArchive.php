<?php

namespace App\Console\Commands;

use App\Models\SlipVerificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * 🧹 ลบรูปสลิปที่ archive ไว้เกิน N วัน (PDPA retention)
 *
 * รูปสลิปมีชื่อผู้โอน/เลขบัญชี → เก็บไว้ debug ได้แต่ต้องไม่เก็บถาวร
 * นโยบาย (user 2026-06-03): เก็บทุกใบ 30 วัน แล้วลบอัตโนมัติ
 *
 * รันรายวันผ่าน schedule ใน routes/console.php (Kernel.php = dead code ใน L11 นี้)
 */
class FortunePurgeSlipArchive extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:purge-slip-archive {--days=30 : เก็บรูปไว้กี่วัน} {--dry-run : แสดงผลโดยไม่ลบจริง}';

    /**
     * @var string
     */
    protected $description = 'ลบรูปสลิปที่ archive ไว้เกินกำหนด (default 30 วัน) — PDPA retention';

    /**
     * โฟลเดอร์เก็บรูปสลิป archived (disk local)
     */
    public const ARCHIVE_DIR = 'fortune/slip_archive';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $this->info("🧹 ลบรูปสลิป archived ที่เก่ากว่า {$days} วัน (ก่อน {$cutoff->toDateTimeString()})"
            .($dryRun ? ' [DRY RUN]' : ''));

        $disk = Storage::disk('local');
        $purgedRows = 0;
        $deletedFiles = 0;

        // 1) ลบตาม log row (เก็บ slip_image_path) — null คอลัมน์ด้วย
        SlipVerificationLog::whereNotNull('slip_image_path')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($disk, $dryRun, &$purgedRows, &$deletedFiles) {
                foreach ($logs as $log) {
                    $path = (string) $log->slip_image_path;
                    if ($path !== '' && $disk->exists($path)) {
                        if (! $dryRun) {
                            try {
                                $disk->delete($path);
                            } catch (\Throwable $e) {
                                // non-blocking
                            }
                        }
                        $deletedFiles++;
                    }
                    if (! $dryRun) {
                        $log->forceFill(['slip_image_path' => null])->saveQuietly();
                    }
                    $purgedRows++;
                }
            });

        // 2) กวาดไฟล์กำพร้าในโฟลเดอร์ (mtime เก่ากว่า cutoff) — เผื่อ row ถูกลบไปก่อน
        $orphans = 0;
        try {
            foreach ($disk->files(self::ARCHIVE_DIR) as $file) {
                $ts = $disk->lastModified($file);
                if ($ts !== false && $ts < $cutoff->getTimestamp()) {
                    if (! $dryRun) {
                        try {
                            $disk->delete($file);
                        } catch (\Throwable $e) {
                            // non-blocking
                        }
                    }
                    $orphans++;
                }
            }
        } catch (\Throwable $e) {
            // โฟลเดอร์อาจยังไม่มี — ไม่เป็นไร
        }

        $this->info("✅ เคลียร์ row: {$purgedRows} | ลบไฟล์: {$deletedFiles} | ไฟล์กำพร้า: {$orphans}");

        return self::SUCCESS;
    }
}
