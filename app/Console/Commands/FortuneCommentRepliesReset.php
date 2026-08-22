<?php

namespace App\Console\Commands;

use App\Models\FortuneCommentReply;
use Database\Seeders\FortuneCommentReplySeeder;
use Illuminate\Console\Command;

/**
 * ล้างคลังคำตอบคอมเมนต์แล้วโหลดชุดล่าสุดจาก seeder
 *
 * ทำไมต้องมี:
 * seeder เป็น idempotent (มีของแล้วข้าม) เพื่อกัน flip-flop ทุก deploy
 * → พอแก้ข้อความในไฟล์ seeder แล้ว deploy ของเก่าใน DB จะไม่เปลี่ยนตาม
 * คำสั่งนี้คือทางเดียวที่ทำให้ชุดใหม่ลงจริง โดยไม่ต้องเข้าไป truncate เอง
 *
 * ⚠️ ลบข้อความที่แอดมินแก้เองในหน้าเว็บทิ้งทั้งหมด — ต้องยืนยันก่อนเสมอ
 *
 * @example
 * php artisan fortune:comment-replies:reset --force
 */
class FortuneCommentRepliesReset extends Command
{
    protected $signature = 'fortune:comment-replies:reset
        {--force : ข้ามคำถามยืนยัน (ใช้ตอน deploy อัตโนมัติ)}';

    protected $description = 'ล้างคลังคำตอบคอมเมนต์แล้วโหลดชุดล่าสุดจาก seeder';

    /**
     * รันคำสั่ง
     *
     * @return int
     */
    public function handle(): int
    {
        $existing = FortuneCommentReply::count();

        $this->warn("⚠️  จะลบคลังคำตอบเดิมทิ้งทั้งหมด {$existing} ชุด (รวมที่แอดมินแก้เอง)");

        if (! $this->option('force') && ! $this->confirm('ยืนยันหรือไม่?')) {
            $this->info('ยกเลิก');

            return self::SUCCESS;
        }

        // ล้างแบบ delete ไม่ใช่ truncate — ตารางมี softDeletes และเผื่อมี FK ในอนาคต
        FortuneCommentReply::withTrashed()->forceDelete();

        $this->info("🗑️  ลบของเดิม {$existing} ชุดแล้ว");

        // เรียก seeder ตัวเดิม — ตอนนี้ตารางว่างแล้ว มันจึงยอมสร้างของใหม่
        $this->callSilent('db:seed', [
            '--class' => FortuneCommentReplySeeder::class,
            '--force' => true,
        ]);

        $total = FortuneCommentReply::count();
        $this->info("✅ โหลดชุดใหม่แล้ว รวม {$total} ชุด");

        foreach (FortuneCommentReply::query()
            ->selectRaw('category, count(*) as c')
            ->groupBy('category')
            ->pluck('c', 'category') as $category => $count) {
            $this->line("   {$category} = {$count}");
        }

        return self::SUCCESS;
    }
}
