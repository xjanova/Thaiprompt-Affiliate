<?php

namespace App\Services;

/**
 * LINE Rich Media Service
 *
 * Handles rich media content for LINE OA
 * - Image carousels
 * - Image templates
 * - Video messages
 * - Location sharing
 */
class LineRichMediaService
{
    /**
     * Create image carousel for sponsor benefits
     */
    public function createBenefitsCarousel(): array
    {
        return [
            'type' => 'template',
            'altText' => '🎁 สิทธิพิเศษของสมาชิก',
            'template' => [
                'type' => 'image_carousel',
                'columns' => [
                    [
                        'imageUrl' => asset('images/benefits/commission.jpg'),
                        'action' => [
                            'type' => 'message',
                            'label' => 'ค่าคอมมิชชั่น',
                            'text' => 'ค่าคอมมิชชั่น',
                        ],
                    ],
                    [
                        'imageUrl' => asset('images/benefits/team.jpg'),
                        'action' => [
                            'type' => 'message',
                            'label' => 'สร้างทีม',
                            'text' => 'สร้างทีม',
                        ],
                    ],
                    [
                        'imageUrl' => asset('images/benefits/training.jpg'),
                        'action' => [
                            'type' => 'message',
                            'label' => 'อบรม',
                            'text' => 'อบรมฟรี',
                        ],
                    ],
                    [
                        'imageUrl' => asset('images/benefits/bonus.jpg'),
                        'action' => [
                            'type' => 'message',
                            'label' => 'โบนัส',
                            'text' => 'โบนัสพิเศษ',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create success story carousel
     */
    public function createSuccessStoryCarousel(): array
    {
        $stories = [
            [
                'name' => 'คุณสมชาย',
                'image' => asset('images/success/story1.jpg'),
                'income' => '50,000',
                'period' => '3 เดือน',
            ],
            [
                'name' => 'คุณสมศรี',
                'image' => asset('images/success/story2.jpg'),
                'income' => '100,000',
                'period' => '6 เดือน',
            ],
            [
                'name' => 'คุณสมหมาย',
                'image' => asset('images/success/story3.jpg'),
                'income' => '200,000',
                'period' => '1 ปี',
            ],
        ];

        $columns = [];
        foreach ($stories as $story) {
            $columns[] = [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => $story['image'],
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
                            'text' => $story['name'],
                            'weight' => 'bold',
                            'size' => 'lg',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'margin' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '💰 รายได้',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                    'flex' => 0,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => "฿{$story['income']}",
                                    'size' => 'sm',
                                    'color' => '#51CF66',
                                    'weight' => 'bold',
                                    'align' => 'end',
                                ],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'margin' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '⏱️ ระยะเวลา',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                    'flex' => 0,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $story['period'],
                                    'size' => 'sm',
                                    'color' => '#111111',
                                    'align' => 'end',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            'type' => 'flex',
            'altText' => '🌟 เรื่องราวความสำเร็จ',
            'contents' => [
                'type' => 'carousel',
                'contents' => $columns,
            ],
        ];
    }

    /**
     * Create video introduction message
     */
    public function createVideoIntroMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => '📹 วิดีโอแนะนำธุรกิจ',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'video',
                    'url' => asset('videos/intro.mp4'),
                    'previewUrl' => asset('images/video-preview.jpg'),
                    'altContent' => [
                        'type' => 'image',
                        'url' => asset('images/video-preview.jpg'),
                        'size' => 'full',
                        'aspectRatio' => '16:9',
                        'aspectMode' => 'cover',
                    ],
                    'aspectRatio' => '16:9',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'รู้จักธุรกิจของเรา',
                            'weight' => 'bold',
                            'size' => 'lg',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ดูวิดีโอสั้น 3 นาทีเพื่อทำความรู้จักกับโอกาสทางธุรกิจของเรา',
                            'size' => 'sm',
                            'color' => '#666666',
                            'wrap' => true,
                            'margin' => 'md',
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
                                'type' => 'message',
                                'label' => 'สนใจสมัคร',
                                'text' => 'สนใจสมัคร',
                            ],
                            'style' => 'primary',
                            'color' => '#FF6B6B',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create step guide with images
     */
    public function createStepGuideCarousel(): array
    {
        $steps = [
            [
                'step' => 1,
                'title' => 'กรอกข้อมูล',
                'description' => 'กรอกข้อมูลส่วนตัวให้ครบถ้วน',
                'image' => asset('images/guide/step1.png'),
                'icon' => '📝',
            ],
            [
                'step' => 2,
                'title' => 'ยืนยันอีเมล',
                'description' => 'ยืนยันอีเมลด้วยรหัส OTP',
                'image' => asset('images/guide/step2.png'),
                'icon' => '📧',
            ],
            [
                'step' => 3,
                'title' => 'ตั้งรหัสผ่าน',
                'description' => 'สร้างรหัสผ่านที่ปลอดภัย',
                'image' => asset('images/guide/step3.png'),
                'icon' => '🔒',
            ],
            [
                'step' => 4,
                'title' => 'เริ่มต้นใช้งาน',
                'description' => 'เข้าสู่ระบบและเริ่มสร้างรายได้',
                'image' => asset('images/guide/step4.png'),
                'icon' => '🚀',
            ],
        ];

        $bubbles = [];
        foreach ($steps as $step) {
            $bubbles[] = [
                'type' => 'bubble',
                'size' => 'micro',
                'hero' => [
                    'type' => 'image',
                    'url' => $step['image'],
                    'size' => 'full',
                    'aspectMode' => 'cover',
                    'aspectRatio' => '320:213',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $step['icon'].' ขั้นตอนที่ '.$step['step'],
                            'weight' => 'bold',
                            'size' => 'sm',
                            'color' => '#FF6B6B',
                        ],
                        [
                            'type' => 'text',
                            'text' => $step['title'],
                            'weight' => 'bold',
                            'size' => 'md',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => $step['description'],
                            'size' => 'xs',
                            'color' => '#666666',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                    ],
                    'spacing' => 'sm',
                    'paddingAll' => '13px',
                ],
            ];
        }

        return [
            'type' => 'flex',
            'altText' => '📚 คู่มือการสมัคร 4 ขั้นตอน',
            'contents' => [
                'type' => 'carousel',
                'contents' => $bubbles,
            ],
        ];
    }

    /**
     * Create location sharing request
     */
    public function createLocationRequest(): array
    {
        return [
            'type' => 'flex',
            'altText' => '📍 แชร์ location ของคุณ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '📍',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'แชร์ที่อยู่ของคุณ',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เพื่อให้เราสามารถบริการคุณได้ดียิ่งขึ้น',
                            'size' => 'sm',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                            'margin' => 'md',
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
                                'type' => 'uri',
                                'label' => 'แชร์ Location',
                                'uri' => 'https://line.me/R/nv/location',
                            ],
                            'style' => 'primary',
                            'color' => '#339AF0',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create confirmation buttons
     */
    public function createConfirmButtons(string $question, string $yesText = 'ใช่', string $noText = 'ไม่'): array
    {
        return [
            'type' => 'template',
            'altText' => $question,
            'template' => [
                'type' => 'confirm',
                'text' => $question,
                'actions' => [
                    [
                        'type' => 'message',
                        'label' => $yesText,
                        'text' => $yesText,
                    ],
                    [
                        'type' => 'message',
                        'label' => $noText,
                        'text' => $noText,
                    ],
                ],
            ],
        ];
    }
}
