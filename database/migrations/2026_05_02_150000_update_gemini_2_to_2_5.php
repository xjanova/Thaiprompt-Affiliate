<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-02) Update gemini-2.0-flash → gemini-2.5-flash
 *
 * Production logs:
 *   "This model models/gemini-2.0-flash is no longer available to new users.
 *    Please update your code to use a newer model."
 *
 * Google deprecate gemini-2.0-flash → ต้อง switch ไป 2.5-flash
 */
return new class extends Migration
{
    private const MAPPING = [
        'gemini-2.0-flash' => 'gemini-2.5-flash',
        'models/gemini-2.0-flash' => 'gemini-2.5-flash',
    ];

    public function up(): void
    {
        $this->update('ai_api_keys', 'model');
        $this->update('fortune_telling_settings', 'ai_model');
        $this->update('fortune_telling_settings', 'chat_ai_model');
        $this->updateAiContentSettings();
    }

    public function down(): void
    {
        // ไม่ revert — ของใหม่ใช้งานได้ ของเก่า 404
    }

    private function update(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        try {
            foreach (self::MAPPING as $old => $new) {
                $count = DB::table($table)
                    ->where($column, $old)
                    ->update([$column => $new, 'updated_at' => now()]);
                if ($count > 0) {
                    \Illuminate\Support\Facades\Log::info(
                        "Migration update_gemini_2_to_2_5: {$count} row(s) in {$table}.{$column}",
                        ['from' => $old, 'to' => $new]
                    );
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "Migration update_gemini_2_to_2_5 failed for {$table}.{$column}",
                ['error' => $e->getMessage()]
            );
        }
    }

    private function updateAiContentSettings(): void
    {
        if (! Schema::hasTable('ai_content_settings')) {
            return;
        }
        try {
            foreach (self::MAPPING as $old => $new) {
                DB::table('ai_content_settings')
                    ->where('key', 'gemini_model')
                    ->where('value', $old)
                    ->update(['value' => $new, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration update_gemini_2_to_2_5: ai_content_settings failed',
                ['error' => $e->getMessage()]
            );
        }
    }
};
