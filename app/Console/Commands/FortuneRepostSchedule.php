<?php

namespace App\Console\Commands;

use App\Models\FortunePage;
use App\Services\Fortune\FortuneRepostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🔁 (2026-11-07) รีโพสจากเพจต้นทางลงเพจสาขาตามตารางเหลื่อมเวลา
 *
 * รันทุก 15 นาทีจาก routes/console.php — แต่ละรอบ:
 *   1. อัปเดตสถานะโพสที่ถึงเวลาแล้ว (ขึ้นจริง / ถูกลบ / ค้างกลางทาง)
 *   2. ดูตารางของเพจต้นทางในอีก N ชั่วโมงข้างหน้า → เติมช่องที่ยังว่างของแต่ละสาขา
 *
 * ปลอดภัยถ้ารันซ้ำ: ช่องที่มีโพสแล้วถูกข้าม และโพสต้นฉบับ 1 ใบลงสาขาเดียวได้ครั้งเดียว
 *
 * ตัวอย่าง:
 *   php artisan fortune:repost-schedule                 # ทุกสาขาที่เปิดรีโพส
 *   php artisan fortune:repost-schedule --page=5 --dry-run   # ดูแผนของสาขา 5 ไม่โพสจริง
 */
class FortuneRepostSchedule extends Command
{
    protected $signature = 'fortune:repost-schedule
                            {--page= : เฉพาะสาขาเดียว (รหัสสาขา → ไอดีเพจ Facebook → id · หรือ id:<เลข>)}
                            {--dry-run : แสดงแผนเฉยๆ ไม่ตั้งเวลาโพสจริง}';

    protected $description = '🔁 รีโพสคอนเทนต์จากเพจหลักลงเพจสาขา ตามตารางเวลาเหลื่อม (เช่น +30 นาที)';

    public function handle(FortuneRepostService $service): int
    {
        if (! Schema::hasTable('fortune_page_reposts') || ! Schema::hasColumn('fortune_pages', 'repost_enabled')) {
            $this->warn('ยังไม่ได้ migrate ระบบรีโพส → ข้าม');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $only = trim((string) $this->option('page'));

        if ($only !== '') {
            $page = $this->resolveOnePage($only);

            if (! $page) {
                $this->error("❌ ไม่พบสาขา \"{$only}\" (ลองใช้รหัสสาขา / ไอดีเพจ Facebook / id:<เลข>)");

                return self::FAILURE;
            }

            $pages = collect([$page]);
        } else {
            $pages = FortunePage::query()
                ->where('platform', 'facebook')
                ->where('repost_enabled', true)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
        }

        if ($pages->isEmpty()) {
            $this->info('ℹ️  ไม่มีสาขาที่เปิดรีโพส');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($pages as $page) {
            $this->line("🏬 {$page->name} (id {$page->id})");

            try {
                if (! $dryRun) {
                    $r = $service->reconcile($page);

                    if ($r['checked'] > 0 || $r['failed'] > 0) {
                        $this->line("   ↻ ตรวจสถานะ {$r['checked']} · ขึ้นแล้ว {$r['published']} · ไม่ผ่าน {$r['failed']}");
                    }
                }

                // --page ระบุเอง + dry-run = ดูแผนได้แม้สวิตช์ปิด (ไว้เช็คก่อนเปิดจริง)
                $report = $service->run($page, $dryRun, $dryRun && $only !== '');

                $this->line('   '.($report['ok'] ? '✅ ' : '⚠️ ').$report['message']);

                foreach ($report['planned'] as $row) {
                    $this->line(sprintf(
                        '   %s  %-5s  %-9s  %s%s',
                        $row['slot_at'],
                        $row['media_type'],
                        $row['status'],
                        $row['caption'] !== '' ? mb_substr(str_replace("\n", ' ', $row['caption']), 0, 50) : '(ไม่มีข้อความ)',
                        $row['error'] ? '  ❌ '.$row['error'] : ''
                    ));
                }

                foreach ($report['skipped'] as $why) {
                    $this->line('   · '.$why);
                }

                if (! $report['ok']) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("   ❌ {$e->getMessage()}");

                Log::error('🔁 cron รีโพสสาขาล้มเหลว', [
                    'fortune_page_id' => $page->id,
                    'error' => \App\Support\SafeLog::redactSecrets($e->getMessage()),
                ]);
            }
        }

        return $failed > 0 && $only !== '' ? self::FAILURE : self::SUCCESS;
    }

    /**
     * หา "สาขาเดียว" จากค่าที่พิมพ์ — ห้ามคืนหลายเพจ
     *
     * ⚠️ เจอบน prod (2026-11-07): --page=5 เคยจับทั้ง id 5 (เพจอื่นที่ไม่ใช่สาขา)
     *    และรหัสสาขา "5" (id 14) พร้อมกัน → ไล่ตามลำดับแทน: รหัสสาขา → ไอดีเพจ Facebook → id
     *    อยากระบุ id ตรงๆ ใช้ "id:14"
     */
    protected function resolveOnePage(string $value): ?FortunePage
    {
        $base = fn () => FortunePage::query()->where('platform', 'facebook');

        if (preg_match('/^id:(\d+)$/i', $value, $m)) {
            return $base()->whereKey((int) $m[1])->first();
        }

        return $base()->where('code', $value)->first()
            ?? $base()->where('external_page_id', $value)->first()
            ?? (ctype_digit($value) ? $base()->whereKey((int) $value)->first() : null);
    }
}
