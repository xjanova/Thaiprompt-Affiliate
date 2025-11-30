<?php

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Concerns\SafeSeeder;
use Illuminate\Database\Seeder;

class AppNameSettingSeeder extends Seeder
{
    use SafeSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ตรวจสอบว่าตาราง settings มีอยู่หรือไม่
        if (!$this->requireTable('settings', 'AppNameSettingSeeder')) {
            return;
        }

        // Check if app_name setting already exists
        $existingSetting = Setting::where('key', 'app_name')->first();

        if ($existingSetting) {
            $this->command->warn('⚠️  App name setting already exists!');
            $this->command->info("   Current value: {$existingSetting->value}");
            $this->command->info('   Skipping to preserve your customization.');
            return;
        }

        // Fresh install - create app_name setting
        $this->command->info('🌱 Creating app_name setting...');

        Setting::create([
            'key' => 'app_name',
            'value' => 'ร้านค้าชุมชนไทยพร๊อม',
            'type' => 'string',
            'group' => 'general'
        ]);

        $this->command->info('✅ App name created successfully: ร้านค้าชุมชนไทยพร๊อม');
    }
}
