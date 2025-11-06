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
                'name' => 'อิเล็กทรอนิกส์',
                'slug' => 'electronics',
                'description' => 'สินค้าอิเล็กทรอนิกส์และอุปกรณ์เทคโนโลยี รวมถึงคอมพิวเตอร์ แท็บเล็ต สมาร์ทโฟน',
                'is_active' => true,
            ],
            [
                'name' => 'แฟชั่นและเครื่องแต่งกาย',
                'slug' => 'fashion-and-apparel',
                'description' => 'เสื้อผ้า กระเป๋า รองเท้า และเครื่องประดับสำหรับทุกเพศทุกวัย',
                'is_active' => true,
            ],
            [
                'name' => 'ความงามและของใช้ส่วนตัว',
                'slug' => 'beauty-and-personal-care',
                'description' => 'เครื่องสำอาง ผลิตภัณฑ์ดูแลผิว น้ำหอม และของใช้ส่วนตัว',
                'is_active' => true,
            ],
            [
                'name' => 'บ้านและสวน',
                'slug' => 'home-and-garden',
                'description' => 'เฟอร์นิเจอร์ ของตกแต่งบ้าน อุปกรณ์จัดสวน และเครื่องใช้ในบ้าน',
                'is_active' => true,
            ],
            [
                'name' => 'กีฬาและกิจกรรมกลางแจ้ง',
                'slug' => 'sports-and-outdoors',
                'description' => 'อุปกรณ์กีฬา เครื่องออกกำลังกาย อุปกรณ์ตั้งแคมป์ และกลางแจ้ง',
                'is_active' => true,
            ],
            [
                'name' => 'หนังสือและเครื่องเขียน',
                'slug' => 'books-and-stationery',
                'description' => 'หนังสือ นิตยสาร อุปกรณ์เครื่องเขียน และอุปกรณ์สำนักงาน',
                'is_active' => true,
            ],
            [
                'name' => 'ของเล่นและงานอดิเรก',
                'slug' => 'toys-and-hobbies',
                'description' => 'ของเล่นเด็ก โมเดล บอร์ดเกม และอุปกรณ์งานอดิเรก',
                'is_active' => true,
            ],
            [
                'name' => 'อาหารและเครื่องดื่ม',
                'slug' => 'food-and-beverages',
                'description' => 'อาหารสำเร็จรูป ขนมขบเคี้ยว เครื่องดื่ม และของว่าง',
                'is_active' => true,
            ],
            [
                'name' => 'สุขภาพและอาหารเสริม',
                'slug' => 'health-and-supplements',
                'description' => 'วิตามิน อาหารเสริม ยา และผลิตภัณฑ์เพื่อสุขภาพ',
                'is_active' => true,
            ],
            [
                'name' => 'สัตว์เลี้ยง',
                'slug' => 'pets',
                'description' => 'อาหารสัตว์เลี้ยง ของเล่น อุปกรณ์ดูแล และเครื่องใช้สำหรับสัตว์เลี้ยง',
                'is_active' => true,
            ],
            [
                'name' => 'แม่และเด็ก',
                'slug' => 'mother-and-baby',
                'description' => 'ผลิตภัณฑ์สำหรับแม่และเด็ก อุปกรณ์เลี้ยงลูก และของใช้เด็กอ่อน',
                'is_active' => true,
            ],
            [
                'name' => 'เครื่องใช้ไฟฟ้าในบ้าน',
                'slug' => 'home-appliances',
                'description' => 'เครื่องใช้ไฟฟ้า อุปกรณ์ในครัว และเครื่องใช้ในบ้าน',
                'is_active' => true,
            ],
            [
                'name' => 'รถยนต์และมอเตอร์ไซค์',
                'slug' => 'automotive',
                'description' => 'อุปกรณ์ตกแต่งรถยนต์ อะไหล่ และอุปกรณ์ดูแลรักษา',
                'is_active' => true,
            ],
            [
                'name' => 'กล้องและอุปกรณ์ถ่ายภาพ',
                'slug' => 'cameras-and-photography',
                'description' => 'กล้องถ่ายรูป กล้องวิดีโอ เลนส์ ขาตั้ง และอุปกรณ์เสริม',
                'is_active' => true,
            ],
            [
                'name' => 'นาฬิกาและแว่นตา',
                'slug' => 'watches-and-eyewear',
                'description' => 'นาฬิกาข้อมือ นาฬิกาตั้งโต๊ะ แว่นตา และอุปกรณ์เสริม',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $index => $category) {
            ProductCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => $category['is_active'],
                    'sort_order' => $index + 1,
                    'parent_id' => null,
                    'image_url' => null,
                ]
            );
        }

        $this->command->info('✓ Created ' . count($categories) . ' product categories successfully!');
    }
}
