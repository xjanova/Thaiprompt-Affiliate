<?php

namespace Database\Seeders;

use App\Models\StoreBanner;
use Illuminate\Database\Seeder;

/**
 * StoreBanner Seeder - สร้าง Banner เริ่มต้นสำหรับ Homepage
 *
 * สร้าง 3 banner สวยงามสำหรับแสดงบนหน้าแรกของ Storefront
 * พร้อม gradient backgrounds และข้อความภาษาไทย
 *
 * @example php artisan db:seed --class=StoreBannerSeeder
 */
class StoreBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎨 กำลังสร้าง StoreBanner เริ่มต้น...');

        // ตรวจสอบว่ามี banner homepage อยู่แล้วหรือไม่
        $existingBanners = StoreBanner::forHomepage()->count();

        if ($existingBanners > 0) {
            $this->command->info('📌 มี Banner หน้าแรกอยู่แล้ว '.$existingBanners.' รายการ - ข้าม');

            return;
        }

        // ข้อมูล Banner ทั้ง 3 slides
        $banners = [
            [
                'location' => 'homepage',
                'title' => 'Flash Sale สุดพิเศษ',
                'subtitle' => 'ลดกระหน่ำทุกชิ้น ช้อปสินค้าคุณภาพในราคาที่ดีที่สุด ส่งฟรีทั่วไทย',
                'badge' => 'FLASH SALE',
                'highlight_text' => 'สูงสุด 70%',
                'highlight_label' => 'ส่วนลด',
                'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1600&h=600&fit=crop&q=80',
                'gradient' => 'from-orange-500 via-red-500 to-pink-600',
                'cta_text' => 'ช้อปเลย',
                'cta_url' => '/shop?sort_by=popular',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'location' => 'homepage',
                'title' => 'คอลเลคชั่นใหม่ประจำเดือน',
                'subtitle' => 'สินค้ามาใหม่ล่าสุด คัดสรรมาเพื่อคุณโดยเฉพาะ อัพเดททุกสัปดาห์',
                'badge' => 'สินค้าใหม่',
                'highlight_text' => '500+',
                'highlight_label' => 'รายการใหม่',
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1600&h=600&fit=crop&q=80',
                'gradient' => 'from-purple-600 via-pink-500 to-red-500',
                'cta_text' => 'ดูสินค้าใหม่',
                'cta_url' => '/shop?sort_by=newest',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'location' => 'homepage',
                'title' => 'Official Store',
                'subtitle' => 'สินค้าจากร้านค้าทางการ รับประกันคุณภาพ 100% พร้อมบริการหลังการขายครบวงจร',
                'badge' => 'รับประกันของแท้',
                'highlight_text' => '100%',
                'highlight_label' => 'ของแท้',
                'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1600&h=600&fit=crop&q=80',
                'gradient' => 'from-blue-600 via-indigo-500 to-purple-600',
                'cta_text' => 'เข้าชม Official Store',
                'cta_url' => '/official-shop',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        // สร้าง Banners
        foreach ($banners as $bannerData) {
            StoreBanner::create($bannerData);
        }

        $this->command->info('✅ สร้าง Banner สำเร็จ '.count($banners).' รายการ');
    }
}
