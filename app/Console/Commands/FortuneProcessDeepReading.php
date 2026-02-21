<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Services\FortuneChannelManager;
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
            // สร้าง services
            $settings = FortuneTellingSetting::getSettings();
            $conversationService = new FortuneConversationService($settings);
            $channelManager = new FortuneChannelManager($settings);

            // ⚡ ถ้า deep_response มีอยู่แล้ว → ข้าม AI generation ไปส่งเลย
            $skipAiGeneration = ! empty($reading->deep_response);

            if (! $skipAiGeneration) {
                // ✅ streaming mode: ส่งคำทำนายทีละข้อทันทีที่ AI สร้างเสร็จ
                // ลูกค้าจะได้รับ chart + คำทำนายแต่ละข้อทันที ไม่ต้องรอ AI ทำครบทุกข้อ
                // (เหมือนกับ ProcessDeepFortuneReadingJob::handle() ที่ใช้ streaming)
                $result = $conversationService->processPaymentConfirmed(
                    $reading, $notification, $channelManager, $platform, $userId
                );

                // ถ้าไม่ได้ streaming (fallback) → ส่งข้อความรวม
                if (empty($result['streaming']) && ! empty($result['message'])) {
                    $channelManager->sendResponse($platform, $userId, $result,
                        ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                }

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                $this->info("✅ สร้างคำทำนาย + ส่งให้ลูกค้า สำเร็จ ({$duration}ms)");
                Log::info('✅ fortune:process-deep สำเร็จ (streaming)', [
                    'reading_id' => $readingId,
                    'action' => $result['action'] ?? 'unknown',
                    'streaming' => ! empty($result['streaming']),
                    'duration_ms' => $duration,
                ]);

                // ✅ บันทึกว่าส่งให้ลูกค้าแล้ว (ป้องกันส่งซ้ำเมื่อ retry)
                $reading->refresh();
                if (! empty($reading->deep_response)) {
                    $reading->setConversationState('reading_ready_sent', true);
                    $reading->setConversationState('reading_sent_directly', true);
                    $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                }
            } else {
                Log::info('fortune:process-deep: ข้าม AI generation (deep_response มีอยู่แล้ว) → ส่งคำทำนายเลย', [
                    'reading_id' => $readingId,
                ]);
            }

            // ✅ Safety net: ส่งคำทำนายที่มีอยู่แล้วแต่ยังไม่เคยส่ง (กรณี retry / deep_response มีอยู่ก่อน)
            $reading->refresh();
            $alreadySentDirectly = $reading->getConversationState('reading_sent_directly', false);

            if (! empty($reading->deep_response) && ! $alreadySentDirectly) {
                try {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $extra = ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'];

                    // 1. ส่ง chart ถ้ายังไม่ได้ส่ง
                    if (! empty($reading->reading_image_url)) {
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'send_chart',
                            'message' => "🔮✨ คำทำนายของคุณ{$name}พร้อมแล้วค่ะ!",
                            'chart_image_url' => $reading->reading_image_url,
                        ], $extra);
                        sleep(2);
                    }

                    // 2. ส่งคำทำนายเชิงลึก (deep_response) — ✅ เช็ค return value + retry
                    $billRef = $reading->bill_reference ?? '-';
                    $readingText = "🌟 *คำทำนายเชิงลึก*\n"
                        . "📋 เลขที่บิล: {$billRef}\n"
                        . "═══════════════════════\n\n"
                        . $reading->deep_response;

                    $deepSent = $channelManager->sendResponse($platform, $userId, [
                        'action' => 'deep_reading_result',
                        'message' => $readingText,
                        'reading' => $reading,
                    ], $extra);

                    // 🔄 Retry: ถ้าส่งไม่สำเร็จ → ลองอีก 2 ครั้ง (เนื้อหาเสียเงิน สำคัญมาก!)
                    if (! $deepSent) {
                        Log::warning('fortune:process-deep: ส่งคำทำนายครั้งที่ 1 ไม่สำเร็จ → retry ใน 5 วิ', ['reading_id' => $readingId]);
                        sleep(5);
                        $deepSent = $channelManager->sendResponse($platform, $userId, [
                            'action' => 'deep_reading_result',
                            'message' => $readingText,
                            'reading' => $reading,
                        ], $extra);
                    }

                    if (! $deepSent) {
                        Log::warning('fortune:process-deep: ส่งคำทำนายครั้งที่ 2 ไม่สำเร็จ → retry ใน 15 วิ', ['reading_id' => $readingId]);
                        sleep(15);
                        $deepSent = $channelManager->sendResponse($platform, $userId, [
                            'action' => 'deep_reading_result',
                            'message' => $readingText,
                            'reading' => $reading,
                        ], $extra);
                    }

                    if ($deepSent) {
                        $reading->setConversationState('reading_ready_sent', true);
                        $reading->setConversationState('reading_sent_directly', true);
                        $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                        Log::info('fortune:process-deep: ✅ ส่งคำทำนาย (safety net) ให้ลูกค้าสำเร็จ', [
                            'reading_id' => $readingId,
                        ]);
                    } else {
                        Log::error('fortune:process-deep: ❌ ส่งคำทำนายไม่สำเร็จ 3 ครั้ง — รอ retry ครั้งหน้า', [
                            'reading_id' => $readingId,
                            'platform' => $platform,
                            'user_id' => $userId,
                        ]);
                    }
                } catch (\Exception $readyErr) {
                    Log::error('fortune:process-deep: ส่งคำทำนายให้ลูกค้าล้มเหลว (exception)', [
                        'reading_id' => $readingId,
                        'error' => $readyErr->getMessage(),
                    ]);
                }
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
}
