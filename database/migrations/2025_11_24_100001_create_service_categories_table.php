<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_categories - หมวดหมู่บริการ
     *
     * ตารางนี้เก็บหมวดหมู่ของบริการต่างๆ เช่น นวด, สปา, ทำความสะอาด, delivery
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('service_categories')) {
            return;
        }

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();

            // ข้อมูลหมวดหมู่
            $table->string('name', 255)->comment('ชื่อหมวดหมู่ เช่น นวด, สปา');
            $table->string('slug', 255)->unique()->comment('URL slug สำหรับ SEO');
            $table->text('description')->nullable()->comment('คำอธิบายหมวดหมู่');

            // การแสดงผล
            $table->string('icon', 100)->nullable()->comment('Font Awesome icon class');
            $table->string('image', 500)->nullable()->comment('URL รูปภาพหมวดหมู่');
            $table->string('color', 20)->nullable()->comment('สีประจำหมวดหมู่ (hex code)');

            // การจัดการ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
            $table->integer('sort_order')->default(0)->comment('ลำดับการแสดงผล');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug', 'service_cat_slug_idx');
            $table->index(['is_active', 'sort_order'], 'service_cat_active_sort_idx');
        });
    }

    /**
     * ลบตาราง service_categories
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
