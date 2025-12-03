<?php

namespace Database\Seeders;

use App\Models\RankSetting;
use Illuminate\Database\Seeder;

/**
 * RankSettingSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่าระบบ Rank
 * ป้องกัน settings หายหลัง deploy
 */
class RankSettingSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้น Rank Settings
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Rank Settings...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (RankSetting::count() > 0) {
            $this->command->info('  ✓ Rank Settings มีอยู่แล้ว ข้าม...');
            return;
        }

        // สร้างการตั้งค่าเริ่มต้น
        RankSetting::create([
            // General Settings
            'enable_ranking_system' => true,
            'enable_auto_promotion' => true,
            'enable_manual_approval' => false,
            'enable_rank_purchase' => false,

            // Auto Promotion Settings
            'promotion_check_frequency' => 'daily',
            'notify_on_eligible' => true,
            'notify_on_promotion' => true,

            // Manual Approval Settings
            'approval_admins' => null,
            'require_all_approvals' => false,

            // Purchase Settings
            'allow_skip_requirements' => false,
            'purchase_discount_percentage' => 0,
            'purchase_requires_approval' => true,

            // Display Settings
            'show_leaderboard' => true,
            'show_rank_badges' => true,
            'show_progress_bar' => true,
            'show_team_ranks' => true,

            // Point System
            'points_per_referral' => 10,
            'points_per_sale' => 1.00,
            'points_per_active_month' => 5,

            // Bonus Calculation
            'bonus_calculation_period' => 'monthly',
            'accumulate_bonuses' => true,
        ]);

        $this->command->info('✅ Seed ข้อมูล Rank Settings สำเร็จ!');
    }
}
