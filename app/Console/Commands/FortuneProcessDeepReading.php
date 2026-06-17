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
                // 🔒 (2026-06-10) Generation lock — กัน fortune:process-deep รันซ้อนหลาย process
                //   เคสจริง (R5604): webhook sync + check-pending Phase 1 re-dispatch (proc_open)
                //   → AI generate ขนานกัน 3 รอบ → คำทำนายเขียนทับ + ส่งซ้ำ 3 ชุด + AI cost ×3
                //   Cache::add = atomic (redis) → process เดียวเท่านั้นที่ได้ generate
                $genLockKey = "fortune:deep_gen:{$readingId}";
                // (2026-06-17) TTL 300→600s — completeness-gate retry อาจทำให้ gen นานขึ้น;
                //   กัน lock หมดอายุระหว่าง generate แล้ว check-pending (ทุกนาที) dispatch ซ้ำ → gen ซ้อน
                if (! \Illuminate\Support\Facades\Cache::add($genLockKey, 1, 600)) {
                    $this->info("FortuneReading #{$readingId} มี process อื่นกำลัง generate อยู่ — ข้าม (กัน gen ซ้อน)");
                    Log::info('fortune:process-deep: generation lock ถูกถือโดย process อื่น — skip duplicate generation', [
                        'reading_id' => $readingId,
                    ]);

                    return self::SUCCESS;
                }

                try {
                    // ✅ สร้างคำทำนายด้วย AI (ไม่ push ให้ลูกค้าผ่าน pushMessage)
                    // แนวทาง V3: ไม่ใช้ pushMessage สำหรับ fortune delivery (โควต้าจำกัด!)
                    // → บันทึกลง DB เท่านั้น → เมื่อลูกค้าส่งข้อความมา จะส่งผ่าน replyMessage (ฟรี!)
                    // ✅ ส่ง platform + userId เพื่อให้ affiliate auto-register ทำงาน
                    // channelManager = null → ไม่ push เนื้อหาคำทำนาย (streaming = false)

                    // 🔔 (2026-05-14) AI Ping — Loading update 10s/30s/60s + admin alert > 1 min
                    //   เปิด AI session ก่อน call AI — pings วิ่ง async ใน queue worker
                    //   ถ้า AI เสร็จก่อน 10s → ping ทั้งหมด skip (cache cleared)
                    \App\Services\Fortune\FortuneAiPingDispatcher::start(
                        $readingId,
                        $platform,
                        $userId,
                        'deep'
                    );

                    try {
                        $result = $conversationService->processPaymentConfirmed(
                            $reading, $notification, null, $platform, $userId
                        );
                    } finally {
                        // ปิด AI session — pings ที่ยังไม่ run จะ skip
                        \App\Services\Fortune\FortuneAiPingDispatcher::complete($readingId);
                    }

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
                } finally {
                    // 🔓 ปล่อย gen lock เสมอ — gen เสร็จแล้ว deep_response มี (Phase 1 ไม่จับซ้ำ)
                    //    ถ้า gen ล้ม → check-pending retry รอบหน้าได้
                    \Illuminate\Support\Facades\Cache::forget($genLockKey);
                }
            } else {
                Log::info('fortune:process-deep: ข้าม AI generation (deep_response มีอยู่แล้ว)', [
                    'reading_id' => $readingId,
                ]);

                // deep_response มีแล้ว → ตั้ง flag พร้อมส่ง
                $reading->setConversationState('reading_ready_for_reply', true);
            }

            // ✅ Push แจ้งเตือนสั้นๆ "คำทำนายพร้อมแล้ว" (1 push — ไม่ push เนื้อหาคำทำนาย)
            // เนื้อหาจริงจะส่งผ่าน replyMessage เมื่อ user ตอบกลับ (ฟรี!)
            // ถ้า push ล้มเหลว (หมดโควต้า) → user ส่งข้อความมาก็จะได้รับแจ้งผ่าน replyMessage
            $reading->refresh();
            if (! empty($reading->deep_response) && ! empty($userId)) {
                $alreadyNotified = $reading->getConversationState('reading_notification_sent', false);
                $retryCount = (int) $reading->getConversationState('reading_notification_retry_count', 0);

                // 🔒 (2026-06-10) Atomic delivery lock — กันส่งคำทำนายซ้ำจากหลาย process
                //   เคสจริง (R5644): command นี้กำลัง push อยู่ (~18s ภาพ+ไพ่+ข้อความ) →
                //   check-pending Phase 2 เห็น flag ยังไม่ตั้ง → push ซ้ำ → ลูกค้าได้ 2 ชุด
                //   key เดียวกับ ProcessDeepFortuneReadingJob + check-pending Phase 2
                //   (fix เดิม 2026-06-09 อยู่ใน Job::handle() แต่ prod ใช้ Artisan::call → ไม่ผ่าน Job)
                $deliverLockKey = "fortune:deep_deliver:{$readingId}";
                $passFlagGuard = ! $alreadyNotified && $retryCount < 3;
                $deliverLockAcquired = $passFlagGuard
                    && \Illuminate\Support\Facades\Cache::add($deliverLockKey, 1, 600);

                if ($passFlagGuard && ! $deliverLockAcquired) {
                    Log::info('fortune:process-deep: delivery lock ถูกถือโดย process อื่น — skip duplicate push', [
                        'reading_id' => $readingId,
                    ]);
                }

                if ($deliverLockAcquired) {
                    try {
                        $name = $reading->facebook_user_name ?? 'คุณ';
                        $readyMessage = "✨ คุณ{$name}คะ คำทำนายเชิงลึกของคุณพร้อมแล้วค่ะ!\n\n"
                            .'📋 เลขที่บิล: '.($reading->bill_reference ?? '-')."\n\n"
                            ."🔮 พร้อมอ่านเลยไหมคะ?\n"
                            ."💡 พิมพ์อะไรก็ได้ หรือกด 'อ่านคำทำนาย' ด้านล่างค่ะ ✨";

                        // ✅ เช็คโควต้า LINE ก่อนส่ง push (วินิจฉัยสาเหตุ push ล้มเหลว)
                        $quotaInfo = null;
                        if ($platform === 'line') {
                            try {
                                $lineService = new \App\Services\LineFortuneService($settings);
                                $quotaInfo = $lineService->getMessageQuota();

                                $this->info("LINE Quota: {$quotaInfo['used']}/{$quotaInfo['quota']} (เหลือ {$quotaInfo['remaining']})");
                                Log::info('fortune:process-deep: LINE quota check', [
                                    'reading_id' => $readingId,
                                    'quota' => $quotaInfo['quota'] ?? 0,
                                    'used' => $quotaInfo['used'] ?? 0,
                                    'remaining' => $quotaInfo['remaining'] ?? 0,
                                    'percentage' => $quotaInfo['percentage'] ?? 0,
                                    'error' => $quotaInfo['error'] ?? null,
                                ]);

                                if (($quotaInfo['remaining'] ?? 1) <= 0 && empty($quotaInfo['error'])) {
                                    Log::warning('fortune:process-deep: ⚠️ LINE push quota หมดแล้ว!', [
                                        'reading_id' => $readingId,
                                        'quota' => $quotaInfo['quota'] ?? 0,
                                        'used' => $quotaInfo['used'] ?? 0,
                                    ]);
                                    $this->warn('⚠️ LINE push quota หมดแล้ว! ลูกค้าจะได้รับคำทำนายเมื่อพิมพ์ข้อความมา');
                                }
                            } catch (\Exception $quotaErr) {
                                // ไม่ critical — ยังพยายาม push ต่อ
                            }
                        }

                        Log::info('fortune:process-deep: กำลัง push แจ้ง "คำทำนายพร้อมแล้ว"', [
                            'reading_id' => $readingId,
                            'platform' => $platform,
                            'user_id' => $userId,
                            'retry_count' => $retryCount,
                        ]);

                        // ✅ ตั้ง flag "attempted" ก่อนส่ง
                        $reading->setConversationState('reading_notification_attempted', true);
                        $reading->setConversationState('reading_notification_retry_count', $retryCount + 1);

                        // 📱 (2026-05-22) แยก branch FB / LINE
                        //    FB: ส่งคำทำนายเต็มทันที (view_reading_deep) — ตาม user spec "ไม่ต้องมีกล่องอ่านพร้อมแล้ว"
                        //    LINE: คงเดิม — push Flex notification (เพราะ quota จำกัด ต้องรอ user ทักกลับ)
                        if ($platform === 'facebook') {
                            $fullMessage = "🌟 *คำทำนายเชิงลึกของคุณ{$name}*\n";
                            $fullMessage .= '📋 เลขที่บิล: '.($reading->bill_reference ?? '-')."\n";
                            $fullMessage .= '📅 วันที่: '.$reading->created_at->format('d/m/Y H:i')."\n";
                            $fullMessage .= "═══════════════════════\n\n";
                            $fullMessage .= $reading->deep_response;

                            $notifySent = $channelManager->sendResponse($platform, $userId, [
                                'action' => 'view_reading_deep',
                                'message' => $fullMessage,
                                'reading' => $reading,
                                'chart_image_url' => $reading->reading_image_url,
                                'tarot_image_urls' => collect($reading->getCollectedTarotCards())
                                    ->pluck('image_url')->filter()->values()->all(),
                                // 🌙 (2026-05-22) ส่งกล่อง follow-up "หมออยู่ตอบเพิ่ม 10 นาที" หลังคำทำนาย
                                'send_pro_session_followup' => true,
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                            // FB ส่งคำทำนายเต็มแล้ว → ตั้ง flag delivered
                            if ($notifySent) {
                                $reading->setConversationState('reading_sent_directly', true);
                                $reading->setConversationState('reading_ready_sent', true);
                                $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                                $reading->setConversationState('delivered_by_push', true);
                            }
                        } else {
                            $notifySent = $channelManager->sendResponse($platform, $userId, [
                                'action' => 'fortune_ready_notification',
                                'message' => $readyMessage,
                                'reading' => $reading,
                                'quick_replies' => ['อ่านคำทำนาย', 'ไว้ดูทีหลัง'],
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                        }

                        // ✅ Fallback: ถ้า sendResponse ล้มเหลว → ลอง Flex push ตรงด้วย LineFortuneService
                        if (! $notifySent && $platform === 'line') {
                            Log::warning('fortune:process-deep: sendResponse ล้มเหลว → ลอง LINE Flex push ตรง', [
                                'reading_id' => $readingId,
                            ]);

                            try {
                                $lineService = $lineService ?? new \App\Services\LineFortuneService($settings);

                                // ลอง Flex ก่อน (สวยงาม สะดุดตา)
                                $flex = $lineService->buildFortuneReadyFlexMessage($name, $reading->bill_reference ?? null);
                                $notifySent = $lineService->sendRichMessagePriority($userId, [
                                    'alt_text' => '🔮 คำทำนายเชิงลึกพร้อมแล้ว! กดอ่านได้เลยค่ะ',
                                    'contents' => $flex,
                                ]);

                                // Fallback: text + quick replies
                                if (! $notifySent) {
                                    $notifySent = $lineService->sendMessagePriority($userId, $readyMessage, [
                                        'quick_replies' => [
                                            ['label' => '📖 อ่านคำทำนาย', 'text' => 'อ่านคำทำนาย'],
                                            ['label' => '⏰ ไว้ดูทีหลัง', 'text' => 'ไว้ดูทีหลัง'],
                                        ],
                                    ]);
                                }
                            } catch (\Exception $directErr) {
                                Log::error('fortune:process-deep: LINE direct push ล้มเหลวด้วย', [
                                    'reading_id' => $readingId,
                                    'error' => $directErr->getMessage(),
                                ]);
                            }
                        }

                        if (! $notifySent) {
                            // 🔓 push ล้ม → ปล่อย delivery lock ให้ retry/check-pending/ทักกลับ deliver ได้
                            \Illuminate\Support\Facades\Cache::forget($deliverLockKey);
                        }

                        $reading->setConversationState('reading_notification_sent', $notifySent);
                        $reading->setConversationState('reading_notification_sent_at', now()->toIso8601String());

                        Log::info('fortune:process-deep: push แจ้ง "คำทำนายพร้อมแล้ว" ผลลัพธ์', [
                            'reading_id' => $readingId,
                            'sent' => $notifySent,
                            'platform' => $platform,
                            'line_quota_remaining' => $quotaInfo['remaining'] ?? 'unknown',
                        ]);

                        if ($notifySent) {
                            $this->info('✅ Push แจ้ง "คำทำนายพร้อม" สำเร็จ');
                        } else {
                            $this->warn('⚠️ Push แจ้งเตือนไม่สำเร็จ — ลูกค้าจะได้รับเมื่อพิมพ์ข้อความมา');
                        }
                    } catch (\Exception $notifyErr) {
                        // 🔓 push exception → ปล่อย delivery lock ให้ retry/ทักกลับ deliver ได้
                        \Illuminate\Support\Facades\Cache::forget($deliverLockKey);

                        // push ล้มเหลว → ไม่เป็นไร user ส่งข้อความมาจะได้รับผ่าน replyMessage
                        Log::warning('fortune:process-deep: push แจ้งเตือนล้มเหลว (fallback replyMessage)', [
                            'reading_id' => $readingId,
                            'platform' => $platform,
                            'error' => $notifyErr->getMessage(),
                            'trace' => substr($notifyErr->getTraceAsString(), 0, 300),
                        ]);
                        $reading->setConversationState('reading_notification_attempted', true);
                    }
                }
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            // 🩹 (2026-05-09) catch \Throwable แทน \Exception — กัน TypeError/Error leak ผ่าน
            //                 status update + recovery (PHP 8 type errors extends Throwable ไม่ใช่ Exception)
            // 🔓 (2026-06-10) ปล่อย delivery lock ถ้าเราถืออยู่แต่ยังส่งไม่สำเร็จ —
            //   \Error ระหว่าง push ข้าม catch(\Exception) ด้านใน → lock จะค้างจน TTL หมด
            //   (ถ้าส่งสำเร็จแล้ว $notifySent=true → คง lock ไว้ — flag guard กันซ้ำอยู่แล้ว)
            if (! empty($deliverLockAcquired) && empty($notifySent)) {
                \Illuminate\Support\Facades\Cache::forget("fortune:deep_deliver:{$readingId}");
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->error("❌ ล้มเหลว: {$e->getMessage()} ({$duration}ms)");
            Log::error('❌ fortune:process-deep ล้มเหลว', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // 🚨 (2026-05-09) Recovery: alert admin + push failure notification + clear gen_processing
            //   ก่อนหน้า: catch นี้แค่ update STATUS_COMPLETED เท่านั้น (ลูกค้าเงียบ 5 นาที, ไม่มี admin alert)
            //   ปัญหา: dispatchSmart() ใช้ fastcgi_finish_request + Artisan::call → Job::failed() ไม่ run →
            //          C5 fix (2026-05-03 #2) ทั้งชุดอยู่ใน Job::failed ที่ไม่ถูกเรียกบน production
            //   Fix: เรียก FortuneAiFailureRecovery service (idempotent ผ่าน ai_failed_alert flag)
            try {
                app(\App\Services\Fortune\FortuneAiFailureRecovery::class)
                    ->handle($readingId, $platform, $userId, $e, 1);

                Log::info('fortune:process-deep: recovery ทำงานเรียบร้อย', [
                    'reading_id' => $readingId,
                ]);
            } catch (\Throwable $recoveryErr) {
                // ถ้า recovery service ล้ม → ยังต้อง mark COMPLETED กันบิลค้าง (fallback)
                Log::error('fortune:process-deep: recovery service ล้ม — fallback mark completed', [
                    'reading_id' => $readingId,
                    'recovery_error' => $recoveryErr->getMessage(),
                ]);

                try {
                    $reading = FortuneReading::find($readingId);
                    if ($reading && $reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
                    }
                } catch (\Throwable $statusErr) {
                    Log::error('fortune:process-deep: fallback status update ล้ม', [
                        'reading_id' => $readingId,
                        'error' => $statusErr->getMessage(),
                    ]);
                }
            }

            return self::FAILURE;
        }
    }

    // ✅ V3: ไม่ push คำทำนายอีกต่อไป — ส่งผ่าน replyMessage เมื่อ user ส่งข้อความมา (ฟรี!)
}
