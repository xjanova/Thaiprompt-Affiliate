<?php

namespace Database\Seeders;

use App\Models\MlmMember;
use App\Models\MlmPackage;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * สร้างข้อมูล MLM Hierarchy สำหรับการทดสอบ 3 ระบบผัง
 *
 * โครงสร้าง Binary Tree (5 ชั้น เต็มผัง):
 * Level 0: 1 คน (Root)
 * Level 1: 2 คน
 * Level 2: 4 คน
 * Level 3: 8 คน
 * Level 4: 16 คน
 * รวม: 31 สมาชิก
 *
 * 3 ผังที่ต้องสอดคล้องกัน:
 * 1. Binary Tree: ผังไบนารี 2 ขา (binary_parent_id, binary_position)
 * 2. Unilevel Tree: ผังหลายขา (unilevel_sponsor_id)
 * 3. Genealogy Tree: ผังสายเลือด/แนะนำตรง (original_sponsor_id) - มีการแนะนำข้ามสายได้
 */
class MlmHierarchySeeder extends Seeder
{
    /**
     * ชื่อจริงภาษาไทยสำหรับทดสอบ
     */
    private array $thaiFirstNames = [
        'สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมศรี', 'สมบัติ',
        'สุรชัย', 'สุรศักดิ์', 'สุภาพร', 'สุภาภรณ์', 'สุนิสา',
        'วิชัย', 'วิภา', 'วิไล', 'วิรัตน์', 'วิภาวดี',
        'ประสิทธิ์', 'ประภา', 'ประเสริฐ', 'ประไพ', 'ประพันธ์',
        'ชัยวัฒน์', 'ชัยชนะ', 'ชุติมา', 'ชุลีกร', 'ชนิดา',
        'พิชัย', 'พิมพ์', 'พิไล', 'พิศมัย', 'พิมลพรรณ',
        'ธนากร', 'ธนาพร', 'ธิดา', 'ธิติมา', 'ธัญญา',
    ];

    private array $thaiLastNames = [
        'ใจดี', 'มีสุข', 'รุ่งเรือง', 'เจริญศรี', 'สมบูรณ์',
        'สวัสดิ์', 'ประเสริฐ', 'ศรีสุข', 'วงศ์สุวรรณ', 'พิพัฒน์',
        'ทองดี', 'ทองสุข', 'แก้วมณี', 'เพชรดี', 'รัตนา',
    ];

    /**
     * สร้างข้อมูล MLM Hierarchy 5 ชั้น (31 สมาชิก)
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌳 กำลังสร้างระบบ MLM Hierarchy 5 ชั้น (3 ผังสายงาน)...');
        $this->command->info('');

        // ลบข้อมูล demo เดิมก่อน
        $this->cleanupOldData();

        // 1. สร้าง/ดึง Default MLM Plan
        $plan = $this->getOrCreateDefaultPlan();
        $this->command->info("✅ MLM Plan ID: {$plan->id}");

        // 2. ดึง Default MLM Package
        $package = MlmPackage::where('slug', 'bronze-package')->first();
        if (!$package) {
            $this->command->error('❌ ไม่พบ MLM Package กรุณารัน MlmPackageSeeder ก่อน');
            return;
        }
        $this->command->info("✅ MLM Package: {$package->name}");

        // 3. สร้าง Admin User และ MLM Members ทั้ง 31 คน
        $members = $this->createAllMembers($plan, $package);

        // 4. แสดงผลลัพธ์
        $this->showResults($members);
    }

    /**
     * ลบข้อมูล demo เดิม
     *
     * @return void
     */
    private function cleanupOldData(): void
    {
        $this->command->info('🧹 กำลังลบข้อมูล demo เดิม...');

        // ลบ MLM Members ที่มี member_code เริ่มต้นด้วย MLM-DEMO-
        $deletedCount = MlmMember::where('member_code', 'LIKE', 'MLM-DEMO-%')->count();

        if ($deletedCount > 0) {
            // ลบ users ที่เกี่ยวข้อง
            $memberUserIds = MlmMember::where('member_code', 'LIKE', 'MLM-DEMO-%')
                ->pluck('user_id');

            MlmMember::where('member_code', 'LIKE', 'MLM-DEMO-%')->delete();
            User::whereIn('id', $memberUserIds)
                ->where('email', 'LIKE', 'mlm-member-%@example.com')
                ->delete();

            $this->command->info("   ลบ {$deletedCount} สมาชิก MLM เดิม");
        } else {
            $this->command->info('   ไม่มีข้อมูลเดิมที่ต้องลบ');
        }
    }

    /**
     * สร้างหรือดึง Default MLM Plan
     *
     * ใช้ลำดับการตรวจสอบดังนี้:
     * 1. หา plan ที่เป็น default (is_default = true)
     * 2. หา plan ที่มี slug = 'default-plan' (ป้องกัน duplicate)
     * 3. ใช้ plan แรกที่มี
     * 4. สร้างใหม่ด้วย firstOrCreate (ป้องกัน race condition)
     *
     * @return MlmPlan
     */
    private function getOrCreateDefaultPlan(): MlmPlan
    {
        // 1. ตรวจสอบ default plan ที่มีอยู่แล้ว
        $plan = MlmPlan::where('is_default', true)->first();
        if ($plan) {
            return $plan;
        }

        // 2. ตรวจสอบ plan ที่มี slug เดียวกัน (ป้องกัน duplicate key error)
        $plan = MlmPlan::where('slug', 'default-plan')->first();
        if ($plan) {
            // อัพเดทให้เป็น default ถ้ายังไม่ใช่
            if (!$plan->is_default) {
                $plan->update(['is_default' => true]);
            }
            return $plan;
        }

        // 3. ใช้ plan แรกที่มี
        $plan = MlmPlan::first();
        if ($plan) {
            return $plan;
        }

        // 4. สร้างใหม่ด้วย firstOrCreate เพื่อป้องกัน race condition
        return MlmPlan::firstOrCreate(
            ['slug' => 'default-plan'],
            [
                'name' => 'Default Commission Plan',
                'name_th' => 'แผนคอมมิชชันหลัก',
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
            ]
        );
    }

    /**
     * สร้างสมาชิก MLM ทั้ง 31 คน
     *
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @return array
     */
    private function createAllMembers(MlmPlan $plan, MlmPackage $package): array
    {
        $members = [];

        // ================================
        // โครงสร้าง Binary Tree (BFS - fill by level)
        // ================================
        // Level 0: M1
        // Level 1: M2 (L), M3 (R)
        // Level 2: M4 (M2-L), M5 (M2-R), M6 (M3-L), M7 (M3-R)
        // Level 3: M8-M15
        // Level 4: M16-M31
        //
        // Binary Index Formula:
        // - Parent of N = floor((N-1)/2)
        // - Left child of N = 2N + 1
        // - Right child of N = 2N + 2
        // - Position: odd index = left, even index = right

        // ================================
        // โครงสร้าง Original Sponsor (Genealogy) - มีการแนะนำข้ามสาย
        // ================================
        // ส่วนใหญ่ใช้ binary parent เป็น original sponsor
        // แต่บางคนมี original sponsor จากสายอื่น:
        // - M8: แนะนำโดย M3 (จากสายขวา)
        // - M10: แนะนำโดย M7 (จากสายขวา)
        // - M16: แนะนำโดย M6 (จากสายขวา)
        // - M20: แนะนำโดย M3 (จากสายขวา)
        // - M24: แนะนำโดย M2 (จากสายซ้าย)
        // - M28: แนะนำโดย M4 (จากสายซ้าย)

        $crossReferrals = [
            8 => 3,   // M8 แนะนำโดย M3 (ข้ามสาย)
            10 => 7,  // M10 แนะนำโดย M7 (ข้ามสาย)
            16 => 6,  // M16 แนะนำโดย M6 (ข้ามสาย)
            20 => 3,  // M20 แนะนำโดย M3 (ข้ามสาย)
            24 => 2,  // M24 แนะนำโดย M2 (ข้ามสาย)
            28 => 4,  // M28 แนะนำโดย M4 (ข้ามสาย)
        ];

        // ดึง Admin User สำหรับ Root
        $adminUser = User::where('email', 'superadmin@thaiprompt.com')->first();
        if (!$adminUser) {
            $adminUser = User::where('role', 'admin')->where('is_super_admin', true)->first();
        }
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'MLM Admin (Root)',
                'email' => 'mlm-admin@thaiprompt.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_super_admin' => true,
                'preferred_language' => 'th',
            ]);
        }

        // สร้าง Root Member (M1, index 0)
        $members[0] = $this->createMember(
            index: 0,
            user: $adminUser,
            plan: $plan,
            package: $package,
            members: $members,
            crossReferrals: $crossReferrals
        );
        $this->command->info("   M1 (Root): {$adminUser->name}");

        // สร้างสมาชิกที่เหลือ (M2-M31, index 1-30)
        for ($i = 1; $i <= 30; $i++) {
            $memberNum = $i + 1; // M2, M3, ... M31
            $memberCode = sprintf('MLM-DEMO-%04d', $memberNum);

            // สร้างชื่อแบบสุ่ม
            $firstName = $this->thaiFirstNames[array_rand($this->thaiFirstNames)];
            $lastName = $this->thaiLastNames[array_rand($this->thaiLastNames)];

            // สร้าง User
            $user = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => "mlm-member-{$memberNum}@example.com",
                'password' => Hash::make('password123'),
                'role' => 'affiliate',
                'is_super_admin' => false,
                'preferred_language' => 'th',
            ]);

            // สร้าง MLM Member
            $members[$i] = $this->createMember(
                index: $i,
                user: $user,
                plan: $plan,
                package: $package,
                members: $members,
                crossReferrals: $crossReferrals
            );
        }

        // อัพเดทสถิติ
        $this->updateStatistics($members);

        return $members;
    }

    /**
     * สร้าง MLM Member พร้อม 3 ผังสายงาน
     *
     * @param int $index ตำแหน่งใน array (0-30)
     * @param User $user
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @param array $members สมาชิกที่สร้างแล้ว
     * @param array $crossReferrals mapping ของ original sponsor ที่ข้ามสาย
     * @return MlmMember
     */
    private function createMember(
        int $index,
        User $user,
        MlmPlan $plan,
        MlmPackage $package,
        array $members,
        array $crossReferrals
    ): MlmMember {
        $memberNum = $index + 1; // M1, M2, ... M31
        $memberCode = $index === 0 ? 'MLM-DEMO-0001' : sprintf('MLM-DEMO-%04d', $memberNum);

        // ================================
        // คำนวณ Binary Tree Position
        // ================================
        $binaryParentIndex = $index > 0 ? floor(($index - 1) / 2) : null;
        $binaryPosition = null;
        if ($index > 0) {
            // index คี่ = ซ้าย, index คู่ = ขวา
            $binaryPosition = ($index % 2 === 1) ? 'left' : 'right';
        }
        $binaryParent = $binaryParentIndex !== null ? ($members[$binaryParentIndex] ?? null) : null;
        $binaryLevel = $index > 0 ? floor(log($index + 1, 2)) : 0;

        // คำนวณ binary_path
        $binaryPath = $this->calculateBinaryPath($index, $members);

        // ================================
        // คำนวณ Unilevel Position (ใช้โครงสร้างเดียวกับ Binary)
        // ================================
        $unilevelSponsor = $binaryParent; // ใช้ binary parent เป็น unilevel sponsor

        // คำนวณ unilevel_path
        $unilevelPath = null;
        if ($unilevelSponsor) {
            $unilevelPath = $unilevelSponsor->unilevel_path
                ? $unilevelSponsor->unilevel_path . '/' . $unilevelSponsor->id
                : (string) $unilevelSponsor->id;
        }

        // ================================
        // คำนวณ Original Sponsor (Genealogy)
        // ================================
        // ตรวจสอบว่ามี cross referral หรือไม่
        $originalSponsorIndex = null;
        if ($index > 0) {
            if (isset($crossReferrals[$memberNum])) {
                // มี cross referral - ใช้ sponsor ที่กำหนด
                $originalSponsorIndex = $crossReferrals[$memberNum] - 1; // convert M-number to index
            } else {
                // ใช้ binary parent เป็น original sponsor
                $originalSponsorIndex = $binaryParentIndex;
            }
        }
        $originalSponsor = $originalSponsorIndex !== null ? ($members[$originalSponsorIndex] ?? null) : null;

        // ================================
        // สร้าง MLM Member
        // ================================
        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $plan->id,
            'package_id' => $package->id,
            // 3 ผังสายงาน
            'original_sponsor_id' => $originalSponsor?->id,     // ผังสายเลือด (แนะนำตรงจริงๆ)
            'unilevel_sponsor_id' => $unilevelSponsor?->id,     // ผังหลายขา
            'unilevel_level' => $binaryLevel,                    // ใช้ level เดียวกับ binary
            'unilevel_path' => $unilevelPath,
            'binary_sponsor_id' => $binaryParent?->id,          // ผังไบนารี
            'binary_parent_id' => $binaryParent?->id,
            'binary_position' => $binaryPosition,
            'binary_path' => $binaryPath,
            // ข้อมูลอื่นๆ
            'status' => 'active',
            'is_qualified' => true,
            'member_code' => $memberCode,
            'joining_fee_paid' => $package->price ?? 990.00,
            'total_pv' => $package->pv_value ?? 990.00,
            'joined_at' => now()->subDays(rand(1, 365)),
            // สถิติเริ่มต้น
            'total_direct_referrals' => 0,
            'total_team_members' => 0,
            'left_leg_pv' => 0,
            'right_leg_pv' => 0,
            'left_leg_members' => 0,
            'right_leg_members' => 0,
        ]);

        return $member;
    }

    /**
     * คำนวณ binary_path
     *
     * @param int $index
     * @param array $members
     * @return string|null
     */
    private function calculateBinaryPath(int $index, array $members): ?string
    {
        if ($index === 0) {
            return null;
        }

        $path = [];
        $currentIndex = $index;

        while ($currentIndex > 0) {
            $parentIndex = floor(($currentIndex - 1) / 2);
            if (isset($members[$parentIndex])) {
                array_unshift($path, $members[$parentIndex]->id);
            }
            $currentIndex = $parentIndex;
        }

        return count($path) > 0 ? implode('/', $path) : null;
    }

    /**
     * อัพเดทสถิติของสมาชิก MLM
     *
     * @param array $members
     * @return void
     */
    private function updateStatistics(array $members): void
    {
        $this->command->info('');
        $this->command->info('📈 กำลังอัพเดทสถิติสายงาน...');

        foreach ($members as $member) {
            // นับ direct referrals (original sponsor - ผังสายเลือด)
            $directReferralsCount = MlmMember::where('original_sponsor_id', $member->id)->count();

            // นับ unilevel children
            $unilevelChildrenCount = MlmMember::where('unilevel_sponsor_id', $member->id)->count();

            // นับ binary children
            $leftChildCount = MlmMember::where('binary_parent_id', $member->id)
                ->where('binary_position', 'left')
                ->count();
            $rightChildCount = MlmMember::where('binary_parent_id', $member->id)
                ->where('binary_position', 'right')
                ->count();

            // นับ team members (recursive)
            $teamCount = $this->countAllDownline($member->id);

            // นับ left/right leg members
            $leftLegMembers = $this->countBinaryLegMembers($member->id, 'left');
            $rightLegMembers = $this->countBinaryLegMembers($member->id, 'right');

            $member->update([
                'total_direct_referrals' => $directReferralsCount,
                'total_team_members' => $teamCount,
                'left_leg_members' => $leftLegMembers,
                'right_leg_members' => $rightLegMembers,
            ]);
        }

        $this->command->info('✅ อัพเดทสถิติสำเร็จ');
    }

    /**
     * นับจำนวน downline ทั้งหมด (unilevel - recursive)
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

    /**
     * นับจำนวนสมาชิกในขา Binary
     *
     * @param int $memberId
     * @param string $position 'left' หรือ 'right'
     * @return int
     */
    private function countBinaryLegMembers(int $memberId, string $position): int
    {
        $child = MlmMember::where('binary_parent_id', $memberId)
            ->where('binary_position', $position)
            ->first();

        if (!$child) {
            return 0;
        }

        return 1 + $this->countBinarySubtree($child->id);
    }

    /**
     * นับสมาชิกทั้งหมดใน Binary subtree
     *
     * @param int $memberId
     * @return int
     */
    private function countBinarySubtree(int $memberId): int
    {
        $count = 0;
        $children = MlmMember::where('binary_parent_id', $memberId)->get();

        foreach ($children as $child) {
            $count++;
            $count += $this->countBinarySubtree($child->id);
        }

        return $count;
    }

    /**
     * แสดงผลลัพธ์การสร้าง
     *
     * @param array $members
     * @return void
     */
    private function showResults(array $members): void
    {
        $this->command->info('');
        $this->command->info('✨ สร้าง MLM Hierarchy สำเร็จ! รวม ' . count($members) . ' สมาชิก');
        $this->command->info('');
        $this->command->info('📊 โครงสร้าง Binary Tree (5 ชั้น):');
        $this->command->info('   Level 0 (Root): 1 คน');
        $this->command->info('   Level 1: 2 คน (ซ้าย-ขวา)');
        $this->command->info('   Level 2: 4 คน');
        $this->command->info('   Level 3: 8 คน');
        $this->command->info('   Level 4: 16 คน');
        $this->command->info('');
        $this->command->info('🔗 การเชื่อมโยง 3 ผัง:');
        $this->command->info('   • Binary Tree: binary_parent_id + binary_position');
        $this->command->info('   • Unilevel Tree: unilevel_sponsor_id (mirror Binary)');
        $this->command->info('   • Genealogy: original_sponsor_id (มีข้ามสาย 6 คน)');
        $this->command->info('');
        $this->command->info('👥 สมาชิกที่มีการแนะนำข้ามสาย (Cross-referral):');
        $this->command->info('   • M8 ← M3 (จากขวามาซ้าย)');
        $this->command->info('   • M10 ← M7 (จากขวามาซ้าย)');
        $this->command->info('   • M16 ← M6 (จากขวามาซ้าย)');
        $this->command->info('   • M20 ← M3 (จากขวามาซ้าย)');
        $this->command->info('   • M24 ← M2 (จากซ้ายมาขวา)');
        $this->command->info('   • M28 ← M4 (จากซ้ายมาขวา)');
        $this->command->info('');
        $this->command->info('🔐 รหัสผ่านทุกบัญชี: password123');
        $this->command->info('');
    }
}
