<?php

namespace Database\Seeders;

use App\Models\MlmMember;
use App\Models\MlmPackage;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MlmHierarchySeeder extends Seeder
{
    /**
     * สร้างข้อมูล MLM Hierarchy 5 ชั้นสำหรับการทดสอบจริง
     *
     * โครงสร้างสายงาน (Unilevel):
     * Level 0: Admin (Root)
     * Level 1: 3 คน (สมาชิกที่ Admin แนะนำโดยตรง)
     * Level 2: 6 คน (สมาชิกที่ Level 1 แนะนำ - 2 คนต่อคน)
     * Level 3: 12 คน (สมาชิกที่ Level 2 แนะนำ - 2 คนต่อคน)
     * Level 4: 24 คน (สมาชิกที่ Level 3 แนะนำ - 2 คนต่อคน)
     * Level 5: 48 คน (สมาชิกที่ Level 4 แนะนำ - 2 คนต่อคน)
     *
     * รวม: 1 (Admin) + 3 + 6 + 12 + 24 + 48 = 94 สมาชิก
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌳 กำลังสร้างระบบ MLM Hierarchy 5 ชั้น...');
        $this->command->info('');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (MlmMember::where('member_code', 'LIKE', 'MLM-DEMO-%')->exists()) {
            $this->command->warn('⚠️  ข้อมูล MLM Hierarchy มีอยู่แล้ว ข้าม...');
            $this->command->info('   หากต้องการสร้างใหม่ ให้ลบข้อมูลเก่าก่อน');
            return;
        }

        // 1. สร้าง/ดึง Default MLM Plan (จำเป็นสำหรับ foreign key)
        $plan = $this->getOrCreateDefaultPlan();
        $this->command->info("✅ MLM Plan ID: {$plan->id}");

        // 2. ดึง Default MLM Package
        $package = MlmPackage::where('slug', 'bronze-package')->first();
        if (!$package) {
            $this->command->error('❌ ไม่พบ MLM Package กรุณารัน MlmPackageSeeder ก่อน');
            return;
        }
        $this->command->info("✅ MLM Package: {$package->name}");

        // 3. สร้างหรือดึง Admin User
        $adminUser = User::where('email', 'superadmin@thaiprompt.com')->first();
        if (!$adminUser) {
            $adminUser = User::where('role', 'admin')->where('is_super_admin', true)->first();
        }
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'MLM Admin',
                'email' => 'mlm-admin@thaiprompt.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_super_admin' => true,
                'preferred_language' => 'th',
            ]);
        }

        // 4. สร้าง Admin เป็น MLM Member (Root)
        $adminMember = $this->createMlmMember(
            user: $adminUser,
            plan: $plan,
            package: $package,
            sponsor: null,
            level: 0,
            memberCode: 'MLM-DEMO-0001'
        );
        $this->command->info("✅ Root Admin: {$adminUser->name} (ID: {$adminMember->id})");

        // 5. สร้าง MLM Hierarchy 5 ชั้น
        $membersPerLevel = [
            1 => 3,   // Level 1: 3 คน
            2 => 2,   // Level 2: 2 คนต่อ sponsor
            3 => 2,   // Level 3: 2 คนต่อ sponsor
            4 => 2,   // Level 4: 2 คนต่อ sponsor
            5 => 2,   // Level 5: 2 คนต่อ sponsor
        ];

        $currentLevelMembers = [$adminMember];
        $memberCounter = 1; // เริ่มจาก 1 (Admin = 0001)
        $totalCreated = 1;

        // ชื่อจริงภาษาไทยสำหรับทดสอบ
        $thaiFirstNames = [
            'สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมศรี', 'สมบัติ',
            'สุรชัย', 'สุรศักดิ์', 'สุภาพร', 'สุภาภรณ์', 'สุนิสา',
            'วิชัย', 'วิภา', 'วิไล', 'วิรัตน์', 'วิภาวดี',
            'ประสิทธิ์', 'ประภา', 'ประเสริฐ', 'ประไพ', 'ประพันธ์',
            'ชัยวัฒน์', 'ชัยชนะ', 'ชุติมา', 'ชุลีกร', 'ชนิดา',
            'พิชัย', 'พิมพ์', 'พิไล', 'พิศมัย', 'พิมลพรรณ',
            'ธนากร', 'ธนาพร', 'ธิดา', 'ธิติมา', 'ธัญญา',
            'อนุชา', 'อนุสรณ์', 'อรุณ', 'อรพิน', 'อัญชลี',
            'ณัฐพล', 'ณัฐวุฒิ', 'ณิชา', 'ณิชกานต์', 'ณัฐธิดา',
            'กิตติ', 'กิตติศักดิ์', 'กัญญา', 'กัลยา', 'กนกวรรณ',
        ];

        $thaiLastNames = [
            'ใจดี', 'มีสุข', 'รุ่งเรือง', 'เจริญศรี', 'สมบูรณ์',
            'สวัสดิ์', 'ประเสริฐ', 'ศรีสุข', 'วงศ์สุวรรณ', 'พิพัฒน์',
            'ทองดี', 'ทองสุข', 'แก้วมณี', 'เพชรดี', 'รัตนา',
            'สุขสันต์', 'สันติภาพ', 'เกษมสุข', 'วิไลวรรณ', 'ศิริพร',
        ];

        for ($level = 1; $level <= 5; $level++) {
            $newLevelMembers = [];
            $membersPerSponsor = ($level === 1) ? $membersPerLevel[1] : $membersPerLevel[$level];

            foreach ($currentLevelMembers as $sponsor) {
                for ($i = 0; $i < $membersPerSponsor; $i++) {
                    $memberCounter++;
                    $memberCode = sprintf('MLM-DEMO-%04d', $memberCounter);

                    // สร้างชื่อแบบสุ่ม
                    $firstName = $thaiFirstNames[array_rand($thaiFirstNames)];
                    $lastName = $thaiLastNames[array_rand($thaiLastNames)];

                    // สร้าง User
                    $user = User::create([
                        'name' => "{$firstName} {$lastName}",
                        'email' => "mlm-member-{$memberCounter}@example.com",
                        'password' => Hash::make('password123'),
                        'role' => 'affiliate',
                        'is_super_admin' => false,
                        'preferred_language' => 'th',
                    ]);

                    // สร้าง MLM Member
                    $member = $this->createMlmMember(
                        user: $user,
                        plan: $plan,
                        package: $package,
                        sponsor: $sponsor,
                        level: $level,
                        memberCode: $memberCode
                    );

                    $newLevelMembers[] = $member;
                    $totalCreated++;
                }
            }

            $count = count($newLevelMembers);
            $this->command->info("   Level {$level}: สร้าง {$count} สมาชิก");

            $currentLevelMembers = $newLevelMembers;
        }

        // 6. อัพเดทสถิติ
        $this->updateStatistics();

        $this->command->info('');
        $this->command->info("✨ สร้าง MLM Hierarchy สำเร็จ! รวม {$totalCreated} สมาชิก");
        $this->command->info('');
        $this->command->info('📊 โครงสร้างสายงาน:');
        $this->command->info('   Level 0 (Root): 1 คน');
        $this->command->info('   Level 1: 3 คน');
        $this->command->info('   Level 2: 6 คน');
        $this->command->info('   Level 3: 12 คน');
        $this->command->info('   Level 4: 24 คน');
        $this->command->info('   Level 5: 48 คน');
        $this->command->info('');
        $this->command->info('🔐 รหัสผ่านทุกบัญชี: password123');
        $this->command->info('');
    }

    /**
     * สร้างหรือดึง Default MLM Plan
     *
     * @return MlmPlan
     */
    private function getOrCreateDefaultPlan(): MlmPlan
    {
        $plan = MlmPlan::where('is_default', true)->first();

        if (!$plan) {
            $plan = MlmPlan::first();
        }

        if (!$plan) {
            // สร้าง Default Plan สำหรับ foreign key constraint
            $plan = MlmPlan::create([
                'name' => 'Default Commission Plan',
                'name_th' => 'แผนคอมมิชชันหลัก',
                'slug' => 'default-plan',
                'description' => 'Default MLM commission plan using global settings',
                'description_th' => 'แผนคอมมิชชันหลักที่ใช้ตั้งค่าจาก Global Settings',
                'type' => 'hybrid',
                'is_active' => true,
                'is_default' => true,
                'color' => '#4F46E5',
                'icon' => 'star',
                'sort_order' => 1,
                'joining_fee' => 0,
                'requires_joining_fee' => false,
                'use_pv_system' => true,
                'global_pv_rate' => 1.00,
                'global_commission_per_pv' => 1.00,
                'max_total_commission_percentage' => 50.00,
                'enable_overpay_protection' => true,
                'unilevel_levels' => [
                    ['level' => 1, 'percentage' => 10],
                    ['level' => 2, 'percentage' => 5],
                    ['level' => 3, 'percentage' => 3],
                    ['level' => 4, 'percentage' => 2],
                    ['level' => 5, 'percentage' => 1],
                ],
                'unilevel_max_depth' => 10,
                'unilevel_compression' => true,
                'binary_pair_commission' => 100.00,
                'binary_match_percentage' => 50.00,
                'binary_flush_enabled' => true,
                'binary_flush_mode' => 'percentage',
                'binary_carry_forward_pv' => false,
                'binary_spillover' => true,
                'binary_pairing_type' => '1:1',
                'binary_placement_preference' => 'balanced',
                'binary_placement_fill_level_first' => true,
                'auto_placement' => true,
                'auto_placement_type' => 'balanced',
            ]);
        }

        return $plan;
    }

    /**
     * สร้าง MLM Member
     *
     * @param User $user
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @param MlmMember|null $sponsor
     * @param int $level
     * @param string $memberCode
     * @return MlmMember
     */
    private function createMlmMember(
        User $user,
        MlmPlan $plan,
        MlmPackage $package,
        ?MlmMember $sponsor,
        int $level,
        string $memberCode
    ): MlmMember {
        // คำนวณ unilevel_path
        $unilevelPath = $sponsor
            ? ($sponsor->unilevel_path ? $sponsor->unilevel_path . '/' . $sponsor->id : (string)$sponsor->id)
            : null;

        // กำหนด binary position แบบสลับซ้าย-ขวา
        $binaryPosition = null;
        if ($sponsor) {
            // ตรวจสอบว่า sponsor มีลูกซ้ายหรือยัง
            $hasLeftChild = MlmMember::where('binary_parent_id', $sponsor->id)
                ->where('binary_position', 'left')
                ->exists();
            $binaryPosition = $hasLeftChild ? 'right' : 'left';
        }

        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $plan->id,
            'package_id' => $package->id,
            'unilevel_sponsor_id' => $sponsor?->id,
            'unilevel_level' => $level,
            'unilevel_path' => $unilevelPath,
            'binary_sponsor_id' => $sponsor?->id,
            'binary_parent_id' => $sponsor?->id,
            'binary_position' => $binaryPosition,
            'status' => 'active',
            'is_qualified' => true,
            'member_code' => $memberCode,
            'joining_fee_paid' => $package->price ?? 990.00,
            'total_pv' => $package->pv_value ?? 990.00,
            'joined_at' => now()->subDays(rand(1, 365)),
        ]);

        return $member;
    }

    /**
     * อัพเดทสถิติของสมาชิก MLM
     *
     * @return void
     */
    private function updateStatistics(): void
    {
        $this->command->info('');
        $this->command->info('📈 กำลังอัพเดทสถิติสายงาน...');

        // อัพเดทจำนวน direct referrals
        MlmMember::where('member_code', 'LIKE', 'MLM-DEMO-%')->each(function ($member) {
            $directCount = MlmMember::where('unilevel_sponsor_id', $member->id)->count();
            $teamCount = $this->countAllDownline($member->id);

            $member->update([
                'total_direct_referrals' => $directCount,
                'total_team_members' => $teamCount,
            ]);
        });

        $this->command->info('✅ อัพเดทสถิติสำเร็จ');
    }

    /**
     * นับจำนวน downline ทั้งหมด (recursive)
     *
     * @param int $memberId
     * @return int
     */
    private function countAllDownline(int $memberId): int
    {
        $count = 0;
        $directChildren = MlmMember::where('unilevel_sponsor_id', $memberId)->get();

        foreach ($directChildren as $child) {
            $count++;
            $count += $this->countAllDownline($child->id);
        }

        return $count;
    }
}
