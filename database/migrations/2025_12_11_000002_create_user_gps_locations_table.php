<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: สร้างตาราง user_gps_locations
 *
 * สำหรับเก็บตำแหน่ง GPS ที่ผู้ใช้แชร์ให้ Admin ดูใน GPS Monitor
 */
return new class extends Migration
{
    /**
     * สร้างตาราง user_gps_locations
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('user_gps_locations')) {
            return;
        }

        Schema::create('user_gps_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // ตำแหน่ง GPS
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 10, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable(); // km/h
            $table->decimal('heading', 5, 2)->nullable(); // 0-360 degrees

            // ข้อมูลอุปกรณ์
            $table->integer('battery_level')->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('os_version', 50)->nullable();

            // สถานะ
            $table->boolean('is_sharing')->default(false);
            $table->timestamp('last_update')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('is_sharing');
            $table->index('last_update');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('user_gps_locations');
    }
};
