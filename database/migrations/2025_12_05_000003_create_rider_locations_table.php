<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง rider_locations สำหรับเก็บข้อมูล GPS tracking
 * เก็บเฉพาะตอนที่ไรเดอร์รับงานเท่านั้น
 */
return new class extends Migration
{
    /**
     * สร้างตาราง rider_locations
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('rider_locations')) {
            return;
        }

        Schema::create('rider_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')
                ->constrained('riders')
                ->onDelete('cascade');
            $table->foreignId('job_id')->nullable()
                ->constrained('rider_jobs')
                ->onDelete('cascade');

            // พิกัด GPS
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 10, 2)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable(); // กม./ชม.
            $table->decimal('heading', 6, 2)->nullable(); // องศา 0-360

            // ข้อมูลเพิ่มเติม
            $table->string('address')->nullable();
            $table->enum('activity_type', [
                'still',        // หยุดนิ่ง
                'walking',      // เดิน
                'running',      // วิ่ง
                'cycling',      // ปั่นจักรยาน
                'driving',      // ขับรถ
                'unknown'       // ไม่ทราบ
            ])->nullable();

            // แบตเตอรี่และอุปกรณ์
            $table->integer('battery_level')->nullable(); // 0-100
            $table->boolean('is_charging')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();

            // เวลา
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Indexes สำหรับ query ที่รวดเร็ว
            $table->index(['rider_id', 'recorded_at']);
            $table->index(['job_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']);
            $table->index('recorded_at');
        });
    }

    /**
     * ลบตาราง rider_locations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_locations');
    }
};
