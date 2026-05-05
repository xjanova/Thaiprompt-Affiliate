<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 👥 (2026-05-05) เพิ่มคอลัมน์ facebook_group_clicked_at ใน fortune_user_credits
 *
 * บันทึกเวลาที่ user คลิกปุ่ม "เข้ากลุ่มแม่หมอจันทรา" จาก Messenger button template
 *   → tracking redirect ที่ /fortune/track/fb-group/{psid}
 *   → admin วิเคราะห์ funnel + skip ส่งซ้ำในกลุ่มที่ user เข้าแล้ว
 *
 * ใช้ SafeMigration trait — idempotent (ไม่ fail ถ้ารันซ้ำ)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_user_credits', 'facebook_group_clicked_at', function ($t) {
                $t->timestamp('facebook_group_clicked_at')
                    ->nullable()
                    ->after('facebook_followed_confirmed_at')
                    ->comment('เวลาที่ user คลิกปุ่มเข้ากลุ่ม (tracking redirect)');
            });
        });

        $this->safeAddIndex('fortune_user_credits', 'facebook_group_clicked_at');
    }

    public function down(): void
    {
        $this->safeDropIndex('fortune_user_credits', 'fortune_user_credits_facebook_group_clicked_at_index');
        $this->safeDropColumn('fortune_user_credits', ['facebook_group_clicked_at']);
    }
};
