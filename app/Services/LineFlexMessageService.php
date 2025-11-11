<?php

namespace App\Services;

use App\Models\MlmProspect;
use App\Models\User;

/**
 * LINE Flex Message Service
 *
 * Creates beautiful, interactive Flex Messages for LINE OA signup process
 * - Progress bars with visual indicators
 * - Step-by-step guides with icons
 * - Quick reply buttons for easy interaction
 * - Completion celebrations with confetti
 * - Error messages with helpful suggestions
 */
class LineFlexMessageService
{
    // Brand colors
    private const PRIMARY_COLOR = '#FF6B6B';
    private const SUCCESS_COLOR = '#51CF66';
    private const WARNING_COLOR = '#FFD43B';
    private const INFO_COLOR = '#339AF0';
    private const GRAY_COLOR = '#ADB5BD';

    /**
     * Create signup progress Flex Message
     */
    public function createProgressMessage(MlmProspect $prospect, string $currentStep): array
    {
        $progress = $this->calculateProgress($prospect, $currentStep);

        return [
            'type' => 'flex',
            'altText' => "📊 ความคืบหน้าการสมัคร {$progress['percent']}%",
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => $this->createProgressHeader($progress),
                'body' => $this->createProgressBody($prospect, $currentStep, $progress),
                'footer' => $this->createProgressFooter($currentStep),
                'styles' => [
                    'header' => ['backgroundColor' => self::PRIMARY_COLOR],
                    'footer' => ['separator' => true, 'backgroundColor' => '#F8F9FA'],
                ],
            ],
        ];
    }

    /**
     * Create welcome Flex Message with animation
     */
    public function createWelcomeMessage(MlmProspect $prospect): array
    {
        $sponsorName = $prospect->sponsorUser?->name ?? 'ผู้สนับสนุน';

        return [
            'type' => 'flex',
            'altText' => '🎉 ยินดีต้อนรับสู่การสมัครสมาชิก!',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'hero' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎉',
                            'size' => '5xl',
                            'align' => 'center',
                            'gravity' => 'center',
                        ],
                    ],
                    'height' => '120px',
                    'backgroundColor' => self::PRIMARY_COLOR,
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'ยินดีต้อนรับ!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => self::PRIMARY_COLOR,
                        ],
                        [
                            'type' => 'text',
                            'text' => "คุณ {$sponsorName} ได้เชิญคุณมาเป็นส่วนหนึ่งของทีม",
                            'size' => 'sm',
                            'color' => '#666666',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                $this->createInfoRow('⏱️', 'ใช้เวลาประมาณ', '3-5 นาที'),
                                $this->createInfoRow('📝', 'ขั้นตอนทั้งหมด', '5 ขั้นตอน'),
                                $this->createInfoRow('🎁', 'รับสิทธิพิเศษ', 'ทันทีเมื่อสมัครเสร็จ'),
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
                            'style' => 'primary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'message',
                                'label' => 'เริ่มสมัครเลย! 🚀',
                                'text' => 'เริ่มสมัคร',
                            ],
                            'color' => self::PRIMARY_COLOR,
                        ],
                        [
                            'type' => 'button',
                            'style' => 'link',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ต้องการความช่วยเหลือ?',
                                'text' => 'ช่วยเหลือ',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create completion celebration Flex Message
     */
    public function createCompletionMessage(User $user, MlmProspect $prospect): array
    {
        $timeSpent = $prospect->conversation_started_at
            ? now()->diffInMinutes($prospect->conversation_started_at)
            : 0;

        return [
            'type' => 'flex',
            'altText' => '🎊 ยินดีด้วย! สมัครสมาชิกเรียบร้อยแล้ว',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'hero' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎊',
                            'size' => '5xl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'สำเร็จแล้ว!',
                            'size' => 'xl',
                            'weight' => 'bold',
                            'align' => 'center',
                            'color' => '#FFFFFF',
                        ],
                    ],
                    'height' => '150px',
                    'backgroundColor' => self::SUCCESS_COLOR,
                    'paddingAll' => 'xl',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => "ยินดีต้อนรับคุณ {$user->name}!",
                            'weight' => 'bold',
                            'size' => 'lg',
                            'color' => self::SUCCESS_COLOR,
                        ],
                        [
                            'type' => 'text',
                            'text' => 'คุณได้เป็นส่วนหนึ่งของครอบครัวเราแล้ว',
                            'size' => 'sm',
                            'color' => '#666666',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                $this->createInfoRow('👤', 'ชื่อผู้ใช้', $user->username),
                                $this->createInfoRow('📧', 'อีเมล', $user->email),
                                $this->createInfoRow('⏱️', 'ใช้เวลา', "{$timeSpent} นาที"),
                                $this->createInfoRow('🎯', 'สถานะ', 'เปิดใช้งาน'),
                            ],
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🎁 สิทธิพิเศษของคุณ',
                                    'weight' => 'bold',
                                    'color' => self::INFO_COLOR,
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✓ เข้าถึงระบบได้ทันที\n✓ รับโบนัสต้อนรับ\n✓ เริ่มสร้างทีมได้เลย',
                                    'size' => 'sm',
                                    'color' => '#666666',
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
                            'style' => 'primary',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'เข้าสู่ระบบ 🚀',
                                'uri' => route('login'),
                            ],
                            'color' => self::SUCCESS_COLOR,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create error message with suggestions
     */
    public function createErrorMessage(string $error, array $suggestions = []): array
    {
        $suggestionContents = [];
        foreach ($suggestions as $index => $suggestion) {
            $suggestionContents[] = [
                'type' => 'text',
                'text' => ($index + 1) . '. ' . $suggestion,
                'size' => 'sm',
                'color' => '#666666',
                'wrap' => true,
            ];
        }

        return [
            'type' => 'flex',
            'altText' => '⚠️ ' . $error,
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '⚠️',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'พบข้อผิดพลาด',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'align' => 'center',
                            'color' => self::WARNING_COLOR,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => $error,
                            'size' => 'sm',
                            'color' => '#666666',
                            'wrap' => true,
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        ...(empty($suggestions) ? [] : [
                            [
                                'type' => 'separator',
                                'margin' => 'xl',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'margin' => 'lg',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '💡 คำแนะนำ',
                                        'weight' => 'bold',
                                        'color' => self::INFO_COLOR,
                                        'size' => 'sm',
                                    ],
                                    ...$suggestionContents,
                                ],
                            ],
                        ]),
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ลองใหม่อีกครั้ง',
                                'text' => 'ลองใหม่',
                            ],
                            'color' => self::WARNING_COLOR,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create Quick Reply buttons
     */
    public function createQuickReplies(string $step): array
    {
        $replies = match($step) {
            'name' => [
                ['label' => '✏️ แก้ไขชื่อ', 'text' => 'แก้ไขชื่อ'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
            'username' => [
                ['label' => '🎲 สุ่มชื่อผู้ใช้', 'text' => 'สุ่มชื่อผู้ใช้'],
                ['label' => '💡 แนะนำชื่อ', 'text' => 'แนะนำชื่อผู้ใช้'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
            'email' => [
                ['label' => '📧 ส่ง OTP อีกครั้ง', 'text' => 'ส่ง OTP อีกครั้ง'],
                ['label' => '✏️ แก้ไขอีเมล', 'text' => 'แก้ไขอีเมล'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
            'phone' => [
                ['label' => '✏️ แก้ไขเบอร์', 'text' => 'แก้ไขเบอร์โทร'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
            'password' => [
                ['label' => '🔒 แนะนำรหัสผ่าน', 'text' => 'แนะนำรหัสผ่านที่แข็งแรง'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
            default => [
                ['label' => '🔄 เริ่มใหม่', 'text' => 'เริ่มใหม่'],
                ['label' => '❓ ช่วยเหลือ', 'text' => 'ช่วยเหลือ'],
            ],
        };

        return [
            'items' => array_map(fn($reply) => [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $reply['label'],
                    'text' => $reply['text'],
                ],
            ], $replies),
        ];
    }

    /**
     * Calculate signup progress
     */
    private function calculateProgress(MlmProspect $prospect, string $currentStep): array
    {
        $steps = ['name', 'username', 'email', 'otp', 'phone', 'password', 'confirm'];
        $totalSteps = count($steps);
        $currentStepIndex = array_search($currentStep, $steps);
        $completedSteps = max(0, $currentStepIndex);
        $percent = $totalSteps > 0 ? (int) (($completedSteps / $totalSteps) * 100) : 0;

        return [
            'total' => $totalSteps,
            'completed' => $completedSteps,
            'current' => $currentStepIndex + 1,
            'percent' => $percent,
            'currentStepName' => $this->getStepName($currentStep),
        ];
    }

    /**
     * Create progress header
     */
    private function createProgressHeader(array $progress): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => 'ความคืบหน้าการสมัคร',
                    'color' => '#FFFFFF',
                    'weight' => 'bold',
                    'size' => 'md',
                ],
                [
                    'type' => 'text',
                    'text' => "{$progress['percent']}%",
                    'color' => '#FFFFFF',
                    'size' => 'xxl',
                    'weight' => 'bold',
                    'margin' => 'md',
                ],
            ],
            'paddingAll' => 'xl',
        ];
    }

    /**
     * Create progress body
     */
    private function createProgressBody(MlmProspect $prospect, string $currentStep, array $progress): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                // Progress bar
                [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [],
                                    'width' => "{$progress['percent']}%",
                                    'backgroundColor' => self::PRIMARY_COLOR,
                                    'height' => '8px',
                                    'cornerRadius' => 'md',
                                ],
                            ],
                            'backgroundColor' => self::GRAY_COLOR,
                            'height' => '8px',
                            'cornerRadius' => 'md',
                        ],
                    ],
                ],
                // Current step info
                [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'margin' => 'xl',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => "ขั้นตอนที่ {$progress['current']} จาก {$progress['total']}",
                            'size' => 'sm',
                            'color' => '#999999',
                        ],
                        [
                            'type' => 'text',
                            'text' => $progress['currentStepName'],
                            'weight' => 'bold',
                            'size' => 'lg',
                            'color' => self::PRIMARY_COLOR,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create progress footer
     */
    private function createProgressFooter(string $currentStep): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'spacing' => 'sm',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => '💡 กดปุ่มด้านล่างเพื่อใช้คำสั่งด่วน',
                    'size' => 'xs',
                    'color' => '#999999',
                    'align' => 'center',
                ],
            ],
        ];
    }

    /**
     * Create info row helper
     */
    private function createInfoRow(string $icon, string $label, string $value): array
    {
        return [
            'type' => 'box',
            'layout' => 'horizontal',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $icon,
                    'size' => 'sm',
                    'flex' => 0,
                ],
                [
                    'type' => 'text',
                    'text' => $label,
                    'size' => 'sm',
                    'color' => '#999999',
                    'flex' => 3,
                ],
                [
                    'type' => 'text',
                    'text' => $value,
                    'size' => 'sm',
                    'color' => '#111111',
                    'align' => 'end',
                    'flex' => 4,
                    'wrap' => true,
                ],
            ],
        ];
    }

    /**
     * Get Thai step name
     */
    private function getStepName(string $step): string
    {
        return match($step) {
            'name' => '📝 กรอกชื่อ-นามสกุล',
            'username' => '👤 ตั้งชื่อผู้ใช้',
            'email' => '📧 กรอกอีเมล',
            'otp' => '🔐 ยืนยัน OTP',
            'phone' => '📱 กรอกเบอร์โทร',
            'password' => '🔒 ตั้งรหัสผ่าน',
            'confirm' => '✅ ยืนยันข้อมูล',
            default => '📋 กรอกข้อมูล',
        };
    }
}
