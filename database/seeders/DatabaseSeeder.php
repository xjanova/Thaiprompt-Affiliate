<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Starting database seeding...');
        $this->command->info('');

        // Seed demo data in order
        $this->call([
            DemoUsersSeeder::class,        // สร้างผู้ใช้ทดสอบ
            DemoAffiliatesSeeder::class,   // สร้าง affiliates
            DemoCommissionsSeeder::class,  // สร้าง commissions
            DemoPagesSeeder::class,        // สร้างหน้าเพจต่างๆ
            SeoMetaSeeder::class,          // สร้าง SEO meta data
            TestUsersSeeder::class,        // สร้างผู้ใช้ทดสอบเพิ่มเติม (backward compatibility)
            EmailTemplateSeeder::class,    // สร้าง Email Templates สำหรับระบบส่งอีเมล
            AiProvidersSeeder::class,      // สร้าง AI Providers และ Models (OpenAI, Claude, DeepSeek, Gemini)
            MenuItemSeeder::class,         // สร้างเมนูสำหรับ Header
            MlmGlobalSettingsSeeder::class, // สร้างการตั้งค่า MLM พรีเมี่ยม
            MlmPlanSeeder::class,          // สร้างแผนแพคเกจ MLM (Hybrid, Unilevel, Binary)

            // Product & E-commerce Seeders
            ProductCategorySeeder::class,  // สร้างหมวดหมู่สินค้า (ต้องมาก่อน ProductSeeder)
            ProductSeeder::class,          // สร้างสินค้าตัวอย่าง

            // Academy Platform Seeders
            LearningCategorySeeder::class,  // สร้างหมวดหมู่คอร์ส
            LearningArticleSeeder::class,   // สร้างคอร์สและบทความ
            QuizSeeder::class,              // สร้าง Quiz และคำถาม
        ]);

        $this->command->info('');
        $this->command->info('✨ Database seeding completed successfully!');
        $this->command->info('');
    }
}
