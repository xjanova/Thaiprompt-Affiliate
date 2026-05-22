<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🎯 (2026-05-22) อัปเดต free Gemini chat keys → gemini-2.5-flash-lite
 *
 * User spec: "gemini free เปลี่ยนไปใช้ 2.5 ที่โควต้าเยอะกว่าเลย สำหรับงานแชท"
 *
 * Gemini 2.5 free tier quotas (verified 2026-05):
 *   - 2.5 Pro          : 5  RPM (paid quality)
 *   - 2.5 Flash        : 10 RPM (current default ของ chat keys)
 *   - 2.5 Flash Lite   : 15 RPM ⭐ (โควต้าเยอะสุดใน 2.5 family)
 *
 * → 15 vs 10 = +50% throughput สำหรับ chat
 * → ลดโอกาส 429 → ลด drift ไป paid Gemini
 *
 * เงื่อนไขการอัปเดต (กันแตะ paid keys หรือ Pro keys ที่อาจตั้งใจใช้):
 *   1. provider = 'gemini'
 *   2. purpose = 'chat' (เฉพาะแชท)
 *   3. rate_limit_per_minute IS NULL OR <= 30 (อนุมานเป็น free tier)
 *   4. model อยู่ในกลุ่ม legacy/flash (ไม่แตะ Pro/preview ที่ตั้งใจเลือก)
 *
 * Reversible: down() จะ revert กลับ gemini-2.5-flash (ค่าเดิมก่อนหน้านี้)
 */
return new class extends Migration
{
    /**
     * รายชื่อ model ที่ "upgrade-safe" → เปลี่ยนเป็น flash-lite ได้
     * ไม่รวม Pro/preview เพราะ admin อาจตั้งใจเลือก quality สูง
     * NULL / '' จัดการแยกใน WHERE clause
     */
    private const UPGRADEABLE_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-2.0-flash-001',
        'gemini-2.0-flash-exp',
        'gemini-1.5-flash',
        'gemini-1.5-flash-8b',
    ];

    private const TARGET_MODEL = 'gemini-2.5-flash-lite';

    /**
     * เปลี่ยน model ของ free Gemini chat keys
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่ (รัน fresh ครั้งแรกอาจยังไม่มี)
        if (! \Schema::hasTable('ai_api_keys')) {
            return;
        }

        // นับก่อนอัปเดต (สำหรับ log)
        $candidates = DB::table('ai_api_keys')
            ->where('provider', 'gemini')
            ->where('purpose', 'chat')
            ->where(function ($q) {
                $q->whereNull('rate_limit_per_minute')
                    ->orWhere('rate_limit_per_minute', '<=', 30);
            })
            ->where(function ($q) {
                $q->whereNull('model')
                    ->orWhere('model', '')
                    ->orWhereIn('model', self::UPGRADEABLE_MODELS);
            })
            ->get(['id', 'name', 'model', 'rate_limit_per_minute']);

        if ($candidates->isEmpty()) {
            Log::info('Migration: ไม่มี free Gemini chat key ที่ต้อง upgrade — ข้าม');

            return;
        }

        // อัปเดต
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

        Log::info("Migration: upgrade {$updated} Gemini chat key(s) → ".self::TARGET_MODEL, [
            'before' => $candidates->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'model' => $k->model,
                'rpm' => $k->rate_limit_per_minute,
            ])->toArray(),
        ]);
    }

    /**
     * Revert กลับ gemini-2.5-flash (ค่า default ก่อนหน้านี้)
     *
     * ⚠️ ไม่สามารถ revert ที่เคยเป็น 2.0-flash หรือ 1.5-flash ได้
     *    เพราะข้อมูล original หายไปแล้ว — ทุกตัวจะกลายเป็น 2.5-flash
     */
    public function down(): void
    {
        if (! \Schema::hasTable('ai_api_keys')) {
            return;
        }

        $count = DB::table('ai_api_keys')
            ->where('provider', 'gemini')
            ->where('purpose', 'chat')
            ->where('model', self::TARGET_MODEL)
            ->update([
                'model' => 'gemini-2.5-flash',
                'updated_at' => now(),
            ]);

        Log::info("Migration rollback: revert {$count} Gemini chat key(s) → gemini-2.5-flash");
    }
};
