<?php

namespace App\Console\Commands;

use App\Models\SmsCheckerDevice;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * สร้าง SMS Checker Device สำหรับเชื่อมต่อกับ Android App
 *
 * คำสั่งนี้จะสร้าง device record พร้อม api_key และ secret_key
 * ที่ admin ต้องนำไปใส่ใน Android App เพื่อเริ่มรับ SMS notifications
 */
class SmsCheckerCreateDeviceCommand extends Command
{
    /**
     * ชื่อคำสั่งและ arguments
     *
     * @var string
     */
    protected $signature = 'smschecker:create-device
                            {name : ชื่ออุปกรณ์ (เช่น "Samsung Galaxy S24")}
                            {--user= : User ID ของเจ้าของอุปกรณ์ (ไม่บังคับ)}';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'สร้าง SMS Checker Device พร้อม API Key และ Secret Key สำหรับ Android App';

    /**
     * รันคำสั่ง
     *
     * @return int
     */
    public function handle(): int
    {
        $deviceName = $this->argument('name');
        $userId = $this->option('user');

        // ตรวจสอบ user ถ้ามีการระบุ
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ ไม่พบผู้ใช้ ID: {$userId}");
                return Command::FAILURE;
            }
            $this->info("👤 เจ้าของอุปกรณ์: {$user->name} (ID: {$user->id})");
        }

        // สร้าง keys
        $apiKey = SmsCheckerDevice::generateApiKey();
        $secretKey = SmsCheckerDevice::generateSecretKey();
        $deviceId = 'SMSCHK-' . strtoupper(bin2hex(random_bytes(4)));

        // สร้าง device record
        $device = SmsCheckerDevice::create([
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'api_key' => $apiKey,
            'secret_key' => $secretKey,
            'platform' => 'android',
            'status' => 'active',
            'user_id' => $userId,
        ]);

        $this->newLine();
        $this->info('✅ สร้าง SMS Checker Device สำเร็จ!');
        $this->newLine();

        $this->table(
            ['รายการ', 'ค่า'],
            [
                ['Device ID', $device->device_id],
                ['Device Name', $device->device_name],
                ['API Key', $apiKey],
                ['Secret Key', $secretKey],
                ['Status', $device->status],
                ['User ID', $device->user_id ?? '-'],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  กรุณาคัดลอก API Key และ Secret Key ไปตั้งค่าใน Android App');
        $this->warn('⚠️  ข้อมูลเหล่านี้จะไม่สามารถดูได้อีกหลังจากนี้!');
        $this->newLine();

        return Command::SUCCESS;
    }
}
