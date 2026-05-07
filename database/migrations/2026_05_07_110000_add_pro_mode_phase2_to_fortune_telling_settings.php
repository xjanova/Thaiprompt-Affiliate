<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌟 (2026-05-07) Pro Mode Phase 2 — เพิ่ม 3 features
 *
 * 1. Bill Psychology — แม่หมอใจดีคุยให้ลูกค้ายอมโอน
 *    - แก้ objections (ไม่มีเงิน, โอนต่างประเทศ, กลัวโดนหลอก)
 *    - อธิบายค่าครู (ทำบุญ, energy exchange)
 *    - Anti-spam: cap จำนวน mention บิล/session (กันเหมือนสแกม)
 *    - ฟาดกลับแบบผู้ดี ถ้าลูกค้าก้าวร้าว
 *
 * 2. Celtic Premium Chat — หลังตอบครบ N คำถาม คุยต่อจนหมด window
 *    - ใช้ Pro model + context ไพ่ 10 ใบ + Q&A เดิม
 *    - แม่หมอจันทราตัวจริง — ลึก ไม่ผิวเผิน
 *    - เตือนเวลาเมื่อเหลือ < 5 นาที
 *
 * 3. Satisfaction Detector — ตรวจจับลูกค้าพอใจ + ปิดอบอุ่น
 *    - Heuristic: ขอบคุณ / พอแล้ว / เข้าใจแล้ว / ดีมาก
 *    - AI ปิดอย่างอบอุ่น (ไม่เสนอบริการเพิ่ม)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // ============================================================
            // 💳 Bill Psychology
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'bill_psychology_enabled')) {
                $table->boolean('bill_psychology_enabled')
                    ->default(true)
                    ->comment('เปิดโหมดคุยกับลูกค้าที่ไม่กล้าโอน (ใช้ Pro model)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'bill_charity_message')) {
                $table->text('bill_charity_message')
                    ->nullable()
                    ->comment('ข้อความอธิบายค่าครู (admin custom — เอาไปทำบุญอะไร)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'bill_max_mentions_per_session')) {
                $table->tinyInteger('bill_max_mentions_per_session')
                    ->default(2)
                    ->comment('Bot mention บิลได้สูงสุดกี่ครั้ง/session (กันสแกม)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'bill_psychology_window_hours')) {
                $table->tinyInteger('bill_psychology_window_hours')
                    ->default(24)
                    ->comment('ระยะเวลาที่ถือว่ามีบิลค้าง (ชั่วโมง)');
            }

            // ============================================================
            // 🌙 Celtic Premium Chat
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_premium_chat_enabled')) {
                $table->boolean('celtic_premium_chat_enabled')
                    ->default(true)
                    ->comment('หลังตอบ Celtic ครบให้คุยต่อจนหมด window (ใช้ Pro model)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_premium_chat_trigger')) {
                $table->enum('celtic_premium_chat_trigger', ['after_questions_done', 'always_after_q1'])
                    ->default('after_questions_done')
                    ->comment('Trigger: หลังถามครบ / หลัง Q1 ตลอดเวลา');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_premium_chat_warn_minutes_left')) {
                $table->tinyInteger('celtic_premium_chat_warn_minutes_left')
                    ->default(5)
                    ->comment('เตือนลูกค้าก่อนหมดเวลากี่นาที');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_premium_chat_max_messages')) {
                $table->integer('celtic_premium_chat_max_messages')
                    ->default(30)
                    ->comment('จำกัด messages ใน premium chat (กัน abuse)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_premium_chat_prompt_override')) {
                $table->text('celtic_premium_chat_prompt_override')
                    ->nullable()
                    ->comment('Override system prompt (admin custom — ใช้ default ถ้าเว้น)');
            }

            // ============================================================
            // 🙏 Satisfaction Detector
            // ============================================================
            if (! Schema::hasColumn('fortune_telling_settings', 'satisfaction_detection_enabled')) {
                $table->boolean('satisfaction_detection_enabled')
                    ->default(true)
                    ->comment('ตรวจจับลูกค้าพอใจ + ปิดอบอุ่น');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'satisfaction_close_message')) {
                $table->text('satisfaction_close_message')
                    ->nullable()
                    ->comment('ข้อความปิด session (custom — ใช้ default ถ้าเว้น)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $columns = [
            'bill_psychology_enabled',
            'bill_charity_message',
            'bill_max_mentions_per_session',
            'bill_psychology_window_hours',
            'celtic_premium_chat_enabled',
            'celtic_premium_chat_trigger',
            'celtic_premium_chat_warn_minutes_left',
            'celtic_premium_chat_max_messages',
            'celtic_premium_chat_prompt_override',
            'satisfaction_detection_enabled',
            'satisfaction_close_message',
        ];

        $this->safeDropColumn('fortune_telling_settings', $columns);
    }
};
