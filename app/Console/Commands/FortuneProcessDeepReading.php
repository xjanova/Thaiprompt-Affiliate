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

        // ถ้าเสร็จแล้ว → ข้าม
        if (! empty($reading->deep_response) && $reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
            $this->info("FortuneReading #{$readingId} เสร็จแล้ว — ข้าม");

            return self::SUCCESS;
        }

        // ดึง SMS notification (ถ้ามี)
        $notification = $notificationId ? SmsPaymentNotification::find($notificationId) : null;

        try {
            // สร้าง services
            $settings = FortuneTellingSetting::getSettings();
            $conversationService = new FortuneConversationService($settings);
            $channelManager = new FortuneChannelManager($settings);

            // 🔄 เปลี่ยนเป็น batch mode: ไม่ส่ง channelManager (ปิด streaming)
            // เก็บผลทำนายใน DB อย่างเดียว → ส่งข้อความ "คำทำนายพร้อมแล้ว" + ปุ่ม ทีหลัง
            $result = $conversationService->processPaymentConfirmed(
                $reading, $notification, null, $platform, $userId
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ สร้างคำทำนาย สำเร็จ ({$duration}ms)");
            Log::info('✅ fortune:process-deep สำเร็จ', [
                'reading_id' => $readingId,
                'action' => $result['action'] ?? 'unknown',
                'duration_ms' => $duration,
            ]);

            // ✅ ส่งข้อความ "คำทำนายพร้อมแล้ว" พร้อมปุ่ม [ดูเลย] [ไว้ดูทีหลัง]
            // Reload reading เพื่อดึง deep_response ล่าสุด
            $reading->refresh();

            if (! empty($reading->deep_response)) {
                // ✅ คำทำนายสร้างสำเร็จ → ส่งข้อความ "พร้อมแล้ว" + ปุ่ม
                try {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $readyMessage = "🔮✨ คำทำนายของ{$name}พร้อมแล้วค่ะ!\n\n"
                        . "จันทราได้ตรวจดวงชะตาเรียบร้อยแล้ว พร้อมดูเลยไหมคะ?";

                    $sent = $channelManager->sendResponse($platform, $userId, [
                        'action' => 'reading_ready',
                        'message' => $readyMessage,
                        'show_quick_replies' => true,
                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                    // บันทึกสถานะว่าส่งข้อความ "พร้อมแล้ว" แล้ว
                    $reading->setConversationState('reading_ready_sent', true);
                    $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                    Log::info('fortune:process-deep: ส่งข้อความ "คำทำนายพร้อมแล้ว" + ปุ่ม', [
                        'reading_id' => $readingId,
                        'platform' => $platform,
                        'user_id' => $userId,
                        'sent_result' => $sent,
                    ]);
                } catch (\Exception $readyErr) {
                    Log::error('fortune:process-deep: ส่งข้อความ "คำทำนายพร้อมแล้ว" ล้มเหลว', [
                        'reading_id' => $readingId,
                        'platform' => $platform,
                        'user_id' => $userId,
                        'error' => $readyErr->getMessage(),
                    ]);
                }
            } else {
                // ❌ คำทำนายไม่ได้ถูกบันทึก → แจ้งลูกค้าว่าระบบกำลังพยายามอีกครั้ง
                Log::error('fortune:process-deep: deep_response ว่างหลัง processPaymentConfirmed สำเร็จ', [
                    'reading_id' => $readingId,
                    'result_action' => $result['action'] ?? 'unknown',
                ]);

                try {
                    $channelManager->sendResponse($platform, $userId, [
                        'action' => 'error',
                        'message' => "🔮 ขออภัยค่ะ ระบบกำลังสร้างคำทำนายอยู่ กรุณารอสักครู่นะคะ\n\nจันทราจะส่งคำทำนายให้เร็วที่สุดค่ะ 🙏",
                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                } catch (\Exception $errMsgErr) {
                    Log::error('fortune:process-deep: ส่งข้อความ fallback ล้มเหลว', [
                        'error' => $errMsgErr->getMessage(),
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

            // ส่งข้อความ error ให้ user
            try {
                $settings = FortuneTellingSetting::getSettings();
                $channelManager = new FortuneChannelManager($settings);

                $channelManager->sendResponse($platform, $userId, [
                    'action' => 'error',
                    'message' => "🔮 ขออภัยค่ะ ระบบสร้างคำทำนายเชิงลึกขัดข้อง\n\nกรุณาทักแชทเพื่อแจ้งแอดมินได้เลยค่ะ 🙏",
                ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
            } catch (\Exception $msgErr) {
                Log::error('fortune:process-deep: ส่งข้อความ error ไม่สำเร็จ', [
                    'error' => $msgErr->getMessage(),
                ]);
            }

            return self::FAILURE;
        }
    }
}
