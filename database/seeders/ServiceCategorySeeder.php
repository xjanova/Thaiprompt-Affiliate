<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * ServiceCategorySeeder
 *
 * สร้างหมวดหมู่บริการเริ่มต้น
 */
class ServiceCategorySeeder extends Seeder
{
    /**
     * รันการ seed
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed หมวดหมู่บริการ...');

        $categories = [
            [
                'name' => 'นวดและสปา',
                'slug' => 'massage-spa',
                'description' => 'บริการนวดแผนไทย นวดน้ำมัน สปาผ่อนคลาย',
                'icon' => '💆',
                'image' => '/images/services/massage.jpg',
                'color' => '#E91E63',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'ทำความสะอาด',
                'slug' => 'cleaning',
                'description' => 'บริการทำความสะอาดบ้าน คอนโด สำนักงาน',
                'icon' => '🧹',
                'image' => '/images/services/cleaning.jpg',
                'color' => '#4CAF50',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'จัดส่งสินค้า',
                'slug' => 'delivery',
                'description' => 'บริการจัดส่งพัสดุ ของกิน ของใช้ รวดเร็วทันใจ',
                'icon' => '🚚',
                'image' => '/images/services/delivery.jpg',
                'color' => '#FF9800',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'ซ่อมบำรุง',
                'slug' => 'repair-maintenance',
                'description' => 'บริการซ่อมไฟฟ้า ประปา แอร์ เครื่องใช้ไฟฟ้า',
                'icon' => '🔧',
                'image' => '/images/services/repair.jpg',
                'color' => '#2196F3',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'ดูแลสัตว์เลี้ยง',
                'slug' => 'pet-care',
                'description' => 'บริการอาบน้ำสัตว์เลี้ยง ตัดขน ดูแลสุขภาพ',
                'icon' => '🐕',
                'image' => '/images/services/pet-care.jpg',
                'color' => '#9C27B0',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'ความงาม',
                'slug' => 'beauty',
                'description' => 'บริการทำผม เล็บ เมคอัพ ถึงบ้าน',
                'icon' => '💅',
                'image' => '/images/services/beauty.jpg',
                'color' => '#FF5722',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'ดูแลผู้สูงอายุ',
                'slug' => 'elderly-care',
                'description' => 'บริการดูแลผู้สูงอายุ พี่เลี้ยงเด็ก แม่บ้าน',
                'icon' => '👵',
                'image' => '/images/services/elderly-care.jpg',
                'color' => '#795548',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'ติวเสริม',
                'slug' => 'tutoring',
                'description' => 'บริการติวเสริม สอนพิเศษ ถึงบ้าน',
                'icon' => '📚',
                'image' => '/images/services/tutoring.jpg',
                'color' => '#607D8B',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $existing = ServiceCategory::where('slug', $category['slug'])->first();

            if ($existing) {
                $this->command->warn("  ⚠️  หมวดหมู่ '{$category['name']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            ServiceCategory::create($category);
            $this->command->info("  ✅ สร้างหมวดหมู่ '{$category['name']}' สำเร็จ");
        }

        $total = ServiceCategory::count();
        $this->command->info("✅ Seed หมวดหมู่บริการสำเร็จ! รวม {$total} หมวดหมู่");
    }
}
