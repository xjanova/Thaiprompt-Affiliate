<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แก้ไขปัญหาตาราง membership_retention_history ที่หายไป
 *
 * ตารางนี้ใช้เก็บประวัติการรักษายอดของสมาชิกในแต่ละเดือน
 * เป็นส่วนหนึ่งของระบบ Membership Retention System
 *
 * @see App\Services\MembershipRetentionService
 * @see App\Models\MembershipRetentionHistory
 */
return new class extends Migration
{
    /**
     * สร้างตาราง membership_retention_history ถ้ายังไม่มี
     */
    public function up(): void
    {
        // ตรวจสอบก่อนสร้าง - ป้องกันการสร้างซ้ำ
        if (Schema::hasTable('membership_retention_history')) {
            return;
        }

        Schema::create('membership_retention_history', function (Blueprint $table) {
            $table->id();

            // Foreign key ไปยังตาราง users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ข้อมูลรอบเดือน
            $table->string('period_month', 7); // รูปแบบ YYYY-MM
            $table->date('period_start'); // วันเริ่มต้นรอบ
            $table->date('period_end'); // วันสิ้นสุดรอบ

            // ข้อมูลแต้ม
            $table->decimal('required_points', 10, 2); // แต้มที่ต้องรักษา
            $table->decimal('earned_points', 10, 2)->default(0); // แต้มที่ได้รับ

            // สถานะ
            $table->enum('status', ['pending', 'achieved', 'failed'])->default('pending');
            $table->date('achieved_at')->nullable(); // วันที่บรรลุเป้า

            // หมายเหตุ
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes สำหรับการค้นหาที่รวดเร็ว
            $table->index(['user_id', 'period_month'], 'mrh_user_period_idx');
            $table->index('status', 'mrh_status_idx');
        });
    }

    /**
     * ลบตาราง membership_retention_history
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_retention_history');
    }
};
