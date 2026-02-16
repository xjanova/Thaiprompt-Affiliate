<?php

namespace App\Http\Controllers;

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
}
