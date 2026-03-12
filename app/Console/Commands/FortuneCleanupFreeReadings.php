<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ล้างข้อมูลคำทำนายฟรี (basic) ที่เก่าเกินกำหนด
 *
 * ลดภาระฐานข้อมูลโดย:
 * 1. ลบ basic_response ของ readings ฟรีที่เก่ากว่า N วัน (เก็บ metadata ไว้สำหรับ analytics)
 * 2. ลบ readings ฟรีที่เก่ามากๆ (เกิน 90 วัน) ทั้ง record
 * 3. ลบ readings ที่ค้างสถานะ (incomplete) เกิน 7 วัน
 *
 * Schedule: วันละ 1 ครั้ง (daily) ช่วงดึก
 */
class FortuneCleanupFreeReadings extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:cleanup-free
                            {--days-clear-response=30 : จำนวนวันก่อนล้าง basic_response (เก็บ metadata)}
                            {--days-delete=90 : จำนวนวันก่อนลบ record ฟรีทั้งหมด}
                            {--days-incomplete=7 : จำนวนวันก่อนลบ readings ค้างสถานะ}
                            {--dry-run : แสดงผลอย่างเดียว ไม่ลบจริง}
                            {--chunk=500 : จำนวน records ต่อ chunk}';

    /**
     * @var string
     */
    protected $description = 'ล้างข้อมูลคำทำนายฟรีเก่า เพื่อลดภาระฐานข้อมูล';

    /**
     * ประมวลผลคำสั่ง
     */
    public function handle(): int
    {
        $daysClearResponse = (int) $this->option('days-clear-response');
        $daysDelete = (int) $this->option('days-delete');
        $daysIncomplete = (int) $this->option('days-incomplete');
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $this->info("🧹 เริ่มล้างข้อมูลคำทำนายฟรี...");
        if ($isDryRun) {
            $this->warn('   [DRY RUN] จะไม่ลบข้อมูลจริง');
        }

        $totalCleaned = 0;

        // ขั้นที่ 1: ล้าง basic_response ของ readings ฟรีเก่า (เก็บ metadata ไว้)
        $totalCleaned += $this->clearOldBasicResponses($daysClearResponse, $isDryRun, $chunkSize);

        // ขั้นที่ 2: ลบ readings ฟรีเก่ามากๆ ทั้ง record
        $totalCleaned += $this->deleteOldFreeReadings($daysDelete, $isDryRun, $chunkSize);

        // ขั้นที่ 3: ลบ readings ที่ค้างสถานะ (ไม่เสร็จ) เกินกำหนด
        $totalCleaned += $this->deleteIncompleteReadings($daysIncomplete, $isDryRun, $chunkSize);

        $this->info("✅ ล้างข้อมูลเสร็จสิ้น รวม {$totalCleaned} records ที่จัดการ");

        Log::info('Fortune Cleanup: ล้างคำทำนายฟรีเสร็จสิ้น', [
            'total_cleaned' => $totalCleaned,
            'dry_run' => $isDryRun,
        ]);

        return self::SUCCESS;
    }

    /**
     * ขั้นที่ 1: ล้าง basic_response ของ readings ฟรีเก่า
     *
     * เก็บ metadata (user, timestamp, reading_type) ไว้สำหรับ analytics
     * แต่ลบ content ข้อความทำนายที่ไม่จำเป็นแล้ว
     */
    protected function clearOldBasicResponses(int $days, bool $isDryRun, int $chunkSize): int
    {
        $count = FortuneReading::where('is_paid', false)
            ->where('reading_type', 'basic')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->whereNotNull('basic_response')
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        $this->info("   ขั้นที่ 1: พบ {$count} readings ฟรีเก่ากว่า {$days} วัน ที่จะล้าง response");

        if ($count === 0 || $isDryRun) {
            return 0;
        }

        $cleared = FortuneReading::where('is_paid', false)
            ->where('reading_type', 'basic')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->whereNotNull('basic_response')
            ->where('created_at', '<', now()->subDays($days))
            ->update([
                'basic_response' => null,
                'conversation_data' => null,
            ]);

        $this->info("   ✅ ล้าง response แล้ว {$cleared} records");

        return $cleared;
    }

    /**
     * ขั้นที่ 2: ลบ readings ฟรีเก่ามากๆ ทั้ง record
     */
    protected function deleteOldFreeReadings(int $days, bool $isDryRun, int $chunkSize): int
    {
        $count = FortuneReading::where('is_paid', false)
            ->where('reading_type', 'basic')
            ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        $this->info("   ขั้นที่ 2: พบ {$count} readings ฟรีเก่ากว่า {$days} วัน ที่จะลบทั้ง record");

        if ($count === 0 || $isDryRun) {
            return 0;
        }

        $deleted = 0;
        // ลบเป็น chunk เพื่อไม่ให้ lock table นาน
        do {
            $batch = FortuneReading::where('is_paid', false)
                ->where('reading_type', 'basic')
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->where('created_at', '<', now()->subDays($days))
                ->limit($chunkSize)
                ->delete();

            $deleted += $batch;

            if ($batch > 0) {
                usleep(100000); // 100ms พัก DB
            }
        } while ($batch > 0);

        $this->info("   ✅ ลบ records แล้ว {$deleted} records");

        return $deleted;
    }

    /**
     * ขั้นที่ 3: ลบ readings ที่ค้างสถานะ (incomplete) เกินกำหนด
     *
     * เช่น: user พิมพ์ "ดูดวง" แล้วไม่ตอบอะไรอีก → record ค้าง
     */
    protected function deleteIncompleteReadings(int $days, bool $isDryRun, int $chunkSize): int
    {
        $incompleteStatuses = [
            FortuneReading::STATUS_NEW,
            FortuneReading::STATUS_AWAITING_CONFIRMATION,
            FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            FortuneReading::STATUS_COLLECTING_QUESTIONS,
            FortuneReading::STATUS_COLLECTING_TAROT,
        ];

        $count = FortuneReading::whereIn('conversation_status', $incompleteStatuses)
            ->where('is_paid', false)
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        $this->info("   ขั้นที่ 3: พบ {$count} readings ค้างสถานะเกิน {$days} วัน ที่จะลบ");

        if ($count === 0 || $isDryRun) {
            return 0;
        }

        $deleted = 0;
        do {
            $batch = FortuneReading::whereIn('conversation_status', $incompleteStatuses)
                ->where('is_paid', false)
                ->where('created_at', '<', now()->subDays($days))
                ->limit($chunkSize)
                ->delete();

            $deleted += $batch;

            if ($batch > 0) {
                usleep(100000); // 100ms พัก DB
            }
        } while ($batch > 0);

        $this->info("   ✅ ลบ readings ค้างสถานะแล้ว {$deleted} records");

        return $deleted;
    }
}
