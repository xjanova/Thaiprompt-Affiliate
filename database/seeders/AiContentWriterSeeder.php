<?php

namespace Database\Seeders;

use App\Models\AiContentSetting;
use Illuminate\Database\Seeder;

/**
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ AI Content Writer
 *
 * รวมถึง:
 * - การตั้งค่า API Keys เริ่มต้น (ว่าง)
 * - เทมเพลตตัวอย่าง 10 แบบ
 */
class AiContentWriterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🖊️ กำลัง seed ข้อมูล AI Content Writer...');

        // สร้างการตั้งค่าเริ่มต้น
        $this->seedSettings();

        // สร้างเทมเพลตตัวอย่าง

        $this->command->info('✅ Seed ข้อมูล AI Content Writer สำเร็จ!');
    }

    /**
     * สร้างการตั้งค่าเริ่มต้น
     */
    protected function seedSettings(): void
    {
        $settings = [
            // OpenAI Settings
            [
                'key' => 'openai_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'openai',
                'label' => 'OpenAI API Key',
                'description' => 'API Key สำหรับ OpenAI (GPT-4, GPT-4o)',
            ],
            [
                'key' => 'openai_default_model',
                'value' => 'gpt-4o-mini',
                'type' => 'string',
                'group' => 'openai',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ OpenAI',
            ],

            // Claude Settings
            [
                'key' => 'claude_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'claude',
                'label' => 'Claude API Key',
                'description' => 'API Key สำหรับ Anthropic Claude',
            ],
            [
                'key' => 'claude_default_model',
                'value' => 'claude-3-haiku-20240307',
                'type' => 'string',
                'group' => 'claude',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ Claude',
            ],

            // Gemini Settings
            [
                'key' => 'gemini_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'gemini',
                'label' => 'Gemini API Key',
                'description' => 'API Key สำหรับ Google Gemini',
            ],
            [
                'key' => 'gemini_default_model',
                'value' => 'gemini-1.5-flash',
                'type' => 'string',
                'group' => 'gemini',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ Gemini',
            ],

            // General Settings
            [
                'key' => 'default_provider',
                'value' => 'openai',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Provider เริ่มต้น',
                'description' => 'AI Provider ที่ใช้เป็นค่าเริ่มต้น',
            ],
            [
                'key' => 'default_temperature',
                'value' => '0.7',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Temperature เริ่มต้น',
                'description' => 'ระดับความสร้างสรรค์ (0-2)',
            ],
            [
                'key' => 'default_max_tokens',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'general',
                'label' => 'Max Tokens เริ่มต้น',
                'description' => 'จำนวน tokens สูงสุดต่อการสร้าง',
            ],
            [
                'key' => 'enable_usage_tracking',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'บันทึกการใช้งาน',
                'description' => 'เปิด/ปิดการบันทึก Usage Logs',
            ],
        ];

        foreach ($settings as $setting) {
            AiContentSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('   📝 สร้างการตั้งค่า AI Content Writer แล้ว');
    }

}
