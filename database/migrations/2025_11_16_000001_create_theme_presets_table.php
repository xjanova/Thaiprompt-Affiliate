<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง theme_presets
     *
     * เก็บชุด preset สีและการตั้งค่าสำหรับ Arrow X Theme
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('theme_presets')) {
            return;
        }

        Schema::create('theme_presets', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name', 100)->unique()->comment('ชื่อ preset (เช่น classic, modern-blue)');
            $table->string('display_name', 100)->comment('ชื่อแสดง (ภาษาไทย)');
            $table->text('description')->nullable()->comment('คำอธิบาย preset');
            $table->string('preview_image')->nullable()->comment('รูป preview');

            // สี theme (JSON)
            $table->json('colors')->comment('สี theme (primary_start, primary_middle, primary_end, success_color, etc.)');

            // สถานะ
            $table->boolean('is_featured')->default(false)->comment('แนะนำหรือไม่');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
            $table->unsignedInteger('usage_count')->default(0)->comment('จำนวนครั้งที่ถูกใช้');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('usage_count');
        });
    }

    /**
     * ลบตาราง theme_presets
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_presets');
    }
};
