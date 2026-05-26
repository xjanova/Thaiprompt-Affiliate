<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌐 (2026-05-26 v2) Migrate ALL Gemini preview models → stable (gemini-2.5-pro)
 *
 * User directive: "เอาออกแล้วเปลี่นเป็นอันที่ ใช้ได้จริงเท่านั้น"
 *
 * Google docs guidance (May 2026):
 *   - "Most production apps should use a specific stable model"
 *   - "Preview models will be deprecated with at least 2 weeks notice"
 *   - ไม่มี stable Gemini 3 Pro — gemini-3 Pro Preview ถูก shut down ไป 2026-03-09
 *
 * Preview models ที่ลบ + เหตุผลการเลือก target:
 *   - gemini-3.1-pro-preview     → gemini-2.5-pro (stable + reasoning ยังดี)
 *   - gemini-3-flash-preview     → gemini-2.5-pro (เผื่อมีคนใช้)
 *
 * Why gemini-2.5-pro (ไม่ใช่ 3.5-flash)?
 *   - Pro tier admin เคยเลือก → ต้องการ reasoning ลึก
 *   - sensitive_model ใช้ตรวจ crisis/mental_fragile/abusive — flash ไม่พอ
 *   - 2.5-pro = stable ยาวนาน, ราคาเท่าเดิม
 *
 * Production evidence ก่อน migrate:
 *   - 2 keys (ID 1 TP Fortune Gemini, ID 34 sen) ใช้ 3.1-pro-preview
 *   - fortune_telling_settings.sensitive_model = 3.1-pro-preview
 *   - Smoke test → 200 OK ทั้ง 2 keys ก่อน migrate
 *
 * Related code change (commit เดียวกัน):
 *   - app/Models/AiApiKey.php — ลบ preview entries จาก MODELS_BY_PROVIDER + TPM/RPM tables
 */
return new class extends Migration
{
    private const PREVIEW_MODELS = [
        'gemini-3.1-pro-preview',
        'gemini-3-flash-preview',
    ];

    private const TARGET_MODEL = 'gemini-2.5-pro';

    public function up(): void
    {
        // 1. ai_api_keys — Gemini keys ที่ admin ตั้ง preview
        if (Schema::hasTable('ai_api_keys')) {
            $candidates = DB::table('ai_api_keys')
                ->where('provider', 'gemini')
                ->whereIn('model', self::PREVIEW_MODELS)
                ->get(['id', 'name', 'model']);

            if ($candidates->isNotEmpty()) {
                $updated = DB::table('ai_api_keys')
                    ->where('provider', 'gemini')
                    ->whereIn('model', self::PREVIEW_MODELS)
                    ->update([
                        'model' => self::TARGET_MODEL,
                        'updated_at' => now(),
                    ]);

                Log::warning("Migration: อัปเดต {$updated} Gemini key(s) preview → stable", [
                    'before' => $candidates->map(fn ($k) => [
                        'id' => $k->id,
                        'name' => $k->name,
                        'old_model' => $k->model,
                    ])->toArray(),
                    'to' => self::TARGET_MODEL,
                    'reason' => 'production-grade only — preview models มีความเสี่ยง 2-week shutdown notice',
                ]);
            } else {
                Log::info('Migration: ไม่มี Gemini key ใช้ preview models — ข้าม');
            }
        }

        // 2. fortune_telling_settings — admin อาจตั้ง preview เป็น default
        if (Schema::hasTable('fortune_telling_settings')) {
            foreach (['ai_model', 'chat_ai_model', 'sensitive_model', 'sensitive_classifier_model'] as $col) {
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    continue;
                }

                $rowsUpdated = DB::table('fortune_telling_settings')
                    ->whereIn($col, self::PREVIEW_MODELS)
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
     * Rollback: ไม่ revert (preview มีความเสี่ยง shutdown, ไม่ควรกลับไป)
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — preview models มีความเสี่ยง shutdown 2-week notice');
    }
};
