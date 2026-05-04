<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎁 (2026-05-04) ตารางบันทึกการ claim สิทธิ์ดูฟรีรายเดือน
 *
 * ทุกแถว = 1 ครั้งที่ user ลูกค้า claim สำเร็จ
 * UNIQUE(psid, platform, month_key) → กันกดซ้ำเดือนเดียวกัน
 * month_key รูปแบบ "YYYY-MM" → เดือนใหม่ = key ใหม่ = claim ได้ใหม่อัตโนมัติ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_monthly_free_claims')) {
            return;
        }

        Schema::create('fortune_monthly_free_claims', function (Blueprint $table) {
            $table->id();

            // PSID หรือ LINE userId (ขึ้นอยู่กับ platform)
            $table->string('psid', 64)->index()
                ->comment('Facebook PSID หรือ LINE userId');

            $table->string('platform', 20)->default('facebook')
                ->comment('facebook | line');

            // YYYY-MM (Asia/Bangkok timezone)
            $table->string('month_key', 7)->index()
                ->comment('คีย์เดือนรูปแบบ YYYY-MM');

            // เครดิตที่ให้ (default 1, future-proof สำหรับ tier)
            $table->unsignedTinyInteger('credits_granted')->default(1)
                ->comment('จำนวนเครดิตที่ได้จาก claim ครั้งนี้');

            // ที่มาของ claim
            $table->string('source', 30)->default('group_post')
                ->comment('group_post | direct_link | admin_grant');

            // metadata (สำหรับ audit + anti-abuse)
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('referrer', 500)->nullable();

            // เชื่อมกลับไป FortuneUserCredit ที่ได้รับเครดิตเพิ่ม
            $table->foreignId('fortune_user_credit_id')->nullable()
                ->constrained('fortune_user_credits')
                ->nullOnDelete()
                ->comment('FK → fortune_user_credits ที่ได้รับเครดิต');

            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            // กดซ้ำเดือนเดียวกันไม่ได้
            $table->unique(['psid', 'platform', 'month_key'], 'fmfc_user_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_monthly_free_claims');
    }
};
