<?php

namespace App\Console\Commands;

use App\Jobs\GenerateHoroscopeContentJob;
use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Services\FortuneHoroscopePublishService;
use App\Services\FortuneHoroscopeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * สร้างและโพสดวงรายวันอัตโนมัติ
 *
 * ใช้กับ Laravel Scheduler:
 * - fortune:horoscope-process --generate  (ทุก 15 นาที)
 * - fortune:horoscope-process --publish   (ทุก 5 นาที)
 */
class FortuneHoroscopeProcess extends Command
{
    protected $signature = 'fortune:horoscope-process
        {--generate : สร้างเนื้อหาดวงรายวัน}
        {--publish : โพสเนื้อหาที่พร้อมแล้ว}
        {--campaign= : ระบุ campaign ID}
        {--date= : ระบุวันที่ (Y-m-d) default=วันนี้}
        {--dry-run : แสดงผลอย่างเดียว ไม่ทำจริง}
        {--sync : รันแบบ synchronous ไม่ dispatch job}';

    protected $description = 'สร้างและโพสดวงรายวันอัตโนมัติ (AI + Facebook/LINE)';

    public function handle(): int
    {
        $isGenerate = $this->option('generate');
        $isPublish = $this->option('publish');
        $campaignId = $this->option('campaign');
        $dateStr = $this->option('date');
        $isDryRun = $this->option('dry-run');
        $isSync = $this->option('sync');

        $targetDate = $dateStr ? Carbon::parse($dateStr) : Carbon::now('Asia/Bangkok');

        if (! $isGenerate && ! $isPublish) {
            $this->error('ต้องระบุ --generate หรือ --publish');

            return self::FAILURE;
        }

        if ($isGenerate) {
            return $this->processGenerate($campaignId, $targetDate, $isDryRun, $isSync);
        }

        if ($isPublish) {
            return $this->processPublish($campaignId, $targetDate, $isDryRun);
        }

        return self::SUCCESS;
    }

    /**
     * สร้างเนื้อหาดวงรายวัน
     */
    protected function processGenerate(?string $campaignId, Carbon $targetDate, bool $isDryRun, bool $isSync): int
    {
        $this->info("🔮 ตรวจสอบแคมเปญดวงรายวัน สำหรับวันที่ {$targetDate->toDateString()}...");

        $query = FortuneHoroscopeCampaign::readyToGenerate();
        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->line('ไม่มีแคมเปญที่พร้อมสร้างเนื้อหา');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            // เช็คว่าวันนี้เป็นวันที่กำหนดไว้หรือไม่
            if (! $campaign->isActiveDayToday()) {
                $this->line("⏭ [{$campaign->name}] วันนี้ไม่ใช่วันที่กำหนด ข้าม");

                continue;
            }

            // เช็คว่าถึงเวลาหรือยัง (ก่อนเวลา schedule_time 30 นาที)
            $scheduleTime = $campaign->getScheduleTimeCarbon();
            $windowStart = $scheduleTime->copy()->subMinutes(30);

            if (now($campaign->timezone)->lt($windowStart)) {
                $this->line("⏳ [{$campaign->name}] ยังไม่ถึงเวลา (โพส {$campaign->schedule_time})");

                continue;
            }

            $this->info("📝 [{$campaign->name}] กำลังสร้างเนื้อหา...");

            if ($isDryRun) {
                $this->warn("  [DRY-RUN] จะสร้างดวง " . count($campaign->getTargetBirthDays()) . ' วันเกิด');

                continue;
            }

            if ($isSync) {
                // รันตรง ไม่ผ่าน queue
                $service = new FortuneHoroscopeService;
                $result = $service->generateDailyContent($campaign, $targetDate);
                $this->info("  ✅ สำเร็จ {$result['success']} / ล้มเหลว {$result['failed']}");
            } else {
                // Dispatch job
                GenerateHoroscopeContentJob::dispatch($campaign, $targetDate);
                $this->info('  📤 Dispatch job สร้างเนื้อหาแล้ว');
            }
        }

        return self::SUCCESS;
    }

    /**
     * โพสเนื้อหาที่พร้อมแล้ว
     */
    protected function processPublish(?string $campaignId, Carbon $targetDate, bool $isDryRun): int
    {
        $this->info("📤 ตรวจสอบเนื้อหาพร้อมโพส สำหรับวันที่ {$targetDate->toDateString()}...");

        $query = FortuneHoroscopeCampaign::active();
        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        foreach ($campaigns as $campaign) {
            // เช็คว่าถึงเวลาโพสหรือยัง
            $scheduleTime = $campaign->getScheduleTimeCarbon();
            if (now($campaign->timezone)->lt($scheduleTime)) {
                continue;
            }

            // เช็คว่ามีเนื้อหาที่พร้อมแล้วหรือยัง
            $contentCount = FortuneHoroscopeContent::where('campaign_id', $campaign->id)
                ->whereDate('target_date', $targetDate)
                ->where('status', FortuneHoroscopeContent::STATUS_GENERATED)
                ->count();

            if ($contentCount === 0) {
                continue;
            }

            // เช็คว่าโพสแล้วหรือยัง
            $alreadyPosted = $campaign->posts()
                ->whereDate('target_date', $targetDate)
                ->where('status', 'posted')
                ->count();

            if ($alreadyPosted >= count($campaign->getPlatforms())) {
                continue;
            }

            $this->info("📤 [{$campaign->name}] มี {$contentCount} เนื้อหาพร้อมโพส");

            if ($isDryRun) {
                $this->warn('  [DRY-RUN] จะโพสลง: ' . implode(', ', $campaign->getPlatforms()));

                continue;
            }

            $publishService = new FortuneHoroscopePublishService;
            $result = $publishService->createAndPublishPosts($campaign, $targetDate);

            $this->info("  ✅ โพสสำเร็จ {$result['posts_published']} / สร้าง {$result['posts_created']}");

            if (! empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->warn("  ⚠ {$error}");
                }
            }
        }

        return self::SUCCESS;
    }
}
