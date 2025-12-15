<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง line_registration_sessions
 *
 * ใช้สำหรับ tracking session ของหน้าเว็บ
 * เมื่อผู้ใช้เปิดหน้าสมัครสมาชิก จะสร้าง session token
 * หลังจากสมัครผ่าน LINE OA เสร็จ จะอัพเดท status
 * หน้าเว็บจะ polling เช็ค status และ redirect ไปหน้าอัพเดทข้อมูลอัตโนมัติ
 */
return new class extends Migration
{
    /**
     * สร้างตาราง line_registration_sessions
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('line_registration_sessions')) {
            return;
        }

        Schema::create('line_registration_sessions', function (Blueprint $table) {
            $table->id();

            // Session token สำหรับ tracking (ไม่ซ้ำ)
            $table->string('session_token', 64)->unique();

            // ข้อมูล referral/sponsor (ถ้ามี)
            $table->string('referral_code')->nullable()->index();
            $table->unsignedBigInteger('sponsor_user_id')->nullable();
            $table->unsignedBigInteger('sponsor_mlm_member_id')->nullable();

            // LINE User ID ที่ได้หลังจาก follow/login
            $table->string('line_user_id')->nullable()->index();

            // สถานะของ session
            // - pending: รอผู้ใช้เพิ่มเพื่อน LINE OA
            // - followed: เพิ่มเพื่อนแล้ว รอสมัคร
            // - in_progress: กำลังสมัครสมาชิก
            // - completed: สมัครเสร็จสิ้น
            // - expired: หมดอายุ
            // - cancelled: ยกเลิก
            $table->enum('status', [
                'pending',
                'followed',
                'in_progress',
                'completed',
                'expired',
                'cancelled',
            ])->default('pending');

            // User ID หลังจากสมัครเสร็จ
            $table->unsignedBigInteger('user_id')->nullable();

            // ข้อมูลเพิ่มเติม (เก็บเป็น JSON)
            $table->json('metadata')->nullable();

            // IP และ User Agent สำหรับ security
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // วันที่หมดอายุ (default 24 ชั่วโมง)
            $table->timestamp('expires_at')->nullable();

            // วันที่เพิ่มเพื่อน LINE OA
            $table->timestamp('followed_at')->nullable();

            // วันที่สมัครเสร็จ
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * ลบตาราง line_registration_sessions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_registration_sessions');
    }
};
