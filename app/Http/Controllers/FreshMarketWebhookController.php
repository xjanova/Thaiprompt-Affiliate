<?php

namespace App\Http\Controllers;

use App\Models\FreshMarketSetting;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketLineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketWebhookController - LINE Webhook สำหรับตลาดสดไทยพร๊อม
 *
 * รับ Events จาก LINE OA ตลาดสด (แยก OA จากดูดวง)
 * ตาม pattern ของ LineFortuneWebhookController
 */
class FreshMarketWebhookController extends Controller
{
    protected FreshMarketSetting $settings;

    protected FreshMarketLineService $lineService;

    protected ?FreshMarketChannelManager $channelManager = null;

    public function __construct()
    {
        $this->settings = FreshMarketSetting::getSettings();
        $this->lineService = new FreshMarketLineService($this->settings);
        // ChannelManager จะสร้างเมื่อใช้งานจริง (lazy-load) เพื่อให้ webhook ตอบ 200 เร็ว
    }

    /**
     * สร้าง ChannelManager แบบ lazy-load เพื่อไม่ให้ constructor ช้า
     */
    protected function getChannelManager(): FreshMarketChannelManager
    {
        if ($this->channelManager === null) {
            $this->channelManager = new FreshMarketChannelManager($this->settings);
        }

        return $this->channelManager;
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

        $failedCount = 0;
        foreach ($events as $event) {
            try {
                $this->handleEvent($event);
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('FreshMarketWebhook: Error processing event', [
                    'error' => $e->getMessage(),
                    'event_type' => $event['type'] ?? 'unknown',
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // LINE จะ retry ถ้า status ≠ 200 — คืน 200 เสมอเพื่อป้องกัน retry ซ้ำ
        // แต่ log ไว้เพื่อ monitoring
        if ($failedCount > 0) {
            Log::warning("FreshMarketWebhook: {$failedCount}/" . count($events) . ' events failed');
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

        // Flood protection (ใช้ increment เพื่อป้องกัน race condition)
        $floodKey = "fm_flood:{$userId}";
        $floodCount = (int) cache()->increment($floodKey);
        if ($floodCount === 1) {
            cache()->put($floodKey, 1, now()->addSeconds(10));
        }

        if ($floodCount >= 5) {
            Log::warning('FreshMarketWebhook: Flood detected', ['user_id' => $userId]);

            if ($replyToken && $floodCount === 5) {
                $this->lineService->replyMessage($replyToken, [
                    ['type' => 'text', 'text' => '⚠️ ส่งข้อความเร็วเกินไปค่ะ กรุณารอสักครู่นะคะ'],
                ]);
            }

            return;
        }

        // ดึงโปรไฟล์ (cache อยู่แล้วใน LineService ไม่ต้อง call ทุกครั้ง)
        $extra = [
            'reply_token' => $replyToken,
            'user_profile' => $this->lineService->getUserProfile($userId),
        ];

        switch ($messageType) {
            case 'text':
                $messageText = $event['message']['text'] ?? '';
                $this->getChannelManager()->processMessage($userId, $messageText, $extra);
                break;

            case 'image':
                $messageId = $event['message']['id'] ?? '';
                $this->getChannelManager()->processImage($userId, $messageId, $extra);
                break;

            case 'location':
                $lat = $event['message']['latitude'] ?? 0;
                $lng = $event['message']['longitude'] ?? 0;
                $this->getChannelManager()->processLocation($userId, $lat, $lng, $extra);
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

        $this->getChannelManager()->handleFollow($userId, [
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

        $this->getChannelManager()->handleUnfollow($userId);
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

        $this->getChannelManager()->handlePostback($userId, $data, [
            'reply_token' => $replyToken,
        ]);
    }
}
