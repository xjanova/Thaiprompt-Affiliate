<?php

namespace App\Http\Controllers;

use App\Models\FreshMarketSetting;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketLineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketWebhookController - LINE Webhook สำหรับตลาดสดไทยพร้อม
 *
 * รับ Events จาก LINE OA ตลาดสด (แยก OA จากดูดวง)
 * ตาม pattern ของ LineFortuneWebhookController
 */
class FreshMarketWebhookController extends Controller
{
    protected FreshMarketSetting $settings;

    protected FreshMarketLineService $lineService;

    protected FreshMarketChannelManager $channelManager;

    public function __construct()
    {
        $this->settings = FreshMarketSetting::getSettings();
        $this->lineService = new FreshMarketLineService($this->settings);
        $this->channelManager = new FreshMarketChannelManager($this->settings);
    }

    /**
     * รับ Webhook Events จาก LINE Platform
     *
     * @return Response
     */
    public function handle(Request $request): Response
    {
        // ตรวจสอบว่า LINE ตั้งค่าแล้ว
        if (! $this->settings->isLineConfigured()) {
            Log::debug('FreshMarketWebhook: LINE ยังไม่ได้ตั้งค่า');

            return response('LINE not configured', 200);
        }

        // ตรวจสอบลายเซ็น
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if (! $this->lineService->verifySignature($body, $signature ?? '')) {
            Log::warning('FreshMarketWebhook: ลายเซ็นไม่ถูกต้อง', [
                'ip' => $request->ip(),
            ]);

            return response('Invalid signature', 400);
        }

        // Parse events
        $data = json_decode($body, true);
        $events = $data['events'] ?? [];

        foreach ($events as $event) {
            try {
                $this->handleEvent($event);
            } catch (\Exception $e) {
                Log::error('FreshMarketWebhook: Error processing event', [
                    'error' => $e->getMessage(),
                    'event_type' => $event['type'] ?? 'unknown',
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * แยกประเภท Event แล้วส่งไปจัดการ
     */
    protected function handleEvent(array $event): void
    {
        $eventType = $event['type'] ?? null;

        match ($eventType) {
            'message' => $this->handleMessageEvent($event),
            'follow' => $this->handleFollowEvent($event),
            'unfollow' => $this->handleUnfollowEvent($event),
            'postback' => $this->handlePostbackEvent($event),
            default => Log::debug('FreshMarketWebhook: Event type ไม่รองรับ', ['type' => $eventType]),
        };
    }

    /**
     * จัดการ Message Event
     */
    protected function handleMessageEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $messageType = $event['message']['type'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        // Flood protection
        $floodKey = "fm_flood:{$userId}";
        $floodCount = (int) cache()->get($floodKey, 0);
        cache()->put($floodKey, $floodCount + 1, 10);

        if ($floodCount >= 5) {
            Log::warning('FreshMarketWebhook: Flood detected', ['user_id' => $userId]);

            if ($replyToken && $floodCount === 5) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => '⚠️ ส่งข้อความเร็วเกินไปค่ะ กรุณารอสักครู่นะคะ'],
                ]);
            }

            return;
        }

        $extra = [
            'reply_token' => $replyToken,
            'user_profile' => $this->lineService->getUserProfile($userId),
        ];

        switch ($messageType) {
            case 'text':
                $messageText = $event['message']['text'] ?? '';
                $this->channelManager->processMessage($userId, $messageText, $extra);
                break;

            case 'image':
                $messageId = $event['message']['id'] ?? '';
                $this->channelManager->processImage($userId, $messageId, $extra);
                break;

            case 'location':
                $lat = $event['message']['latitude'] ?? 0;
                $lng = $event['message']['longitude'] ?? 0;
                $this->channelManager->processLocation($userId, $lat, $lng, $extra);
                break;

            case 'sticker':
                // ไม่ต้องทำอะไร - ตอบเป็นมิตร
                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => '😊 น่ารักจังค่ะ! มีอะไรให้พี่ตลาดช่วยไหมคะ?'],
                    ]);
                }
                break;

            default:
                if ($replyToken) {
                    $this->lineService->replyMessage($replyToken, [
                        ['type' => 'text', 'text' => 'ขอโทษค่ะ พี่ตลาดรับได้เฉพาะข้อความ รูปภาพ และตำแหน่งค่ะ 😊'],
                    ]);
                }
        }
    }

    /**
     * จัดการ Follow Event (เพิ่มเพื่อน)
     */
    protected function handleFollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId) {
            return;
        }

        Log::info('FreshMarketWebhook: ผู้ใช้เพิ่มเพื่อน', ['user_id' => $userId]);

        $this->channelManager->handleFollow($userId, [
            'reply_token' => $replyToken,
        ]);
    }

    /**
     * จัดการ Unfollow Event (ลบเพื่อน)
     */
    protected function handleUnfollowEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;

        if (! $userId) {
            return;
        }

        Log::info('FreshMarketWebhook: ผู้ใช้ลบเพื่อน', ['user_id' => $userId]);

        $this->channelManager->handleUnfollow($userId);
    }

    /**
     * จัดการ Postback Event (กดปุ่ม)
     */
    protected function handlePostbackEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $data = $event['postback']['data'] ?? '';
        $replyToken = $event['replyToken'] ?? null;

        if (! $userId || ! $data) {
            return;
        }

        Log::debug('FreshMarketWebhook: Postback', [
            'user_id' => $userId,
            'data' => $data,
        ]);

        $this->channelManager->handlePostback($userId, $data, [
            'reply_token' => $replyToken,
        ]);
    }
}
