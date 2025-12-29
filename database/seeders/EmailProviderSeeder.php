<?php

namespace Database\Seeders;

use App\Models\EmailProvider;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับสร้าง Email Providers เริ่มต้น
 *
 * สร้าง providers 3 ตัว: SMTP, Gmail SMTP, Gmail API
 */
class EmailProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ตรวจสอบว่ามี providers อยู่แล้วหรือไม่
        $existingProvidersCount = EmailProvider::whereIn('name', [
            'smtp',
            'gmail_smtp',
            'gmail_api',
        ])->count();

        if ($existingProvidersCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - สร้าง providers ทั้งหมด
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: กำลังสร้าง Email Providers...');

        $providers = $this->getAllProviders();

        foreach ($providers as $provider) {
            EmailProvider::create($provider);
        }

        $this->command->info('✅ สร้าง Email Providers สำเร็จ: '.count($providers).' providers');
    }

    /**
     * Update mode - เพิ่มเฉพาะ providers ที่ยังไม่มี
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  พบ Email Providers ที่มีอยู่แล้ว!');
        $this->command->info('   Running in UPDATE mode (เพิ่มเฉพาะ providers ที่ขาด)...');

        $providers = $this->getAllProviders();
        $added = 0;
        $skipped = 0;

        foreach ($providers as $provider) {
            if (! EmailProvider::where('name', $provider['name'])->exists()) {
                EmailProvider::create($provider);
                $this->command->info("   ➕ เพิ่ม: {$provider['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ เพิ่ม {$added} providers ใหม่");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  ข้าม {$skipped} providers ที่มีอยู่แล้ว");
        }
    }

    /**
     * รายการ providers ทั้งหมด
     */
    private function getAllProviders(): array
    {
        return [
            // Generic SMTP Provider (Default)
            [
                'name' => 'smtp',
                'display_name' => 'SMTP Server',
                'type' => 'smtp',
                'configuration' => [
                    'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                    'port' => env('MAIL_PORT', 2525),
                    'username' => env('MAIL_USERNAME', ''),
                    'password' => env('MAIL_PASSWORD', ''),
                    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                    'from_email' => env('MAIL_FROM_ADDRESS', 'hello@tp-affiliate.local'),
                    'from_name' => env('MAIL_FROM_NAME', 'TP-Affiliate'),
                    'smtp_auth' => true,
                ],
                'is_active' => true,
                'is_default' => true,
                'priority' => 50,
                'daily_limit' => 1000,
                'hourly_limit' => 100,
                'sent_today' => 0,
                'sent_this_hour' => 0,
            ],

            // Gmail SMTP Provider
            [
                'name' => 'gmail_smtp',
                'display_name' => 'Gmail SMTP',
                'type' => 'smtp',
                'configuration' => [
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'username' => env('GMAIL_SMTP_USERNAME', ''),
                    'password' => env('GMAIL_SMTP_PASSWORD', ''),
                    'encryption' => 'tls',
                    'from_email' => env('GMAIL_SMTP_FROM_EMAIL', ''),
                    'from_name' => env('GMAIL_SMTP_FROM_NAME', 'TP-Affiliate'),
                    'smtp_auth' => true,
                ],
                'is_active' => false, // ปิดเป็นค่าเริ่มต้น ต้อง configure ก่อน
                'is_default' => false,
                'priority' => 80,
                'daily_limit' => 500,
                'hourly_limit' => 100,
                'sent_today' => 0,
                'sent_this_hour' => 0,
            ],

            // Gmail API Provider
            [
                'name' => 'gmail_api',
                'display_name' => 'Gmail API',
                'type' => 'api',
                'configuration' => [
                    'credentials_path' => env('GMAIL_API_CREDENTIALS_PATH', ''),
                    'credentials_json' => env('GMAIL_API_CREDENTIALS_JSON', ''),
                    'user_email' => env('GMAIL_API_USER_EMAIL', ''),
                    'access_token' => env('GMAIL_API_ACCESS_TOKEN', ''),
                    'refresh_token' => env('GMAIL_API_REFRESH_TOKEN', ''),
                    'redirect_uri' => env('GMAIL_API_REDIRECT_URI', ''),
                    'from_email' => env('GMAIL_API_FROM_EMAIL', ''),
                    'from_name' => env('GMAIL_API_FROM_NAME', 'TP-Affiliate'),
                ],
                'is_active' => false, // ปิดเป็นค่าเริ่มต้น ต้อง configure ก่อน
                'is_default' => false,
                'priority' => 100,
                'daily_limit' => 500,
                'hourly_limit' => 100,
                'sent_today' => 0,
                'sent_this_hour' => 0,
            ],
        ];
    }
}
