<?php

namespace Database\Seeders;

use App\Models\MobileBanner;
use Illuminate\Database\Seeder;

/**
 * MobileBannerSeeder
 *
 * สร้างข้อมูลตัวอย่าง Banner สำหรับ Mobile App
 * Admin สามารถจัดการ banner ได้จากหน้า /admin/mobile-app/banners
 *
 * Banner จะถูกส่งไปยังแอพมือถือผ่าน API: GET /api/v1/mobile/banners
 */
class MobileBannerSeeder extends Seeder
{
    /**
     * สร้างข้อมูลตัวอย่าง Banner
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🖼️ กำลัง seed ข้อมูล Mobile Banners...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (MobileBanner::count() > 0) {
            $this->command->info('📌 Mobile Banners มีข้อมูลอยู่แล้ว ข้าม...');
            return;
        }

        // Sample Banners
        $banners = [
            [
                'title' => 'ยินดีต้อนรับสู่ TP-Affiliate',
                'image' => '/images/banners/welcome-banner.jpg',
                'link' => '/about',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'top',
                'sort_order' => 1,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            [
                'title' => 'โปรโมชั่นพิเศษ สมัครสมาชิกวันนี้',
                'image' => '/images/banners/promo-banner.jpg',
                'link' => '/register',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'top',
                'sort_order' => 2,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            [
                'title' => 'ติดตามข่าวสารและโปรโมชั่น',
                'image' => '/images/banners/news-banner.jpg',
                'link' => 'https://line.me',
                'link_type' => 'external',
                'link_target' => null,
                'position' => 'middle',
                'sort_order' => 1,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
        ];

        foreach ($banners as $bannerData) {
            MobileBanner::create($bannerData);
        }

        $this->command->info('✅ สร้าง Mobile Banners สำเร็จ! (' . count($banners) . ' รายการ)');
    }
}
