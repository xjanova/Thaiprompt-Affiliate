<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_signup_settings
     *
     * เก็บการตั้งค่าต่างๆ สำหรับระบบสมัครสมาชิกผ่าน LINE
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('line_signup_settings')) {
            return;
        }

        Schema::create('line_signup_settings', function (Blueprint $table) {
            $table->id();

            // Setting key-value pair
            $table->string('key', 100)->unique()->comment('คีย์การตั้งค่า');
            $table->text('value')->nullable()->comment('ค่าการตั้งค่า (JSON or string)');
            $table->string('type', 50)->default('string')->comment('ประเภทข้อมูล: string, boolean, integer, json');
            $table->string('group', 50)->default('general')->comment('กลุ่มการตั้งค่า: signup, otp, validation, rewards, gamification, integration');
            $table->text('description')->nullable()->comment('คำอธิบายการตั้งค่า');

            // Meta data
            $table->boolean('is_public')->default(false)->comment('แสดงในหน้าสาธารณะได้หรือไม่');
            $table->boolean('is_editable')->default(true)->comment('แก้ไขได้หรือไม่');

            $table->timestamps();

            // Indexes
            $table->index('key');
            $table->index('group');
        });
    }

    /**
     * ลบตาราง line_signup_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_settings');
    }
};
