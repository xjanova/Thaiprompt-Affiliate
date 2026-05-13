<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔁 (2026-05-13) Retry reading ใด ๆ ที่ค้าง — force re-dispatch AI Job
 *
 * ใช้กรณี:
 *   - Reading paid + AI fail → status=completed + deep_response = error text
 *   - ลูกค้าค้างไม่ได้รับคำทำนาย แม้จ่ายเงินแล้ว
 *   - Pay-First Deep 39 ที่หลุด flow → ต้อง dispatch Job ใหม่
 *
 * Difference จาก fortune:check-pending:
 *   check-pending: filter เฉพาะ deep_response=NULL (ไม่จับเคส error text)
 *   retry-reading: รับ ID ตรง + clear deep_response ก่อน dispatch
 *
 * Usage:
 *   php artisan fortune:retry-reading 2474         # retry reading ID 2474
 *   php artisan fortune:retry-reading --all-failed # ทุก reading paid + ไม่มีคำทำนายจริง
 *   php artisan fortune:retry-reading --dry        # dry run (กับ --all-failed)
 */
class FortuneRetryReading extends Command
{
    protected $signature = 'fortune:retry-reading
                            {readingId? : Reading ID — ถ้าไม่ระบุต้อง --all-failed}
                            {--all-failed : Retry ทุก reading paid + AI fail + paid_at < 48hr}
                            {--dry : Dry run — แสดง list ไม่ dispatch}
                            {--hours=48 : ค้นย้อนหลังกี่ชั่วโมง (default 48 สำหรับ --all-failed)}';

    protected $description = '🔁 Retry AI generate สำหรับ reading ค้าง (paid + ยังไม่ได้คำทำนายจริง)';

    public function handle(): int
    {
        $specificId = $this->argument('readingId');
        $allFailed = $this->option('all-failed');
        $isDry = $this->option('dry');
        $hours = (int) $this->option('hours');

        if (! $specificId && ! $allFailed) {
            $this->error('ต้องระบุ readingId หรือ --all-failed');

            return 1;
        }

        $query = FortuneReading::where('reading_type', 'deep')
            ->where('is_paid', true);

        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            // --all-failed: paid + ยังไม่ได้คำทำนายจริง
            //   เกณฑ์: deep_response NULL OR เป็น error text (มีคำว่า "ขัดข้อง"/"ล้มเหลว")
            //   และ paid_at < $hours ago
            $query->where('paid_at', '>=', now()->subHours($hours))
                ->where(function ($q) {
                    $q->whereNull('deep_response')
                        ->orWhere('deep_response', '')
                        ->orWhere('deep_response', 'like', '%ขัดข้อง%')
                        ->orWhere('deep_response', 'like', '%AI%ล้มเหลว%')
                        ->orWhere('deep_response', 'like', '%ทีมงาน%ได้รับแจ้ง%');
                });
        }

        $stuck = $query->orderBy('paid_at', 'desc')->get();

        if ($stuck->isEmpty()) {
            $this->info('✅ ไม่พบ reading ที่ต้อง retry');

            return 0;
        }

        $this->info("🔍 พบ {$stuck->count()} reading ที่ค้าง:");
        $this->table(
            ['ID', 'User', 'Platform', 'จ่าย', 'จ่ายเมื่อ', 'Status', 'มี Birth?', 'มี Questions?'],
            $stuck->map(fn ($r) => [
                $r->id,
                $r->facebook_user_name ?? '-',
                $r->platform ?? '?',
                number_format($r->amount_paid, 2),
                $r->paid_at?->diffForHumans() ?? '?',
                $r->conversation_status,
                $r->birth_date ? '✅' : '❌',
                ! empty($r->questions) ? '✅ '.count($r->questions) : '❌',
            ])->toArray()
        );

        if ($isDry) {
            $this->warn('Dry run — ไม่ได้ retry จริง');

            return 0;
        }

        $dispatched = 0;
        $skipped = 0;

        foreach ($stuck as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    $this->warn("  ⚠️ #{$reading->id} skip — ไม่มี user_id");
                    $skipped++;

                    continue;
                }

                if (empty($reading->birth_date)) {
                    $this->warn("  ⚠️ #{$reading->id} skip — ไม่มี birth_date (ต้อง recover-paid-no-birthdate แทน)");
                    $skipped++;

                    continue;
                }

                if (empty($reading->questions)) {
                    $this->warn("  ⚠️ #{$reading->id} skip — ไม่มี questions");
                    $skipped++;

                    continue;
                }

                // Clear error text + reset flags ก่อน dispatch
                $reading->update([
                    'deep_response' => null,
                    'conversation_status' => FortuneReading::STATUS_PAID,
                ]);
                $reading->setConversationState('reading_sent_directly', false);
                $reading->setConversationState('reading_ready_sent', false);
                $reading->setConversationState('reading_notification_attempted', false);
                $reading->setConversationState('reading_notification_retry_count', 0);
                $reading->setConversationState('ai_failed_alert', false);

                // Dispatch Job
                ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id,
                    null,
                    $platform,
                    $userId
                );

                $this->info("  ✅ #{$reading->id} {$reading->facebook_user_name} — dispatch AI Job สำเร็จ ({$platform})");
                Log::info('Fortune Retry: dispatch Job สำเร็จ', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'user' => $reading->facebook_user_name,
                ]);
                $dispatched++;
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $skipped++;
                Log::error('Fortune Retry: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: dispatched {$dispatched} | skipped {$skipped}");

        return 0;
    }
}
