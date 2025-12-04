<?php

namespace Database\Seeders;

use App\Models\MlmMember;
use App\Models\MlmPackage;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * ThaipromptMlmSeeder - สร้างข้อมูล MLM สำหรับ Thaiprompt
 *
 * ลบ demo users เก่าทั้งหมด และสร้างใหม่ด้วยชื่อ Thaiprompt 00-30
 *
 * โครงสร้าง:
 * 1. Binary Tree: 5 ชั้นเต็ม (31 คน) - แบบ BFS
 * 2. Unilevel Tree: ชั้นละ 5 คน - แบบ BFS เติมเต็มชั้น
 * 3. Original Sponsor: ทุกคนแนะนำตรงโดย Admin คนเดียว
 */
class ThaipromptMlmSeeder extends Seeder
{
    /**
     * จำนวนสมาชิก Thaiprompt (TP-00 ถึง TP-30)
     */
    private const MEMBER_COUNT = 31;

    /**
     * ความกว้างของ Unilevel Tree (ลูกต่อ parent)
     */
    private const UNILEVEL_WIDTH = 5;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 ThaipromptMlmSeeder - สร้างระบบ MLM ใหม่');
        $this->command->info('');

        // 1. ลบข้อมูล demo เก่าทั้งหมด
        $this->cleanupAllDemoData();

        // 2. ดึง/สร้าง MLM Plan และ Package
        $plan = $this->getOrCreateDefaultPlan();
        $package = $this->getOrCreateDefaultPackage();

        // 3. ดึง Admin User (Root Leader)
        $admin = $this->getAdminUser();
        $adminMember = $this->getOrCreateAdminMember($admin, $plan, $package);

        // 4. สร้าง Thaiprompt 00-30 (31 คน)
        $members = $this->createThaipromptMembers($admin, $adminMember, $plan, $package);

        // 5. อัพเดทสถิติ
        $this->updateStatistics($adminMember, $members);

        // 6. แสดงผลลัพธ์
        $this->showResults($members);
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
            $this->command->info("✅ MLM Plan: {$plan->name}");
            return $plan;
        }

        $plan = MlmPlan::first();
        if ($plan) {
            $this->command->info("✅ MLM Plan: {$plan->name}");
            return $plan;
        }

        $plan = MlmPlan::create([
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

        $this->command->info("✅ สร้าง MLM Plan: {$plan->name}");
        return $plan;
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
            $this->command->info("✅ MLM Package: {$package->name}");
            return $package;
        }

        $package = MlmPackage::first();
        if ($package) {
            $this->command->info("✅ MLM Package: {$package->name}");
            return $package;
        }

        $package = MlmPackage::create([
            'name' => 'Starter Package',
            'name_th' => 'แพ็คเกจเริ่มต้น',
            'slug' => 'starter-package',
            'description' => 'Basic starter package',
            'price' => 0,
            'pv_value' => 100,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->command->info("✅ สร้าง MLM Package: {$package->name}");
        return $package;
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
            $this->command->error('❌ ไม่พบ Super Admin กรุณารัน DemoUsersSeeder ก่อน');
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
            $this->command->info("✅ Admin MLM Member: {$member->member_code}");
            return $member;
        }

        $member = MlmMember::create([
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

        $this->command->info("✅ สร้าง Admin MLM Member: {$member->member_code}");
        return $member;
    }

    /**
     * สร้าง Thaiprompt Members (00-30)
     *
     * @param User $admin
     * @param MlmMember $adminMember
     * @param MlmPlan $plan
     * @param MlmPackage $package
     * @return array
     */
    private function createThaipromptMembers(User $admin, MlmMember $adminMember, MlmPlan $plan, MlmPackage $package): array
    {
        $this->command->info('');
        $this->command->info('👥 กำลังสร้าง Thaiprompt 00-30 (31 คน)...');

        $members = [];

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

            // คำนวณตำแหน่งใน Binary Tree (BFS)
            $binaryInfo = $this->calculateBinaryPosition($i, $members);

            // คำนวณตำแหน่งใน Unilevel Tree (BFS, width = 5)
            $unilevelInfo = $this->calculateUnilevelPosition($i, $members, $adminMember);

            // สร้าง MLM Member
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

                // Binary Tree
                'binary_sponsor_id' => $binaryInfo['parent_id'],
                'binary_parent_id' => $binaryInfo['parent_id'],
                'binary_position' => $binaryInfo['position'],
                'binary_path' => $binaryInfo['path'],

                // Unilevel Tree
                'unilevel_sponsor_id' => $unilevelInfo['sponsor_id'],
                'unilevel_level' => $unilevelInfo['level'],
                'unilevel_path' => $unilevelInfo['path'],

                // Stats (จะอัพเดทภายหลัง)
                'total_pv' => $package->pv_value ?? 100,
                'total_direct_referrals' => 0,
                'total_team_members' => 0,
                'left_leg_members' => 0,
                'right_leg_members' => 0,
                'left_leg_pv' => 0,
                'right_leg_pv' => 0,
            ]);

            $members[$i] = $member;

            // แสดง progress ทุก 10 คน
            if (($i + 1) % 10 === 0 || $i === 0 || $i === self::MEMBER_COUNT - 1) {
                $this->command->info("   สร้าง {$memberCode}: Thaiprompt {$memberNum}");
            }
        }

        return $members;
    }

    /**
     * คำนวณตำแหน่งใน Binary Tree (Full Binary Tree, BFS)
     *
     * Index Formula:
     * - Parent of index N = floor((N - 1) / 2)
     * - Left child of N = 2N + 1
     * - Right child of N = 2N + 2
     * - Position: odd index = left, even index = right
     *
     * @param int $index ตำแหน่ง (0-30)
     * @param array $members สมาชิกที่สร้างแล้ว
     * @return array ['parent_id', 'position', 'path']
     */
    private function calculateBinaryPosition(int $index, array $members): array
    {
        // Index 0 = Root (ไม่มี parent ใน binary)
        if ($index === 0) {
            return [
                'parent_id' => null,
                'position' => null,
                'path' => null,
            ];
        }

        // คำนวณ parent index
        $parentIndex = (int) floor(($index - 1) / 2);
        $parent = $members[$parentIndex] ?? null;

        // คำนวณตำแหน่ง (odd = left, even = right)
        $position = ($index % 2 === 1) ? 'left' : 'right';

        // คำนวณ path
        $path = $this->buildBinaryPath($index, $members);

        return [
            'parent_id' => $parent?->id,
            'position' => $position,
            'path' => $path,
        ];
    }

    /**
     * สร้าง binary path
     *
     * @param int $index
     * @param array $members
     * @return string|null
     */
    private function buildBinaryPath(int $index, array $members): ?string
    {
        if ($index === 0) {
            return null;
        }

        $path = [];
        $currentIndex = $index;

        while ($currentIndex > 0) {
            $parentIndex = (int) floor(($currentIndex - 1) / 2);
            if (isset($members[$parentIndex])) {
                array_unshift($path, $members[$parentIndex]->id);
            }
            $currentIndex = $parentIndex;
        }

        return count($path) > 0 ? implode('/', $path) : null;
    }

    /**
     * คำนวณตำแหน่งใน Unilevel Tree (BFS, width = 5)
     *
     * โครงสร้าง:
     * - Level 1: TP-00 ถึง TP-04 (5 คน ภายใต้ Admin)
     * - Level 2: TP-05 ถึง TP-29 (25 คน, 5 คนต่อ parent ใน Level 1)
     * - Level 3: TP-30 (1 คน ภายใต้ TP-05)
     *
     * @param int $index ตำแหน่ง (0-30)
     * @param array $members สมาชิกที่สร้างแล้ว
     * @param MlmMember $adminMember
     * @return array ['sponsor_id', 'level', 'path']
     */
    private function calculateUnilevelPosition(int $index, array $members, MlmMember $adminMember): array
    {
        $width = self::UNILEVEL_WIDTH;

        // Level 1: index 0-4 (5 คนแรก ภายใต้ Admin)
        if ($index < $width) {
            return [
                'sponsor_id' => $adminMember->id,
                'level' => 1,
                'path' => (string) $adminMember->id,
            ];
        }

        // Level 2+: คำนวณ parent จาก index
        // Parent index = floor((index - width) / width)
        $parentIndex = (int) floor(($index - $width) / $width);
        $parent = $members[$parentIndex] ?? null;

        if (!$parent) {
            // Fallback ถ้าไม่มี parent
            return [
                'sponsor_id' => $adminMember->id,
                'level' => 1,
                'path' => (string) $adminMember->id,
            ];
        }

        // คำนวณ level
        $level = $parent->unilevel_level + 1;

        // คำนวณ path
        $path = $parent->unilevel_path
            ? $parent->unilevel_path . '/' . $parent->id
            : (string) $parent->id;

        return [
            'sponsor_id' => $parent->id,
            'level' => $level,
            'path' => $path,
        ];
    }

    /**
     * อัพเดทสถิติของทุก members
     *
     * @param MlmMember $adminMember
     * @param array $members
     * @return void
     */
    private function updateStatistics(MlmMember $adminMember, array $members): void
    {
        $this->command->info('');
        $this->command->info('📊 กำลังอัพเดทสถิติ...');

        // อัพเดท Admin Member
        $directReferrals = count($members); // ทุกคนเป็น direct referral ของ Admin

        // นับ unilevel children ของ Admin (Level 1)
        $unilevelChildren = MlmMember::where('unilevel_sponsor_id', $adminMember->id)->count();

        $adminMember->update([
            'total_direct_referrals' => $directReferrals,
            'total_team_members' => count($members),
        ]);

        $this->command->info("   Admin: {$directReferrals} direct referrals, {$unilevelChildren} unilevel children");

        // อัพเดทสถิติของแต่ละ member
        foreach ($members as $member) {
            // นับ unilevel children
            $unilevelChildren = MlmMember::where('unilevel_sponsor_id', $member->id)->count();

            // นับ binary children
            $leftChild = MlmMember::where('binary_parent_id', $member->id)
                ->where('binary_position', 'left')
                ->exists();
            $rightChild = MlmMember::where('binary_parent_id', $member->id)
                ->where('binary_position', 'right')
                ->exists();

            // นับ left/right leg members
            $leftLegMembers = $this->countBinarySubtree($member->id, 'left');
            $rightLegMembers = $this->countBinarySubtree($member->id, 'right');

            // นับ total team (unilevel downline)
            $teamCount = $this->countUnilevelDownline($member->id);

            $member->update([
                'total_direct_referrals' => 0, // ไม่มีใครแนะนำตรง (ทุกคนถูกแนะนำโดย Admin)
                'total_team_members' => $teamCount,
                'left_leg_members' => $leftLegMembers,
                'right_leg_members' => $rightLegMembers,
            ]);
        }

        $this->command->info('   ✅ อัพเดทสถิติเสร็จ');
    }

    /**
     * นับสมาชิกใน Binary subtree
     *
     * @param int $memberId
     * @param string $position 'left' หรือ 'right'
     * @return int
     */
    private function countBinarySubtree(int $memberId, string $position): int
    {
        $child = MlmMember::where('binary_parent_id', $memberId)
            ->where('binary_position', $position)
            ->first();

        if (!$child) {
            return 0;
        }

        return 1 + $this->countAllBinaryChildren($child->id);
    }

    /**
     * นับ binary children ทั้งหมด (recursive)
     *
     * @param int $memberId
     * @return int
     */
    private function countAllBinaryChildren(int $memberId): int
    {
        $count = 0;
        $children = MlmMember::where('binary_parent_id', $memberId)->get();

        foreach ($children as $child) {
            $count++;
            $count += $this->countAllBinaryChildren($child->id);
        }

        return $count;
    }

    /**
     * นับ unilevel downline ทั้งหมด (recursive)
     *
     * @param int $memberId
     * @return int
     */
    private function countUnilevelDownline(int $memberId): int
    {
        $count = 0;
        $children = MlmMember::where('unilevel_sponsor_id', $memberId)->get();

        foreach ($children as $child) {
            $count++;
            $count += $this->countUnilevelDownline($child->id);
        }

        return $count;
    }

    /**
     * แสดงผลลัพธ์
     *
     * @param array $members
     * @return void
     */
    private function showResults(array $members): void
    {
        $this->command->info('');
        $this->command->info('✨ สร้าง Thaiprompt MLM สำเร็จ!');
        $this->command->info('');
        $this->command->info('📊 สรุป:');
        $this->command->info("   • จำนวนสมาชิก: " . count($members) . " คน (Thaiprompt 00-30)");
        $this->command->info('');
        $this->command->info('🌳 โครงสร้าง Binary Tree (5 ชั้น):');
        $this->command->info('   Level 0: TP-00 (Root)');
        $this->command->info('   Level 1: TP-01, TP-02');
        $this->command->info('   Level 2: TP-03 - TP-06 (4 คน)');
        $this->command->info('   Level 3: TP-07 - TP-14 (8 คน)');
        $this->command->info('   Level 4: TP-15 - TP-30 (16 คน)');
        $this->command->info('');
        $this->command->info('👥 โครงสร้าง Unilevel Tree (width=5):');
        $this->command->info('   Level 1: TP-00 - TP-04 (5 คน ภายใต้ Admin)');
        $this->command->info('   Level 2: TP-05 - TP-29 (25 คน)');
        $this->command->info('   Level 3: TP-30 (1 คน)');
        $this->command->info('');
        $this->command->info('🔗 Original Sponsor:');
        $this->command->info('   ทุกคน (31 คน) แนะนำตรงโดย Admin (ADMIN-0001)');
        $this->command->info('');
        $this->command->info('🔐 ข้อมูลเข้าสู่ระบบ:');
        $this->command->info('   Email: thaipromptXX@thaiprompt.com (XX = 00-30)');
        $this->command->info('   Password: password123');
        $this->command->info('');
    }
}
