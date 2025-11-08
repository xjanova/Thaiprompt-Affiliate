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
        $settings = [
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

        foreach ($settings as $setting) {
            CookieSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Cookie settings seeded successfully!');
    }
}
