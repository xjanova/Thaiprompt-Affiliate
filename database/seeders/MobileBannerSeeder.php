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
        // ⚠️ link ต้องเป็น route ที่มีอยู่ในแอพ หรือ URL ภายนอก
        // Routes ที่ใช้ได้: /dashboard, /register, /shopping, /wallet, /referral,
        //                   /services, /academy, /support, /tpix, /stores, /cart เป็นต้น
        //
        // 📸 รูปภาพ: ใช้ placeholder images จาก picsum.photos สำหรับข้อมูลตัวอย่าง
        // Admin สามารถเปลี่ยนรูปได้จากหน้า /admin/mobile-app/banners
        //
        // 📍 ตำแหน่ง (position):
        //   - home = แบนเนอร์หน้าหลัก
        //   - shop = แบนเนอร์หน้าช้อป
        $banners = [
            // === แบนเนอร์หน้าหลัก (home) ===
            [
                'title' => 'ยินดีต้อนรับสู่ TP-Affiliate',
                'image' => 'https://picsum.photos/seed/banner1/800/400',
                'link' => '/dashboard',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'home',  // หน้าหลัก
                'sort_order' => 1,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            [
                'title' => 'โปรโมชั่นพิเศษ สมัครสมาชิกวันนี้',
                'image' => 'https://picsum.photos/seed/banner2/800/400',
                'link' => '/register',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'home',  // หน้าหลัก
                'sort_order' => 2,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            [
                'title' => 'ติดตามข่าวสารและโปรโมชั่น',
                'image' => 'https://picsum.photos/seed/banner3/800/400',
                'link' => 'https://line.me/R/ti/p/@thaiprompt',
                'link_type' => 'external',
                'link_target' => null,
                'position' => 'home',  // หน้าหลัก
                'sort_order' => 3,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            // === แบนเนอร์หน้าช้อป (shop) ===
            [
                'title' => 'สินค้าลดราคาพิเศษ',
                'image' => 'https://picsum.photos/seed/shop-banner1/800/400',
                'link' => '/shopping',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'shop',  // หน้าช้อป
                'sort_order' => 1,
                'is_active' => true,
                'view_count' => 0,
                'click_count' => 0,
            ],
            [
                'title' => 'สินค้าใหม่ประจำสัปดาห์',
                'image' => 'https://picsum.photos/seed/shop-banner2/800/400',
                'link' => '/shopping',
                'link_type' => 'internal',
                'link_target' => null,
                'position' => 'shop',  // หน้าช้อป
                'sort_order' => 2,
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
