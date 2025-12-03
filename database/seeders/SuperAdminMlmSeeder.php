<?php

namespace Database\Seeders;

use App\Models\MlmMember;
use App\Models\MlmPackage;
use App\Models\MlmPlan;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\Concerns\SafeSeeder;
use Illuminate\Database\Seeder;

/**
 * SuperAdminMlmSeeder
 *
 * สร้าง MlmMember สำหรับ SuperAdmin โดยอัตโนมัติ
 * เพื่อให้ SuperAdmin เป็น "แม่ทีมใหญ่" (Root Leader) ในระบบ MLM
 *
 * สิ่งที่ทำ:
 * 1. สร้าง MlmMember record สำหรับ SuperAdmin
 * 2. ตั้งค่า default_sponsor_member_code ให้ชี้ไปที่ SuperAdmin
 * 3. สมาชิกใหม่ที่ไม่มี referral code จะอยู่ใต้ SuperAdmin โดยอัตโนมัติ
 */
class SuperAdminMlmSeeder extends Seeder
{
    use SafeSeeder;

    /**
     * Member code สำหรับ SuperAdmin (Root Leader)
     *
     * ใช้รูปแบบ ADMIN-XXXX แทน MLM-DEMO-XXXX
     * เพื่อแยกจาก demo data
     */
    private const SUPERADMIN_MEMBER_CODE = 'ADMIN-0001';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('👑 กำลังตั้งค่า SuperAdmin เป็น Root Leader ของระบบ MLM...');
        $this->command->info('');

        // ตรวจสอบว่าตาราง mlm_members มีอยู่หรือไม่
        if (!$this->requireTable('mlm_members', 'SuperAdminMlmSeeder')) {
            return;
        }

        // 1. หา SuperAdmin user
        $superAdmin = $this->findSuperAdmin();
        if (!$superAdmin) {
            $this->command->error('❌ ไม่พบ SuperAdmin user - กรุณารัน DemoUsersSeeder ก่อน');
            return;
        }
        $this->command->info("✅ พบ SuperAdmin: {$superAdmin->name} ({$superAdmin->email})");

        // 2. ตรวจสอบว่า SuperAdmin มี MlmMember อยู่แล้วหรือไม่
        $existingMember = MlmMember::withTrashed()
            ->where('user_id', $superAdmin->id)
            ->first();

        if ($existingMember) {
            // ถ้าถูก soft-delete ให้ restore กลับมา
            if ($existingMember->trashed()) {
                $existingMember->restore();
                $this->command->info('🔄 Restored MlmMember ที่ถูก soft-delete');
            }

            // อัพเดท member_code ให้เป็น ADMIN-0001
            if ($existingMember->member_code !== self::SUPERADMIN_MEMBER_CODE) {
                $existingMember->update([
                    'member_code' => self::SUPERADMIN_MEMBER_CODE,
                ]);
                $this->command->info("🔄 อัพเดท member_code เป็น: " . self::SUPERADMIN_MEMBER_CODE);
            }

            $member = $existingMember;
            $this->command->info("ℹ️  SuperAdmin มี MlmMember อยู่แล้ว (ID: {$member->id})");
        } else {
            // 3. หา Default MLM Plan
            $plan = $this->getDefaultPlan();
            if (!$plan) {
                $this->command->error('❌ ไม่พบ MLM Plan - กรุณารัน MlmPlanSeeder ก่อน');
                return;
            }
            $this->command->info("✅ MLM Plan: {$plan->name}");

            // 4. หา Default MLM Package
            $package = $this->getDefaultPackage();
            if (!$package) {
                $this->command->error('❌ ไม่พบ MLM Package - กรุณารัน MlmPackageSeeder ก่อน');
                return;
            }
            $this->command->info("✅ MLM Package: {$package->name}");

            // 5. สร้าง MlmMember สำหรับ SuperAdmin
            $member = $this->createSuperAdminMember($superAdmin, $plan, $package);
            $this->command->info("✅ สร้าง MlmMember สำหรับ SuperAdmin สำเร็จ!");
        }

        // 6. ตั้งค่า default_sponsor_member_code
        $this->setDefaultSponsor($member);

        $this->command->info('');
        $this->command->info('🎉 SuperAdmin พร้อมเป็น Root Leader แล้ว!');
        $this->command->info("   Member Code: {$member->member_code}");
        $this->command->info('   สมาชิกใหม่ที่ไม่มี referral code จะอยู่ใต้ SuperAdmin โดยอัตโนมัติ');
        $this->command->info('');
    }

    /**
     * หา SuperAdmin user
     *
     * ลำดับการค้นหา:
     * 1. หา email = superadmin@thaiprompt.com
     * 2. หา is_super_admin = true
     * 3. หา role = admin (fallback)
     *
     * @return User|null
     */
    private function findSuperAdmin(): ?User
    {
        // 1. หาจาก email เฉพาะ
        $user = User::where('email', 'superadmin@thaiprompt.com')->first();
        if ($user) {
            return $user;
        }

        // 2. หาจาก is_super_admin flag
        $user = User::where('is_super_admin', true)->first();
        if ($user) {
            return $user;
        }

        // 3. Fallback: หา admin คนแรก
        return User::where('role', 'admin')->first();
    }

    /**
     * หา Default MLM Plan
     *
     * @return MlmPlan|null
     */
    private function getDefaultPlan(): ?MlmPlan
    {
        // หา plan ที่เป็น default
        $plan = MlmPlan::where('is_default', true)->first();
        if ($plan) {
            return $plan;
        }

        // หา plan ที่มี slug = default-plan
        $plan = MlmPlan::where('slug', 'default-plan')->first();
        if ($plan) {
            return $plan;
        }

        // Fallback: ใช้ plan แรกที่มี
        return MlmPlan::first();
    }

    /**
     * หา Default MLM Package
     *
     * ⚠️ หมายเหตุ: ตาราง mlm_packages ไม่มีคอลัมน์ is_default
     * ใช้ sort_order หรือ slug ในการหา default package แทน
     *
     * @return MlmPackage|null
     */
    private function getDefaultPackage(): ?MlmPackage
    {
        // หา bronze-package (standard package)
        $package = MlmPackage::where('slug', 'bronze-package')->first();
        if ($package) {
            return $package;
        }

        // หา package ที่ active และราคาต่ำสุด (เหมาะสำหรับ default)
        $package = MlmPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->first();
        if ($package) {
            return $package;
        }

        // Fallback: ใช้ package แรกที่มี
        return MlmPackage::first();
    }

    /**
     * สร้าง MlmMember สำหรับ SuperAdmin
     *
     * SuperAdmin จะเป็น Root (ไม่มี sponsor)
     *
     * @param User $superAdmin
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @return MlmMember
     */
    private function createSuperAdminMember(User $superAdmin, MlmPlan $plan, MlmPackage $package): MlmMember
    {
        return MlmMember::create([
            'user_id' => $superAdmin->id,
            'mlm_plan_id' => $plan->id,
            'package_id' => $package->id,

            // SuperAdmin ไม่มี sponsor (เป็น Root)
            'original_sponsor_id' => null,
            'unilevel_sponsor_id' => null,
            'unilevel_level' => 0,           // Level 0 = Root
            'unilevel_path' => null,

            'binary_sponsor_id' => null,
            'binary_parent_id' => null,
            'binary_position' => null,
            'binary_path' => null,

            // ข้อมูลอื่นๆ
            'status' => 'active',
            'is_qualified' => true,
            'member_code' => self::SUPERADMIN_MEMBER_CODE,
            'joining_fee_paid' => 0,         // SuperAdmin ไม่ต้องจ่าย
            'total_pv' => 0,
            'joined_at' => now(),

            // สถิติเริ่มต้น
            'total_direct_referrals' => 0,
            'total_team_members' => 0,
            'left_leg_pv' => 0,
            'right_leg_pv' => 0,
            'left_leg_members' => 0,
            'right_leg_members' => 0,
        ]);
    }

    /**
     * ตั้งค่า default_sponsor_member_code ให้ชี้ไปที่ SuperAdmin
     *
     * @param MlmMember $member
     * @return void
     */
    private function setDefaultSponsor(MlmMember $member): void
    {
        $currentValue = Setting::get('default_sponsor_member_code');

        if ($currentValue === $member->member_code) {
            $this->command->info("ℹ️  default_sponsor_member_code ถูกต้องแล้ว: {$member->member_code}");
            return;
        }

        Setting::set(
            'default_sponsor_member_code',
            $member->member_code,
            'string',
            'mlm'
        );

        $this->command->info("✅ ตั้งค่า default_sponsor_member_code = {$member->member_code}");
    }
}
