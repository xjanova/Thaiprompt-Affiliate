<?php

namespace Database\Seeders;

use App\Models\LineSignupSession;
use App\Models\MlmProspect;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LineSignupSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้าง Demo LINE Signup Sessions สำหรับทดสอบ
     *
     * Session Types:
     * 1. new - session ใหม่ (ยังไม่ได้สมัคร)
     * 2. in_progress - กำลังสมัครอยู่
     * 3. completed - สมัครสำเร็จ
     *
     * @return void
     */
    public function run(): void
    {
        // ตรวจสอบว่ามี sessions อยู่แล้ว
        $existingCount = LineSignupSession::count();

        if ($existingCount > 0) {
            $this->command->warn('⚠️  LINE Signup Sessions already exist!');
            $this->command->info('   Skipping to preserve existing session data.');
            return;
        }

        $this->command->info('📱 สร้าง Demo LINE Signup Sessions...');

        // ดึง demo admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Session 1: New Session (session ใหม่)
        LineSignupSession::create([
            'line_user_id' => 'U1234567890abcdef1234567890abcde',
            'session_token' => Str::random(60),
            'current_step' => 'welcome', // ขั้นตอนแรก
            'status' => LineSignupSession::STATUS_ACTIVE,
            'language' => 'th',
            'otp_code' => null,
            'otp_attempts' => 0,
            'otp_expires_at' => null,
            'started_at' => Carbon::now()->subMinutes(5),
            'last_activity_at' => Carbon::now()->subMinutes(2),
            'completed_at' => null,
            'collected_data' => [
                'line_display_name' => 'John Doe',
                'line_picture_url' => 'https://via.placeholder.com/200',
                'referral_code' => 'REF123456',
                'utm_source' => 'line_direct',
                'user_agent' => 'Mozilla/5.0 LINE/iOS',
                'ip_address' => '192.168.1.1',
            ],
        ]);

        // Session 2: In Progress Session (กำลังสมัครอยู่)
        LineSignupSession::create([
            'line_user_id' => 'U9876543210abcdef0987654321abcde',
            'session_token' => Str::random(60),
            'current_step' => 'password', // อยู่ที่ขั้นตอน password
            'status' => LineSignupSession::STATUS_ACTIVE,
            'language' => 'th',
            'otp_code' => null,
            'otp_attempts' => 0,
            'otp_expires_at' => null,
            'started_at' => Carbon::now()->subHours(1),
            'last_activity_at' => Carbon::now()->subMinutes(10),
            'completed_at' => null,
            'collected_data' => [
                'phone' => '0891234567',
                'email' => 'jane@example.com',
                'full_name' => 'Jane Smith',
                'address' => '123 หมู่ 4 ตำบล... อำเภอ... จังหวัด...',
                'id_card_number' => '1234567890123',
                'line_display_name' => 'Jane Smith',
                'line_picture_url' => 'https://via.placeholder.com/200',
                'referral_code' => 'REF789012',
                'utm_source' => 'line_invitation',
                'user_agent' => 'Mozilla/5.0 LINE/Android',
                'ip_address' => '192.168.1.2',
            ],
        ]);

        // Session 3: Completed Session (สมัครสำเร็จ)
        $completedUser = null;
        if ($admin) {
            $completedUser = User::create([
                'name' => 'Bob Wilson',
                'email' => 'bob.signup@example.com',
                'phone' => '0893456789',
                'password' => bcrypt('password'),
                'line_user_id' => 'Uabcdef1234567890abcdef1234567890',
                'line_display_name' => 'Bob Wilson',
                'line_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        LineSignupSession::create([
            'line_user_id' => 'Uabcdef1234567890abcdef1234567890',
            'session_token' => Str::random(60),
            'current_step' => 'completed', // เสร็จสิ้น
            'status' => LineSignupSession::STATUS_COMPLETED,
            'language' => 'th',
            'user_id' => $completedUser?->id, // Link to created user
            'otp_code' => null,
            'otp_attempts' => 0,
            'otp_expires_at' => null,
            'started_at' => Carbon::now()->subDays(1),
            'last_activity_at' => Carbon::now()->subDays(1)->addHours(1),
            'completed_at' => Carbon::now()->subDays(1)->addHours(1),
            'collected_data' => [
                'phone' => '0893456789',
                'email' => 'bob.signup@example.com',
                'full_name' => 'Bob Wilson',
                'address' => '456 ซ.ใหญ่ ตำบล... อำเภอ... จังหวัด...',
                'id_card_number' => '9876543210987',
                'line_display_name' => 'Bob Wilson',
                'line_picture_url' => 'https://via.placeholder.com/200',
                'referral_code' => 'REF345678',
                'utm_source' => 'line_link',
                'user_agent' => 'Mozilla/5.0 LINE/iOS',
                'ip_address' => '192.168.1.3',
            ],
        ]);

        $this->command->info('✅ LINE Signup Sessions สร้างสำเร็จ! (3 sessions)');
        $this->command->line('');
        $this->command->info('📊 Session Types:');
        $this->command->line('  1. Active Session (welcome step) - session ใหม่');
        $this->command->line('  2. Active Session (password step) - กำลังสมัครอยู่');
        $this->command->line('  3. Completed Session - สมัครสำเร็จ');
        $this->command->line('');
        $this->command->info('💡 ใช้ sessions นี้ทดสอบ:');
        $this->command->info('   - LINE webhook flow');
        $this->command->info('   - Signup progress tracking');
        $this->command->info('   - OTP verification');
        $this->command->info('   - Session resumption');
    }
}
