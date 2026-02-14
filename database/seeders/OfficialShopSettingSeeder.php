<?php

namespace Database\Seeders;

use App\Models\OfficialShopSetting;
use Illuminate\Database\Seeder;

/**
 * OfficialShopSettingSeeder
 *
 * สร้างค่าเริ่มต้นสำหรับ Official Shop Settings
 */
class OfficialShopSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Official Shop Settings...');

        // ใช้ static method จาก Model เพื่อสร้างค่าเริ่มต้น
        OfficialShopSetting::seedDefaults();

        $count = OfficialShopSetting::count();

        $this->command->info("✅ Seed ข้อมูล Official Shop Settings สำเร็จ! ({$count} รายการ)");
    }
}
