<?php

namespace App\Console\Commands;

use App\Models\VideoAutoJob;
use App\Models\VideoAutoProject;
use App\Models\VideoAutoSchedule;
use App\Services\VideoAutomation\VideoAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ประมวลผล Video Automation Jobs
 *
 * คำสั่งนี้จะ:
 * 1. ตรวจสอบและรัน scheduled jobs
 * 2. ประมวลผล pending jobs
 * 3. Retry failed jobs
 * 4. ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ
 */
class ProcessVideoAutomationCommand extends Command
{
    /**
     * ชื่อและ signature ของ command
     *
     * @var string
     */
    protected $signature = 'video-automation:process
                            {--schedules : ประมวลผล scheduled jobs เท่านั้น}
                            {--pending : ประมวลผล pending jobs เท่านั้น}
                            {--retry : Retry failed jobs เท่านั้น}
                            {--cleanup : ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ}
                            {--limit=10 : จำนวน jobs ที่จะประมวลผลต่อครั้ง}
                            {--dry-run : แสดงผลเท่านั้น ไม่ทำจริง}';

    /**
     * คำอธิบายของ command
     *
     * @var string
     */
    protected $description = 'ประมวลผล Video Automation jobs: schedules, pending, retry, cleanup';

    /**
     * VideoAutomationService
     */
    protected VideoAutomationService $service;

    /**
     * สร้าง instance ใหม่
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new VideoAutomationService;
    }

    /**
     * รัน command
     */
    public function handle(): int
    {
        $this->info('🎬 Video Automation Processor');
        $this->info('=============================');

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('📋 DRY RUN MODE - จะแสดงผลเท่านั้น ไม่ทำจริง');
            $this->newLine();
        }

        // ถ้าไม่ระบุ option ใดๆ ให้ทำทั้งหมด
        $runAll = ! $this->option('schedules') &&
                  ! $this->option('pending') &&
                  ! $this->option('retry') &&
                  ! $this->option('cleanup');

        $totalProcessed = 0;

        // 1. Process Schedules
        if ($runAll || $this->option('schedules')) {
            $totalProcessed += $this->processSchedules($limit, $dryRun);
        }

        // 2. Process Pending Jobs
        if ($runAll || $this->option('pending')) {
            $totalProcessed += $this->processPendingJobs($limit, $dryRun);
        }

        // 3. Retry Failed Jobs
        if ($runAll || $this->option('retry')) {
            $totalProcessed += $this->retryFailedJobs($limit, $dryRun);
        }

        // 4. Cleanup Source Files
        if ($runAll || $this->option('cleanup')) {
            $totalProcessed += $this->cleanupSourceFiles($limit, $dryRun);
        }

        $this->newLine();
        $this->info("✅ ประมวลผลทั้งหมด: {$totalProcessed} รายการ");

        return Command::SUCCESS;
    }

    /**
     * ประมวลผล Scheduled Jobs
     */
    protected function processSchedules(int $limit, bool $dryRun): int
    {
        $this->newLine();
        $this->info('📅 กำลังตรวจสอบ Scheduled Jobs...');

        $schedules = VideoAutoSchedule::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', today());
            })
            ->limit($limit)
            ->get();

        if ($schedules->isEmpty()) {
            $this->line('   ไม่มี schedule ที่ถึงเวลา');

            return 0;
        }

        $processed = 0;

        foreach ($schedules as $schedule) {
            $this->line("   🕐 Schedule: {$schedule->name}");

            if ($dryRun) {
                $this->line("      → จะสร้าง {$schedule->videos_per_run} โปรเจกต์");
                $processed++;

                continue;
            }

            try {
                // สร้างโปรเจกต์จาก template
                for ($i = 0; $i < $schedule->videos_per_run; $i++) {
                    $project = VideoAutoProject::createFromTemplate(
                        $schedule->template,
                        [
                            'name' => $schedule->name.' - '.now()->format('Y-m-d H:i:s')." #{$i}",
                            'target_platforms' => $schedule->publish_platforms,
                        ]
                    );

                    // สร้าง job
                    $job = VideoAutoJob::create([
                        'project_id' => $project->id,
                        'job_type' => 'full_pipeline',
                        'status' => 'pending',
                        'trigger_source' => 'schedule',
                    ]);

                    $this->line("      ✓ สร้างโปรเจกต์: {$project->name} (Job #{$job->id})");
                }

                // อัพเดท schedule
                $schedule->last_run_at = now();
                $schedule->next_run_at = $this->calculateNextRun($schedule);
                $schedule->increment('run_count');
                $schedule->increment('success_count');
                $schedule->consecutive_failures = 0;
                $schedule->save();

                $processed++;

            } catch (\Exception $e) {
                $this->error("      ✗ Error: {$e->getMessage()}");

                $schedule->increment('fail_count');
                $schedule->increment('consecutive_failures');
                $schedule->last_error = $e->getMessage();

                if ($schedule->pause_on_error &&
                    $schedule->consecutive_failures >= $schedule->max_consecutive_failures) {
                    $schedule->is_active = false;
                    $this->warn('      ⚠ Schedule ถูกหยุดเนื่องจากล้มเหลวติดต่อกัน');
                }

                $schedule->save();

                Log::error('Video Automation Schedule Error', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("   ✅ ประมวลผล Schedule: {$processed} รายการ");

        return $processed;
    }

    /**
     * ประมวลผล Pending Jobs
     */
    protected function processPendingJobs(int $limit, bool $dryRun): int
    {
        $this->newLine();
        $this->info('⏳ กำลังประมวลผล Pending Jobs...');

        $jobs = VideoAutoJob::where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($jobs->isEmpty()) {
            $this->line('   ไม่มี pending jobs');

            return 0;
        }

        $processed = 0;

        foreach ($jobs as $job) {
            $project = $job->project;

            $this->line("   🔄 Job #{$job->id}: {$project->name}");
            $this->line("      Type: {$job->job_type_label}");

            if ($dryRun) {
                $processed++;

                continue;
            }

            try {
                // รัน job ตาม type
                $result = $this->runJob($job, $project);

                if ($result['success']) {
                    $this->line('      ✓ สำเร็จ');
                    $processed++;

                    // ลบไฟล์ถ้าตั้งค่าไว้
                    if ($project->delete_source_after_publish &&
                        $project->status === 'published') {
                        $this->cleanupProjectFiles($project, $dryRun);
                    }
                } else {
                    $this->error("      ✗ ล้มเหลว: {$result['error']}");
                }

            } catch (\Exception $e) {
                $job->fail($e->getMessage(), $e->getTraceAsString());
                $this->error("      ✗ Exception: {$e->getMessage()}");

                Log::error('Video Automation Job Error', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("   ✅ ประมวลผล Pending: {$processed} jobs");

        return $processed;
    }

    /**
     * Retry Failed Jobs
     */
    protected function retryFailedJobs(int $limit, bool $dryRun): int
    {
        $this->newLine();
        $this->info('🔁 กำลัง Retry Failed Jobs...');

        $jobs = VideoAutoJob::readyToRetry()
            ->limit($limit)
            ->get();

        if ($jobs->isEmpty()) {
            $this->line('   ไม่มี failed jobs ที่พร้อม retry');

            return 0;
        }

        $processed = 0;

        foreach ($jobs as $job) {
            $this->line("   🔄 Job #{$job->id}: Retry #{$job->retry_count}");

            if ($dryRun) {
                $processed++;

                continue;
            }

            if ($job->retry()) {
                $this->line('      ✓ เพิ่มเข้า queue แล้ว');
                $processed++;
            } else {
                $this->line('      ✗ ไม่สามารถ retry ได้');
            }
        }

        $this->info("   ✅ Retry: {$processed} jobs");

        return $processed;
    }

    /**
     * ลบไฟล์ต้นฉบับหลังโพสต์สำเร็จ
     *
     * ⚠️ จะลบได้ต่อเมื่อโพสต์ YouTube สำเร็จเท่านั้น!
     */
    protected function cleanupSourceFiles(int $limit, bool $dryRun): int
    {
        $this->newLine();
        $this->info('🧹 กำลังลบไฟล์ต้นฉบับ...');
        $this->warn('   ⚠️ จะลบเฉพาะโปรเจกต์ที่โพสต์ YouTube สำเร็จแล้วเท่านั้น');

        // ดึงโปรเจกต์ที่โพสต์แล้วและต้องการลบไฟล์
        $projects = VideoAutoProject::where('status', 'published')
            ->where('delete_source_after_publish', true)
            ->where('source_files_deleted', false)
            ->whereNotNull('last_published_at')
            ->where('last_published_at', '<=', now()->subMinutes(5)) // รอ 5 นาทีหลังโพสต์
            ->limit($limit)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('   ไม่มีไฟล์ที่ต้องลบ');

            return 0;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($projects as $project) {
            $this->line("   🗑️ โปรเจกต์: {$project->name}");

            // ⚠️ ตรวจสอบว่าโพสต์ YouTube สำเร็จหรือไม่
            if (! $project->hasSuccessfulYouTubePublish()) {
                $this->warn('      ⏭️ ข้าม - ยังไม่มีการโพสต์ YouTube สำเร็จ');
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line('      → จะลบไฟล์ต้นฉบับ');
                $processed++;

                continue;
            }

            $this->cleanupProjectFiles($project, $dryRun);
            $processed++;
        }

        $this->info("   ✅ Cleanup: {$processed} โปรเจกต์");
        if ($skipped > 0) {
            $this->warn("   ⏭️ ข้าม: {$skipped} โปรเจกต์ (ยังไม่โพสต์ YouTube สำเร็จ)");
        }

        return $processed;
    }

    /**
     * ลบไฟล์ของโปรเจกต์
     */
    protected function cleanupProjectFiles(VideoAutoProject $project, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line("      → จะลบไฟล์ต้นฉบับของ: {$project->name}");

            return;
        }

        if ($project->deleteSourceFiles()) {
            $this->line('      ✓ ลบไฟล์ต้นฉบับแล้ว');

            // อัพเดท publish history
            $project->publishHistory()
                ->where('source_deleted', false)
                ->update([
                    'source_deleted' => true,
                    'source_deleted_at' => now(),
                ]);
        } else {
            $this->line('      - ไม่มีไฟล์ที่ต้องลบ');
        }
    }

    /**
     * รัน job
     */
    protected function runJob(VideoAutoJob $job, VideoAutoProject $project): array
    {
        switch ($job->job_type) {
            case 'full_pipeline':
                return $this->service->runFullPipeline($project, $job);

            case 'generate_music':
                $job->start();
                $result = $this->service->generateMusic($project, $job);
                if ($result['success']) {
                    $job->complete($result);
                } else {
                    $job->fail($result['error'] ?? 'Unknown error');
                }

                return $result;

            case 'generate_images':
                $job->start();
                $result = $this->service->generateImages($project, $job);
                if ($result['success']) {
                    $job->complete($result);
                } else {
                    $job->fail($result['error'] ?? 'Unknown error');
                }

                return $result;

            case 'create_video':
                $job->start();
                $result = $this->service->createVideo($project, $job);
                if ($result['success']) {
                    $job->complete($result);
                } else {
                    $job->fail($result['error'] ?? 'Unknown error');
                }

                return $result;

            default:
                return [
                    'success' => false,
                    'error' => "Unsupported job type: {$job->job_type}",
                ];
        }
    }

    /**
     * คำนวณเวลารันครั้งต่อไป
     */
    protected function calculateNextRun(VideoAutoSchedule $schedule): ?\Carbon\Carbon
    {
        $now = now()->timezone($schedule->timezone);

        switch ($schedule->frequency_type) {
            case 'once':
                return null; // ไม่มีครั้งต่อไป

            case 'daily':
                $next = $now->copy()->setTimeFromTimeString($schedule->daily_time);
                if ($next->lte($now)) {
                    $next->addDay();
                }

                return $next;

            case 'weekly':
                $days = $schedule->weekly_days ?? [1]; // Default วันจันทร์
                $time = $schedule->weekly_time ?? '09:00';

                $next = $now->copy()->setTimeFromTimeString($time);

                // หาวันถัดไปที่ตรงกับ weekly_days
                for ($i = 0; $i < 8; $i++) {
                    if (in_array($next->dayOfWeek, $days) && $next->gt($now)) {
                        return $next;
                    }
                    $next->addDay();
                }

                return $next;

            case 'monthly':
                $days = $schedule->monthly_days ?? [1];
                $time = $schedule->monthly_time ?? '09:00';

                $next = $now->copy()->setTimeFromTimeString($time);

                // หาวันที่ถัดไปที่ตรงกับ monthly_days
                for ($i = 0; $i < 35; $i++) {
                    if (in_array($next->day, $days) && $next->gt($now)) {
                        return $next;
                    }
                    $next->addDay();
                }

                return $next;

            case 'custom':
                // TODO: Parse cron expression
                return $now->addDay();

            default:
                return $now->addDay();
        }
    }
}
