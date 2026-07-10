<?php

namespace App\Console\Commands;

use App\Models\FortuneContentCampaign;
use App\Services\ContentCampaignAutoPostService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * โพสคอนเทนต์อัตโนมัติแบบหลายแคมเปญ — แต่ละแคมเปญโพสตามตารางเวลาของตัวเอง
 *
 * Usage:
 *   php artisan fortune:content:publish                          # auto-detect: หา slot ของทุกแคมเปญที่ตรงเวลานี้
 *   php artisan fortune:content:publish --campaign=kamlangjai --slot=07:30   # บังคับโพสแคมเปญ+slot
 *   php artisan fortune:content:publish --campaign=karma --slot=08:00 --force  # ลบเก่าแล้วโพสใหม่
 *   php artisan fortune:content:publish --all                    # โพสทุก slot ของทุกแคมเปญที่เปิด (ทดสอบ)
 *
 * เรียกโดย Scheduler ทุก 5 นาที — command match slot ใน window 5 นาที
 * (pattern เดียวกับ fortune:mystic:publish เดิม)
 */
class FortuneContentPublish extends Command
{
    protected $signature = 'fortune:content:publish
        {--campaign= : slug ของแคมเปญ (ใช้คู่กับ --slot)}
        {--slot= : เวลา slot "HH:MM" เช่น 07:30}
        {--date= : วันที่ (YYYY-MM-DD) default: today}
        {--all : โพสทุก slot ของทุกแคมเปญที่เปิด}
        {--force : ลบโพสเก่า (FB + DB) แล้วสร้างใหม่}';

    protected $description = 'โพสคอนเทนต์อัตโนมัติแบบหลายแคมเปญ (กำลังใจ/กฎแห่งกรรม/จิตวิทยา/สายมู ฯลฯ)';

    public function handle(ContentCampaignAutoPostService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'), 'Asia/Bangkok')
            : now('Asia/Bangkok');
        $force = (bool) $this->option('force');

        // ── โหมดระบุแคมเปญ + slot ตรงๆ (manual/ทดสอบ)
        if ($this->option('campaign') !== null) {
            $campaign = FortuneContentCampaign::where('slug', $this->option('campaign'))->first();
            if (! $campaign) {
                $this->error("❌ ไม่พบแคมเปญ slug \"{$this->option('campaign')}\"");

                return self::FAILURE;
            }

            $slot = (string) $this->option('slot');
            if (! preg_match('/^\d{1,2}:\d{2}$/', $slot)) {
                $this->error('❌ ต้องระบุ --slot=HH:MM เช่น --slot=07:30');

                return self::FAILURE;
            }

            return $this->publishOne($service, $campaign, $slot, $date, $force);
        }

        $campaigns = FortuneContentCampaign::where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('ℹ️  ไม่มีแคมเปญที่เปิดใช้งาน → ข้าม');

            return self::SUCCESS;
        }

        // ── โหมด --all: โพสทุก slot ของทุกแคมเปญ (ทดสอบ)
        if ($this->option('all')) {
            $success = 0;
            $total = 0;
            foreach ($campaigns as $campaign) {
                foreach ($campaign->scheduleSlots() as $slot) {
                    $total++;
                    $ok = $this->publishOne($service, $campaign, $slot['key'], $date, $force) === self::SUCCESS;
                    if ($ok) {
                        $success++;
                    }
                }
            }
            $this->info("รวม: {$success}/{$total} สำเร็จ");

            return $success === $total ? self::SUCCESS : self::FAILURE;
        }

        // ── Auto-detect: match slot ของแต่ละแคมเปญใน window 5 นาที (ปัดลง)
        //    แคมเปญล้มเหลวไม่กระทบแคมเปญอื่น — โพสอิสระจากกัน
        $currentTimeMin = (int) $date->format('H') * 60 + (int) $date->format('i');
        $matched = 0;
        $failed = 0;

        foreach ($campaigns as $campaign) {
            foreach ($campaign->scheduleSlots() as $slot) {
                $slotTimeMin = $slot['hour'] * 60 + $slot['minute'];
                $diff = $currentTimeMin - $slotTimeMin;
                if ($diff < 0 || $diff >= 5) {
                    continue;
                }

                $matched++;
                $ok = $this->publishOne($service, $campaign, $slot['key'], $date, $force) === self::SUCCESS;
                if (! $ok) {
                    $failed++;
                }
                break; // แคมเปญละ 1 slot ต่อรอบ (slot ใน window เดียวกันซ้อนไม่ได้อยู่แล้ว)
            }
        }

        if ($matched === 0) {
            $this->info("⏭  เวลา {$date->format('H:i')} ไม่ตรง slot ของแคมเปญใดๆ");

            return self::SUCCESS;
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function publishOne(
        ContentCampaignAutoPostService $service,
        FortuneContentCampaign $campaign,
        string $slotTime,
        Carbon $date,
        bool $force
    ): int {
        $this->info("📣 [{$campaign->name_th}] โพส — {$date->toDateString()} {$slotTime}");

        if ($force) {
            $this->warn('⚠️  --force: จะลบโพสเก่าก่อน republish');
        }

        // 🛡️ try/catch ครอบ — exception หลุด (เช่น DB ล่มชั่วคราว) ต้องไม่ฆ่า loop
        //    ของแคมเปญอื่นที่รออยู่ (สัญญา: แคมเปญล้มไม่กระทบแคมเปญอื่น)
        try {
            $result = $service->generateAndPublish($campaign, $slotTime, $date, $force);
        } catch (\Throwable $e) {
            $this->error('❌ exception: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($result['success']) {
            $this->info('✅ '.($result['message'] ?? 'สำเร็จ'));
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
}
