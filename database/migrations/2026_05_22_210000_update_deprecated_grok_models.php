<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-22) อัปเดต Grok keys ที่ใช้ model deprecated → grok-3-latest
 *
 * Bug report: ลูกค้าได้ error 400 "Model not found: grok-2-latest"
 *
 * Root cause: xAI deprecate Grok 2 series + grok-beta — ลบ endpoint ทั้งหมด
 *   - grok-2-latest, grok-2-1212, grok-2-vision-1212 — 400 Model not found
 *   - grok-beta, grok-vision-beta — 400 Model not found
 *
 * Fix:
 *   1. Code fallback (3 จุดใน FortuneAIService) — แก้แล้วใน commit เดียวกัน
 *   2. Migration นี้ — อัปเดต keys ที่ admin set ค่าเก่าไว้
 *   3. ลบจาก MODELS_BY_PROVIDER dropdown แล้ว (admin เลือกใหม่ไม่ได้)
 *
 * Target: grok-3-latest (เสถียร + ราคา/quality balance ดี)
 *   - admin ที่ต้องการรุ่นอื่น (grok-4, grok-3-mini) → เปลี่ยนใน UI หลัง deploy
 */
return new class extends Migration
{
    /**
     * Models ที่ xAI ลบแล้ว — ต้อง migrate
     */
    private const DEPRECATED_MODELS = [
        'grok-2-latest',
        'grok-2-1212',
        'grok-2-vision-1212',
        'grok-beta',
        'grok-vision-beta',
    ];

    private const TARGET_MODEL = 'grok-3-latest';

    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        // นับ + log ก่อนอัปเดต (สำหรับ audit)
        $candidates = DB::table('ai_api_keys')
            ->where('provider', 'grok')
            ->where(function ($q) {
                $q->whereNull('model')
                    ->orWhere('model', '')
                    ->orWhereIn('model', self::DEPRECATED_MODELS);
            })
            ->get(['id', 'name', 'model']);

        if ($candidates->isEmpty()) {
            Log::info('Migration: ไม่มี Grok key ที่ใช้ deprecated model — ข้าม');

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

        Log::warning("Migration: อัปเดต {$updated} Grok key(s) deprecated → ".self::TARGET_MODEL, [
            'before' => $candidates->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'old_model' => $k->model ?: '(empty)',
            ])->toArray(),
            'note' => 'xAI deprecated grok-2-* + grok-beta — ต้องใช้ grok-3-latest หรือ grok-4-latest',
        ]);
    }

    /**
     * Rollback: ไม่ revert (deprecated models ใช้ไม่ได้แล้ว — revert จะกลับมา 400 อีก)
     */
    public function down(): void
    {
        Log::warning('Migration rollback skipped — Grok 2 series deprecated by xAI, cannot revert');
    }
};
