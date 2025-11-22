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
     * ⚠️ แก้ไขจากเดิม: ใช้ 'status' แทน 'is_active' ใน line_bot_conversations
     *    (เพราะตารางนี้ไม่มี is_active column)
     *
     * เน้น:
     * - Conversations: query by line_user_id, created_at, status
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
            if (!Schema::hasColumn('line_bot_conversations', 'line_user_id')) {
                return; // ข้ามถ้าตารางยังไม่มี column นี้
            }

            $this->safeAddIndex('line_bot_conversations', ['line_user_id', 'created_at'], 'idx_user_created');

            // Query บ่อย: ค้นหาด้วย session_id
            if (Schema::hasColumn('line_bot_conversations', 'session_id')) {
                $this->safeAddIndex('line_bot_conversations', 'session_id', 'idx_session_id');
            }

            // ⚠️ แก้ไข: ใช้ 'status' แทน 'is_active'
            // Query: ดึง conversations ที่ active/closed/archived
            if (Schema::hasColumn('line_bot_conversations', 'status')) {
                $this->safeAddIndex('line_bot_conversations', ['status', 'updated_at'], 'idx_status_updated');
            }
        });

        // ===== LINE BOT MESSAGES =====
        if (Schema::hasTable('line_bot_messages')) {
            Schema::table('line_bot_messages', function (Blueprint $table) {
                // Query บ่อยมาก: ดึง messages ของ conversation เรียงตามเวลา
                if (Schema::hasColumn('line_bot_messages', 'conversation_id')) {
                    $this->safeAddIndex('line_bot_messages', ['conversation_id', 'created_at'], 'idx_conv_created');
                }

                // Query: ดึง messages ตาม sender (user/bot) - ถ้ามี column
                if (Schema::hasColumn('line_bot_messages', 'sender')) {
                    $this->safeAddIndex('line_bot_messages', ['sender', 'created_at'], 'idx_sender_created');
                }

                // Query: ดึง messages ตาม direction (inbound/outbound) - ถ้ามี column
                if (Schema::hasColumn('line_bot_messages', 'direction')) {
                    $this->safeAddIndex('line_bot_messages', ['direction', 'created_at'], 'idx_direction_created');
                }
            });
        }

        // ===== LINE SIGNUP SESSIONS =====
        if (Schema::hasTable('line_signup_sessions')) {
            Schema::table('line_signup_sessions', function (Blueprint $table) {
                // Query บ่อย: ดึง sessions ตาม status และเวลาอัปเดตล่าสุด
                if (Schema::hasColumn('line_signup_sessions', 'status')) {
                    $this->safeAddIndex('line_signup_sessions', ['status', 'updated_at'], 'idx_status_updated');
                }

                // Query: ดึง session ของ user
                if (Schema::hasColumn('line_signup_sessions', 'line_user_id')) {
                    $this->safeAddIndex('line_signup_sessions', 'line_user_id', 'idx_line_user');
                }

                // Query: ดึง session ตาม token
                if (Schema::hasColumn('line_signup_sessions', 'session_token')) {
                    $this->safeAddIndex('line_signup_sessions', 'session_token', 'idx_session_token');
                }
            });
        }

        // ===== LINE BOT KEYWORDS =====
        if (Schema::hasTable('line_bot_keywords')) {
            Schema::table('line_bot_keywords', function (Blueprint $table) {
                // Query บ่อย: ค้นหา keyword ที่ active
                if (Schema::hasColumn('line_bot_keywords', 'is_active') && Schema::hasColumn('line_bot_keywords', 'trigger')) {
                    $this->safeAddIndex('line_bot_keywords', ['is_active', 'trigger'], 'idx_active_trigger');
                }

                // Query: ดึง keywords ตาม priority
                if (Schema::hasColumn('line_bot_keywords', 'priority') && Schema::hasColumn('line_bot_keywords', 'is_active')) {
                    $this->safeAddIndex('line_bot_keywords', ['priority', 'is_active'], 'idx_priority_active');
                }

                // Query: ดึง keywords ที่ match บ่อย (sorting)
                if (Schema::hasColumn('line_bot_keywords', 'times_matched')) {
                    $this->safeAddIndex('line_bot_keywords', 'times_matched', 'idx_times_matched');
                }
            });
        }

        // ===== LINE SIGNUP CONVERSATIONS =====
        if (Schema::hasTable('line_signup_conversations')) {
            Schema::table('line_signup_conversations', function (Blueprint $table) {
                // Query: ดึง conversations ของ session
                if (Schema::hasColumn('line_signup_conversations', 'session_id')) {
                    $this->safeAddIndex('line_signup_conversations', ['session_id', 'created_at'], 'idx_session_created');
                }
            });
        }

        // ===== LINE FLEX MESSAGE TEMPLATES =====
        if (Schema::hasTable('line_flex_message_templates')) {
            Schema::table('line_flex_message_templates', function (Blueprint $table) {
                // Query: ดึง templates ที่ active
                if (Schema::hasColumn('line_flex_message_templates', 'is_active')) {
                    $this->safeAddIndex('line_flex_message_templates', 'is_active', 'idx_is_active');
                }
            });
        }

        // ===== LINE RICH MENUS =====
        if (Schema::hasTable('line_rich_menus')) {
            Schema::table('line_rich_menus', function (Blueprint $table) {
                // Query: ดึง rich menu ที่ active และ default
                if (Schema::hasColumn('line_rich_menus', 'is_active') && Schema::hasColumn('line_rich_menus', 'is_default')) {
                    $this->safeAddIndex('line_rich_menus', ['is_active', 'is_default'], 'idx_active_default');
                }
            });
        }

        // ===== LINE LOGIN LOGS =====
        if (Schema::hasTable('line_login_logs')) {
            Schema::table('line_login_logs', function (Blueprint $table) {
                // Query: ดึง logs ของ user
                if (Schema::hasColumn('line_login_logs', 'line_user_id')) {
                    $this->safeAddIndex('line_login_logs', ['line_user_id', 'created_at'], 'idx_user_created');
                }

                // Query: ดึง logs ตาม status
                if (Schema::hasColumn('line_login_logs', 'status')) {
                    $this->safeAddIndex('line_login_logs', ['status', 'created_at'], 'idx_status_created');
                }
            });
        }

        // ===== LINE BOT AI SETTINGS =====
        if (Schema::hasTable('line_bot_ai_settings')) {
            Schema::table('line_bot_ai_settings', function (Blueprint $table) {
                // Query: ดึง AI settings ที่ active
                if (Schema::hasColumn('line_bot_ai_settings', 'is_active')) {
                    $this->safeAddIndex('line_bot_ai_settings', 'is_active', 'idx_is_active');
                } elseif (Schema::hasColumn('line_bot_ai_settings', 'status')) {
                    $this->safeAddIndex('line_bot_ai_settings', 'status', 'idx_status');
                }
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
        $this->safeDropIndex('line_bot_conversations', 'idx_user_created');
        $this->safeDropIndex('line_bot_conversations', 'idx_session_id');
        $this->safeDropIndex('line_bot_conversations', 'idx_status_updated');

        // LINE BOT MESSAGES
        if (Schema::hasTable('line_bot_messages')) {
            $this->safeDropIndex('line_bot_messages', 'idx_conv_created');
            $this->safeDropIndex('line_bot_messages', 'idx_sender_created');
            $this->safeDropIndex('line_bot_messages', 'idx_direction_created');
        }

        // LINE SIGNUP SESSIONS
        if (Schema::hasTable('line_signup_sessions')) {
            $this->safeDropIndex('line_signup_sessions', 'idx_status_updated');
            $this->safeDropIndex('line_signup_sessions', 'idx_line_user');
            $this->safeDropIndex('line_signup_sessions', 'idx_session_token');
        }

        // LINE BOT KEYWORDS
        if (Schema::hasTable('line_bot_keywords')) {
            $this->safeDropIndex('line_bot_keywords', 'idx_active_trigger');
            $this->safeDropIndex('line_bot_keywords', 'idx_priority_active');
            $this->safeDropIndex('line_bot_keywords', 'idx_times_matched');
        }

        // LINE SIGNUP CONVERSATIONS
        if (Schema::hasTable('line_signup_conversations')) {
            $this->safeDropIndex('line_signup_conversations', 'idx_session_created');
        }

        // LINE FLEX MESSAGE TEMPLATES
        if (Schema::hasTable('line_flex_message_templates')) {
            $this->safeDropIndex('line_flex_message_templates', 'idx_is_active');
        }

        // LINE RICH MENUS
        if (Schema::hasTable('line_rich_menus')) {
            $this->safeDropIndex('line_rich_menus', 'idx_active_default');
        }

        // LINE LOGIN LOGS
        if (Schema::hasTable('line_login_logs')) {
            $this->safeDropIndex('line_login_logs', 'idx_user_created');
            $this->safeDropIndex('line_login_logs', 'idx_status_created');
        }

        // LINE BOT AI SETTINGS
        if (Schema::hasTable('line_bot_ai_settings')) {
            $this->safeDropIndex('line_bot_ai_settings', 'idx_is_active');
            $this->safeDropIndex('line_bot_ai_settings', 'idx_status');
        }
    }
};
