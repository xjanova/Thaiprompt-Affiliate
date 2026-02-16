<?php

namespace App\Services;

use App\Contracts\MessagingPlatformInterface;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LINE Fortune Service
 *
 * บริการส่งข้อความดูดวงผ่าน LINE Official Account
 * รองรับ Flex Message สำหรับแสดงผลที่สวยงาม
 */
class LineFortuneService implements MessagingPlatformInterface
{
    protected FortuneTellingSetting $settings;

    protected string $channelAccessToken;

    protected string $channelSecret;

    /**
     * LINE Messaging API Endpoint
     */
    protected const API_ENDPOINT = 'https://api.line.me/v2/bot';

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->channelAccessToken = $this->settings->line_channel_access_token ?? config('services.line.channel_token');
        $this->channelSecret = $this->settings->line_channel_secret ?? config('services.line.channel_secret');
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatformName(): string
    {
        return 'line';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRichMessage(): bool
    {
        return true; // LINE รองรับ Flex Message
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage(string $recipientId, string $message, array $options = []): bool
    {
        $messages = [
            [
                'type' => 'text',
                'text' => $message,
            ],
        ];

        // ถ้ามี quick replies
        if (! empty($options['quick_replies'])) {
            $messages[0]['quickReply'] = [
                'items' => $this->buildQuickReplyItems($options['quick_replies']),
            ];
        }

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRichMessage(string $recipientId, array $richContent): bool
    {
        $messages = [
            [
                'type' => 'flex',
                'altText' => $richContent['alt_text'] ?? 'ข้อความจากระบบดูดวง',
                'contents' => $richContent['contents'] ?? $richContent,
            ],
        ];

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function sendImage(string $recipientId, string $imageUrl, ?string $previewUrl = null): bool
    {
        $messages = [
            [
                'type' => 'image',
                'originalContentUrl' => $imageUrl,
                'previewImageUrl' => $previewUrl ?? $imageUrl,
            ],
        ];

        return $this->pushMessage($recipientId, $messages);
    }

    /**
     * {@inheritdoc}
     */
    public function sendQuickReplies(string $recipientId, string $message, array $quickReplies): bool
    {
        return $this->sendMessage($recipientId, $message, ['quick_replies' => $quickReplies]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile(string $userId): ?array
    {
        try {
            // ⚡ ลด timeout: profile ไม่สำคัญมาก ถ้าช้าใช้ชื่อ default "คุณ"
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(3)
                ->connectTimeout(2)
                ->get(self::API_ENDPOINT."/profile/{$userId}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'id' => $data['userId'] ?? $userId,
                    'name' => $data['displayName'] ?? null,
                    'picture_url' => $data['pictureUrl'] ?? null,
                    'status_message' => $data['statusMessage'] ?? null,
                    'language' => $data['language'] ?? 'th',
                    'platform' => 'line',
                ];
            }

            Log::warning('LINE: ไม่สามารถดึงโปรไฟล์ได้', [
                'user_id' => $userId,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('LINE: Error getting profile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isMessageEvent(array $event): bool
    {
        return ($event['type'] ?? null) === 'message' &&
               ($event['message']['type'] ?? null) === 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageText(array $event): ?string
    {
        if (! $this->isMessageEvent($event)) {
            return null;
        }

        return $event['message']['text'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdFromEvent(array $event): ?string
    {
        return $event['source']['userId'] ?? null;
    }

    // ============================================================
    // Flex Message Templates - สำหรับแสดงผลสวยงาม
    // ============================================================

    /**
     * สร้าง Flex Message สำหรับคำทำนาย
     *
     * @param  string  $prediction  คำทำนาย
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $billRef  เลขที่บิล
     * @return array Flex Message content
     */
    public function buildFortuneFlexMessage(string $prediction, string $userName, ?string $billRef = null): array
    {
        $bodyContents = [
            // ชื่อผู้ใช้
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🌙',
                        'size' => 'xl',
                        'flex' => 0,
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'paddingStart' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => "สำหรับคุณ{$userName}",
                                'size' => 'md',
                                'weight' => 'bold',
                                'color' => '#6B46C1',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'ดวงชะตาของคุณวันนี้',
                                'size' => 'xs',
                                'color' => '#999999',
                                'margin' => 'xs',
                            ],
                        ],
                    ],
                ],
                'alignItems' => 'center',
            ],
            // เส้นแบ่ง
            [
                'type' => 'separator',
                'margin' => 'xl',
                'color' => '#E8E0FF',
            ],
            // คำทำนาย
            [
                'type' => 'text',
                'text' => $prediction,
                'wrap' => true,
                'size' => 'md',
                'color' => '#333333',
                'margin' => 'xl',
                'lineSpacing' => '6px',
            ],
        ];

        // เพิ่มเลขที่บิลถ้ามี
        if ($billRef) {
            $bodyContents[] = [
                'type' => 'separator',
                'margin' => 'xl',
                'color' => '#E8E0FF',
            ];
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'md',
                'backgroundColor' => '#F8F7FF',
                'cornerRadius' => 'md',
                'paddingAll' => 'sm',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔖 เลขที่บิล:',
                        'size' => 'xs',
                        'color' => '#888888',
                        'flex' => 2,
                    ],
                    [
                        'type' => 'text',
                        'text' => $billRef,
                        'size' => 'xs',
                        'color' => '#6B46C1',
                        'flex' => 3,
                        'weight' => 'bold',
                    ],
                ],
            ];
        }

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
                'footer' => ['backgroundColor' => '#F8F7FF'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔮',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => '✨ จันทราดูดวง ✨',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ผลคำทำนายดวงชะตา',
                        'color' => '#FFFFFFCC',
                        'size' => 'sm',
                        'align' => 'center',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✨ แชร์ให้เพื่อนมาดูดวงด้วยกันนะคะ',
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'align' => 'center',
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับเสนอดูดวงละเอียด (Upsell)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  float  $price  ราคา
     * @return array Flex Message content
     */
    public function buildUpsellFlexMessage(string $userName, float $price): array
    {
        return [
            'type' => 'bubble',
            'styles' => [
                'header' => [
                    'backgroundColor' => '#F6AD55',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🌟 ดูดวงละเอียด 🌟',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "คุณ{$userName} อยากรู้ลึกกว่านี้ไหมคะ?",
                        'wrap' => true,
                        'size' => 'md',
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    ['type' => 'text', 'text' => '📅', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'บอกวันเดือนปีเกิด', 'flex' => 5, 'size' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'margin' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '❓', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'ถามได้ 3 คำถาม', 'flex' => 5, 'size' => 'sm'],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'margin' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '💰', 'flex' => 1],
                                    ['type' => 'text', 'text' => "เพียง {$price} บาท", 'flex' => 5, 'size' => 'sm', 'weight' => 'bold', 'color' => '#6B46C1'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'md',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6B46C1',
                        'action' => [
                            'type' => 'message',
                            'label' => '✨ ต้องการ',
                            'text' => 'ต้องการดูดวงละเอียด',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'action' => [
                            'type' => 'message',
                            'label' => 'ไม่ต้องการ',
                            'text' => 'ไม่ต้องการ',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับแสดงบัญชีธนาคาร
     *
     * @param  array  $bankAccounts  รายการบัญชีธนาคาร
     * @param  float  $amount  ยอดเงินที่ต้องโอน
     * @param  string  $expiresAt  เวลาหมดอายุ
     * @param  string|null  $billRef  เลขที่บิล
     * @return array Flex Message content
     */
    public function buildPaymentFlexMessage(array $bankAccounts, float $amount, string $expiresAt, ?string $billRef = null): array
    {
        $bankContents = [];

        foreach ($bankAccounts as $account) {
            $isPromptpay = $account['is_promptpay'] ?? false;
            $contents = [];

            // ชื่อธนาคาร/พร้อมเพย์
            $contents[] = [
                'type' => 'text',
                'text' => $isPromptpay ? "📱 {$account['bank_name']}" : "🏦 {$account['bank_name']}",
                'weight' => 'bold',
                'size' => 'sm',
            ];

            // เลขบัญชี/พร้อมเพย์
            $numberLabel = $isPromptpay ? 'พร้อมเพย์' : 'เลขบัญชี';
            $contents[] = [
                'type' => 'text',
                'text' => "{$numberLabel}: {$account['account_number']}",
                'size' => 'sm',
                'margin' => 'sm',
            ];

            // ชื่อบัญชี
            $contents[] = [
                'type' => 'text',
                'text' => "ชื่อ: {$account['account_name']}",
                'size' => 'sm',
            ];

            // PromptPay เสริม (กรณี both mode)
            if (! $isPromptpay && ! empty($account['promptpay_id'])) {
                $contents[] = [
                    'type' => 'text',
                    'text' => "📱 พร้อมเพย์: {$account['promptpay_id']}",
                    'size' => 'sm',
                    'color' => '#6B46C1',
                    'margin' => 'sm',
                ];
            }

            $bankContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'paddingAll' => 'md',
                'backgroundColor' => $isPromptpay ? '#F0E6FF' : '#F7FAFC',
                'cornerRadius' => 'md',
                'contents' => $contents,
            ];
        }

        $headerContents = [
            [
                'type' => 'text',
                'text' => '฿'.number_format($amount, 2),
                'color' => '#FFFFFF',
                'size' => 'xxl',
                'weight' => 'bold',
                'align' => 'center',
            ],
            [
                'type' => 'text',
                'text' => '⚠️ กรุณาโอนตรงตามยอด (รวมทศนิยม)',
                'color' => '#FED7D7',
                'size' => 'xs',
                'align' => 'center',
                'margin' => 'sm',
            ],
        ];

        if ($billRef) {
            $headerContents[] = [
                'type' => 'text',
                'text' => "🔖 บิล: {$billRef}",
                'color' => '#E9D8FD',
                'size' => 'xs',
                'align' => 'center',
                'margin' => 'sm',
            ];
        }

        return [
            'type' => 'bubble',
            'styles' => [
                'header' => [
                    'backgroundColor' => '#6B46C1',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $headerContents,
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => array_merge(
                    [
                        [
                            'type' => 'text',
                            'text' => isset($bankAccounts[0]['is_promptpay']) && $bankAccounts[0]['is_promptpay']
                                ? '📱 ช่องทางชำระเงิน (พร้อมเพย์)'
                                : '💳 บัญชีรับโอน',
                            'weight' => 'bold',
                            'size' => 'md',
                        ],
                    ],
                    $bankContents,
                    [
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'text',
                            'text' => "⏰ หมดอายุ: {$expiresAt} น.",
                            'size' => 'sm',
                            'color' => '#E53E3E',
                            'margin' => 'lg',
                            'align' => 'center',
                        ],
                    ]
                ),
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'เมื่อโอนแล้วระบบจะตรวจสอบอัตโนมัติ',
                        'size' => 'xs',
                        'color' => '#888888',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'และส่งคำทำนายให้ทันทีค่ะ ✨',
                        'size' => 'xs',
                        'color' => '#888888',
                        'align' => 'center',
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับ Welcome / Help
     *
     * @return array Flex Message content
     */
    public function buildWelcomeFlexMessage(string $userName = ''): array
    {
        // สร้างข้อความทักทาย — ถ้ามีชื่อจะใส่ชื่อด้วย
        $greeting = $userName
            ? "สวัสดีค่ะ คุณ{$userName} ✨"
            : 'สวัสดีค่ะ ✨';

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => [
                    'backgroundColor' => '#6B46C1',
                ],
                'footer' => [
                    'backgroundColor' => '#F8F7FF',
                ],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔮',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => '🔮 จันทรายินดีต้อนรับค่ะ 🔮',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                    [
                        'type' => 'text',
                        'text' => $greeting,
                        'color' => '#FFFFFFCC',
                        'size' => 'sm',
                        'align' => 'center',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'จันทรารับดูดวงเรื่องต่างๆ',
                        'wrap' => true,
                        'size' => 'md',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '💕 ความรัก คู่ครอง', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '💼 การงาน อาชีพ', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '💰 การเงิน โชคลาภ', 'size' => 'sm'],
                            ['type' => 'text', 'text' => '🏥 สุขภาพ', 'size' => 'sm'],
                        ],
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    // บริการของเรา
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'spacing' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📋 บริการของเรา',
                                'weight' => 'bold',
                                'size' => 'sm',
                                'color' => '#6B46C1',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'backgroundColor' => '#F0FFF4',
                                'cornerRadius' => 'md',
                                'paddingAll' => 'md',
                                'contents' => [
                                    ['type' => 'text', 'text' => '🆓', 'size' => 'lg', 'flex' => 0],
                                    [
                                        'type' => 'box',
                                        'layout' => 'vertical',
                                        'flex' => 1,
                                        'paddingStart' => 'md',
                                        'contents' => [
                                            ['type' => 'text', 'text' => 'ดูดวงพื้นฐาน (ฟรี)', 'size' => 'sm', 'weight' => 'bold'],
                                            ['type' => 'text', 'text' => 'ทำนายเรื่องทั่วไป', 'size' => 'xs', 'color' => '#888888'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'backgroundColor' => '#F8F7FF',
                                'cornerRadius' => 'md',
                                'paddingAll' => 'md',
                                'contents' => [
                                    ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                                    [
                                        'type' => 'box',
                                        'layout' => 'vertical',
                                        'flex' => 1,
                                        'paddingStart' => 'md',
                                        'contents' => [
                                            ['type' => 'text', 'text' => 'ดูดวงละเอียด (49 บาท)', 'size' => 'sm', 'weight' => 'bold'],
                                            ['type' => 'text', 'text' => 'ถาม 3 คำถาม พร้อมวันเกิด', 'size' => 'xs', 'color' => '#888888'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    [
                        'type' => 'text',
                        'text' => '💡 พิมพ์คำถามมาได้เลยค่ะ เช่น',
                        'weight' => 'bold',
                        'size' => 'sm',
                        'margin' => 'xl',
                    ],
                    [
                        'type' => 'text',
                        'text' => '• ปีนี้จะมีคู่ครองไหม\n• ควรเปลี่ยนงานไหม\n• ดวงการเงินเป็นอย่างไร',
                        'wrap' => true,
                        'size' => 'sm',
                        'color' => '#666666',
                        'margin' => 'sm',
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'md',
                'paddingAll' => 'lg',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#6B46C1',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔮 เริ่มดูดวงเลย',
                            'text' => 'ดูดวง',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'message',
                            'label' => '📊 เช็คสิทธิ์',
                            'text' => 'เช็คสิทธิ์',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับคำทำนายละเอียด (Deep Reading) — แต่ละคำถาม
     *
     * ออกแบบเป็นการ์ดสวยๆ มี header สีตามหมวดคำถาม
     * มี icon, คำถาม, คำทำนาย แยกส่วนชัดเจน
     *
     * @param  int  $questionNum  ลำดับคำถาม (1, 2, 3)
     * @param  string  $question  คำถามของผู้ใช้
     * @param  string  $answer  คำทำนาย
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @return array Flex Message bubble
     */
    public function buildDeepReadingFlexMessage(int $questionNum, string $question, string $answer, int $totalQuestions = 3): array
    {
        // กำหนดสี + icon ตามหมวดคำถาม
        $category = $this->detectQuestionCategory($question);
        $theme = $this->getCategoryTheme($category);

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => $theme['color']],
                'footer' => ['backgroundColor' => '#F8F7FF'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    // แถวบน: icon + หมวด
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $theme['icon'],
                                'size' => 'xxl',
                                'flex' => 0,
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'flex' => 1,
                                'paddingStart' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "คำทำนายข้อที่ {$questionNum}/{$totalQuestions}",
                                        'color' => '#FFFFFF',
                                        'size' => 'md',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $theme['label'],
                                        'color' => '#FFFFFFCC',
                                        'size' => 'sm',
                                        'margin' => 'xs',
                                    ],
                                ],
                            ],
                        ],
                        'alignItems' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    // คำถาม
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'backgroundColor' => '#F3F0FF',
                        'cornerRadius' => 'lg',
                        'paddingAll' => 'lg',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '❓ คำถาม',
                                'size' => 'xs',
                                'color' => '#6B46C1',
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => $question,
                                'wrap' => true,
                                'size' => 'md',
                                'color' => '#4A3880',
                                'margin' => 'sm',
                                'weight' => 'bold',
                            ],
                        ],
                    ],
                    // เส้นแบ่ง
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    // คำทำนาย
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '🔮 คำทำนาย',
                                'size' => 'sm',
                                'color' => $theme['color'],
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => $answer,
                                'wrap' => true,
                                'size' => 'md',
                                'color' => '#333333',
                                'margin' => 'md',
                                'lineSpacing' => '8px',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✨ จันทราดูดวง',
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'flex' => 1,
                    ],
                    [
                        'type' => 'text',
                        'text' => "Q{$questionNum}/{$totalQuestions}",
                        'size' => 'xs',
                        'color' => '#9B8EC4',
                        'align' => 'end',
                        'flex' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับข้อความขอบคุณปิดท้ายคำทำนายละเอียด
     *
     * มีปุ่ม "ดูดวงอีกครั้ง" + "เช็คสิทธิ์" เพื่อกระตุ้น engagement
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @return array Flex Message bubble
     */
    public function buildThankYouFlexMessage(string $userName = 'คุณ'): array
    {
        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🙏',
                        'size' => '3xl',
                        'align' => 'center',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ขอบคุณที่ไว้วางใจค่ะ',
                        'color' => '#FFFFFF',
                        'size' => 'lg',
                        'weight' => 'bold',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "คุณ{$userName} ✨",
                        'size' => 'md',
                        'weight' => 'bold',
                        'color' => '#6B46C1',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'หวังว่าคำทำนายจะเป็นแนวทางที่ดีในชีวิตนะคะ ขอให้โชคดีและมีความสุขมากๆ ค่ะ',
                        'wrap' => true,
                        'size' => 'md',
                        'color' => '#555555',
                        'margin' => 'md',
                        'lineSpacing' => '6px',
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'xl',
                        'color' => '#E8E0FF',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'backgroundColor' => '#F8F7FF',
                        'cornerRadius' => 'lg',
                        'paddingAll' => 'lg',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '💡 พิมพ์ "ดูคำทำนาย" เพื่อดูอีกครั้ง',
                                'wrap' => true,
                                'size' => 'sm',
                                'color' => '#6B46C1',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'สามารถดูย้อนหลังได้ทุกเมื่อค่ะ',
                                'wrap' => true,
                                'size' => 'xs',
                                'color' => '#888888',
                                'margin' => 'sm',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'paddingAll' => 'lg',
                'contents' => [
                    // แถวที่ 1: ดูดวงอีกครั้ง + เช็คสิทธิ์
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'spacing' => 'md',
                        'contents' => [
                            [
                                'type' => 'button',
                                'style' => 'primary',
                                'color' => '#6B46C1',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'message',
                                    'label' => '🔮 ดูดวงอีกครั้ง',
                                    'text' => 'ดูดวง',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'style' => 'secondary',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'message',
                                    'label' => '📊 เช็คสิทธิ์',
                                    'text' => 'เช็คสิทธิ์',
                                ],
                            ],
                        ],
                    ],
                    // แถวที่ 2: ปุ่มแชร์
                    [
                        'type' => 'button',
                        'style' => 'secondary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📤 แชร์ให้เพื่อน',
                            'uri' => 'https://line.me/R/nv/recommendOA/'.($this->settings->line_bot_basic_id ?? config('services.line.bot_basic_id', '@002dqcls')),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message สำหรับเลือกหมวดคำถาม (ดูดวงละเอียด)
     *
     * แสดงปุ่มหมวดคำถามสวยๆ พร้อมไอคอนและสี
     * ผู้ใช้สามารถกดเลือกหมวดหรือพิมพ์เองได้
     *
     * @param  int  $questionNumber  คำถามข้อที่ (1, 2)
     * @param  int  $totalQuestions  จำนวนคำถามทั้งหมด
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string|null  $previousQuestion  คำถามก่อนหน้า (ถ้ามี)
     * @return array Flex Message bubble
     */
    public function buildQuestionSelectionFlexMessage(int $questionNumber, int $totalQuestions, string $userName = 'คุณ', ?string $previousQuestion = null): array
    {
        $bodyContents = [];

        // ถ้าเป็นข้อที่ 2+ → แสดงว่ารับคำถามก่อนหน้าแล้ว
        if ($previousQuestion) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'backgroundColor' => '#E8F5E9',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '✅',
                        'size' => 'md',
                        'flex' => 0,
                    ],
                    [
                        'type' => 'text',
                        'text' => 'รับคำถามข้อที่ '.($questionNumber - 1).' แล้วค่ะ',
                        'size' => 'sm',
                        'color' => '#2E7D32',
                        'flex' => 1,
                        'paddingStart' => 'sm',
                    ],
                ],
            ];
            $bodyContents[] = [
                'type' => 'separator',
                'margin' => 'lg',
                'color' => '#E8E0FF',
            ];
        }

        // ข้อความแนะนำ
        $bodyContents[] = [
            'type' => 'text',
            'text' => "เลือกหมวดที่สนใจ หรือพิมพ์คำถามเองได้เลยค่ะ 👇",
            'wrap' => true,
            'size' => 'sm',
            'color' => '#666666',
            'margin' => $previousQuestion ? 'lg' : 'none',
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 1: ความรัก + การงาน
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'lg',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('💕', 'ความรัก คู่ครอง', '#E91E8C', 'ดูดวงความรัก'),
                $this->buildCategoryButton('💼', 'การงาน อาชีพ', '#1976D2', 'ดูดวงการงาน'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 2: การเงิน + สุขภาพ
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('💰', 'การเงิน โชคลาภ', '#E8890C', 'ดูดวงการเงิน'),
                $this->buildCategoryButton('🏥', 'สุขภาพ', '#43A047', 'ดูดวงสุขภาพ'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 3: ดวงรวม + การเรียน
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('🔮', 'ดวงชะตารวม', '#6B46C1', 'ดูดวงรวม'),
                $this->buildCategoryButton('📚', 'การเรียน สอบ', '#5D4037', 'ดูดวงการเรียน'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 4: ครอบครัว + ธุรกิจ
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('👨‍👩‍👧‍👦', 'ครอบครัว', '#00897B', 'ดูดวงครอบครัว'),
                $this->buildCategoryButton('🏢', 'ธุรกิจ ลงทุน', '#FF6F00', 'ดูดวงธุรกิจ'),
            ],
        ];

        // ปุ่มหมวดคำถาม — แถวที่ 5: การเดินทาง + ฮวงจุ้ย/โชค
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'md',
            'spacing' => 'md',
            'contents' => [
                $this->buildCategoryButton('✈️', 'การเดินทาง', '#0288D1', 'ดูดวงการเดินทาง'),
                $this->buildCategoryButton('🍀', 'โชคลาภ เลขเด็ด', '#2E7D32', 'ดูดวงโชคลาภ'),
            ],
        ];

        // คำแนะนำเพิ่มเติม
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'vertical',
            'margin' => 'xl',
            'backgroundColor' => '#F8F7FF',
            'cornerRadius' => 'lg',
            'paddingAll' => 'md',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => '💡 หรือพิมพ์คำถามเองได้ เช่น',
                    'size' => 'xs',
                    'color' => '#6B46C1',
                ],
                [
                    'type' => 'text',
                    'text' => '"ปีนี้จะมีแฟนไหม" "ควรเปลี่ยนงานดีไหม" "เปิดร้านอาหารดีไหม"',
                    'size' => 'xs',
                    'color' => '#999999',
                    'wrap' => true,
                    'margin' => 'sm',
                ],
            ],
        ];

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => [
                'header' => ['backgroundColor' => '#6B46C1'],
            ],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'lg',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📝',
                                'size' => 'xxl',
                                'flex' => 0,
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'flex' => 1,
                                'paddingStart' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "คำถามข้อที่ {$questionNumber} จาก {$totalQuestions}",
                                        'color' => '#FFFFFF',
                                        'size' => 'lg',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => "คุณ{$userName} อยากถามเรื่องอะไรคะ?",
                                        'color' => '#FFFFFFCC',
                                        'size' => 'sm',
                                        'margin' => 'xs',
                                    ],
                                ],
                            ],
                        ],
                        'alignItems' => 'center',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'paddingAll' => 'xl',
                'contents' => $bodyContents,
            ],
        ];
    }

    /**
     * สร้างปุ่มหมวดคำถาม (ใช้ภายใน buildQuestionSelectionFlexMessage)
     *
     * @param  string  $icon  Emoji ไอคอน
     * @param  string  $label  ชื่อหมวด
     * @param  string  $color  สี hex
     * @param  string  $text  ข้อความที่จะส่งเมื่อกด
     * @return array Flex Message button component
     */
    protected function buildCategoryButton(string $icon, string $label, string $color, string $text): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'flex' => 1,
            'backgroundColor' => $color.'15',  // สีจาง 15% opacity
            'cornerRadius' => 'xl',
            'paddingAll' => 'md',
            'action' => [
                'type' => 'message',
                'label' => "{$icon} {$label}",
                'text' => $text,
            ],
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $icon,
                    'size' => 'xl',
                    'align' => 'center',
                ],
                [
                    'type' => 'text',
                    'text' => $label,
                    'size' => 'xs',
                    'align' => 'center',
                    'color' => $color,
                    'weight' => 'bold',
                    'margin' => 'sm',
                ],
            ],
        ];
    }

    /**
     * ตรวจจับหมวดคำถาม จาก keyword ในคำถาม
     *
     * @param  string  $question  คำถามของผู้ใช้
     * @return string หมวดคำถาม (love, work, money, health, general)
     */
    protected function detectQuestionCategory(string $question): string
    {
        $question = mb_strtolower($question);

        $categories = [
            'love' => ['รัก', 'คู่', 'แฟน', 'สามี', 'ภรรยา', 'แต่งงาน', 'หย่า', 'ความสัมพันธ์', 'คนรัก', 'เนื้อคู่', 'คู่ครอง', 'จีบ'],
            'work' => ['งาน', 'อาชีพ', 'เปลี่ยนงาน', 'เลื่อนตำแหน่ง', 'ธุรกิจ', 'ค้าขาย', 'หุ้นส่วน', 'ลาออก', 'สัมภาษณ์', 'เจ้านาย'],
            'money' => ['เงิน', 'การเงิน', 'โชค', 'ลาภ', 'หวย', 'หนี้', 'ร่ำรวย', 'รายได้', 'ลงทุน', 'กำไร', 'เศรษฐี', 'ทรัพย์'],
            'health' => ['สุขภาพ', 'ป่วย', 'โรค', 'หมอ', 'ผ่าตัด', 'อุบัติเหตุ', 'ร่างกาย', 'แข็งแรง'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($question, $kw) !== false) {
                    return $cat;
                }
            }
        }

        return 'general';
    }

    /**
     * ดึง theme สี + icon ตามหมวดคำถาม
     *
     * @param  string  $category  หมวดคำถาม
     * @return array ['color', 'icon', 'label']
     */
    protected function getCategoryTheme(string $category): array
    {
        return match ($category) {
            'love' => [
                'color' => '#E91E8C',
                'icon' => '💕',
                'label' => 'ความรัก คู่ครอง',
            ],
            'work' => [
                'color' => '#1976D2',
                'icon' => '💼',
                'label' => 'การงาน อาชีพ',
            ],
            'money' => [
                'color' => '#E8890C',
                'icon' => '💰',
                'label' => 'การเงิน โชคลาภ',
            ],
            'health' => [
                'color' => '#43A047',
                'icon' => '🏥',
                'label' => 'สุขภาพ',
            ],
            default => [
                'color' => '#6B46C1',
                'icon' => '🔮',
                'label' => 'คำทำนาย',
            ],
        };
    }

    // ============================================================
    // 🆕 Flex Message Templates — ข้อความสวยงามทุกจุด
    // ============================================================

    /**
     * สร้าง Flex Message ยืนยันดูดวง (awaiting_confirmation)
     *
     * แสดงสิทธิ์ฟรีที่เหลือ + ราคาดูดวงละเอียด + ปุ่มเลือก
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  จำนวนสิทธิ์ฟรีที่เหลือ
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @param  bool  $isUnlimited  มีสิทธิ์ไม่จำกัดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildConfirmationFlexMessage(string $userName, int $remaining, float $deepReadingPrice, bool $deepReadingEnabled = true, bool $isUnlimited = false): array
    {
        // แสดงสิทธิ์ฟรี
        $creditText = $isUnlimited || $remaining >= 99
            ? '✨ ไม่จำกัด ✨'
            : ($remaining > 0 ? "{$remaining} ครั้ง" : 'หมดแล้ว');
        $creditColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';

        $bodyContents = [
            // สวัสดี
            [
                'type' => 'text',
                'text' => "สวัสดีค่ะ คุณ{$userName} ✨",
                'size' => 'md',
                'weight' => 'bold',
                'color' => '#333333',
            ],
            [
                'type' => 'text',
                'text' => 'จันทราพร้อมดูดวงให้ค่ะ',
                'size' => 'sm',
                'color' => '#999999',
                'margin' => 'sm',
            ],
            // สิทธิ์ฟรีวันนี้
            [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'xl',
                'backgroundColor' => $remaining > 0 || $isUnlimited ? '#E8F5E9' : '#FFEBEE',
                'cornerRadius' => 'lg',
                'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '📊 สิทธิ์ฟรีวันนี้:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                    ['type' => 'text', 'text' => $creditText, 'size' => 'sm', 'weight' => 'bold', 'color' => $creditColor, 'flex' => 2, 'align' => 'end'],
                ],
            ],
            // เส้นแบ่ง
            ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'],
            // บริการ
            [
                'type' => 'text',
                'text' => '🎁 บริการของจันทรา',
                'size' => 'md',
                'weight' => 'bold',
                'color' => '#6B46C1',
                'margin' => 'xl',
            ],
        ];

        // บริการฟรี
        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'margin' => 'lg',
            'contents' => [
                ['type' => 'text', 'text' => '🆓', 'size' => 'lg', 'flex' => 0],
                [
                    'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                    'contents' => [
                        ['type' => 'text', 'text' => 'ดูดวงพื้นฐาน (ฟรี)', 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333'],
                        ['type' => 'text', 'text' => 'ทำนายเรื่องทั่วไปแบบสั้นๆ', 'size' => 'xs', 'color' => '#999999'],
                    ],
                ],
            ],
        ];

        // บริการเสียเงิน (ถ้าเปิด)
        if ($deepReadingEnabled) {
            $priceDisplay = number_format($deepReadingPrice, 0);
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "ดูดวงละเอียด ({$priceDisplay} บาท)", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => 'ถาม 2 คำถาม + วิเคราะห์จากวันเกิด', 'size' => 'xs', 'color' => '#999999'],
                        ],
                    ],
                ],
            ];
        }

        // ปุ่ม footer
        $footerContents = [];
        if ($remaining > 0 || $isUnlimited) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🔮 ดูดวงเลย', 'text' => 'ดู'],
            ];
        }
        if ($deepReadingEnabled) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '💎 ดูดวงละเอียด', 'text' => 'ดูดวงละเอียด'],
            ];
        }
        if (empty($footerContents)) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง'],
            ];
        }

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'แม่หมอจันทราดูดวง', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'ยินดีต้อนรับค่ะ ✨', 'color' => '#FFFFFFCC', 'size' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message ขอวันเกิด (collecting_birthdate)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @return array Flex Message bubble
     */
    public function buildBirthdateRequestFlexMessage(float $deepReadingPrice): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);

        return [
            'type' => 'bubble',
            'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#7C3AED']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🎂', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวงละเอียด', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "เริ่มต้น {$priceDisplay} บาท • ถาม 2 คำถาม", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'กรุณาบอกวันเดือนปีเกิดค่ะ', 'size' => 'md', 'weight' => 'bold', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'เพื่อวิเคราะห์ดวงชะตาได้แม่นยำ ✨', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm'],
                    ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'],
                    // รูปแบบที่รับ
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#F8F7FF', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 รูปแบบที่รับ', 'size' => 'sm', 'weight' => 'bold', 'color' => '#6B46C1'],
                            ['type' => 'text', 'text' => '• 15/08/1990', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                            ['type' => 'text', 'text' => '• 15/08/2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                            ['type' => 'text', 'text' => '• 15 สิงหาคม 2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                        ],
                    ],
                    // ราคา + สิ่งที่ได้
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "💎 สิ่งที่คุณจะได้รับ ({$priceDisplay} บาท)", 'size' => 'xs', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => '✅ ถามได้ 2 คำถาม วิเคราะห์เจาะลึก', 'size' => 'xs', 'color' => '#666666', 'margin' => 'sm', 'wrap' => true],
                            ['type' => 'text', 'text' => '✅ สีมงคล เลขมงคล ฤกษ์ดี', 'size' => 'xs', 'color' => '#666666', 'margin' => 'sm'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message วันเกิดผิดรูปแบบ (invalid_birthdate)
     *
     * @return array Flex Message bubble
     */
    public function buildInvalidBirthdateFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'รูปแบบวันเกิดไม่ถูกต้อง', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'กรุณาพิมพ์วันเกิดใหม่ค่ะ', 'size' => 'md', 'color' => '#333333'],
                    ['type' => 'separator', 'margin' => 'lg', 'color' => '#FFCDD2'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF3E0', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '📅 ตัวอย่างที่ถูกต้อง', 'size' => 'sm', 'weight' => 'bold', 'color' => '#E65100'],
                            ['type' => 'text', 'text' => '• 15/08/1990', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                            ['type' => 'text', 'text' => '• 15/08/2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                            ['type' => 'text', 'text' => '• 15 สิงหาคม 2533', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message หมดสิทธิ์ฟรี (ai_limit)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildAiLimitFlexMessage(float $deepReadingPrice, bool $deepReadingEnabled = true): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);

        $bodyContents = [
            [
                'type' => 'box', 'layout' => 'horizontal', 'backgroundColor' => '#FFEBEE', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '⏰', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'สิทธิ์ฟรีวันนี้หมดแล้วค่ะ', 'size' => 'sm', 'weight' => 'bold', 'color' => '#C62828'],
                            ['type' => 'text', 'text' => 'ฟรีวันละ 1 คำถาม', 'size' => 'xs', 'color' => '#999999'],
                        ],
                    ],
                ],
            ],
        ];

        if ($deepReadingEnabled) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            $bodyContents[] = [
                'type' => 'text', 'text' => '💎 แนะนำ: ดูดวงละเอียด', 'size' => 'md', 'weight' => 'bold', 'color' => '#E8890C', 'margin' => 'xl',
            ];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => "เริ่มต้นเพียง {$priceDisplay} บาท", 'size' => 'lg', 'weight' => 'bold', 'color' => '#E8890C', 'align' => 'center'],
                    ['type' => 'separator', 'margin' => 'md', 'color' => '#FFE082'],
                    ['type' => 'text', 'text' => '📌 ถามได้ 2 คำถาม', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                    ['type' => 'text', 'text' => '📌 วิเคราะห์จากวันเกิดเจาะลึก', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                    ['type' => 'text', 'text' => '📌 สีมงคล เลขมงคล ฤกษ์ดี', 'size' => 'sm', 'color' => '#555555', 'margin' => 'sm'],
                ],
            ];
        }

        $footerContents = [];
        if ($deepReadingEnabled) {
            $footerContents[] = [
                'type' => 'button', 'style' => 'primary', 'color' => '#E8890C', 'height' => 'sm',
                'action' => ['type' => 'message', 'label' => "💎 ดูดวงละเอียด {$priceDisplay} บาท", 'text' => 'ดูดวงละเอียด'],
            ];
        }
        $footerContents[] = [
            'type' => 'button', 'style' => 'secondary', 'height' => 'sm',
            'action' => ['type' => 'message', 'label' => '📊 เช็คสิทธิ์', 'text' => 'เช็คสิทธิ์'],
        ];

        return [
            'type' => 'bubble', 'size' => 'mega',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'แม่หมอจันทราดูดวง', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => 'ยินดีต้อนรับค่ะ ✨', 'color' => '#FFFFFFCC', 'size' => 'sm'],
                        ],
                    ],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message เช็คสิทธิ์ (check_remaining)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  int  $remaining  สิทธิ์ฟรีที่เหลือ
     * @param  int  $used  ใช้ไปแล้ว
     * @param  int  $total  สิทธิ์ทั้งหมด
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @param  bool  $deepReadingEnabled  เปิดดูดวงละเอียดหรือไม่
     * @param  bool  $isUnlimited  มีสิทธิ์ไม่จำกัดหรือไม่
     * @return array Flex Message bubble
     */
    public function buildCheckRemainingFlexMessage(string $userName, int $remaining, int $used, int $total, float $deepReadingPrice, bool $deepReadingEnabled = true, bool $isUnlimited = false): array
    {
        $creditText = $isUnlimited || $remaining >= 99 ? '✨ ไม่จำกัด ✨' : "{$remaining} ครั้ง";
        $statusColor = $remaining > 0 || $isUnlimited ? '#43A047' : '#E53935';
        $priceDisplay = number_format($deepReadingPrice, 0);

        $bodyContents = [
            ['type' => 'text', 'text' => "คุณ{$userName}", 'size' => 'md', 'weight' => 'bold', 'color' => '#333333'],
            // สิทธิ์คงเหลือ
            [
                'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#F8F7FF', 'cornerRadius' => 'lg', 'paddingAll' => 'lg',
                'contents' => [
                    [
                        'type' => 'box', 'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => '📊 ใช้ไปแล้ว:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => $isUnlimited ? '-' : "{$used}/{$total} ครั้ง", 'size' => 'sm', 'weight' => 'bold', 'color' => '#333333', 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '🆓 สิทธิ์ฟรีคงเหลือ:', 'size' => 'sm', 'flex' => 3, 'color' => '#555555'],
                            ['type' => 'text', 'text' => $creditText, 'size' => 'sm', 'weight' => 'bold', 'color' => $statusColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                ],
            ],
        ];

        // แนะนำดูดวงละเอียด (ถ้าเปิด)
        if ($deepReadingEnabled) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'xl', 'color' => '#E8E0FF'];
            $bodyContents[] = [
                'type' => 'box', 'layout' => 'horizontal', 'margin' => 'xl', 'backgroundColor' => '#FFF8E1', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                'contents' => [
                    ['type' => 'text', 'text' => '💎', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => "ดูดวงละเอียด {$priceDisplay} บาท", 'size' => 'sm', 'weight' => 'bold', 'color' => '#E8890C'],
                            ['type' => 'text', 'text' => 'ถาม 2 คำถาม วิเคราะห์จากวันเกิด', 'size' => 'xs', 'color' => '#999999', 'wrap' => true],
                        ],
                    ],
                ],
            ];
        }

        $footerContents = [
            ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวง', 'text' => 'ดูดวง']],
        ];
        if ($deepReadingEnabled) {
            $footerContents[] = ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => "💎 ดูดวงละเอียด {$priceDisplay}.-", 'text' => 'ดูดวงละเอียด']];
        }

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#1976D2']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '📊', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'สิทธิ์การใช้งาน', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => ['type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl', 'contents' => $bodyContents],
            'footer' => ['type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg', 'contents' => $footerContents],
        ];
    }

    /**
     * สร้าง Flex Message ปฏิเสธ/ยกเลิก (declined, cancelled)
     *
     * @param  string  $userName  ชื่อผู้ใช้
     * @param  string  $type  'declined' หรือ 'cancelled'
     * @return array Flex Message bubble
     */
    public function buildDeclinedFlexMessage(string $userName, string $type = 'declined'): array
    {
        $title = $type === 'cancelled' ? 'ยกเลิกแล้วค่ะ' : 'ไม่เป็นไรค่ะ';
        $subtitle = "คุณ{$userName} สามารถกลับมาดูดวงได้ทุกเมื่อนะคะ ✨";
        $headerColor = $type === 'cancelled' ? '#78909C' : '#8E24AA';

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => $headerColor]],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => $type === 'cancelled' ? '✅' : '🙏', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'text', 'text' => $title, 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => $subtitle, 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                    ['type' => 'text', 'text' => 'ขอให้โชคดีค่ะ 🌟', 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg'],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'horizontal', 'spacing' => 'md', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงใหม่', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'uri', 'label' => '📤 แชร์ให้เพื่อน', 'uri' => 'https://line.me/R/nv/recommendOA/'.($this->settings->line_bot_basic_id ?? config('services.line.bot_basic_id', '@002dqcls'))]],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message บิลหมดอายุ (payment_expired)
     *
     * @param  float  $deepReadingPrice  ราคาดูดวงละเอียด
     * @return array Flex Message bubble
     */
    public function buildPaymentExpiredFlexMessage(float $deepReadingPrice): array
    {
        $priceDisplay = number_format($deepReadingPrice, 0);

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⏰', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'บิลหมดอายุแล้ว', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'ระยะเวลาชำระเงินหมดแล้วค่ะ', 'size' => 'sm', 'color' => '#555555'],
                    ['type' => 'text', 'text' => 'สามารถเริ่มดูดวงละเอียดใหม่ได้เลยค่ะ', 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#E8890C', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => "💎 ดูดวงละเอียด {$priceDisplay} บาท", 'text' => 'ดูดวงละเอียด']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงฟรี', 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message รอชำระเงิน (waiting_payment)
     *
     * @param  float  $amount  ยอดชำระ
     * @param  string  $billRef  เลขที่บิล
     * @param  string  $expiresAt  เวลาหมดอายุ
     * @param  int  $remainingMinutes  เวลาที่เหลือ (นาที)
     * @return array Flex Message bubble
     */
    public function buildWaitingPaymentFlexMessage(float $amount, string $billRef, string $expiresAt, int $remainingMinutes): array
    {
        $amountDisplay = number_format($amount, 2);
        $urgentColor = $remainingMinutes <= 10 ? '#E53935' : '#E8890C';

        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E8890C']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '💰', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'รอชำระเงิน', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "บิล: {$billRef}", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    // ยอดชำระ
                    ['type' => 'text', 'text' => "฿{$amountDisplay}", 'size' => 'xxl', 'weight' => 'bold', 'color' => '#E8890C', 'align' => 'center'],
                    ['type' => 'text', 'text' => '⚠️ โอนตรงตามทศนิยม', 'size' => 'xs', 'color' => '#E53935', 'align' => 'center', 'margin' => 'sm'],
                    ['type' => 'separator', 'margin' => 'xl', 'color' => '#FFF3E0'],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'lg',
                        'contents' => [
                            ['type' => 'text', 'text' => '⏰ โอนก่อน:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                            ['type' => 'text', 'text' => "{$expiresAt} น.", 'size' => 'sm', 'weight' => 'bold', 'color' => $urgentColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    [
                        'type' => 'box', 'layout' => 'horizontal', 'margin' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '⏳ เหลือเวลา:', 'size' => 'sm', 'color' => '#555555', 'flex' => 2],
                            ['type' => 'text', 'text' => "{$remainingMinutes} นาที", 'size' => 'sm', 'weight' => 'bold', 'color' => $urgentColor, 'flex' => 2, 'align' => 'end'],
                        ],
                    ],
                    // ข้อความเตือน
                    ['type' => 'text', 'text' => 'เมื่อโอนแล้วรอสักครู่ ระบบจะส่งคำทำนายให้ทันทีค่ะ ✨', 'size' => 'xs', 'color' => '#999999', 'margin' => 'xl', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🏦 ดูบัญชีธนาคาร', 'text' => 'บัญชี']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '❌ ยกเลิก', 'text' => 'ยกเลิก']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message กำลังประมวลผล (view_reading_processing)
     *
     * @param  string  $billRef  เลขที่บิล
     * @return array Flex Message bubble
     */
    public function buildProcessingFlexMessage(string $billRef): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#1976D2']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⏳', 'size' => 'xxl', 'flex' => 0],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'flex' => 1, 'paddingStart' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'กำลังสร้างคำทำนาย', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold'],
                            ['type' => 'text', 'text' => "บิล: {$billRef}", 'color' => '#FFFFFFCC', 'size' => 'xs'],
                        ],
                    ],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'AI กำลังวิเคราะห์ดวงชะตาให้ค่ะ', 'size' => 'sm', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'ใช้เวลาประมาณ 1-2 นาที', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#E3F2FD', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💡 พิมพ์ "ดูผล" เพื่อเช็คสถานะ', 'size' => 'xs', 'color' => '#1565C0'],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#1976D2', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะ', 'text' => 'ดูผล']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ไม่มีคำทำนาย (view_reading_empty)
     *
     * @return array Flex Message bubble
     */
    public function buildNoReadingFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#6B46C1']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔮', 'size' => 'xxl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'ยังไม่มีคำทำนาย', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'เริ่มดูดวงกันเลยค่ะ!', 'size' => 'md', 'color' => '#333333'],
                    ['type' => 'text', 'text' => 'พิมพ์คำถามหรือกดปุ่มด้านล่างได้เลยนะคะ ✨', 'size' => 'sm', 'color' => '#999999', 'margin' => 'sm', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงเลย', 'text' => 'ดูดวง']],
                    ['type' => 'button', 'style' => 'secondary', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '💎 ดูดวงละเอียด', 'text' => 'ดูดวงละเอียด']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ไว้ดูทีหลัง (view_later)
     *
     * @return array Flex Message bubble
     */
    public function buildViewLaterFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#43A047']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '✅', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'บันทึกแล้วค่ะ', 'color' => '#FFFFFF', 'size' => 'lg', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'คำทำนายถูกบันทึกไว้แล้วค่ะ', 'size' => 'sm', 'color' => '#333333'],
                    [
                        'type' => 'box', 'layout' => 'vertical', 'margin' => 'xl', 'backgroundColor' => '#E8F5E9', 'cornerRadius' => 'lg', 'paddingAll' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '💡 พิมพ์ "ดูคำทำนาย" เพื่อดูได้ทุกเมื่อ', 'size' => 'xs', 'color' => '#2E7D32', 'wrap' => true],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูคำทำนาย', 'text' => 'ดูคำทำนาย']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ข้อผิดพลาด (error)
     *
     * @param  string  $message  ข้อความ error (optional)
     * @return array Flex Message bubble
     */
    public function buildErrorFlexMessage(string $message = ''): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#E53935']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'เกิดข้อผิดพลาด', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'ขอโทษค่ะ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง 🙏', 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ลองอีกครั้ง', 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex Message ดูดวงละเอียดถูกปิด (deep_reading_disabled)
     *
     * @return array Flex Message bubble
     */
    public function buildDeepReadingDisabledFlexMessage(): array
    {
        return [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => '#78909C']],
            'header' => [
                'type' => 'box', 'layout' => 'horizontal', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'text', 'text' => '🔒', 'size' => 'xl', 'flex' => 0],
                    ['type' => 'text', 'text' => 'ปิดให้บริการชั่วคราว', 'color' => '#FFFFFF', 'size' => 'md', 'weight' => 'bold', 'flex' => 1, 'paddingStart' => 'md'],
                ],
            ],
            'body' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'xl',
                'contents' => [
                    ['type' => 'text', 'text' => 'บริการดูดวงละเอียดปิดให้บริการชั่วคราวค่ะ', 'size' => 'sm', 'color' => '#555555', 'wrap' => true],
                    ['type' => 'text', 'text' => 'ยังสามารถดูดวงฟรีได้ตามปกตินะคะ ✨', 'size' => 'sm', 'color' => '#999999', 'margin' => 'lg', 'wrap' => true],
                ],
            ],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'paddingAll' => 'lg',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => '#6B46C1', 'height' => 'sm', 'action' => ['type' => 'message', 'label' => '🔮 ดูดวงฟรี', 'text' => 'ดูดวง']],
                ],
            ],
        ];
    }

    // ============================================================
    // Private Methods
    // ============================================================

    /**
     * ส่งข้อความผ่าน LINE Messaging API (Push)
     *
     * @param  string  $to  LINE User ID
     * @param  array  $messages  รายการข้อความ
     */
    protected function pushMessage(string $to, array $messages): bool
    {
        try {
            // ⚡ ลด timeout push: 5s + retry 1 ครั้ง (จาก 15s + 2 retries)
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(5)
                ->connectTimeout(3)
                ->retry(1, 500)
                ->post(self::API_ENDPOINT.'/message/push', [
                    'to' => $to,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('LINE Push Message Error', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('LINE Push Message Exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ตอบกลับข้อความ (Reply) — เร็วกว่า pushMessage มาก!
     *
     * replyMessage ใช้ replyToken จาก webhook → ตอบกลับทันที ไม่ต้องสร้าง connection ใหม่
     * ⚡ เร็วกว่า pushMessage 2-3 เท่า + ฟรี (ไม่นับ quota)
     *
     * @param  string  $replyToken  Reply token จาก webhook (ใช้ได้ภายใน 1 นาที)
     * @param  array  $messages  รายการข้อความ (สูงสุด 5 ข้อความ)
     */
    public function replyMessage(string $replyToken, array $messages): bool
    {
        try {
            // ⚡ ลด timeout reply: 5s (จาก 10s)
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(5)
                ->connectTimeout(3)
                ->post(self::API_ENDPOINT.'/message/reply', [
                    'replyToken' => $replyToken,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('LINE Reply Message Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('LINE Reply Message Exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ตอบกลับด้วย Flex Message ผ่าน replyToken (เร็วที่สุด!)
     *
     * @param  string  $replyToken  Reply token จาก webhook
     * @param  array  $flexContent  Flex Message content
     * @param  string  $altText  ข้อความ fallback สำหรับ notification
     */
    public function replyWithFlex(string $replyToken, array $flexContent, string $altText = 'ข้อความจากระบบดูดวง'): bool
    {
        return $this->replyMessage($replyToken, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $flexContent,
            ],
        ]);
    }

    /**
     * ส่ง Flex Message ด้วย replyToken ก่อน ถ้าไม่ได้ → fallback เป็น pushMessage
     *
     * @param  string  $recipientId  LINE User ID
     * @param  array  $flexContent  Flex Message content
     * @param  string  $altText  ข้อความ fallback
     * @param  string|null  $replyToken  Reply token (ถ้ามี)
     */
    public function sendFlexWithReplyFallback(string $recipientId, array $flexContent, string $altText, ?string $replyToken = null): bool
    {
        // ลอง replyMessage ก่อน (เร็วกว่า + ฟรี)
        if ($replyToken) {
            $result = $this->replyWithFlex($replyToken, $flexContent, $altText);
            if ($result) {
                return true;
            }
            Log::warning('LINE: replyMessage ล้มเหลว fallback เป็น pushMessage', [
                'recipient_id' => $recipientId,
            ]);
        }

        // Fallback: ใช้ pushMessage
        return $this->sendRichMessage($recipientId, [
            'alt_text' => $altText,
            'contents' => $flexContent,
        ]);
    }

    /**
     * ส่งข้อความ text ด้วย replyToken ก่อน ถ้าไม่ได้ → fallback เป็น pushMessage
     *
     * @param  string  $recipientId  LINE User ID
     * @param  string  $message  ข้อความ
     * @param  string|null  $replyToken  Reply token (ถ้ามี)
     */
    public function sendMessageWithReplyFallback(string $recipientId, string $message, ?string $replyToken = null): bool
    {
        // ลอง replyMessage ก่อน
        if ($replyToken) {
            $result = $this->replyMessage($replyToken, [
                ['type' => 'text', 'text' => $message],
            ]);
            if ($result) {
                return true;
            }
        }

        // Fallback: pushMessage
        return $this->sendMessage($recipientId, $message);
    }

    /**
     * สร้าง Quick Reply Items สำหรับ LINE
     */
    protected function buildQuickReplyItems(array $quickReplies): array
    {
        $items = [];

        foreach ($quickReplies as $reply) {
            $items[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'] ?? $reply['title'] ?? $reply,
                    'text' => $reply['text'] ?? $reply['payload'] ?? $reply,
                ],
            ];
        }

        return $items;
    }

    /**
     * ตรวจสอบ Signature จาก LINE Webhook
     *
     * @param  string  $body  Request body
     * @param  string  $signature  X-Line-Signature header
     */
    public function verifySignature(string $body, string $signature): bool
    {
        $hash = base64_encode(hash_hmac('sha256', $body, $this->channelSecret, true));

        return hash_equals($hash, $signature);
    }

    /**
     * ทดสอบการเชื่อมต่อ LINE API
     *
     * @return array ผลการทดสอบ ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function testConnection(): array
    {
        try {
            // ตรวจสอบว่ามี token หรือไม่
            if (empty($this->channelAccessToken)) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ Channel Access Token กรุณาตั้งค่าก่อน',
                ];
            }

            // เรียก API เพื่อดึงข้อมูล Bot
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(2, 1000)
                ->get(self::API_ENDPOINT.'/info');

            if ($response->successful()) {
                $botInfo = $response->json();

                return [
                    'success' => true,
                    'message' => 'เชื่อมต่อสำเร็จ! Bot: '.($botInfo['displayName'] ?? 'Unknown'),
                    'data' => [
                        'bot_info' => $botInfo,
                    ],
                ];
            }

            // กรณี API Error
            $error = $response->json();
            $errorMessage = $error['message'] ?? 'Unknown error';

            return [
                'success' => false,
                'message' => "เชื่อมต่อไม่สำเร็จ: {$errorMessage}",
                'data' => [
                    'status' => $response->status(),
                    'error' => $error,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('LINE Test Connection Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ];
        }
    }
}
