<?php

namespace Database\Seeders;

use App\Models\StoreTrophy;
use Illuminate\Database\Seeder;

/**
 * StoreTrophySeeder
 *
 * สร้างข้อมูล Trophy เริ่มต้นสำหรับร้านค้า
 */
class StoreTrophySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏆 กำลัง seed ข้อมูล Store Trophies...');

        $trophies = [
            // === ยอดขาย (Sales Count) ===
            [
                'name' => 'First Sale',
                'name_th' => 'ขายได้แล้ว!',
                'description' => 'Complete your first sale',
                'description_th' => 'ขายสินค้าได้ครั้งแรก',
                'icon' => 'fa-shopping-cart',
                'icon_color' => '#CD7F32',
                'badge_color' => '#CD7F32',
                'requirement_type' => 'sales_count',
                'requirement_value' => 1,
                'tier' => 'bronze',
                'points' => 10,
            ],
            [
                'name' => 'Rising Seller',
                'name_th' => 'นักขายมือใหม่',
                'description' => 'Complete 10 sales',
                'description_th' => 'ขายสินค้าได้ 10 รายการ',
                'icon' => 'fa-chart-line',
                'icon_color' => '#C0C0C0',
                'badge_color' => '#C0C0C0',
                'requirement_type' => 'sales_count',
                'requirement_value' => 10,
                'tier' => 'silver',
                'points' => 25,
            ],
            [
                'name' => 'Popular Seller',
                'name_th' => 'ร้านขายดี',
                'description' => 'Complete 50 sales',
                'description_th' => 'ขายสินค้าได้ 50 รายการ',
                'icon' => 'fa-fire',
                'icon_color' => '#FFD700',
                'badge_color' => '#FFD700',
                'requirement_type' => 'sales_count',
                'requirement_value' => 50,
                'tier' => 'gold',
                'points' => 50,
            ],
            [
                'name' => 'Top Seller',
                'name_th' => 'ร้านยอดนิยม',
                'description' => 'Complete 200 sales',
                'description_th' => 'ขายสินค้าได้ 200 รายการ',
                'icon' => 'fa-crown',
                'icon_color' => '#E5E4E2',
                'badge_color' => '#E5E4E2',
                'requirement_type' => 'sales_count',
                'requirement_value' => 200,
                'tier' => 'platinum',
                'points' => 100,
            ],
            [
                'name' => 'Elite Seller',
                'name_th' => 'ร้านระดับเพชร',
                'description' => 'Complete 1000 sales',
                'description_th' => 'ขายสินค้าได้ 1,000 รายการ',
                'icon' => 'fa-gem',
                'icon_color' => '#B9F2FF',
                'badge_color' => '#B9F2FF',
                'badge_gradient_from' => '#B9F2FF',
                'badge_gradient_to' => '#87CEEB',
                'requirement_type' => 'sales_count',
                'requirement_value' => 1000,
                'tier' => 'diamond',
                'points' => 200,
            ],

            // === คะแนนรีวิว (Rating) ===
            [
                'name' => 'Quality Service',
                'name_th' => 'บริการคุณภาพ',
                'description' => 'Maintain 4.0+ rating',
                'description_th' => 'รักษาคะแนนรีวิว 4.0 ขึ้นไป',
                'icon' => 'fa-star',
                'icon_color' => '#FFD700',
                'badge_color' => '#FFD700',
                'requirement_type' => 'rating',
                'requirement_value' => 4.0,
                'tier' => 'gold',
                'points' => 30,
            ],
            [
                'name' => 'Excellent Service',
                'name_th' => 'บริการยอดเยี่ยม',
                'description' => 'Maintain 4.5+ rating',
                'description_th' => 'รักษาคะแนนรีวิว 4.5 ขึ้นไป',
                'icon' => 'fa-star',
                'icon_color' => '#E5E4E2',
                'badge_color' => '#E5E4E2',
                'requirement_type' => 'rating',
                'requirement_value' => 4.5,
                'tier' => 'platinum',
                'points' => 50,
            ],
            [
                'name' => 'Perfect Service',
                'name_th' => 'บริการสมบูรณ์แบบ',
                'description' => 'Maintain 4.8+ rating',
                'description_th' => 'รักษาคะแนนรีวิว 4.8 ขึ้นไป',
                'icon' => 'fa-medal',
                'icon_color' => '#B9F2FF',
                'badge_color' => '#B9F2FF',
                'requirement_type' => 'rating',
                'requirement_value' => 4.8,
                'tier' => 'diamond',
                'points' => 100,
            ],

            // === ผู้ติดตาม (Followers) ===
            [
                'name' => 'Getting Known',
                'name_th' => 'เริ่มเป็นที่รู้จัก',
                'description' => 'Gain 10 followers',
                'description_th' => 'มีผู้ติดตาม 10 คน',
                'icon' => 'fa-users',
                'icon_color' => '#CD7F32',
                'badge_color' => '#CD7F32',
                'requirement_type' => 'followers',
                'requirement_value' => 10,
                'tier' => 'bronze',
                'points' => 15,
            ],
            [
                'name' => 'Community Builder',
                'name_th' => 'สร้างชุมชน',
                'description' => 'Gain 100 followers',
                'description_th' => 'มีผู้ติดตาม 100 คน',
                'icon' => 'fa-user-group',
                'icon_color' => '#C0C0C0',
                'badge_color' => '#C0C0C0',
                'requirement_type' => 'followers',
                'requirement_value' => 100,
                'tier' => 'silver',
                'points' => 30,
            ],
            [
                'name' => 'Fan Favorite',
                'name_th' => 'ขวัญใจแฟนๆ',
                'description' => 'Gain 500 followers',
                'description_th' => 'มีผู้ติดตาม 500 คน',
                'icon' => 'fa-heart',
                'icon_color' => '#FFD700',
                'badge_color' => '#FFD700',
                'requirement_type' => 'followers',
                'requirement_value' => 500,
                'tier' => 'gold',
                'points' => 60,
            ],
            [
                'name' => 'Influencer Store',
                'name_th' => 'ร้านอินฟลูเอนเซอร์',
                'description' => 'Gain 2000 followers',
                'description_th' => 'มีผู้ติดตาม 2,000 คน',
                'icon' => 'fa-bullhorn',
                'icon_color' => '#E5E4E2',
                'badge_color' => '#E5E4E2',
                'requirement_type' => 'followers',
                'requirement_value' => 2000,
                'tier' => 'platinum',
                'points' => 100,
            ],

            // === จำนวนสินค้า (Products) ===
            [
                'name' => 'Product Pioneer',
                'name_th' => 'เริ่มต้นสินค้า',
                'description' => 'List 5 products',
                'description_th' => 'ลงสินค้า 5 รายการ',
                'icon' => 'fa-box',
                'icon_color' => '#CD7F32',
                'badge_color' => '#CD7F32',
                'requirement_type' => 'products',
                'requirement_value' => 5,
                'tier' => 'bronze',
                'points' => 10,
            ],
            [
                'name' => 'Product Pro',
                'name_th' => 'มีสินค้าครบ',
                'description' => 'List 25 products',
                'description_th' => 'ลงสินค้า 25 รายการ',
                'icon' => 'fa-boxes-stacked',
                'icon_color' => '#C0C0C0',
                'badge_color' => '#C0C0C0',
                'requirement_type' => 'products',
                'requirement_value' => 25,
                'tier' => 'silver',
                'points' => 25,
            ],
            [
                'name' => 'Mega Store',
                'name_th' => 'ร้านใหญ่',
                'description' => 'List 100 products',
                'description_th' => 'ลงสินค้า 100 รายการ',
                'icon' => 'fa-warehouse',
                'icon_color' => '#FFD700',
                'badge_color' => '#FFD700',
                'requirement_type' => 'products',
                'requirement_value' => 100,
                'tier' => 'gold',
                'points' => 50,
            ],

            // === Premium Trophy (เฉพาะร้าน Premium) ===
            [
                'name' => 'Premium Store',
                'name_th' => 'ร้านพรีเมี่ยม',
                'description' => 'Selected as Premium Store by AI',
                'description_th' => 'ได้รับเลือกเป็นร้านพรีเมี่ยมจาก AI',
                'icon' => 'fa-trophy',
                'icon_color' => '#FFD700',
                'badge_color' => '#FFD700',
                'badge_gradient_from' => '#FFD700',
                'badge_gradient_to' => '#FFA500',
                'requirement_type' => 'rating',
                'requirement_value' => 0,
                'tier' => 'gold',
                'points' => 150,
                'is_premium' => true,
            ],
            [
                'name' => 'Diamond Premium',
                'name_th' => 'พรีเมี่ยมเพชร',
                'description' => 'Top Premium Store with 90+ AI score',
                'description_th' => 'ร้านพรีเมี่ยมระดับสูงสุด คะแนน AI 90+',
                'icon' => 'fa-gem',
                'icon_color' => '#B9F2FF',
                'badge_color' => '#B9F2FF',
                'badge_gradient_from' => '#B9F2FF',
                'badge_gradient_to' => '#FFD700',
                'requirement_type' => 'rating',
                'requirement_value' => 0,
                'tier' => 'diamond',
                'points' => 300,
                'is_premium' => true,
            ],
        ];

        $order = 0;
        foreach ($trophies as $trophy) {
            $order++;
            $trophy['display_order'] = $order;
            $trophy['is_active'] = true;
            $trophy['is_premium'] = $trophy['is_premium'] ?? false;

            StoreTrophy::updateOrCreate(
                ['name' => $trophy['name']],
                $trophy
            );
        }

        $this->command->info('✅ Seed ข้อมูล Store Trophies สำเร็จ! (' . count($trophies) . ' trophies)');
    }
}
