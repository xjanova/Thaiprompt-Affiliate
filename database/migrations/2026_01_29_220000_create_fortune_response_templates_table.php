<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_response_templates
     * เก็บเทมเพลตการตอบกลับคำทำนาย (รองรับรูปภาพ + placeholders)
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_response_templates')) {
            return;
        }

        Schema::create('fortune_response_templates', function (Blueprint $table) {
            $table->id();

            // ข้อมูลหลัก
            $table->string('name', 100)->comment('ชื่อเทมเพลต');
            $table->string('slug', 100)->unique()->comment('Key สำหรับอ้างอิง');
            $table->enum('type', ['basic', 'deep', 'welcome', 'payment', 'limit_exceeded', 'error'])
                ->default('basic')
                ->comment('ประเภทเทมเพลต');

            // เนื้อหาเทมเพลต
            $table->text('body')->comment('เนื้อหาเทมเพลต (รองรับ placeholders เช่น {response}, {user_name})');
            $table->string('header_text', 500)->nullable()->comment('ข้อความส่วนหัว (ก่อนคำทำนาย)');
            $table->string('footer_text', 500)->nullable()->comment('ข้อความส่วนท้าย (หลังคำทำนาย)');

            // รูปภาพ
            $table->string('header_image_url', 500)->nullable()->comment('URL รูปส่วนหัว (ส่งก่อนข้อความ)');
            $table->string('footer_image_url', 500)->nullable()->comment('URL รูปส่วนท้าย เช่น QR Code ชำระเงิน');

            // Placeholders ที่ใช้ได้ใน body (JSON array)
            $table->json('available_placeholders')->nullable()
                ->comment('รายการ placeholders ที่ใช้ได้ (เช่น {response}, {user_name})');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->boolean('is_default')->default(false)->comment('เป็นเทมเพลตเริ่มต้นของประเภทนั้น');
            $table->unsignedInteger('sort_order')->default(0)->comment('ลำดับการแสดง');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'is_active'], 'fortune_tpl_type_active_idx');
            $table->index(['type', 'is_default'], 'fortune_tpl_type_default_idx');
        });
    }

    /**
     * ลบตาราง fortune_response_templates
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_response_templates');
    }
};
