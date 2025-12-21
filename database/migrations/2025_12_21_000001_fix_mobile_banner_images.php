<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * แก้ไข image URLs ของ mobile banners ที่ใช้ path ที่ไม่มีไฟล์จริง
     *
     * ปัญหา:
     * 1. Seeder เดิมใช้ path /images/banners/xxx.jpg ที่ไม่มีไฟล์จริง
     * 2. Banner ที่ 3 มี position = 'middle' ทำให้ไม่แสดงร่วมกับ banner อื่น
     *
     * แก้ไข:
     * 1. อัพเดท image URLs ให้ใช้ placeholder images ที่ทำงานได้
     * 2. เปลี่ยน position เป็น 'top' ทั้งหมดเพื่อให้แสดงครบทุก banner
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mobile_banners มีอยู่หรือไม่
        if (!DB::getSchemaBuilder()->hasTable('mobile_banners')) {
            return;
        }

        // อัพเดท banners ที่ใช้ path เก่าที่ไม่มีไฟล์จริง
        $oldPaths = [
            '/images/banners/welcome-banner.jpg',
            '/images/banners/promo-banner.jpg',
            '/images/banners/news-banner.jpg',
        ];

        $newUrls = [
            '/images/banners/welcome-banner.jpg' => 'https://picsum.photos/seed/banner1/800/400',
            '/images/banners/promo-banner.jpg' => 'https://picsum.photos/seed/banner2/800/400',
            '/images/banners/news-banner.jpg' => 'https://picsum.photos/seed/banner3/800/400',
        ];

        // อัพเดท image paths
        foreach ($newUrls as $oldPath => $newUrl) {
            DB::table('mobile_banners')
                ->where('image', $oldPath)
                ->update([
                    'image' => $newUrl,
                    'position' => 'top',  // เปลี่ยนเป็น 'top' เพื่อให้แสดงรวมกัน
                    'updated_at' => now(),
                ]);
        }

        // อัพเดท sort_order ให้เรียงถูกต้อง
        $banners = DB::table('mobile_banners')
            ->where('position', 'top')
            ->orderBy('id')
            ->get();

        $sortOrder = 1;
        foreach ($banners as $banner) {
            DB::table('mobile_banners')
                ->where('id', $banner->id)
                ->update(['sort_order' => $sortOrder++]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่จำเป็นต้อง rollback เพราะเป็นการแก้ไขข้อมูลให้ถูกต้อง
    }
};
