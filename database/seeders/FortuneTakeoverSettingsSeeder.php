<?php

namespace Database\Seeders;

use App\Models\FortuneTellingSetting;
use Illuminate\Database\Seeder;

/**
 * FortuneTakeoverSettingsSeeder
 *
 * ตั้งค่าเริ่มต้นสำหรับระบบเทคโอเวอร์ (แม่หมอ/แอดมินคุยแทน AI)
 * Seeder นี้ idempotent — รันซ้ำได้โดยไม่ทับข้อมูลผู้ใช้
 */
class FortuneTakeoverSettingsSeeder extends Seeder
{
    /**
     * สร้างค่าเริ่มต้นสำหรับระบบเทคโอเวอร์
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ค่าเริ่มต้น Fortune Takeover...');

        // ดึง settings (สร้างใหม่ถ้ายังไม่มี)
        $settings = FortuneTellingSetting::getSettings();

        $defaults = [
            'ai_resume_command' => '/ai',
            'customer_handoff_keywords' => [
                'คุยกับคน',
                'คุยกับแอดมิน',
                'คุยกับแม่หมอ',
                'คุยกับเจ้าหน้าที่',
                'ขอคุยกับคน',
                'ขอคุยกับแม่หมอ',
                'ขอแม่หมอ',
                'ต้องการพูดกับคน',
                'อยากคุยกับคน',
                'ติดต่อแอดมิน',
                'ขอแอดมิน',
                'admin',
            ],
            'takeover_notify_customer' => true,
            'takeover_customer_message' => '🙏 สวัสดี แม่หมอเข้ามาดูแลเอง ขอสักครู่นะ 💜',
            'takeover_resume_message' => '✨ ระบบอัจฉริยะกลับมาดูแลต่อแล้ว พิมพ์สอบถามได้เลย',
        ];

        $updateData = [];
        foreach ($defaults as $key => $value) {
            // เติมเฉพาะค่าที่ยังไม่ได้ตั้ง (เพื่อไม่ทับข้อมูลผู้ใช้)
            if ($settings->{$key} === null || $settings->{$key} === '' || $settings->{$key} === []) {
                $updateData[$key] = $value;
            }
        }

        if (! empty($updateData)) {
            $settings->update($updateData);
            $this->command->info('✅ เพิ่มค่าเริ่มต้น '.count($updateData).' รายการ');
        } else {
            $this->command->info('⏭ ค่าทั้งหมดมีอยู่แล้ว — ข้าม');
        }

        // Clear cache เพื่อให้ load ค่าใหม่
        FortuneTellingSetting::clearSettingsCache();

        $this->command->info('✅ Seed Fortune Takeover Settings สำเร็จ!');
    }
}
