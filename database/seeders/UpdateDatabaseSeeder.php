<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master seeder to run all Video Reward System seeders
 */
class UpdateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║   🎬 Video Reward System - Database Setup     ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->info('');

        // Run Video Reward System seeder
        $this->command->info('📦 Seeding Video Reward System data...');
        $this->call(VideoRewardSystemSeeder::class);

        $this->command->info('');

        // Run Menu seeder
        $this->command->info('🎨 Seeding Video Reward System menus...');
        $this->call(VideoRewardMenuSeeder::class);

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║            ✅ Setup Complete!                  ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('📝 Next steps:');
        $this->command->info('   1. Access Admin: /admin/video-rewards/dashboard');
        $this->command->info('   2. Configure channels and videos');
        $this->command->info('   3. Create quests for users');
        $this->command->info('   4. Set exchange rates');
        $this->command->info('');
        $this->command->info('📚 Documentation: VIDEO_REWARD_SYSTEM_README.md');
        $this->command->info('');
    }
}
