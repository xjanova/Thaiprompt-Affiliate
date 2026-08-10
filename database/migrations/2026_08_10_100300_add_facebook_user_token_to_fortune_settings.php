<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔑 (2026-08-10) เก็บ User Access Token ของเจ้าของเพจ (ใส่ครั้งเดียว)
 *
 * เจ้าของสั่ง: "เพียงแต่นำไอดีเพจมาเพิ่ม พอ ไม่ต้องทำอะไรซับซ้อน เราใช้ของเราเอง"
 *
 * ข้อจำกัดจริงของ Facebook: การส่งข้อความต้องใช้ **Page Access Token ของเพจนั้นเท่านั้น**
 * token ของเพจ A ยิงเข้าเพจ B ไม่ได้ → เลี่ยงไม่ได้
 *
 * ทางออกที่ไม่ต้องก็อป token ทีละเพจ:
 *   เก็บ User Token ของเจ้าของไว้ 1 ตัว → ระบบยิง /me/accounts เอา
 *   "ชื่อเพจ + page token" ของทุกเพจที่เจ้าของดูแลมาให้เอง
 *   → เพิ่มเพจใหม่ = ใส่แค่ไอดีเพจ (หรือกดเลือกจากรายการ)
 *
 * ⚠️ ทำได้เพราะทุกเพจเป็นของเจ้าของคนเดียวกัน — ถ้าเป็นเพจของคนอื่นต้องก็อป token มาเอง
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ facebook_user_token
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'facebook_user_token')) {
                // text เพราะ token ยาวเกิน 255 และเก็บแบบเข้ารหัส (ยาวขึ้นอีกเท่าตัว)
                $table->text('facebook_user_token')->nullable();
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'facebook_user_token_checked_at')) {
                $table->timestamp('facebook_user_token_checked_at')->nullable();
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['facebook_user_token', 'facebook_user_token_checked_at'] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
