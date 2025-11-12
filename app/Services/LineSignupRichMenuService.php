<?php

namespace App\Services;

use App\Models\LineOaSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineSignupRichMenuService
{
    protected $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    /**
     * Create signup-focused Rich Menu
     */
    public function createSignupRichMenu(): ?string
    {
        $richMenuData = [
            'size' => [
                'width' => 2500,
                'height' => 1686,
            ],
            'selected' => true,
            'name' => 'สมัครสมาชิก & เมนูหลัก',
            'chatBarText' => 'เมนู',
            'areas' => [
                // Row 1 - Left: สมัครสมาชิก (ใหญ่)
                [
                    'bounds' => [
                        'x' => 0,
                        'y' => 0,
                        'width' => 1250,
                        'height' => 843,
                    ],
                    'action' => [
                        'type' => 'postback',
                        'data' => 'action=start_signup',
                        'displayText' => 'สมัครสมาชิก',
                    ],
                ],
                // Row 1 - Right: เส้นทางเศรษฐี
                [
                    'bounds' => [
                        'x' => 1250,
                        'y' => 0,
                        'width' => 1250,
                        'height' => 843,
                    ],
                    'action' => [
                        'type' => 'postback',
                        'data' => 'action=wealth_path',
                        'displayText' => 'เส้นทางเศรษฐี',
                    ],
                ],
                // Row 2 - Col 1: วิธีการทำงาน
                [
                    'bounds' => [
                        'x' => 0,
                        'y' => 843,
                        'width' => 833,
                        'height' => 843,
                    ],
                    'action' => [
                        'type' => 'postback',
                        'data' => 'action=how_it_works',
                        'displayText' => 'วิธีการทำงาน',
                    ],
                ],
                // Row 2 - Col 2: เรื่องสำเร็จ
                [
                    'bounds' => [
                        'x' => 833,
                        'y' => 843,
                        'width' => 834,
                        'height' => 843,
                    ],
                    'action' => [
                        'type' => 'postback',
                        'data' => 'action=success_stories',
                        'displayText' => 'เรื่องสำเร็จ',
                    ],
                ],
                // Row 2 - Col 3: ติดต่อเรา
                [
                    'bounds' => [
                        'x' => 1667,
                        'y' => 843,
                        'width' => 833,
                        'height' => 843,
                    ],
                    'action' => [
                        'type' => 'message',
                        'text' => 'ติดต่อทีมงาน',
                    ],
                ],
            ],
        ];

        try {
            $response = $this->lineService->createRichMenu($richMenuData);

            if (isset($response['richMenuId'])) {
                return $response['richMenuId'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to create Rich Menu', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Rich Menu image (you'll need to create this PNG file)
     */
    public function getRichMenuImagePath(): string
    {
        // This should point to your Rich Menu image (2500x1686 px)
        return storage_path('app/public/line/rich-menu-signup.png');
    }

    /**
     * Set Rich Menu as default
     */
    public function setDefaultRichMenu(string $richMenuId): bool
    {
        try {
            return $this->lineService->setDefaultRichMenu($richMenuId);
        } catch (\Exception $e) {
            Log::error('Failed to set default Rich Menu', [
                'richMenuId' => $richMenuId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate Quick Reply buttons for signup flow
     */
    public function getSignupQuickReply(string $step): array
    {
        $quickReplies = [
            'welcome' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🚀 เริ่มสมัครเลย!',
                            'data' => 'action=start_signup',
                            'displayText' => 'เริ่มสมัครสมาชิก',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '📖 อ่านเพิ่มเติม',
                            'data' => 'action=learn_more',
                            'displayText' => 'อยากรู้เพิ่มเติม',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '💰 ดูรายได้ที่เป็นไปได้',
                            'data' => 'action=earning_potential',
                            'displayText' => 'ดูรายได้ที่เป็นไปได้',
                        ],
                    ],
                ],
            ],
            'name' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '💡 ตัวอย่างชื่อ',
                            'text' => 'ตัวอย่างชื่อ',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '❓ ทำไมต้องกรอกชื่อจริง',
                            'text' => 'ทำไมต้องกรอกชื่อจริง',
                        ],
                    ],
                ],
            ],
            'phone' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔒 ข้อมูลปลอดภัยไหม',
                            'text' => 'ข้อมูลปลอดภัยไหม',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '💡 ตัวอย่างเบอร์',
                            'text' => 'ตัวอย่างเบอร์',
                        ],
                    ],
                ],
            ],
            'otp' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🔄 ขอ OTP ใหม่',
                            'data' => 'action=resend_otp',
                            'displayText' => 'ขอ OTP ใหม่',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '❓ OTP อยู่ที่ไหน',
                            'text' => 'OTP อยู่ที่ไหน',
                        ],
                    ],
                ],
            ],
            'password' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '💡 ตัวอย่างรหัสผ่าน',
                            'text' => 'ตัวอย่างรหัสผ่านที่ดี',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔐 เคล็ดลับความปลอดภัย',
                            'text' => 'เคล็ดลับรหัสผ่านปลอดภัย',
                        ],
                    ],
                ],
            ],
            'referral' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '⏭️ ข้ามขั้นตอนนี้',
                            'text' => 'SKIP',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '❓ ประโยชน์รหัสแนะนำ',
                            'text' => 'ประโยชน์ของรหัสแนะนำคืออะไร',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔍 หารหัสแนะนำ',
                            'text' => 'หารหัสแนะนำได้ที่ไหน',
                        ],
                    ],
                ],
            ],
            'confirmation' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'message',
                            'label' => '✅ ยืนยันข้อมูล',
                            'text' => 'ยืนยัน',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '✏️ แก้ไขข้อมูล',
                            'data' => 'action=edit_data',
                            'displayText' => 'แก้ไขข้อมูล',
                        ],
                    ],
                ],
            ],
        ];

        return $quickReplies[$step] ?? [];
    }

    /**
     * Build complete message with Quick Reply
     */
    public function buildMessageWithQuickReply(array $message, string $step): array
    {
        $quickReply = $this->getSignupQuickReply($step);

        if (empty($quickReply)) {
            return $message;
        }

        $message['quickReply'] = $quickReply;

        return $message;
    }

    /**
     * Get wealth path Flex Message
     */
    public function buildWealthPathMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'เส้นทางสู่เศรษฐี - 5 ขั้นตอน',
            'contents' => [
                'type' => 'carousel',
                'contents' => [
                    $this->buildWealthStep(1, '🚀 เริ่มต้น', 'สมัครสมาชิกฟรี', '0 บาท', '2 นาที', '#6C5CE7'),
                    $this->buildWealthStep(2, '💫 ระดับเริ่มต้น', 'แชร์เบื้องต้น', '500-2,000 บาท', '1 สัปดาห์', '#00B894'),
                    $this->buildWealthStep(3, '⭐ ระดับกลาง', 'สร้างทีม', '5,000-15,000 บาท', '1 เดือน', '#FDCB6E'),
                    $this->buildWealthStep(4, '🌟 ระดับสูง', 'ผู้นำทีม', '30,000-100,000 บาท', '3 เดือน', '#E17055'),
                    $this->buildWealthStep(5, '👑 เศรษฐี', 'รายได้ Passive', '100,000+ บาท', '6-12 เดือน', '#D63031'),
                ],
            ],
        ];
    }

    /**
     * Build individual wealth step bubble
     */
    protected function buildWealthStep(int $step, string $title, string $subtitle, string $earning, string $time, string $color): array
    {
        return [
            'type' => 'bubble',
            'size' => 'micro',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "ขั้นที่ {$step}",
                        'size' => 'xs',
                        'color' => '#FFFFFF',
                        'weight' => 'bold',
                    ],
                ],
                'backgroundColor' => $color,
                'paddingAll' => 'md',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $title,
                        'weight' => 'bold',
                        'size' => 'lg',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => $subtitle,
                        'size' => 'sm',
                        'color' => '#999999',
                        'margin' => 'sm',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '💰 รายได้',
                                        'size' => 'xs',
                                        'color' => '#666666',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $earning,
                                        'size' => 'xs',
                                        'color' => '#111111',
                                        'align' => 'end',
                                        'weight' => 'bold',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '⏱️ ระยะเวลา',
                                        'size' => 'xs',
                                        'color' => '#666666',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $time,
                                        'size' => 'xs',
                                        'color' => '#111111',
                                        'align' => 'end',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'paddingAll' => 'md',
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => 'ดูรายละเอียด',
                            'data' => "action=wealth_detail&step={$step}",
                        ],
                        'style' => 'primary',
                        'color' => $color,
                        'height' => 'sm',
                    ],
                ],
                'paddingAll' => 'sm',
            ],
        ];
    }

    /**
     * Get how it works message
     */
    public function buildHowItWorksMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'วิธีการทำงานของระบบ',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800',
                    'size' => 'full',
                    'aspectRatio' => '20:13',
                    'aspectMode' => 'cover',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💼 วิธีการทำงาน',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ง่ายๆ แค่ 3 ขั้นตอน!',
                            'size' => 'md',
                            'margin' => 'md',
                            'color' => '#666666',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'md',
                            'contents' => [
                                $this->buildHowItWorksStep('1', 'สมัครสมาชิก', 'รับรหัสแนะนำส่วนตัว ฟรี!'),
                                $this->buildHowItWorksStep('2', 'แชร์ลิงก์', 'แชร์ไปยัง Social Media, LINE, Facebook'),
                                $this->buildHowItWorksStep('3', 'รับเงิน', 'เพื่อนสมัคร = คุณได้คอมมิชชั่น!'),
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'xl',
                            'backgroundColor' => '#E8F5E9',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '💡 เคล็ดลับความสำเร็จ',
                                    'weight' => 'bold',
                                    'size' => 'sm',
                                    'color' => '#2E7D32',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ยิ่งแชร์มาก ยิ่งได้มาก!\nทีมของคุณก็สร้างรายได้ให้คุณด้วย',
                                    'size' => 'xs',
                                    'color' => '#2E7D32',
                                    'margin' => 'sm',
                                    'wrap' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'postback',
                                'label' => '🚀 เริ่มสมัครเลย!',
                                'data' => 'action=start_signup',
                            ],
                            'style' => 'primary',
                            'color' => '#1DB446',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build how it works step
     */
    protected function buildHowItWorksStep(string $number, string $title, string $description): array
    {
        return [
            'type' => 'box',
            'layout' => 'horizontal',
            'spacing' => 'md',
            'contents' => [
                [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $number,
                            'size' => 'xl',
                            'color' => '#FFFFFF',
                            'weight' => 'bold',
                            'align' => 'center',
                        ],
                    ],
                    'backgroundColor' => '#1DB446',
                    'cornerRadius' => 'xl',
                    'width' => '40px',
                    'height' => '40px',
                    'justifyContent' => 'center',
                    'flex' => 0,
                ],
                [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $title,
                            'weight' => 'bold',
                            'size' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => $description,
                            'size' => 'xs',
                            'color' => '#999999',
                            'wrap' => true,
                            'margin' => 'xs',
                        ],
                    ],
                ],
            ],
        ];
    }
}
