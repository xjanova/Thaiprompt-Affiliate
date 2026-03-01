<?php

namespace App\Services;

use App\Models\FreshMarketSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketLineService - บริการส่งข้อความผ่าน LINE OA ตลาดสด
 *
 * ใช้ credentials แยกจากระบบดูดวง (FreshMarketSetting)
 * ตาม pattern ของ LineFortuneService
 */
class FreshMarketLineService
{
    protected FreshMarketSetting $settings;

    protected string $channelAccessToken;

    protected string $channelSecret;

    protected const API_ENDPOINT = 'https://api.line.me/v2/bot';

    protected const DATA_API_ENDPOINT = 'https://api-data.line.me/v2/bot';

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();
        $this->channelAccessToken = $this->settings->line_channel_access_token ?? '';
        $this->channelSecret = $this->settings->line_channel_secret ?? '';
    }

    // ===== Signature Verification =====

    /**
     * ตรวจสอบลายเซ็น LINE Webhook
     */
    public function verifySignature(string $body, string $signature): bool
    {
        if (empty($this->channelSecret)) {
            Log::warning('FreshMarketLine: Channel secret ไม่ได้ตั้งค่า');

            return false;
        }

        $hash = base64_encode(hash_hmac('sha256', $body, $this->channelSecret, true));

        return hash_equals($hash, $signature);
    }

    // ===== Messaging =====

    /**
     * ตอบกลับข้อความ (Reply)
     */
    public function replyMessage(string $replyToken, array $messages): bool
    {
        return $this->callApi('/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $messages,
        ]);
    }

    /**
     * ส่งข้อความแบบ Push
     */
    public function pushMessage(string $userId, array $messages): bool
    {
        return $this->callApi('/message/push', [
            'to' => $userId,
            'messages' => $messages,
        ]);
    }

    /**
     * ส่งข้อความ Text
     */
    public function sendText(string $userId, string $text): bool
    {
        return $this->pushMessage($userId, [
            ['type' => 'text', 'text' => $text],
        ]);
    }

    /**
     * ส่ง Flex Message
     */
    public function sendFlexMessage(string $userId, array $flexContent, string $altText = 'ตลาดสดไทยพร้อม'): bool
    {
        return $this->pushMessage($userId, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flexContent,
            ],
        ]);
    }

    /**
     * ตอบกลับด้วย Flex Message
     */
    public function replyFlexMessage(string $replyToken, array $flexContent, string $altText = 'ตลาดสดไทยพร้อม'): bool
    {
        return $this->replyMessage($replyToken, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flexContent,
            ],
        ]);
    }

    // ===== User Profile =====

    /**
     * ดึงโปรไฟล์ผู้ใช้ LINE (cache 24 ชม.)
     */
    public function getUserProfile(string $userId): ?array
    {
        $cacheKey = "fm_line_profile:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->channelAccessToken}",
                ])->get(self::API_ENDPOINT."/profile/{$userId}");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('FreshMarketLine: ไม่สามารถดึงโปรไฟล์ได้', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('FreshMarketLine: Error ดึงโปรไฟล์', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);

                return null;
            }
        });
    }

    // ===== Content API =====

    /**
     * ดาวน์โหลดเนื้อหาข้อความ (รูปภาพ, เสียง, วิดีโอ)
     */
    public function getMessageContent(string $messageId): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->channelAccessToken}",
            ])->get(self::DATA_API_ENDPOINT."/message/{$messageId}/content");

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('FreshMarketLine: ไม่สามารถดาวน์โหลดเนื้อหาได้', [
                'message_id' => $messageId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('FreshMarketLine: Error ดาวน์โหลดเนื้อหา', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return null;
        }
    }

    // ===== Flex Message Builders =====

    /**
     * สร้าง Flex Message สินค้า
     */
    public function buildListingFlex(array $listing): array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';

        return [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url' => $listing['image'] ?? 'https://via.placeholder.com/400x300',
                'size' => 'full',
                'aspectRatio' => '4:3',
                'aspectMode' => 'cover',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $listing['title'] ?? 'สินค้า',
                        'weight' => 'bold',
                        'size' => 'lg',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => '฿'.number_format($listing['price'] ?? 0, 0).' / '.($listing['unit'] ?? 'ชิ้น'),
                        'color' => $primaryColor,
                        'weight' => 'bold',
                        'size' => 'xl',
                        'margin' => 'sm',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'margin' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📍 '.($listing['distance'] ?? '?').' กม.',
                                'size' => 'sm',
                                'color' => '#888888',
                            ],
                            [
                                'type' => 'text',
                                'text' => '🏪 '.($listing['shop_name'] ?? 'ร้านค้า'),
                                'size' => 'sm',
                                'color' => '#888888',
                                'align' => 'end',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'postback',
                            'label' => '🛒 สั่งซื้อ',
                            'data' => 'action=order&listing_id='.($listing['id'] ?? 0),
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📋 ดูเพิ่ม',
                            'uri' => url('/taladsod/listing/'.($listing['slug'] ?? '')),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Carousel สินค้าหลายรายการ
     */
    public function buildListingsCarousel(array $listings): array
    {
        $bubbles = [];

        foreach (array_slice($listings, 0, 10) as $listing) {
            $bubbles[] = $this->buildListingFlex($listing);
        }

        return [
            'type' => 'carousel',
            'contents' => $bubbles,
        ];
    }

    /**
     * สร้าง Flex สรุปออเดอร์
     */
    public function buildOrderSummaryFlex(array $order): array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';

        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🧾 สรุปคำสั่งซื้อ',
                        'weight' => 'bold',
                        'size' => 'lg',
                    ],
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => ($order['title'] ?? 'สินค้า').' x'.($order['quantity'] ?? 1),
                                'size' => 'md',
                            ],
                            [
                                'type' => 'text',
                                'text' => '💰 รวม ฿'.number_format($order['total'] ?? 0, 0),
                                'color' => $primaryColor,
                                'weight' => 'bold',
                                'size' => 'lg',
                                'margin' => 'sm',
                            ],
                            [
                                'type' => 'text',
                                'text' => '📍 '.($order['delivery_type_label'] ?? 'นัดรับ'),
                                'size' => 'sm',
                                'color' => '#888888',
                                'margin' => 'sm',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'postback',
                            'label' => '✅ ยืนยัน',
                            'data' => 'action=confirm_order&order_id='.($order['id'] ?? 0),
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'action' => [
                            'type' => 'postback',
                            'label' => '❌ ยกเลิก',
                            'data' => 'action=cancel_order&order_id='.($order['id'] ?? 0),
                        ],
                    ],
                ],
            ],
        ];
    }

    // ===== Internal API Call =====

    /**
     * เรียก LINE Messaging API
     */
    protected function callApi(string $path, array $data): bool
    {
        if (empty($this->channelAccessToken)) {
            Log::warning('FreshMarketLine: Channel access token ไม่ได้ตั้งค่า');

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->channelAccessToken}",
                'Content-Type' => 'application/json',
            ])->post(self::API_ENDPOINT.$path, $data);

            if (! $response->successful()) {
                Log::warning('FreshMarketLine: API call ล้มเหลว', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('FreshMarketLine: Exception calling API', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
