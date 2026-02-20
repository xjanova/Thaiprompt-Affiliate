<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ AI Chat (สนทนาทั่วไป) ในตาราง fortune_telling_settings
     *
     * แยก provider สำหรับ chat ทั่วไป (Gemini) ออกจาก provider สำหรับทำนาย (Grok)
     * เพื่อให้ admin ตั้งค่าแยกกันได้ — chat เน้นสนทนาเป็นธรรมชาติ, fortune เน้นทำนายแม่น
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิด AI Chat ทั่วไป
            $this->safeAddColumn($table, 'fortune_telling_settings', 'enable_ai_chat', function ($table) {
                $table->boolean('enable_ai_chat')->default(true)->after('comment_engagement_prompt');
            });

            // AI Provider สำหรับ chat (แยกจาก provider ทำนาย)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'chat_ai_provider', function ($table) {
                $table->string('chat_ai_provider', 50)->default('gemini')->after('enable_ai_chat');
            });

            // AI Model สำหรับ chat
            $this->safeAddColumn($table, 'fortune_telling_settings', 'chat_ai_model', function ($table) {
                $table->string('chat_ai_model', 100)->default('gemini-2.0-flash')->after('chat_ai_provider');
            });

            // API Key override สำหรับ chat (ถ้าว่าง → ใช้ key จาก pool/global)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'chat_ai_api_key', function ($table) {
                $table->string('chat_ai_api_key', 500)->nullable()->after('chat_ai_model');
            });

            // System prompt สำหรับ chat (ถ้าว่าง → ใช้ default)
            $this->safeAddColumn($table, 'fortune_telling_settings', 'chat_system_prompt', function ($table) {
                $table->text('chat_system_prompt')->nullable()->after('chat_ai_api_key');
            });
        });
    }

    /**
     * ลบคอลัมน์ AI Chat
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', [
            'enable_ai_chat',
            'chat_ai_provider',
            'chat_ai_model',
            'chat_ai_api_key',
            'chat_system_prompt',
        ]);
    }
};
