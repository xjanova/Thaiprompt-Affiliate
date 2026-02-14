<?php

namespace Database\Seeders;

use App\Models\MlmMember;
use App\Models\MlmPackage;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ThaipromptMlmSeeder - สร้างข้อมูล MLM สำหรับ Thaiprompt
 *
 * โครงสร้าง:
 * 1. Original Sponsor: ทุกคนแนะนำตรงโดย Admin
 * 2. Binary Tree: เต็มชั้น 5 ระดับ (31 คน)
 *    Level 0:              TP-00
 *    Level 1:        TP-01       TP-02
 *    Level 2:    TP-03  TP-04  TP-05  TP-06
 *    Level 3:  TP-07 ... TP-14 (8 คน)
 *    Level 4:  TP-15 ... TP-30 (16 คน)
 * 3. Unilevel: ต่อเรียงลำดับ (Admin → TP-00 → TP-01 → ...)
 */
class ThaipromptMlmSeeder extends Seeder
{
    /**
     * จำนวนสมาชิก Thaiprompt (TP-00 ถึง TP-30)
     */
    private const MEMBER_COUNT = 31;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 ThaipromptMlmSeeder - สร้างระบบ MLM');
        $this->command->info('');

        // 1. ลบข้อมูล demo เก่าทั้งหมด
        $this->cleanupAllDemoData();

        // 2. ดึง/สร้าง MLM Plan และ Package
        $plan = $this->getOrCreateDefaultPlan();
        $package = $this->getOrCreateDefaultPackage();

        // 3. ดึง Admin User (Root Leader)
        $admin = $this->getAdminUser();
        $adminMember = $this->getOrCreateAdminMember($admin, $plan, $package);

        // 4. สร้าง Thaiprompt 00-30 (31 คน) - 2 ขั้นตอน
        $members = $this->createAllMembers($adminMember, $plan, $package);
        $this->updateBinaryRelationships($members, $adminMember);

        // 5. อัพเดทสถิติ
        $this->updateStatistics($adminMember, $members);

        // 6. แสดงผลลัพธ์
        $this->showResults();
    }

    /**
     * ลบข้อมูล demo เก่าทั้งหมด
     */
    private function cleanupAllDemoData(): void
    {
        $this->command->info('🧹 กำลังลบข้อมูล demo เก่าทั้งหมด...');

        $emailPatterns = [
            'mlm-member-%@example.com',
            'affiliate%@example.com',
            'user%@example.com',
            'thaiprompt%@thaiprompt.com',
        ];

        $memberCodePatterns = [
            'MLM-DEMO-%',
            'TP-%',
        ];

        $totalDeleted = 0;

        foreach ($memberCodePatterns as $pattern) {
            $count = MlmMember::withTrashed()
                ->where('member_code', 'LIKE', $pattern)
                ->count();

            if ($count > 0) {
                $userIds = MlmMember::withTrashed()
                    ->where('member_code', 'LIKE', $pattern)
                    ->pluck('user_id')
                    ->toArray();

                MlmMember::withTrashed()
                    ->where('member_code', 'LIKE', $pattern)
                    ->forceDelete();

                User::whereIn('id', $userIds)
                    ->where('role', '!=', 'admin')
                    ->delete();

                $totalDeleted += $count;
            }
        }

        foreach ($emailPatterns as $pattern) {
            $count = User::where('email', 'LIKE', $pattern)
                ->where('role', '!=', 'admin')
                ->count();

            if ($count > 0) {
                User::where('email', 'LIKE', $pattern)
                    ->where('role', '!=', 'admin')
                    ->delete();

                $totalDeleted += $count;
            }
        }

        $this->command->info($totalDeleted > 0
            ? "   ✅ ลบข้อมูลเก่าเสร็จ ({$totalDeleted} records)"
            : '   ไม่มีข้อมูลเดิมที่ต้องลบ');
    }

    /**
     * ดึงหรือสร้าง Default MLM Plan
     */
    private function getOrCreateDefaultPlan(): MlmPlan
    {
        return MlmPlan::where('is_default', true)->first()
            ?? MlmPlan::first()
            ?? MlmPlan::create([
                'name' => 'Thaiprompt Plan',
                'name_th' => 'แผน Thaiprompt',
                'slug' => 'thaiprompt-plan',
                'description' => 'Default Thaiprompt MLM Plan',
                'description_th' => 'แผน MLM มาตรฐาน Thaiprompt',
                'type' => 'hybrid',
                'is_active' => true,
                'is_default' => true,
                'color' => '#6366f1',
                'icon' => 'crown',
                'sort_order' => 1,
                'joining_fee' => 0,
                'requires_joining_fee' => false,
            ]);
    }

    /**
     * ดึงหรือสร้าง Default MLM Package
     */
    private function getOrCreateDefaultPackage(): MlmPackage
    {
        return MlmPackage::where('slug', 'bronze-package')->first()
            ?? MlmPackage::first()
            ?? MlmPackage::create([
                'name' => 'Starter Package',
                'name_th' => 'แพ็คเกจเริ่มต้น',
                'slug' => 'starter-package',
                'description' => 'Basic starter package',
                'price' => 0,
                'pv_value' => 100,
                'is_active' => true,
                'sort_order' => 1,
            ]);
    }

    /**
     * ดึง Admin User
     */
    private function getAdminUser(): User
    {
        $admin = User::where('email', 'superadmin@thaiprompt.com')->first()
            ?? User::where('role', 'admin')->where('is_super_admin', true)->first();

        if (! $admin) {
            $this->command->error('❌ ไม่พบ Super Admin กรุณารัน AdminUsersSeeder ก่อน');
            throw new \Exception('Super Admin not found');
        }

        $this->command->info("✅ Admin: {$admin->name}");

        return $admin;
    }

    /**
     * ดึงหรือสร้าง Admin MLM Member
     */
    private function getOrCreateAdminMember(User $admin, MlmPlan $plan, MlmPackage $package): MlmMember
    {
        return MlmMember::where('user_id', $admin->id)->first()
            ?? MlmMember::create([
                'user_id' => $admin->id,
                'mlm_plan_id' => $plan->id,
                'package_id' => $package->id,
                'member_code' => 'ADMIN-0001',
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now()->subYears(1),
                'total_pv' => 0,
                'total_direct_referrals' => 0,
                'total_team_members' => 0,
            ]);
    }

    /**
     * สร้าง Thaiprompt Members ทั้งหมด (ขั้นตอนที่ 1)
     * สร้าง Users และ MlmMembers ก่อน โดยยังไม่ตั้งค่า binary relationships
     */
    private function createAllMembers(MlmMember $adminMember, MlmPlan $plan, MlmPackage $package): array
    {
        $this->command->info('');
        $this->command->info('👥 กำลังสร้าง Thaiprompt 00-30 (31 คน)...');

        $members = [];
        $previousMember = $adminMember;

        for ($i = 0; $i < self::MEMBER_COUNT; $i++) {
            $memberNum = str_pad($i, 2, '0', STR_PAD_LEFT);
            $memberCode = "TP-{$memberNum}";

            // สร้าง User
            $user = User::create([
                'name' => "Thaiprompt {$memberNum}",
                'email' => "thaiprompt{$memberNum}@thaiprompt.com",
                'password' => Hash::make('password123'),
                'role' => 'affiliate',
                'is_super_admin' => false,
                'preferred_language' => 'th',
            ]);

            // Unilevel: ต่อเรียงลำดับจากคนก่อนหน้า
            $unilevelLevel = $i + 1;
            $unilevelPath = $previousMember->unilevel_path
                ? $previousMember->unilevel_path.'/'.$previousMember->id
                : (string) $previousMember->id;

            // สร้าง MLM Member (Binary จะอัพเดททีหลัง)
            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $plan->id,
                'package_id' => $package->id,
                'member_code' => $memberCode,
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now()->subDays(self::MEMBER_COUNT - $i),

                // Original Sponsor = Admin (ทุกคนแนะนำตรงโดย Admin)
                'original_sponsor_id' => $adminMember->id,

                // Binary Tree (จะอัพเดทในขั้นตอนที่ 2)
                'binary_sponsor_id' => null,
                'binary_parent_id' => null,
                'binary_position' => null,
                'binary_path' => null,

                // Unilevel Tree - ต่อเรียงลำดับ
                'unilevel_sponsor_id' => $previousMember->id,
                'unilevel_level' => $unilevelLevel,
                'unilevel_path' => $unilevelPath,

                // Stats
                'total_pv' => $package->pv_value ?? 100,
                'total_direct_referrals' => 0,
                'total_team_members' => 0,
                'left_leg_members' => 0,
                'right_leg_members' => 0,
            ]);

            $members[$i] = $member;
            $previousMember = $member;
        }

        $this->command->info('   ✅ สร้าง Users และ Members เสร็จ');

        return $members;
    }

    /**
     * อัพเดท Binary Tree Relationships (ขั้นตอนที่ 2)
     *
     * Full Binary Tree:
     * - Parent ของ index N = floor((N-1) / 2)
     * - Position: odd = left, even = right
     *
     * Level 0: index 0 (TP-00)
     * Level 1: index 1-2 (TP-01, TP-02)
     * Level 2: index 3-6 (TP-03 - TP-06)
     * Level 3: index 7-14 (TP-07 - TP-14)
     * Level 4: index 15-30 (TP-15 - TP-30)
     */
    private function updateBinaryRelationships(array $members, MlmMember $adminMember): void
    {
        $this->command->info('');
        $this->command->info('🌳 กำลังสร้าง Binary Tree (เต็มชั้น 5 ระดับ)...');

        foreach ($members as $index => $member) {
            if ($index === 0) {
                // TP-00 เป็น root ของ binary tree ต่อจาก Admin
                $member->update([
                    'binary_sponsor_id' => $adminMember->id,
                    'binary_parent_id' => $adminMember->id,
                    'binary_position' => 'left',
                    'binary_path' => (string) $adminMember->id,
                ]);

                continue;
            }

            // คำนวณ parent index (full binary tree formula)
            $parentIndex = (int) floor(($index - 1) / 2);
            $parent = $members[$parentIndex];

            // Position: odd index = left, even index = right
            $position = ($index % 2 === 1) ? 'left' : 'right';

            // สร้าง path
            $path = $parent->binary_path.'/'.$parent->id;

            $member->update([
                'binary_sponsor_id' => $parent->id,
                'binary_parent_id' => $parent->id,
                'binary_position' => $position,
                'binary_path' => $path,
            ]);
        }

        $this->command->info('   ✅ สร้าง Binary Tree เสร็จ');
        $this->command->info('');
        $this->command->info('   โครงสร้าง Binary Tree:');
        $this->command->info('   Level 0: TP-00');
        $this->command->info('   Level 1: TP-01 (L), TP-02 (R)');
        $this->command->info('   Level 2: TP-03, TP-04, TP-05, TP-06');
        $this->command->info('   Level 3: TP-07 - TP-14 (8 คน)');
        $this->command->info('   Level 4: TP-15 - TP-30 (16 คน)');
    }

    /**
     * อัพเดทสถิติ
     */
    private function updateStatistics(MlmMember $adminMember, array $members): void
    {
        $this->command->info('');
        $this->command->info('📊 กำลังอัพเดทสถิติ...');

        // อัพเดท Admin
        $adminMember->update([
            'total_direct_referrals' => self::MEMBER_COUNT,
            'total_team_members' => self::MEMBER_COUNT,
            'left_leg_members' => self::MEMBER_COUNT, // ทุกคนอยู่ใต้ left (TP-00)
        ]);

        // อัพเดทแต่ละ member
        foreach ($members as $index => $member) {
            // นับ left/right leg members จาก binary tree
            $leftCount = $this->countBinaryChildren($member->id, 'left', $members);
            $rightCount = $this->countBinaryChildren($member->id, 'right', $members);

            // นับ unilevel downline
            $teamCount = $this->countUnilevelDownline($member->id, $members);

            $member->update([
                'left_leg_members' => $leftCount,
                'right_leg_members' => $rightCount,
                'total_team_members' => $teamCount,
            ]);
        }

        $this->command->info('   ✅ อัพเดทสถิติเสร็จ');
    }

    /**
     * นับ binary children ในตำแหน่งที่กำหนด
     */
    private function countBinaryChildren(int $memberId, string $position, array $members): int
    {
        $count = 0;
        foreach ($members as $member) {
            if ($member->binary_parent_id === $memberId && $member->binary_position === $position) {
                $count++;
                $count += $this->countAllBinaryDescendants($member->id, $members);
            }
        }

        return $count;
    }

    /**
     * นับ binary descendants ทั้งหมด
     */
    private function countAllBinaryDescendants(int $memberId, array $members): int
    {
        $count = 0;
        foreach ($members as $member) {
            if ($member->binary_parent_id === $memberId) {
                $count++;
                $count += $this->countAllBinaryDescendants($member->id, $members);
            }
        }

        return $count;
    }

    /**
     * นับ unilevel downline
     */
    private function countUnilevelDownline(int $memberId, array $members): int
    {
        $count = 0;
        foreach ($members as $member) {
            if ($member->unilevel_sponsor_id === $memberId) {
                $count++;
                $count += $this->countUnilevelDownline($member->id, $members);
            }
        }

        return $count;
    }

    /**
     * แสดงผลลัพธ์
     */
    private function showResults(): void
    {
        $this->command->info('');
        $this->command->info('✨ สร้าง Thaiprompt MLM สำเร็จ!');
        $this->command->info('');
        $this->command->info('📊 สรุป:');
        $this->command->info('   • จำนวนสมาชิก: 31 คน (Thaiprompt 00-30)');
        $this->command->info('   • Original Sponsor: ทุกคนแนะนำตรงโดย Admin');
        $this->command->info('');
        $this->command->info('🌳 Binary Tree (เต็มชั้น 5 ระดับ):');
        $this->command->info('                    TP-00');
        $this->command->info('              TP-01       TP-02');
        $this->command->info('           TP-03 TP-04  TP-05 TP-06');
        $this->command->info('              ... (31 คน) ...');
        $this->command->info('');
        $this->command->info('🔗 Unilevel Tree (ต่อเรียงลำดับ):');
        $this->command->info('   Admin → TP-00 → TP-01 → ... → TP-30');
        $this->command->info('');
        $this->command->info('🔐 ข้อมูลเข้าสู่ระบบ:');
        $this->command->info('   Email: thaipromptXX@thaiprompt.com (XX = 00-30)');
        $this->command->info('   Password: password123');
        $this->command->info('');
    }
}
