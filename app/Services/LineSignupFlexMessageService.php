<?php

namespace App\Services;

use App\Models\LineSignupSession;
use App\Models\User;

class LineSignupFlexMessageService
{
    /**
     * Build name input message
     */
    public function buildNameInputMessage(string $displayName): array
    {
        return [
            'type' => 'flex',
            'altText' => 'กรอกชื่อของคุณ',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => 'https://images.unsplash.com/photo-1557683316-973673baf926?w=800',
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
                            'text' => '👋 สวัสดี!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'text',
                            'text' => "ยินดีต้อนรับสู่ระบบสมาชิก",
                            'size' => 'lg',
                            'weight' => 'bold',
                            'margin' => 'md',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 1/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '14%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '📝 กรุณากรอกชื่อ-นามสกุล ของคุณ',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เช่น: สมชาย ใจดี',
                            'size' => 'sm',
                            'color' => '#999999',
                            'margin' => 'sm',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬 พิมพ์ชื่อของคุณในช่องแชทด้านล่าง',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build email input message
     */
    public function buildEmailInputMessage(string $name): array
    {
        return [
            'type' => 'flex',
            'altText' => 'กรอกอีเมลของคุณ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '✅ ยอดเยี่ยม!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'text',
                            'text' => "ขอบคุณ คุณ{$name}",
                            'size' => 'md',
                            'margin' => 'sm',
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
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 2/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '28%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '📧 กรุณากรอกอีเมล',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
                            'weight' => 'bold',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เช่น: example@email.com',
                            'size' => 'sm',
                            'color' => '#999999',
                            'margin' => 'sm',
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
                                            'text' => '✓',
                                            'size' => 'sm',
                                            'color' => '#1DB446',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'ใช้สำหรับเข้าสู่ระบบ',
                                            'size' => 'xs',
                                            'color' => '#666666',
                                            'flex' => 1,
                                            'margin' => 'sm',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '✓',
                                            'size' => 'sm',
                                            'color' => '#1DB446',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'รับข่าวสารและโปรโมชั่น',
                                            'size' => 'xs',
                                            'color' => '#666666',
                                            'flex' => 1,
                                            'margin' => 'sm',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬 พิมพ์อีเมลของคุณในช่องแชท',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build phone input message
     */
    public function buildPhoneInputMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'กรอกเบอร์โทรศัพท์',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '📱 หมายเลขโทรศัพท์',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 3/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '42%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'กรุณากรอกหมายเลขโทรศัพท์',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เช่น: 0812345678',
                            'size' => 'sm',
                            'color' => '#999999',
                            'margin' => 'sm',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#FFF9E6',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🔐 เราจะส่งรหัส OTP เพื่อยืนยันตัวตน',
                                    'size' => 'xs',
                                    'color' => '#8B7500',
                                    'wrap' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬 พิมพ์เบอร์โทรศัพท์ (10 หลัก)',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build OTP verification message
     */
    public function buildOtpVerificationMessage(string $phone): array
    {
        $maskedPhone = substr($phone, 0, 3) . 'XXX' . substr($phone, -4);

        return [
            'type' => 'flex',
            'altText' => 'ยืนยัน OTP',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🔐 ยืนยัน OTP',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 4/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '57%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เราได้ส่งรหัส OTP ไปยัง',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'sm',
                            'color' => '#666666',
                        ],
                        [
                            'type' => 'text',
                            'text' => $maskedPhone,
                            'size' => 'lg',
                            'weight' => 'bold',
                            'margin' => 'sm',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#E8F5E9',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '✉️ รหัส OTP ถูกส่งมาทาง LINE แล้ว',
                                    'size' => 'sm',
                                    'color' => '#2E7D32',
                                    'wrap' => true,
                                    'weight' => 'bold',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'กรุณาตรวจสอบข้อความด้านบน',
                                    'size' => 'xs',
                                    'color' => '#2E7D32',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '⏱ รหัส OTP จะหมดอายุใน 5 นาที',
                            'size' => 'xs',
                            'color' => '#FF6B6B',
                            'margin' => 'lg',
                            'align' => 'center',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬 พิมพ์รหัส OTP 6 หลัก',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build password input message
     */
    public function buildPasswordInputMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'ตั้งรหัสผ่าน',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🔒 ตั้งรหัสผ่าน',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 5/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '71%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'กรุณาตั้งรหัสผ่านสำหรับบัญชีของคุณ',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
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
                                            'text' => '✓',
                                            'size' => 'sm',
                                            'color' => '#1DB446',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'อย่างน้อย 8 ตัวอักษร',
                                            'size' => 'xs',
                                            'color' => '#666666',
                                            'flex' => 1,
                                            'margin' => 'sm',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '✓',
                                            'size' => 'sm',
                                            'color' => '#1DB446',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'ควรมีตัวอักษรและตัวเลขผสมกัน',
                                            'size' => 'xs',
                                            'color' => '#666666',
                                            'flex' => 1,
                                            'margin' => 'sm',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬 พิมพ์รหัสผ่านที่ต้องการใช้',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build referral input message
     */
    public function buildReferralInputMessage(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'รหัสผู้แนะนำ (ถ้ามี)',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎁 รหัสผู้แนะนำ',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 6/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '85%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'มีรหัสผู้แนะนำหรือไม่?',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
                            'weight' => 'bold',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'หากมีผู้แนะนำ กรุณากรอกรหัส',
                            'size' => 'sm',
                            'color' => '#666666',
                            'margin' => 'sm',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#FFF3E0',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🎉 พิเศษ! รับโบนัสเพิ่ม',
                                    'size' => 'sm',
                                    'color' => '#E65100',
                                    'wrap' => true,
                                    'weight' => 'bold',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'เมื่อระบุรหัสผู้แนะนำ คุณจะได้รับคะแนนโบนัสพิเศษ!',
                                    'size' => 'xs',
                                    'color' => '#E65100',
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
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ข้ามขั้นตอนนี้',
                                'text' => 'SKIP',
                            ],
                            'style' => 'secondary',
                            'height' => 'sm',
                        ],
                        [
                            'type' => 'text',
                            'text' => '💬 หรือพิมพ์รหัสผู้แนะนำ',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build confirmation message
     */
    public function buildConfirmationMessage(LineSignupSession $session): array
    {
        $data = $session->collected_data;

        return [
            'type' => 'flex',
            'altText' => 'ยืนยันข้อมูล',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '✅ ยืนยันข้อมูล',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
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
                                    'type' => 'text',
                                    'text' => 'ขั้นตอนที่ 7/7',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [
                                        [
                                            'type' => 'box',
                                            'layout' => 'vertical',
                                            'contents' => [],
                                            'backgroundColor' => '#1DB446',
                                            'height' => '6px',
                                            'width' => '100%',
                                        ],
                                    ],
                                    'backgroundColor' => '#E0E0E0',
                                    'height' => '6px',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'กรุณาตรวจสอบข้อมูลของคุณ',
                            'wrap' => true,
                            'margin' => 'xl',
                            'size' => 'md',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                $this->buildInfoRow('ชื่อ-นามสกุล', $data['name'] ?? '-'),
                                $this->buildInfoRow('อีเมล', $data['email'] ?? '-'),
                                $this->buildInfoRow('เบอร์โทร', $data['phone'] ?? '-'),
                                $this->buildInfoRow('รหัสผู้แนะนำ', $data['referral_code'] ?? 'ไม่ระบุ'),
                            ],
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
                            'action' => [
                                'type' => 'message',
                                'label' => '✅ ยืนยันและสร้างบัญชี',
                                'text' => 'ยืนยัน',
                            ],
                            'style' => 'primary',
                            'color' => '#1DB446',
                            'height' => 'sm',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'หรือพิมพ์ "ยืนยัน" เพื่อดำเนินการต่อ',
                            'size' => 'xs',
                            'color' => '#666666',
                            'align' => 'center',
                            'margin' => 'sm',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build success message
     */
    public function buildSuccessMessage(User $user): array
    {
        return [
            'type' => 'flex',
            'altText' => 'สมัครสมาชิกสำเร็จ!',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => 'https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?w=800',
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
                            'text' => '🎉 ยินดีด้วย!',
                            'weight' => 'bold',
                            'size' => 'xxl',
                            'color' => '#1DB446',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'สมัครสมาชิกสำเร็จแล้ว',
                            'size' => 'lg',
                            'color' => '#666666',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#E8F5E9',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '📧 อีเมล',
                                    'size' => 'xs',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $user->email,
                                    'size' => 'sm',
                                    'weight' => 'bold',
                                    'margin' => 'xs',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '🔑 รหัสสมาชิก',
                                    'size' => 'xs',
                                    'color' => '#999999',
                                    'margin' => 'md',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $user->member_number ?? 'N/A',
                                    'size' => 'sm',
                                    'weight' => 'bold',
                                    'margin' => 'xs',
                                ],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#FFF9E6',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🎁 คุณได้รับโบนัส 100 คะแนน!',
                                    'size' => 'sm',
                                    'color' => '#E65100',
                                    'weight' => 'bold',
                                ],
                            ],
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
                            'action' => [
                                'type' => 'uri',
                                'label' => 'เข้าสู่ระบบ',
                                'uri' => url('/login'),
                            ],
                            'style' => 'primary',
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'ดูแดชบอร์ด',
                                'uri' => url('/user/dashboard'),
                            ],
                            'style' => 'secondary',
                            'margin' => 'sm',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build welcome sequence message
     */
    public function buildWelcomeSequenceMessage(User $user): array
    {
        $affiliate = $user->affiliate;

        return [
            'type' => 'flex',
            'altText' => 'ยินดีต้อนรับสู่ระบบ!',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '👋 สวัสดี ' . $user->name,
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เริ่มต้นใช้งานกับเราวันนี้',
                            'size' => 'md',
                            'color' => '#666666',
                            'margin' => 'md',
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
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '1️⃣',
                                            'size' => 'lg',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'อัพเดทโปรไฟล์ของคุณ',
                                            'size' => 'sm',
                                            'flex' => 1,
                                            'margin' => 'md',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '2️⃣',
                                            'size' => 'lg',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'แชร์รหัสแนะนำเพื่อน',
                                            'size' => 'sm',
                                            'flex' => 1,
                                            'margin' => 'md',
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'horizontal',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '3️⃣',
                                            'size' => 'lg',
                                            'flex' => 0,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'รับรายได้และโบนัส',
                                            'size' => 'sm',
                                            'flex' => 1,
                                            'margin' => 'md',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'backgroundColor' => '#F0F0F0',
                            'cornerRadius' => 'md',
                            'paddingAll' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🎫 รหัสแนะนำของคุณ',
                                    'size' => 'xs',
                                    'color' => '#999999',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $affiliate?->referral_code ?? 'N/A',
                                    'size' => 'xl',
                                    'weight' => 'bold',
                                    'margin' => 'sm',
                                    'align' => 'center',
                                ],
                            ],
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
                            'action' => [
                                'type' => 'uri',
                                'label' => '🚀 เริ่มต้นใช้งาน',
                                'uri' => url('/user/dashboard'),
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
     * Build info row helper
     */
    protected function buildInfoRow(string $label, string $value): array
    {
        return [
            'type' => 'box',
            'layout' => 'horizontal',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $label,
                    'size' => 'sm',
                    'color' => '#999999',
                    'flex' => 2,
                ],
                [
                    'type' => 'text',
                    'text' => $value,
                    'size' => 'sm',
                    'color' => '#111111',
                    'flex' => 3,
                    'wrap' => true,
                    'weight' => 'bold',
                ],
            ],
        ];
    }
}
