<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🌙 (2026-05-23) ปิดระบบดูดวง 39฿ (Deep) — เหลือแต่ Celtic 99฿
 *
 * User spec: "ตอนนี้ให้ปิดระบบการทำนายด้วย 39 บาทไปก่อน เหลือแต่ 99"
 *
 * Why migration (ไม่ใช่แค่กดที่ admin UI):
 *   - ลูกค้า user spec บอก "ปรับปรุงอย่าหยุด" → ทำผ่าน deploy แทนเข้า admin
 *   - เปลี่ยน flag ใน DB → ทำงานทันทีหลัง deploy
 *   - Default ใน model ก็เปลี่ยนเป็น false แล้ว (กัน install ใหม่)
 *
 * Effect:
 *   - isDeepReadingEnabled() returns false ทุกที่ (~30 call sites)
 *   - Tier menu ไม่ขึ้นปุ่ม 39฿ (ดูที่ FortuneChannelManager)
 *   - Fallback message redirect ไป Celtic 99฿
 *
 * Reversible:
 *   - down() restore enable_deep_reading = true
 *   - หรือ admin กด toggle ใน /admin/fortune/settings (priority สูงกว่า migration)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return; // ไม่มีตาราง — ข้าม (fresh install จะใช้ model default = false)
        }

        if (! Schema::hasColumn('fortune_telling_settings', 'enable_deep_reading')) {
            return; // column ยังไม่มี — ข้าม (migration เก่ายังไม่รัน)
        }

        DB::table('fortune_telling_settings')->update([
            'enable_deep_reading' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        if (! Schema::hasColumn('fortune_telling_settings', 'enable_deep_reading')) {
            return;
        }

        DB::table('fortune_telling_settings')->update([
            'enable_deep_reading' => true,
            'updated_at' => now(),
        ]);
    }
};
