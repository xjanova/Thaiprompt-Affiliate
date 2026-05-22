<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-23) เพิ่มค่า 'chat_paid' ใน enum purpose
 *
 * วัตถุประสงค์:
 *   - คีย์พิเศษ chat แบบจ่ายเงิน (badge สีฟ้า ในแอดมิน) — last resort
 *   - Pool ใช้เป็น Tier 3 fallback หลัง chat (free) + any ตายหมด
 *   - STRICT scope — caller=null ไม่ใช้ (เผา quota paid), caller='chat'
 *     เท่านั้นที่จะถึง chat_paid และเฉพาะตอน free pool หมด
 *
 * Fallback chain สำหรับ caller='chat':
 *   Tier 0 = purpose='chat'      (FREE — exact match)
 *   Tier 1 = purpose='any'       (general backup)
 *   Tier 2 = purpose=null/legacy (legacy)
 *   Tier 3 = purpose='chat_paid' (LAST RESORT — paid chat สีฟ้า)
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

        // เช็ค enum มี chat_paid แล้วหรือยัง (idempotent)
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            if ($columnInfo && stripos($columnInfo->COLUMN_TYPE, "'chat_paid'") !== false) {
                Log::info('Migration skip: chat_paid มีอยู่ใน enum แล้ว');

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Migration: inspect column type ล้มเหลว — ดำเนินการ ALTER ต่อ', [
                'error' => $e->getMessage(),
            ]);
        }

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

        Log::info('✅ Migration: เพิ่ม chat_paid ใน enum purpose ของ ai_api_keys');
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

        // ห้าม rollback ถ้ามี row ใช้ chat_paid
        $usedNew = DB::table('ai_api_keys')->where('purpose', 'chat_paid')->exists();
        if ($usedNew) {
            Log::warning('Migration rollback skip: มี row ใช้ chat_paid อยู่');

            return;
        }

        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'any',
                'prediction',
                'prediction_deep',
                'prediction_celtic',
                'free_card',
                'chat',
                'sensitive',
                'tts'
            ) NOT NULL DEFAULT 'any'
        ");
    }
};
