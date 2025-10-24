<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionSetting;

class CommissionSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['level' => 1, 'percentage' => 10.00, 'name' => 'Level 1 Commission', 'description' => 'Direct referral commission'],
            ['level' => 2, 'percentage' => 5.00, 'name' => 'Level 2 Commission', 'description' => 'Second level commission'],
            ['level' => 3, 'percentage' => 3.00, 'name' => 'Level 3 Commission', 'description' => 'Third level commission'],
            ['level' => 4, 'percentage' => 2.00, 'name' => 'Level 4 Commission', 'description' => 'Fourth level commission'],
            ['level' => 5, 'percentage' => 1.00, 'name' => 'Level 5 Commission', 'description' => 'Fifth level commission'],
        ];

        foreach ($settings as $setting) {
            CommissionSetting::create($setting);
        }
    }
}
