<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง game_settings สำหรับเก็บการตั้งค่าเกม
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('game_settings')) {
            return;
        }

        Schema::create('game_settings', function (Blueprint $table) {
            $table->id();

            // ชื่อ setting (unique key)
            $table->string('key', 100)->unique();

            // ค่าของ setting (รองรับข้อความยาว)
            $table->text('value')->nullable();

            // ประเภทข้อมูล (string, integer, boolean, json)
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');

            // กลุ่มของ setting
            $table->string('group', 50)->default('general')->index();

            // คำอธิบาย
            $table->string('description')->nullable();

            // สถานะการใช้งาน
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['key', 'is_active']);
            // หมายเหตุ: index สำหรับ 'group' ถูกสร้างแล้วที่บรรทัด 34 (.index())
        });
    }

    /**
     * ลบตาราง game_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('game_settings');
    }
};
