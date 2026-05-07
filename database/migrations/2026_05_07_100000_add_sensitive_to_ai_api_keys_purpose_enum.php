<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-07) เพิ่มค่า 'sensitive' ใน enum purpose ของ ai_api_keys
 *
 * วัตถุประสงค์:
 *   - ให้ admin มาร์ค key ของ Pro model (Gemini Pro / GPT-5+) ว่าใช้สำหรับ
 *     เคสบริบทละเอียดอ่อน (ลูกค้าอารมณ์ร้าย / คำถามซับซ้อน / โทนหนัก)
 *   - FortuneSensitivityDetector ตรวจพบ → acquireKey($provider, 'sensitive')
 *     จะดึง key ที่ purpose='sensitive' มาใช้แทน chat/prediction ปกติ
 *   - hierarchy: 'sensitive' = STRICT (ไม่ fallback ไป any/prediction) เพื่อกัน
 *     burn budget โดยไม่ตั้งใจ — caller ต้องเช็ค null + fallback เอง
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

        // เช็ค enum มี sensitive แล้วหรือยัง (idempotent)
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            if ($columnInfo && stripos($columnInfo->COLUMN_TYPE, 'sensitive') !== false) {
                Log::info('Migration skip: sensitive มีอยู่ใน enum แล้ว');

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
                'free_card',
                'chat',
                'sensitive'
            ) NOT NULL DEFAULT 'any'
        ");

        Log::info('✅ Migration: เพิ่ม sensitive ใน enum purpose ของ ai_api_keys');
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

        // ห้าม rollback ถ้ามี row ใช้ sensitive
        $usedSensitive = DB::table('ai_api_keys')
            ->where('purpose', 'sensitive')
            ->exists();

        if ($usedSensitive) {
            Log::warning('Migration rollback skip: มี row ใช้ sensitive อยู่ — ห้าม rollback');

            return;
        }

        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'any',
                'prediction',
                'free_card',
                'chat'
            ) NOT NULL DEFAULT 'any'
        ");
    }
};
