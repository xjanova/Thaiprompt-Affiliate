<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CookieSetting;

class CookieSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if cookie settings already exist
        $existingSettingsCount = CookieSetting::whereIn('key', [
            'cookie_banner_enabled',
            'cookie_banner_title',
            'cookie_banner_description'
        ])->count();

        if ($existingSettingsCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all settings
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all cookie settings...');

        $settings = $this->getAllSettings();

        foreach ($settings as $setting) {
            CookieSetting::create($setting);
        }

        $this->command->info('✅ Cookie settings seeded successfully: ' . count($settings) . ' settings');
    }

    /**
     * Update mode - add only missing settings
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing cookie settings detected!');
        $this->command->info('   Running in UPDATE mode (adding missing settings only)...');

        $settings = $this->getAllSettings();
        $added = 0;
        $skipped = 0;

        foreach ($settings as $setting) {
            if (!CookieSetting::where('key', $setting['key'])->exists()) {
                CookieSetting::create($setting);
                $this->command->info("   ➕ Added: {$setting['key']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new cookie settings.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing settings (preserved).");
        }
    }

    /**
     * Get all default cookie settings
     */
    private function getAllSettings(): array
    {
        return [
            [
                'key' => 'cookie_banner_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'เปิดใช้งานแบนเนอร์คุกกี้หรือไม่',
            ],
            [
                'key' => 'cookie_banner_title',
                'value' => 'เราใช้คุกกี้เพื่อปรับปรุงประสบการณ์ของคุณ',
                'type' => 'text',
                'description' => 'หัวข้อของแบนเนอร์คุกกี้',
            ],
            [
                'key' => 'cookie_banner_description',
                'value' => 'เราใช้คุกกี้เพื่อวิเคราะห์การใช้งานเว็บไซต์ ปรับปรุงประสบการณ์ของคุณ และแสดงเนื้อหาที่เหมาะสม คุณสามารถจัดการการตั้งค่าคุกกี้ได้ตามต้องการ เราปฏิบัติตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) อย่างเคร่งครัด',
                'type' => 'text',
                'description' => 'คำอธิบายของแบนเนอร์คุกกี้',
            ],
            [
                'key' => 'cookie_policy_url',
                'value' => '/cookie-policy',
                'type' => 'text',
                'description' => 'URL ของหน้านโยบายคุกกี้',
            ],
            [
                'key' => 'auto_block_without_consent',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'บล็อกการติดตามจนกว่าจะได้รับความยินยอม',
            ],
            [
                'key' => 'cookie_expiry_days',
                'value' => '365',
                'type' => 'integer',
                'description' => 'จำนวนวันที่คุกกี้จะหมดอายุ',
            ],
        ];
    }
}
