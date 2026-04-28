<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\MysticContentAutoPostService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * โพสคอนเทนต์สายมูประจำ slot
 *
 * Usage:
 *   php artisan fortune:mystic:publish              # auto-detect: หา slot ที่ตรงกับเวลานี้
 *   php artisan fortune:mystic:publish --slot=8     # บังคับโพส slot 08:00
 *   php artisan fortune:mystic:publish --slot=20 --force  # ลบของเก่าแล้วโพสใหม่
 *   php artisan fortune:mystic:publish --all        # โพสทุก slot ของวันนี้ (ทดสอบ)
 *   php artisan fortune:mystic:publish --slot=8 --date=2026-04-30
 *
 * เรียกโดย Schedule ทุกชั่วโมง — command จะเช็คเองว่าตรง slot หรือไม่
 */
class FortuneMysticPublish extends Command
{
    protected $signature = 'fortune:mystic:publish
        {--slot= : ชั่วโมงของ slot (0-23) ถ้าไม่ระบุจะ auto-detect จากเวลาปัจจุบัน}
        {--date= : วันที่ (YYYY-MM-DD) default: today}
        {--all : โพสทุก slot ที่ตั้งไว้ใน schedule}
        {--force : ลบโพสเก่า (FB + DB) แล้วสร้างใหม่}';

    protected $description = 'สร้างและโพสคอนเทนต์สายมูประจำ slot (สายมู/แก้เคล็ด/สิ่งลี้ลับ ฯลฯ)';

    public function handle(MysticContentAutoPostService $service): int
    {
        // Toggle เช็คก่อน — ถ้าปิดอยู่ ข้าม (กัน scheduler รันค้างไว้แต่ admin ปิด)
        $settings = FortuneTellingSetting::getSettings();
        if (! $settings->mystic_content_enabled) {
            $this->info('ℹ️  ระบบโพสคอนเทนต์สายมูปิดอยู่ใน admin → ข้าม');

            return self::SUCCESS;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'), 'Asia/Bangkok')
            : now('Asia/Bangkok');
        $force = (bool) $this->option('force');

        $configuredSlots = $this->getConfiguredSlots($settings);
        if (empty($configuredSlots)) {
            $this->warn('⚠️  ไม่มี slot ใน mystic_content_schedule — ตั้งค่าใน admin ก่อน');

            return self::FAILURE;
        }

        // โหมด --all: โพสทุก slot
        if ($this->option('all')) {
            return $this->publishAll($service, $configuredSlots, $date, $force);
        }

        // โหมดระบุ --slot
        if ($this->option('slot') !== null) {
            $slot = (int) $this->option('slot');

            return $this->publishOne($service, $slot, $date, $force);
        }

        // โหมด auto-detect — เช็คชั่วโมงปัจจุบันว่าตรง slot ไหน
        $currentHour = (int) $date->format('H');
        if (! in_array($currentHour, $configuredSlots, true)) {
            $this->info("⏭  ชั่วโมง {$currentHour}:00 ไม่ตรง slot ใดๆ ที่ตั้งไว้ (slots: "
                . implode(', ', $configuredSlots) . ')');

            return self::SUCCESS;
        }

        return $this->publishOne($service, $currentHour, $date, $force);
    }

    /**
     * อ่าน schedule slots จาก settings
     *
     * @return array<int>  เช่น [8, 20]
     */
    protected function getConfiguredSlots(FortuneTellingSetting $settings): array
    {
        $raw = $settings->mystic_content_schedule;

        // อาจเป็น array (cast เปิด) หรือ JSON string (cast ไม่เปิด)
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw) || empty($raw)) {
            return [8, 20]; // default
        }

        $slots = [];
        foreach ($raw as $time) {
            // รับได้ทั้ง "08:00" และ 8
            if (is_string($time) && preg_match('/^(\d{1,2})(?::\d{2})?$/', trim($time), $m)) {
                $hour = (int) $m[1];
            } elseif (is_int($time)) {
                $hour = $time;
            } else {
                continue;
            }

            if ($hour >= 0 && $hour <= 23) {
                $slots[] = $hour;
            }
        }

        return array_values(array_unique($slots));
    }

    protected function publishOne(
        MysticContentAutoPostService $service,
        int $slot,
        Carbon $date,
        bool $force
    ): int {
        $this->info("🔮 โพสคอนเทนต์สายมู — {$date->toDateString()} slot {$slot}:00");

        if ($force) {
            $this->warn('⚠️  --force: จะลบโพสเก่าก่อน republish');
        }

        $result = $service->generateAndPublish($slot, $date, $force);

        if ($result['success']) {
            $this->info('✅ ' . ($result['message'] ?? 'สำเร็จ'));
            if (! empty($result['topic'])) {
                $this->line("   หมวด: {$result['topic']}");
            }
            if (! empty($result['sub_topic'])) {
                $this->line("   หัวข้อ: {$result['sub_topic']}");
            }
            if (! empty($result['url'])) {
                $this->line("   🔗 {$result['url']}");
            }

            return self::SUCCESS;
        }

        $this->error('❌ ' . ($result['message'] ?? 'ล้มเหลว'));

        return self::FAILURE;
    }

    protected function publishAll(
        MysticContentAutoPostService $service,
        array $slots,
        Carbon $date,
        bool $force
    ): int {
        $this->info("🔮 โพสทุก slot ของวันที่ {$date->toDateString()} — slots: " . implode(', ', $slots));

        $success = 0;
        foreach ($slots as $slot) {
            $this->line("  • slot {$slot}:00...");
            $result = $service->generateAndPublish($slot, $date, $force);

            $icon = $result['success'] ? '✅' : '❌';
            $this->line("    {$icon} " . ($result['message'] ?? '-'));

            if ($result['success']) {
                $success++;
            }
        }

        $total = count($slots);
        $this->info("รวม: {$success}/{$total} สำเร็จ");

        return $success === $total ? self::SUCCESS : self::FAILURE;
    }
}
