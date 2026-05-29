<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🗑️ (2026-05-29) รีแมป model ปลอม 'gpt-5.5-mini' → 'gpt-5.4-mini'
 *
 * เหตุผล: ยืนยัน live กับ OpenAI API (/v1/responses, key prod id 37) แล้วว่า
 *   'gpt-5.5-mini' คืน HTTP 400 "model_not_found" — ไม่มีรุ่นนี้จริง
 *   (เคยหลุดเข้า AiApiKey::MODELS_BY_PROVIDER + docs โดยเข้าใจผิด)
 *
 * ถ้า key/setting ใดตั้งค่าเป็น gpt-5.5-mini ค้างไว้ → API call จะ fail 400 →
 *   ลูกค้า Celtic 99฿ เจอ "ติดขัด" จ่ายเงินแล้วไม่ได้คำตอบ
 *
 * รุ่นที่ใช้แทน = 'gpt-5.4-mini' (ยืนยัน valid, $0.75/$4.50 — คุ้มราคา reasoning gen 5.4)
 *
 * อัปเดต 3 จุด (แพทเทิร์นเดียวกับ 2026_05_02_140000_update_deprecated_ai_model_names):
 *   1. ai_api_keys.model
 *   2. fortune_telling_settings.ai_model / chat_ai_model / sensitive_model
 *   3. ai_content_settings (key/value table)
 */
return new class extends Migration
{
    /**
     * ชื่อเก่า (ปลอม) → ชื่อใหม่ที่ใช้งานได้จริง
     */
    private const OLD = 'gpt-5.5-mini';

    private const NEW = 'gpt-5.4-mini';

    public function up(): void
    {
        $this->fixAiApiKeys();
        $this->fixFortuneSettings();
        $this->fixAiContentSettings();
    }

    public function down(): void
    {
        // ไม่ revert — ของใหม่ใช้งานได้ ของเก่า (gpt-5.5-mini) คืน 400 model_not_found
    }

    /**
     * อัปเดต ai_api_keys.model
     */
    private function fixAiApiKeys(): void
    {
        if (! Schema::hasTable('ai_api_keys') || ! Schema::hasColumn('ai_api_keys', 'model')) {
            return;
        }

        try {
            $count = DB::table('ai_api_keys')
                ->where('model', self::OLD)
                ->update(['model' => self::NEW, 'updated_at' => now()]);

            if ($count > 0) {
                Log::info("Migration: รีแมป {$count} ai_api_keys row(s) จาก gpt-5.5-mini → gpt-5.4-mini");
            }
        } catch (\Throwable $e) {
            Log::warning('Migration fix_gpt_5_5_mini: ai_api_keys failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * อัปเดต fortune_telling_settings — ai_model / chat_ai_model / sensitive_model
     */
    private function fixFortuneSettings(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        try {
            foreach (['ai_model', 'chat_ai_model', 'sensitive_model'] as $col) {
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    continue;
                }

                DB::table('fortune_telling_settings')
                    ->where($col, self::OLD)
                    ->update([$col => self::NEW, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Migration fix_gpt_5_5_mini: fortune_settings failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * อัปเดต ai_content_settings (key/value table)
     */
    private function fixAiContentSettings(): void
    {
        if (! Schema::hasTable('ai_content_settings')) {
            return;
        }

        try {
            DB::table('ai_content_settings')
                ->where('value', self::OLD)
                ->update(['value' => self::NEW, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Migration fix_gpt_5_5_mini: ai_content_settings failed', ['error' => $e->getMessage()]);
        }
    }
};
