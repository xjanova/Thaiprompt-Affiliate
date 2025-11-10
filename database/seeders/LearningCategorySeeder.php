<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningCategory;
use Illuminate\Support\Str;

class LearningCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing learning categories only, preserves existing configurations.
     * User customizations (names, colors, icons, order) are preserved.
     */
    public function run(): void
    {
        $existingCategoriesCount = LearningCategory::count();

        if ($existingCategoriesCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all learning categories
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all learning categories...');

        $categories = $this->getAllCategories();

        foreach ($categories as $category) {
            LearningCategory::create($category);
        }

        $this->command->info('✅ Learning categories seeded successfully: ' . count($categories) . ' categories');
    }

    /**
     * Update mode - add only missing learning categories
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing learning categories detected!');
        $this->command->info('   Running in UPDATE mode (adding missing categories only)...');

        $categories = $this->getAllCategories();
        $added = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if (!LearningCategory::where('slug', $category['slug'])->exists()) {
                LearningCategory::create($category);
                $this->command->info("   ➕ Added: {$category['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new learning categories.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing categories (preserved).");
        }
    }

    /**
     * Get all default learning categories
     */
    private function getAllCategories(): array
    {
        return [
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
                'description' => 'การสร้างและบริหารธุรกิจออนไลน์อย่างมืออาชีพ',
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
    }
}
