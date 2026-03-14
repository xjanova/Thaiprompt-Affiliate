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

    /**
     * เก็บ error ล่าสุดเพื่อแสดงให้ admin ดู
     */
    protected string $lastError = '';

    protected const API_ENDPOINT = 'https://api.line.me/v2/bot';

    protected const DATA_API_ENDPOINT = 'https://api-data.line.me/v2/bot';

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();
        $this->channelAccessToken = $this->settings->line_channel_access_token ?? '';
        $this->channelSecret = $this->settings->line_channel_secret ?? '';
    }

    /**
     * ดึง error ล่าสุด
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * ตรวจสอบ Access Token โดยเรียก LINE API /info endpoint
     *
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function verifyToken(): array
    {
        if (empty($this->channelAccessToken)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Channel Access Token ยังไม่ได้ตั้งค่า',
            ];
        }

        try {
            // เรียก GET /v2/bot/info เพื่อดึงข้อมูล Bot
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->channelAccessToken}",
            ])->get(self::API_ENDPOINT.'/info');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'error' => null,
                ];
            }

            $body = $response->json();
            $errorMsg = $body['message'] ?? 'Unknown error';

            return [
                'success' => false,
                'data' => null,
                'error' => "LINE API ตอบกลับ HTTP {$response->status()}: {$errorMsg}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'ไม่สามารถเชื่อมต่อ LINE API: '.$e->getMessage(),
            ];
        }
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
    public function sendFlexMessage(string $userId, array $flexContent, string $altText = 'ตลาดสดไทยพร๊อม'): bool
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
    public function replyFlexMessage(string $replyToken, array $flexContent, string $altText = 'ตลาดสดไทยพร๊อม'): bool
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

        // ตรวจสอบ cache ก่อน — ไม่ cache null เพื่อให้ retry ได้
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->channelAccessToken}",
            ])
                ->connectTimeout(3)
                ->timeout(5)
                ->get(self::API_ENDPOINT."/profile/{$userId}");

            if ($response->successful()) {
                $profile = $response->json();
                Cache::put($cacheKey, $profile, 86400); // cache 24 ชม.

                return $profile;
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
            ])->connectTimeout(5)->timeout(15)->get(self::DATA_API_ENDPOINT."/message/{$messageId}/content");

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
        $distance = $listing['distance'] ?? '?';
        $shopName = mb_substr($listing['shop_name'] ?? 'ร้านค้า', 0, 20);
        $title = mb_substr($listing['title'] ?? 'สินค้า', 0, 30);
        $priceText = '฿' . number_format($listing['price'] ?? 0, 0) . '/' . ($listing['unit'] ?? 'ชิ้น');

        return [
            'type' => 'bubble',
            'size' => 'kilo',
            'hero' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'image',
                        'url' => $listing['image'] ?? url('/images/fresh-market/default-product.png'),
                        'size' => 'full',
                        'aspectRatio' => '4:3',
                        'aspectMode' => 'cover',
                    ],
                    // Badge ระยะทาง (ซ้อนทับรูป)
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'position' => 'absolute',
                        'offsetTop' => '8px',
                        'offsetEnd' => '8px',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => "📍 {$distance} กม.",
                                'size' => 'xxs',
                                'color' => '#FFFFFF',
                            ],
                        ],
                        'backgroundColor' => '#00000088',
                        'cornerRadius' => '12px',
                        'paddingAll' => '4px',
                        'paddingStart' => '8px',
                        'paddingEnd' => '8px',
                    ],
                ],
                'paddingAll' => '0px',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => '12px',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $title,
                        'weight' => 'bold',
                        'size' => 'md',
                        'wrap' => true,
                        'maxLines' => 2,
                    ],
                    [
                        'type' => 'text',
                        'text' => $priceText,
                        'color' => $primaryColor,
                        'weight' => 'bold',
                        'size' => 'lg',
                    ],
                    [
                        'type' => 'text',
                        'text' => "🏪 {$shopName}",
                        'size' => 'xs',
                        'color' => '#888888',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'paddingAll' => '10px',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'height' => 'sm',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🛒 สั่งซื้อ',
                            'data' => 'action=order&listing_id=' . ($listing['id'] ?? 0),
                            'displayText' => 'สั่งซื้อ ' . $title,
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📋 ดูเพิ่ม',
                            'uri' => url('/taladsod/listing/' . ($listing['slug'] ?? '')),
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

        // จำกัด 5 bubbles เพื่อไม่เกิน LINE 50KB limit (kilo size ~8KB/bubble)
        foreach (array_slice($listings, 0, 5) as $listing) {
            $bubbles[] = $this->buildListingFlex($listing);
        }

        // ตรวจสอบขนาด — ถ้าเกิน 50KB ลด bubble ลง
        $carousel = ['type' => 'carousel', 'contents' => $bubbles];
        $size = strlen(json_encode($carousel));
        if ($size > 49000) {
            $carousel['contents'] = array_slice($bubbles, 0, 3);
            Log::warning('FreshMarketLine: Carousel ใหญ่เกิน 50KB ลดเหลือ 3 bubbles', ['size' => $size]);
        }

        return $carousel;
    }

    /**
     * สร้าง Flex สรุปออเดอร์
     */
    public function buildOrderSummaryFlex(array $order): array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';
        $title = $order['title'] ?? 'สินค้า';
        $quantity = $order['quantity'] ?? 1;
        $unitPrice = $order['unit_price'] ?? 0;
        $subtotal = $order['subtotal'] ?? ($unitPrice * $quantity);
        $deliveryFee = $order['delivery_fee'] ?? 0;
        $total = $order['total'] ?? ($subtotal + $deliveryFee);
        $deliveryLabel = $order['delivery_type_label'] ?? '🏪 นัดรับเอง';
        $unit = $order['unit'] ?? 'ชิ้น';

        // สร้างรายละเอียดราคา
        $priceRows = [
            $this->buildFlexRow("📦 {$title}", "x{$quantity} {$unit}"),
            $this->buildFlexRow('💵 ราคา/หน่วย', '฿' . number_format($unitPrice, 0)),
            $this->buildFlexRow('🛒 ค่าสินค้า', '฿' . number_format($subtotal, 0)),
        ];

        if ($deliveryFee > 0) {
            $priceRows[] = $this->buildFlexRow('🚚 ค่าจัดส่ง', '฿' . number_format($deliveryFee, 0));
        }

        $priceRows[] = ['type' => 'separator', 'margin' => 'md'];
        $priceRows[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'contents' => [
                ['type' => 'text', 'text' => '💰 รวมทั้งหมด', 'weight' => 'bold', 'size' => 'md', 'flex' => 0],
                ['type' => 'text', 'text' => '฿' . number_format($total, 0), 'weight' => 'bold', 'size' => 'lg', 'color' => $primaryColor, 'align' => 'end'],
            ],
        ];
        $priceRows[] = [
            'type' => 'text',
            'text' => "📍 {$deliveryLabel}",
            'size' => 'sm',
            'color' => '#888888',
            'margin' => 'md',
        ];

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
                        'size' => 'xl',
                        'color' => '#333333',
                    ],
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => $priceRows,
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
                        'height' => 'sm',
                        'action' => [
                            'type' => 'postback',
                            'label' => '✅ ยืนยันสั่งซื้อ',
                            'data' => 'action=confirm_order&order_id=' . ($order['id'] ?? 0),
                            'displayText' => 'ยืนยัน',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'postback',
                            'label' => '❌ ยกเลิก',
                            'data' => 'action=cancel_order&order_id=' . ($order['id'] ?? 0),
                            'displayText' => 'ยกเลิก',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex row แสดงราคา (label + value)
     */
    protected function buildFlexRow(string $label, string $value): array
    {
        return [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'sm',
            'contents' => [
                ['type' => 'text', 'text' => $label, 'size' => 'sm', 'color' => '#555555', 'flex' => 0],
                ['type' => 'text', 'text' => $value, 'size' => 'sm', 'color' => '#333333', 'align' => 'end'],
            ],
        ];
    }

    /**
     * สร้าง Flex Message แจ้งสั่งซื้อสำเร็จ (สวยงาม พร้อมปุ่มติดตาม)
     */
    public function buildOrderConfirmationFlex(array $order): array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';
        $orderNumber = $order['order_number'] ?? 'TSD-NEW';
        $title = $order['title'] ?? 'สินค้า';
        $quantity = $order['quantity'] ?? 1;
        $unit = $order['unit'] ?? 'ชิ้น';
        $total = $order['total'] ?? 0;
        $deliveryLabel = $order['delivery_type_label'] ?? '🏪 นัดรับเอง';

        return [
            'type' => 'bubble',
            'styles' => [
                'header' => ['backgroundColor' => $primaryColor],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '✅ สั่งซื้อสำเร็จ!', 'color' => '#FFFFFF', 'weight' => 'bold', 'size' => 'lg'],
                    ['type' => 'text', 'text' => "หมายเลข: {$orderNumber}", 'color' => '#FFFFFFCC', 'size' => 'sm', 'margin' => 'sm'],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'contents' => [
                    $this->buildFlexRow("📦 {$title}", "x{$quantity} {$unit}"),
                    $this->buildFlexRow('💰 ยอดรวม', '฿' . number_format($total, 0)),
                    $this->buildFlexRow('📍 วิธีรับ', $deliveryLabel),
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'text',
                        'text' => '⏳ รอผู้ขายรับออเดอร์ค่ะ',
                        'size' => 'sm',
                        'color' => '#FF8C00',
                        'margin' => 'md',
                        'weight' => 'bold',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'height' => 'sm',
                        'action' => [
                            'type' => 'postback',
                            'label' => '📦 เช็คสถานะออเดอร์',
                            'data' => 'action=track_order&order_id=' . ($order['id'] ?? 0),
                            'displayText' => 'สถานะ',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🛒 สั่งเพิ่ม',
                            'data' => 'action=menu&choice=buy',
                            'displayText' => 'อยากซื้อ',
                        ],
                    ],
                ],
            ],
        ];
    }

    // ===== Listing Review Flex =====

    /**
     * สร้าง Flex Message สรุปสินค้าก่อนลงขาย (ยืนยัน/แก้ไข/ยกเลิก)
     */
    public function buildListingReviewFlex(array $listing): ?array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';
        $title = $listing['title'] ?? 'สินค้า';
        $price = number_format($listing['price'] ?? 0, 0);
        $unit = $listing['unit'] ?? 'ชิ้น';
        $imageCount = $listing['image_count'] ?? 0;
        $description = $listing['description'] ?? '';
        $heroImage = $listing['images'][0] ?? null;

        $bodyContents = [
            [
                'type' => 'text',
                'text' => "📦 {$title}",
                'weight' => 'bold',
                'size' => 'lg',
                'wrap' => true,
            ],
            [
                'type' => 'text',
                'text' => "฿{$price} / {$unit}",
                'color' => $primaryColor,
                'weight' => 'bold',
                'size' => 'xl',
                'margin' => 'sm',
            ],
            [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "📸 {$imageCount} รูป",
                        'size' => 'sm',
                        'color' => '#888888',
                    ],
                    [
                        'type' => 'text',
                        'text' => '📍 มีพิกัดร้าน',
                        'size' => 'sm',
                        'color' => '#888888',
                        'margin' => 'xs',
                    ],
                ],
            ],
        ];

        if ($description) {
            $bodyContents[] = [
                'type' => 'text',
                'text' => $description,
                'size' => 'sm',
                'color' => '#666666',
                'margin' => 'md',
                'wrap' => true,
                'maxLines' => 2,
            ];
        }

        $bubble = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'postback',
                            'label' => '✅ ยืนยันลงขาย',
                            'data' => 'action=confirm_listing',
                        ],
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'button',
                                'style' => 'secondary',
                                'action' => [
                                    'type' => 'postback',
                                    'label' => '✏️ แก้ไข',
                                    'data' => 'action=edit_listing',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'style' => 'secondary',
                                'action' => [
                                    'type' => 'message',
                                    'label' => '❌ ยกเลิก',
                                    'text' => 'ยกเลิก',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // เพิ่ม hero image ถ้ามี
        if ($heroImage) {
            $bubble['hero'] = [
                'type' => 'image',
                'url' => $heroImage,
                'size' => 'full',
                'aspectRatio' => '4:3',
                'aspectMode' => 'cover',
            ];
        }

        return $bubble;
    }

    /**
     * สร้าง Flex Message ลงขายสำเร็จ (ดูรายการ/ลงขายเพิ่ม)
     */
    public function buildListingSuccessFlex(array $listing): array
    {
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';
        $title = $listing['title'] ?? 'สินค้า';
        $price = number_format($listing['price'] ?? 0, 0);
        $unit = $listing['unit'] ?? 'ชิ้น';

        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🎉 ลงขายสำเร็จ!',
                        'weight' => 'bold',
                        'size' => 'lg',
                        'color' => $primaryColor,
                    ],
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => "📦 {$title}",
                                'size' => 'md',
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => "💰 ฿{$price}/{$unit}",
                                'color' => $primaryColor,
                                'size' => 'md',
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
                            'label' => '📸 ลงขายเพิ่ม',
                            'data' => 'action=sell_more',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📋 ดูรายการ',
                            'uri' => url('/taladsod/seller-dashboard'),
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
            $this->lastError = 'Channel Access Token ยังไม่ได้ตั้งค่า';
            Log::warning('FreshMarketLine: Channel access token ไม่ได้ตั้งค่า');

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->channelAccessToken}",
                'Content-Type' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(10)
                ->post(self::API_ENDPOINT.$path, $data);

            if (! $response->successful()) {
                $body = $response->json() ?? [];
                $errorMsg = $body['message'] ?? mb_substr($response->body(), 0, 500);
                $this->lastError = "LINE API HTTP {$response->status()}: {$errorMsg}";

                Log::warning('FreshMarketLine: API call ล้มเหลว', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $this->lastError = '';

            return true;
        } catch (\Exception $e) {
            $this->lastError = 'ไม่สามารถเชื่อมต่อ LINE API: '.$e->getMessage();

            Log::error('FreshMarketLine: Exception calling API', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
