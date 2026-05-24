<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-05-24) อัปเดต Gemini keys + settings ที่ใช้ model deprecated → gemini-2.5-flash
 *
 * Bug report (user audit): "เช็คความเสถียร provider อื่นๆ ด้วย" หลัง fix Groq
 *
 * Root cause: Google deprecate Gemini 2.0 family + 1.5 family
 *   อ้างอิง: https://ai.google.dev/gemini-api/docs/models
 *   "These models are deprecated and will be shut down soon"
 *
 *   - gemini-2.0-flash / -flash-001 / -flash-lite / -flash-exp → deprecated
 *   - gemini-1.5-flash / -flash-8b / -pro → not listed (likely removed)
 *
 * Production evidence (7-day audit):
 *   Gemini failure rate 21.3% (787/3690) — 610×429 + 105×5xx + 53×timeout
 *   ส่วนใหญ่จาก 2.5 family (ยังใช้ได้) แต่ admin บางตัวอาจใช้ 2.0/1.5 ที่ Google ยกเลิก
 *
 * Fix:
 *   1. ลบจาก MODELS_BY_PROVIDER + MODEL_TPM/RPM tables (commit เดียวกัน)
 *   2. Migration นี้ — อัปเดต DB rows: ai_api_keys + fortune_telling_settings
 *
 * Target: gemini-2.5-flash (เสถียร + ใช้แพร่หลายในระบบเรา)
 *   - admin ที่อยากใช้ 3.x → เปลี่ยนใน UI หลัง deploy
 */
return new class extends Migration
{
    private const DEPRECATED_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-001',
        'gemini-2.0-flash-lite',
        'gemini-2.0-flash-exp',
        'gemini-1.5-flash',
        'gemini-1.5-flash-8b',
        'gemini-1.5-pro',
    ];

    private const TARGET_MODEL = 'gemini-2.5-flash';

    public function up(): void
    {
        // 1. ai_api_keys — Gemini keys ที่ admin ตั้ง model deprecated
        if (Schema::hasTable('ai_api_keys')) {
            $candidates = DB::table('ai_api_keys')
                ->where('provider', 'gemini')
                ->whereIn('model', self::DEPRECATED_MODELS)
                ->get(['id', 'name', 'model']);

            if ($candidates->isNotEmpty()) {
                $updated = 0;
                foreach ($candidates as $key) {
                    DB::table('ai_api_keys')
                        ->where('id', $key->id)
                        ->update([
                            'model' => self::TARGET_MODEL,
                            'updated_at' => now(),
                        ]);
                    $updated++;
                }

                Log::warning("Migration: อัปเดต {$updated} Gemini key(s) deprecated → ".self::TARGET_MODEL, [
                    'before' => $candidates->map(fn ($k) => [
                        'id' => $k->id,
                        'name' => $k->name,
                        'old_model' => $k->model ?: '(empty)',
                    ])->toArray(),
                    'note' => 'Google deprecated Gemini 2.0/1.5 family — ดู ai.google.dev/gemini-api/docs/models',
                ]);
            } else {
                Log::info('Migration: ไม่มี Gemini key ที่ใช้ deprecated model — ข้าม');
            }
        }

        // 2. fortune_telling_settings — admin อาจตั้ง model deprecated เป็น default ของ setting
        if (Schema::hasTable('fortune_telling_settings')) {
            foreach (['ai_model', 'chat_ai_model', 'sensitive_model', 'sensitive_classifier_model'] as $col) {
                // เช็คคอลัมน์มีจริง (กัน schema mismatch)
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    continue;
                }

                $rowsUpdated = DB::table('fortune_telling_settings')
                    ->whereIn($col, self::DEPRECATED_MODELS)
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
     * Rollback: ไม่ revert (deprecated models ใช้ไม่ได้แล้ว — revert จะกลับมา error อีก)
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — Gemini models deprecated by Google, cannot revert');
    }
};
