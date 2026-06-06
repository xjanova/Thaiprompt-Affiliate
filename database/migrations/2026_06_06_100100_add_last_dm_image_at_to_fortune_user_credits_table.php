<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💬 (2026-06-06) เพิ่ม last_dm_image_at ใน fortune_user_credits
 *
 * เก็บเวลาที่ "ส่งรูปแบนเนอร์ใน DM" ให้ลูกค้าคนนี้ล่าสุด
 * → ใช้เช็คกติกา "ได้รูปสัปดาห์นี้แล้ว → ส่งข้อความชวนแทน"
 *
 * ⚠️ ทำไมต้องเก็บใน DB ไม่ใช่ Cache:
 *   auto-deploy รัน `cache:clear` ทุกครั้ง (เกือบทุกวัน) → ถ้า track ใน Cache
 *   จะถูกล้างทุก deploy = ลูกค้าได้รูปซ้ำทั้งที่เพิ่งได้ไป. DB column รอด deploy.
 *
 * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return เพราะเป็น ALTER TABLE (เพิ่มคอลัมน์)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ last_dm_image_at
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_user_credits', 'last_dm_image_at')) {
                $table->timestamp('last_dm_image_at')
                    ->nullable()
                    ->after('last_warmup_sent_at')
                    ->comment('💬 (2026-06-06) เวลาที่ส่งรูปแบนเนอร์ใน DM ล่าสุด — ใช้กันส่งรูปซ้ำในสัปดาห์เดียวกัน');
            }
        });
    }

    /**
     * ลบคอลัมน์ last_dm_image_at
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_user_credits', 'last_dm_image_at')) {
                $table->dropColumn('last_dm_image_at');
            }
        });
    }
};
