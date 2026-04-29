<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์การตั้งค่า Celtic Cross Tarot Mode (99 บาท)
     *
     * - enable_celtic_cross: เปิด/ปิด mode (default ปิด)
     * - celtic_cross_price: ค่าครู (default 99)
     * - celtic_cross_max_questions: จำนวนคำถามต่อบิล (default 3 — Q1=full storytelling, Q2-3=follow-up)
     * - celtic_cross_qa_window_minutes: เวลาที่ลูกค้าถามต่อได้หลัง Q1 ตอบเสร็จ (default 60 นาที)
     * - celtic_cross_main_prompt: prompt template สำหรับ Q1 (full storytelling, weave 10 cards)
     * - celtic_cross_followup_prompt: prompt template สำหรับ Q2-Q3 (no card explain)
     * - celtic_cross_proactive_enabled: เปิด AI เชิญชวน Celtic Cross เมื่อลูกค้าทุกข์มาก
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // Toggle หลัก
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_cross')) {
                $table->boolean('enable_celtic_cross')
                    ->default(false)
                    ->after('brave_search_api_key')
                    ->comment('เปิด/ปิด Celtic Cross Tarot Mode (99฿)');
            }

            // ราคา + limits
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_price')) {
                $table->decimal('celtic_cross_price', 8, 2)
                    ->default(99.00)
                    ->after('enable_celtic_cross')
                    ->comment('ค่าครู Celtic Cross (บาท)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
                $table->unsignedTinyInteger('celtic_cross_max_questions')
                    ->default(3)
                    ->after('celtic_cross_price')
                    ->comment('จำนวนคำถามต่อบิล (Q1=full + Q2-3=follow-up)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_qa_window_minutes')) {
                $table->unsignedSmallInteger('celtic_cross_qa_window_minutes')
                    ->default(60)
                    ->after('celtic_cross_max_questions')
                    ->comment('เวลาที่ถาม Q2-3 ได้หลัง Q1 ตอบเสร็จ (นาที)');
            }

            // AI Prompts
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_main_prompt')) {
                $table->text('celtic_cross_main_prompt')
                    ->nullable()
                    ->after('celtic_cross_qa_window_minutes')
                    ->comment('Prompt template สำหรับ Q1 — admin แก้ได้ ใช้ตัวแปร {question}, {cards}, {brand_name}');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_followup_prompt')) {
                $table->text('celtic_cross_followup_prompt')
                    ->nullable()
                    ->after('celtic_cross_main_prompt')
                    ->comment('Prompt template สำหรับ Q2-Q3 — admin แก้ได้');
            }

            // Proactive AI suggestion
            if (! Schema::hasColumn('fortune_telling_settings', 'celtic_cross_proactive_enabled')) {
                $table->boolean('celtic_cross_proactive_enabled')
                    ->default(true)
                    ->after('celtic_cross_followup_prompt')
                    ->comment('AI เชิญชวน Celtic Cross เมื่อลูกค้าแสดงความทุกข์ในแชทปกติ');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'enable_celtic_cross',
                'celtic_cross_price',
                'celtic_cross_max_questions',
                'celtic_cross_qa_window_minutes',
                'celtic_cross_main_prompt',
                'celtic_cross_followup_prompt',
                'celtic_cross_proactive_enabled',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
