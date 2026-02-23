<?php

namespace Database\Seeders;

use App\Models\HoroscopeDreamCategory;
use Illuminate\Database\Seeder;

/**
 * สร้างข้อมูลหมวดหมู่ทำนายฝัน 12 หมวด
 */
class HoroscopeDreamCategorySeeder extends Seeder
{
    /**
     * สร้างข้อมูลหมวดหมู่ฝัน
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌙 กำลัง seed หมวดหมู่ทำนายฝัน...');

        if (HoroscopeDreamCategory::count() >= 10) {
            $this->command->info('หมวดหมู่ฝันมีอยู่แล้ว ข้าม...');

            return;
        }

        $categories = [
            ['name_th' => 'สัตว์', 'slug' => 'animals', 'icon' => '🐾', 'color_hex' => '#f97316', 'description_th' => 'ฝันเห็นสัตว์ต่างๆ เช่น งู เสือ ช้าง หมา แมว นก ปลา', 'sort_order' => 1],
            ['name_th' => 'คนและบุคคล', 'slug' => 'people', 'icon' => '👤', 'color_hex' => '#3b82f6', 'description_th' => 'ฝันเห็นคน ญาติ คนรัก คนที่เสียชีวิต คนแปลกหน้า', 'sort_order' => 2],
            ['name_th' => 'ธรรมชาติ', 'slug' => 'nature', 'icon' => '🌿', 'color_hex' => '#22c55e', 'description_th' => 'ฝันเห็นต้นไม้ ดอกไม้ ป่า ภูเขา ทะเล ท้องฟ้า', 'sort_order' => 3],
            ['name_th' => 'น้ำและทะเล', 'slug' => 'water', 'icon' => '🌊', 'color_hex' => '#06b6d4', 'description_th' => 'ฝันเห็นน้ำ น้ำท่วม ฝน แม่น้ำ ทะเล สระน้ำ', 'sort_order' => 4],
            ['name_th' => 'ความตายและวิญญาณ', 'slug' => 'death-spirit', 'icon' => '👻', 'color_hex' => '#7c3aed', 'description_th' => 'ฝันเห็นผี วิญญาณ ความตาย งานศพ โลงศพ', 'sort_order' => 5],
            ['name_th' => 'เงินและทรัพย์สิน', 'slug' => 'money', 'icon' => '💰', 'color_hex' => '#eab308', 'description_th' => 'ฝันเห็นเงิน ทอง เพชร สมบัติ กระเป๋าเงิน', 'sort_order' => 6],
            ['name_th' => 'อาหารและเครื่องดื่ม', 'slug' => 'food', 'icon' => '🍚', 'color_hex' => '#f59e0b', 'description_th' => 'ฝันเห็นอาหาร กินข้าว ผลไม้ เครื่องดื่ม ขนม', 'sort_order' => 7],
            ['name_th' => 'ยานพาหนะและการเดินทาง', 'slug' => 'travel', 'icon' => '🚗', 'color_hex' => '#64748b', 'description_th' => 'ฝันเห็นรถ เครื่องบิน เรือ รถไฟ การเดินทาง อุบัติเหตุ', 'sort_order' => 8],
            ['name_th' => 'บ้านและสิ่งก่อสร้าง', 'slug' => 'buildings', 'icon' => '🏠', 'color_hex' => '#a855f7', 'description_th' => 'ฝันเห็นบ้าน วัด โรงเรียน ตลาด อาคาร ห้อง', 'sort_order' => 9],
            ['name_th' => 'ศาสนาและสิ่งศักดิ์สิทธิ์', 'slug' => 'religion', 'icon' => '🙏', 'color_hex' => '#fbbf24', 'description_th' => 'ฝันเห็นพระ พระพุทธรูป วัด เทวดา นรก สวรรค์', 'sort_order' => 10],
            ['name_th' => 'ไฟและภัยพิบัติ', 'slug' => 'disaster', 'icon' => '🔥', 'color_hex' => '#ef4444', 'description_th' => 'ฝันเห็นไฟ แผ่นดินไหว พายุ ฟ้าผ่า อุบัติเหตุ', 'sort_order' => 11],
            ['name_th' => 'ความรักและครอบครัว', 'slug' => 'love-family', 'icon' => '❤️', 'color_hex' => '#ec4899', 'description_th' => 'ฝันเห็นแต่งงาน ตั้งครรภ์ เด็กทารก คนรัก หย่าร้าง', 'sort_order' => 12],
        ];

        foreach ($categories as $category) {
            HoroscopeDreamCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('✅ Seed หมวดหมู่ทำนายฝัน 12 หมวด สำเร็จ!');
    }
}
