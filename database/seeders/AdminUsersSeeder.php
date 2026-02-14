<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SafeSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AdminUsersSeeder
 *
 * สร้าง Admin users หลักของระบบ (Super Admin และ Regular Admin)
 * ไม่รวม demo users อื่นๆ
 */
class AdminUsersSeeder extends Seeder
{
    use SafeSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (! $this->requireTable('users', 'AdminUsersSeeder')) {
            return;
        }

        $this->command->info('');
        $this->command->info('👑 กำลังสร้าง Admin Users...');
        $this->command->info('');

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
        $this->command->info("✅ Super Admin: {$superAdmin->email}");

        // Regular Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@thaiprompt.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_super_admin' => false,
                'permissions' => [
                    'view_dashboard',
                    'manage_users',
                    'manage_affiliates',
                    'manage_commissions',
                    'view_reports',
                ],
                'preferred_language' => 'th',
            ]
        );
        $this->command->info("✅ Admin: {$admin->email}");

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@thaiprompt.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'is_super_admin' => false,
                'permissions' => [
                    'view_dashboard',
                    'manage_affiliates',
                    'view_reports',
                ],
                'preferred_language' => 'th',
            ]
        );
        $this->command->info("✅ Manager: {$manager->email}");

        $this->command->info('');
        $this->command->info('🔐 Password: password123');
        $this->command->info('');
    }
}
