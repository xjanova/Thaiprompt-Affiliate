<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่ม indexes สำหรับ LINE bot tables เพื่อเพิ่ม performance
     *
     * ✅ ใช้ SafeMigration trait เพื่อความปลอดภัย
     * ✅ อิงจากคอลัมน์ที่มีจริงในตาราง
     *
     * @return void
     */
    public function up(): void
    {
        // ===================================
        // line_bot_conversations table
        // ===================================
        // ⚠️ Note: ตารางนี้ใช้ 'status' ไม่ใช่ 'is_active'
        // Columns: status, line_user_id, user_id, ai_setting_id, last_message_at, updated_at

        // Index สำหรับ query conversations ที่ active และเรียงตาม updated_at
        $this->safeAddIndex(
            'line_bot_conversations',
            ['status', 'updated_at'],
            'idx_status_updated'
        );

        // Index สำหรับ query conversations ตาม user_id และ status
        $this->safeAddIndex(
            'line_bot_conversations',
            ['user_id', 'status'],
            'idx_user_status'
        );

        // Index สำหรับ query conversations ตาม ai_setting_id
        $this->safeAddIndex(
            'line_bot_conversations',
            'ai_setting_id',
            'idx_ai_setting'
        );

        // Index สำหรับ query conversations ตาม last_message_at (สำหรับ cleanup งานเก่า)
        $this->safeAddIndex(
            'line_bot_conversations',
            'last_message_at',
            'idx_last_message'
        );

        // ===================================
        // line_bot_messages table (ถ้ามี)
        // ===================================
        if (Schema::hasTable('line_bot_messages')) {
            // Index สำหรับ query messages ตาม conversation_id และ created_at
            $this->safeAddIndex(
                'line_bot_messages',
                ['conversation_id', 'created_at'],
                'idx_conversation_created'
            );

            // Index สำหรับ query messages ตาม type (ถ้ามี column นี้)
            if (Schema::hasColumn('line_bot_messages', 'type')) {
                $this->safeAddIndex(
                    'line_bot_messages',
                    'type',
                    'idx_type'
                );
            }

            // Index สำหรับ query messages ตาม direction (ถ้ามี column นี้)
            if (Schema::hasColumn('line_bot_messages', 'direction')) {
                $this->safeAddIndex(
                    'line_bot_messages',
                    'direction',
                    'idx_direction'
                );
            }
        }

        // ===================================
        // line_bot_ai_settings table (ถ้ามี)
        // ===================================
        if (Schema::hasTable('line_bot_ai_settings')) {
            // Index สำหรับ query settings ที่ active (ถ้ามี is_active column)
            if (Schema::hasColumn('line_bot_ai_settings', 'is_active')) {
                $this->safeAddIndex(
                    'line_bot_ai_settings',
                    'is_active',
                    'idx_is_active'
                );
            }

            // Index สำหรับ query settings ตาม status (ถ้ามี status column แทน)
            if (Schema::hasColumn('line_bot_ai_settings', 'status')) {
                $this->safeAddIndex(
                    'line_bot_ai_settings',
                    'status',
                    'idx_status'
                );
            }
        }
    }

    /**
     * ลบ indexes ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // line_bot_conversations indexes
        $this->safeDropIndex('line_bot_conversations', 'idx_status_updated');
        $this->safeDropIndex('line_bot_conversations', 'idx_user_status');
        $this->safeDropIndex('line_bot_conversations', 'idx_ai_setting');
        $this->safeDropIndex('line_bot_conversations', 'idx_last_message');

        // line_bot_messages indexes
        if (Schema::hasTable('line_bot_messages')) {
            $this->safeDropIndex('line_bot_messages', 'idx_conversation_created');
            $this->safeDropIndex('line_bot_messages', 'idx_type');
            $this->safeDropIndex('line_bot_messages', 'idx_direction');
        }

        // line_bot_ai_settings indexes
        if (Schema::hasTable('line_bot_ai_settings')) {
            $this->safeDropIndex('line_bot_ai_settings', 'idx_is_active');
            $this->safeDropIndex('line_bot_ai_settings', 'idx_status');
        }
    }
};
