<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-07) เพิ่ม Sensitive AI Mode settings ใน fortune_telling_settings
 *
 * Sensitive AI Mode = ระบบสลับใช้ Pro model อัตโนมัติเมื่อบริบทละเอียดอ่อน
 *
 * จุดประสงค์:
 *   - ลูกค้าอารมณ์ร้าย / คำหยาบ / คำถามซับซ้อน / หัวข้อหนัก (ตาย/ป่วย/หย่า)
 *     → switch ใช้ Gemini Pro / GPT-5+ แทน Groq llama default
 *   - ใช้จิตวิทยาขั้นสูง — ต้อนแต่ไม่ด่า, ไม้แข็งแต่สุภาพ
 *   - กลับ default อัตโนมัติเมื่อบริบทเย็นลง (ไม่ sticky)
 *
 * Detection: heuristic (regex) + Groq classifier hybrid
 * Budget: per-user daily count + total daily THB cap
 * Off-topic: strike counter + revert/block action
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม sensitive_* columns
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // 🎚️ Mode: ปิด / เฉพาะทำนายเสียเงิน / ใช้ทั่วบอท
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_ai_mode')) {
                $table->enum('sensitive_ai_mode', ['off', 'paid_only', 'all'])
                    ->default('paid_only')
                    ->after('chat_system_prompt')
                    ->comment('โหมด Sensitive AI: off=ปิด, paid_only=เฉพาะทำนายเสียเงิน, all=ทั่วบอท');
            }

            // 🔍 Detection mode: heuristic เร็ว / hybrid แม่น / hybrid+brain (Phase 3)
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_detection_mode')) {
                $table->enum('sensitive_detection_mode', ['heuristic', 'hybrid', 'hybrid_with_brain'])
                    ->default('hybrid')
                    ->comment('โหมดตรวจจับ: heuristic=regex เท่านั้น, hybrid=regex+AI classifier, hybrid_with_brain=+ObsidianX (Phase 3)');
            }

            // 🧠 Sensitive Provider/Model — Pro tier
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_provider')) {
                $table->string('sensitive_provider', 50)
                    ->default('gemini')
                    ->comment('Provider ของ Pro model (gemini, openai, anthropic)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_model')) {
                $table->string('sensitive_model', 100)
                    ->default('gemini-3.1-pro-preview')
                    ->comment('Pro model ที่ใช้เมื่อ trigger (เช่น gemini-3.1-pro-preview)');
            }

            // 🤖 Classifier: Groq llama-8b ฟรี เร็ว
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_classifier_provider')) {
                $table->string('sensitive_classifier_provider', 50)
                    ->default('groq')
                    ->comment('Provider ของ AI classifier (Groq llama-8b แนะนำ — ฟรี เร็ว)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_classifier_model')) {
                $table->string('sensitive_classifier_model', 100)
                    ->default('llama-3.1-8b-instant')
                    ->comment('Model ของ classifier (llama-3.1-8b-instant แนะนำ)');
            }

            // 📋 Custom keyword/topic lists (admin edit ได้)
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_keywords')) {
                $table->json('sensitive_keywords')
                    ->nullable()
                    ->comment('JSON array: คำหยาบ/อารมณ์ร้าย/ทดสอบบอท (admin override default)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_topics')) {
                $table->json('sensitive_topics')
                    ->nullable()
                    ->comment('JSON array: หัวข้อหนัก (ตาย, ป่วยหนัก, หย่า, ฆ่าตัวตาย)');
            }

            // 💰 Budget guard
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_max_per_user_daily')) {
                $table->integer('sensitive_max_per_user_daily')
                    ->default(5)
                    ->comment('จำกัด trigger Pro กี่ครั้ง/user/วัน (กัน abuse)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_max_total_daily_thb')) {
                $table->decimal('sensitive_max_total_daily_thb', 10, 2)
                    ->default(200.00)
                    ->comment('budget cap รวม/วัน (THB) — เกินแล้ว block ทั้งระบบ');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_max_tokens_per_call')) {
                $table->integer('sensitive_max_tokens_per_call')
                    ->default(2000)
                    ->comment('จำกัด tokens/call (กัน prompt injection ทำให้แพง)');
            }

            // 🚧 Off-topic guard
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_offtopic_strikes')) {
                $table->integer('sensitive_offtopic_strikes')
                    ->default(3)
                    ->comment('ถาม off-topic กี่ครั้งจึงตัดสินใจ (default 3)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_offtopic_action')) {
                $table->enum('sensitive_offtopic_action', ['revert', 'block', 'handoff'])
                    ->default('revert')
                    ->comment('การจัดการเมื่อเกิน strikes: revert=กลับ default, block=บล็อก, handoff=ส่งให้แอดมิน');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_offtopic_block_message')) {
                $table->text('sensitive_offtopic_block_message')
                    ->nullable()
                    ->comment('ข้อความเมื่อ block (admin custom ได้)');
            }

            // 📊 Logging
            if (! Schema::hasColumn('fortune_telling_settings', 'sensitive_log_enabled')) {
                $table->boolean('sensitive_log_enabled')
                    ->default(true)
                    ->comment('บันทึก log ทุก trigger ลง fortune_sensitive_events');
            }
        });
    }

    /**
     * ลบ sensitive_* columns
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $columns = [
            'sensitive_ai_mode',
            'sensitive_detection_mode',
            'sensitive_provider',
            'sensitive_model',
            'sensitive_classifier_provider',
            'sensitive_classifier_model',
            'sensitive_keywords',
            'sensitive_topics',
            'sensitive_max_per_user_daily',
            'sensitive_max_total_daily_thb',
            'sensitive_max_tokens_per_call',
            'sensitive_offtopic_strikes',
            'sensitive_offtopic_action',
            'sensitive_offtopic_block_message',
            'sensitive_log_enabled',
        ];

        $this->safeDropColumn('fortune_telling_settings', $columns);
    }
};
