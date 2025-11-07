<?php

namespace Database\Seeders;

use App\Models\MlmPlan;
use Illuminate\Database\Seeder;

class MlmPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สำคัญ: MlmPlan คือ "แผนคอมมิชชัน" ไม่ใช่ "แพคเกจสมาชิก"
     * - MlmPlan = แผนการคำนวณค่าคอมมิชชัน (มีเพียง 1 แผนหลักที่ใช้ร่วมกัน)
     * - MlmPackage = แพคเกจสำหรับสมาชิก (Bronze, Silver, Gold, etc.)
     *   ดู MlmPackageSeeder.php
     */
    public function run(): void
    {
        // ลบข้อมูลเก่าทั้งหมดเพื่อป้องกันความซ้ำซ้อน
        $this->command->info('🗑️  Cleaning old MLM Plans...');
        MlmPlan::query()->delete();

        // สร้าง 1 แผนคอมมิชชันหลักเท่านั้น (ใช้ร่วมกันโดยสมาชิกทุกคน)
        $this->command->info('📝 Creating default MLM Commission Plan...');

        MlmPlan::create([
            'name' => 'Default MLM Commission Plan',
            'name_th' => 'แผนคอมมิชชัน MLM หลัก',
            'description' => 'Main commission plan used for all members. Hybrid system with both Unilevel and Binary structures for maximum earning potential.',
            'description_th' => 'แผนคอมมิชชันหลักที่ใช้สำหรับสมาชิกทุกคน ระบบไฮบริดที่รวม Unilevel และ Binary เพื่อศักยภาพรายได้สูงสุด',
            'slug' => 'default-mlm-plan',
            'type' => 'hybrid',
            'is_active' => true,
            'is_default' => true, // แผนหลักของระบบ
            'color' => '#4F46E5',
            'icon' => 'chart-network',
            'sort_order' => 1,

            // No joining fee (fees are in mlm_packages table)
            'joining_fee' => 0.00,
            'requires_joining_fee' => false,

            // PV System
            'use_pv_system' => true,
            'global_pv_rate' => 1.00, // 1 THB = 1 PV
            'global_commission_per_pv' => 0.10, // Base 10% commission per PV

            // Unilevel Settings (10 levels)
            'unilevel_levels' => [
                ['level' => 1, 'percentage' => 10],
                ['level' => 2, 'percentage' => 8],
                ['level' => 3, 'percentage' => 6],
                ['level' => 4, 'percentage' => 5],
                ['level' => 5, 'percentage' => 4],
                ['level' => 6, 'percentage' => 3],
                ['level' => 7, 'percentage' => 2],
                ['level' => 8, 'percentage' => 1],
                ['level' => 9, 'percentage' => 1],
                ['level' => 10, 'percentage' => 1],
            ],
            'unilevel_max_depth' => 10,
            'unilevel_compression' => false,

            // Binary Settings
            'binary_pair_commission' => 100.00, // 100 THB per pair
            'binary_match_percentage' => 10.00, // 10% of weaker leg
            'binary_max_pairs_per_day' => null, // Unlimited
            'binary_max_commission_per_day' => null, // Unlimited
            'binary_flush_percentage' => 100.00, // Flush 100% of matched PV
            'binary_spillover' => true,
            'binary_pairing_type' => '1:1',

            // Auto-placement
            'auto_placement' => true,
            'auto_placement_type' => 'balanced',

            // Rank requirements
            'requires_rank' => false,
            'rank_requirements' => null,
        ]);

        $this->command->info('✅ Created 1 default MLM Commission Plan (ไม่ใช่แพคเกจ!)');
        $this->command->info('ℹ️  หากต้องการดูแพคเกจสมาชิก ให้รัน: php artisan db:seed --class=MlmPackageSeeder');
    }
}
