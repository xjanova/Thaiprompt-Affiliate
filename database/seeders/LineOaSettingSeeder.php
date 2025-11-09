<?php

namespace Database\Seeders;

use App\Models\LineOaSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LineOaSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder sets up the default LINE OA settings configuration
     * for user registration via LINE Official Account.
     *
     * 📌 Smart Seeding Strategy:
     * Checks if LINE OA settings exist before seeding to preserve user configurations.
     */
    public function run(): void
    {
        // Check if LINE OA settings already exist
        $existingSetting = LineOaSetting::first();

        if ($existingSetting) {
            $this->command->warn('⚠️  LINE OA settings already exist!');
            $this->command->info('   Skipping to preserve your LINE configuration.');
            $this->command->info('   If you need to reset, please delete existing settings manually.');
            return;
        }

        // Fresh install - create default LINE OA configuration
        $this->command->info('🌱 Fresh install: Creating LINE OA settings...');

        LineOaSetting::create([
            'login_channel_id' => env('LINE_LOGIN_CHANNEL_ID', ''),
            'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
            'redirect_uri' => env('LINE_REDIRECT_URI', url('/auth/line/callback')),
            'messaging_channel_id' => env('LINE_MESSAGING_CHANNEL_ID', ''),
            'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
            'liff_id' => env('LINE_LIFF_ID', ''),
            'require_line_registration' => false,
            'enable_line_messaging' => false,
            'welcome_message' => "👋 ยินดีต้อนรับสู่ระบบ Affiliate!\n\nขอบคุณที่เพิ่มเพื่อนกับเรา คุณสามารถเข้าสู่ระบบและเริ่มสร้างรายได้ได้ทันที!",
            'registration_success_message' => "🎉 สมัครสมาชิกสำเร็จ!\n\n✨ ยินดีต้อนรับสู่ครอบครัว Affiliate ของเรา\n\n📌 ขั้นตอนถัดไป:\n• เข้าสู่ระบบเพื่อดู Dashboard\n• ตั้งค่าโปรไฟล์ของคุณ\n• เริ่มต้นแชร์ลิงก์และสร้างรายได้\n\nหากมีคำถาม สามารถติดต่อเราได้ทุกเมื่อ! 💬",
            'is_active' => true,
        ]);

        $this->command->info('✅ LINE OA settings created successfully!');
        $this->command->info('');
        $this->command->info('📝 Note: Please configure your LINE credentials in .env file:');
        $this->command->info('   - LINE_LOGIN_CHANNEL_ID');
        $this->command->info('   - LINE_CHANNEL_SECRET');
        $this->command->info('   - LINE_REDIRECT_URI');
        $this->command->info('   - LINE_MESSAGING_CHANNEL_ID (optional)');
        $this->command->info('   - LINE_CHANNEL_ACCESS_TOKEN (optional)');
        $this->command->info('   - LINE_LIFF_ID (optional)');
        $this->command->info('');
    }
}
