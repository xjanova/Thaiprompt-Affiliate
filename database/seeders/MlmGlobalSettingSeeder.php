<?php

namespace Database\Seeders;

use App\Models\MlmGlobalSetting;
use Illuminate\Database\Seeder;

class MlmGlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * อัพเดท MLM Global Settings สำหรับระบบผู้มุ่งหวัง
     */
    public function run(): void
    {
        $setting = MlmGlobalSetting::first();

        if ($setting) {
            $setting->update([
                'prospect_lock_duration_hours' => 24, // ล็อก 1 วัน
                'enable_prospect_lock' => true,
                'enable_auto_add_sponsor_friend' => true,
                'enable_line_signup' => true,
                'require_line_verification' => false,
            ]);

            $this->command->info('✅ MLM Global Settings updated for Prospect system!');
        } else {
            $this->command->warn('⚠️  No MLM Global Settings found. Please run MLM seeders first.');
        }
    }
}
