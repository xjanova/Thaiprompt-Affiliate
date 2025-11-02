<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CommissionDepthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default commission depth if not exists
        Setting::firstOrCreate(
            ['key' => 'commission_depth'],
            [
                'value' => '10',
                'type' => 'integer',
                'group' => 'affiliate',
            ]
        );

        $this->command->info('Commission depth setting created successfully.');
    }
}
