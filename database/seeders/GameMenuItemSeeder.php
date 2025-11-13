<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class GameMenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if menu item already exists
        $exists = MenuItem::where('route', 'admin.games.index')->exists();

        if ($exists) {
            $this->command->info('⚠️  Game menu item already exists, skipping...');
            return;
        }

        // Get the highest order number for admin menu location
        $maxOrder = MenuItem::where('menu_location', 'admin')->max('order') ?? 0;

        // Create Game Management menu item
        MenuItem::create([
            'menu_location' => 'admin',
            'title' => 'จัดการเกม',
            'url' => null,
            'route' => 'admin.games.index',
            'target' => '_self',
            'icon' => 'fa-gamepad', // FontAwesome icon
            'order' => $maxOrder + 1,
            'parent_id' => null,
            'is_active' => true,
            'conditions' => [
                'logged_in' => true,
                'roles' => ['admin', 'super_admin']
            ],
        ]);

        $this->command->info('✅ Game menu item added successfully!');
        $this->command->info('📍 Location: Admin Sidebar');
        $this->command->info('🎮 Icon: fa-gamepad');
        $this->command->info('🔗 Route: admin.games.index');
    }
}
