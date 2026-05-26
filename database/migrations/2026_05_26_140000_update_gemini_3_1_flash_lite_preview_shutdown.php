<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-05-26) Migrate gemini-3.1-flash-lite-preview → gemini-3.1-flash-lite (stable)
 *
 * Bug report (user): "วันนี้ gemini เป็นอะไร เช็ค log api หน่อยใช้ไม่ได้เลย"
 *
 * Root cause: Google shut down preview model — released stable cousin (drop `-preview` suffix)
 *   อ้างอิง: https://ai.google.dev/gemini-api/docs/models (May 2026)
 *   Error: "This model models/gemini-3.1-flash-lite-preview is no longer available"
 *
 * Production evidence (24hr audit before fix):
 *   - Gemini fail rate 46.5% (199/428)
 *   - 164/199 errors (82%) = "model no longer available" for gemini-3.1-flash-lite-preview
 *   - 9 keys ใช้ model นี้ → ใช้ไม่ได้ทั้งหมด
 *   - Fix ผ่าน tinker แล้ว (8/9 keys recovered, 1 key ติด project-denied แยกประเด็น)
 *
 * Migration นี้ทำเพื่อ:
 *   1. Lock in DB state จาก tinker hotfix → ป้องกัน drift หาก rollback restore
 *   2. ครอบคลุม fresh installs / staging / dev environments
 *   3. Audit trail ใน schema migrations table
 *
 * Related code change (commit เดียวกัน):
 *   - app/Models/AiApiKey.php — ลบ 'gemini-3.1-flash-lite-preview' จาก MODELS_BY_PROVIDER + TPM/RPM tables
 *
 * Target: gemini-3.1-flash-lite (stable เทียบเท่า, ราคาเดิม, ไม่มีความเสี่ยง deprecation)
 */
return new class extends Migration
{
    private const DEPRECATED_MODEL = 'gemini-3.1-flash-lite-preview';
    private const TARGET_MODEL = 'gemini-3.1-flash-lite';

    public function up(): void
    {
        // 1. ai_api_keys — Gemini keys ที่ admin ตั้ง model deprecated
        if (Schema::hasTable('ai_api_keys')) {
            $candidates = DB::table('ai_api_keys')
                ->where('provider', 'gemini')
                ->where('model', self::DEPRECATED_MODEL)
                ->get(['id', 'name', 'model', 'is_active', 'consecutive_errors']);

            if ($candidates->isNotEmpty()) {
                $updated = DB::table('ai_api_keys')
                    ->where('provider', 'gemini')
                    ->where('model', self::DEPRECATED_MODEL)
                    ->update([
                        'model' => self::TARGET_MODEL,
                        'is_active' => 1,
                        'consecutive_errors' => 0,
                        'disabled_until' => null,
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);

                Log::warning("Migration: อัปเดต {$updated} Gemini key(s) preview → stable", [
                    'before' => $candidates->map(fn ($k) => [
                        'id' => $k->id,
                        'name' => $k->name,
                        'was_active' => $k->is_active,
                        'errors' => $k->consecutive_errors,
                    ])->toArray(),
                    'from' => self::DEPRECATED_MODEL,
                    'to' => self::TARGET_MODEL,
                    'note' => 'Google shut down preview ของ 3.1-flash-lite — ใช้ stable แทน (drop `-preview` suffix)',
                ]);
            } else {
                Log::info('Migration: ไม่มี Gemini key ที่ใช้ gemini-3.1-flash-lite-preview — ข้าม');
            }
        }

        // 2. fortune_telling_settings — admin อาจตั้ง model deprecated เป็น default ของ setting
        if (Schema::hasTable('fortune_telling_settings')) {
            foreach (['ai_model', 'chat_ai_model', 'sensitive_model', 'sensitive_classifier_model'] as $col) {
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    continue;
                }

                $rowsUpdated = DB::table('fortune_telling_settings')
                    ->where($col, self::DEPRECATED_MODEL)
                    ->update([
                        $col => self::TARGET_MODEL,
                        'updated_at' => now(),
                    ]);

                if ($rowsUpdated > 0) {
                    Log::warning("Migration: fortune_telling_settings.{$col} {$rowsUpdated} rows → ".self::TARGET_MODEL);
                }
            }
        }
    }

    /**
     * Rollback: ไม่ revert (preview ใช้ไม่ได้แล้ว — revert จะกลับมา error อีก)
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — gemini-3.1-flash-lite-preview shut down by Google, cannot revert');
    }
};
