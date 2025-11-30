<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง work_shifts
     * ตารางสำหรับกำหนดกะการทำงาน (เช้า กลางวัน เย็น กลางคืน)
     */
    public function up(): void
    {
        if (Schema::hasTable('work_shifts')) {
            return;
        }

        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained()
            // เพราะ vendor_stores ถูกสร้างใน migration 2025_11_03
            $table->unsignedBigInteger('store_id')->nullable();

            // ข้อมูลกะ
            $table->string('name'); // เช่น "กะเช้า", "กะบ่าย", "กะดึก"
            $table->string('code')->unique(); // เช่น "MORNING", "AFTERNOON", "NIGHT"
            $table->text('description')->nullable();

            // เวลาเข้า-ออก
            $table->time('start_time'); // เวลาเริ่มกะ
            $table->time('end_time'); // เวลาสิ้นสุดกะ
            $table->boolean('crosses_midnight')->default(false); // กะข้ามวัน

            // การตั้งค่า
            $table->integer('break_duration_minutes')->default(60); // เวลาพัก (นาที)
            $table->time('break_start_time')->nullable(); // เวลาเริ่มพัก
            $table->time('break_end_time')->nullable(); // เวลาสิ้นสุดพัก

            // ค่าแรง
            $table->decimal('hourly_rate_multiplier', 5, 2)->default(1.00); // ตัวคูณค่าแรง (OT = 1.5)
            $table->decimal('daily_allowance', 10, 2)->default(0); // เบี้ยเลี้ยงต่อกะ

            // สี/ไอคอน สำหรับ UI
            $table->string('color')->default('#3B82F6'); // สี hex
            $table->string('icon')->nullable(); // ไอคอน

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['store_id', 'is_active']);
        });

        // เพิ่ม foreign key ถ้า vendor_stores มีอยู่แล้ว
        if (Schema::hasTable('vendor_stores')) {
            Schema::table('work_shifts', function (Blueprint $table) {
                $table->foreign('store_id')
                    ->references('id')
                    ->on('vendor_stores')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * ลบตาราง work_shifts
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
