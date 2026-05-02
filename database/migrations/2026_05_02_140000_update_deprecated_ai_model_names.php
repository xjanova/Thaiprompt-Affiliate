<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-02) Update deprecated AI model names ใน ai_api_keys + fortune_telling_settings
 *
 * Production logs:
 *   - OpenRouter 404: "No endpoints found for anthropic/claude-3.5-sonnet"
 *   - Gemini 404: "models/gemini-2.0-flash-exp is not found"
 *
 * Provider deprecate model names เดิม → ต้องอัปเดตทั้ง:
 *   1. ai_api_keys.model — per-key model override
 *   2. fortune_telling_settings.ai_model + chat_ai_model
 *   3. (optional) ai_content_settings.gemini_model
 */
return new class extends Migration
{
    /**
     * mapping: ชื่อเก่า → ชื่อใหม่ที่ใช้งานได้
     */
    private const MODEL_MAPPING = [
        // OpenRouter — old short aliases removed
        'anthropic/claude-3.5-sonnet' => 'anthropic/claude-sonnet-4',
        'anthropic/claude-3.5-haiku' => 'anthropic/claude-haiku-4.5',
        'anthropic/claude-3-opus' => 'anthropic/claude-opus-4',
        // Gemini — experimental discontinued
        'gemini-2.0-flash-exp' => 'gemini-2.0-flash',
        'google/gemini-2.0-flash-exp:free' => 'google/gemini-2.0-flash-001',
        'google/gemini-2.0-flash-exp' => 'google/gemini-2.0-flash-001',
    ];

    public function up(): void
    {
        $this->updateAiApiKeys();
        $this->updateFortuneSettings();
        $this->updateAiContentSettings();
    }

    public function down(): void
    {
        // ไม่ revert — ของใหม่ใช้งานได้ ของเก่า 404
    }

    /**
     * อัปเดต ai_api_keys.model
     */
    private function updateAiApiKeys(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        try {
            foreach (self::MODEL_MAPPING as $old => $new) {
                $count = DB::table('ai_api_keys')
                    ->where('model', $old)
                    ->update(['model' => $new, 'updated_at' => now()]);

                if ($count > 0) {
                    \Illuminate\Support\Facades\Log::info(
                        "Migration: updated {$count} ai_api_keys row(s)",
                        ['from' => $old, 'to' => $new]
                    );
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration update_deprecated_ai_models: ai_api_keys failed',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * อัปเดต fortune_telling_settings.ai_model + chat_ai_model
     */
    private function updateFortuneSettings(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        try {
            foreach (self::MODEL_MAPPING as $old => $new) {
                DB::table('fortune_telling_settings')
                    ->where('ai_model', $old)
                    ->update(['ai_model' => $new, 'updated_at' => now()]);

                DB::table('fortune_telling_settings')
                    ->where('chat_ai_model', $old)
                    ->update(['chat_ai_model' => $new, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration update_deprecated_ai_models: fortune_settings failed',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * อัปเดต ai_content_settings (key/value table)
     */
    private function updateAiContentSettings(): void
    {
        if (! Schema::hasTable('ai_content_settings')) {
            return;
        }

        try {
            foreach (self::MODEL_MAPPING as $old => $new) {
                DB::table('ai_content_settings')
                    ->whereIn('key', ['gemini_model', 'claude_model', 'openai_model', 'openrouter_model'])
                    ->where('value', $old)
                    ->update(['value' => $new, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration update_deprecated_ai_models: ai_content_settings failed',
                ['error' => $e->getMessage()]
            );
        }
    }
};
