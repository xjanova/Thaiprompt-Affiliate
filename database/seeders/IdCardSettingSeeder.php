<?php

namespace Database\Seeders;

use App\Models\IdCardSetting;
use Illuminate\Database\Seeder;

/**
 * IdCardSettingSeeder - สร้างการตั้งค่าเริ่มต้นสำหรับ Virtual ID Card
 *
 * สร้างการตั้งค่าสำหรับ 8 ระดับ Rank พร้อม gradient และ effects ที่แตกต่างกัน
 */
class IdCardSettingSeeder extends Seeder
{
    /**
     * รัน seeder
     */
    public function run(): void
    {
        $this->command->info('🎨 กำลัง seed การตั้งค่า Virtual ID Card...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (IdCardSetting::count() > 0) {
            $this->command->warn('⚠️  พบการตั้งค่า ID Card ที่มีอยู่แล้ว ข้าม...');

            return;
        }

        // สร้าง default setting (ใช้กับทุกระดับ)
        IdCardSetting::create([
            'rank_level' => null,
            'name' => 'Default ID Card',
            'name_th' => 'บัตรประจำตัวเริ่มต้น',
            'background_type' => 'gradient',
            'background_gradient' => [
                'from' => '#8B5CF6',
                'via' => '#EC4899',
                'to' => '#3B82F6',
                'direction' => 'br',
            ],
            'logo_position' => ['x' => 24, 'y' => 24, 'visible' => true, 'width' => 48, 'height' => 48],
            'profile_position' => ['x' => 24, 'y' => 176, 'visible' => true, 'width' => 80, 'height' => 80],
            'name_position' => ['x' => 112, 'y' => 184, 'visible' => true],
            'rank_badge_position' => ['x' => 380, 'y' => 24, 'visible' => true],
            'member_number_position' => ['x' => 112, 'y' => 224, 'visible' => true],
            'qr_position' => ['x' => 370, 'y' => 190, 'visible' => true, 'width' => 56, 'height' => 56],
            'member_since_position' => ['x' => 360, 'y' => 168, 'visible' => true],
            'stars_position' => ['x' => 80, 'y' => 230, 'visible' => true],
            'effects' => [],
            'is_active' => true,
            'is_default' => true,
        ]);

        // สร้างการตั้งค่าสำหรับแต่ละระดับ
        $rankSettings = [
            // Level 1: Bronze
            [
                'rank_level' => 1,
                'name' => 'Bronze ID Card',
                'name_th' => 'บัตรประจำตัว Bronze',
                'background_gradient' => ['from' => '#B45309', 'via' => '#C2410C', 'to' => '#92400E', 'direction' => 'br'],
                'effects' => ['shimmer' => false, 'particles' => false],
            ],
            // Level 2: Silver
            [
                'rank_level' => 2,
                'name' => 'Silver ID Card',
                'name_th' => 'บัตรประจำตัว Silver',
                'background_gradient' => ['from' => '#9CA3AF', 'via' => '#D1D5DB', 'to' => '#6B7280', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => false],
            ],
            // Level 3: Gold
            [
                'rank_level' => 3,
                'name' => 'Gold ID Card',
                'name_th' => 'บัตรประจำตัว Gold',
                'background_gradient' => ['from' => '#EAB308', 'via' => '#F59E0B', 'to' => '#D97706', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => false, 'glow' => true],
            ],
            // Level 4: Platinum
            [
                'rank_level' => 4,
                'name' => 'Platinum ID Card',
                'name_th' => 'บัตรประจำตัว Platinum',
                'background_gradient' => ['from' => '#CBD5E1', 'via' => '#E2E8F0', 'to' => '#94A3B8', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => false, 'holographic' => true],
            ],
            // Level 5: Diamond
            [
                'rank_level' => 5,
                'name' => 'Diamond ID Card',
                'name_th' => 'บัตรประจำตัว Diamond',
                'background_gradient' => ['from' => '#22D3EE', 'via' => '#0EA5E9', 'to' => '#3B82F6', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => true, 'sparkle' => true],
            ],
            // Level 6: Crown
            [
                'rank_level' => 6,
                'name' => 'Crown ID Card',
                'name_th' => 'บัตรประจำตัว Crown',
                'background_gradient' => ['from' => '#F59E0B', 'via' => '#FBBF24', 'to' => '#F97316', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => true, 'glow' => true, 'crown' => true],
            ],
            // Level 7: Royal
            [
                'rank_level' => 7,
                'name' => 'Royal ID Card',
                'name_th' => 'บัตรประจำตัว Royal',
                'background_gradient' => ['from' => '#9333EA', 'via' => '#8B5CF6', 'to' => '#4F46E5', 'direction' => 'br'],
                'effects' => ['shimmer' => true, 'particles' => true, 'glow' => true, 'royal_ornaments' => true],
            ],
            // Level 8: Legend
            [
                'rank_level' => 8,
                'name' => 'Legend ID Card',
                'name_th' => 'บัตรประจำตัว Legend',
                'background_gradient' => ['from' => '#EC4899', 'via' => '#9333EA', 'to' => '#4F46E5', 'direction' => 'br'],
                'effects' => [
                    'shimmer' => true,
                    'particles' => true,
                    'glow' => true,
                    'aurora' => true,
                    'legendary' => true,
                ],
            ],
        ];

        foreach ($rankSettings as $setting) {
            IdCardSetting::create(array_merge([
                'background_type' => 'gradient',
                'logo_position' => ['x' => 24, 'y' => 24, 'visible' => true, 'width' => 48, 'height' => 48],
                'profile_position' => ['x' => 24, 'y' => 176, 'visible' => true, 'width' => 80, 'height' => 80],
                'name_position' => ['x' => 112, 'y' => 184, 'visible' => true],
                'rank_badge_position' => ['x' => 380, 'y' => 24, 'visible' => true],
                'member_number_position' => ['x' => 112, 'y' => 224, 'visible' => true],
                'qr_position' => ['x' => 370, 'y' => 190, 'visible' => true, 'width' => 56, 'height' => 56],
                'member_since_position' => ['x' => 360, 'y' => 168, 'visible' => true],
                'stars_position' => ['x' => 80, 'y' => 230, 'visible' => true],
                'is_active' => true,
                'is_default' => false,
            ], $setting));
        }

        $this->command->info('✅ สร้างการตั้งค่า ID Card สำเร็จ! (9 รายการ)');
    }
}
