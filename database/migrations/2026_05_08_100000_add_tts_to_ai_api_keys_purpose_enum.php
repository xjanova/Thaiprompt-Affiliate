<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎙️ (2026-05-08) เพิ่มค่า 'tts' ใน enum purpose ของ ai_api_keys
 *
 * วัตถุประสงค์:
 *   - ให้ admin มาร์ค key ของ MiniMax / OpenAI TTS ว่าใช้สำหรับสังเคราะห์เสียง
 *   - FortuneVoiceSummaryService::resolveProvider() จะ acquireKey($provider, 'tts')
 *     หา key ที่ purpose='tts' มาใช้
 *   - hierarchy: 'tts' = STRICT (ไม่ fallback ไป any/prediction) เพราะ TTS API
 *     คนละ schema — ใช้ key prediction มา synth เสียงจะ fail ทั้งหมด
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

        // เช็ค enum มี tts แล้วหรือยัง (idempotent)
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            if ($columnInfo && stripos($columnInfo->COLUMN_TYPE, "'tts'") !== false) {
                Log::info('Migration skip: tts มีอยู่ใน enum แล้ว');

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
                'sensitive',
                'tts'
            ) NOT NULL DEFAULT 'any'
        ");

        Log::info('✅ Migration: เพิ่ม tts ใน enum purpose ของ ai_api_keys');
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

        // ห้าม rollback ถ้ามี row ใช้ tts
        $usedTts = DB::table('ai_api_keys')
            ->where('purpose', 'tts')
            ->exists();

        if ($usedTts) {
            Log::warning('Migration rollback skip: มี row ใช้ tts อยู่ — ห้าม rollback');

            return;
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
    }
};
