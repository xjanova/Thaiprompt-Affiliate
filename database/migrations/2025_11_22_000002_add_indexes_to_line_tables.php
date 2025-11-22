<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม indexes สำหรับ LINE tables เพื่อเพิ่มประสิทธิภาพการ query
     *
     * เน้น:
     * - Conversations: query by line_user_id, created_at
     * - Messages: query by conversation_id, created_at
     * - Sessions: query by status, updated_at
     * - Keywords: query by trigger, is_active
     *
     * @return void
     */
    public function up(): void
    {
        // ===== LINE BOT CONVERSATIONS =====
        Schema::table('line_bot_conversations', function (Blueprint $table) {
            // Query บ่อย: ดึง conversations ของ user แต่ละคน เรียงตามเวลา
            $this->safeAddIndex('line_bot_conversations', ['line_user_id', 'created_at'], 'idx_user_created');

            // Query บ่อย: ค้นหาด้วย session_id
            $this->safeAddIndex('line_bot_conversations', 'session_id');

            // Query: ดึง conversations ที่ยัง active
            $this->safeAddIndex('line_bot_conversations', ['is_active', 'updated_at'], 'idx_active_updated');
        });

        // ===== LINE BOT MESSAGES =====
        Schema::table('line_bot_messages', function (Blueprint $table) {
            // Query บ่อยมาก: ดึง messages ของ conversation เรียงตามเวลา
            $this->safeAddIndex('line_bot_messages', ['conversation_id', 'created_at'], 'idx_conv_created');

            // Query: ดึง messages ตาม sender (user/bot)
            $this->safeAddIndex('line_bot_messages', ['sender', 'created_at'], 'idx_sender_created');
        });

        // ===== LINE SIGNUP SESSIONS =====
        Schema::table('line_signup_sessions', function (Blueprint $table) {
            // Query บ่อย: ดึง sessions ตาม status และเวลาอัปเดตล่าสุด
            $this->safeAddIndex('line_signup_sessions', ['status', 'updated_at'], 'idx_status_updated');

            // Query: ดึง session ของ user
            $this->safeAddIndex('line_signup_sessions', 'line_user_id');

            // Query: ดึง session ตาม token
            $this->safeAddIndex('line_signup_sessions', 'session_token');
        });

        // ===== LINE BOT KEYWORDS =====
        Schema::table('line_bot_keywords', function (Blueprint $table) {
            // Query บ่อย: ค้นหา keyword ที่ active
            $this->safeAddIndex('line_bot_keywords', ['is_active', 'trigger'], 'idx_active_trigger');

            // Query: ดึง keywords ตาม priority
            $this->safeAddIndex('line_bot_keywords', ['priority', 'is_active'], 'idx_priority_active');

            // Query: ดึง keywords ที่ match บ่อย (sorting)
            $this->safeAddIndex('line_bot_keywords', 'times_matched');
        });

        // ===== LINE SIGNUP CONVERSATIONS =====
        if (Schema::hasTable('line_signup_conversations')) {
            Schema::table('line_signup_conversations', function (Blueprint $table) {
                // Query: ดึง conversations ของ session
                $this->safeAddIndex('line_signup_conversations', ['session_id', 'created_at'], 'idx_session_created');
            });
        }

        // ===== LINE FLEX MESSAGE TEMPLATES =====
        if (Schema::hasTable('line_flex_message_templates')) {
            Schema::table('line_flex_message_templates', function (Blueprint $table) {
                // Query: ดึง templates ที่ active
                $this->safeAddIndex('line_flex_message_templates', 'is_active');
            });
        }

        // ===== LINE RICH MENUS =====
        if (Schema::hasTable('line_rich_menus')) {
            Schema::table('line_rich_menus', function (Blueprint $table) {
                // Query: ดึง rich menu ที่ active และ default
                $this->safeAddIndex('line_rich_menus', ['is_active', 'is_default'], 'idx_active_default');
            });
        }

        // ===== LINE LOGIN LOGS =====
        if (Schema::hasTable('line_login_logs')) {
            Schema::table('line_login_logs', function (Blueprint $table) {
                // Query: ดึง logs ของ user
                $this->safeAddIndex('line_login_logs', ['line_user_id', 'created_at'], 'idx_user_created');

                // Query: ดึง logs ตาม status
                $this->safeAddIndex('line_login_logs', ['status', 'created_at'], 'idx_status_created');
            });
        }
    }

    /**
     * ลบ indexes ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // LINE BOT CONVERSATIONS
        $this->safeDropIndex('line_bot_conversations', 'line_bot_conversations_line_user_id_created_at_index');
        $this->safeDropIndex('line_bot_conversations', 'line_bot_conversations_session_id_index');
        $this->safeDropIndex('line_bot_conversations', 'line_bot_conversations_is_active_updated_at_index');

        // LINE BOT MESSAGES
        $this->safeDropIndex('line_bot_messages', 'line_bot_messages_conversation_id_created_at_index');
        $this->safeDropIndex('line_bot_messages', 'line_bot_messages_sender_created_at_index');

        // LINE SIGNUP SESSIONS
        $this->safeDropIndex('line_signup_sessions', 'line_signup_sessions_status_updated_at_index');
        $this->safeDropIndex('line_signup_sessions', 'line_signup_sessions_line_user_id_index');
        $this->safeDropIndex('line_signup_sessions', 'line_signup_sessions_session_token_index');

        // LINE BOT KEYWORDS
        $this->safeDropIndex('line_bot_keywords', 'line_bot_keywords_is_active_trigger_index');
        $this->safeDropIndex('line_bot_keywords', 'line_bot_keywords_priority_is_active_index');
        $this->safeDropIndex('line_bot_keywords', 'line_bot_keywords_times_matched_index');

        // LINE SIGNUP CONVERSATIONS
        if (Schema::hasTable('line_signup_conversations')) {
            $this->safeDropIndex('line_signup_conversations', 'line_signup_conversations_session_id_created_at_index');
        }

        // LINE FLEX MESSAGE TEMPLATES
        if (Schema::hasTable('line_flex_message_templates')) {
            $this->safeDropIndex('line_flex_message_templates', 'line_flex_message_templates_is_active_index');
        }

        // LINE RICH MENUS
        if (Schema::hasTable('line_rich_menus')) {
            $this->safeDropIndex('line_rich_menus', 'line_rich_menus_is_active_is_default_index');
        }

        // LINE LOGIN LOGS
        if (Schema::hasTable('line_login_logs')) {
            $this->safeDropIndex('line_login_logs', 'line_login_logs_line_user_id_created_at_index');
            $this->safeDropIndex('line_login_logs', 'line_login_logs_status_created_at_index');
        }
    }
};
