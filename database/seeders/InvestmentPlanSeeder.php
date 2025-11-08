<?php

namespace Database\Seeders;

use App\Models\InvestmentPlan;
use Illuminate\Database\Seeder;

class InvestmentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Bronze Plan',
                'name_th' => 'แผนบรอนซ์',
                'description' => 'Entry-level investment plan with stable returns',
                'description_th' => 'แผนการลงทุนระดับเริ่มต้นที่ให้ผลตอบแทนที่มั่นคง',
                'min_amount' => 1000,
                'max_amount' => 9999,
                'roi_rate' => 0.5, // 0.5% daily
                'roi_frequency' => 'daily',
                'term_days' => 30,
                'lock_days' => 7,
                'allow_compound' => true,
                'allow_early_withdrawal' => true,
                'early_withdrawal_penalty' => 5.0,
                'referral_bonus_rate' => 2.0,
                'is_active' => true,
                'is_featured' => false,
                'max_investors' => null,
                'current_investors' => 0,
                'sort_order' => 1,
                'required_rank_id' => null,
                'requires_kyc' => true,
                'features' => [
                    'Daily ROI distribution',
                    'Auto-compound available',
                    'Early withdrawal with 5% penalty',
                    '2% referral bonus',
                ],
                'color' => '#CD7F32',
                'icon' => 'bronze-medal',
            ],
            [
                'name' => 'Silver Plan',
                'name_th' => 'แผนซิลเวอร์',
                'description' => 'Mid-tier investment plan with enhanced returns',
                'description_th' => 'แผนการลงทุนระดับกลางที่ให้ผลตอบแทนที่ดีขึ้น',
                'min_amount' => 10000,
                'max_amount' => 49999,
                'roi_rate' => 1.0, // 1% daily
                'roi_frequency' => 'daily',
                'term_days' => 60,
                'lock_days' => 14,
                'allow_compound' => true,
                'allow_early_withdrawal' => true,
                'early_withdrawal_penalty' => 7.0,
                'referral_bonus_rate' => 3.0,
                'is_active' => true,
                'is_featured' => true,
                'max_investors' => null,
                'current_investors' => 0,
                'sort_order' => 2,
                'required_rank_id' => null,
                'requires_kyc' => true,
                'features' => [
                    'Daily ROI distribution',
                    'Higher ROI rate',
                    'Auto-compound available',
                    'Early withdrawal with 7% penalty',
                    '3% referral bonus',
                ],
                'color' => '#C0C0C0',
                'icon' => 'silver-medal',
            ],
            [
                'name' => 'Gold Plan',
                'name_th' => 'แผนโกลด์',
                'description' => 'Premium investment plan with superior returns',
                'description_th' => 'แผนการลงทุนระดับพรีเมียมที่ให้ผลตอบแทนที่เหนือกว่า',
                'min_amount' => 50000,
                'max_amount' => 199999,
                'roi_rate' => 1.5, // 1.5% daily
                'roi_frequency' => 'daily',
                'term_days' => 90,
                'lock_days' => 21,
                'allow_compound' => true,
                'allow_early_withdrawal' => true,
                'early_withdrawal_penalty' => 10.0,
                'referral_bonus_rate' => 5.0,
                'is_active' => true,
                'is_featured' => true,
                'max_investors' => null,
                'current_investors' => 0,
                'sort_order' => 3,
                'required_rank_id' => null,
                'requires_kyc' => true,
                'features' => [
                    'Daily ROI distribution',
                    'Premium ROI rate',
                    'Auto-compound available',
                    'Priority support',
                    'Early withdrawal with 10% penalty',
                    '5% referral bonus',
                ],
                'color' => '#FFD700',
                'icon' => 'gold-medal',
            ],
            [
                'name' => 'Platinum Plan',
                'name_th' => 'แผนแพลทินัม',
                'description' => 'Elite investment plan with maximum returns',
                'description_th' => 'แผนการลงทุนระดับอีลีทที่ให้ผลตอบแทนสูงสุด',
                'min_amount' => 200000,
                'max_amount' => null,
                'roi_rate' => 2.0, // 2% daily
                'roi_frequency' => 'daily',
                'term_days' => 180,
                'lock_days' => 30,
                'allow_compound' => true,
                'allow_early_withdrawal' => false,
                'early_withdrawal_penalty' => 0,
                'referral_bonus_rate' => 10.0,
                'is_active' => true,
                'is_featured' => true,
                'max_investors' => 100,
                'current_investors' => 0,
                'sort_order' => 4,
                'required_rank_id' => null,
                'requires_kyc' => true,
                'features' => [
                    'Daily ROI distribution',
                    'Maximum ROI rate',
                    'Auto-compound available',
                    'VIP support',
                    'Exclusive benefits',
                    'No early withdrawal',
                    '10% referral bonus',
                    'Limited slots (100 investors)',
                ],
                'color' => '#E5E4E2',
                'icon' => 'platinum-medal',
            ],
            [
                'name' => 'Weekly Compound',
                'name_th' => 'แผนรีลงทุนรายสัปดาห์',
                'description' => 'Weekly compounding plan for steady growth',
                'description_th' => 'แผนรีลงทุนรายสัปดาห์เพื่อการเติบโตที่มั่นคง',
                'min_amount' => 5000,
                'max_amount' => 99999,
                'roi_rate' => 5.0, // 5% weekly
                'roi_frequency' => 'weekly',
                'term_days' => 84, // 12 weeks
                'lock_days' => 7,
                'allow_compound' => true,
                'allow_early_withdrawal' => true,
                'early_withdrawal_penalty' => 8.0,
                'referral_bonus_rate' => 3.0,
                'is_active' => true,
                'is_featured' => false,
                'max_investors' => null,
                'current_investors' => 0,
                'sort_order' => 5,
                'required_rank_id' => null,
                'requires_kyc' => true,
                'features' => [
                    'Weekly ROI distribution',
                    'Auto-compound available',
                    '12-week investment term',
                    'Early withdrawal with 8% penalty',
                    '3% referral bonus',
                ],
                'color' => '#4169E1',
                'icon' => 'calendar-week',
            ],
        ];

        foreach ($plans as $plan) {
            InvestmentPlan::create($plan);
        }

        $this->command->info('Investment plans seeded successfully!');
    }
}
