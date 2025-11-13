<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class ChatbotMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🤖 Seeding Chatbot Rental System menu items...');

        // Check if menu items already exist
        $existingMenu = MenuItem::where('title', 'ระบบบอทแชท')->first();

        if ($existingMenu) {
            $this->command->warn('⚠️  Chatbot menu already exists. Skipping...');
            return;
        }

        DB::beginTransaction();

        try {
            // Main Menu: Chatbot System
            $chatbotMenu = MenuItem::create([
                'menu_location' => 'header',
                'title' => 'ระบบบอทแชท',
                'icon' => 'fas fa-robot',
                'url' => '/chatbot',
                'target' => '_self',
                'parent_id' => null,
                'order' => 100,
                'is_active' => true,
                'conditions' => json_encode(['logged_in' => true]),
            ]);

            // Sub Menu: My Bots
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'บอทของฉัน',
                'icon' => 'fas fa-list',
                'url' => '/chatbot',
                'target' => '_self',
                'parent_id' => $chatbotMenu->id,
                'order' => 1,
                'is_active' => true,
                'conditions' => json_encode(['logged_in' => true]),
            ]);

            // Sub Menu: Create Bot
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'สร้างบอทใหม่',
                'icon' => 'fas fa-plus-circle',
                'url' => '/chatbot/create',
                'target' => '_self',
                'parent_id' => $chatbotMenu->id,
                'order' => 2,
                'is_active' => true,
                'conditions' => json_encode(['logged_in' => true]),
            ]);

            // Sub Menu: Marketplace
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'ตลาดบอท',
                'icon' => 'fas fa-store',
                'url' => '/chatbot/marketplace',
                'target' => '_self',
                'parent_id' => $chatbotMenu->id,
                'order' => 3,
                'is_active' => true,
            ]);

            // Sub Menu: My Rentals
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'บอทที่เช่า',
                'icon' => 'fas fa-handshake',
                'url' => '/chatbot/marketplace/my-rentals',
                'target' => '_self',
                'parent_id' => $chatbotMenu->id,
                'order' => 4,
                'is_active' => true,
                'conditions' => json_encode(['logged_in' => true]),
            ]);

            // Sub Menu: My Earnings
            MenuItem::create([
                'menu_location' => 'header',
                'title' => 'รายได้ของฉัน',
                'icon' => 'fas fa-coins',
                'url' => '/chatbot/marketplace/my-earnings',
                'target' => '_self',
                'parent_id' => $chatbotMenu->id,
                'order' => 5,
                'is_active' => true,
                'conditions' => json_encode(['logged_in' => true]),
            ]);

            DB::commit();

            $this->command->info('✅ Chatbot menu items seeded successfully!');
            $this->command->info('   - Main Menu: ระบบบอทแชท');
            $this->command->info('   - บอทของฉัน');
            $this->command->info('   - สร้างบอทใหม่');
            $this->command->info('   - ตลาดบอท');
            $this->command->info('   - บอทที่เช่า');
            $this->command->info('   - รายได้ของฉัน');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Failed to seed chatbot menu: ' . $e->getMessage());
            throw $e;
        }
    }
}
