<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แก้ไขระบบ Mobile Banners:
     *
     * 1. เปลี่ยน position จาก 'top/middle/bottom' เป็น 'home/shop'
     *    - home = แบนเนอร์หน้าหลัก
     *    - shop = แบนเนอร์หน้า shop
     *
     * 2. แก้ไข image URLs ที่ใช้ path ที่ไม่มีไฟล์จริง
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mobile_banners มีอยู่หรือไม่
        if (! Schema::hasTable('mobile_banners')) {
            return;
        }

        // 1. เปลี่ยน enum position จาก top/middle/bottom เป็น home/shop
        // ก่อนอื่นต้องอัพเดทข้อมูลที่มีอยู่
        DB::table('mobile_banners')
            ->whereIn('position', ['top', 'middle', 'bottom'])
            ->update(['position' => 'home']);

        // เปลี่ยน column type เป็น enum ใหม่
        Schema::table('mobile_banners', function (Blueprint $table) {
            // ลบ column เดิมและสร้างใหม่ (MySQL ไม่รองรับการเปลี่ยน enum โดยตรง)
            $table->string('position_new', 20)->default('home')->after('link_target');
        });

        // คัดลอกค่าจาก position เดิมไป position_new
        DB::table('mobile_banners')->update(['position_new' => 'home']);

        // ลบ column เดิม
        Schema::table('mobile_banners', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        // เปลี่ยนชื่อ column ใหม่
        Schema::table('mobile_banners', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
        });

        // เพิ่ม index
        Schema::table('mobile_banners', function (Blueprint $table) {
            $table->index('position');
        });

        // 2. อัพเดท image URLs ที่ใช้ path เก่าที่ไม่มีไฟล์จริง
        $newUrls = [
            '/images/banners/welcome-banner.jpg' => 'https://picsum.photos/seed/banner1/800/400',
            '/images/banners/promo-banner.jpg' => 'https://picsum.photos/seed/banner2/800/400',
            '/images/banners/news-banner.jpg' => 'https://picsum.photos/seed/banner3/800/400',
        ];

        foreach ($newUrls as $oldPath => $newUrl) {
            DB::table('mobile_banners')
                ->where('image', $oldPath)
                ->update([
                    'image' => $newUrl,
                    'updated_at' => now(),
                ]);
        }

        // 3. อัพเดท sort_order ให้เรียงถูกต้อง
        $banners = DB::table('mobile_banners')
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
     */
    public function down(): void
    {
        if (! Schema::hasTable('mobile_banners')) {
            return;
        }

        // เปลี่ยนกลับเป็น top/middle/bottom
        Schema::table('mobile_banners', function (Blueprint $table) {
            $table->dropIndex(['position']);
        });

        DB::table('mobile_banners')
            ->where('position', 'home')
            ->update(['position' => 'top']);

        DB::table('mobile_banners')
            ->where('position', 'shop')
            ->update(['position' => 'middle']);
    }
};
