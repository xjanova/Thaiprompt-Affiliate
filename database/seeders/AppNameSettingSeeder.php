<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class AppNameSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating app_name setting...');

        // Update app_name from "ร้านค้าชุมชนไทยพร้อม" to "ร้านค้าชุมชนไทยพร๊อม"
        Setting::updateOrCreate(
            ['key' => 'app_name'],
            [
                'value' => 'ร้านค้าชุมชนไทยพร๊อม',
                'type' => 'string',
                'group' => 'general'
            ]
        );

        $this->command->info('✅ App name updated successfully to: ร้านค้าชุมชนไทยพร๊อม');
    }
}
