<?php

namespace Database\Seeders;

use App\Models\MlmGlobalSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MlmGlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * อัพเดท MLM Global Settings สำหรับระบบผู้มุ่งหวัง
     *
     * Note: This seeder requires migration 2025_11_08_000004_add_prospect_settings_to_mlm_global_settings_table
     * If the migration hasn't run yet, this seeder will skip gracefully.
     */
    public function run(): void
    {
        $setting = MlmGlobalSetting::first();

        if (!$setting) {
            $this->command->warn('⚠️  No MLM Global Settings found. Please run MLM seeders first.');
            return;
        }

        // Check if prospect columns exist before updating
        $hasProspectColumns = Schema::hasColumns('mlm_global_settings', [
            'prospect_lock_duration_hours',
            'enable_prospect_lock',
            'enable_auto_add_sponsor_friend',
            'enable_line_signup',
            'require_line_verification',
        ]);

        if ($hasProspectColumns) {
            $setting->update([
                'prospect_lock_duration_hours' => 24, // ล็อก 1 วัน
                'enable_prospect_lock' => true,
                'enable_auto_add_sponsor_friend' => true,
                'enable_line_signup' => true,
                'require_line_verification' => false,
            ]);

            $this->command->info('✅ MLM Global Settings updated for Prospect system!');
        } else {
            $this->command->warn('⚠️  Prospect columns not found. Please run migration: 2025_11_08_000004_add_prospect_settings_to_mlm_global_settings_table');
            $this->command->info('💡 Skipping MlmGlobalSettingSeeder...');
        }
    }
}
