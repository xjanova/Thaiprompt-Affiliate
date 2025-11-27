<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_auto_settings
     * เก็บการตั้งค่าระบบ Video Automation รวมถึง API keys
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_auto_settings')) {
            return;
        }

        Schema::create('video_auto_settings', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('key')->unique()->comment('ชื่อ setting key');
            $table->text('value')->nullable()->comment('ค่าของ setting');
            $table->string('type')->default('string')->comment('ประเภทข้อมูล: string, json, boolean, integer, encrypted');
            $table->string('group')->default('general')->index()->comment('กลุ่ม: general, suno, freepik, youtube, social');
            $table->string('label')->nullable()->comment('ป้ายกำกับแสดงใน UI');
            $table->text('description')->nullable()->comment('คำอธิบายการตั้งค่า');

            // ค่าเริ่มต้นและการตรวจสอบ
            $table->text('default_value')->nullable()->comment('ค่าเริ่มต้น');
            $table->boolean('is_encrypted')->default(false)->comment('เข้ารหัสค่าหรือไม่');
            $table->boolean('is_required')->default(false)->comment('จำเป็นต้องกรอกหรือไม่');
            $table->json('validation_rules')->nullable()->comment('กฎการตรวจสอบ');

            // การจัดการ
            $table->integer('sort_order')->default(0)->comment('ลำดับการแสดง');
            $table->boolean('is_active')->default(true)->index()->comment('เปิดใช้งานหรือไม่');

            $table->timestamps();

            // Indexes
            $table->index(['group', 'sort_order'], 'vas_group_sort_idx');
        });
    }

    /**
     * ลบตาราง video_auto_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_auto_settings');
    }
};
