<?php

namespace Database\Seeders;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class KycVerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้าง Demo KYC Verification Records สำหรับทดสอบ
     *
     * สถานะ:
     * - pending: รอการตรวจสอบ
     * - approved: อนุมัติแล้ว
     * - rejected: ปฏิเสธแล้ว (มีเหตุผล)
     *
     * @return void
     */
    public function run(): void
    {
        // ตรวจสอบว่ามี KYC records อยู่แล้ว
        $existingCount = KycVerification::count();

        if ($existingCount > 0) {
            $this->command->warn('⚠️  KYC Verification records already exist!');
            $this->command->info('   Skipping to preserve existing KYC data.');
            return;
        }

        $this->command->info('🔐 สร้าง Demo KYC Verification Records...');

        // ดึง demo users (จาก DemoUsersSeeder)
        $users = User::whereIn('email', [
            'john@example.com',
            'jane@example.com',
            'bob@example.com',
        ])->get();

        if ($users->count() < 3) {
            $this->command->warn('⚠️  ต้องมี Demo Users อย่างน้อย 3 คน (รัน DemoUsersSeeder ก่อน)');
            // สร้าง demo users หากไม่มี
            $users = $this->createDemoUsers();
        }

        // ดึง admin user สำหรับการ review
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Record 1: Pending KYC (รอการตรวจสอบ)
        if ($users->count() >= 1) {
            KycVerification::create([
                'user_id' => $users[0]->id,
                'id_card_image' => 'kyc/idcard_' . $users[0]->id . '.jpg',
                'selfie_image' => 'kyc/selfie_' . $users[0]->id . '.jpg',
                'status' => 'pending',
                'submitted_at' => Carbon::now()->subDays(2),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'extracted_data' => [
                    'id_card_number' => '1234567890123',
                    'full_name' => 'สมชาย ใจดี',
                    'date_of_birth' => '1990-01-15',
                    'nationality' => 'ไทย',
                    'address' => '123 หมู่ 4 ตำบล... อำเภอ... จังหวัด...',
                ],
            ]);
        }

        // Record 2: Approved KYC (อนุมัติแล้ว)
        if ($users->count() >= 2) {
            KycVerification::create([
                'user_id' => $users[1]->id,
                'id_card_image' => 'kyc/idcard_' . $users[1]->id . '.jpg',
                'selfie_image' => 'kyc/selfie_' . $users[1]->id . '.jpg',
                'status' => 'approved',
                'submitted_at' => Carbon::now()->subDays(5),
                'reviewed_by' => $admin?->id,
                'reviewed_at' => Carbon::now()->subDays(4),
                'rejection_reason' => null,
                'extracted_data' => [
                    'id_card_number' => '9876543210987',
                    'full_name' => 'สมหญิง สวยงาม',
                    'date_of_birth' => '1995-06-20',
                    'nationality' => 'ไทย',
                    'address' => '456 ซ.ใหญ่ ตำบล... อำเภอ... จังหวัด...',
                ],
            ]);
        }

        // Record 3: Rejected KYC (ปฏิเสธ)
        if ($users->count() >= 3) {
            KycVerification::create([
                'user_id' => $users[2]->id,
                'id_card_image' => 'kyc/idcard_' . $users[2]->id . '.jpg',
                'selfie_image' => null,
                'status' => 'rejected',
                'submitted_at' => Carbon::now()->subDays(7),
                'reviewed_by' => $admin?->id,
                'reviewed_at' => Carbon::now()->subDays(6),
                'rejection_reason' => 'รูปบัตรประชาชนไม่ชัดเจน ขอให้ส่งใหม่อีกครั้ง',
                'extracted_data' => [
                    'id_card_number' => null,
                    'full_name' => 'สมศักดิ์ ตันหนักแน่น',
                    'date_of_birth' => '1988-12-10',
                    'nationality' => 'ไทย',
                    'address' => null,
                    'note' => 'ดูลายมือซ้อนทับ ไม่ชัดเจน',
                ],
            ]);
        }

        $this->command->info('✅ KYC Verification Records สร้างสำเร็จ!');
        $this->command->line('');
        $this->command->info('📊 สถานะ KYC:');
        $this->command->line('  • pending (1 record) - รอการตรวจสอบ');
        $this->command->line('  • approved (1 record) - อนุมัติแล้ว');
        $this->command->line('  • rejected (1 record) - ปฏิเสธแล้ว');
        $this->command->line('');
        $this->command->info('💡 ทดสอบบนเว็บ:');
        $this->command->info('   1. ไปที่ /admin/kyc-verification/');
        $this->command->info('   2. ตรวจสอบสถานะต่างๆ');
        $this->command->info('   3. ลองอนุมัติ/ปฏิเสธ record ที่ pending');
    }

    /**
     * สร้าง Demo Users หากไม่มี
     */
    private function createDemoUsers()
    {
        $this->command->info('📝 สร้าง Demo Users...');

        $users = [
            [
                'name' => 'สมชาย ใจดี',
                'email' => 'john@example.com',
                'phone' => '0891234567',
            ],
            [
                'name' => 'สมหญิง สวยงาม',
                'email' => 'jane@example.com',
                'phone' => '0892345678',
            ],
            [
                'name' => 'สมศักดิ์ ตันหนักแน่น',
                'email' => 'bob@example.com',
                'phone' => '0893456789',
            ],
        ];

        $created = collect();

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);

            $created->push($user);
        }

        return $created;
    }
}
