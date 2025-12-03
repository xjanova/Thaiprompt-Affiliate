<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง store_banners สำหรับจัดการ Banner ของร้านค้า
 *
 * ตารางนี้เก็บข้อมูล banners ที่ใช้แสดงบนหน้า storefront และหน้าร้านค้าแต่ละร้าน
 * รองรับทั้ง banner ของระบบ (homepage) และ banner ของร้านค้าแต่ละร้าน (store_id)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('store_banners')) {
            return;
        }

        Schema::create('store_banners', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์กับร้านค้า (null = banner ของระบบ/homepage)
            $table->foreignId('store_id')
                ->nullable()
                ->constrained('vendor_stores')
                ->onDelete('cascade');

            // ตำแหน่งที่แสดง banner
            $table->string('location', 50)->default('homepage'); // homepage, category, store
            $table->string('category_slug', 100)->nullable(); // สำหรับ location = category

            // ข้อมูล Banner
            $table->string('title', 200)->nullable();
            $table->string('subtitle', 300)->nullable();
            $table->string('badge', 50)->nullable(); // เช่น "Flash Sale", "ใหม่"
            $table->string('highlight_text', 50)->nullable(); // เช่น "70%", "฿500"
            $table->string('highlight_label', 50)->nullable(); // เช่น "ส่วนลด", "ออฟ"

            // รูปภาพและ Gradient
            $table->string('image_url', 500)->nullable();
            $table->string('gradient', 200)->nullable(); // Tailwind gradient classes

            // Call-to-Action
            $table->string('cta_text', 50)->nullable();
            $table->string('cta_url', 500)->nullable();

            // การจัดเรียง
            $table->integer('sort_order')->default(0);

            // สถานะ
            $table->boolean('is_active')->default(true);

            // กำหนดช่วงเวลาแสดง
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // สถิติ
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('location');
            $table->index('is_active');
            $table->index(['store_id', 'is_active'], 'store_banner_idx');
            $table->index(['location', 'is_active', 'sort_order'], 'banner_display_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('store_banners');
    }
};
