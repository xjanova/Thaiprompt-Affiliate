<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง mlm_rebuild_tasks สำหรับติดตามความคืบหน้าการ rebuild tree
 *
 * ใช้สำหรับ:
 * - ติดตาม progress ของการ rebuild Unilevel tree จาก Genealogy
 * - รองรับการ resume หากหน้าเว็บปิดไป
 * - ป้องกันการทำงานซ้ำซ้อน
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('mlm_rebuild_tasks')) {
            return;
        }

        Schema::create('mlm_rebuild_tasks', function (Blueprint $table) {
            $table->id();

            // ประเภทของ task
            $table->string('type', 50)->default('unilevel_rebuild');
            // เช่น: unilevel_rebuild, binary_rebuild, full_recalculate

            // สถานะ
            $table->enum('status', [
                'pending',      // รอเริ่ม
                'running',      // กำลังทำงาน
                'completed',    // เสร็จสมบูรณ์
                'failed',       // ล้มเหลว
                'cancelled',    // ถูกยกเลิก
            ])->default('pending');

            // Progress tracking
            $table->unsignedInteger('total_items')->default(0);        // จำนวนสมาชิกทั้งหมด
            $table->unsignedInteger('processed_items')->default(0);    // จำนวนที่ประมวลผลแล้ว
            $table->unsignedInteger('failed_items')->default(0);       // จำนวนที่ล้มเหลว
            $table->decimal('progress_percentage', 5, 2)->default(0);  // % ความคืบหน้า

            // ข้อมูล configuration ที่ใช้ rebuild
            $table->json('config')->nullable();
            // เช่น: {"unilevel_max_width": 3, "unilevel_max_depth": 10, "trigger": "width_change"}

            // Error tracking
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // ใครเริ่ม task
            $table->foreignId('initiated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Last processed member (สำหรับ resume)
            $table->unsignedBigInteger('last_processed_id')->nullable();

            // Lock สำหรับป้องกัน concurrent execution
            $table->string('lock_key', 100)->nullable()->unique();
            $table->timestamp('lock_expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_rebuild_tasks');
    }
};
