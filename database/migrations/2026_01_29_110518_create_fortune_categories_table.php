<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_categories
     *
     * ตารางนี้เก็บหมวดหมู่การทำนาย เช่น ความรัก, การเงิน, สุขภาพ, การงาน, ฯลฯ
     * แต่ละหมวดหมู่สามารถกำหนด prompt context เฉพาะได้
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('fortune_categories')) {
            return;
        }

        Schema::create('fortune_categories', function (Blueprint $table) {
            $table->id();

            // ข้อมูลหมวดหมู่
            $table->string('name', 100)->comment('ชื่อหมวดหมู่ เช่น ความรัก, การเงิน');
            $table->string('slug', 100)->unique()->comment('Slug สำหรับ URL');
            $table->string('icon', 50)->nullable()->comment('Icon class หรือ emoji');
            $table->string('color', 20)->default('#3B82F6')->comment('สีของหมวดหมู่ (hex code)');
            $table->text('description')->nullable()->comment('คำอธิบายหมวดหมู่');

            // Prompt customization
            $table->text('prompt_context')->nullable()->comment('บริบทเพิ่มเติมสำหรับหมวดหมู่นี้');
            $table->text('example_questions')->nullable()->comment('ตัวอย่างคำถามสำหรับหมวดหมู่นี้');

            // การจัดการ
            $table->boolean('is_active')->default(true)->comment('เปิด/ปิดหมวดหมู่');
            $table->integer('order')->default(0)->comment('ลำดับการแสดง');

            $table->timestamps();
            $table->softDeletes();

            // Indexes (slug unique สร้างแล้วจาก ->unique() ที่บรรทัด name)
            $table->index('is_active', 'fortune_cat_active_idx');
            $table->index('order', 'fortune_cat_order_idx');
        });
    }

    /**
     * ลบตาราง fortune_categories
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_categories');
    }
};
