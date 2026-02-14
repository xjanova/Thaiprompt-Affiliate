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

            // เรียก processPaymentConfirmed() — streaming mode ส่งทีละข้อ
            $result = $conversationService->processPaymentConfirmed(
                $reading, $notification, $channelManager, $platform, $userId
            );

            // ถ้าไม่ได้ streaming (fallback) → ส่งข้อความรวม
            if (empty($result['streaming']) && ! empty($result['message'])) {
                $channelManager->sendResponse($platform, $userId, $result);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ สร้างคำทำนาย + ส่ง Messenger สำเร็จ ({$duration}ms)");
            Log::info('✅ fortune:process-deep สำเร็จ', [
                'reading_id' => $readingId,
                'action' => $result['action'] ?? 'unknown',
                'duration_ms' => $duration,
            ]);

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

            // ส่งข้อความ error ให้ user
            try {
                $settings = FortuneTellingSetting::getSettings();
                $channelManager = new FortuneChannelManager($settings);

                $channelManager->sendResponse($platform, $userId, [
                    'action' => 'error',
                    'message' => "🔮 ขออภัยค่ะ ระบบสร้างคำทำนายเชิงลึกขัดข้อง\n\nกรุณาทักแชทเพื่อแจ้งแอดมินได้เลยค่ะ 🙏",
                ], ['from_admin' => true]);
            } catch (\Exception $msgErr) {
                Log::error('fortune:process-deep: ส่งข้อความ error ไม่สำเร็จ', [
                    'error' => $msgErr->getMessage(),
                ]);
            }

            return self::FAILURE;
        }
    }
}
