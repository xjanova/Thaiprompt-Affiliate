<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💬 (2026-06-06) เพิ่ม toggle enable_invite_rotation
 *
 * เปิด/ปิดฟีเจอร์ "ได้รูปสัปดาห์นี้แล้ว → ส่งข้อความชวนแบบสุ่มแทนรูป"
 *
 *   - Default: true (เปิด — ตาม USER SPEC)
 *   - เมื่อ true: ลูกค้าที่ได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว → DM กลับครั้งถัดไป
 *                ส่งเป็นข้อความเชิญชวนสุ่มจาก fortune_invite_messages (ไม่ส่งรูปซ้ำ)
 *   - เมื่อ false: คงพฤติกรรมเดิม (ส่งรูปแบนเนอร์ทุกครั้งตาม cooldown 24 ชม.)
 *
 * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return เพราะเป็น ALTER TABLE (เพิ่มคอลัมน์)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ enable_invite_rotation
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_invite_rotation')) {
                $table->boolean('enable_invite_rotation')
                    ->default(true)
                    ->after('enable_public_comment_reply')
                    ->comment('💬 (2026-06-06) เปิดระบบสุ่มข้อความชวนแทนรูป เมื่อลูกค้าได้รูปในสัปดาห์นี้แล้ว');
            }
        });
    }

    /**
     * ลบคอลัมน์ enable_invite_rotation
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_invite_rotation')) {
                $table->dropColumn('enable_invite_rotation');
            }
        });
    }
};
