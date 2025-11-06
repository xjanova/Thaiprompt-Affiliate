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
        // Create default Hybrid plan
        MlmPlan::updateOrCreate(
            ['slug' => 'premium-hybrid-plan'],
            [
            'name' => 'Premium Hybrid Plan',
            'name_th' => 'แผนไฮบริดพรีเมี่ยม',
            'description' => 'Complete MLM plan with both Unilevel and Binary commission structures. Perfect for maximum earnings potential.',
            'description_th' => 'แผน MLM ที่สมบูรณ์แบบ รองรับทั้งระบบ Unilevel และ Binary เหมาะสำหรับรายได้สูงสุด',
            'slug' => 'premium-hybrid-plan',
            'type' => 'hybrid',
            'is_active' => true,
            'is_default' => true,
            'color' => '#4F46E5',
            'icon' => 'star',
            'sort_order' => 1,

            // Joining Fee
            'joining_fee' => 0.00,
            'requires_joining_fee' => false,

            // PV System
            'use_pv_system' => true,
            'global_pv_rate' => 1.00, // 1 THB = 1 PV
            'global_commission_per_pv' => 0.10, // 10% commission per PV

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

        // Create Unilevel-only plan
        MlmPlan::updateOrCreate(
            ['slug' => 'unilevel-basic-plan'],
            [
            'name' => 'Unilevel Basic Plan',
            'name_th' => 'แผน Unilevel พื้นฐาน',
            'description' => 'Simple and straightforward Unilevel commission structure.',
            'description_th' => 'โครงสร้างคอมมิชชันแบบ Unilevel ที่เรียบง่ายและตรงไปตรงมา',
            'slug' => 'unilevel-basic-plan',
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => false,
            'color' => '#10B981',
            'icon' => 'users',
            'sort_order' => 2,

            // Joining Fee
            'joining_fee' => 0.00,
            'requires_joining_fee' => false,

            // PV System
            'use_pv_system' => true,
            'global_pv_rate' => 1.00,
            'global_commission_per_pv' => 0.08, // 8% commission per PV

            // Unilevel Settings (5 levels)
            'unilevel_levels' => [
                ['level' => 1, 'percentage' => 15],
                ['level' => 2, 'percentage' => 10],
                ['level' => 3, 'percentage' => 5],
                ['level' => 4, 'percentage' => 3],
                ['level' => 5, 'percentage' => 2],
            ],
            'unilevel_max_depth' => 5,
            'unilevel_compression' => true,

            // Binary Settings (not used)
            'binary_pair_commission' => 0.00,
            'binary_match_percentage' => 0.00,
            'binary_spillover' => false,
            'binary_pairing_type' => '1:1',
            'auto_placement' => false,
        ]);

        // Create Binary-only plan
        MlmPlan::updateOrCreate(
            ['slug' => 'binary-power-plan'],
            [
            'name' => 'Binary Power Plan',
            'name_th' => 'แผน Binary ทรงพลัง',
            'description' => 'Powerful Binary plan with spillover and team building.',
            'description_th' => 'แผน Binary ที่ทรงพลัง พร้อมระบบ Spillover และการสร้างทีม',
            'slug' => 'binary-power-plan',
            'type' => 'binary',
            'is_active' => true,
            'is_default' => false,
            'color' => '#F59E0B',
            'icon' => 'trending-up',
            'sort_order' => 3,

            // Joining Fee
            'joining_fee' => 1000.00,
            'requires_joining_fee' => true,

            // PV System
            'use_pv_system' => true,
            'global_pv_rate' => 1.00,
            'global_commission_per_pv' => 0.10,

            // Unilevel Settings (not used)
            'unilevel_levels' => [],
            'unilevel_max_depth' => 0,
            'unilevel_compression' => false,

            // Binary Settings
            'binary_pair_commission' => 150.00, // 150 THB per pair
            'binary_match_percentage' => 15.00, // 15% of weaker leg
            'binary_max_pairs_per_day' => 50, // Max 50 pairs per day
            'binary_max_commission_per_day' => 10000.00, // Max 10,000 THB per day
            'binary_flush_percentage' => 80.00, // Flush 80%, carry 20%
            'binary_spillover' => true,
            'binary_pairing_type' => '1:1',

            // Auto-placement
            'auto_placement' => true,
            'auto_placement_type' => 'weak_leg',

            // Rank requirements
            'requires_rank' => false,
        ]);
    }
}
