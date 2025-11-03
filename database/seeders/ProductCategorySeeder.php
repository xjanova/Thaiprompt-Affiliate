<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'คอร์สเรียน AI',
                'slug' => 'ai-courses',
                'description' => 'คอร์สเรียนเกี่ยวกับปัญญาประดิษฐ์และ Machine Learning',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'E-Books',
                'slug' => 'ebooks',
                'description' => 'หนังสืออิเล็กทรอนิกส์ด้านเทคโนโลยีและธุรกิจ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Templates',
                'slug' => 'templates',
                'description' => 'เทมเพลตสำหรับงานต่างๆ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Prompt Engineering',
                'slug' => 'prompt-engineering',
                'description' => 'คอร์สและเครื่องมือสำหรับ Prompt Engineering',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => 1, // Sub-category of AI Courses
            ],
            [
                'name' => 'ChatGPT Mastery',
                'slug' => 'chatgpt-mastery',
                'description' => 'เรียนรู้การใช้งาน ChatGPT อย่างมืออาชีพ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => 1, // Sub-category of AI Courses
            ],
            [
                'name' => 'Business Templates',
                'slug' => 'business-templates',
                'description' => 'เทมเพลตสำหรับธุรกิจ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => 3, // Sub-category of Templates
            ],
            [
                'name' => 'Marketing Materials',
                'slug' => 'marketing-materials',
                'description' => 'สื่อการตลาดและเครื่องมือ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Software & Tools',
                'slug' => 'software-tools',
                'description' => 'ซอฟต์แวร์และเครื่องมือต่างๆ',
                'is_active' => true,
                'image_url' => null,
                'parent_id' => null,
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }

        $this->command->info('Product categories seeded successfully!');
    }
}
