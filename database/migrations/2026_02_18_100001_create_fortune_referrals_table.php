<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_referrals
 *
 * ติดตามว่าใครเชิญใครมาใช้บริการดูดวง
 * เมื่อเพื่อนที่ถูกเชิญจ่ายค่าดูดวง → สร้าง MlmMember ใต้คนเชิญ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_referrals')) {
            return;
        }

        Schema::create('fortune_referrals', function (Blueprint $table) {
            $table->id();

            // Token ไม่ซ้ำสำหรับลิงก์เชิญเพื่อน
            $table->string('referral_token', 64)->unique();

            // คนเชิญ
            $table->foreignId('referrer_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->unsignedBigInteger('referrer_mlm_member_id')->nullable();

            // เพื่อนที่ถูกเชิญ (เติมเมื่อแอดเพื่อน LINE OA)
            $table->string('referred_line_user_id')->nullable()->index();

            // User ที่สร้างจากเพื่อน (เติมเมื่อจ่ายเงินดูดวง)
            $table->unsignedBigInteger('referred_user_id')->nullable();

            // สถานะ
            $table->string('status', 20)->default('pending')->index();
            // pending = สร้างลิงก์แล้ว รอเพื่อนกดแอด
            // followed = เพื่อนแอด LINE OA แล้ว
            // converted = เพื่อนจ่ายเงินดูดวง + สมัครสมาชิกแล้ว
            // expired = หมดอายุ

            // Timestamps ต่างๆ
            $table->timestamp('followed_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            // ข้อมูล tracking
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // หมดอายุ (30 วัน)
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();

            // Indexes
            $table->index('referrer_user_id');
            $table->index(['status', 'referred_line_user_id']);
        });

        // เพิ่ม foreign key แยก (กัน error ถ้าตารางยังไม่มี)
        if (Schema::hasTable('mlm_members')) {
            Schema::table('fortune_referrals', function (Blueprint $table) {
                $table->foreign('referrer_mlm_member_id')
                    ->references('id')
                    ->on('mlm_members')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('fortune_referrals', function (Blueprint $table) {
                $table->foreign('referred_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_referrals');
    }
};
