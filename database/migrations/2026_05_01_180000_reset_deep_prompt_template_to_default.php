<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 (2026-05-01) Reset deep_prompt_template to NULL (use new default)
 *
 * เหตุผล:
 *   - มีการ refactor prompt structure ใหม่ (Section A persona + closing + ความยาว 600-900 คำ)
 *   - admin ที่เคยตั้ง custom prompt ใน DB จะค้างอยู่กับ structure เก่า
 *   - ต้องการให้ admin เห็น default ใหม่ใน UI + AI ใช้ default ใหม่
 *
 * วิธี:
 *   1. Backup ค่าเดิมใน column ใหม่ deep_prompt_template_legacy (ก่อน NULL)
 *   2. NULL ออก deep_prompt_template ทุก row → ใช้ default จาก getDefaultDeepPrompt()
 *
 * Admin สามารถดูค่าเดิมได้จาก deep_prompt_template_legacy ถ้าต้องการ restore
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // 1. เพิ่มคอลัมน์ backup (ถ้ายังไม่มี)
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'deep_prompt_template_legacy', function ($table) {
                $table->text('deep_prompt_template_legacy')
                    ->nullable()
                    ->after('deep_prompt_template')
                    ->comment('Backup ของ deep_prompt_template ก่อน reset เป็น default ใหม่ (2026-05-01)');
            });
        });

        // 2. Backup + NULL
        try {
            $rows = DB::table('fortune_telling_settings')
                ->whereNotNull('deep_prompt_template')
                ->where('deep_prompt_template', '!=', '')
                ->get(['id', 'deep_prompt_template']);

            foreach ($rows as $row) {
                DB::table('fortune_telling_settings')
                    ->where('id', $row->id)
                    ->update([
                        'deep_prompt_template_legacy' => $row->deep_prompt_template,
                        'deep_prompt_template' => null,
                    ]);
            }
        } catch (\Throwable $e) {
            // ไม่ critical — log แต่ไม่ rollback
            \Illuminate\Support\Facades\Log::warning(
                'Migration reset_deep_prompt: backup/null failed',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // Restore จาก legacy column ถ้ามี
        try {
            $rows = DB::table('fortune_telling_settings')
                ->whereNotNull('deep_prompt_template_legacy')
                ->get(['id', 'deep_prompt_template_legacy']);

            foreach ($rows as $row) {
                DB::table('fortune_telling_settings')
                    ->where('id', $row->id)
                    ->update([
                        'deep_prompt_template' => $row->deep_prompt_template_legacy,
                    ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Migration reset_deep_prompt rollback: restore failed',
                ['error' => $e->getMessage()]
            );
        }

        // ไม่ลบคอลัมน์ legacy — เก็บไว้เป็น backup เผื่อใช้ในอนาคต
    }
};
