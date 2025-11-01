<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อระดับ เช่น Bronze, Silver, Gold, Platinum, Diamond
            $table->string('name_th')->nullable(); // ชื่อภาษาไทย
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->integer('level')->unique(); // ระดับ 1, 2, 3, ... (เรียงจากต่ำไปสูง)
            $table->string('icon')->nullable(); // URL หรือ path ของไอคอน
            $table->string('color')->default('#3B82F6'); // สีประจำระดับ (hex code)
            $table->string('badge_icon')->nullable(); // ไอคอนตรา/เหรียญ

            // ผลตอบแทน
            $table->decimal('commission_rate', 5, 2)->default(0); // % commission เพิ่มเติม
            $table->decimal('bonus_multiplier', 5, 2)->default(1.00); // ตัวคูณโบนัส

            // Requirements (สำหรับแสดงเบื้องต้น - detail ใน rank_requirements)
            $table->integer('min_points')->default(0); // คะแนนขั้นต่ำ
            $table->integer('min_referrals')->default(0); // จำนวนคนแนะนำขั้นต่ำ
            $table->decimal('min_sales', 12, 2)->default(0); // ยอดขายขั้นต่ำ

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // ระดับเริ่มต้นสำหรับ user ใหม่
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
