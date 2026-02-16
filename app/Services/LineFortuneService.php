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
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(30)
                ->connectTimeout(15)
                ->retry(2, 2000)
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
            $bankContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'paddingAll' => 'md',
                'backgroundColor' => '#F7FAFC',
                'cornerRadius' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "🏦 {$account['bank_name']}",
                        'weight' => 'bold',
                        'size' => 'sm',
                    ],
                    [
                        'type' => 'text',
                        'text' => "เลขบัญชี: {$account['account_number']}",
                        'size' => 'sm',
                        'margin' => 'sm',
                    ],
                    [
                        'type' => 'text',
                        'text' => "ชื่อ: {$account['account_name']}",
                        'size' => 'sm',
                    ],
                ],
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
                            'text' => '💳 บัญชีรับโอน',
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
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(30)
                ->connectTimeout(15)
                ->retry(2, 2000)
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
     * ตอบกลับข้อความ (Reply)
     *
     * @param  string  $replyToken  Reply token จาก webhook
     * @param  array  $messages  รายการข้อความ
     */
    public function replyMessage(string $replyToken, array $messages): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(30)
                ->connectTimeout(15)
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
                ->timeout(30)
                ->connectTimeout(15)
                ->retry(2, 2000)
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
