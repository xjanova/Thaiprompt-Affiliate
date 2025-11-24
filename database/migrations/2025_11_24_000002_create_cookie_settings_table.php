<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง cookie_settings สำหรับเก็บการตั้งค่าคุกกี้
     *
     * ตารางนี้ใช้เก็บการตั้งค่าต่างๆ สำหรับระบบคุกกี้ เช่น:
     * - cookie_banner_enabled: เปิด/ปิดแบนเนอร์
     * - cookie_banner_title: หัวข้อแบนเนอร์
     * - cookie_banner_description: คำอธิบาย
     * - cookie_policy_url: URL ไปยังหน้านโยบายคุกกี้
     *
     * @return void
     */
    public function up(): void
    {
        // เช็คว่าตารางมีอยู่แล้วหรือยัง
        if (Schema::hasTable('cookie_settings')) {
            return;
        }

        Schema::create('cookie_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, boolean, json, integer
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * ลบตาราง cookie_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cookie_settings');
    }
};
