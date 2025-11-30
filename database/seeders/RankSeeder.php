<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\RankBonus;
use App\Models\RankRequirement;
use Database\Seeders\Concerns\SafeSeeder;
use Illuminate\Database\Seeder;

/**
 * RankSeeder - ระบบยศ/ระดับสมาชิก 8 ระดับ
 *
 * โครงสร้างระดับ:
 * 1. Bronze (สำริด) - ระดับเริ่มต้นสำหรับสมาชิกใหม่
 * 2. Silver (เงิน) - เริ่มสร้างทีม
 * 3. Gold (ทอง) - ผู้นำทีมระดับต้น
 * 4. Platinum (แพลตินัม) - ผู้นำทีมระดับกลาง
 * 5. Diamond (เพชร) - ผู้นำทีมระดับสูง
 * 6. Crown (มงกุฎ) - ผู้นำระดับเอลิท
 * 7. Royal (รอยัล) - ผู้นำระดับพรีเมียม
 * 8. Legend (ตำนาน) - ระดับสูงสุด พร้อมโบนัสพิเศษ 1 ล้านบาท
 *
 * @package Database\Seeders
 */
class RankSeeder extends Seeder
{
    use SafeSeeder;

    /**
     * รันการ seed ข้อมูลระดับ
     *
     * @return void
     */
    public function run(): void
    {
        // ตรวจสอบว่าตาราง ranks มีอยู่หรือไม่
        if (!$this->requireTable('ranks', 'RankSeeder')) {
            return;
        }

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        $existingRanksCount = Rank::whereIn('level', [1, 2, 3])->count();

        if ($existingRanksCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * โหมด Fresh Install - สร้างข้อมูลทั้งหมดใหม่
     *
     * @return void
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: กำลัง seed ระดับทั้งหมดพร้อม requirements และ bonuses...');

        $ranks = $this->getAllRanks();

        foreach ($ranks as $rankData) {
            $this->createRankWithRelations($rankData);
        }

        $this->command->info('✅ สร้างระดับ Requirements และ Bonuses สำเร็จ!');
        $this->command->info('');
        $this->command->info('📊 สรุประดับที่สร้าง:');
        $this->command->info('   1. Bronze (สำริด) - ระดับเริ่มต้น');
        $this->command->info('   2. Silver (เงิน) - Commission 7.5%');
        $this->command->info('   3. Gold (ทอง) - Commission 10%');
        $this->command->info('   4. Platinum (แพลตินัม) - Commission 15%');
        $this->command->info('   5. Diamond (เพชร) - Commission 20%');
        $this->command->info('   6. Crown (มงกุฎ) - Commission 25%');
        $this->command->info('   7. Royal (รอยัล) - Commission 30%');
        $this->command->info('   8. Legend (ตำนาน) - Commission 35% + โบนัส 1,000,000 บาท');
        $this->command->info('');
    }

    /**
     * โหมด Update - เพิ่มเฉพาะระดับที่ยังไม่มี
     *
     * @return void
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  พบข้อมูลระดับที่มีอยู่แล้ว!');
        $this->command->info('   กำลังรันในโหมด UPDATE (เพิ่มเฉพาะระดับที่ยังไม่มี)...');

        $ranks = $this->getAllRanks();
        $added = 0;
        $skipped = 0;

        foreach ($ranks as $rankData) {
            if (!Rank::where('level', $rankData['level'])->exists()) {
                $this->createRankWithRelations($rankData);
                $this->command->info("   ➕ เพิ่ม: {$rankData['name_th']} (Level {$rankData['level']})");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ เพิ่ม {$added} ระดับใหม่");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  ข้าม {$skipped} ระดับที่มีอยู่แล้ว");
        }
    }

    /**
     * สร้างระดับพร้อม requirements และ bonuses
     *
     * @param array $rankData ข้อมูลระดับ
     * @return void
     */
    private function createRankWithRelations(array $rankData): void
    {
        $requirements = $rankData['requirements'];
        $bonuses = $rankData['bonuses'];
        unset($rankData['requirements'], $rankData['bonuses']);

        $rank = Rank::create($rankData);

        // สร้าง requirements
        foreach ($requirements as $req) {
            RankRequirement::create([
                'rank_id' => $rank->id,
                'requirement_type' => $req['type'],
                'name' => $req['name'],
                'name_th' => $req['name_th'],
                'description' => $req['description'] ?? null,
                'description_th' => $req['description_th'] ?? null,
                'target_value' => $req['target'],
                'operator' => $req['operator'] ?? '>=',
                'unit' => $req['unit'] ?? null,
                'is_required' => $req['is_required'] ?? true,
                'is_active' => true,
            ]);
        }

        // สร้าง bonuses
        foreach ($bonuses as $bonus) {
            RankBonus::create([
                'rank_id' => $rank->id,
                'bonus_type' => $bonus['type'],
                'name' => $bonus['name'],
                'name_th' => $bonus['name_th'],
                'description' => $bonus['description'] ?? null,
                'description_th' => $bonus['description_th'] ?? null,
                'amount' => $bonus['amount'] ?? null,
                'percentage' => $bonus['percentage'] ?? null,
                'reward_type' => $bonus['reward_type'] ?? null,
                'reward_details' => $bonus['reward_details'] ?? null,
                'is_active' => true,
                'auto_apply' => $bonus['auto_apply'] ?? true,
                'priority' => $bonus['priority'] ?? 0,
            ]);
        }
    }

    /**
     * ข้อมูลระดับทั้งหมด 8 ระดับ
     *
     * @return array
     */
    private function getAllRanks(): array
    {
        return [
            // ========================================
            // Level 1: Bronze (สำริด) - ระดับเริ่มต้น
            // ========================================
            [
                'name' => 'Bronze',
                'name_th' => 'สำริด',
                'description' => 'Starting rank for all new members. Begin your journey here!',
                'description_th' => 'ระดับเริ่มต้นสำหรับสมาชิกใหม่ทุกคน เริ่มต้นการเดินทางของคุณที่นี่!',
                'level' => 1,
                'icon' => '/images/ranks/bronze.svg',
                'color' => '#CD7F32',
                'badge_icon' => '🥉',
                'stars' => 1,
                'commission_rate' => 5.00,
                'bonus_multiplier' => 1.00,
                'promotion_bonus' => 0, // ระดับเริ่มต้นไม่มีโบนัสเลื่อนตำแหน่ง
                'max_downline_level_bonus' => 3,
                'unilevel_commission_levels' => [10, 5, 2], // 3 ชั้น
                'privileges' => [],
                'min_points' => 0,
                'min_referrals' => 0,
                'min_sales' => 0,
                'monthly_maintenance_pv' => 0,
                'withdrawal_fee_discount' => 0,
                'max_withdrawals_per_month' => 2,
                'is_active' => true,
                'is_default' => true,
                'is_top_tier' => false,
                'sort_order' => 1,
                'requirements' => [
                    [
                        'type' => 'points',
                        'name' => 'No requirements',
                        'name_th' => 'ไม่มีข้อกำหนด',
                        'description_th' => 'ระดับเริ่มต้นสำหรับสมาชิกใหม่ทุกคน',
                        'target' => 0,
                        'operator' => '>=',
                    ],
                ],
                'bonuses' => [
                    [
                        'type' => 'one_time',
                        'name' => 'Welcome Bonus',
                        'name_th' => 'โบนัสต้อนรับ',
                        'description_th' => 'โบนัสต้อนรับสมาชิกใหม่',
                        'amount' => 100,
                        'auto_apply' => true,
                    ],
                ],
            ],

            // ========================================
            // Level 2: Silver (เงิน)
            // ========================================
            [
                'name' => 'Silver',
                'name_th' => 'เงิน',
                'description' => 'Active affiliate with growing network. Building your foundation.',
                'description_th' => 'พันธมิตรที่กำลังเติบโต กำลังสร้างรากฐานของคุณ',
                'level' => 2,
                'icon' => '/images/ranks/silver.svg',
                'color' => '#C0C0C0',
                'badge_icon' => '🥈',
                'stars' => 2,
                'commission_rate' => 7.50,
                'bonus_multiplier' => 1.25,
                'promotion_bonus' => 500, // โบนัสเลื่อนตำแหน่ง 500 บาท
                'max_downline_level_bonus' => 4,
                'unilevel_commission_levels' => [10, 5, 3, 2], // 4 ชั้น
                'privileges' => ['vip_support'],
                'min_points' => 100,
                'min_referrals' => 5,
                'min_sales' => 10000,
                'monthly_maintenance_pv' => 50,
                'withdrawal_fee_discount' => 5,
                'max_withdrawals_per_month' => 4,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 2,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 100 Points', 'name_th' => 'รับคะแนน 100 แต้ม', 'target' => 100, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '5 Referrals', 'name_th' => 'แนะนำ 5 คน', 'target' => 5, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿10,000 Sales', 'name_th' => 'ยอดขาย 10,000 บาท', 'target' => 10000, 'unit' => 'บาท'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Silver Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับเงิน', 'amount' => 500, 'auto_apply' => true],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 2.50],
                ],
            ],

            // ========================================
            // Level 3: Gold (ทอง)
            // ========================================
            [
                'name' => 'Gold',
                'name_th' => 'ทอง',
                'description' => 'Successful affiliate with strong performance. You are a leader now!',
                'description_th' => 'พันธมิตรที่ประสบความสำเร็จ คุณเป็นผู้นำแล้ว!',
                'level' => 3,
                'icon' => '/images/ranks/gold.svg',
                'color' => '#FFD700',
                'badge_icon' => '🥇',
                'stars' => 3,
                'commission_rate' => 10.00,
                'bonus_multiplier' => 1.50,
                'promotion_bonus' => 2000,
                'max_downline_level_bonus' => 5,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1], // 5 ชั้น
                'privileges' => ['vip_support', 'priority_withdrawal'],
                'min_points' => 500,
                'min_referrals' => 20,
                'min_sales' => 50000,
                'monthly_maintenance_pv' => 100,
                'withdrawal_fee_discount' => 10,
                'max_withdrawals_per_month' => 8,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 3,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 500 Points', 'name_th' => 'รับคะแนน 500 แต้ม', 'target' => 500, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '20 Referrals', 'name_th' => 'แนะนำ 20 คน', 'target' => 20, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿50,000 Sales', 'name_th' => 'ยอดขาย 50,000 บาท', 'target' => 50000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '10 Active Members', 'name_th' => 'สมาชิกที่ Active 10 คน', 'target' => 10, 'unit' => 'คน'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Gold Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับทอง', 'amount' => 2000, 'auto_apply' => true],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 500],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 5.00],
                ],
            ],

            // ========================================
            // Level 4: Platinum (แพลตินัม)
            // ========================================
            [
                'name' => 'Platinum',
                'name_th' => 'แพลตินัม',
                'description' => 'Elite affiliate with exceptional results. A true business builder!',
                'description_th' => 'พันธมิตรระดับยอดเยี่ยม ผู้สร้างธุรกิจตัวจริง!',
                'level' => 4,
                'icon' => '/images/ranks/platinum.svg',
                'color' => '#E5E4E2',
                'badge_icon' => '💎',
                'stars' => 4,
                'commission_rate' => 15.00,
                'bonus_multiplier' => 2.00,
                'promotion_bonus' => 10000,
                'max_downline_level_bonus' => 6,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1, 1], // 6 ชั้น
                'privileges' => ['vip_support', 'priority_withdrawal', 'exclusive_products', 'leadership_training'],
                'min_points' => 2000,
                'min_referrals' => 50,
                'min_sales' => 200000,
                'monthly_maintenance_pv' => 200,
                'withdrawal_fee_discount' => 20,
                'max_withdrawals_per_month' => 15,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 4,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 2000 Points', 'name_th' => 'รับคะแนน 2,000 แต้ม', 'target' => 2000, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '50 Referrals', 'name_th' => 'แนะนำ 50 คน', 'target' => 50, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿200,000 Sales', 'name_th' => 'ยอดขาย 200,000 บาท', 'target' => 200000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '25 Active Members', 'name_th' => 'สมาชิกที่ Active 25 คน', 'target' => 25, 'unit' => 'คน'],
                    ['type' => 'team_sales', 'name' => '฿500,000 Team Sales', 'name_th' => 'ยอดขายทีม 500,000 บาท', 'target' => 500000, 'unit' => 'บาท'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Platinum Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับแพลตินัม', 'amount' => 10000, 'auto_apply' => true],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 2000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 10.00],
                    ['type' => 'privilege', 'name' => 'Leadership Training', 'name_th' => 'สิทธิ์เข้าอบรมผู้นำ', 'reward_type' => 'leadership_training'],
                ],
            ],

            // ========================================
            // Level 5: Diamond (เพชร)
            // ========================================
            [
                'name' => 'Diamond',
                'name_th' => 'เพชร',
                'description' => 'Top-tier affiliate with outstanding achievements. You shine bright!',
                'description_th' => 'พันธมิตรระดับสูง ผลงานโดดเด่น คุณเจิดจรัส!',
                'level' => 5,
                'icon' => '/images/ranks/diamond.svg',
                'color' => '#B9F2FF',
                'badge_icon' => '💠',
                'stars' => 5,
                'commission_rate' => 20.00,
                'bonus_multiplier' => 2.50,
                'promotion_bonus' => 50000,
                'max_downline_level_bonus' => 7,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1, 1, 0.5], // 7 ชั้น
                'privileges' => ['vip_support', 'priority_withdrawal', 'exclusive_products', 'leadership_training', 'travel_rewards'],
                'min_points' => 10000,
                'min_referrals' => 100,
                'min_sales' => 1000000,
                'monthly_maintenance_pv' => 300,
                'withdrawal_fee_discount' => 30,
                'max_withdrawals_per_month' => null, // ไม่จำกัด
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 5,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 10,000 Points', 'name_th' => 'รับคะแนน 10,000 แต้ม', 'target' => 10000, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '100 Referrals', 'name_th' => 'แนะนำ 100 คน', 'target' => 100, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿1,000,000 Sales', 'name_th' => 'ยอดขาย 1,000,000 บาท', 'target' => 1000000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '50 Active Members', 'name_th' => 'สมาชิกที่ Active 50 คน', 'target' => 50, 'unit' => 'คน'],
                    ['type' => 'team_sales', 'name' => '฿2,000,000 Team Sales', 'name_th' => 'ยอดขายทีม 2,000,000 บาท', 'target' => 2000000, 'unit' => 'บาท'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Diamond Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับเพชร', 'amount' => 50000, 'auto_apply' => true],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 5000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 15.00],
                    ['type' => 'privilege', 'name' => 'VIP Support', 'name_th' => 'บริการ VIP พิเศษ', 'reward_type' => 'vip_support'],
                    ['type' => 'privilege', 'name' => 'Travel Rewards', 'name_th' => 'สิทธิ์รางวัลท่องเที่ยว', 'reward_type' => 'travel_rewards'],
                ],
            ],

            // ========================================
            // Level 6: Crown (มงกุฎ) - ระดับใหม่
            // ========================================
            [
                'name' => 'Crown',
                'name_th' => 'มงกุฎ',
                'description' => 'Elite leader with crown achievement. Wearing the crown of success!',
                'description_th' => 'ผู้นำระดับเอลิท สวมมงกุฎแห่งความสำเร็จ!',
                'level' => 6,
                'icon' => '/images/ranks/crown.svg',
                'color' => '#FFB800',
                'badge_icon' => '👑',
                'stars' => 6,
                'commission_rate' => 25.00,
                'bonus_multiplier' => 3.00,
                'promotion_bonus' => 100000,
                'max_downline_level_bonus' => 8,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1, 1, 0.5, 0.5], // 8 ชั้น
                'privileges' => ['vip_support', 'priority_withdrawal', 'exclusive_products', 'leadership_training', 'travel_rewards', 'car_bonus'],
                'min_points' => 25000,
                'min_referrals' => 200,
                'min_sales' => 2500000,
                'monthly_maintenance_pv' => 500,
                'withdrawal_fee_discount' => 40,
                'max_withdrawals_per_month' => null,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 6,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 25,000 Points', 'name_th' => 'รับคะแนน 25,000 แต้ม', 'target' => 25000, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '200 Referrals', 'name_th' => 'แนะนำ 200 คน', 'target' => 200, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿2,500,000 Sales', 'name_th' => 'ยอดขาย 2,500,000 บาท', 'target' => 2500000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '100 Active Members', 'name_th' => 'สมาชิกที่ Active 100 คน', 'target' => 100, 'unit' => 'คน'],
                    ['type' => 'team_sales', 'name' => '฿5,000,000 Team Sales', 'name_th' => 'ยอดขายทีม 5,000,000 บาท', 'target' => 5000000, 'unit' => 'บาท'],
                    ['type' => 'diamond_legs', 'name' => '2 Diamond Legs', 'name_th' => 'มีลูกทีมระดับเพชร 2 คน', 'target' => 2, 'unit' => 'คน'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Crown Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับมงกุฎ', 'amount' => 100000, 'auto_apply' => true, 'description_th' => 'โบนัสพิเศษ 100,000 บาท เมื่อเลื่อนระดับ'],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 10000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 20.00],
                    ['type' => 'privilege', 'name' => 'Car Bonus', 'name_th' => 'สิทธิ์โบนัสรถยนต์', 'reward_type' => 'car_bonus', 'reward_details' => 'ค่ารถยนต์รายเดือน 20,000 บาท'],
                ],
            ],

            // ========================================
            // Level 7: Royal (รอยัล) - ระดับใหม่
            // ========================================
            [
                'name' => 'Royal',
                'name_th' => 'รอยัล',
                'description' => 'Royal class leader. You are among the royalty of success!',
                'description_th' => 'ผู้นำระดับรอยัล คุณอยู่ในกลุ่มราชวงศ์แห่งความสำเร็จ!',
                'level' => 7,
                'icon' => '/images/ranks/royal.svg',
                'color' => '#8B5CF6',
                'badge_icon' => '🏆',
                'stars' => 7,
                'commission_rate' => 30.00,
                'bonus_multiplier' => 4.00,
                'promotion_bonus' => 300000,
                'max_downline_level_bonus' => 9,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1, 1, 0.5, 0.5, 0.25], // 9 ชั้น
                'privileges' => ['vip_support', 'priority_withdrawal', 'exclusive_products', 'leadership_training', 'travel_rewards', 'car_bonus', 'house_bonus', 'personal_assistant'],
                'min_points' => 50000,
                'min_referrals' => 350,
                'min_sales' => 5000000,
                'monthly_maintenance_pv' => 1000,
                'withdrawal_fee_discount' => 50,
                'max_withdrawals_per_month' => null,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => false,
                'sort_order' => 7,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 50,000 Points', 'name_th' => 'รับคะแนน 50,000 แต้ม', 'target' => 50000, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '350 Referrals', 'name_th' => 'แนะนำ 350 คน', 'target' => 350, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿5,000,000 Sales', 'name_th' => 'ยอดขาย 5,000,000 บาท', 'target' => 5000000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '200 Active Members', 'name_th' => 'สมาชิกที่ Active 200 คน', 'target' => 200, 'unit' => 'คน'],
                    ['type' => 'team_sales', 'name' => '฿10,000,000 Team Sales', 'name_th' => 'ยอดขายทีม 10,000,000 บาท', 'target' => 10000000, 'unit' => 'บาท'],
                    ['type' => 'crown_legs', 'name' => '3 Crown Legs', 'name_th' => 'มีลูกทีมระดับมงกุฎ 3 คน', 'target' => 3, 'unit' => 'คน'],
                ],
                'bonuses' => [
                    ['type' => 'one_time', 'name' => 'Royal Promotion Bonus', 'name_th' => 'โบนัสเลื่อนระดับรอยัล', 'amount' => 300000, 'auto_apply' => true, 'description_th' => 'โบนัสพิเศษ 300,000 บาท เมื่อเลื่อนระดับ'],
                    ['type' => 'monthly', 'name' => 'Monthly Bonus', 'name_th' => 'โบนัสรายเดือน', 'amount' => 25000],
                    ['type' => 'commission', 'name' => 'Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชัน', 'percentage' => 25.00],
                    ['type' => 'privilege', 'name' => 'House Bonus', 'name_th' => 'สิทธิ์โบนัสบ้าน', 'reward_type' => 'house_bonus', 'reward_details' => 'ค่าบ้านรายเดือน 50,000 บาท'],
                    ['type' => 'privilege', 'name' => 'Personal Assistant', 'name_th' => 'ผู้ช่วยส่วนตัว', 'reward_type' => 'personal_assistant'],
                ],
            ],

            // ========================================
            // Level 8: Legend (ตำนาน) - ระดับสูงสุด
            // ========================================
            [
                'name' => 'Legend',
                'name_th' => 'ตำนาน',
                'description' => 'The ultimate achievement! You are a LEGEND. One-time 1,000,000 THB auto-bonus!',
                'description_th' => 'ความสำเร็จสูงสุด! คุณคือตำนาน รับโบนัสอัตโนมัติ 1,000,000 บาท ครั้งเดียว!',
                'level' => 8,
                'icon' => '/images/ranks/legend.svg',
                'color' => '#EC4899',
                'badge_icon' => '🌟',
                'stars' => 8,
                'commission_rate' => 35.00,
                'bonus_multiplier' => 5.00,
                'promotion_bonus' => 1000000, // โบนัสเลื่อนตำแหน่ง 1,000,000 บาท!
                'max_downline_level_bonus' => 10,
                'unilevel_commission_levels' => [10, 5, 3, 2, 1, 1, 0.5, 0.5, 0.25, 0.25], // 10 ชั้น
                'privileges' => [
                    'vip_support',
                    'priority_withdrawal',
                    'exclusive_products',
                    'leadership_training',
                    'travel_rewards',
                    'car_bonus',
                    'house_bonus',
                    'global_bonus_pool',
                    'personal_assistant',
                    'unlimited_withdrawals',
                ],
                'min_points' => 100000,
                'min_referrals' => 500,
                'min_sales' => 10000000,
                'monthly_maintenance_pv' => 2000,
                'withdrawal_fee_discount' => 100, // ฟรีค่าธรรมเนียม
                'max_withdrawals_per_month' => null,
                'is_active' => true,
                'is_default' => false,
                'is_top_tier' => true, // ระดับสูงสุด
                'sort_order' => 8,
                'requirements' => [
                    ['type' => 'points', 'name' => 'Earn 100,000 Points', 'name_th' => 'รับคะแนน 100,000 แต้ม', 'target' => 100000, 'unit' => 'แต้ม'],
                    ['type' => 'referrals', 'name' => '500 Referrals', 'name_th' => 'แนะนำ 500 คน', 'target' => 500, 'unit' => 'คน'],
                    ['type' => 'sales', 'name' => '฿10,000,000 Sales', 'name_th' => 'ยอดขาย 10,000,000 บาท', 'target' => 10000000, 'unit' => 'บาท'],
                    ['type' => 'active_referrals', 'name' => '300 Active Members', 'name_th' => 'สมาชิกที่ Active 300 คน', 'target' => 300, 'unit' => 'คน'],
                    ['type' => 'team_sales', 'name' => '฿25,000,000 Team Sales', 'name_th' => 'ยอดขายทีม 25,000,000 บาท', 'target' => 25000000, 'unit' => 'บาท'],
                    ['type' => 'royal_legs', 'name' => '3 Royal Legs', 'name_th' => 'มีลูกทีมระดับรอยัล 3 คน', 'target' => 3, 'unit' => 'คน'],
                ],
                'bonuses' => [
                    [
                        'type' => 'one_time',
                        'name' => 'LEGEND MEGA BONUS',
                        'name_th' => 'โบนัสตำนาน 1 ล้านบาท',
                        'description' => 'One-time automatic bonus of 1,000,000 THB upon reaching Legend rank',
                        'description_th' => 'โบนัสอัตโนมัติ 1,000,000 บาท เมื่อเลื่อนสู่ระดับตำนาน (ได้รับครั้งเดียวเท่านั้น)',
                        'amount' => 1000000,
                        'auto_apply' => true,
                        'priority' => 100,
                    ],
                    ['type' => 'monthly', 'name' => 'Legend Monthly Bonus', 'name_th' => 'โบนัสรายเดือนตำนาน', 'amount' => 100000],
                    ['type' => 'commission', 'name' => 'Ultimate Commission Boost', 'name_th' => 'เพิ่มค่าคอมมิชชันสูงสุด', 'percentage' => 30.00],
                    ['type' => 'privilege', 'name' => 'Global Bonus Pool', 'name_th' => 'แบ่งปันกำไรทั่วโลก', 'reward_type' => 'global_bonus_pool', 'reward_details' => 'รับส่วนแบ่ง 2% จากกำไรทั่วโลกรายเดือน'],
                    ['type' => 'privilege', 'name' => 'No Withdrawal Fee', 'name_th' => 'ไม่มีค่าธรรมเนียมถอน', 'reward_type' => 'unlimited_withdrawals', 'reward_details' => 'ถอนฟรีไม่จำกัด'],
                ],
            ],
        ];
    }
}
