<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PageBuilder;
use App\Models\PageBuilderSection;

class HomepageImportSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🏠 Importing current homepage to Page Builder...');

        // สร้างหรืออัพเดท Homepage Page Builder
        $homepage = PageBuilder::updateOrCreate(
            [
                'page_type' => 'homepage',
                'slug' => 'home',
            ],
            [
                'name' => 'หน้าแรก',
                'is_active' => true,
                'meta_data' => [
                    'title' => 'หน้าแรก | Windows Theme',
                    'description' => 'แพลตฟอร์ม Affiliate Marketing ครบวงจร มั่นคง จ่ายจริง พร้อมระบบจัดการที่ทันสมัยที่สุด',
                ],
                'settings' => [
                    'layout' => 'windows',
                    'show_taskbar' => true,
                ],
            ]
        );

        // ลบ sections เดิมทั้งหมด (ถ้ามี)
        $homepage->sections()->delete();

        // Section 1: Hero Section
        PageBuilderSection::create([
            'page_builder_id' => $homepage->id,
            'section_type' => 'hero_premium',
            'name' => 'Hero Section - Premium',
            'order' => 0,
            'is_active' => true,
            'is_visible' => true,
            'settings' => [
                'background' => 'gradient',
                'gradient_from' => '#581c87', // purple-900
                'gradient_to' => '#1e3a8a', // blue-900
                'text_align' => 'center',
                'padding_y' => 'min-h-screen',
                'has_logo' => true,
                'has_animated_background' => true,
            ],
            'content' => [
                'logo' => 'images/logo.svg',
                'title' => 'สร้างรายได้ไม่จำกัด',
                'title_gradient' => true,
                'subtitle' => 'กับระบบ Affiliate ที่ทรงพลัง',
                'description' => 'แพลตฟอร์มครบวงจร • มั่นคง • จ่ายจริง<br>พร้อมระบบจัดการที่ทันสมัยที่สุด',
                'cta_primary_text' => '🚀 เริ่มต้นฟรีวันนี้',
                'cta_primary_url' => '/register',
                'cta_secondary_text' => 'เข้าสู่ระบบ',
                'cta_secondary_url' => '/login',
                'feature_cards' => [
                    [
                        'icon' => '💼',
                        'title' => 'ระบบ Affiliate',
                        'description' => 'หาเงินออนไลน์ รายได้ไม่จำกัด',
                        'url' => '/user/dashboard',
                        'gradient_from' => '#9333ea', // purple-600
                        'gradient_to' => '#db2777', // pink-600
                    ],
                    [
                        'icon' => '🛒',
                        'title' => 'ซื้อซอฟแวร์',
                        'description' => 'ระบบครบวงจร ราคาคุ้มค่า',
                        'url' => '/software/products',
                        'badge' => 'ใหม่!',
                        'gradient_from' => '#2563eb', // blue-600
                        'gradient_to' => '#0891b2', // cyan-600
                    ],
                    [
                        'icon' => '🔮',
                        'title' => 'ทาโร่ต์ AI',
                        'description' => 'ทำนายดวงชะตา ฟรีทุกวัน',
                        'url' => '/tarot',
                        'gradient_from' => '#4f46e5', // indigo-600
                        'gradient_to' => '#9333ea', // purple-600
                    ],
                    [
                        'icon' => '📊',
                        'title' => 'แพลตฟอร์ม',
                        'description' => 'ข้อมูลระบบและฟีเจอร์',
                        'url' => '/about-professional',
                        'gradient_from' => '#db2777', // pink-600
                        'gradient_to' => '#ea580c', // orange-600
                    ],
                ],
                'trust_badges' => [
                    ['icon' => 'check', 'text' => 'ปลอดภัย 100%'],
                    ['icon' => 'check', 'text' => 'จ่ายจริง ตรงเวลา'],
                    ['icon' => 'users', 'text' => 'สมาชิก 10,000+'],
                ],
            ],
        ]);

        // Section 2: Statistics Section
        PageBuilderSection::create([
            'page_builder_id' => $homepage->id,
            'section_type' => 'statistics_realtime',
            'name' => 'สถิติแบบ Realtime',
            'order' => 1,
            'is_active' => true,
            'is_visible' => true,
            'settings' => [
                'background' => 'white',
                'gradient_from' => '#ffffff',
                'gradient_to' => '#ffffff',
                'columns' => 4,
                'use_realtime_data' => true,
            ],
            'content' => [
                'title' => 'สถิติสด',
                'subtitle' => 'อัพเดทแบบเรียลไทม์',
                'stats' => [
                    [
                        'icon' => '👥',
                        'label' => 'สมาชิกทั้งหมด',
                        'value' => 'total_users',
                        'value_type' => 'dynamic',
                        'suffix' => '+',
                        'color' => 'blue',
                    ],
                    [
                        'icon' => '🤝',
                        'label' => 'Affiliates',
                        'value' => 'total_affiliates',
                        'value_type' => 'dynamic',
                        'suffix' => '+',
                        'color' => 'purple',
                    ],
                    [
                        'icon' => '💰',
                        'label' => 'คอมมิชชั่นที่จ่าย',
                        'value' => 'total_commissions',
                        'value_type' => 'dynamic',
                        'prefix' => '฿',
                        'color' => 'green',
                    ],
                    [
                        'icon' => '🎯',
                        'label' => 'อัตราความสำเร็จ',
                        'value' => 'success_rate',
                        'value_type' => 'dynamic',
                        'suffix' => '%',
                        'color' => 'pink',
                    ],
                ],
            ],
        ]);

        // Section 3: Features Section
        PageBuilderSection::create([
            'page_builder_id' => $homepage->id,
            'section_type' => 'features',
            'name' => 'คุณสมบัติเด่น',
            'order' => 2,
            'is_active' => true,
            'is_visible' => true,
            'settings' => [
                'columns' => 3,
                'icon_style' => 'gradient',
                'card_style' => 'glass',
                'background' => 'gradient',
                'gradient_from' => '#1e1b4b', // indigo-950
                'gradient_to' => '#581c87', // purple-900
            ],
            'content' => [
                'title' => 'ทำไมต้องเลือกเรา?',
                'subtitle' => 'คุณสมบัติที่ทำให้เราแตกต่าง',
                'features' => [
                    [
                        'icon' => '🚀',
                        'title' => 'ระบบอัตโนมัติ',
                        'description' => 'คำนวณคอมมิชชั่นและจ่ายเงินอัตโนมัติ ไม่ต้องรอนาน',
                    ],
                    [
                        'icon' => '🔒',
                        'title' => 'ปลอดภัยสูงสุด',
                        'description' => 'ระบบรักษาความปลอดภัยระดับธนาคาร SSL 256-bit',
                    ],
                    [
                        'icon' => '💎',
                        'title' => 'ระบบ MLM',
                        'description' => 'ระบบ Multi-Level Marketing แบบไร้ขีดจำกัด',
                    ],
                    [
                        'icon' => '📱',
                        'title' => 'รองรับมือถือ',
                        'description' => 'ใช้งานได้ทุกที่ ทุกเวลา บนทุกอุปกรณ์',
                    ],
                    [
                        'icon' => '🎁',
                        'title' => 'โบนัสพิเศษ',
                        'description' => 'โปรโมชั่นและโบนัสมากมายตลอดทั้งปี',
                    ],
                    [
                        'icon' => '📞',
                        'title' => 'ซัพพอร์ต 24/7',
                        'description' => 'ทีมงานพร้อมช่วยเหลือตลอด 24 ชั่วโมง',
                    ],
                ],
            ],
        ]);

        // Section 4: Investment Plans (ถ้ามี)
        PageBuilderSection::create([
            'page_builder_id' => $homepage->id,
            'section_type' => 'content_block',
            'name' => 'แผนการลงทุน',
            'order' => 3,
            'is_active' => true,
            'is_visible' => true,
            'settings' => [
                'background' => 'gradient',
                'gradient_from' => '#581c87',
                'gradient_to' => '#9f1239',
                'padding_y' => 'py-20',
                'use_dynamic_content' => true,
            ],
            'content' => [
                'title' => 'แผนการลงทุน ROI',
                'description' => 'เลือกแพ็กเกจที่เหมาะกับคุณ รับผลตอบแทนรายเดือน',
                'content_type' => 'investment_plans',
            ],
        ]);

        // Section 5: CTA Section
        PageBuilderSection::create([
            'page_builder_id' => $homepage->id,
            'section_type' => 'cta',
            'name' => 'Call to Action',
            'order' => 4,
            'is_active' => true,
            'is_visible' => true,
            'settings' => [
                'background' => 'gradient',
                'gradient_from' => '#667eea',
                'gradient_to' => '#764ba2',
                'text_align' => 'center',
                'padding_y' => 'py-20',
            ],
            'content' => [
                'title' => 'พร้อมเริ่มต้นแล้วหรือยัง?',
                'description' => 'เข้าร่วมกับเรา และเริ่มสร้างรายได้วันนี้',
                'cta_text' => 'สมัครสมาชิกฟรี',
                'cta_url' => '/register',
            ],
        ]);

        $this->command->info('✅ Homepage imported successfully!');
        $this->command->info("   📄 Page ID: {$homepage->id}");
        $this->command->info("   📦 Sections: {$homepage->sections()->count()}");
    }
}
