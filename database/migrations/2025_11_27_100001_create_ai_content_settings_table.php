<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_settings
 *
 * เก็บการตั้งค่า API Keys และ Configuration ต่างๆ
 * สำหรับระบบ AI Content Writer
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * สร้างตาราง ai_content_settings สำหรับเก็บการตั้งค่า
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('ai_content_settings')) {
            return;
        }

        Schema::create('ai_content_settings', function (Blueprint $table) {
            $table->id();

            // กลุ่มการตั้งค่า
            $table->string('group', 50)->default('general');

            // Key-Value
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();

            // ชนิดข้อมูล (string, integer, boolean, json, encrypted)
            $table->string('type', 20)->default('string');

            // คำอธิบาย
            $table->string('label')->nullable();
            $table->text('description')->nullable();

            // การเรียงลำดับ
            $table->integer('sort_order')->default(0);

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['group', 'sort_order'], 'acs_group_sort_idx');
            $table->index('is_active', 'acs_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_settings');
    }
};
