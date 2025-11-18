<?php

namespace Database\Seeders;

use App\Models\LineSignupTemplate;
use Illuminate\Database\Seeder;

class LineSignupTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'template_key' => 'welcome_hero',
                'template_name' => 'Welcome Message with Hero Image',
                'description' => 'Welcome message สำหรับผู้ใช้ใหม่ที่ติดตาม LINE OA',
                'flex_message_json' => $this->getWelcomeHeroTemplate(),
                'variables' => ['user_name'],
                'is_active' => true,
                'is_default' => true,  // ⭐ Template ต้นฉบับ - สามารถ reset ได้
                'category' => 'welcome',  // หมวดหมู่: ต้อนรับ
            ],
            [
                'template_key' => 'earning_calculator',
                'template_name' => 'Earning Calculator',
                'description' => 'แสดงการคำนวณรายได้ที่เป็นไปได้',
                'flex_message_json' => $this->getEarningCalculatorTemplate(),
                'variables' => ['referrals', 'monthly_earning', 'yearly_earning'],
                'is_active' => true,
                'is_default' => true,  // ⭐ Template ต้นฉบับ - สามารถ reset ได้
                'category' => 'earning',  // หมวดหมู่: รายได้
            ],
            [
                'template_key' => 'success_story',
                'template_name' => 'Success Story',
                'description' => 'เรื่องราวความสำเร็จของสมาชิก',
                'flex_message_json' => $this->getSuccessStoryTemplate(),
                'variables' => ['member_name', 'before_income', 'after_income', 'duration', 'quote'],
                'is_active' => true,
                'is_default' => true,  // ⭐ Template ต้นฉบับ - สามารถ reset ได้
                'category' => 'success_story',  // หมวดหมู่: เรื่องราวความสำเร็จ
            ],
            [
                'template_key' => 'training_course',
                'template_name' => 'Training Course Promo',
                'description' => 'โปรโมทคอร์สเรียนฟรี',
                'flex_message_json' => $this->getTrainingCourseTemplate(),
                'variables' => ['course_count', 'total_hours'],
                'is_active' => true,
                'is_default' => true,  // ⭐ Template ต้นฉบับ - สามารถ reset ได้
                'category' => 'training',  // หมวดหมู่: อบรม
            ],
            [
                'template_key' => 'quick_start_guide',
                'template_name' => 'Quick Start Guide',
                'description' => 'คู่มือเริ่มต้นสำหรับสมาชิกใหม่',
                'flex_message_json' => $this->getQuickStartGuideTemplate(),
                'variables' => ['member_code', 'referral_code'],
                'is_active' => true,
                'is_default' => true,  // ⭐ Template ต้นฉบับ - สามารถ reset ได้
                'category' => 'guide',  // หมวดหมู่: คู่มือ
            ],
        ];

        foreach ($templates as $template) {
            LineSignupTemplate::updateOrCreate(
                ['template_key' => $template['template_key']],
                $template
            );
        }

        $this->command->info('✅ LINE Signup Templates seeded successfully!');
    }

    /**
     * Welcome Hero Template
     */
    protected function getWelcomeHeroTemplate(): array
    {
        return [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800',
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
                        'text' => '🎉 ยินดีต้อนรับ!',
                        'weight' => 'bold',
                        'size' => 'xxl',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'สวัสดี {{user_name}} 👋',
                        'size' => 'lg',
                        'margin' => 'md',
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
                        'spacing' => 'md',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '✓',
                                        'size' => 'lg',
                                        'color' => '#1DB446',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => 'สมัครฟรี ไม่มีค่าใช้จ่าย',
                                        'size' => 'sm',
                                        'color' => '#666666',
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
                                        'text' => '✓',
                                        'size' => 'lg',
                                        'color' => '#1DB446',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => 'สร้างรายได้ได้จริง',
                                        'size' => 'sm',
                                        'color' => '#666666',
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
                                        'text' => '✓',
                                        'size' => 'lg',
                                        'color' => '#1DB446',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => 'มีทีมงานช่วยเหลือตลอด',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                        'margin' => 'md',
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
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🚀 สมัครสมาชิก',
                            'data' => 'action=start_signup',
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => '📖 เรียนรู้เพิ่มเติม',
                            'data' => 'action=learn_more',
                        ],
                        'style' => 'secondary',
                    ],
                ],
            ],
        ];
    }

    /**
     * Earning Calculator Template
     */
    protected function getEarningCalculatorTemplate(): array
    {
        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '💰 คำนวณรายได้',
                        'weight' => 'bold',
                        'size' => 'xl',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'รายได้ที่คุณจะได้รับ',
                        'size' => 'sm',
                        'color' => '#999999',
                        'margin' => 'sm',
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
                                        'text' => 'จำนวนผู้แนะนำ',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{referrals}} คน',
                                        'size' => 'sm',
                                        'color' => '#111111',
                                        'align' => 'end',
                                        'weight' => 'bold',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'separator',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'รายได้ต่อเดือน',
                                        'size' => 'md',
                                        'color' => '#111111',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{monthly_earning}} ฿',
                                        'size' => 'lg',
                                        'color' => '#1DB446',
                                        'align' => 'end',
                                        'weight' => 'bold',
                                    ],
                                ],
                                'margin' => 'md',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'รายได้ต่อปี',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{yearly_earning}} ฿',
                                        'size' => 'md',
                                        'color' => '#E17055',
                                        'align' => 'end',
                                        'weight' => 'bold',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'xl',
                        'backgroundColor' => '#FFF9E6',
                        'cornerRadius' => 'md',
                        'paddingAll' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '💡 เคล็ดลับ: ยิ่งแนะนำมาก ยิ่งได้มาก!',
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
                'contents' => [
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => 'เริ่มสร้างรายได้',
                            'data' => 'action=start_signup',
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446',
                    ],
                ],
            ],
        ];
    }

    /**
     * Success Story Template
     */
    protected function getSuccessStoryTemplate(): array
    {
        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🌟 เรื่องสำเร็จ',
                        'weight' => 'bold',
                        'size' => 'xl',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'text',
                        'text' => '{{member_name}}',
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
                        'spacing' => 'md',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '⬇️ ก่อน',
                                        'size' => 'sm',
                                        'color' => '#999999',
                                        'flex' => 0,
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{before_income}}',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                        'margin' => 'md',
                                        'wrap' => true,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '⬆️ หลัง',
                                        'size' => 'sm',
                                        'color' => '#1DB446',
                                        'flex' => 0,
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{after_income}}',
                                        'size' => 'sm',
                                        'color' => '#1DB446',
                                        'weight' => 'bold',
                                        'margin' => 'md',
                                        'wrap' => true,
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
                                        'color' => '#999999',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{duration}}',
                                        'size' => 'xs',
                                        'color' => '#666666',
                                        'margin' => 'md',
                                    ],
                                ],
                            ],
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
                                'text' => '💬 "{{quote}}"',
                                'size' => 'sm',
                                'color' => '#2E7D32',
                                'wrap' => true,
                                'style' => 'italic',
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
                            'label' => 'ฉันก็ทำได้!',
                            'data' => 'action=start_signup',
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446',
                    ],
                ],
            ],
        ];
    }

    /**
     * Training Course Template
     */
    protected function getTrainingCourseTemplate(): array
    {
        return [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800',
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
                        'text' => '📚 คอร์สเรียนฟรี!',
                        'weight' => 'bold',
                        'size' => 'xl',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'เรียนรู้เทคนิคการขายออนไลน์',
                        'size' => 'md',
                        'margin' => 'md',
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
                                        'text' => '📖 จำนวนคอร์ส',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{course_count}} คอร์ส',
                                        'size' => 'sm',
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
                                        'text' => '⏰ รวมเวลา',
                                        'size' => 'sm',
                                        'color' => '#666666',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '{{total_hours}} ชั่วโมง',
                                        'size' => 'sm',
                                        'color' => '#111111',
                                        'align' => 'end',
                                        'weight' => 'bold',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'หัวข้อที่จะได้เรียน:',
                        'size' => 'sm',
                        'weight' => 'bold',
                        'margin' => 'xl',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '✓ การตลาดออนไลน์เบื้องต้น',
                                'size' => 'xs',
                                'color' => '#666666',
                            ],
                            [
                                'type' => 'text',
                                'text' => '✓ Social Media Marketing',
                                'size' => 'xs',
                                'color' => '#666666',
                            ],
                            [
                                'type' => 'text',
                                'text' => '✓ Content Creation',
                                'size' => 'xs',
                                'color' => '#666666',
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
                            'label' => 'เข้าเรียนเลย!',
                            'data' => 'action=start_signup',
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446',
                    ],
                ],
            ],
        ];
    }

    /**
     * Quick Start Guide Template
     */
    protected function getQuickStartGuideTemplate(): array
    {
        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🎉 ยินดีต้อนรับสู่ครอบครัว!',
                        'weight' => 'bold',
                        'size' => 'xl',
                        'color' => '#1DB446',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => 'เริ่มต้นใช้งานได้ทันที',
                        'size' => 'sm',
                        'color' => '#999999',
                        'margin' => 'sm',
                    ],
                    [
                        'type' => 'separator',
                        'margin' => 'lg',
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
                                'text' => '🆔 รหัสสมาชิก',
                                'size' => 'xs',
                                'color' => '#999999',
                            ],
                            [
                                'type' => 'text',
                                'text' => '{{member_code}}',
                                'size' => 'xl',
                                'weight' => 'bold',
                                'margin' => 'sm',
                                'align' => 'center',
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'backgroundColor' => '#E8F5E9',
                        'cornerRadius' => 'md',
                        'paddingAll' => 'md',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '🎫 รหัสแนะนำของคุณ',
                                'size' => 'xs',
                                'color' => '#2E7D32',
                            ],
                            [
                                'type' => 'text',
                                'text' => '{{referral_code}}',
                                'size' => 'xl',
                                'weight' => 'bold',
                                'color' => '#1DB446',
                                'margin' => 'sm',
                                'align' => 'center',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'ใช้ชวนเพื่อนได้เลย!',
                                'size' => 'xxs',
                                'color' => '#2E7D32',
                                'align' => 'center',
                                'margin' => 'sm',
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => '3 ขั้นตอนเริ่มต้น:',
                        'weight' => 'bold',
                        'size' => 'sm',
                        'margin' => 'xl',
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'md',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '1️⃣ อัพเดทโปรไฟล์ของคุณ',
                                'size' => 'xs',
                                'color' => '#666666',
                            ],
                            [
                                'type' => 'text',
                                'text' => '2️⃣ แชร์รหัสแนะนำเพื่อน',
                                'size' => 'xs',
                                'color' => '#666666',
                            ],
                            [
                                'type' => 'text',
                                'text' => '3️⃣ รับรายได้และโบนัส',
                                'size' => 'xs',
                                'color' => '#666666',
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
                            'label' => '🚀 เข้าสู่ระบบ',
                            'uri' => 'https://your-domain.com/login',
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => '📖 ดูคู่มือเต็ม',
                            'data' => 'action=full_guide',
                        ],
                        'style' => 'secondary',
                    ],
                ],
            ],
        ];
    }
}
