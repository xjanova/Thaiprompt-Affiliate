<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class DemoHomeSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating demo home sections...');

        // Hero Section
        HomeSection::firstOrCreate(
            ['name' => 'hero-main'],
            [
                'title' => 'สร้างรายได้กับ ThaiPrompt Affiliate',
                'subtitle' => 'เริ่มต้นสร้างรายได้ผ่านระบบแนะนำสินค้า ง่าย สะดวก รับเงินจริง',
                'content' => null,
                'type' => 'hero',
                'settings' => [
                    'background_color' => '#6366f1',
                    'text_color' => '#ffffff',
                    'button_text' => 'เริ่มต้นฟรีวันนี้',
                    'button_link' => '/register',
                ],
                'allowed_roles' => null, // ทุกคนเห็นได้
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        // Features Section
        HomeSection::firstOrCreate(
            ['name' => 'features-main'],
            [
                'title' => 'ทำไมต้องเลือกเรา',
                'subtitle' => 'ฟีเจอร์ครบครัน ใช้งานง่าย',
                'content' => json_encode([
                    [
                        'icon' => '💰',
                        'title' => 'รายได้สูง',
                        'description' => 'รับค่าคอมมิชชั่นสูงถึง 20% ต่อการขาย',
                    ],
                    [
                        'icon' => '📊',
                        'title' => 'รายงานแบบเรียลไทม์',
                        'description' => 'ติดตามยอดขายและค่าคอมมิชชั่นได้ทุกเวลา',
                    ],
                    [
                        'icon' => '🚀',
                        'title' => 'ง่ายต่อการใช้งาน',
                        'description' => 'ระบบใช้งานง่าย เริ่มต้นได้ทันที',
                    ],
                    [
                        'icon' => '🔒',
                        'title' => 'ปลอดภัย',
                        'description' => 'ระบบรักษาความปลอดภัยระดับสูง',
                    ],
                ]),
                'type' => 'features',
                'settings' => null,
                'allowed_roles' => null,
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        // Statistics Section
        HomeSection::firstOrCreate(
            ['name' => 'stats-main'],
            [
                'title' => 'ผลงานที่น่าประทับใจ',
                'subtitle' => 'ตัวเลขที่พูดแทนเรา',
                'content' => json_encode([
                    ['label' => 'ผู้ใช้ทั้งหมด', 'value' => '10,000+', 'icon' => '👥'],
                    ['label' => 'รายได้รวม', 'value' => '฿50M+', 'icon' => '💰'],
                    ['label' => 'คอมมิชชั่นจ่าย', 'value' => '฿10M+', 'icon' => '💸'],
                    ['label' => 'ความพึงพอใจ', 'value' => '98%', 'icon' => '⭐'],
                ]),
                'type' => 'statistics',
                'settings' => [
                    'background_color' => '#f3f4f6',
                ],
                'allowed_roles' => null,
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        // CTA Section (For Members Only)
        HomeSection::firstOrCreate(
            ['name' => 'cta-member'],
            [
                'title' => 'พร้อมเริ่มต้นสร้างรายได้แล้วหรือยัง?',
                'subtitle' => 'เข้าสู่ระบบเพื่อเริ่มแนะนำสินค้าและรับค่าคอมมิชชั่นได้ทันที',
                'content' => null,
                'type' => 'cta',
                'settings' => [
                    'button_text' => 'เข้าสู่ระบบ',
                    'button_link' => '/login',
                    'button_color' => '#10b981',
                ],
                'allowed_roles' => ['admin', 'affiliate', 'user'], // เฉพาะสมาชิก
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        // Testimonials Section
        HomeSection::firstOrCreate(
            ['name' => 'testimonials-main'],
            [
                'title' => 'ความคิดเห็นจากลูกค้า',
                'subtitle' => 'สิ่งที่ลูกค้าของเราบอกเล่า',
                'content' => json_encode([
                    [
                        'name' => 'คุณสมชาย ใจดี',
                        'role' => 'Affiliate',
                        'avatar' => 'S',
                        'message' => 'ระบบใช้งานง่ายมาก รายได้เข้าสม่ำเสมอ แนะนำเลยครับ',
                        'rating' => 5,
                    ],
                    [
                        'name' => 'คุณสมหญิง รักงาน',
                        'role' => 'Affiliate Manager',
                        'avatar' => 'ส',
                        'message' => 'ทำงานสบายมาก ค่าคอมมิชชั่นสูงกว่าที่อื่น',
                        'rating' => 5,
                    ],
                    [
                        'name' => 'คุณวิชัย ขยัน',
                        'role' => 'Top Affiliate',
                        'avatar' => 'ว',
                        'message' => 'เป็นแพลตฟอร์มที่ดีที่สุดที่เคยใช้ รายงานครบถ้วน',
                        'rating' => 5,
                    ],
                ]),
                'type' => 'testimonials',
                'settings' => null,
                'allowed_roles' => null,
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Created 5 demo home sections');
        $this->command->info('');
    }
}
