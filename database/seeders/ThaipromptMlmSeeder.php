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
 * โครงสร้างแบบง่าย - สายเดียวต่อเรียงลำดับ:
 * Admin → TP-00 → TP-01 → TP-02 → ... → TP-30
 *
 * ทุกคนแนะนำตรงโดย Admin (original_sponsor)
 * แต่ผังต่อเรียงลำดับ (binary/unilevel parent = คนก่อนหน้า)
 */
class ThaipromptMlmSeeder extends Seeder
{
    /**
     * จำนวนสมาชิก Thaiprompt (TP-00 ถึง TP-30)
     */
    private const MEMBER_COUNT = 31;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 ThaipromptMlmSeeder - สร้างระบบ MLM แบบสายเดียว');
        $this->command->info('');

        // 1. ลบข้อมูล demo เก่าทั้งหมด
        $this->cleanupAllDemoData();

        // 2. ดึง/สร้าง MLM Plan และ Package
        $plan = $this->getOrCreateDefaultPlan();
        $package = $this->getOrCreateDefaultPackage();

        // 3. ดึง Admin User (Root Leader)
        $admin = $this->getAdminUser();
        $adminMember = $this->getOrCreateAdminMember($admin, $plan, $package);

        // 4. สร้าง Thaiprompt 00-30 (31 คน) ต่อเรียงลำดับ
        $this->createThaipromptMembers($adminMember, $plan, $package);

        // 5. แสดงผลลัพธ์
        $this->showResults($adminMember);
    }

    /**
     * ลบข้อมูล demo เก่าทั้งหมด
     *
     * @return void
     */
    private function cleanupAllDemoData(): void
    {
        $this->command->info('🧹 กำลังลบข้อมูล demo เก่าทั้งหมด...');

        // รายการ email patterns ที่ต้องลบ
        $emailPatterns = [
            'mlm-member-%@example.com',
            'affiliate%@example.com',
            'user%@example.com',
            'thaiprompt%@thaiprompt.com',
        ];

        // รายการ member_code patterns ที่ต้องลบ
        $memberCodePatterns = [
            'MLM-DEMO-%',
            'TP-%',
        ];

        $totalDeleted = 0;

        // ลบ MlmMembers ก่อน (เพราะมี foreign key กับ User)
        foreach ($memberCodePatterns as $pattern) {
            $count = MlmMember::withTrashed()
                ->where('member_code', 'LIKE', $pattern)
                ->count();

            if ($count > 0) {
                // ดึง user_ids ก่อนลบ
                $userIds = MlmMember::withTrashed()
                    ->where('member_code', 'LIKE', $pattern)
                    ->pluck('user_id')
                    ->toArray();

                // Force delete MLM Members
                MlmMember::withTrashed()
                    ->where('member_code', 'LIKE', $pattern)
                    ->forceDelete();

                // ลบ Users ที่เกี่ยวข้อง (ยกเว้น admin)
                User::whereIn('id', $userIds)
                    ->where('role', '!=', 'admin')
                    ->delete();

                $this->command->info("   ลบ {$count} MLM members ({$pattern})");
                $totalDeleted += $count;
            }
        }

        // ลบ Users ที่เป็น demo (ที่ยังไม่ถูกลบ)
        foreach ($emailPatterns as $pattern) {
            $count = User::where('email', 'LIKE', $pattern)
                ->where('role', '!=', 'admin')
                ->count();

            if ($count > 0) {
                User::where('email', 'LIKE', $pattern)
                    ->where('role', '!=', 'admin')
                    ->delete();

                $this->command->info("   ลบ {$count} users ({$pattern})");
                $totalDeleted += $count;
            }
        }

        if ($totalDeleted === 0) {
            $this->command->info('   ไม่มีข้อมูลเดิมที่ต้องลบ');
        } else {
            $this->command->info("   ✅ ลบข้อมูลเก่าเสร็จ (รวม {$totalDeleted} records)");
        }
    }

    /**
     * ดึงหรือสร้าง Default MLM Plan
     *
     * @return MlmPlan
     */
    private function getOrCreateDefaultPlan(): MlmPlan
    {
        $plan = MlmPlan::where('is_default', true)->first();
        if ($plan) {
            return $plan;
        }

        $plan = MlmPlan::first();
        if ($plan) {
            return $plan;
        }

        return MlmPlan::create([
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
     *
     * @return MlmPackage
     */
    private function getOrCreateDefaultPackage(): MlmPackage
    {
        $package = MlmPackage::where('slug', 'bronze-package')->first();
        if ($package) {
            return $package;
        }

        $package = MlmPackage::first();
        if ($package) {
            return $package;
        }

        return MlmPackage::create([
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
     *
     * @return User
     */
    private function getAdminUser(): User
    {
        $admin = User::where('email', 'superadmin@thaiprompt.com')->first();

        if (!$admin) {
            $admin = User::where('role', 'admin')
                ->where('is_super_admin', true)
                ->first();
        }

        if (!$admin) {
            $this->command->error('❌ ไม่พบ Super Admin กรุณารัน AdminUsersSeeder ก่อน');
            throw new \Exception('Super Admin not found');
        }

        $this->command->info("✅ Admin: {$admin->name} ({$admin->email})");
        return $admin;
    }

    /**
     * ดึงหรือสร้าง Admin MLM Member
     *
     * @param User $admin
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @return MlmMember
     */
    private function getOrCreateAdminMember(User $admin, MlmPlan $plan, MlmPackage $package): MlmMember
    {
        $member = MlmMember::where('user_id', $admin->id)->first();

        if ($member) {
            return $member;
        }

        return MlmMember::create([
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
     * สร้าง Thaiprompt Members (00-30) ต่อเรียงลำดับ
     *
     * โครงสร้าง:
     * Admin → TP-00 → TP-01 → TP-02 → ... → TP-30
     *
     * - original_sponsor_id = Admin (ทุกคนแนะนำตรงโดย Admin)
     * - binary_parent_id = คนก่อนหน้า (ต่อเรียงลำดับ)
     * - unilevel_sponsor_id = คนก่อนหน้า (ต่อเรียงลำดับ)
     *
     * @param MlmMember $adminMember
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @return void
     */
    private function createThaipromptMembers(MlmMember $adminMember, MlmPlan $plan, MlmPackage $package): void
    {
        $this->command->info('');
        $this->command->info('👥 กำลังสร้าง Thaiprompt 00-30 (31 คน) ต่อเรียงลำดับ...');

        // คนก่อนหน้า (เริ่มจาก Admin)
        $previousMember = $adminMember;
        $previousPath = (string) $adminMember->id;

        for ($i = 0; $i < self::MEMBER_COUNT; $i++) {
            $memberNum = str_pad($i, 2, '0', STR_PAD_LEFT); // 00, 01, 02, ...
            $memberCode = "TP-{$memberNum}";
            $email = "thaiprompt{$memberNum}@thaiprompt.com";

            // สร้าง User
            $user = User::create([
                'name' => "Thaiprompt {$memberNum}",
                'email' => $email,
                'password' => Hash::make('password123'),
                'role' => 'affiliate',
                'is_super_admin' => false,
                'preferred_language' => 'th',
            ]);

            // คำนวณ level และ path
            $level = $i + 1; // TP-00 = level 1, TP-01 = level 2, ...
            $currentPath = $previousPath . '/' . $previousMember->id;

            // สร้าง MLM Member ต่อจากคนก่อนหน้า
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

                // Binary Tree - ต่อจากคนก่อนหน้า (ซ้ายเสมอ)
                'binary_sponsor_id' => $previousMember->id,
                'binary_parent_id' => $previousMember->id,
                'binary_position' => 'left',
                'binary_path' => $currentPath,

                // Unilevel Tree - ต่อจากคนก่อนหน้า
                'unilevel_sponsor_id' => $previousMember->id,
                'unilevel_level' => $level,
                'unilevel_path' => $currentPath,

                // Stats
                'total_pv' => $package->pv_value ?? 100,
                'total_direct_referrals' => 0,
                'total_team_members' => 0,
                'left_leg_members' => 0,
                'right_leg_members' => 0,
                'left_leg_pv' => 0,
                'right_leg_pv' => 0,
            ]);

            // อัพเดทคนก่อนหน้า
            $previousMember->increment('left_leg_members');
            $previousMember->increment('total_team_members');

            // เตรียมสำหรับคนถัดไป
            $previousMember = $member;
            $previousPath = $currentPath;

            // แสดง progress
            if ($i % 10 === 0 || $i === self::MEMBER_COUNT - 1) {
                $this->command->info("   สร้าง {$memberCode} ต่อจาก " . ($i === 0 ? 'Admin' : 'TP-' . str_pad($i - 1, 2, '0', STR_PAD_LEFT)));
            }
        }

        // อัพเดท Admin's direct referrals
        $adminMember->update([
            'total_direct_referrals' => self::MEMBER_COUNT,
            'total_team_members' => self::MEMBER_COUNT,
        ]);

        $this->command->info('   ✅ สร้างสมาชิกเสร็จ!');
    }

    /**
     * แสดงผลลัพธ์
     *
     * @param MlmMember $adminMember
     * @return void
     */
    private function showResults(MlmMember $adminMember): void
    {
        $this->command->info('');
        $this->command->info('✨ สร้าง Thaiprompt MLM สำเร็จ!');
        $this->command->info('');
        $this->command->info('📊 สรุป:');
        $this->command->info('   • จำนวนสมาชิก: ' . self::MEMBER_COUNT . ' คน (Thaiprompt 00-30)');
        $this->command->info('');
        $this->command->info('🔗 โครงสร้างสายงาน (ต่อเรียงลำดับ):');
        $this->command->info('   Admin → TP-00 → TP-01 → TP-02 → ... → TP-30');
        $this->command->info('');
        $this->command->info('👤 Original Sponsor:');
        $this->command->info('   ทุกคน (31 คน) แนะนำตรงโดย Admin');
        $this->command->info('');
        $this->command->info('🌳 Binary/Unilevel Tree:');
        $this->command->info('   ต่อเรียงลำดับจากคนก่อนหน้า (สายเดียว)');
        $this->command->info('');
        $this->command->info('🔐 ข้อมูลเข้าสู่ระบบ:');
        $this->command->info('   Email: thaipromptXX@thaiprompt.com (XX = 00-30)');
        $this->command->info('   Password: password123');
        $this->command->info('');
    }
}
