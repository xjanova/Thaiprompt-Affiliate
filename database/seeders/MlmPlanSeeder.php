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
     * - แผนคอมมิชชันถูกกำหนดโดยตรงใน MlmGlobalSettings
     *
     * 📌 CRITICAL FIX: ต้องมี Default Plan อย่างน้อย 1 อัน
     * เพราะ mlm_members.mlm_plan_id เป็น NOT NULL และต้อง foreign key ไปหา mlm_plans
     * ถ้าไม่มี MlmPlan จะไม่สามารถสมัครสมาชิกได้!
     *
     * หมายเหตุ:
     * - MlmPackage = แพคเกจสำหรับสมาชิก (Bronze, Silver, Gold, etc.)
     *   ดู MlmPackageSeeder.php
     */
    public function run(): void
    {
        $this->command->info('🔧 กำลังตั้งค่า MLM Plans...');

        // ✅ CRITICAL: ต้องมี Default Plan เพื่อให้ระบบสมัครสมาชิกทำงานได้
        // เพราะ mlm_members.mlm_plan_id เป็น NOT NULL
        $defaultPlan = MlmPlan::where('slug', 'default-plan')->first();

        if (! $defaultPlan) {
            $defaultPlan = MlmPlan::create([
                'name' => 'Default Plan',
                'name_th' => 'แผนมาตรฐาน',
                'slug' => 'default-plan',
                'description' => 'Default commission plan for all members',
                'description_th' => 'แผนคอมมิชชันมาตรฐานสำหรับสมาชิกทุกคน',
                'type' => 'hybrid',
                'is_active' => true,
                'is_default' => true,
                'color' => '#6366f1',
                'icon' => 'fa-crown',
                'sort_order' => 1,
                'joining_fee' => 0,
                'requires_joining_fee' => false,
            ]);
            $this->command->info('✅ สร้าง Default Plan สำเร็จ: '.$defaultPlan->name);
        } else {
            // อัพเดทให้เป็น default
            $defaultPlan->update([
                'is_active' => true,
                'is_default' => true,
            ]);
            $this->command->info('ℹ️  Default Plan มีอยู่แล้ว: '.$defaultPlan->name);
        }

        $this->command->info('ℹ️  ระบบใช้แผนคอมมิชชัน Global ที่ฮาร์ดโค้ดในโค้ด (MlmGlobalSettings)');
        $this->command->info('ℹ️  Default Plan นี้ใช้สำหรับ foreign key reference เท่านั้น');
    }
}
