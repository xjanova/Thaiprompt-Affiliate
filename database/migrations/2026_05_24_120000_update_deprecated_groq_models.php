<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-24) อัปเดต Groq keys ที่ใช้ model deprecated → llama-3.3-70b-versatile
 *
 * Bug report (user): "เหมือนโมเดล groq บางตัวมันใช้ไม่ได้"
 *
 * Root cause: Groq ลบ models หลายตัวจาก endpoint
 *   อ้างอิง: https://console.groq.com/docs/deprecations
 *
 *   | Deprecated model                                | Shutdown   | Replacement                |
 *   |-------------------------------------------------|------------|----------------------------|
 *   | llama-3.1-70b-versatile                         | 2025-01-24 | llama-3.3-70b-versatile    |
 *   | llama-3.2-90b-vision-preview                    | 2024-11-25 | (vision ไม่รองรับ — fallback OpenAI ที่ FortuneAIService:1011) |
 *   | llama-3.2-11b-vision-preview                    | 2024-10-28 | (vision ไม่รองรับ)         |
 *   | meta-llama/llama-4-maverick-17b-128e-instruct   | 2026-03-09 | openai/gpt-oss-120b        |
 *   | gemma2-9b-it                                    | 2025-10-08 | llama-3.1-8b-instant       |
 *   | mixtral-8x7b-32768                              | 2025-03-20 | llama-3.3-70b-versatile    |
 *   | qwen-qwq-32b                                    | (removed)  | qwen/qwen3-32b             |
 *   | deepseek-r1-distill-llama-70b                   | (removed)  | openai/gpt-oss-120b        |
 *
 * Fix:
 *   1. ลบจาก MODELS_BY_PROVIDER + MODEL_TPM_FREE_TIER + MODEL_RPM_FREE_TIER (commit เดียวกัน)
 *   2. Migration นี้ — อัปเดต DB rows ที่ admin set ค่าเก่าไว้
 *
 * Target default: llama-3.3-70b-versatile (เสถียร + เป็น default ของระบบ)
 *   - admin ที่ต้องการ vision → ใช้ OpenAI key ตามที่ระบบ fallback อยู่แล้ว
 *   - admin ที่ต้องการรุ่นอื่น (gpt-oss-120b, qwen3-32b) → เปลี่ยนใน UI หลัง deploy
 */
return new class extends Migration
{
    /**
     * Models ที่ Groq ลบแล้ว — ต้อง migrate
     */
    private const DEPRECATED_MODELS = [
        'llama-3.1-70b-versatile',
        'llama-3.2-90b-vision-preview',
        'llama-3.2-11b-vision-preview',
        'meta-llama/llama-4-maverick-17b-128e-instruct',
        'gemma2-9b-it',
        'mixtral-8x7b-32768',
        'qwen-qwq-32b',
        'deepseek-r1-distill-llama-70b',
    ];

    private const TARGET_MODEL = 'llama-3.3-70b-versatile';

    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        // นับ + log ก่อนอัปเดต (สำหรับ audit)
        $candidates = DB::table('ai_api_keys')
            ->where('provider', 'groq')
            ->whereIn('model', self::DEPRECATED_MODELS)
            ->get(['id', 'name', 'model']);

        if ($candidates->isEmpty()) {
            Log::info('Migration: ไม่มี Groq key ที่ใช้ deprecated model — ข้าม');

            return;
        }

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

        Log::warning("Migration: อัปเดต {$updated} Groq key(s) deprecated → ".self::TARGET_MODEL, [
            'before' => $candidates->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'old_model' => $k->model ?: '(empty)',
            ])->toArray(),
            'note' => 'Groq deprecated หลาย models — ดู https://console.groq.com/docs/deprecations',
        ]);

        // 🪙 (2026-05-24 v2) Upgrade sensitive_classifier_model default
        //   ก่อนหน้านี้ default='llama-3.1-8b-instant' (TPM 6000) — ทะลุง่าย → 413
        //   เปลี่ยน default → 'llama-3.3-70b-versatile' (TPM 12000)
        //   แต่ ALTER เฉพาะ row ที่ยังเป็นค่า default เดิม (กัน admin ที่ตั้งใจเลือก 8b)
        if (Schema::hasTable('fortune_telling_settings')) {
            $sensUpdated = DB::table('fortune_telling_settings')
                ->where('sensitive_classifier_model', 'llama-3.1-8b-instant')
                ->update([
                    'sensitive_classifier_model' => 'llama-3.3-70b-versatile',
                    'updated_at' => now(),
                ]);

            if ($sensUpdated > 0) {
                Log::warning("Migration: upgrade sensitive_classifier_model 8b-instant → 3.3-70b ({$sensUpdated} rows)", [
                    'reason' => 'TPM 6000 → 12000 ลด 413 Request too large',
                ]);
            }
        }
    }

    /**
     * Rollback: ไม่ revert (deprecated models ใช้ไม่ได้แล้ว — revert จะกลับมา 400 อีก)
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — Groq models deprecated, cannot revert');
    }
};
