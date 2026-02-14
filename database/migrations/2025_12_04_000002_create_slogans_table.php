<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง slogans
 *
 * ใช้เก็บคำขวัญ/คำคมสำหรับแสดงใน Seller Dashboard
 * และหน้าอื่นๆ ของระบบ
 */
return new class extends Migration
{
    /**
     * สร้างตาราง slogans
     */
    public function up(): void
    {
        if (Schema::hasTable('slogans')) {
            return;
        }

        Schema::create('slogans', function (Blueprint $table) {
            $table->id();

            // เนื้อหาคำขวัญ
            $table->text('content');

            // ผู้แต่ง/แหล่งที่มา (ถ้ามี)
            $table->string('author')->nullable();

            // หมวดหมู่คำขวัญ
            $table->string('category')->default('general');

            // ไอคอน Emoji สำหรับแสดง
            $table->string('icon', 10)->default('💡');

            // สถานะการใช้งาน
            $table->boolean('is_active')->default(true);

            // ลำดับความสำคัญ (สูง = แสดงบ่อยกว่า)
            $table->integer('priority')->default(1);

            // จำนวนครั้งที่แสดง (สำหรับ analytics)
            $table->unsignedBigInteger('display_count')->default(0);

            // แสดงเฉพาะบาง role (null = ทุก role)
            $table->json('roles')->nullable();

            // แสดงในช่วงเวลาที่กำหนด
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * ลบตาราง slogans
     */
    public function down(): void
    {
        Schema::dropIfExists('slogans');
    }
};
