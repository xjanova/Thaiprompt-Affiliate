<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledBroadcast;
use App\Models\LineBroadcastMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan Command สำหรับส่ง LINE Broadcast Messages ที่ถึงเวลาแล้ว
 *
 * ใช้กับ Cron Job:
 * * * * * * php artisan broadcasts:send-scheduled >> /dev/null 2>&1
 *
 * หรือใน Laravel Scheduler (app/Console/Kernel.php):
 * $schedule->command('broadcasts:send-scheduled')->everyMinute();
 */
class SendScheduledBroadcasts extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'broadcasts:send-scheduled
                            {--dry-run : แสดงรายการ broadcasts ที่จะส่งโดยไม่ส่งจริง}
                            {--force : บังคับส่งแม้ว่าจะยังไม่ถึงเวลา}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'ส่ง LINE Broadcast Messages ที่ตั้งเวลาไว้และถึงเวลาแล้ว';

    /**
     * Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 กำลังตรวจสอบ Scheduled Broadcasts...');

        // ดึง broadcasts ที่ต้องส่ง
        $query = LineBroadcastMessage::query()
            ->where('status', 'scheduled');

        // ถ้าไม่ใช่ force ให้เช็คเวลา
        if (!$this->option('force')) {
            $query->where('scheduled_at', '<=', now());
        }

        // เรียงตามเวลาที่ตั้งไว้
        $broadcasts = $query->orderBy('scheduled_at', 'asc')->get();

        if ($broadcasts->isEmpty()) {
            $this->info('✨ ไม่มี Scheduled Broadcasts ที่ต้องส่งในขณะนี้');
            return Command::SUCCESS;
        }

        $this->info("📊 พบ Scheduled Broadcasts จำนวน: {$broadcasts->count()}");

        // แสดงรายละเอียด
        $table = $broadcasts->map(function ($broadcast) {
            return [
                'ID' => $broadcast->id,
                'ชื่อ' => $broadcast->name,
                'ประเภท' => $broadcast->message_type,
                'ผู้รับ' => $broadcast->target_type,
                'กำหนดส่ง' => $broadcast->scheduled_at?->format('Y-m-d H:i:s'),
                'ผู้รับทั้งหมด' => $broadcast->total_recipients,
            ];
        })->toArray();

        $this->table(
            ['ID', 'ชื่อ', 'ประเภท', 'ผู้รับ', 'กำหนดส่ง', 'ผู้รับทั้งหมด'],
            $table
        );

        // ถ้าเป็น dry-run ให้หยุดตรงนี้
        if ($this->option('dry-run')) {
            $this->warn('⚠️  Dry-run mode: ไม่มีการส่งข้อความจริง');
            return Command::SUCCESS;
        }

        // ยืนยันก่อนส่ง (ถ้าไม่ได้ run ผ่าน cron)
        if ($this->input->isInteractive()) {
            if (!$this->confirm('คุณต้องการส่ง Broadcasts เหล่านี้หรือไม่?', true)) {
                $this->warn('❌ ยกเลิกการส่ง');
                return Command::SUCCESS;
            }
        }

        // Dispatch jobs
        $successCount = 0;
        $failCount = 0;

        $this->output->progressStart($broadcasts->count());

        foreach ($broadcasts as $broadcast) {
            try {
                // Dispatch job ไปยัง queue
                SendScheduledBroadcast::dispatch($broadcast);

                $successCount++;
                $this->output->progressAdvance();

                Log::info("✅ Dispatched broadcast job: {$broadcast->name} (ID: {$broadcast->id})");

            } catch (\Exception $e) {
                $failCount++;
                $this->output->progressAdvance();

                $this->error("❌ ไม่สามารถ dispatch broadcast {$broadcast->id}: " . $e->getMessage());
                Log::error("❌ Failed to dispatch broadcast {$broadcast->id}: " . $e->getMessage());
            }
        }

        $this->output->progressFinish();

        // สรุปผลลัพธ์
        $this->newLine();
        $this->info("✅ Dispatch สำเร็จ: {$successCount}");

        if ($failCount > 0) {
            $this->error("❌ Dispatch ล้มเหลว: {$failCount}");
        }

        $this->info('🎉 เสร็จสิ้น! Jobs จะถูกประมวลผลผ่าน Queue');

        return Command::SUCCESS;
    }
}
