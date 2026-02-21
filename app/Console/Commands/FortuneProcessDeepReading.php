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
                // ✅ แจ้งลูกค้าว่า "ชำระเงินสำเร็จ กำลังเตรียมคำทำนาย" ก่อนเริ่ม AI
                // เช็ค wait_message_sent flag เพื่อไม่ส่งซ้ำ (SMS flow ส่งไปแล้วใน handleFortuneReadingPayment)
                $alreadySentWait = $reading->getConversationState('wait_message_sent', false);
                if (! $alreadySentWait && ! empty($userId)) {
                    try {
                        $name = $reading->facebook_user_name ?? 'คุณ';
                        $channelManager->sendResponse($platform, $userId, [
                            'action' => 'payment_confirmed_wait',
                            'message' => "✨ ขอบคุณค่ะ {$name}! ได้รับการชำระเงินเรียบร้อยแล้ว\n\n"
                                . "🔮 จันทราจะตรวจดวงชะตาให้นะคะ รอสักครู่ประมาณ 5 นาทีค่ะ ✨",
                            'reading' => $reading,
                            'facebook_user_id' => $userId,
                        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                        $reading->setConversationState('wait_message_sent', true);
                        $reading->setConversationState('wait_message_sent_at', now()->toIso8601String());

                        Log::info('fortune:process-deep: ส่งข้อความ "ชำระเงินสำเร็จ กำลังเตรียมคำทำนาย"', [
                            'reading_id' => $readingId,
                            'platform' => $platform,
                        ]);
                    } catch (\Throwable $waitErr) {
                        Log::warning('fortune:process-deep: ส่งข้อความ "รอสักครู่" ล้มเหลว (ไม่กระทบ AI)', [
                            'reading_id' => $readingId,
                            'error' => $waitErr->getMessage(),
                        ]);
                    }
                }

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

                $streamingSentCount = $result['streaming_sent_count'] ?? 0;
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                $this->info("✅ สร้างคำทำนาย สำเร็จ ({$duration}ms) — streaming ส่งได้ {$streamingSentCount} ข้อ");
                Log::info('✅ fortune:process-deep สำเร็จ', [
                    'reading_id' => $readingId,
                    'action' => $result['action'] ?? 'unknown',
                    'streaming' => ! empty($result['streaming']),
                    'streaming_sent_count' => $streamingSentCount,
                    'duration_ms' => $duration,
                ]);

                // ✅ บันทึกว่าส่งให้ลูกค้าแล้ว — เฉพาะเมื่อส่งได้จริง!
                // ⚠️ ถ้า streaming ส่งไม่ได้เลย (0 ข้อ) → ไม่ set flag → safety net ด้านล่างจะส่งให้
                $reading->refresh();
                if (! empty($reading->deep_response) && $streamingSentCount > 0) {
                    $reading->setConversationState('reading_ready_sent', true);
                    $reading->setConversationState('reading_sent_directly', true);
                    $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());
                } elseif (! empty($result['streaming']) && $streamingSentCount === 0) {
                    Log::warning('fortune:process-deep: streaming ส่งไม่ได้เลย → safety net จะส่งให้', [
                        'reading_id' => $readingId,
                    ]);
                }
            } else {
                Log::info('fortune:process-deep: ข้าม AI generation (deep_response มีอยู่แล้ว) → ส่งคำทำนายเลย', [
                    'reading_id' => $readingId,
                ]);
            }

            // ✅ Safety net: ส่งคำทำนายที่มีอยู่แล้วแต่ยังไม่เคยส่ง (กรณี retry / streaming ล้มเหลว / deep_response มีอยู่ก่อน)
            $reading->refresh();
            $alreadySentDirectly = $reading->getConversationState('reading_sent_directly', false);

            if (! empty($reading->deep_response) && ! $alreadySentDirectly) {
                try {
                    $name = $reading->facebook_user_name ?? 'คุณ';
                    $deepSent = false;

                    Log::info('fortune:process-deep: safety net → ส่งคำทำนายให้ลูกค้า', [
                        'reading_id' => $readingId,
                        'platform' => $platform,
                    ]);

                    // 1. ส่ง chart ถ้ายังไม่ได้ส่ง
                    if (! empty($reading->reading_image_url)) {
                        $lineService = ($platform === 'line') ? $channelManager->getPlatform('line') : null;
                        if ($lineService) {
                            $lineService->sendImage($userId, $reading->reading_image_url);
                        } else {
                            $channelManager->sendResponse($platform, $userId, [
                                'action' => 'send_chart',
                                'message' => "🔮✨ คำทำนายของคุณ{$name}พร้อมแล้วค่ะ!",
                                'chart_image_url' => $reading->reading_image_url,
                            ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                        }
                        sleep(2);
                    }

                    // 2. ส่งคำทำนายเชิงลึก — ⚡ ส่งทีละข้อ (ไม่ใช้ carousel ที่อาจเกิน LINE size limit)
                    if ($platform === 'line') {
                        $deepSent = $this->sendIndividualBubblesForLine(
                            $channelManager, $userId, $reading, $name
                        );
                    } else {
                        // Facebook / platform อื่น → ส่ง text เดียว
                        $billRef = $reading->bill_reference ?? '-';
                        $readingText = "🌟 *คำทำนายเชิงลึก*\n"
                            . "📋 เลขที่บิล: {$billRef}\n"
                            . "═══════════════════════\n\n"
                            . $reading->deep_response;

                        $deepSent = $channelManager->sendResponse($platform, $userId, [
                            'action' => 'deep_reading_result',
                            'message' => $readingText,
                            'reading' => $reading,
                        ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);
                    }

                    if ($deepSent) {
                        $reading->setConversationState('reading_ready_sent', true);
                        $reading->setConversationState('reading_sent_directly', true);
                        $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                        Log::info('fortune:process-deep: ✅ ส่งคำทำนาย (safety net) ให้ลูกค้าสำเร็จ', [
                            'reading_id' => $readingId,
                        ]);
                    } else {
                        Log::error('fortune:process-deep: ❌ ส่งคำทำนายไม่สำเร็จทุกวิธี — รอ retry ครั้งหน้า', [
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

    /**
     * ส่งคำทำนายเชิงลึกทีละข้อสำหรับ LINE (ไม่ใช้ carousel → ป้องกัน size limit)
     *
     * ลำดับ: ลอง Flex bubble ทีละข้อ → ถ้า Flex ไม่ได้ → fallback text ทั้งก้อน
     */
    protected function sendIndividualBubblesForLine(
        FortuneChannelManager $channelManager,
        string $userId,
        FortuneReading $reading,
        string $userName
    ): bool {
        $lineService = $channelManager->getPlatform('line');
        if (! $lineService instanceof \App\Services\LineFortuneService) {
            Log::error('fortune:process-deep: safety net ไม่พบ LineFortuneService');

            return false;
        }

        // ลอง parse คำทำนายเป็น QA pairs
        $collectedQuestions = $reading->getCollectedQuestions();
        $deepResponse = $reading->deep_response;
        $sentCount = 0;

        if (! empty($collectedQuestions)) {
            // ดึง parseDeepResponseByQuestions จาก FortuneChannelManager (เป็น protected)
            // → ใช้ regex parse เอง (logic เดียวกัน)
            $parsedQA = $this->parseDeepResponseSimple($deepResponse, $collectedQuestions);

            if (! empty($parsedQA)) {
                $totalQuestions = count($parsedQA);
                foreach ($parsedQA as $idx => $qa) {
                    $questionNum = $idx + 1;
                    try {
                        $flex = $lineService->buildDeepReadingFlexMessage(
                            $questionNum, $qa['question'], $qa['answer'], $totalQuestions
                        );
                        $sent = $lineService->sendRichMessage($userId, [
                            'alt_text' => "🔮 คำทำนายข้อ {$questionNum}/{$totalQuestions}: {$qa['question']}",
                            'contents' => $flex,
                        ]);

                        if ($sent) {
                            $sentCount++;
                        } else {
                            Log::warning("fortune:process-deep: safety net Flex ส่งไม่ได้ข้อ {$questionNum} → ลอง text", [
                                'reading_id' => $reading->id,
                            ]);
                            // Fallback: ส่งเป็น text ธรรมดา
                            $textMsg = "🔮 คำทำนายข้อที่ {$questionNum}/{$totalQuestions}\n"
                                . "❓ {$qa['question']}\n\n"
                                . $qa['answer'];
                            if ($lineService->sendMessage($userId, mb_substr($textMsg, 0, 5000))) {
                                $sentCount++;
                            }
                        }
                        usleep(1500000); // 1.5s — ป้องกัน rate limit
                    } catch (\Exception $e) {
                        Log::warning("fortune:process-deep: safety net ส่งข้อ {$questionNum} ล้มเหลว", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($sentCount > 0) {
                    return true;
                }
            }
        }

        // Fallback สุดท้าย: ส่ง deep_response เป็น text ธรรมดาทั้งก้อน (ดีกว่าไม่ส่งอะไรเลย!)
        Log::info('fortune:process-deep: safety net fallback → ส่ง text ทั้งก้อน', [
            'reading_id' => $reading->id,
            'response_length' => mb_strlen($deepResponse),
        ]);

        $billRef = $reading->bill_reference ?? '-';
        $fullText = "🌟 คำทำนายเชิงลึก\n"
            . "📋 เลขที่บิล: {$billRef}\n"
            . "═══════════════════════\n\n"
            . $deepResponse;

        // ถ้ายาวเกิน LINE limit (5000 ตัวอักษร) → แบ่งส่ง
        $chunks = mb_str_split($fullText, 4500);
        foreach ($chunks as $chunk) {
            $sent = $lineService->sendMessage($userId, $chunk);
            if ($sent) {
                $sentCount++;
            }
            usleep(1500000);
        }

        return $sentCount > 0;
    }

    /**
     * Parse deep_response ตามคำถาม (simplified version)
     *
     * @return array [['question' => '...', 'answer' => '...'], ...]
     */
    protected function parseDeepResponseSimple(string $deepResponse, array $collectedQuestions): array
    {
        $totalQuestions = count($collectedQuestions);
        if ($totalQuestions === 0) {
            return [];
        }

        // ลอง split ด้วยรูปแบบที่ AI ใช้
        $patterns = [
            '/(?:คำถาม(?:ที่)?\s*(\d+)\s*[:：])/u',
            '/(?:ข้อ\s*(\d+)\s*[:：])/u',
            '/(?:^|\n)\s*(\d+)\s*[.)\]]\s*/u',
            '/(?:\*{1,2}คำถาม(?:ที่)?\s*(\d+)\*{1,2}\s*[:：]?)/u',
            '/(?:═{3,})/u',
        ];

        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $deepResponse, -1, PREG_SPLIT_NO_EMPTY);
            $cleanParts = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (mb_strlen($trimmed) < 20) {
                    continue;
                }
                if (preg_match('/^[🌟📋═*\s]*(?:คำทำนายเชิงลึก|เลขที่บิล)/u', $trimmed)) {
                    continue;
                }
                $cleanParts[] = $trimmed;
            }

            if (count($cleanParts) === $totalQuestions) {
                $result = [];
                foreach ($collectedQuestions as $idx => $question) {
                    $result[] = [
                        'question' => $question,
                        'answer' => trim($cleanParts[$idx]),
                    ];
                }

                return $result;
            }
        }

        return [];
    }
}
