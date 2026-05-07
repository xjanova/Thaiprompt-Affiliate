<?php

namespace App\Console\Commands;

use App\Jobs\ProcessVoiceSummaryJob;
use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ (2026-05-08) สร้างเสียงสรุปคำทำนาย Celtic 99฿ + push ให้ลูกค้า (background)
 *
 * เรียกจาก ProcessVoiceSummaryJob::dispatchSmart() ผ่าน Artisan::call()
 *   หรือ proc_open() เป็น background process
 *
 * Usage:
 *   php artisan fortune:process-voice-summary {readingId} {platform} {userId}
 *
 * Manual usage (admin retry):
 *   php artisan fortune:process-voice-summary 1234 line U_xxxxxxx
 */
class FortuneProcessVoiceSummary extends Command
{
    protected $signature = 'fortune:process-voice-summary
                            {readingId : FortuneReading ID}
                            {platform : Platform (facebook/line)}
                            {userId : Platform user ID}
                            {--force : บังคับ regenerate แม้จะมี cache}';

    protected $description = '🎙️ สร้างเสียงสรุปคำทำนาย Celtic 99฿ + push ให้ลูกค้า';

    public function handle(): int
    {
        $readingId = (int) $this->argument('readingId');
        $platform = $this->argument('platform');
        $userId = $this->argument('userId');
        $force = (bool) $this->option('force');

        Log::info('🎙️ fortune:process-voice-summary เริ่ม', [
            'reading_id' => $readingId,
            'platform' => $platform,
            'force' => $force,
        ]);

        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            $this->error("ไม่พบ FortuneReading #{$readingId}");

            return self::FAILURE;
        }

        // --force → เคลียร์ cache ก่อน
        if ($force) {
            $voiceService = app(\App\Services\FortuneVoiceSummaryService::class);
            $voiceService->clearCache($reading);
            $reading->setConversationState('voice_summary_pushed', false);
            $this->info("เคลียร์ voice cache ของ #{$readingId} แล้ว");
        }

        // รัน Job synchronously (เพราะ command นี้ถูกเรียกจาก background process แล้ว)
        $job = new ProcessVoiceSummaryJob($readingId, $platform, $userId);

        try {
            $job->handle();
            $this->info("✅ Voice summary สำเร็จ #{$readingId}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ ล้มเหลว: '.$e->getMessage());
            Log::error('fortune:process-voice-summary exception', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
