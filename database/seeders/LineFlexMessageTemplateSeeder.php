<?php

namespace Database\Seeders;

use App\Models\LineFlexMessageTemplate;
use Illuminate\Database\Seeder;

class LineFlexMessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if LINE Flex templates already exist
        $existingTemplatesCount = LineFlexMessageTemplate::whereIn('name', [
            'Welcome Message - Classic',
            'Promotion - Flash Sale',
            'Product Card'
        ])->count();

        if ($existingTemplatesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all templates
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all LINE Flex message templates...');

        $templates = $this->getAllTemplates();

        foreach ($templates as $template) {
            LineFlexMessageTemplate::create($template);
        }

        $this->command->info('✅ LINE Flex message templates seeded successfully: ' . count($templates) . ' templates');
    }

    /**
     * Update mode - add only missing templates
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing LINE Flex templates detected!');
        $this->command->info('   Running in UPDATE mode (adding missing templates only)...');

        $templates = $this->getAllTemplates();
        $added = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            if (!LineFlexMessageTemplate::where('name', $template['name'])->exists()) {
                LineFlexMessageTemplate::create($template);
                $this->command->info("   ➕ Added: {$template['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new LINE Flex templates.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing templates (preserved).");
        }
    }

    /**
     * Get all default LINE Flex message templates
     */
    private function getAllTemplates(): array
    {
        return [
            // Welcome Message
            [
                'name' => 'Welcome Message - Classic',
                'category' => 'greeting',
                'description' => 'เทมเพลตข้อความต้อนรับแบบคลาสสิก',
                'is_seed' => true,
                'is_active' => true,
                'flex_content' => [
                    'type' => 'bubble',
                    'hero' => [
                        'type' => 'image',
                        'url' => 'https://via.placeholder.com/1040x520/06C755/FFFFFF?text=Welcome',
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
                                'text' => 'ยินดีต้อนรับ! 🎉',
                                'weight' => 'bold',
                                'size' => 'xxl',
                                'color' => '#06C755',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'ขอบคุณที่เป็นส่วนหนึ่งของเรา',
                                'size' => 'md',
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
                                    [
                                        'type' => 'box',
                                        'layout' => 'baseline',
                                        'spacing' => 'sm',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => '✓',
                                                'color' => '#06C755',
                                                'weight' => 'bold',
                                                'flex' => 0,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'รับโปรโมชั่นพิเศษก่อนใคร',
                                                'wrap' => true,
                                                'size' => 'sm',
                                                'flex' => 1,
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 'box',
                                        'layout' => 'baseline',
                                        'spacing' => 'sm',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => '✓',
                                                'color' => '#06C755',
                                                'weight' => 'bold',
                                                'flex' => 0,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'ติดตามสถานะคำสั่งซื้อ',
                                                'wrap' => true,
                                                'size' => 'sm',
                                                'flex' => 1,
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 'box',
                                        'layout' => 'baseline',
                                        'spacing' => 'sm',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => '✓',
                                                'color' => '#06C755',
                                                'weight' => 'bold',
                                                'flex' => 0,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'แชทกับทีมงานได้ตลอดเวลา',
                                                'wrap' => true,
                                                'size' => 'sm',
                                                'flex' => 1,
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
                                'style' => 'primary',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => 'เริ่มต้นใช้งาน',
                                    'uri' => 'https://your-website.com',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Promotion
            [
                'name' => 'Promotion - Flash Sale',
                'category' => 'promotion',
                'description' => 'เทมเพลตโปรโมชั่น Flash Sale',
                'is_seed' => true,
                'is_active' => true,
                'flex_content' => [
                    'type' => 'bubble',
                    'size' => 'mega',
                    'header' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'FLASH SALE',
                                        'color' => '#ffffff',
                                        'size' => 'xl',
                                        'flex' => 1,
                                        'weight' => 'bold',
                                        'align' => 'center',
                                    ],
                                ],
                            ],
                        ],
                        'paddingAll' => '20px',
                        'backgroundColor' => '#FF3B3F',
                        'spacing' => 'md',
                        'height' => '80px',
                        'paddingTop' => '22px',
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '🔥 ลดสูงสุด 50%',
                                'weight' => 'bold',
                                'color' => '#FF3B3F',
                                'size' => 'xxl',
                            ],
                            [
                                'type' => 'text',
                                'text' => 'สินค้าคุณภาพ ราคาพิเศษ',
                                'size' => 'sm',
                                'color' => '#999999',
                                'margin' => 'md',
                            ],
                            [
                                'type' => 'separator',
                                'margin' => 'xxl',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'margin' => 'xxl',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'box',
                                        'layout' => 'horizontal',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => 'โค้ดส่วนลด',
                                                'size' => 'sm',
                                                'color' => '#555555',
                                                'flex' => 0,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'FLASH50',
                                                'size' => 'sm',
                                                'color' => '#FF3B3F',
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
                                                'text' => 'ใช้ได้ถึง',
                                                'size' => 'sm',
                                                'color' => '#555555',
                                                'flex' => 0,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'วันนี้เท่านั้น!',
                                                'size' => 'sm',
                                                'color' => '#FF3B3F',
                                                'align' => 'end',
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
                                'style' => 'primary',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => 'ช้อปเลย!',
                                    'uri' => 'https://your-website.com/sale',
                                ],
                                'color' => '#FF3B3F',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'contents' => [],
                                'margin' => 'sm',
                            ],
                        ],
                        'flex' => 0,
                    ],
                ],
            ],

            // Product
            [
                'name' => 'Product Card',
                'category' => 'product',
                'description' => 'เทมเพลตแสดงรายละเอียดสินค้า',
                'is_seed' => true,
                'is_active' => true,
                'flex_content' => [
                    'type' => 'bubble',
                    'hero' => [
                        'type' => 'image',
                        'url' => 'https://via.placeholder.com/1040x1040/EEEEEE/999999?text=Product+Image',
                        'size' => 'full',
                        'aspectRatio' => '1:1',
                        'aspectMode' => 'cover',
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => 'ชื่อสินค้า',
                                'weight' => 'bold',
                                'size' => 'xl',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'margin' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'icon',
                                        'size' => 'sm',
                                        'url' => 'https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '4.8',
                                        'size' => 'sm',
                                        'color' => '#999999',
                                        'margin' => 'md',
                                        'flex' => 0,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => '(256 รีวิว)',
                                        'size' => 'sm',
                                        'color' => '#999999',
                                        'margin' => 'md',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'คำอธิบายสินค้าสั้นๆ ที่น่าสนใจ มีคุณภาพดี ราคาย่อมเยา',
                                'wrap' => true,
                                'color' => '#666666',
                                'size' => 'sm',
                                'margin' => 'md',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'margin' => 'lg',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'box',
                                        'layout' => 'baseline',
                                        'spacing' => 'sm',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => 'ราคา',
                                                'color' => '#aaaaaa',
                                                'size' => 'sm',
                                                'flex' => 1,
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => '฿990',
                                                'wrap' => true,
                                                'color' => '#06C755',
                                                'size' => 'xl',
                                                'flex' => 4,
                                                'weight' => 'bold',
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
                                'style' => 'primary',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => 'ดูรายละเอียด',
                                    'uri' => 'https://your-website.com/product',
                                ],
                            ],
                            [
                                'type' => 'button',
                                'style' => 'link',
                                'height' => 'sm',
                                'action' => [
                                    'type' => 'message',
                                    'label' => 'เพิ่มลงตะกร้า',
                                    'text' => 'เพิ่มสินค้าลงตะกร้า',
                                ],
                            ],
                        ],
                        'flex' => 0,
                    ],
                ],
            ],
        ];
    }
}
