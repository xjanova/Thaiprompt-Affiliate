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
            // ============ บริการสุขภาพและความงาม ============
            [
                'name' => 'นวดและสปา',
                'slug' => 'massage-spa',
                'description' => 'บริการนวดแผนไทย นวดน้ำมัน นวดฝ่าเท้า สปาถึงบ้าน',
                'icon' => 'fas fa-spa',
                'image' => '/images/services/massage.jpg',
                'color' => '#8B5CF6',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'ตัดผมและทำผม',
                'slug' => 'hair-salon',
                'description' => 'บริการตัดผม ทำสีผม ดัดผม เกล้าผม ถึงบ้าน',
                'icon' => 'fas fa-cut',
                'image' => '/images/services/hair.jpg',
                'color' => '#EC4899',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'เสริมสวย',
                'slug' => 'beauty',
                'description' => 'บริการแต่งหน้า ทำเล็บ ต่อขนตา ถึงบ้าน',
                'icon' => 'fas fa-magic',
                'image' => '/images/services/beauty.jpg',
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
                'icon' => 'fas fa-snowflake',
                'image' => '/images/services/aircon.jpg',
                'color' => '#06B6D4',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'ช่างไฟฟ้า',
                'slug' => 'electrician',
                'description' => 'บริการซ่อมไฟฟ้า เดินสายไฟ ติดตั้งอุปกรณ์ไฟฟ้า',
                'icon' => 'fas fa-bolt',
                'image' => '/images/services/electrician.jpg',
                'color' => '#F59E0B',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'ช่างประปา',
                'slug' => 'plumber',
                'description' => 'บริการซ่อมประปา แก้ท่อตัน ติดตั้งสุขภัณฑ์',
                'icon' => 'fas fa-faucet',
                'image' => '/images/services/plumber.jpg',
                'color' => '#3B82F6',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'ซ่อมเครื่องใช้ไฟฟ้า',
                'slug' => 'appliance-repair',
                'description' => 'ซ่อมตู้เย็น เครื่องซักผ้า ไมโครเวฟ และเครื่องใช้ไฟฟ้าอื่นๆ',
                'icon' => 'fas fa-tv',
                'image' => '/images/services/appliance.jpg',
                'color' => '#6366F1',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'ช่างทั่วไป',
                'slug' => 'handyman',
                'description' => 'งานซ่อมแซมทั่วไป ประกอบเฟอร์นิเจอร์ ติดตั้งของ',
                'icon' => 'fas fa-tools',
                'image' => '/images/services/handyman.jpg',
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
                'icon' => 'fas fa-broom',
                'image' => '/images/services/cleaning.jpg',
                'color' => '#10B981',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'ล้างรถ',
                'slug' => 'car-wash',
                'description' => 'บริการล้างรถถึงบ้าน ขัดเคลือบ ดูดฝุ่น',
                'icon' => 'fas fa-car',
                'image' => '/images/services/carwash.jpg',
                'color' => '#0EA5E9',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'ซักรีด',
                'slug' => 'laundry',
                'description' => 'บริการรับ-ส่งซักรีด ซักแห้ง รีดผ้า',
                'icon' => 'fas fa-tshirt',
                'image' => '/images/services/laundry.jpg',
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
                'icon' => 'fas fa-utensils',
                'image' => '/images/services/food.jpg',
                'color' => '#EF4444',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'ส่งพัสดุ',
                'slug' => 'parcel-delivery',
                'description' => 'บริการรับส่งพัสดุ เอกสาร ของด่วน',
                'icon' => 'fas fa-box',
                'image' => '/images/services/delivery.jpg',
                'color' => '#F97316',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'ขนย้าย',
                'slug' => 'moving',
                'description' => 'บริการขนย้ายบ้าน สำนักงาน รถกระบะ',
                'icon' => 'fas fa-truck',
                'image' => '/images/services/moving.jpg',
                'color' => '#84CC16',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 14,
            ],
            [
                'name' => 'เรียกรถ',
                'slug' => 'ride-hailing',
                'description' => 'บริการเรียกรถรับส่ง มอเตอร์ไซค์ รถยนต์',
                'icon' => 'fas fa-motorcycle',
                'image' => '/images/services/ride.jpg',
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
                'icon' => 'fas fa-chalkboard-teacher',
                'image' => '/images/services/tutoring.jpg',
                'color' => '#A855F7',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'สอนฟิตเนส',
                'slug' => 'fitness-training',
                'description' => 'เทรนเนอร์ส่วนตัว โยคะ พิลาทิส ถึงบ้าน',
                'icon' => 'fas fa-dumbbell',
                'image' => '/images/services/fitness.jpg',
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
                'icon' => 'fas fa-paw',
                'image' => '/images/services/pet.jpg',
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
                'icon' => 'fas fa-user-nurse',
                'image' => '/images/services/nursing.jpg',
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
                'icon' => 'fas fa-camera',
                'image' => '/images/services/photo.jpg',
                'color' => '#1E293B',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'ดูแลสวน',
                'slug' => 'gardening',
                'description' => 'บริการตัดหญ้า ดูแลสวน ปลูกต้นไม้',
                'icon' => 'fas fa-leaf',
                'image' => '/images/services/garden.jpg',
                'color' => '#16A34A',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 21,
            ],
            [
                'name' => 'อื่นๆ',
                'slug' => 'others',
                'description' => 'บริการอื่นๆ ที่ไม่อยู่ในหมวดหมู่ข้างต้น',
                'icon' => 'fas fa-ellipsis-h',
                'image' => '/images/services/others.jpg',
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
