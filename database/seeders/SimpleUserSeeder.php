<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SimpleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้าง test users สำหรับทดสอบ Login
     */
    public function run(): void
    {
        // ตรวจสอบว่ามีตารางหรือไม่
        if (!DB::getSchemaBuilder()->hasTable('users')) {
            $this->command->error('ตาราง users ยังไม่มี! กรุณารัน: php artisan migrate ก่อน');
            return;
        }

        $this->command->info('กำลังสร้าง test users...');

        // ลบ test users เก่าถ้ามี (optional)
        $testEmails = [
            'admin@test.com',
            'user@test.com',
            'test@test.com',
        ];

        User::whereIn('email', $testEmails)->delete();

        // สร้าง Admin User
        try {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'referral_code' => 'ADMIN001',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone' => '0812345678',
            ]);

            $this->command->info("✓ สร้าง Admin User สำเร็จ (email: admin@test.com, password: password)");

        } catch (\Exception $e) {
            $this->command->error("✗ ไม่สามารถสร้าง Admin User: " . $e->getMessage());
        }

        // สร้าง Test User 1
        try {
            $user1 = User::create([
                'name' => 'Test User',
                'email' => 'user@test.com',
                'password' => Hash::make('password'),
                'referral_code' => 'USER001',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone' => '0823456789',
            ]);

            $this->command->info("✓ สร้าง Test User สำเร็จ (email: user@test.com, password: password)");

        } catch (\Exception $e) {
            $this->command->error("✗ ไม่สามารถสร้าง Test User: " . $e->getMessage());
        }

        // สร้าง Test User 2
        try {
            $user2 = User::create([
                'name' => 'Demo User',
                'email' => 'test@test.com',
                'password' => Hash::make('123456'),
                'referral_code' => 'TEST001',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone' => '0834567890',
            ]);

            $this->command->info("✓ สร้าง Demo User สำเร็จ (email: test@test.com, password: 123456)");

        } catch (\Exception $e) {
            $this->command->error("✗ ไม่สามารถสร้าง Demo User: " . $e->getMessage());
        }

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('สร้าง Test Users เสร็จสิ้น!');
        $this->command->info('========================================');
        $this->command->info('คุณสามารถ Login ได้ที่: /login');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('1. Email: admin@test.com | Password: password');
        $this->command->info('2. Email: user@test.com  | Password: password');
        $this->command->info('3. Email: test@test.com  | Password: 123456');
        $this->command->info('========================================');
    }
}
