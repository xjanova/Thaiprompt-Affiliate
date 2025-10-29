<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างผู้ใช้ทดสอบหลายๆ role สำหรับการ deploy
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating demo users...');

        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@thaiprompt.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_super_admin' => true,
                'permissions' => User::availablePermissions(),
                'preferred_language' => 'th',
            ]
        );
        $this->command->info('✅ Super Admin: superadmin@thaiprompt.com');

        // Regular Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@thaiprompt.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_super_admin' => false,
                'permissions' => ['view_dashboard', 'manage_users', 'manage_affiliates', 'manage_commissions', 'view_reports'],
                'preferred_language' => 'th',
            ]
        );
        $this->command->info('✅ Admin: admin@thaiprompt.com');

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@thaiprompt.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'is_super_admin' => false,
                'permissions' => ['view_dashboard', 'manage_affiliates', 'view_reports'],
                'preferred_language' => 'th',
            ]
        );
        $this->command->info('✅ Manager: manager@thaiprompt.com');

        // Affiliates (5 users)
        for ($i = 1; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "affiliate{$i}@example.com"],
                [
                    'name' => "Affiliate User {$i}",
                    'password' => Hash::make('password123'),
                    'role' => 'affiliate',
                    'is_super_admin' => false,
                    'permissions' => ['view_dashboard'],
                    'preferred_language' => $i % 2 === 0 ? 'en' : 'th',
                ]
            );
        }
        $this->command->info('✅ 5 Affiliates: affiliate1-5@example.com');

        // Regular Users (10 users)
        for ($i = 1; $i <= 10; $i++) {
            User::firstOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'name' => "Demo User {$i}",
                    'password' => Hash::make('password123'),
                    'role' => 'user',
                    'is_super_admin' => false,
                    'permissions' => ['view_dashboard'],
                    'preferred_language' => $i % 2 === 0 ? 'en' : 'th',
                ]
            );
        }
        $this->command->info('✅ 10 Users: user1-10@example.com');

        $this->command->info('');
        $this->command->info('📝 Default Password: password123');
        $this->command->info('');
    }
}
