<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * ServiceCategorySeeder
 *
 * สร้างหมวดหมู่บริการเริ่มต้น พร้อมไอคอน SVG ที่สวยงาม
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
            // ============ บริการสุขภาพและความงาม ============
            [
                'name' => 'นวดและสปา',
                'slug' => 'massage-spa',
                'description' => 'บริการนวดแผนไทย นวดน้ำมัน นวดฝ่าเท้า สปาถึงบ้าน',
                'icon' => '/icons/services/massage-spa.svg',
                'image' => '/icons/services/massage-spa.svg',
                'color' => '#8B5CF6',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'ตัดผมและทำผม',
                'slug' => 'hair-salon',
                'description' => 'บริการตัดผม ทำสีผม ดัดผม เกล้าผม ถึงบ้าน',
                'icon' => '/icons/services/hair-salon.svg',
                'image' => '/icons/services/hair-salon.svg',
                'color' => '#EC4899',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'เสริมสวย',
                'slug' => 'beauty',
                'description' => 'บริการแต่งหน้า ทำเล็บ ต่อขนตา ถึงบ้าน',
                'icon' => '/icons/services/beauty.svg',
                'image' => '/icons/services/beauty.svg',
                'color' => '#F472B6',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],

            // ============ บริการซ่อมบำรุง ============
            [
                'name' => 'ซ่อมแอร์',
                'slug' => 'air-conditioner',
                'description' => 'บริการซ่อมแอร์ ล้างแอร์ ติดตั้งแอร์ เติมน้ำยา',
                'icon' => '/icons/services/air-conditioner.svg',
                'image' => '/icons/services/air-conditioner.svg',
                'color' => '#06B6D4',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'ช่างไฟฟ้า',
                'slug' => 'electrician',
                'description' => 'บริการซ่อมไฟฟ้า เดินสายไฟ ติดตั้งอุปกรณ์ไฟฟ้า',
                'icon' => '/icons/services/electrician.svg',
                'image' => '/icons/services/electrician.svg',
                'color' => '#F59E0B',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'ช่างประปา',
                'slug' => 'plumber',
                'description' => 'บริการซ่อมประปา แก้ท่อตัน ติดตั้งสุขภัณฑ์',
                'icon' => '/icons/services/plumber.svg',
                'image' => '/icons/services/plumber.svg',
                'color' => '#3B82F6',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'ซ่อมเครื่องใช้ไฟฟ้า',
                'slug' => 'appliance-repair',
                'description' => 'ซ่อมตู้เย็น เครื่องซักผ้า ไมโครเวฟ และเครื่องใช้ไฟฟ้าอื่นๆ',
                'icon' => '/icons/services/appliance-repair.svg',
                'image' => '/icons/services/appliance-repair.svg',
                'color' => '#6366F1',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'ช่างทั่วไป',
                'slug' => 'handyman',
                'description' => 'งานซ่อมแซมทั่วไป ประกอบเฟอร์นิเจอร์ ติดตั้งของ',
                'icon' => '/icons/services/handyman.svg',
                'image' => '/icons/services/handyman.svg',
                'color' => '#78716C',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 8,
            ],

            // ============ บริการทำความสะอาด ============
            [
                'name' => 'ทำความสะอาดบ้าน',
                'slug' => 'house-cleaning',
                'description' => 'บริการทำความสะอาดบ้าน คอนโด สำนักงาน',
                'icon' => '/icons/services/house-cleaning.svg',
                'image' => '/icons/services/house-cleaning.svg',
                'color' => '#10B981',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'ล้างรถ',
                'slug' => 'car-wash',
                'description' => 'บริการล้างรถถึงบ้าน ขัดเคลือบ ดูดฝุ่น',
                'icon' => '/icons/services/car-wash.svg',
                'image' => '/icons/services/car-wash.svg',
                'color' => '#0EA5E9',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'ซักรีด',
                'slug' => 'laundry',
                'description' => 'บริการรับ-ส่งซักรีด ซักแห้ง รีดผ้า',
                'icon' => '/icons/services/laundry.svg',
                'image' => '/icons/services/laundry.svg',
                'color' => '#14B8A6',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 11,
            ],

            // ============ บริการขนส่ง ============
            [
                'name' => 'ส่งอาหาร',
                'slug' => 'food-delivery',
                'description' => 'บริการสั่งอาหารและเครื่องดื่มส่งถึงที่',
                'icon' => '/icons/services/food-delivery.svg',
                'image' => '/icons/services/food-delivery.svg',
                'color' => '#EF4444',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'ส่งพัสดุ',
                'slug' => 'parcel-delivery',
                'description' => 'บริการรับส่งพัสดุ เอกสาร ของด่วน',
                'icon' => '/icons/services/parcel-delivery.svg',
                'image' => '/icons/services/parcel-delivery.svg',
                'color' => '#F97316',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'ขนย้าย',
                'slug' => 'moving',
                'description' => 'บริการขนย้ายบ้าน สำนักงาน รถกระบะ',
                'icon' => '/icons/services/moving.svg',
                'image' => '/icons/services/moving.svg',
                'color' => '#84CC16',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 14,
            ],
            [
                'name' => 'เรียกรถ',
                'slug' => 'ride-hailing',
                'description' => 'บริการเรียกรถรับส่ง มอเตอร์ไซค์ รถยนต์',
                'icon' => '/icons/services/ride-hailing.svg',
                'image' => '/icons/services/ride-hailing.svg',
                'color' => '#22C55E',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 15,
            ],

            // ============ บริการการศึกษา ============
            [
                'name' => 'สอนพิเศษ',
                'slug' => 'tutoring',
                'description' => 'ติวเตอร์สอนพิเศษ วิชาการ ภาษา ดนตรี ศิลปะ',
                'icon' => '/icons/services/tutoring.svg',
                'image' => '/icons/services/tutoring.svg',
                'color' => '#A855F7',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'สอนฟิตเนส',
                'slug' => 'fitness-training',
                'description' => 'เทรนเนอร์ส่วนตัว โยคะ พิลาทิส ถึงบ้าน',
                'icon' => '/icons/services/fitness-training.svg',
                'image' => '/icons/services/fitness-training.svg',
                'color' => '#DC2626',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 17,
            ],

            // ============ บริการสัตว์เลี้ยง ============
            [
                'name' => 'บริการสัตว์เลี้ยง',
                'slug' => 'pet-services',
                'description' => 'อาบน้ำ ตัดขน รับเลี้ยง พาเดินเล่น',
                'icon' => '/icons/services/pet-services.svg',
                'image' => '/icons/services/pet-services.svg',
                'color' => '#FB923C',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 18,
            ],

            // ============ บริการสุขภาพ ============
            [
                'name' => 'พยาบาลดูแลผู้ป่วย',
                'slug' => 'nursing-care',
                'description' => 'บริการพยาบาลดูแลผู้สูงอายุ ผู้ป่วยพักฟื้น',
                'icon' => '/icons/services/nursing-care.svg',
                'image' => '/icons/services/nursing-care.svg',
                'color' => '#F43F5E',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 19,
            ],

            // ============ บริการอื่นๆ ============
            [
                'name' => 'ช่างภาพ',
                'slug' => 'photography',
                'description' => 'ถ่ายรูป วิดีโอ งานอีเวนท์ ภาพบุคคล',
                'icon' => '/icons/services/photography.svg',
                'image' => '/icons/services/photography.svg',
                'color' => '#1E293B',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'ดูแลสวน',
                'slug' => 'gardening',
                'description' => 'บริการตัดหญ้า ดูแลสวน ปลูกต้นไม้',
                'icon' => '/icons/services/gardening.svg',
                'image' => '/icons/services/gardening.svg',
                'color' => '#16A34A',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 21,
            ],
            [
                'name' => 'อื่นๆ',
                'slug' => 'others',
                'description' => 'บริการอื่นๆ ที่ไม่อยู่ในหมวดหมู่ข้างต้น',
                'icon' => '/icons/services/others.svg',
                'image' => '/icons/services/others.svg',
                'color' => '#6B7280',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 99,
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
