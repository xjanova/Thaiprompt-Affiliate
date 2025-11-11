<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BotMarketplaceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'name' => 'การตลาดและโซเชียลมีเดีย',
                'slug' => 'marketing-social-media',
                'description' => 'บอทสำหรับการโพสต์คอนเทนต์, จัดการโซเชียลมีเดีย, และการตลาดออนไลน์',
                'icon' => 'fas fa-bullhorn',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ซัพพอร์ตลูกค้า',
                'slug' => 'customer-support',
                'description' => 'บอททตอบคำถาม, ดูแลลูกค้า, และจัดการ ticket ซัพพอร์ต',
                'icon' => 'fas fa-headset',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'การขายและคำปรึกษา',
                'slug' => 'sales-consulting',
                'description' => 'บอทปิดการขาย, แนะนำสินค้า, และให้คำปรึกษาลูกค้า',
                'icon' => 'fas fa-chart-line',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'สร้างคอนเทนต์',
                'slug' => 'content-creation',
                'description' => 'บอทสร้างคอนเทนต์, เขียนบทความ, และสร้างภาพด้วย AI',
                'icon' => 'fas fa-pen-fancy',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'อีคอมเมิร์ซ',
                'slug' => 'e-commerce',
                'description' => 'บอทจัดการออเดอร์, แนะนำสินค้า, และซัพพอร์ตร้านค้าออนไลน์',
                'icon' => 'fas fa-shopping-cart',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'การศึกษาและอบรม',
                'slug' => 'education-training',
                'description' => 'บอทสอน, ทดสอบความรู้, และให้ความรู้แบบอัตโนมัติ',
                'icon' => 'fas fa-graduation-cap',
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'บริการทางการเงิน',
                'slug' => 'financial-services',
                'description' => 'บอทให้คำปรึกษาทางการเงิน, วิเคราะห์การลงทุน, และแจ้งเตือนทางการเงิน',
                'icon' => 'fas fa-coins',
                'sort_order' => 7,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'สุขภาพและความงาม',
                'slug' => 'health-beauty',
                'description' => 'บอทให้คำปรึกษาด้านสุขภาพ, ความงาม, และการดูแลตนเอง',
                'icon' => 'fas fa-spa',
                'sort_order' => 8,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ความบันเทิง',
                'slug' => 'entertainment',
                'description' => 'บอทเล่นเกม, สร้างความบันเทิง, และโต้ตอบแบบสนุกสนาน',
                'icon' => 'fas fa-gamepad',
                'sort_order' => 9,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'อื่นๆ',
                'slug' => 'others',
                'description' => 'บอทประเภทอื่นๆ ที่ไม่อยู่ในหมวดหมู่ข้างต้น',
                'icon' => 'fas fa-ellipsis-h',
                'sort_order' => 99,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Use updateOrInsert to make this seeder idempotent
        foreach ($categories as $category) {
            DB::table('bot_marketplace_categories')->updateOrInsert(
                ['slug' => $category['slug']], // unique key
                $category
            );
        }

        $this->command->info('Bot marketplace categories seeded successfully!');
    }
}
