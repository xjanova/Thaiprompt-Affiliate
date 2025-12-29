<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง line_signup_invitations ที่หายไป
     *
     * ตารางนี้ใช้สำหรับเก็บข้อมูล invitation links สำหรับการสมัครสมาชิกผ่าน LINE
     * - เก็บ token สำหรับแชร์ลิงก์เชิญ
     * - ติดตามจำนวนครั้งที่ใช้งาน
     * - รองรับ referral system
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('line_signup_invitations')) {
            return;
        }

        Schema::create('line_signup_invitations', function (Blueprint $table) {
            $table->id();

            // Foreign key - ผู้เชิญ (เจ้าของ invitation)
            $table->foreignId('inviter_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Token และ referral code
            $table->string('invitation_token')->unique();
            $table->string('line_user_id')->nullable(); // Target LINE user if known
            $table->string('referral_code')->index();

            // การใช้งาน
            $table->integer('max_uses')->default(1);
            $table->integer('uses_count')->default(0);

            // สถานะ: active, used, expired
            $table->enum('status', ['active', 'used', 'expired'])->default('active');

            // ข้อมูลเพิ่มเติม
            $table->json('metadata')->nullable(); // Campaign info, source, etc.

            // วันหมดอายุและวันใช้งานครั้งแรก
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('first_used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['invitation_token', 'status'], 'line_inv_token_status_idx');
            $table->index('referral_code', 'line_inv_referral_idx');
        });
    }

    /**
     * ลบตาราง line_signup_invitations
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_invitations');
    }
};
