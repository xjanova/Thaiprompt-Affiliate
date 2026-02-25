<?php

namespace Database\Seeders;

use App\Models\FortuneTellingSetting;
use Illuminate\Database\Seeder;

class FortuneCommissionSeeder extends Seeder
{
    /**
     * ตั้งค่าเริ่มต้น Level 1/Level 2 commission สำหรับระบบดูดวง
     *
     * อัพเดทค่า default ให้กับ fortune_telling_settings ที่มีอยู่แล้ว
     * - Level 1 (สายตรง): fixed 10 บาท
     * - Level 2 (หลาน): fixed 5 บาท, เปิดใช้งาน
     */
    public function run(): void
    {
        $this->command->info('🔮 กำลัง seed ค่าเริ่มต้น Fortune Level 1/Level 2 Commission...');

        // อัพเดท settings ที่มีอยู่ (ไม่ overwrite ถ้ามีค่าแล้ว)
        $settings = FortuneTellingSetting::all();

        if ($settings->isEmpty()) {
            $this->command->info('ไม่พบ FortuneTellingSetting — ข้าม (จะถูกสร้างโดย FortuneTellingSettingSeeder)');

            return;
        }

        $updated = 0;
        foreach ($settings as $setting) {
            $changed = false;

            // ตั้งค่า Level 1 เฉพาะเมื่อยังไม่มีค่า
            if ($setting->fortune_level1_commission_type === null) {
                $setting->fortune_level1_commission_type = 'fixed';
                $changed = true;
            }
            if ($setting->fortune_level1_commission_amount === null) {
                $setting->fortune_level1_commission_amount = 10;
                $changed = true;
            }

            // ตั้งค่า Level 2 เฉพาะเมื่อยังไม่มีค่า
            if ($setting->fortune_level2_enabled === null) {
                $setting->fortune_level2_enabled = true;
                $changed = true;
            }
            if ($setting->fortune_level2_commission_type === null) {
                $setting->fortune_level2_commission_type = 'fixed';
                $changed = true;
            }
            if ($setting->fortune_level2_commission_amount === null) {
                $setting->fortune_level2_commission_amount = 5;
                $changed = true;
            }

            if ($changed) {
                $setting->save();
                $updated++;
            }
        }

        $this->command->info("✅ อัพเดท Fortune Commission Settings สำเร็จ! ({$updated}/{$settings->count()} records)");
    }
}
