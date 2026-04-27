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
        {--date= : วันที่ดวง (YYYY-MM-DD), default: today}';

    protected $description = 'สร้างและโพสดวงประจำวันสำหรับเจ้าของวันเกิดที่ระบุ';

    public function handle(): int
    {
        $arg = $this->argument('dayOfBirth');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $service = app(DailyHoroscopeAutoPostService::class);

        if (strtolower($arg) === 'all') {
            $this->info("📅 โพสดวงประจำวันทั้ง 7 วันเกิด ({$date->toDateString()})");

            $results = [];
            foreach (range(1, 7) as $day) {
                $this->line("  • วัน{$day} ({$this->dayName($day)})...");
                $result = $service->generateAndPublish($day, $date);
                $results[] = ['day' => $day, 'result' => $result];

                $icon = $result['success'] ? '✅' : '❌';
                $this->line("    {$icon} " . ($result['message'] ?? '-'));
            }

            $success = count(array_filter($results, fn ($r) => $r['result']['success']));
            $this->info("รวม: {$success}/7 สำเร็จ");

            return $success === 7 ? self::SUCCESS : self::FAILURE;
        }

        $day = (int) $arg;
        if ($day < 1 || $day > 7) {
            $this->error('dayOfBirth ต้องเป็น 1-7 หรือ all');

            return self::FAILURE;
        }

        $this->info("📅 โพสดวงคนเกิดวัน{$this->dayName($day)} ({$date->toDateString()})");
        $result = $service->generateAndPublish($day, $date);

        if ($result['success']) {
            $this->info('✅ ' . ($result['message'] ?? 'สำเร็จ'));
            if (! empty($result['url'])) {
                $this->line("🔗 {$result['url']}");
            }

            return self::SUCCESS;
        }

        $this->error('❌ ' . ($result['message'] ?? 'ล้มเหลว'));

        return self::FAILURE;
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
