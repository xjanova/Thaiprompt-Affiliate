<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์สำหรับระบบรับสมัครผ่าน LINE AI
 *
 * ฟิลด์ที่เพิ่ม:
 * - use_for_recruitment: เปิดใช้งานสำหรับการรับสมัคร
 * - recruitment_system_prompt: คำสั่งระบบสำหรับการรับสมัคร
 * - allowed_topics: หัวข้อที่อนุญาตให้คุย
 * - blocked_topics: หัวข้อที่ห้ามคุย
 * - response_style: สไตล์การตอบ
 * - greeting_message: ข้อความต้อนรับ
 * - max_conversation_turns: จำนวนรอบการคุยสูงสุด
 * - collect_user_info: เก็บข้อมูลผู้ใช้
 * - required_fields: ฟิลด์ที่ต้องเก็บ
 */
return new class extends Migration
{
    /**
     * เพิ่มฟิลด์สำหรับระบบรับสมัคร
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_bot_ai_settings', function (Blueprint $table) {
            // การเปิดใช้งานสำหรับการรับสมัคร
            if (!Schema::hasColumn('line_bot_ai_settings', 'use_for_recruitment')) {
                $table->boolean('use_for_recruitment')->default(false)->after('is_active');
            }

            // System prompt พิเศษสำหรับการรับสมัคร
            if (!Schema::hasColumn('line_bot_ai_settings', 'recruitment_system_prompt')) {
                $table->text('recruitment_system_prompt')->nullable()->after('system_prompt');
            }

            // หัวข้อที่อนุญาตให้คุย (JSON array)
            if (!Schema::hasColumn('line_bot_ai_settings', 'allowed_topics')) {
                $table->json('allowed_topics')->nullable()->after('recruitment_system_prompt');
            }

            // หัวข้อที่ห้ามคุย (JSON array)
            if (!Schema::hasColumn('line_bot_ai_settings', 'blocked_topics')) {
                $table->json('blocked_topics')->nullable()->after('allowed_topics');
            }

            // สไตล์การตอบ: friendly, professional, casual
            if (!Schema::hasColumn('line_bot_ai_settings', 'response_style')) {
                $table->string('response_style', 50)->default('friendly')->after('blocked_topics');
            }

            // ข้อความต้อนรับ
            if (!Schema::hasColumn('line_bot_ai_settings', 'greeting_message')) {
                $table->text('greeting_message')->nullable()->after('response_style');
            }

            // จำนวนรอบการคุยสูงสุดก่อนส่งต่อ
            if (!Schema::hasColumn('line_bot_ai_settings', 'max_conversation_turns')) {
                $table->integer('max_conversation_turns')->default(50)->after('greeting_message');
            }

            // เก็บข้อมูลผู้ใช้หรือไม่
            if (!Schema::hasColumn('line_bot_ai_settings', 'collect_user_info')) {
                $table->boolean('collect_user_info')->default(true)->after('max_conversation_turns');
            }

            // ฟิลด์ที่ต้องเก็บ (JSON array)
            if (!Schema::hasColumn('line_bot_ai_settings', 'required_fields')) {
                $table->json('required_fields')->nullable()->after('collect_user_info');
            }

            // ข้อความเมื่อหมดรอบการคุย
            if (!Schema::hasColumn('line_bot_ai_settings', 'max_turns_message')) {
                $table->text('max_turns_message')->nullable()->after('required_fields');
            }

            // เปิดใช้งาน topic filtering
            if (!Schema::hasColumn('line_bot_ai_settings', 'enable_topic_filtering')) {
                $table->boolean('enable_topic_filtering')->default(true)->after('max_turns_message');
            }

            // ข้อความเมื่อคุยเรื่องที่ห้าม
            if (!Schema::hasColumn('line_bot_ai_settings', 'off_topic_message')) {
                $table->text('off_topic_message')->nullable()->after('enable_topic_filtering');
            }
        });
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_bot_ai_settings', function (Blueprint $table) {
            $columns = [
                'use_for_recruitment',
                'recruitment_system_prompt',
                'allowed_topics',
                'blocked_topics',
                'response_style',
                'greeting_message',
                'max_conversation_turns',
                'collect_user_info',
                'required_fields',
                'max_turns_message',
                'enable_topic_filtering',
                'off_topic_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('line_bot_ai_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
