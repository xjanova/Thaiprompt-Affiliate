<?php

namespace Database\Seeders;

use App\Models\MlmPlan;
use Illuminate\Database\Seeder;

class MlmPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create SINGLE default MLM Commission Plan (used by ALL members regardless of package)
        MlmPlan::updateOrCreate(
            ['slug' => 'default-mlm-plan'],
            [
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
            ]
        );

        // Mark all other plans as inactive (if any exist)
        MlmPlan::where('slug', '!=', 'default-mlm-plan')->update([
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->command->info('✓ Created default MLM Plan (single plan for all members)');
    }
}
