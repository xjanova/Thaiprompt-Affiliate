<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-05) เพิ่มค่า 'free_card' ใน enum purpose ของ ai_api_keys
 *
 * Bug: admin เลือก "🎁 เฉพาะทำนายฟรี" ใน edit form → save fail
 *   เพราะ enum เดิมรองรับแค่ ['any', 'prediction', 'chat']
 *
 * Fix: ALTER COLUMN เพิ่ม 'free_card' (รักษาค่าเดิม + default 'any')
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

        // เช็ค enum มี free_card แล้วหรือยัง (idempotent)
        try {
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'ai_api_keys'
                  AND COLUMN_NAME = 'purpose'
            ");

            if ($columnInfo && stripos($columnInfo->COLUMN_TYPE, 'free_card') !== false) {
                Log::info('Migration skip: free_card มีอยู่ใน enum แล้ว');
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
                'chat'
            ) NOT NULL DEFAULT 'any'
        ");

        Log::info('✅ Migration: เพิ่ม free_card ใน enum purpose ของ ai_api_keys');
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

        // ห้าม rollback ถ้ามี row ใช้ free_card
        $usedFreeCard = DB::table('ai_api_keys')
            ->where('purpose', 'free_card')
            ->exists();

        if ($usedFreeCard) {
            Log::warning('Migration rollback skip: มี row ใช้ free_card อยู่ — ห้าม rollback');
            return;
        }

        DB::statement("
            ALTER TABLE ai_api_keys
            MODIFY COLUMN purpose ENUM(
                'any',
                'prediction',
                'chat'
            ) NOT NULL DEFAULT 'any'
        ");
    }
};
