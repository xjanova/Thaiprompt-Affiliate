<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🚫 (2026-05-23) ลบ 'any' จาก enum purpose ของ ai_api_keys
 *
 * User spec: "เอา purpose any ออกไปเลย — มันเป็นสาเหตุที่ทำให้ มั่วกันเยอะ"
 *
 * เหตุผล:
 *   - purpose='any' = รูรั่ว — caller ที่ specific purpose หมด/429 จะ fallback มา key 'any'
 *     (เคสจริง 2026-05-23: admin ตั้ง OpenAI ใช้แค่ Celtic 99 แต่ token ไหลไป Deep/Sensitive
 *      ผ่าน fallback `any` → quota หมดเร็ว)
 *   - ลบ 'any' ออกจาก enum → DB constraint ป้องกัน insert/update key purpose='any' ใหม่
 *
 * ขั้นตอน:
 *   1. Migrate row ที่ purpose='any' ไป NULL (กัน data ตกหล่นก่อน ALTER ENUM)
 *      → row เหล่านี้จะถูก soft-deleted แล้ว (ทำใน DB direct ก่อนหน้านี้)
 *   2. ALTER ENUM ลบ 'any' + เปลี่ยน default เป็น 'chat' (general safe default)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            Log::warning('Migration skip: ai_api_keys table not found');

            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            Log::warning('Migration skip: ENUM modify รองรับเฉพาะ MySQL/MariaDB', [
                'driver' => $driver,
            ]);

            return;
        }

        // 1. ALTER column เป็น NULLABLE ก่อน (เพื่อให้ migrate purpose='any' ไป NULL ได้)
        //    🩹 (2026-05-23 v2) column ปัจจุบันเป็น NOT NULL → update ไป NULL ไม่ได้
        //    Step 1: ALTER MODIFY ให้ nullable + เพิ่ม 'any' ใน enum ชั่วคราว (compat)
        //    Step 2: UPDATE purpose=any → NULL
        //    Step 3: ALTER MODIFY ลบ 'any' ออก enum
        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'any',
                'prediction',
                'prediction_deep',
                'prediction_celtic',
                'free_card',
                'chat',
                'chat_paid',
                'sensitive',
                'tts'
            ) NULL DEFAULT 'any'
        ");

        // 2. Migrate ทุก row purpose='any' ไป NULL (รวม soft-deleted)
        //    🩹 ต้องครอบคลุม soft-deleted rows — ไม่งั้น ALTER ENUM จะ fail
        //    "Data truncated for column 'purpose'"
        $totalCount = DB::table('ai_api_keys')->where('purpose', 'any')->count();

        if ($totalCount > 0) {
            DB::table('ai_api_keys')
                ->where('purpose', 'any')
                ->update([
                    'purpose' => null,
                    'is_active' => 0,
                    'notes' => DB::raw("CONCAT(COALESCE(notes,''), '\n[2026-05-23] migrated from purpose=any (deprecated)')"),
                    'updated_at' => now(),
                ]);

            Log::warning("Migration: migrated {$totalCount} keys from purpose='any' → NULL (รวม soft-deleted)");
        }

        // 2. เช็ค enum ปัจจุบัน
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            if ($columnInfo && stripos($columnInfo->COLUMN_TYPE, "'any'") === false) {
                Log::info('Migration skip: any ถูกลบจาก enum แล้ว');

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Migration: inspect column type ล้มเหลว — ดำเนินการ ALTER ต่อ', [
                'error' => $e->getMessage(),
            ]);
        }

        // 3. ALTER ENUM ลบ 'any' + เปลี่ยน default เป็น 'chat'
        //    (chat = general safe default ที่ใช้ได้ทั่วไป + ไม่เผา quota แพง)
        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'prediction',
                'prediction_deep',
                'prediction_celtic',
                'free_card',
                'chat',
                'chat_paid',
                'sensitive',
                'tts'
            ) NULL DEFAULT 'chat'
        ");

        Log::info('✅ Migration: ลบ any จาก enum purpose + default → chat');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // เพิ่ม 'any' กลับเข้า enum (ไม่ migrate row กลับ — admin ต้องตั้งใหม่)
        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'any',
                'prediction',
                'prediction_deep',
                'prediction_celtic',
                'free_card',
                'chat',
                'chat_paid',
                'sensitive',
                'tts'
            ) NOT NULL DEFAULT 'any'
        ");

        Log::info('🔄 Rollback: เพิ่ม any กลับเข้า enum purpose');
    }
};
