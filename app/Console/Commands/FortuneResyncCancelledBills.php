<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\FcmNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * fortune:resync-cancelled-bills
 *
 * Backfill FCM push สำหรับบิลที่ถูกยกเลิกแล้ว เพื่อให้แอพ smschecker
 * ลบบิลที่ยังค้างอยู่ใน UI (รอ admin อนุมัติ)
 *
 * เหตุผล: บิลที่ถูกยกเลิกแล้ว (ทั้ง auto-expired และ user-cancelled) อาจค้างใน
 * UI ของแอพ smschecker เพราะ:
 *   - แอพออฟไลน์ตอน FCM ส่งไป
 *   - FCM token หมดอายุ
 *   - บิลถูกยกเลิกก่อนระบบ FCM cancellation ถูกเพิ่มเข้ามา (legacy)
 *
 * วิธีทำ: scan FortuneReading ที่ STATUS_COMPLETED + is_paid=false +
 * มี bill_reference + updated_at อยู่ใน window ที่ระบุ (default 30 วัน)
 * แล้วส่ง FCM `notifyFortuneReadingCancelled` ทีละใบ พร้อม cancellation_reason
 *
 * รันอัตโนมัติทุกวันตอน 06:00 (low traffic) หรือเรียก manual ก็ได้
 */
class FortuneResyncCancelledBills extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:resync-cancelled-bills
        {--days=30 : จำนวนวันย้อนหลังที่จะ scan (default 30)}
        {--limit=200 : จำกัดจำนวนบิลต่อรอบ (กัน FCM rate limit)}
        {--dry-run : แสดงจำนวนโดยไม่ส่ง FCM จริง}';

    /**
     * @var string
     */
    protected $description = 'Backfill FCM push ให้แอพ smschecker ลบบิลที่ถูกยกเลิกแล้วซึ่งยังค้างใน UI';

    /**
     * ประมวลผล command
     */
    public function handle(FcmNotificationService $fcmService): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);

        $this->info('🔄 Resync cancelled bills');
        $this->info('========================');
        $this->info("Window: {$days} วันย้อนหลัง (since {$cutoff->format('Y-m-d H:i')})");
        $this->info("Limit: {$limit} บิลต่อรอบ");
        $this->info('');

        // Scan บิลที่ถูกยกเลิกแล้ว: STATUS_COMPLETED + is_paid=false + มี bill_reference
        //   conversation_status = COMPLETED + is_paid = false → ตรงตามเงื่อนไขที่
        //   transformFortuneReadingToOrderApproval() ใน SmsPaymentController แปลงเป็น 'cancelled'
        $cancelledReadings = FortuneReading::where('conversation_status', FortuneReading::STATUS_COMPLETED)
            ->where('is_paid', false)
            ->whereNotNull('bill_reference')
            ->where('updated_at', '>=', $cutoff)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        if ($cancelledReadings->isEmpty()) {
            $this->info('✅ ไม่มีบิลยกเลิกใน window นี้');

            return self::SUCCESS;
        }

        $this->info("พบบิลยกเลิก {$cancelledReadings->count()} ใบ");

        // นับสถิติแยกตามประเภท
        $stats = [
            'auto_expired' => 0,
            'user_cancelled' => 0,
            'auto_expired_grace' => 0,
            'unknown' => 0,
        ];

        foreach ($cancelledReadings as $reading) {
            $reason = 'unknown';
            $state = $reading->conversation_state ?? [];
            if (is_array($state) && ! empty($state['cancellation_reason'])) {
                $reason = (string) $state['cancellation_reason'];
            }
            $stats[$reason] = ($stats[$reason] ?? 0) + 1;
        }

        $this->table(['ประเภทยกเลิก', 'จำนวน'], [
            ['auto_expired (Phase J 30 นาที)', $stats['auto_expired']],
            ['user_cancelled (กดยกเลิกเอง)', $stats['user_cancelled']],
            ['auto_expired_grace (cleanup 90 นาที)', $stats['auto_expired_grace']],
            ['unknown (บิลเก่าก่อนระบบ)', $stats['unknown']],
        ]);

        if ($dryRun) {
            $this->warn('[DRY-RUN] ไม่ได้ส่ง FCM จริง');

            return self::SUCCESS;
        }

        // ส่ง FCM ทีละใบ
        $sent = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($cancelledReadings->count());
        $bar->start();

        foreach ($cancelledReadings as $reading) {
            try {
                $result = $fcmService->notifyFortuneReadingCancelled($reading);
                if ($result) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                Log::warning('FortuneResyncCancelledBills: FCM ล้ม (ข้าม)', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        Log::info('FortuneResyncCancelledBills: backfill เสร็จ', [
            'window_days' => $days,
            'total_scanned' => $cancelledReadings->count(),
            'fcm_sent' => $sent,
            'fcm_failed' => $failed,
            'stats' => $stats,
        ]);

        $this->info("✅ ส่ง FCM สำเร็จ: {$sent} / {$cancelledReadings->count()} บิล");
        if ($failed > 0) {
            $this->warn("⚠️  ส่งไม่สำเร็จ: {$failed} บิล (token หมดอายุหรือแอพออฟไลน์)");
        }

        return self::SUCCESS;
    }
}
