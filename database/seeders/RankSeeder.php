<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\RankBonus;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        // Define ranks with beautiful Thai names and icons
        $ranks = [
            [
                'name' => 'Bronze',
                'name_th' => 'สำริด',
                'description' => 'Starting rank for new affiliates',
                'description_th' => 'ระดับเริ่มต้นสำหรับพันธมิตรใหม่',
                'level' => 1,
                'icon' => '/images/ranks/bronze.svg',
                'color' => '#CD7F32',
                'badge_icon' => '🥉',
                'stars' => 1,
                'commission_rate' => 5.00,
                'bonus_multiplier' => 1.00,
                'min_points' => 0,
                'min_referrals' => 0,
                'min_sales' => 0,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn Points', 'name_th' => 'รับคะแนน', 'target' => 0, 'operator' => '>='],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Welcome Bonus', 'name_th' => 'โบนัสต้อนรับ', 'amount' => 100],
                ],
            ],
            [
                'name' => 'Silver',
                'name_th' => 'เงิน',
                'description' => 'Active affiliate with growing network',
                'description_th' => 'พันธมิตรที่กำลังเติบโต',
                'level' => 2,
                'icon' => '/images/ranks/silver.svg',
                'color' => '#C0C0C0',
                'badge_icon' => '🥈',
                'stars' => 2,
                'commission_rate' => 7.50,
                'bonus_multiplier' => 1.25,
                'min_points' => 100,
                'min_referrals' => 5,
                'min_sales' => 10000,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 100 Points', 'name_th' => 'รับคะแนน 100 แต้ม', 'target' => 100, 'operator' => '>='],
                    ['type' => 'referrals', 'name' => '5 Referrals', 'name_th' => 'แนะนำ 5 คน', 'target' => 5, 'operator' => '>='],
                    ['type' => 'sales', 'name' => '฿10,000 Sales', 'name_th' => 'ยอดขาย 10,000 บาท', 'target' => 10000, 'operator' => '>='],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Silver Bonus', 'name_th' => 'โบนัสระดับเงิน', 'amount' => 500],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 2.50],
                ],
            ],
            [
                'name' => 'Gold',
                'name_th' => 'ทอง',
                'description' => 'Successful affiliate with strong performance',
                'description_th' => 'พันธมิตรที่ประสบความสำเร็จ',
                'level' => 3,
                'icon' => '/images/ranks/gold.svg',
                'color' => '#FFD700',
                'badge_icon' => '🥇',
                'stars' => 3,
                'commission_rate' => 10.00,
                'bonus_multiplier' => 1.50,
                'min_points' => 500,
                'min_referrals' => 20,
                'min_sales' => 50000,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 500 Points', 'name_th' => 'รับคะแนน 500 แต้ม', 'target' => 500, 'operator' => '>='],
                    ['type' => 'referrals', 'name' => '20 Referrals', 'name_th' => 'แนะนำ 20 คน', 'target' => 20, 'operator' => '>='],
                    ['type' => 'sales', 'name' => '฿50,000 Sales', 'name_th' => 'ยอดขาย 50,000 บาท', 'target' => 50000, 'operator' => '>='],
                    ['type' => 'active_referrals', 'name' => '10 Active Members', 'name_th' => 'สมาชิกที่ Active 10 คน', 'target' => 10, 'operator' => '>='],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Gold Bonus', 'name_th' => 'โบนัสระดับทอง', 'amount' => 2000],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 500],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 5.00],
                ],
            ],
            [
                'name' => 'Platinum',
                'name_th' => 'แพลตินัม',
                'description' => 'Elite affiliate with exceptional results',
                'description_th' => 'พันธมิตรระดับยอดเยี่ยม',
                'level' => 4,
                'icon' => '/images/ranks/platinum.svg',
                'color' => '#E5E4E2',
                'badge_icon' => '💎',
                'stars' => 4,
                'commission_rate' => 15.00,
                'bonus_multiplier' => 2.00,
                'min_points' => 2000,
                'min_referrals' => 50,
                'min_sales' => 200000,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 2000 Points', 'name_th' => 'รับคะแนน 2000 แต้ม', 'target' => 2000, 'operator' => '>='],
                    ['type' => 'referrals', 'name' => '50 Referrals', 'name_th' => 'แนะนำ 50 คน', 'target' => 50, 'operator' => '>='],
                    ['type' => 'sales', 'name' => '฿200,000 Sales', 'name_th' => 'ยอดขาย 200,000 บาท', 'target' => 200000, 'operator' => '>='],
                    ['type' => 'active_referrals', 'name' => '25 Active Members', 'name_th' => 'สมาชิกที่ Active 25 คน', 'target' => 25, 'operator' => '>='],
                    ['type' => 'team_sales', 'name' => '฿500,000 Team Sales', 'name_th' => 'ยอดขายทีม 500,000 บาท', 'target' => 500000, 'operator' => '>='],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Platinum Bonus', 'name_th' => 'โบนัสระดับแพลตินัม', 'amount' => 10000],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 2000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 10.00],
                ],
            ],
            [
                'name' => 'Diamond',
                'name_th' => 'เพชร',
                'description' => 'Top-tier affiliate with outstanding achievements',
                'description_th' => 'พันธมิตรระดับสูงสุด',
                'level' => 5,
                'icon' => '/images/ranks/diamond.svg',
                'color' => '#B9F2FF',
                'badge_icon' => '💠',
                'stars' => 5,
                'commission_rate' => 20.00,
                'bonus_multiplier' => 3.00,
                'min_points' => 10000,
                'min_referrals' => 100,
                'min_sales' => 1000000,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 5,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 10000 Points', 'name_th' => 'รับคะแนน 10000 แต้ม', 'target' => 10000, 'operator' => '>='],
                    ['type' => 'referrals', 'name' => '100 Referrals', 'name_th' => 'แนะนำ 100 คน', 'target' => 100, 'operator' => '>='],
                    ['type' => 'sales', 'name' => '฿1,000,000 Sales', 'name_th' => 'ยอดขาย 1,000,000 บาท', 'target' => 1000000, 'operator' => '>='],
                    ['type' => 'active_referrals', 'name' => '50 Active Members', 'name_th' => 'สมาชิกที่ Active 50 คน', 'target' => 50, 'operator' => '>='],
                    ['type' => 'team_sales', 'name' => '฿2,000,000 Team Sales', 'name_th' => 'ยอดขายทีม 2,000,000 บาท', 'target' => 2000000, 'operator' => '>='],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Diamond Bonus', 'name_th' => 'โบนัสระดับเพชร', 'amount' => 50000],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 5000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 15.00],
                    ['type' => 'privilege', 'name' => 'VIP Support', 'name_th' => 'บริการ VIP พิเศษ', 'reward_type' => 'vip_support'],
                ],
            ],
        ];

        foreach ($ranks as $rankData) {
            $requirements = $rankData['requirements'];
            $bonuses = $rankData['bonuses'];
            unset($rankData['requirements'], $rankData['bonuses']);

            // Use updateOrCreate to prevent duplicate entry errors on re-run
            $rank = Rank::updateOrCreate(
                ['level' => $rankData['level']], // Match by level (unique key)
                $rankData
            );

            // Delete existing requirements and bonuses before recreating
            // This ensures data is always fresh when seeder is re-run
            RankRequirement::where('rank_id', $rank->id)->delete();
            RankBonus::where('rank_id', $rank->id)->delete();

            // Create requirements
            foreach ($requirements as $req) {
                RankRequirement::create([
                    'rank_id' => $rank->id,
                    'requirement_type' => $req['type'],
                    'name' => $req['name'],
                    'name_th' => $req['name_th'],
                    'target_value' => $req['target'],
                    'operator' => $req['operator'],
                    'is_required' => true,
                    'is_active' => true,
                ]);
            }

            // Create bonuses
            foreach ($bonuses as $bonus) {
                RankBonus::create([
                    'rank_id' => $rank->id,
                    'bonus_type' => $bonus['type'],
                    'name' => $bonus['name'],
                    'name_th' => $bonus['name_th'],
                    'amount' => $bonus['amount'] ?? null,
                    'percentage' => $bonus['percentage'] ?? null,
                    'reward_type' => $bonus['reward_type'] ?? null,
                    'is_active' => true,
                    'auto_apply' => true,
                ]);
            }
        }

        $this->command->info('✅ Ranks, Requirements, and Bonuses seeded successfully!');
    }
}
