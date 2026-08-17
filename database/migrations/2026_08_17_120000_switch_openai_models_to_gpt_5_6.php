<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🔄 (2026-08-17) ย้าย OpenAI models จาก GPT-5.4 → GPT-5.6
 *
 * เหตุผล — ถูกกว่าและฉลาดกว่าพร้อมกัน (ไม่ใช่ trade-off):
 *   gpt-5.4-mini = $0.75 in / $4.50 out  · Artificial Analysis Intelligence Index ~41
 *   gpt-5.6-luna = $0.20 in / $1.20 out  · AA Index 52   ← ถูกกว่า 3.75 เท่า + ฉลาดกว่า
 *
 * วัดกับยอดใช้จริง key 37 (30 วัน: in 7.67M / out 17.89M):
 *   gpt-5.4-mini = $86.26/เดือน  →  gpt-5.6-luna = $23.00/เดือน  (ประหยัด ~73%)
 *
 * ✅ Verified live บน /v1/responses ด้วย key prod (id 37) 2026-08-17 —
 *    gpt-5.6-luna / -terra / -sol ผ่านทั้ง 3 ตัว (effort=low → reasoning 0 token)
 *    ไม่ใช่การเดาชื่อ model (บทเรียนจาก gpt-5.5-mini ที่ไม่มีอยู่จริง — migration 2026_05_29_120000)
 *
 * แผนที่การรีแมป:
 *   • ai_api_keys purpose='sensitive' → gpt-5.6-sol  (AA Index 59)
 *     เส้นทางนี้คือคำถามหนัก ตาย/ป่วย/หย่า/ฆ่าตัวตาย — ตอบพลาดแล้วอันตรายจริง
 *     ปริมาณเบามาก (93 คอล/30 วัน) → ต้นทุนเพิ่มแค่ ~$1/เดือน
 *   • ai_api_keys อื่นๆ + settings ทั้งหมด → gpt-5.6-luna
 *   • gpt-5.4-nano → gpt-5.6-luna ด้วย (nano ราคาพอกัน $0.20/$1.25 แต่ฉลาดน้อยกว่าชัดเจน)
 *
 * ⚠️ ไม่แตะ gpt-5.4 / gpt-5.5 / gpt-5.5-pro / gpt-4o — ถ้าแอดมินตั้งไว้เองแปลว่าตั้งใจ
 * ⚠️ ไม่แตะ provider อื่น (gemini/groq/minimax) — chat ยังอยู่บน Gemini free tier = ฿0
 */
return new class extends Migration
{
    /** models เก่าที่จะถูกรีแมป */
    private const FROM = ['gpt-5.4-mini', 'gpt-5.4-nano'];

    /** ปลายทางปกติ — ถูกสุด + ฉลาดกว่าของเดิม */
    private const TO_DEFAULT = 'gpt-5.6-luna';

    /** ปลายทางของ key purpose='sensitive' — flagship */
    private const TO_SENSITIVE = 'gpt-5.6-sol';

    public function up(): void
    {
        $this->migrateAiApiKeys();
        $this->migrateFortuneSettings();
        $this->migrateAiContentSettings();
    }

    public function down(): void
    {
        // ย้อนกลับได้ — คืนเป็น gpt-5.4-mini ทั้งหมด (nano ไม่คืน เพราะไม่มีเหตุผลใช้แล้ว)
        if (! Schema::hasTable('ai_api_keys') || ! Schema::hasColumn('ai_api_keys', 'model')) {
            return;
        }

        try {
            DB::table('ai_api_keys')
                ->where('provider', 'openai')
                ->whereIn('model', [self::TO_DEFAULT, self::TO_SENSITIVE])
                ->update(['model' => 'gpt-5.4-mini', 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Migration switch_openai_5_6 down: ล้มเหลว', ['error' => $e->getMessage()]);
        }
    }

    /**
     * อัปเดต ai_api_keys.model — แยกตาม purpose
     */
    private function migrateAiApiKeys(): void
    {
        if (! Schema::hasTable('ai_api_keys') || ! Schema::hasColumn('ai_api_keys', 'model')) {
            return;
        }

        try {
            // 1) sensitive → Sol (เส้นทางเสี่ยงสูง ปริมาณต่ำ)
            $sensitive = 0;
            if (Schema::hasColumn('ai_api_keys', 'purpose')) {
                $sensitive = DB::table('ai_api_keys')
                    ->where('provider', 'openai')
                    ->where('purpose', 'sensitive')
                    ->whereIn('model', self::FROM)
                    ->update(['model' => self::TO_SENSITIVE, 'updated_at' => now()]);
            }

            // 2) ที่เหลือทั้งหมด → Luna
            $normal = DB::table('ai_api_keys')
                ->where('provider', 'openai')
                ->whereIn('model', self::FROM)
                ->update(['model' => self::TO_DEFAULT, 'updated_at' => now()]);

            Log::info('Migration switch_openai_5_6: รีแมป ai_api_keys สำเร็จ', [
                'sensitive_to_sol' => $sensitive,
                'others_to_luna' => $normal,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Migration switch_openai_5_6: ai_api_keys ล้มเหลว', ['error' => $e->getMessage()]);
        }
    }

    /**
     * อัปเดต fortune_telling_settings — ai_model / chat_ai_model / sensitive_model
     */
    private function migrateFortuneSettings(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        try {
            foreach (['ai_model', 'chat_ai_model', 'sensitive_model'] as $col) {
                if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                    continue;
                }

                // sensitive_model ไปที่ Sol เช่นเดียวกับ key purpose='sensitive'
                $target = $col === 'sensitive_model' ? self::TO_SENSITIVE : self::TO_DEFAULT;

                DB::table('fortune_telling_settings')
                    ->whereIn($col, self::FROM)
                    ->update([$col => $target, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Migration switch_openai_5_6: fortune_settings ล้มเหลว', ['error' => $e->getMessage()]);
        }
    }

    /**
     * อัปเดต ai_content_settings (key/value table)
     */
    private function migrateAiContentSettings(): void
    {
        if (! Schema::hasTable('ai_content_settings')) {
            return;
        }

        try {
            DB::table('ai_content_settings')
                ->whereIn('value', self::FROM)
                ->update(['value' => self::TO_DEFAULT, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Migration switch_openai_5_6: ai_content_settings ล้มเหลว', ['error' => $e->getMessage()]);
        }
    }
};
