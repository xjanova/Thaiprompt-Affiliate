<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ServiceSeeder
 *
 * สร้างบริการตัวอย่าง
 */
class ServiceSeeder extends Seeder
{
    /**
     * รันการ seed
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed บริการตัวอย่าง...');

        // หา owner และ category
        $owner = User::where('role', 'admin')->first() ?? User::first();

        if (!$owner) {
            $this->command->error('❌ ไม่พบ User สำหรับเป็น owner บริการ');
            return;
        }

        $massageCategory = ServiceCategory::where('slug', 'massage-spa')->first();
        $cleaningCategory = ServiceCategory::where('slug', 'cleaning')->first();
        $deliveryCategory = ServiceCategory::where('slug', 'delivery')->first();

        if (!$massageCategory || !$cleaningCategory || !$deliveryCategory) {
            $this->command->error('❌ ไม่พบ ServiceCategory กรุณารัน ServiceCategorySeeder ก่อน');
            return;
        }

        $services = [
            // นวดและสปา
            [
                'category_id' => $massageCategory->id,
                'owner_id' => $owner->id,
                'name' => 'นวดแผนไทย 1 ชั่วโมง',
                'slug' => 'thai-massage-1hr',
                'description' => 'นวดแผนไทยแท้ 1 ชั่วโมง ผ่อนคลายกล้ามเนื้อ บรรเทาอาการปวดเมื่อย',
                'base_price' => 350.00,
                'duration_minutes' => 60,
                'images' => ['/images/services/thai-massage.jpg'],
                'options' => [
                    ['name' => 'น้ำมันหอมระเหย', 'price' => 50],
                    ['name' => 'ขัดผิว', 'price' => 100],
                ],
                'requires_location' => true,
                'is_active' => true,
                'is_featured' => true,
                'min_booking_hours' => 2,
                'cancellation_hours' => 3,
                'commission_rate' => 10.00,
            ],
            [
                'category_id' => $massageCategory->id,
                'owner_id' => $owner->id,
                'name' => 'นวดน้ำมันอโรมา 90 นาที',
                'slug' => 'aroma-oil-massage-90min',
                'description' => 'นวดน้ำมันหอมระเหย ผ่อนคลายสุดๆ พร้อมน้ำมันหอมกลิ่นหอม',
                'base_price' => 550.00,
                'duration_minutes' => 90,
                'images' => ['/images/services/aroma-massage.jpg'],
                'options' => [
                    ['name' => 'ประคบสมุนไพร', 'price' => 150],
                ],
                'requires_location' => true,
                'is_active' => true,
                'is_featured' => true,
                'min_booking_hours' => 3,
                'cancellation_hours' => 4,
                'commission_rate' => 10.00,
            ],

            // ทำความสะอาด
            [
                'category_id' => $cleaningCategory->id,
                'owner_id' => $owner->id,
                'name' => 'ทำความสะอาดบ้าน ขนาดเล็ก',
                'slug' => 'house-cleaning-small',
                'description' => 'ทำความสะอาดบ้านขนาดเล็ก 1-2 ห้องนอน พื้นที่ไม่เกิน 50 ตร.ม.',
                'base_price' => 500.00,
                'duration_minutes' => 120,
                'images' => ['/images/services/house-cleaning.jpg'],
                'options' => [
                    ['name' => 'ทำความสะอาดห้องครัว', 'price' => 200],
                    ['name' => 'ล้างห้องน้ำ', 'price' => 150],
                    ['name' => 'เช็ดกระจก', 'price' => 100],
                ],
                'requires_location' => true,
                'is_active' => true,
                'is_featured' => true,
                'min_booking_hours' => 6,
                'cancellation_hours' => 12,
                'commission_rate' => 15.00,
            ],
            [
                'category_id' => $cleaningCategory->id,
                'owner_id' => $owner->id,
                'name' => 'ทำความสะอาดคอนโด',
                'slug' => 'condo-cleaning',
                'description' => 'ทำความสะอาดคอนโด สตูดิโอ - 1 ห้องนอน พื้นที่ไม่เกิน 40 ตร.ม.',
                'base_price' => 400.00,
                'duration_minutes' => 90,
                'images' => ['/images/services/condo-cleaning.jpg'],
                'options' => [
                    ['name' => 'ซักผ้าม่าน', 'price' => 250],
                ],
                'requires_location' => true,
                'is_active' => true,
                'is_featured' => false,
                'min_booking_hours' => 4,
                'cancellation_hours' => 8,
                'commission_rate' => 15.00,
            ],

            // จัดส่ง
            [
                'category_id' => $deliveryCategory->id,
                'owner_id' => $owner->id,
                'name' => 'จัดส่งด่วนภายในเมือง',
                'slug' => 'express-delivery',
                'description' => 'บริการจัดส่งด่วน รับ-ส่ง ภายใน 1-2 ชั่วโมง',
                'base_price' => 80.00,
                'duration_minutes' => 60,
                'images' => ['/images/services/express-delivery.jpg'],
                'options' => [
                    ['name' => 'ประกันสินค้า', 'price' => 30],
                    ['name' => 'บริการ COD', 'price' => 20],
                ],
                'requires_location' => true,
                'is_active' => true,
                'is_featured' => true,
                'min_booking_hours' => 1,
                'cancellation_hours' => 1,
                'commission_rate' => 20.00,
            ],
        ];

        foreach ($services as $service) {
            // ตรวจสอบว่ามีบริการนี้อยู่แล้วหรือไม่
            $existing = Service::where('slug', $service['slug'])->first();

            if ($existing) {
                $this->command->warn("  ⚠️  บริการ '{$service['name']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            Service::create($service);
            $this->command->info("  ✅ สร้างบริการ '{$service['name']}' สำเร็จ");
        }

        $total = Service::count();
        $this->command->info("✅ Seed บริการตัวอย่างสำเร็จ! รวม {$total} บริการ");
    }
}
