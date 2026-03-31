<?php

namespace App\Http\Controllers;

use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmProspect;
use App\Services\FortuneChannelManager;
use App\Services\LineFortuneService;
use App\Services\LineGatekeeperService;
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

        // ✅ FIX: ส่ง 200 OK ให้ LINE ก่อน → ป้องกัน LINE retry จาก timeout
        // ใช้ fastcgi_finish_request() (PHP-FPM) หรือ ob_end_flush() (Apache mod_php)
        // เพื่อปิดการเชื่อมต่อ HTTP แล้วประมวลผลต่อในพื้นหลัง
        $response = response('OK', 200);

        // ส่ง HTTP response ก่อนประมวลผล events
        if (function_exists('fastcgi_finish_request')) {
            // PHP-FPM: ส่ง response ทันที → ประมวลผลต่อใน background
            $response->send();
            fastcgi_finish_request();

            // ✅ ประมวลผล events หลังส่ง response แล้ว (ลูกค้าไม่ต้องรอ)
            foreach ($events as $event) {
                $this->handleEvent($event);
            }

            // ส่ง response ว่างเพื่อให้ Laravel middleware ไม่ error
            return response('', 200);
        }

        // Non-FPM (Apache mod_php, CLI): ประมวลผลก่อน return ปกติ
        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return $response;
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

        // ========================================
        // จับคู่ FortuneReferral จาก ref_{token} (แม่นยำ 100%)
        // ========================================
        if (preg_match('/^ref_([A-Za-z0-9]{32})$/i', trim($messageText), $matches)) {
            $this->handleReferralTokenMessage($userId, $matches[1], $replyToken);

            return;
        }

        try {
            // ✅ Gatekeeper: เช็คทราฟฟิคภาพรวมทั้งระบบก่อน (ทุก user รวมกัน)
            if (LineGatekeeperService::isSystemThrottled()) {
                Log::warning('LINE Webhook: System throttled — ส่งข้อความเตือน', [
                    'user_id' => $userId,
                    'stats' => LineGatekeeperService::getStats(),
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "⏳ ขณะนี้มีผู้ใช้งานจำนวนมากค่ะ\n\nกรุณารอสักครู่แล้วพิมพ์ข้อความมาใหม่นะคะ 🙏✨"],
                    ]);
                }

                return;
            }

            // ✅ Flood Protection: กันเฉพาะ spam bot ที่ส่งถี่มากผิดปกติ
            $floodKey = "line_flood:{$userId}";
            $floodCount = (int) cache()->get($floodKey, 0);
            cache()->put($floodKey, $floodCount + 1, 10); // นับข้อความใน 10 วินาที

            if ($floodCount >= 10) {
                // ⚡ ส่ง replyMessage ตรงๆ (ฟรี ไม่นับ push quota) — ไม่เรียก AI, ไม่เรียก LINE push
                Log::warning('LINE Webhook: Flood detected — ส่งข้อความเตือนซ้ำ', [
                    'user_id' => $userId,
                    'flood_count' => $floodCount,
                    'text' => mb_substr($messageText, 0, 30),
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "🙏 กรุณารอสักครู่ค่ะ ระบบกำลังประมวลผลอยู่\n\nพิมพ์ข้อความทีละข้อความนะคะ 💫"],
                    ]);
                }

                return;
            }

            // ⚡ ดึง profile จาก cache 24hr (ไม่เรียก LINE API ถ้ามี cache แล้ว)
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
        // การจับคู่ referral ย้ายไป handleReferralTokenMessage() แล้ว
        // เพื่อนจะส่ง "ref_{token}" มาทาง message event → จับคู่ 100%
        // ========================================

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
     * จับคู่ referral จาก token ที่ฝังในข้อความ (แม่นยำ 100%)
     *
     * เมื่อเพื่อนกด LINE deep link → ข้อความ "ref_{token}" ถูก pre-fill
     * เพื่อนกดส่ง → method นี้จับคู่ token กับ FortuneReferral ได้ทันที
     */
    protected function handleReferralTokenMessage(string $lineUserId, string $token, ?string $replyToken): void
    {
        try {
            // 1. หา FortuneReferral จาก token
            $referral = FortuneReferral::where('referral_token', $token)
                ->where('status', FortuneReferral::STATUS_PENDING)
                ->where('expires_at', '>', now())
                ->first();

            if (! $referral) {
                // token หมดอายุหรือไม่ถูกต้อง
                Log::info('LINE Webhook: ref_ token ไม่ถูกต้องหรือหมดอายุ', [
                    'token' => $token,
                    'line_user_id' => $lineUserId,
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "⏰ ลิงก์เชิญนี้หมดอายุแล้วค่ะ\n\nกรุณาขอลิงก์ใหม่จากผู้เชิญนะคะ\n\nหรือพิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยค่ะ 🔮"],
                    ]);
                }

                return;
            }

            // 2. ตรวจว่า LINE user นี้ถูกจับคู่กับ referral อื่นอยู่แล้วหรือไม่
            $existingReferral = FortuneReferral::where('referred_line_user_id', $lineUserId)
                ->whereIn('status', [FortuneReferral::STATUS_FOLLOWED, FortuneReferral::STATUS_CONVERTED])
                ->first();

            if ($existingReferral) {
                Log::info('LINE Webhook: LINE user มี referral อยู่แล้ว', [
                    'existing_referral_id' => $existingReferral->id,
                    'line_user_id' => $lineUserId,
                ]);

                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => "🔮 คุณเข้าระบบแล้วค่ะ\n\nพิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยนะคะ ✨"],
                    ]);
                }

                return;
            }

            // 3. ดึง LINE profile
            $userName = '';
            try {
                $profile = $this->lineService->getUserProfile($lineUserId);
                $userName = $profile['name'] ?? '';
            } catch (\Exception $e) {
                // ไม่กระทบ flow หลัก
            }

            // 4. Mark referral as "followed" + บันทึก LINE user ID
            $referral->markAsFollowed($lineUserId);

            // 5. อัพเดท MlmProspect ที่เชื่อมกัน
            if ($referral->mlm_prospect_id) {
                $prospect = MlmProspect::find($referral->mlm_prospect_id);
                if ($prospect) {
                    $prospect->update([
                        'line_user_id' => $lineUserId,
                        'line_display_name' => $userName ?: null,
                        'line_picture_url' => $profile['pictureUrl'] ?? null,
                        'clicked_at' => now(),
                        'status' => 'in_progress',
                        'is_locked' => true,
                        'locked_until' => now()->addHours(24),
                    ]);
                }
            }

            Log::info('LINE Webhook: จับคู่ referral สำเร็จ 100% ผ่าน deep link token', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
                'new_follower_line_id' => $lineUserId,
                'display_name' => $userName,
            ]);

            // 6. ส่ง Welcome + แนะนำให้ดูดวง
            $referrerName = $referral->referrerUser?->name ?? 'เพื่อน';

            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => "🎉 ยินดีต้อนรับค่ะ".($userName ? " คุณ{$userName}" : '')."\n\nคุณ{$referrerName} เชิญคุณมาดูดวงค่ะ\n\n🔮 พิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยนะคะ ✨"],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('LINE Webhook: handleReferralTokenMessage ล้มเหลว', [
                'token' => $token,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);

            if ($replyToken) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => "🔮 พิมพ์ \"ดูดวง\" เพื่อเริ่มดูดวงได้เลยค่ะ ✨"],
                ]);
            }
        }
    }

    /**
     * Handle unfollow event (user block/remove friend)
     */
    protected function handleUnfollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;

        if (! $userId) {
            return;
        }

        Log::info('LINE Webhook: User unfollowed (blocked bot)', ['user_id' => $userId]);

        try {
            // ปิด conversation ที่ค้างอยู่ทั้งหมด (ยกเว้น PAID — อาจกำลัง processing)
            $closedConversations = FortuneReading::where('facebook_user_id', $userId)
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_AWAITING_CONFIRMATION,
                    FortuneReading::STATUS_BASIC_DONE,
                    FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                    FortuneReading::STATUS_COLLECTING_QUESTIONS,
                    FortuneReading::STATUS_COLLECTING_TAROT,
                    FortuneReading::STATUS_NEW,
                ])
                ->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            // ยกเลิก pending payment bills + คืน UniquePaymentAmount
            $pendingReadings = FortuneReading::where('facebook_user_id', $userId)
                ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                ->where('is_paid', false)
                ->whereNotNull('unique_payment_amount_id')
                ->with('uniquePaymentAmount')
                ->get();

            foreach ($pendingReadings as $reading) {
                if ($reading->uniquePaymentAmount && $reading->uniquePaymentAmount->status === 'reserved') {
                    $reading->uniquePaymentAmount->cancel();
                }
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }

            if ($closedConversations > 0 || $pendingReadings->count() > 0) {
                Log::info('LINE Webhook: Unfollow cleanup สำเร็จ', [
                    'user_id' => $userId,
                    'closed_conversations' => $closedConversations,
                    'cancelled_bills' => $pendingReadings->count(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('LINE Webhook: Unfollow cleanup ล้มเหลว', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
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
            'menu' => $this->handleSimulateTextPostback($userId, $replyToken, 'เมนู'),
            'confirm_transfer' => $this->handleConfirmTransferPostback($userId, $replyToken, $params),
            default => Log::warning('LINE Webhook: Unknown postback action', [
                'user_id' => $userId,
                'action' => $action,
                'data' => $data,
            ]),
        };
    }

    /**
     * Handle deep reading postback
     */
    protected function handleDeepReadingPostback(string $userId, ?string $replyToken): void
    {
        try {
            // ✅ FIX: ดึง profile จาก cache ก่อน → ไม่ต้องให้ ChannelManager เรียก LINE API ซ้ำ
            // ป้องกัน delay 3-8 วินาทีจากการเรียก getUserProfile ใน ChannelManager
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) {
                // ไม่เป็นไร — ChannelManager จะ fallback ดึงเอง
            }
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                'ต้องการดูดวงละเอียด',
                $userProfile,
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
            // ✅ ดึง profile จาก cache → ป้องกัน delay จาก getUserProfile ใน ChannelManager
            $userProfile = null;
            try {
                $userProfile = $this->lineService->getUserProfile($userId);
            } catch (\Exception $profileErr) { /* ignore */ }
            $userProfile = $userProfile ?: ['id' => $userId, 'name' => null];

            $this->channelManager->processMessage(
                FortuneChannelManager::PLATFORM_LINE,
                $userId,
                $text,
                $userProfile,
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

            // ✅ ดึงชื่อผู้ใช้: ลำดับ 1) LINE Profile API, 2) DB (reading ล่าสุด), 3) fallback 'คุณ'
            $userName = 'คุณ';
            $profile = $this->lineService->getUserProfile($userId);
            if ($profile && ! empty($profile['name'])) {
                $userName = $profile['name'];
            } else {
                // ถ้า LINE API ไม่ได้ชื่อ → ดึงจาก reading ล่าสุด
                $latestReading = FortuneReading::where('facebook_user_id', $userId)
                    ->whereNotNull('facebook_user_name')
                    ->latest()
                    ->value('facebook_user_name');
                if ($latestReading) {
                    $userName = $latestReading;
                }
            }

            // ✅ ดึงข้อมูล wallet + รายได้ค่าคอม
            $walletBalance = 0;
            $totalCommission = 0;
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if ($user) {
                $walletBalance = $user->wallet?->balance ?? 0;
                // ดึงยอดรายได้ค่าคอมรวม (จาก wallet transactions ประเภท commission)
                $totalCommission = $user->wallet
                    ? \App\Models\WalletTransaction::where('wallet_id', $user->wallet->id)
                        ->where('type', 'credit')
                        ->where('description', 'LIKE', '%คอมมิชชั่น%')
                        ->sum('amount')
                    : 0;
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
                'member_status' => null,
                'wallet_balance' => $walletBalance,
                'total_commission' => $totalCommission,
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

            // ตอบผู้ใช้ทันที — แจ้งว่ากำลังสร้างคำทำนาย
            $billRef = $reading->bill_reference ?? $reading->id;
            $this->lineService->sendMessageWithReplyFallback(
                $userId,
                "✅ รับแจ้งแล้วค่ะ!\n\n🔮 กำลังสร้างคำทำนายให้ค่ะ...\n⏳ แป๊บเดียวเสร็จค่ะ จะแจ้งทันทีเลยนะคะ ✨",
                $replyToken
            );

            // ✅ FIX: Auto-process payment + dispatch deep reading job
            // เมื่อผู้ใช้กด "โอนเงินแล้ว" → เริ่มสร้างคำทำนายทันที
            // (ก่อนหน้านี้แค่บันทึก transfer_reported แต่ไม่ dispatch job)
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $autoProcessOnReport = $settings->auto_process_on_transfer_report ?? true;

            if ($autoProcessOnReport && ! $reading->is_paid) {
                $reading->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'conversation_status' => \App\Models\FortuneReading::STATUS_PAID,
                ]);

                Log::info('LINE Webhook: Auto-process payment on transfer report', [
                    'reading_id' => $reading->id,
                    'bill_ref' => $billRef,
                ]);

                // Dispatch background job สร้างคำทำนาย
                \App\Jobs\ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id,
                    null,
                    'line',
                    $userId
                );
            }

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
