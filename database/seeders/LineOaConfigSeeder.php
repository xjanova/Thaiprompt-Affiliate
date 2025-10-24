<?php

namespace Database\Seeders;

use App\Models\LineOaConfig;
use Illuminate\Database\Seeder;

class LineOaConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LineOaConfig::create([
            'name' => 'Default LINE OA Configuration',
            'channel_id' => env('LINE_CHANNEL_ID', ''),
            'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
            'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
            'webhook_url' => env('APP_URL') . '/api/webhooks/line',
            'is_active' => false, // Set to false by default, admin needs to configure
            'require_kyc_for_withdrawal' => true,
            'min_withdrawal_without_kyc' => 1000.00,
            'welcome_message' => [
                'type' => 'text',
                'text' => "ยินดีต้อนรับสู่ระบบ!\n\nกรุณายืนยันตัวตนเพื่อใช้งานฟีเจอร์การถอนเงิน\n\nส่งข้อความว่า 'ยืนยันตัวตน' หรือ 'verify' เพื่อเริ่มต้น",
            ],
            'kyc_success_message' => [
                'type' => 'text',
                'text' => "✅ ยืนยันตัวตนสำเร็จ!\n\nคุณสามารถใช้งานระบบถอนเงินได้เต็มรูปแบบแล้ว\n\nขอบคุณที่ใช้บริการของเรา",
            ],
            'settings' => [
                'auto_verify' => true,
                'require_friend_add' => true,
                'send_withdrawal_notifications' => true,
            ],
        ]);

        $this->command->info('LINE OA configuration seeded successfully!');
        $this->command->warn('Please update LINE_CHANNEL_ID, LINE_CHANNEL_SECRET, and LINE_CHANNEL_ACCESS_TOKEN in .env file');
    }
}
