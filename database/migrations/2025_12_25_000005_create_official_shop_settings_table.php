<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง official_shop_settings
     *
     * ตารางนี้เก็บการตั้งค่าต่างๆ สำหรับระบบ Official Shop
     * เช่น คะแนนขั้นต่ำ AI Selection, จำนวนสินค้าขายดี, น้ำหนักคะแนน, ฯลฯ
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('official_shop_settings')) {
            return;
        }

        Schema::create('official_shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('คีย์การตั้งค่า');
            $table->text('value')->nullable()->comment('ค่าการตั้งค่า');
            $table->string('type', 50)->default('string')->comment('ประเภทข้อมูล (string, integer, boolean, json)');
            $table->text('description')->nullable()->comment('คำอธิบายการตั้งค่า');
            $table->timestamps();

            // Index
            $table->index('key');
        });
    }

    /**
     * ลบตาราง official_shop_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('official_shop_settings');
    }
};
