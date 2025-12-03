<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * SiteSettingSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่าเว็บไซต์
 * ป้องกัน settings หายหลัง deploy
 */
class SiteSettingSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้น Site Settings
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Site Settings...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (SiteSetting::count() > 0) {
            $this->command->info('  ✓ Site Settings มีอยู่แล้ว ข้าม...');
            return;
        }

        // สร้างการตั้งค่าเริ่มต้น
        SiteSetting::create([
            'site_name' => 'TP-Affiliate',
            'site_description' => 'ระบบ Affiliate Marketing ที่ทรงพลังที่สุด',
            'logo_spin' => false,
            'maintenance_mode' => false,
            'meta_keywords' => 'affiliate, mlm, marketing, commission, ระบบ affiliate, ระบบคอมมิชชั่น',
            'meta_description' => 'TP-Affiliate - ระบบ Affiliate Marketing และ MLM ที่ครบครันและทรงพลังที่สุดในประเทศไทย',
        ]);

        $this->command->info('✅ Seed ข้อมูล Site Settings สำเร็จ!');
    }
}
