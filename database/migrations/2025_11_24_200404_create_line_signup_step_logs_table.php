<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_signup_step_logs สำหรับติดตามขั้นตอนการสมัครสมาชิกผ่าน LINE
     *
     * ตารางนี้ใช้สำหรับเก็บ log การทำงานของแต่ละขั้นตอนในกระบวนการสมัครสมาชิก
     * เช่น welcome, name, email, phone, otp, password, referral, confirmation
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่ (ป้องกันการสร้างซ้ำ)
        if (Schema::hasTable('line_signup_step_logs')) {
            return;
        }

        // ตรวจสอบว่าตาราง parent (line_signup_sessions) มีอยู่หรือไม่
        if (!Schema::hasTable('line_signup_sessions')) {
            throw new \RuntimeException(
                'ตาราง line_signup_sessions ต้องมีอยู่ก่อนสร้าง line_signup_step_logs กรุณา run migration สำหรับ line_signup_sessions ก่อน'
            );
        }

        Schema::create('line_signup_step_logs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key ไปยัง line_signup_sessions
            $table->foreignId('session_id')
                ->constrained('line_signup_sessions')
                ->onDelete('cascade');

            // ชื่อขั้นตอน (welcome, name, email, phone, otp, password, referral, confirmation)
            $table->string('step_name');

            // สถานะของขั้นตอน
            $table->enum('status', ['started', 'completed', 'skipped', 'failed'])
                ->default('started');

            // ข้อมูลเพิ่มเติมของขั้นตอน (JSON)
            $table->json('step_data')->nullable();

            // ข้อความ error (ถ้ามี)
            $table->text('error_message')->nullable();

            // จำนวนครั้งที่พยายาม
            $table->integer('attempts')->default(1);

            // เวลาเริ่มต้นขั้นตอน
            $table->timestamp('started_at')->nullable();

            // เวลาที่ทำขั้นตอนเสร็จ
            $table->timestamp('completed_at')->nullable();

            // Timestamps มาตรฐาน
            $table->timestamps();

            // Indexes สำหรับการ query
            $table->index(['session_id', 'step_name'], 'line_step_logs_session_step_idx');
            $table->index('step_name', 'line_step_logs_step_name_idx');
            $table->index('status', 'line_step_logs_status_idx');
        });
    }

    /**
     * ลบตาราง line_signup_step_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_step_logs');
    }
};
