<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * StoreRatingSeeder
 *
 * สร้างข้อมูลตัวอย่างสำหรับระบบคะแนนร้านค้า (ถ้าต้องการ)
 * ปกติจะไม่สร้างข้อมูลจริงเพราะเป็นข้อมูลที่ผู้ใช้ต้องสร้างเอง
 */
class StoreRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 ระบบ StoreRating พร้อมใช้งาน (ไม่มีข้อมูลเริ่มต้น)');
        $this->command->info('✅ ผู้ใช้สามารถให้คะแนนร้านค้าได้ที่ /user/store-rating');
    }
}
