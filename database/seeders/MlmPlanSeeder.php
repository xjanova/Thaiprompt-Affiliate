<?php

namespace Database\Seeders;

use App\Models\MlmPlan;
use Illuminate\Database\Seeder;

class MlmPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ⚠️ สำคัญ: ระบบใช้แผนคอมมิชชัน Global ที่ฮาร์ดโค้ดในโค้ด
     * - ไม่ต้องจัดการแผนคอมมิชชันผ่าน UI
     * - ไม่ต้อง seed ข้อมูลแผนคอมมิชชันลงฐานข้อมูล
     * - แผนคอมมิชชันถูกกำหนดโดยตรงใน MlmGlobalSettings
     *
     * 📌 Special Case - Intentional Cleanup Seeder:
     * This seeder INTENTIONALLY deletes all MLM Plans because the system
     * uses hardcoded commission plans in the code, not database records.
     * This is NOT following Smart Seeding Guidelines because deletion is the
     * intended behavior for this specific case.
     *
     * หมายเหตุ:
     * - MlmPackage = แพคเกจสำหรับสมาชิก (Bronze, Silver, Gold, etc.)
     *   ดู MlmPackageSeeder.php
     */
    public function run(): void
    {
        // ลบข้อมูลแผนคอมมิชชันทั้งหมดออกจากฐานข้อมูล
        // (Intentional deletion - this is a cleanup seeder)
        $this->command->info('🗑️  Cleaning all MLM Plans from database...');
        MlmPlan::query()->delete();

        $this->command->info('✅ Cleaned MLM Plans table');
        $this->command->info('ℹ️  ระบบใช้แผนคอมมิชชัน Global ที่ฮาร์ดโค้ดในโค้ด ไม่ต้องจัดการผ่าน UI');
        $this->command->info('ℹ️  หากต้องการดูแพคเกจสมาชิก ให้รัน: php artisan db:seed --class=MlmPackageSeeder');
    }
}
