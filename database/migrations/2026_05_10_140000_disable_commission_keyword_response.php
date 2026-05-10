<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🛑 (2026-05-10) ปิด keyword response "ระบบแนะนำเพื่อนดูดวง" ชั่วคราว
 *
 * เหตุผล: user ขอเอากล่องข้อความนี้ออกก่อน
 *   trigger words: commission, คอมมิชชั่น, earning, รายได้, ได้เงิน, ค่าแนะนำ, แชร์รายได้
 *   → ทุกครั้งที่ลูกค้าพิมพ์คำเหล่านี้ → บอทตอบกล่อง "🎁 ระบบแนะนำเพื่อนดูดวง"
 *
 * วิธีกลับคืน: ไปที่ /admin/line-bot/keywords/ → toggle keyword "commission" กลับเป็น active
 *   หรือ: UPDATE line_bot_keywords SET is_active = 1 WHERE keyword = 'commission';
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        DB::table('line_bot_keywords')
            ->where('keyword', 'commission')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        DB::table('line_bot_keywords')
            ->where('keyword', 'commission')
            ->update(['is_active' => true]);
    }
};
