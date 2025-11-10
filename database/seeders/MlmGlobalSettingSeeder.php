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
     *
     * 📌 Smart Seeding Strategy:
     * This seeder ONLY updates NULL values with defaults for new columns.
     * It will NEVER overwrite existing user customizations.
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

        if (!$hasProspectColumns) {
            $this->command->warn('⚠️  Prospect columns not found. Please run migration: 2025_11_08_000004_add_prospect_settings_to_mlm_global_settings_table');
            $this->command->info('💡 Skipping MlmGlobalSettingSeeder...');
            return;
        }

        // Smart Update: Only set NULL values, preserve existing customizations
        $updates = [];
        $updated = 0;
        $skipped = 0;

        $defaultValues = [
            'prospect_lock_duration_hours' => 24,
            'enable_prospect_lock' => true,
            'enable_auto_add_sponsor_friend' => true,
            'enable_line_signup' => true,
            'require_line_verification' => false,
        ];

        foreach ($defaultValues as $key => $defaultValue) {
            if (is_null($setting->$key)) {
                $updates[$key] = $defaultValue;
                $this->command->info("   ➕ Setting default for: {$key}");
                $updated++;
            } else {
                $skipped++;
            }
        }

        if (!empty($updates)) {
            $setting->update($updates);
            $this->command->info("✅ Updated {$updated} MLM Global Settings with default values.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing values (preserved).");
        }

        if (empty($updates) && $skipped === 0) {
            $this->command->info('✅ MLM Global Settings are already up to date!');
        }
    }
}
