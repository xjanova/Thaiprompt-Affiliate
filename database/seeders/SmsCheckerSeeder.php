<?php

namespace Database\Seeders;

use App\Models\SmsCheckerDevice;
use App\Models\VendorStore;
use Illuminate\Database\Seeder;

/**
 * SMS Checker Device Seeder
 *
 * สร้างอุปกรณ์ทดสอบสำหรับระบบ SMS Payment Checker
 */
class SmsCheckerSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับ SMS Checker
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล SMS Checker Device...');

        // ตรวจสอบว่ามี device อยู่แล้วหรือไม่
        if (SmsCheckerDevice::where('device_id', 'SMSCHK-TESTDEV1')->exists()) {
            $this->command->info('ข้อมูล SMS Checker Device มีอยู่แล้ว ข้าม...');

            return;
        }

        // สร้างอุปกรณ์ทดสอบ — ผูกกับ Platform Store (ร้าน admin/official)
        SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-TESTDEV1',
            'device_name' => 'อุปกรณ์ทดสอบ #1',
            'api_key' => str_repeat('a1b2c3d4', 8), // 64 chars (สำหรับทดสอบเท่านั้น!)
            'secret_key' => str_repeat('e5f6g7h8', 8), // 64 chars (สำหรับทดสอบเท่านั้น!)
            'platform' => 'android',
            'app_version' => '1.0.0',
            'status' => 'active',
            'user_id' => 1,
            'store_id' => VendorStore::getPlatformStoreId(),
        ]);

        $this->command->info('✅ Seed ข้อมูล SMS Checker Device สำเร็จ!');
        $this->command->warn('⚠️  อุปกรณ์ทดสอบ: SMSCHK-TESTDEV1 (ใช้สำหรับ development เท่านั้น!)');
    }
}
