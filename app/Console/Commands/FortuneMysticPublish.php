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
        {--page= : ทำเฉพาะสาขาเดียว (รหัสสาขา / id / ไอดีเพจ) — เว้นว่าง = ทุกสาขาที่เปิด}
        {--force : ลบโพสเก่า (FB + DB) แล้วสร้างใหม่}';

    protected $description = 'สร้างและโพสคอนเทนต์สายมูประจำ slot (ทุกสาขาที่เปิด)';

    use \App\Console\Commands\Concerns\RunsForEachFortunePage;

    /**
     * 🏬 (2026-08-15) วนโพสให้ทุกสาขา
     *
     * ⚠️ ต้อง resolve service ใหม่ในแต่ละสาขา — ของเดิมรับผ่าน method injection
     *    ซึ่ง resolve ครั้งเดียวก่อนเข้า loop = สาขาที่ 2 โพสด้วย token ของสาขาแรก
     */
    public function handle(): int
    {
        $stats = $this->forEachActiveFortunePage(
            'facebook',
            fn () => $this->runForCurrentPage(app(MysticContentAutoPostService::class)) === self::SUCCESS
        );

        if ($stats['ran'] > 1) {
            $this->info("🏬 รวม {$stats['ran']} สาขา — สำเร็จ {$stats['ok']} · ล้มเหลว {$stats['failed']}");
        }

        return $stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * โพสให้สาขาที่ context ชี้อยู่ตอนนี้ (เนื้อในเดิมทั้งหมด ไม่แตะ)
     */
    protected function runForCurrentPage(MysticContentAutoPostService $service): int
    {
        // Toggle เช็คก่อน — ถ้าปิดอยู่ ข้าม (กัน scheduler รันค้างไว้แต่ admin ปิด)
        $settings = FortuneTellingSetting::getSettings();
        if (! $settings->mystic_content_enabled) {
            $this->info('ℹ️  ระบบโพสคอนเทนต์สายมูปิดอยู่ใน admin → ข้าม');

            return self::SUCCESS;
        }

        // 🛡️ (2026-07-10) Runtime guard กันโพสซ้ำ 2 ระบบ: ถ้ามีแคมเปญ bridge สายมู
        //    (topic_source=mystic_topics) เปิดอยู่ในระบบแคมเปญใหม่ → ระบบเก่าหลบให้
        //    (idempotency อยู่คนละตาราง — ถ้าปล่อยรันคู่จะโพสซ้ำบนเพจ + แย่ง LRU topic กัน)
        try {
            $bridgeActive = \App\Models\FortuneContentCampaign::query()
                ->where('is_enabled', true)
                ->where('topic_source', \App\Models\FortuneContentCampaign::SOURCE_MYSTIC_TOPICS)
                ->exists();
            if ($bridgeActive) {
                $this->info('ℹ️  แคมเปญ "สายมู" ในระบบแคมเปญใหม่เปิดอยู่ — ระบบเก่าหลบให้ (กันโพสซ้ำ)');

                return self::SUCCESS;
            }
        } catch (\Throwable $e) {
            // ตารางแคมเปญยังไม่มี (ยังไม่ migrate) → ระบบเก่าทำงานตามปกติ
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

        // 🩹 (2026-05-13) Auto-detect — match HH:MM ใน window 5 นาที (รองรับ slot ที่นาทีอื่น)
        //   user report: "ตั้งเวลาโพสไม่ได้" — slot 08:30 ไม่ trigger เพราะ command เช็คแค่ hour
        //   ใหม่: scheduler รัน everyFiveMinutes → match slot ใน window 5 นาที (ปัดลง)
        //   เช่น now=08:32 → slot 08:30 match (32 - 30 = 2 นาที < 5) → publish slot=8
        $currentTimeMin = (int) $date->format('H') * 60 + (int) $date->format('i');
        $matchedSlot = null;
        $slotConfigs = $this->getConfiguredSlotsWithMinutes($settings);
        foreach ($slotConfigs as $slot) {
            $slotTimeMin = $slot['hour'] * 60 + $slot['minute'];
            $diff = $currentTimeMin - $slotTimeMin;
            if ($diff >= 0 && $diff < 5) {
                $matchedSlot = $slot;
                break;
            }
        }

        if ($matchedSlot === null) {
            $slotsStr = implode(', ', array_map(fn ($s) => sprintf('%02d:%02d', $s['hour'], $s['minute']), $slotConfigs));
            $this->info("⏭  เวลา {$date->format('H:i')} ไม่ตรง slot ใดๆ (slots: {$slotsStr})");

            return self::SUCCESS;
        }

        return $this->publishOne($service, $matchedSlot['hour'], $date, $force);
    }

    /**
     * อ่าน schedule slots จาก settings
     *
     * @return array<int> เช่น [8, 20] — return เฉพาะ hour (backward compat)
     */
    protected function getConfiguredSlots(FortuneTellingSetting $settings): array
    {
        $withMinutes = $this->getConfiguredSlotsWithMinutes($settings);

        return array_values(array_unique(array_map(fn ($s) => $s['hour'], $withMinutes)));
    }

    /**
     * 🆕 (2026-05-13) อ่าน schedule slots พร้อม minute — รองรับ HH:MM format
     *
     * @return array<array{hour:int,minute:int}> เช่น [['hour'=>8,'minute'=>0], ['hour'=>8,'minute'=>30]]
     */
    protected function getConfiguredSlotsWithMinutes(FortuneTellingSetting $settings): array
    {
        $raw = $settings->mystic_content_schedule;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw) || empty($raw)) {
            return [['hour' => 8, 'minute' => 0], ['hour' => 20, 'minute' => 0]]; // default
        }

        $slots = [];
        foreach ($raw as $time) {
            $hour = 0;
            $minute = 0;
            if (is_string($time) && preg_match('/^(\d{1,2})(?::(\d{2}))?$/', trim($time), $m)) {
                $hour = (int) $m[1];
                $minute = isset($m[2]) ? (int) $m[2] : 0;
            } elseif (is_int($time)) {
                $hour = $time;
                $minute = 0;
            } else {
                continue;
            }

            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                $slots[] = ['hour' => $hour, 'minute' => $minute];
            }
        }

        // dedupe
        $unique = [];
        foreach ($slots as $s) {
            $key = sprintf('%02d:%02d', $s['hour'], $s['minute']);
            $unique[$key] = $s;
        }

        return array_values($unique);
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
            $this->info('✅ '.($result['message'] ?? 'สำเร็จ'));
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

        $this->error('❌ '.($result['message'] ?? 'ล้มเหลว'));

        return self::FAILURE;
    }

    protected function publishAll(
        MysticContentAutoPostService $service,
        array $slots,
        Carbon $date,
        bool $force
    ): int {
        $this->info("🔮 โพสทุก slot ของวันที่ {$date->toDateString()} — slots: ".implode(', ', $slots));

        $success = 0;
        foreach ($slots as $slot) {
            $this->line("  • slot {$slot}:00...");
            $result = $service->generateAndPublish($slot, $date, $force);

            $icon = $result['success'] ? '✅' : '❌';
            $this->line("    {$icon} ".($result['message'] ?? '-'));

            if ($result['success']) {
                $success++;
            }
        }

        $total = count($slots);
        $this->info("รวม: {$success}/{$total} สำเร็จ");

        return $success === $total ? self::SUCCESS : self::FAILURE;
    }
}
