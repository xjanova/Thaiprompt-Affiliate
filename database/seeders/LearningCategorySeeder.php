<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningCategory;
use Illuminate\Support\Str;

class LearningCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'เริ่มต้นใช้งาน AI',
                'slug' => 'getting-started-ai',
                'description' => 'เรียนรู้พื้นฐาน AI และการใช้งาน ChatGPT, Midjourney และเครื่องมือ AI อื่นๆ',
                'icon' => '🤖',
                'color' => '#667eea',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Affiliate Marketing',
                'slug' => 'affiliate-marketing',
                'description' => 'เทคนิคการตลาดออนไลน์และสร้างรายได้แบบพาสซีฟด้วย Affiliate',
                'icon' => '💰',
                'color' => '#f093fb',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'การตลาดดิจิทัล',
                'slug' => 'digital-marketing',
                'description' => 'กลยุทธ์การตลาดออนไลน์, SEO, Social Media Marketing',
                'icon' => '📱',
                'color' => '#4facfe',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'สร้างเนื้อหา',
                'slug' => 'content-creation',
                'description' => 'เทคนิคการสร้างเนื้อหาที่ดึงดูดและขายได้',
                'icon' => '✍️',
                'color' => '#43e97b',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'ธุรกิจออนไลน์',
                'slug' => 'online-business',
                'description' => 'การสร้างและบริหารธุรกิจออนไนน์อย่างมืออาชีพ',
                'icon' => '💼',
                'color' => '#fa709a',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'เครื่องมือและเทคโนโลยี',
                'slug' => 'tools-technology',
                'description' => 'เรียนรู้การใช้เครื่องมือและเทคโนโลยีสมัยใหม่',
                'icon' => '🔧',
                'color' => '#30cfd0',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            LearningCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Learning categories seeded successfully!');
    }
}
