<?php

namespace App\Http\Controllers;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\LineFortuneService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * LINE Fortune Webhook Controller
 *
 * รับ webhook events จาก LINE Official Account สำหรับระบบดูดวง
 *
 * Webhook URL: /webhook/line/fortune
 */
class LineFortuneWebhookController extends Controller
{
    protected FortuneTellingSetting $settings;

    protected LineFortuneService $lineService;

    protected FortuneChannelManager $channelManager;

    public function __construct()
    {
        $this->settings = FortuneTellingSetting::getSettings();
        $this->lineService = new LineFortuneService($this->settings);
        $this->channelManager = new FortuneChannelManager($this->settings);
    }

    /**
     * Handle LINE Webhook
     */
    public function handle(Request $request): Response
    {
        // ตรวจสอบว่าเปิดใช้งาน LINE หรือไม่
        if (! $this->settings->line_enabled) {
            Log::warning('LINE Webhook: LINE is not enabled');

            return response('LINE is not enabled', 200);
        }

        // ตรวจสอบ signature
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if (! $this->lineService->verifySignature($body, $signature ?? '')) {
            Log::warning('LINE Webhook: Invalid signature');

            return response('Invalid signature', 400);
        }

        // Parse events
        $data = json_decode($body, true);
        $events = $data['events'] ?? [];

        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return response('OK', 200);
    }

    /**
     * Handle single event
     */
    protected function handleEvent(array $event): void
    {
        $eventType = $event['type'] ?? null;

        Log::info('LINE Webhook: Event received', [
            'type' => $eventType,
            'source' => $event['source'] ?? null,
        ]);

        match ($eventType) {
            'message' => $this->handleMessageEvent($event),
            'follow' => $this->handleFollowEvent($event),
            'unfollow' => $this->handleUnfollowEvent($event),
            'postback' => $this->handlePostbackEvent($event),
            default => Log::debug('LINE Webhook: Unhandled event type', ['type' => $eventType]),
        };
    }

    /**
     * Handle message event
     */
    protected function handleMessageEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $messageType = $event['message']['type'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            Log::warning('LINE Webhook: No userId in message event');

            return;
        }

        // รองรับเฉพาะ text message
        if ($messageType !== 'text') {
            $this->lineService->replyMessage($replyToken, [
                [
                    'type' => 'text',
                    'text' => "🙏 ขอบคุณที่ทักมานะคะ\n\nทางเพจรับเฉพาะข้อความเท่านั้นค่ะ\n\nพิมพ์คำถามที่อยากให้ดูดวงมาได้เลยนะคะ 🔮✨",
                ],
            ]);

            return;
        }

        $messageText = $event['message']['text'] ?? '';

        try {
            // ⚡ ดึง profile ครั้งเดียวแล้วส่งต่อ (ลด API call ซ้ำ)
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) {
                Log::debug('LINE Webhook: ดึง profile ไม่ได้ ใช้ default', [
                    'user_id' => $userId,
                ]);
            }
            // ส่ง empty array ถ้า null → กัน FortuneChannelManager เรียก getUserProfile ซ้ำ
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            // ประมวลผลข้อความผ่าน Channel Manager
            $result = $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $messageText,
                $userProfile,
                ['reply_token' => $replyToken]
            );

            Log::info('LINE Webhook: Message processed', [
                'user_id' => $userId,
                'action' => $result['action'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Error processing message', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile().':'.$e->getLine(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            // ส่งข้อความ error (ลอง reply ก่อน ถ้าไม่ได้ใช้ push)
            $errorMessage = 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏';
            $this->lineService->sendMessageWithReplyFallback($userId, $errorMessage, $replyToken);
        }
    }

    /**
     * Handle follow event (user add friend)
     *
     * ดึงชื่อจาก LINE Profile แล้วส่ง Welcome Flex Message พร้อมชื่อ
     */
    protected function handleFollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        // ดึงชื่อจาก LINE Profile
        $userName = '';
        try {
            $profile = $this->lineService->getUserProfile($userId);
            $userName = $profile['name'] ?? '';

            Log::info('LINE Webhook: New follower', [
                'user_id' => $userId,
                'display_name' => $userName,
            ]);
        } catch (\Exception $e) {
            Log::warning('LINE Webhook: ดึงโปรไฟล์ไม่ได้ ส่ง Welcome โดยไม่มีชื่อ', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        // ========================================
        // จับคู่ FortuneReferral (ถ้ามีคนเชิญ)
        // ========================================
        try {
            $referral = \App\Models\FortuneReferral::where('status', \App\Models\FortuneReferral::STATUS_PENDING)
                ->whereNull('referred_line_user_id')
                ->where('expires_at', '>', now())
                ->where('created_at', '>=', now()->subMinutes(30))
                ->latest()
                ->first();

            if ($referral) {
                $referral->markAsFollowed($userId);
                Log::info('LINE Webhook: จับคู่ referral สำเร็จ', [
                    'referral_id' => $referral->id,
                    'referrer_user_id' => $referral->referrer_user_id,
                    'new_follower_line_id' => $userId,
                ]);
            }
        } catch (\Exception $refErr) {
            Log::debug('LINE Webhook: ตรวจ referral ไม่สำเร็จ (ไม่กระทบ welcome)', [
                'error' => $refErr->getMessage(),
            ]);
        }

        // ส่ง Welcome Message พร้อมชื่อ
        $welcomeFlex = $this->lineService->buildWelcomeFlexMessage($userName);

        $this->lineService->replyMessage($replyToken, [
            [
                'type' => 'flex',
                'altText' => $userName
                    ? "ยินดีต้อนรับค่ะ คุณ{$userName} 🔮"
                    : 'ทางเพจยินดีต้อนรับค่ะ 🔮',
                'contents' => $welcomeFlex,
            ],
        ]);
    }

    /**
     * Handle unfollow event (user block/remove friend)
     */
    protected function handleUnfollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;

        if ($userId) {
            Log::info('LINE Webhook: User unfollowed', ['user_id' => $userId]);
        }
    }

    /**
     * Handle postback event (button clicks)
     */
    protected function handlePostbackEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $data = $event['postback']['data'] ?? '';
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        Log::info('LINE Webhook: Postback received', [
            'user_id' => $userId,
            'data' => $data,
        ]);

        // Parse postback data
        parse_str($data, $params);
        $action = $params['action'] ?? '';

        match ($action) {
            'deep_reading' => $this->handleDeepReadingPostback($userId, $replyToken),
            'cancel' => $this->handleCancelPostback($userId, $replyToken),
            'view_last_reading' => $this->handleSimulateTextPostback($userId, $replyToken, 'ดูคำทำนาย'),
            'check_remaining' => $this->handleSimulateTextPostback($userId, $replyToken, 'เช็คสิทธิ์'),
            'check_status' => $this->handleCheckStatusPostback($userId, $replyToken),
            'report_payment' => $this->handleReportPaymentPostback($userId, $replyToken),
            'help' => $this->handleHelpPostback($userId, $replyToken),
            'confirm_transfer' => $this->handleConfirmTransferPostback($userId, $replyToken, $params),
            default => null,
        };
    }

    /**
     * Handle deep reading postback
     */
    protected function handleDeepReadingPostback(string $userId, ?string $replyToken): void
    {
        try {
            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                'ต้องการดูดวงละเอียด',
                null,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback deep_reading ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * Handle cancel postback
     */
    protected function handleCancelPostback(string $userId, ?string $replyToken): void
    {
        try {
            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                'ยกเลิก',
                null,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback cancel ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * จำลองส่งข้อความ text ผ่าน channelManager (ใช้กับ postback ที่ trigger เหมือนพิมพ์ข้อความ)
     */
    protected function handleSimulateTextPostback(string $userId, ?string $replyToken, string $text): void
    {
        try {
            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $text,
                null,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback simulate text ล้มเหลว', [
                'user_id' => $userId,
                'text' => $text,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แสดงสถานะ/สิทธิ์ (จากปุ่ม Rich Menu)
     *
     * ดึงข้อมูลสิทธิ์ฟรี, เครดิตพิเศษ, สถานะสมาชิก แล้วส่ง Flex
     */
    protected function handleCheckStatusPostback(string $userId, ?string $replyToken): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $maxFreeReadings = $settings->max_free_readings ?? 3;
            $usedToday = FortuneReading::countTodayReadings($userId);

            // ดึงข้อมูลเครดิตพิเศษ
            $userCredit = \App\Models\FortuneUserCredit::findByUser($userId);
            $specialCredits = 0;
            $isUnlimited = false;

            $normalRemaining = max(0, $maxFreeReadings - $usedToday);
            if ($userCredit) {
                if ($userCredit->isCurrentlyUnlimited()) {
                    $isUnlimited = true;
                    $normalRemaining = 99;
                } elseif ($userCredit->isDailyResetActive()) {
                    $normalRemaining = max($normalRemaining, $maxFreeReadings);
                } else {
                    $extraCredits = $userCredit->getRemainingCredits();
                    $specialCredits = $extraCredits;
                    $normalRemaining += $extraCredits;
                }
            }

            // ดึงชื่อผู้ใช้จาก LINE Profile
            $userName = 'คุณ';
            $profile = $this->lineService->getUserProfile($userId);
            if ($profile && ! empty($profile['displayName'])) {
                $userName = $profile['displayName'];
            }

            // สร้าง result ในรูปแบบที่ FortuneChannelManager ต้องการ
            $result = [
                'action' => 'check_status',
                'message' => "✅ สถานะ: สิทธิ์ฟรี {$normalRemaining} ครั้ง",
                'reading' => null,
                'user_name' => $userName,
                'remaining' => $normalRemaining,
                'used' => $usedToday,
                'total' => $maxFreeReadings,
                'special_credits' => $specialCredits,
                'is_unlimited' => $isUnlimited,
                'member_status' => null, // TODO: ตรวจสอบสมาชิก Thaiprompt ในอนาคต
            ];

            // ส่งผ่าน FortuneChannelManager เพื่อใช้ Flex Message
            $this->channelManager->sendResponse(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $result,
                ['reply_token' => $replyToken]
            );
        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback check_status ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ ไม่สามารถดึงข้อมูลสถานะได้ กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แจ้งปัญหาการโอนเงิน — แสดงคำเตือน + ข้อมูลบิลที่รอชำระ
     */
    protected function handleReportPaymentPostback(string $userId, ?string $replyToken): void
    {
        try {
            // หาบิลที่รอชำระ
            $pendingReading = FortuneReading::where('facebook_user_id', $userId)
                ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->with('uniquePaymentAmount')
                ->latest()
                ->first();

            $uniqueAmount = null;
            $expiresAt = null;

            if ($pendingReading && $pendingReading->uniquePaymentAmount) {
                $uniqueAmount = (float) $pendingReading->uniquePaymentAmount->amount;
                $expiresAtCarbon = $pendingReading->uniquePaymentAmount->expires_at ?? null;
                $expiresAt = $expiresAtCarbon ? $expiresAtCarbon->format('H:i') : null;
            }

            $flex = $this->lineService->buildPaymentProblemFlexMessage(
                $pendingReading,
                $uniqueAmount,
                $expiresAt
            );

            $this->lineService->sendFlexWithReplyFallback(
                $userId, $flex, '⚠️ แจ้งปัญหาการโอนเงิน', $replyToken
            );

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback report_payment ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * แสดงวิธีใช้งาน (help)
     */
    protected function handleHelpPostback(string $userId, ?string $replyToken): void
    {
        try {
            $flex = $this->lineService->buildHelpFlexMessage();

            $this->lineService->sendFlexWithReplyFallback(
                $userId, $flex, 'ℹ️ วิธีใช้งานแม่หมอจันทรา', $replyToken
            );

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback help ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }

    /**
     * ยืนยันว่าโอนเงินแล้ว — บันทึก transfer_reported + แจ้ง admin
     */
    protected function handleConfirmTransferPostback(string $userId, ?string $replyToken, array $params): void
    {
        try {
            $readingId = $params['reading_id'] ?? null;

            // หา reading ที่ตรงกับ ID + user
            $reading = null;
            if ($readingId) {
                $reading = FortuneReading::where('id', $readingId)
                    ->where('facebook_user_id', $userId)
                    ->first();
            }

            // ถ้าไม่มี reading_id → หาจาก pending ล่าสุด
            if (! $reading) {
                $reading = FortuneReading::where('facebook_user_id', $userId)
                    ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                    ->where('is_paid', false)
                    ->latest()
                    ->first();
            }

            if (! $reading) {
                $this->lineService->sendMessageWithReplyFallback(
                    $userId, 'ไม่พบบิลที่รอชำระค่ะ กรุณาเริ่มดูดวงใหม่ 🔮', $replyToken
                );

                return;
            }

            // บันทึกว่าผู้ใช้แจ้งว่าโอนแล้ว
            $reading->update([
                'transfer_reported' => true,
                'transfer_reported_at' => now(),
            ]);

            Log::info('LINE Webhook: ผู้ใช้แจ้งว่าโอนเงินแล้ว', [
                'user_id' => $userId,
                'reading_id' => $reading->id,
                'bill_ref' => $reading->bill_reference,
            ]);

            // ตอบผู้ใช้
            $billRef = $reading->bill_reference ?? $reading->id;
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                "✅ รับแจ้งแล้วค่ะ! (บิล: {$billRef})\n\nทีมงานจะตรวจสอบและส่งคำทำนายให้โดยเร็วที่สุดค่ะ 🙏\n\n⏳ กรุณารอสักครู่นะคะ",
                $replyToken
            );

        } catch (\Exception $e) {
            Log::error('LINE Webhook: Postback confirm_transfer ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->lineService->sendMessageWithReplyFallback(
                $userId, 'ขออภัยค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', $replyToken
            );
        }
    }
}
