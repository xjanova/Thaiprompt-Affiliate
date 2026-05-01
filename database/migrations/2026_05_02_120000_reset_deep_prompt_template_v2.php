<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-02) Reset deep_prompt_template v2 — ใหม่กว่า migration 2026-05-01
 *
 * เหตุผล:
 *   - ผู้ใช้แจ้งว่าคำทำนายซ้ำ-วน-เหมือนไม่ใช้ดวงดาว + พบบั๊ก "คุณคุณ" (genderPrefix + name fallback)
 *   - แก้ default prompt ใน FortuneConversationService::getDefaultDeepPrompt() ให้:
 *     • ลด word count (Q1 600-900 → 350-500, Q2/Q3 450-650 → 250-350)
 *     • ลบกฎขัดแย้ง "ห้ามสาธยายดาว" → เปลี่ยนเป็น "ต้องอ้าง 2 ภพ + 1 ดาว"
 *     • เพิ่ม anti-repetition word blacklist
 *     • เพิ่ม lottery handler (บังคับฟันธงเลข + งวด)
 *
 * วิธี:
 *   1. ถ้า admin เคยตั้ง custom prompt ทับไว้ → backup ไป legacy_v2 ก่อน NULL
 *   2. NULL ออก deep_prompt_template ทุก row → ใช้ default v2 ใหม่จาก code
 *
 * Restore: SQL UPDATE fortune_telling_settings
 *   SET deep_prompt_template = deep_prompt_template_legacy_v2 WHERE deep_prompt_template_legacy_v2 IS NOT NULL;
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // 1. เพิ่มคอลัมน์ backup v2 (แยกจาก legacy เดิม เผื่อ admin set v1 prompt ไว้แล้ว set v2 ทับ)
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'deep_prompt_template_legacy_v2', function ($table) {
                $table->text('deep_prompt_template_legacy_v2')
                    ->nullable()
                    ->after('deep_prompt_template')
                    ->comment('Backup ของ deep_prompt_template ก่อน reset เป็น default v2 (2026-05-02)');
            });
        });

        // 2. Backup + NULL ทุก row ที่มีค่า
        try {
            $rows = DB::table('fortune_telling_settings')
                ->whereNotNull('deep_prompt_template')
                ->where('deep_prompt_template', '!=', '')
                ->get(['id', 'deep_prompt_template']);

            foreach ($rows as $row) {
                DB::table('fortune_telling_settings')
                    ->where('id', $row->id)
                    ->update([
                        'deep_prompt_template_legacy_v2' => $row->deep_prompt_template,
                        'deep_prompt_template' => null,
                    ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration reset_deep_prompt_v2: backup/null failed',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // Restore จาก legacy_v2 ถ้ามี
        try {
            $rows = DB::table('fortune_telling_settings')
                ->whereNotNull('deep_prompt_template_legacy_v2')
                ->get(['id', 'deep_prompt_template_legacy_v2']);

            foreach ($rows as $row) {
                DB::table('fortune_telling_settings')
                    ->where('id', $row->id)
                    ->update([
                        'deep_prompt_template' => $row->deep_prompt_template_legacy_v2,
                    ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration reset_deep_prompt_v2 rollback: restore failed',
                ['error' => $e->getMessage()]
            );
        }
    }
};
