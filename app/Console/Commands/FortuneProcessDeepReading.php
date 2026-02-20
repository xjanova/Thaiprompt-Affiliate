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
                // ✅ ส่ง Chart Image ให้ลูกค้าทันที (ก่อนรอ AI ทำงาน ~1-5 นาที)
                // ลูกค้าจะได้เห็นรูปดวงดาวก่อนเลย ไม่ต้องรอ AI เสร็จ
                try {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $birthDate = $reading->birth_date?->format('Y-m-d');
                    $chartService = new \App\Services\FortuneChartService;
                    $chartUrl = $reading->reading_image_url; // เช็ค chart ที่สร้างไว้แล้ว

                    if (empty($chartUrl)) {
                        // สร้าง chart ใหม่
                        if ($birthDate) {
                            $chartUrl = $chartService->generateBirthChart(
                                $birthDate, $name, $reading->user_profile['gender'] ?? null
                            );
                        } else {
                            $chartUrl = $chartService->generateQuickChart($name);
                        }

                        if ($chartUrl) {
                            $reading->update(['reading_image_url' => $chartUrl]);
                        }
                    }

                    // ส่ง chart ให้ลูกค้าทันที
                    if ($chartUrl && ! empty($userId)) {
                        $extra = ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'];
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'send_chart',
                            'message' => "🔮 ดวงชะตาของคุณ{$name}ค่ะ ✨\n\n⏳ กำลังวิเคราะห์คำทำนายเชิงลึก รอสักครู่นะคะ...",
                            'chart_image_url' => $chartUrl,
                        ], $extra);

                        $reading->setConversationState('chart_sent_early', true);
                        Log::info('fortune:process-deep: ส่ง chart ให้ลูกค้าก่อนรอ AI', [
                            'reading_id' => $readingId,
                            'chart_url' => $chartUrl,
                        ]);
                    }
                } catch (\Throwable $chartErr) {
                    Log::error('fortune:process-deep: ส่ง chart ล่วงหน้าล้มเหลว (ไม่กระทบ AI)', [
                        'reading_id' => $readingId,
                        'error' => $chartErr->getMessage(),
                        'error_class' => get_class($chartErr),
                        'gd_loaded' => extension_loaded('gd'),
                    ]);
                }

                // 🔄 batch mode: ไม่ส่ง channelManager (ปิด streaming คำทำนาย)
                // เก็บผลทำนายใน DB → ส่งคำทำนายหลัง AI เสร็จ
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
            } else {
                Log::info('fortune:process-deep: ข้าม AI generation (deep_response มีอยู่แล้ว) → ส่งคำทำนายเลย', [
                    'reading_id' => $readingId,
                ]);
            }

            // ✅ ส่งคำทำนายให้ลูกค้าทันที (ไม่ว่าจะเพิ่งสร้างหรือมีอยู่แล้ว)
            $reading->refresh();

            if (! empty($reading->deep_response)) {
                try {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $extra = ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'];
                    $chartSentEarly = $reading->getConversationState('chart_sent_early', false);

                    // 1. ถ้ายังไม่ได้ส่ง chart ก่อนหน้า → ส่ง chart พร้อมคำทำนาย
                    if (! $chartSentEarly && ! empty($reading->reading_image_url)) {
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'send_chart',
                            'message' => "🔮✨ คำทำนายของคุณ{$name}พร้อมแล้วค่ะ!",
                            'chart_image_url' => $reading->reading_image_url,
                        ], $extra);
                        sleep(2); // ⚡ 2s — ให้ LINE API พร้อมก่อนส่งข้อความถัดไป
                    } else {
                        // chart ส่งไปแล้ว → ส่งแค่ข้อความแจ้ง
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'reading_ready',
                            'message' => "🔮✨ คำทำนายของคุณ{$name}พร้อมแล้วค่ะ!",
                        ], $extra);
                        sleep(2); // ⚡ 2s — เว้นระยะก่อนส่งคำทำนาย
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

                    // ✅ เซ็ต flag เฉพาะเมื่อส่งสำเร็จจริงเท่านั้น!
                    if ($deepSent) {
                        sleep(2);

                        // 3. ข้อความปิดท้าย (ไม่สำคัญเท่าคำทำนาย)
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'reading_complete',
                            'message' => "💫 หวังว่าคำทำนายจะเป็นประโยชน์นะคะ\n\n"
                                . "💡 พิมพ์ 'ดูคำทำนาย' เพื่อดูอีกครั้งได้ทุกเมื่อค่ะ 🔮",
                            'reading' => $reading,
                        ], $extra);

                        // ✅ ส่งสำเร็จจริง → บันทึกสถานะ
                        $reading->setConversationState('reading_ready_sent', true);
                        $reading->setConversationState('reading_sent_directly', true);
                        $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                        Log::info('fortune:process-deep: ✅ ส่งคำทำนายให้ลูกค้าสำเร็จ', [
                            'reading_id' => $readingId,
                            'chart_sent_early' => $chartSentEarly,
                        ]);
                    } else {
                        // ❌ ส่งไม่สำเร็จทุกครั้ง → ไม่เซ็ต flag → check-pending/retry ครั้งหน้าจะลองใหม่
                        Log::error('fortune:process-deep: ❌ ส่งคำทำนายไม่สำเร็จ 3 ครั้ง — รอ retry ครั้งหน้า', [
                            'reading_id' => $readingId,
                            'platform' => $platform,
                            'user_id' => $userId,
                        ]);
                    }
                } catch (\Exception $readyErr) {
                    // ❌ Exception → ไม่เซ็ต flag → ครั้งหน้าจะลองส่งใหม่
                    Log::error('fortune:process-deep: ส่งคำทำนายให้ลูกค้าล้มเหลว (exception)', [
                        'reading_id' => $readingId,
                        'error' => $readyErr->getMessage(),
                    ]);
                }
            } else {
                // ❌ คำทำนายไม่ได้ถูกบันทึก → รอ check-pending retry
                Log::warning('fortune:process-deep: deep_response ว่างหลัง processPaymentConfirmed — รอ check-pending retry', [
                    'reading_id' => $readingId,
                    'result_action' => $result['action'] ?? 'unknown',
                ]);
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
