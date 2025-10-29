<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Create 2 test user accounts that are not connected to any affiliate line
     */
    public function run(): void
    {
        // Test User 1
        User::firstOrCreate(
            ['email' => 'testuser1@example.com'],
            [
                'name' => 'Test User 1',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_super_admin' => false,
                'affiliate_id' => null, // Not connected to any affiliate line
                'permissions' => ['view_dashboard'],
                'preferred_language' => 'th',
            ]
        );

        // Test User 2
        User::firstOrCreate(
            ['email' => 'testuser2@example.com'],
            [
                'name' => 'Test User 2',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_super_admin' => false,
                'affiliate_id' => null, // Not connected to any affiliate line
                'permissions' => ['view_dashboard'],
                'preferred_language' => 'en',
            ]
        );

        $this->command->info('✅ Test users created successfully!');
        $this->command->info('📧 Email: testuser1@example.com | Password: password');
        $this->command->info('📧 Email: testuser2@example.com | Password: password');
    }
}
