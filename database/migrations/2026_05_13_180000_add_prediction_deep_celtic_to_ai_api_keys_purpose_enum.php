<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-13) เพิ่มค่า 'prediction_deep' + 'prediction_celtic' ใน enum purpose
 *
 * วัตถุประสงค์:
 *   - แยก key ที่ใช้ทำนาย Deep 39฿ ออกจาก Celtic 99฿
 *   - admin มาร์ค key ของแต่ละแพคเกจได้ละเอียดขึ้น (เช่น GPT-5 สำหรับ Celtic
 *     เพราะลูกค้าจ่าย 99฿ คุณภาพต้องสูง, Gemini Flash สำหรับ Deep 39฿
 *     เพราะค่าครูถูก speed สำคัญกว่า)
 *   - Hierarchy ใน scopeForPurpose:
 *     prediction_deep   → match ['prediction_deep', 'prediction', 'any', null]
 *     prediction_celtic → match ['prediction_celtic', 'prediction', 'any', null]
 *     prediction        → match ['prediction', 'any', null]  (legacy เดิม)
 *   → admin ไม่ต้องสร้าง key ซ้ำ — ถ้ามาร์ค 'prediction' ก็ใช้ได้ทั้ง 2 แพคเกจ
 *     ถ้าอยากแยกเจาะจง ค่อยมาร์ค prediction_deep / prediction_celtic
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

        // เช็ค enum มี prediction_deep + prediction_celtic แล้วหรือยัง (idempotent)
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            $hasDeep = $columnInfo && stripos($columnInfo->COLUMN_TYPE, "'prediction_deep'") !== false;
            $hasCeltic = $columnInfo && stripos($columnInfo->COLUMN_TYPE, "'prediction_celtic'") !== false;

            if ($hasDeep && $hasCeltic) {
                Log::info('Migration skip: prediction_deep + prediction_celtic มีอยู่ใน enum แล้ว');

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
                'sensitive',
                'tts'
            ) NOT NULL DEFAULT 'any'
        ");

        Log::info('✅ Migration: เพิ่ม prediction_deep + prediction_celtic ใน enum purpose ของ ai_api_keys');
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

        // ห้าม rollback ถ้ามี row ใช้ค่าใหม่
        $usedNew = DB::table('ai_api_keys')
            ->whereIn('purpose', ['prediction_deep', 'prediction_celtic'])
            ->exists();

        if ($usedNew) {
            Log::warning('Migration rollback skip: มี row ใช้ prediction_deep/prediction_celtic อยู่');

            return;
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
    }
};
