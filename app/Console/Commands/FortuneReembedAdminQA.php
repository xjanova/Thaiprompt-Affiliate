<?php

namespace App\Console\Commands;

use App\Models\FortuneAdminQA;
use App\Services\GeminiEmbeddingService;
use Illuminate\Console\Command;

/**
 * Re-embed admin Q&A vectors ด้วยโมเดล embedding ปัจจุบัน (gemini-embedding-001)
 *
 * ใช้หลังเปลี่ยนโมเดล embedding (text-embedding-004 → gemini-embedding-001 เมื่อ 2026-06-01)
 * เพราะ vector คนละ embedding space — ของเก่า retrieve กับ query ใหม่ไม่แม่น
 *
 * รัน: php artisan fortune:reembed-admin-qa
 */
class FortuneReembedAdminQA extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:reembed-admin-qa
        {--limit=0 : จำกัดจำนวน (0 = ทั้งหมด)}
        {--sleep=200 : หน่วง ms ระหว่าง embed แต่ละครั้ง (กัน rate limit)}';

    /**
     * @var string
     */
    protected $description = 'Re-embed admin Q&A (fortune_admin_qa.q_embedding) ด้วยโมเดล embedding ปัจจุบัน';

    /**
     * รัน re-embed
     */
    public function handle(): int
    {
        $svc = new GeminiEmbeddingService;
        $limit = (int) $this->option('limit');
        $sleepMs = max(0, (int) $this->option('sleep'));

        $base = FortuneAdminQA::query()
            ->whereNotNull('q_text')
            ->where('q_text', '!=', '')
            ->orderBy('id');

        if ($limit > 0) {
            $base->limit($limit);
        }

        $total = (clone $base)->count();
        if ($total === 0) {
            $this->info('ไม่มี admin Q&A ให้ re-embed');

            return self::SUCCESS;
        }

        $this->info("🔄 เริ่ม re-embed admin Q&A {$total} รายการ ด้วย ".GeminiEmbeddingService::MODEL);

        $ok = 0;
        $fail = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $base->chunkById(100, function ($rows) use ($svc, $sleepMs, &$ok, &$fail, $bar) {
            foreach ($rows as $row) {
                $vec = $svc->embed((string) $row->q_text);
                if (is_array($vec) && count($vec) === GeminiEmbeddingService::DIMENSION) {
                    $row->q_embedding = $vec;
                    $row->save();
                    $ok++;
                } else {
                    $fail++;
                }

                $bar->advance();
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ เสร็จ — สำเร็จ {$ok} / ล้มเหลว {$fail}");

        if ($fail > 0) {
            $this->warn("⚠️ มี {$fail} รายการ embed ไม่สำเร็จ (อาจ rate limit / API ล่ม) — รันซ้ำได้");
        }

        return self::SUCCESS;
    }
}
