<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CommissionDepthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Only creates commission depth setting if it doesn't exist.
     * Preserves user-customized commission depth value.
     */
    public function run(): void
    {
        $existingSetting = Setting::where('key', 'commission_depth')->first();

        if ($existingSetting) {
            $this->command->warn('⚠️  Commission depth setting already exists!');
            $this->command->info("   Current value: {$existingSetting->value}");
            $this->command->info('   Skipping to preserve your customization.');
            return;
        }

        Setting::create([
            'key' => 'commission_depth',
            'value' => '10',
            'type' => 'integer',
            'group' => 'affiliate',
        ]);

        $this->command->info('✅ Commission depth setting created successfully (default: 10 levels).');
    }
}
