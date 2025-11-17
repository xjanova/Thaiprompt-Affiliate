<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_signup_sessions
     *
     * ตารางนี้ใช้เก็บข้อมูล session การสมัครสมาชิกผ่าน LINE
     * รองรับการสมัครแบบ multi-step conversation
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('line_signup_sessions')) {
            return;
        }

        Schema::create('line_signup_sessions', function (Blueprint $table) {
            $table->id();

            // ข้อมูล LINE User
            $table->string('line_user_id')->unique()->comment('LINE User ID');
            $table->string('session_token', 100)->unique()->comment('Unique session token');

            // Progress Tracking
            $table->string('current_step')->default('welcome')->comment('ขั้นตอนปัจจุบัน');
            $table->json('collected_data')->nullable()->comment('ข้อมูลที่รวบรวมได้ (JSON)');

            // OTP Verification
            $table->string('otp_code', 10)->nullable()->comment('OTP code');
            $table->timestamp('otp_expires_at')->nullable()->comment('OTP expiry time');
            $table->unsignedInteger('otp_attempts')->default(0)->comment('จำนวนครั้งที่พยายามใส่ OTP');

            // Status
            $table->enum('status', ['active', 'completed', 'abandoned', 'expired'])
                ->default('active')
                ->comment('สถานะของ session');
            $table->string('language', 10)->default('th')->comment('ภาษาที่ใช้');

            // Relations
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User ID เมื่อสมัครสำเร็จ');

            $table->foreignId('affiliate_id')
                ->nullable()
                ->constrained('affiliates')
                ->nullOnDelete()
                ->comment('Affiliate ที่ชวน (ถ้ามี)');

            // Timestamps
            $table->timestamp('started_at')->nullable()->comment('เวลาเริ่ม session');
            $table->timestamp('completed_at')->nullable()->comment('เวลาสมัครสำเร็จ');
            $table->timestamp('last_activity_at')->nullable()->comment('เวลา activity ล่าสุด');
            $table->timestamps();

            // Indexes
            $table->index('line_user_id', 'line_sessions_line_user_idx');
            $table->index('status', 'line_sessions_status_idx');
            $table->index('current_step', 'line_sessions_step_idx');
            $table->index('last_activity_at', 'line_sessions_activity_idx');
        });
    }

    /**
     * ลบตาราง line_signup_sessions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_sessions');
    }
};
