<?php

namespace App\Console\Commands;

use App\Services\DailyHoroscopeAutoPostService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * โพสดวงประจำวันรายเจ้าของวันเกิด
 *
 * Usage:
 *   php artisan fortune:daily-horoscope:publish 1            # จันทร์
 *   php artisan fortune:daily-horoscope:publish 7            # อาทิตย์
 *   php artisan fortune:daily-horoscope:publish all          # ทุกวันเรียงเลย (ทดสอบ)
 *   php artisan fortune:daily-horoscope:publish 1 --date=2026-04-28
 */
class FortuneDailyHoroscopePublish extends Command
{
    protected $signature = 'fortune:daily-horoscope:publish
        {dayOfBirth : 1=จันทร์, 2=อังคาร, 3=พุธ, 4=พฤหัส, 5=ศุกร์, 6=เสาร์, 7=อาทิตย์, all=ทุกวัน}
        {--date= : วันที่ดวง (YYYY-MM-DD), default: today}
        {--page= : ทำเฉพาะสาขาเดียว (รหัสสาขา / id / ไอดีเพจ) — เว้นว่าง = ทุกสาขาที่เปิด}
        {--force : ลบโพสเก่า (FB + DB) แล้วสร้างใหม่ — ใช้สำหรับ republish ภาพไม่สวย}';

    protected $description = 'สร้างและโพสดวงประจำวันสำหรับเจ้าของวันเกิดที่ระบุ (ทุกสาขาที่เปิด)';

    use \App\Console\Commands\Concerns\RunsForEachFortunePage;

    /**
     * 🏬 (2026-08-15) วนโพสให้ทุกสาขา — เดิมโพสเฉพาะเพจหลัก
     *    เพราะ scheduled command ไม่มีใคร bind FortunePageContext ให้
     */
    public function handle(): int
    {
        // requireAutoPost=true → เฉพาะสาขาที่แอดมินติ๊ก “โพสอัตโนมัติ” ไว้เท่านั้น
        $stats = $this->forEachActiveFortunePage('facebook', fn () => $this->publishForCurrentPage(), true);

        if ($stats['ran'] > 1) {
            $this->info("🏬 รวม {$stats['ran']} สาขา — สำเร็จ {$stats['ok']} · ล้มเหลว {$stats['failed']}");
        }

        return $stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * โพสให้สาขาที่ context ชี้อยู่ตอนนี้
     */
    protected function publishForCurrentPage(): bool
    {
        $arg = $this->argument('dayOfBirth');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $force = (bool) $this->option('force');

        // ⚠️ resolve ใหม่ทุกสาขา — service จับ settings ไว้ตอน construct
        //    ใช้ตัวเดิมซ้ำ = สาขาที่ 2 โพสด้วย token ของสาขาแรก
        $service = app(DailyHoroscopeAutoPostService::class);

        if ($force) {
            $this->warn('⚠️  --force: จะลบโพสเก่าบน FB + DB ก่อน republish');
        }

        if (strtolower($arg) === 'all') {
            $this->info("📅 โพสดวงประจำวันทั้ง 7 วันเกิด ({$date->toDateString()})");

            $results = [];
            foreach (range(1, 7) as $day) {
                $this->line("  • วัน{$day} ({$this->dayName($day)})...");
                $result = $service->generateAndPublish($day, $date, $force);
                $results[] = ['day' => $day, 'result' => $result];

                $icon = $result['success'] ? '✅' : '❌';
                $this->line("    {$icon} ".($result['message'] ?? '-'));
            }

            $success = count(array_filter($results, fn ($r) => $r['result']['success']));
            $this->info("รวม: {$success}/7 สำเร็จ");

            return $success === 7;
        }

        $day = (int) $arg;
        if ($day < 1 || $day > 7) {
            $this->error('dayOfBirth ต้องเป็น 1-7 หรือ all');

            return false;
        }

        $this->info("📅 โพสดวงคนเกิดวัน{$this->dayName($day)} ({$date->toDateString()})");
        $result = $service->generateAndPublish($day, $date, $force);

        if ($result['success']) {
            $this->info('✅ '.($result['message'] ?? 'สำเร็จ'));
            if (! empty($result['url'])) {
                $this->line("🔗 {$result['url']}");
            }

            return true;
        }

        $this->error('❌ '.($result['message'] ?? 'ล้มเหลว'));

        return false;
    }

    protected function dayName(int $day): string
    {
        return [
            1 => 'จันทร์',
            2 => 'อังคาร',
            3 => 'พุธ',
            4 => 'พฤหัสบดี',
            5 => 'ศุกร์',
            6 => 'เสาร์',
            7 => 'อาทิตย์',
        ][$day] ?? '?';
    }
}
