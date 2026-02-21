<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Services\FortuneConversationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * สร้างคำทำนายเชิงลึกและส่งให้ลูกค้า (background process)
 *
 * ใช้เรียกผ่าน exec() จาก web request เพื่อหลีกเลี่ยง web server timeout
 * Command นี้รัน process แยก → ไม่ติด nginx/Apache timeout
 *
 * Usage:
 *   php artisan fortune:process-deep {readingId} {platform} {userId} [--notification-id=]
 */
class FortuneProcessDeepReading extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fortune:process-deep
                            {readingId : FortuneReading ID}
                            {platform : Platform (facebook/line)}
                            {userId : Platform user ID (Facebook PSID)}
                            {--notification-id= : SmsPaymentNotification ID (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'สร้างคำทำนายเชิงลึกและส่งให้ลูกค้าผ่าน Messenger (background)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $readingId = (int) $this->argument('readingId');
        $platform = $this->argument('platform');
        $userId = $this->argument('userId');
        $notificationId = $this->option('notification-id') ? (int) $this->option('notification-id') : null;

        $startTime = microtime(true);

        Log::info('🔮 fortune:process-deep เริ่มประมวลผล', [
            'reading_id' => $readingId,
            'platform' => $platform,
            'user_id' => $userId,
            'notification_id' => $notificationId,
        ]);

        // ดึง FortuneReading
        $reading = FortuneReading::find($readingId);

        if (! $reading) {
            $this->error("ไม่พบ FortuneReading #{$readingId}");
            Log::error('fortune:process-deep: ไม่พบ FortuneReading', ['reading_id' => $readingId]);

            return self::FAILURE;
        }

        // ถ้าเสร็จแล้ว → เช็คว่าส่งลูกค้าจริงหรือยัง
        if (! empty($reading->deep_response) && $reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
            $alreadySent = $reading->getConversationState('reading_sent_directly', false);

            if ($alreadySent) {
                $this->info("FortuneReading #{$readingId} เสร็จ + ส่งแล้ว — ข้าม");

                return self::SUCCESS;
            }

            // ⚠️ คำทำนายเสร็จแล้วแต่ยังไม่เคยส่งลูกค้า → ส่งเลย!
            $this->info("FortuneReading #{$readingId} มีคำทำนายแล้วแต่ยังไม่ได้ส่ง → กำลังส่ง...");
            Log::info('fortune:process-deep: คำทำนายเสร็จแล้วแต่ยังไม่ได้ส่ง → retry ส่ง', [
                'reading_id' => $readingId,
                'platform' => $platform,
                'user_id' => $userId,
            ]);

            // ใช้ flow ส่งคำทำนายด้านล่าง (ไม่ return ที่นี่)
        }

        // ดึง SMS notification (ถ้ามี)
        $notification = $notificationId ? SmsPaymentNotification::find($notificationId) : null;

        try {
            // สร้าง services (ไม่สร้าง channelManager — ไม่ push อีกต่อไป)
            $settings = FortuneTellingSetting::getSettings();
            $conversationService = new FortuneConversationService($settings);

            // ⚡ ถ้า deep_response มีอยู่แล้ว → ข้าม AI generation ไปส่งเลย
            $skipAiGeneration = ! empty($reading->deep_response);

            if (! $skipAiGeneration) {
                // ✅ สร้างคำทำนายด้วย AI (ไม่ push ให้ลูกค้าผ่าน pushMessage)
                // แนวทาง V3: ไม่ใช้ pushMessage สำหรับ fortune delivery (โควต้าจำกัด!)
                // → บันทึกลง DB เท่านั้น → เมื่อลูกค้าส่งข้อความมา จะส่งผ่าน replyMessage (ฟรี!)
                $result = $conversationService->processPaymentConfirmed(
                    $reading, $notification, null, null, null // ไม่ส่ง channelManager → ไม่ push
                );

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                $this->info("✅ สร้างคำทำนาย สำเร็จ ({$duration}ms) — บันทึก DB แล้ว รอ user ดึงผ่าน replyMessage");
                Log::info('✅ fortune:process-deep สำเร็จ — ไม่ push (รอ replyMessage)', [
                    'reading_id' => $readingId,
                    'action' => $result['action'] ?? 'unknown',
                    'duration_ms' => $duration,
                    'deep_response_length' => mb_strlen($reading->fresh()?->deep_response ?? ''),
                ]);

                // ✅ ตั้ง flag "คำทำนายพร้อม" → เมื่อ user ส่งข้อความมาจะได้รับผ่าน replyMessage
                $reading->refresh();
                if (! empty($reading->deep_response)) {
                    $reading->setConversationState('reading_ready_for_reply', true);
                    $reading->setConversationState('reading_ready_at', now()->toIso8601String());
                }
            } else {
                Log::info('fortune:process-deep: ข้าม AI generation (deep_response มีอยู่แล้ว)', [
                    'reading_id' => $readingId,
                ]);

                // deep_response มีแล้ว → ตั้ง flag พร้อมส่ง
                $reading->setConversationState('reading_ready_for_reply', true);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->error("❌ ล้มเหลว: {$e->getMessage()} ({$duration}ms)");
            Log::error('❌ fortune:process-deep ล้มเหลว', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // ✅ เปลี่ยนสถานะเป็น completed เพื่อไม่ให้บิลค้างที่ paid ตลอดไป
            // fortune:check-pending จะ retry ให้อีกครั้ง (ถ้า deep_response ยังว่าง)
            try {
                $reading = FortuneReading::find($readingId);
                if ($reading && $reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                    $reading->update([
                        'conversation_status' => FortuneReading::STATUS_COMPLETED,
                    ]);
                    Log::info('fortune:process-deep: เปลี่ยนสถานะเป็น completed หลังล้มเหลว', [
                        'reading_id' => $readingId,
                    ]);
                }
            } catch (\Exception $statusErr) {
                Log::error('fortune:process-deep: เปลี่ยนสถานะไม่สำเร็จ', [
                    'reading_id' => $readingId,
                    'error' => $statusErr->getMessage(),
                ]);
            }

            // ❌ ไม่ส่ง error message ให้ลูกค้า — ลูกค้าได้รับ "รอสักครู่" ไปแล้ว
            // fortune:check-pending จะ retry ให้อัตโนมัติทุก 1 นาที
            // ถ้ารอนานเกิน 10 นาที → check-pending จะส่ง "คนใช้งานมาก" แทน

            return self::FAILURE;
        }
    }

    // ✅ V3: ไม่ push คำทำนายอีกต่อไป — ส่งผ่าน replyMessage เมื่อ user ส่งข้อความมา (ฟรี!)
}
