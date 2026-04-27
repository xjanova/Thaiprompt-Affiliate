<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม follow-tracking columns สำหรับเช็คว่า user กดติดตามเพจหรือยัง
     *
     * - facebook_follow_prompted_at: ครั้งล่าสุดที่ส่งกล่อง "ติดตามเพจ" ให้ user
     *   (ใช้ throttle ไม่ให้สแปม — cooldown 7 วัน)
     * - facebook_followed_confirmed_at: เมื่อ user คลิก postback "✅ ติดตามแล้ว"
     *   (ครั้งเดียวจบ — ไม่ส่งกล่องนี้ให้อีก)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_user_credits', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_user_credits', 'facebook_follow_prompted_at', function ($table) {
                $table->timestamp('facebook_follow_prompted_at')->nullable()->after('last_warmup_sent_at');
            });

            $this->safeAddColumn($table, 'fortune_user_credits', 'facebook_followed_confirmed_at', function ($table) {
                $table->timestamp('facebook_followed_confirmed_at')->nullable()->after('facebook_follow_prompted_at');
            });
        });
    }

    /**
     * ลบ columns ที่เพิ่ม
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_user_credits', ['facebook_follow_prompted_at', 'facebook_followed_confirmed_at']);
    }
};
