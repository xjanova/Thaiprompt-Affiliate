<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Models\HomepageTemplate;
use Illuminate\Database\Seeder;

/**
 * HomepageManagerSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ Homepage Manager
 * รวมถึง default sections และ templates สำเร็จรูป
 */
class HomepageManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏠 กำลัง seed ข้อมูล Homepage Manager...');

        // ตรวจสอบว่ามี sections อยู่แล้วหรือไม่
        if (HomepageSection::exists()) {
            $this->command->info('   ข้อมูล Homepage Sections มีอยู่แล้ว ข้าม...');
        } else {
            $this->seedDefaultSections();
        }

        // ตรวจสอบว่ามี templates อยู่แล้วหรือไม่
        if (HomepageTemplate::exists()) {
            $this->command->info('   ข้อมูล Homepage Templates มีอยู่แล้ว ข้าม...');
        } else {
            $this->seedDefaultTemplates();
        }

        $this->command->info('✅ Seed ข้อมูล Homepage Manager สำเร็จ!');
    }

    /**
     * สร้าง default sections สำหรับหน้าแรก
     */
    protected function seedDefaultSections(): void
    {
        $this->command->info('   สร้าง Default Sections...');

        // 1. Hero Section
        $hero = HomepageSection::create([
            'name' => 'Hero Section',
            'type' => 'hero',
            'order' => 0,
            'is_active' => true,
            'is_fullwidth' => true,
            'min_height' => '600px',
            'padding' => '100px 20px',
            'background_type' => 'gradient',
            'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'background_color' => '#667eea',
            'background_color_dark' => '#1e1b4b',
            'animation' => 'fadeIn',
        ]);

        // Hero Elements
        $hero->elements()->createMany([
            [
                'name' => 'Main Heading',
                'type' => 'heading',
                'order' => 0,
                'content' => 'ยินดีต้อนรับสู่ระบบ Affiliate',
                'content_en' => 'Welcome to Affiliate System',
                'position_type' => 'relative',
                'font_size' => '48px',
                'font_weight' => '700',
                'text_align' => 'center',
                'color' => '#ffffff',
                'color_dark' => '#ffffff',
                'animation' => 'fadeInDown',
                'animation_delay' => 0,
                'settings' => ['heading_level' => 'h1'],
            ],
            [
                'name' => 'Subtitle',
                'type' => 'text',
                'order' => 1,
                'content' => 'ระบบจัดการธุรกิจออนไลน์ครบวงจร สร้างรายได้อย่างยั่งยืน',
                'content_en' => 'Complete Online Business Management System for Sustainable Income',
                'position_type' => 'relative',
                'font_size' => '20px',
                'text_align' => 'center',
                'color' => 'rgba(255,255,255,0.9)',
                'color_dark' => 'rgba(255,255,255,0.9)',
                'animation' => 'fadeInUp',
                'animation_delay' => 200,
                'margin' => '20px auto',
                'max_width' => '600px',
            ],
            [
                'name' => 'CTA Button',
                'type' => 'button',
                'order' => 2,
                'content' => 'เริ่มต้นใช้งาน',
                'content_en' => 'Get Started',
                'position_type' => 'relative',
                'link_url' => '/register',
                'font_size' => '18px',
                'font_weight' => '600',
                'text_align' => 'center',
                'color' => '#667eea',
                'color_dark' => '#667eea',
                'background_color' => '#ffffff',
                'background_color_dark' => '#ffffff',
                'padding' => '15px 40px',
                'border_radius' => '50px',
                'animation' => 'fadeInUp',
                'animation_delay' => 400,
                'margin' => '30px auto 0',
                'hover_effects' => [
                    'transform' => 'translateY(-2px)',
                    'box_shadow' => '0 10px 30px rgba(0,0,0,0.2)',
                ],
            ],
        ]);

        // 2. Features Section
        $features = HomepageSection::create([
            'name' => 'Features Section',
            'type' => 'features',
            'order' => 1,
            'is_active' => true,
            'is_fullwidth' => false,
            'min_height' => '500px',
            'padding' => '80px 20px',
            'background_type' => 'color',
            'background_color' => '#f8fafc',
            'background_color_dark' => '#111827',
            'animation' => 'fadeIn',
        ]);

        $features->elements()->createMany([
            [
                'name' => 'Features Title',
                'type' => 'heading',
                'order' => 0,
                'content' => 'ฟีเจอร์เด่นของระบบ',
                'content_en' => 'Key Features',
                'position_type' => 'relative',
                'font_size' => '36px',
                'font_weight' => '700',
                'text_align' => 'center',
                'color' => '#1f2937',
                'color_dark' => '#f9fafb',
                'animation' => 'fadeIn',
                'margin' => '0 0 50px 0',
                'settings' => ['heading_level' => 'h2'],
            ],
            [
                'name' => 'Feature 1 - MLM',
                'type' => 'container',
                'order' => 1,
                'content' => '<div class="text-center p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg"><i class="fas fa-network-wired text-4xl text-indigo-500 mb-4"></i><h3 class="text-xl font-bold mb-2">ระบบ MLM</h3><p class="text-gray-600 dark:text-gray-400">รองรับทั้ง Unilevel และ Binary พร้อมคำนวณคอมมิชชั่นอัตโนมัติ</p></div>',
                'position_type' => 'relative',
                'animation' => 'fadeInUp',
                'animation_delay' => 100,
            ],
            [
                'name' => 'Feature 2 - E-Commerce',
                'type' => 'container',
                'order' => 2,
                'content' => '<div class="text-center p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg"><i class="fas fa-shopping-cart text-4xl text-green-500 mb-4"></i><h3 class="text-xl font-bold mb-2">E-Commerce</h3><p class="text-gray-600 dark:text-gray-400">ขายสินค้าออนไลน์ พร้อมระบบจัดการสต็อกและการจัดส่ง</p></div>',
                'position_type' => 'relative',
                'animation' => 'fadeInUp',
                'animation_delay' => 200,
            ],
            [
                'name' => 'Feature 3 - AI Bot',
                'type' => 'container',
                'order' => 3,
                'content' => '<div class="text-center p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg"><i class="fas fa-robot text-4xl text-purple-500 mb-4"></i><h3 class="text-xl font-bold mb-2">AI Bot</h3><p class="text-gray-600 dark:text-gray-400">ระบบ AI ช่วยตอบคำถามและบริการลูกค้าอัตโนมัติ</p></div>',
                'position_type' => 'relative',
                'animation' => 'fadeInUp',
                'animation_delay' => 300,
            ],
        ]);

        // 3. Stats Section
        $stats = HomepageSection::create([
            'name' => 'Statistics Section',
            'type' => 'stats',
            'order' => 2,
            'is_active' => true,
            'is_fullwidth' => true,
            'min_height' => '300px',
            'padding' => '60px 20px',
            'background_type' => 'gradient',
            'background_gradient' => 'linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%)',
            'background_color' => '#1e3a5f',
            'background_color_dark' => '#0d1b2a',
        ]);

        $stats->elements()->createMany([
            [
                'name' => 'Stat 1',
                'type' => 'container',
                'order' => 0,
                'content' => '<div class="text-center"><div class="text-4xl font-bold text-white mb-2">10,000+</div><div class="text-gray-300">สมาชิกทั้งหมด</div></div>',
                'position_type' => 'relative',
                'animation' => 'zoomIn',
                'animation_delay' => 0,
            ],
            [
                'name' => 'Stat 2',
                'type' => 'container',
                'order' => 1,
                'content' => '<div class="text-center"><div class="text-4xl font-bold text-white mb-2">฿50M+</div><div class="text-gray-300">ยอดขายรวม</div></div>',
                'position_type' => 'relative',
                'animation' => 'zoomIn',
                'animation_delay' => 100,
            ],
            [
                'name' => 'Stat 3',
                'type' => 'container',
                'order' => 2,
                'content' => '<div class="text-center"><div class="text-4xl font-bold text-white mb-2">99%</div><div class="text-gray-300">ความพึงพอใจ</div></div>',
                'position_type' => 'relative',
                'animation' => 'zoomIn',
                'animation_delay' => 200,
            ],
            [
                'name' => 'Stat 4',
                'type' => 'container',
                'order' => 3,
                'content' => '<div class="text-center"><div class="text-4xl font-bold text-white mb-2">24/7</div><div class="text-gray-300">บริการตลอด</div></div>',
                'position_type' => 'relative',
                'animation' => 'zoomIn',
                'animation_delay' => 300,
            ],
        ]);

        // 4. CTA Section
        $cta = HomepageSection::create([
            'name' => 'Call to Action',
            'type' => 'cta',
            'order' => 3,
            'is_active' => true,
            'is_fullwidth' => true,
            'min_height' => '300px',
            'padding' => '80px 20px',
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'background_color_dark' => '#1f2937',
        ]);

        $cta->elements()->createMany([
            [
                'name' => 'CTA Title',
                'type' => 'heading',
                'order' => 0,
                'content' => 'พร้อมเริ่มต้นสร้างรายได้แล้วหรือยัง?',
                'content_en' => 'Ready to Start Earning?',
                'position_type' => 'relative',
                'font_size' => '32px',
                'font_weight' => '700',
                'text_align' => 'center',
                'color' => '#1f2937',
                'color_dark' => '#f9fafb',
                'animation' => 'fadeIn',
                'settings' => ['heading_level' => 'h2'],
            ],
            [
                'name' => 'CTA Description',
                'type' => 'text',
                'order' => 1,
                'content' => 'สมัครสมาชิกฟรีวันนี้ รับสิทธิพิเศษมากมาย',
                'content_en' => 'Sign up for free today and get exclusive benefits',
                'position_type' => 'relative',
                'font_size' => '18px',
                'text_align' => 'center',
                'color' => '#6b7280',
                'color_dark' => '#9ca3af',
                'margin' => '20px 0 30px',
            ],
            [
                'name' => 'CTA Button',
                'type' => 'button',
                'order' => 2,
                'content' => 'สมัครสมาชิกฟรี',
                'content_en' => 'Sign Up Free',
                'position_type' => 'relative',
                'link_url' => '/register',
                'font_size' => '18px',
                'font_weight' => '600',
                'text_align' => 'center',
                'color' => '#ffffff',
                'color_dark' => '#ffffff',
                'background_color' => '#667eea',
                'background_color_dark' => '#667eea',
                'padding' => '15px 50px',
                'border_radius' => '50px',
                'animation' => 'fadeInUp',
                'hover_effects' => [
                    'background_color' => '#5a67d8',
                ],
            ],
        ]);

        $this->command->info('   ✓ สร้าง 4 Default Sections พร้อม Elements แล้ว');
    }

    /**
     * สร้าง default templates
     */
    protected function seedDefaultTemplates(): void
    {
        $this->command->info('   สร้าง Default Templates...');

        // Business Template
        HomepageTemplate::create([
            'name' => 'Business Professional',
            'slug' => 'business-professional',
            'category' => 'business',
            'description' => 'เทมเพลตสำหรับธุรกิจ มีความเป็นมืออาชีพ พร้อม Hero, Features, Stats และ CTA',
            'sections_data' => [
                [
                    'name' => 'Hero',
                    'type' => 'hero',
                    'min_height' => '500px',
                    'padding' => '80px 20px',
                    'background_type' => 'gradient',
                    'background_gradient' => 'linear-gradient(135deg, #1a365d 0%, #2d3748 100%)',
                    'elements' => [
                        [
                            'type' => 'heading',
                            'content' => 'ธุรกิจของคุณ',
                            'font_size' => '48px',
                            'font_weight' => '700',
                            'text_align' => 'center',
                            'color' => '#ffffff',
                            'settings' => ['heading_level' => 'h1'],
                        ],
                        [
                            'type' => 'text',
                            'content' => 'สร้างความสำเร็จไปด้วยกัน',
                            'font_size' => '20px',
                            'text_align' => 'center',
                            'color' => 'rgba(255,255,255,0.8)',
                            'margin' => '20px 0',
                        ],
                    ],
                ],
                [
                    'name' => 'Features',
                    'type' => 'features',
                    'min_height' => '400px',
                    'padding' => '60px 20px',
                    'background_type' => 'color',
                    'background_color' => '#f7fafc',
                    'elements' => [],
                ],
            ],
            'is_active' => true,
        ]);

        // Landing Page Template
        HomepageTemplate::create([
            'name' => 'Landing Page',
            'slug' => 'landing-page',
            'category' => 'landing',
            'description' => 'เทมเพลต Landing Page สำหรับโปรโมทสินค้าหรือบริการ',
            'sections_data' => [
                [
                    'name' => 'Hero',
                    'type' => 'hero',
                    'min_height' => '600px',
                    'padding' => '100px 20px',
                    'background_type' => 'gradient',
                    'background_gradient' => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)',
                    'elements' => [
                        [
                            'type' => 'heading',
                            'content' => 'สินค้าใหม่ล่าสุด!',
                            'font_size' => '56px',
                            'font_weight' => '800',
                            'text_align' => 'center',
                            'color' => '#ffffff',
                            'settings' => ['heading_level' => 'h1'],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        // Minimal Template
        HomepageTemplate::create([
            'name' => 'Minimal Clean',
            'slug' => 'minimal-clean',
            'category' => 'minimal',
            'description' => 'เทมเพลตสไตล์ Minimal เรียบง่ายแต่ทันสมัย',
            'sections_data' => [
                [
                    'name' => 'Hero',
                    'type' => 'hero',
                    'min_height' => '500px',
                    'padding' => '80px 20px',
                    'background_type' => 'color',
                    'background_color' => '#ffffff',
                    'background_color_dark' => '#111827',
                    'elements' => [
                        [
                            'type' => 'heading',
                            'content' => 'Simple. Clean. Modern.',
                            'font_size' => '48px',
                            'font_weight' => '300',
                            'text_align' => 'center',
                            'color' => '#1f2937',
                            'color_dark' => '#f9fafb',
                            'settings' => ['heading_level' => 'h1'],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $this->command->info('   ✓ สร้าง 3 Default Templates แล้ว');
    }
}
