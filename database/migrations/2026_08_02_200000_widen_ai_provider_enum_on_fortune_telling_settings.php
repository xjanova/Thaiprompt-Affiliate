<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ขยาย ENUM `ai_provider` ให้ครอบคลุม provider ที่ระบบใช้จริง
     *
     * 🐛 (2026-08-02) ปัญหาที่เจอบน prod:
     *   - validation ของแอดมิน (FortuneSettingsController:210) รับ openai/anthropic/grok/... อยู่แล้ว
     *   - แต่คอลัมน์ยังเป็น enum('gemini','groq','qwen','openrouter') ตั้งแต่ migration แรก (2026-01-29)
     *   - พอสั่ง `ai_provider = 'openai'` → MySQL ตอบ 1265 Data truncated → ทั้ง statement ถูก rollback
     *
     * ผลกระทบจริง: เปิด `prediction_strict_provider` ไม่ได้เลยสำหรับ provider ที่ให้บริการ
     * คำทำนายจริงอยู่ (คีย์ purpose=prediction บน prod คือ openai/gpt-5.4-mini) — พอ strict
     * ล็อกไม่ได้ คำทำนาย 39/99 จึงสลับโมเดลไปมาตามคีย์ที่ pool หยิบได้ → รูปแบบคำทำนายไม่นิ่ง
     *
     * รายชื่อที่ใส่ = ชุดเดียวกับ validation rule เป๊ะ ๆ (กัน drift รอบใหม่)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'ai_provider')) {
            return;
        }

        // ENUM DDL แบบนี้เป็นของ MySQL/MariaDB — driver อื่นข้ามไป (เช่น sqlite ตอนเทสต์)
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            'ALTER TABLE `fortune_telling_settings` MODIFY COLUMN `ai_provider` '
            ."ENUM('gemini','groq','grok','qwen','openrouter','deepseek','typhoon','openai','anthropic') "
            ."NOT NULL DEFAULT 'gemini' COMMENT 'AI Provider ที่ใช้'"
        );
    }

    /**
     * ย้อนกลับเป็น 4 ค่าเดิม
     *
     * ⚠️ ต้องดึงค่าที่อยู่นอกชุดเดิมกลับเป็น 'gemini' ก่อน ไม่งั้น MySQL จะ truncate
     *    ข้อมูลเงียบ ๆ (หรือ error) ตอน ALTER
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'ai_provider')) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('fortune_telling_settings')
            ->whereNotIn('ai_provider', ['gemini', 'groq', 'qwen', 'openrouter'])
            ->update(['ai_provider' => 'gemini']);

        DB::statement(
            'ALTER TABLE `fortune_telling_settings` MODIFY COLUMN `ai_provider` '
            ."ENUM('gemini','groq','qwen','openrouter') "
            ."NOT NULL DEFAULT 'gemini' COMMENT 'AI Provider ที่ใช้'"
        );
    }
};
