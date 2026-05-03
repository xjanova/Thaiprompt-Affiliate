<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\FcmNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * fortune:resync-bill {id|bill_reference}
 *
 * Backfill FCM push สำหรับบิล Fortune ใบใดใบหนึ่ง — แอพ SMS Checker จะเห็นบิลทันที
 *
 * เคสที่ใช้:
 *   - บิล Celtic 99฿ ก่อนแก้ bug 2026-05-03 (ไม่มี FCM push เลย)
 *   - บิลที่สร้างตอนแอพ SMS offline / FCM token หมดอายุ
 *   - แอดมิน manual ตรวจพบบิลค้างไม่เห็นใน SMS app
 *
 * ใช้:
 *   php artisan fortune:resync-bill 1224              # ระบุ reading_id
 *   php artisan fortune:resync-bill FT-2025-001234    # ระบุ bill_reference
 *   php artisan fortune:resync-bill 1224 --dry-run    # ดูข้อมูลโดยไม่ส่ง FCM
 */
class FortuneResyncBill extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:resync-bill
        {target : reading_id (ตัวเลข) หรือ bill_reference (FT-...)}
        {--dry-run : แสดงข้อมูลโดยไม่ส่ง FCM จริง}';

    /**
     * @var string
     */
    protected $description = 'ส่ง FCM push ใหม่ให้แอพ SMS Checker เห็นบิล Fortune ใบใดใบหนึ่ง (resync บิลที่ตกหล่น)';

    /**
     * ประมวลผล command
     */
    public function handle(FcmNotificationService $fcmService): int
    {
        $target = (string) $this->argument('target');
        $dryRun = (bool) $this->option('dry-run');

        // หา reading — รองรับทั้ง id ตัวเลข และ bill_reference
        $reading = is_numeric($target)
            ? FortuneReading::find((int) $target)
            : FortuneReading::where('bill_reference', $target)->first();

        if (! $reading) {
            $this->error("❌ ไม่พบ reading: {$target}");

            return self::FAILURE;
        }

        // แสดงรายละเอียดก่อนส่ง
        $this->info('📋 Reading details');
        $this->info('==================');
        $this->table(['Field', 'Value'], [
            ['ID', $reading->id],
            ['bill_reference', $reading->bill_reference ?? '(none)'],
            ['amount_paid', $reading->amount_paid],
            ['status', $reading->conversation_status],
            ['is_paid', $reading->is_paid ? 'true' : 'false'],
            ['platform', $reading->platform ?? '?'],
            ['created_at', $reading->created_at?->format('Y-m-d H:i:s')],
            ['updated_at', $reading->updated_at?->format('Y-m-d H:i:s')],
            ['UPA id', $reading->unique_payment_amount_id ?? '(none)'],
        ]);

        if (empty($reading->bill_reference)) {
            $this->error('❌ บิลนี้ไม่มี bill_reference — ส่ง FCM ไม่ได้');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('[DRY-RUN] ไม่ได้ส่ง FCM จริง');

            return self::SUCCESS;
        }

        // ส่ง FCM push (ใช้ method เดียวกับตอนสร้างบิลใหม่)
        try {
            $result = $fcmService->notifyNewFortuneReading($reading);

            if ($result) {
                $this->info("✅ ส่ง FCM push สำเร็จ — แอพ SMS Checker ควรเห็นบิลภายใน 1-2 วินาที");
                Log::info('fortune:resync-bill: FCM resent', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                ]);

                return self::SUCCESS;
            }

            $this->error('⚠️ FCM service คืน false — ตรวจ FCM credentials / device tokens');

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("❌ FCM ล้ม: {$e->getMessage()}");
            Log::warning('fortune:resync-bill: FCM throw', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
