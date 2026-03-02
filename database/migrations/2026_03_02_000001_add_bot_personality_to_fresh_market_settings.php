<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ bot personality, greeting, AI scope, data access
     * สำหรับทำให้บอท "พี่ตลาด" ฉลาดขึ้น + admin ควบคุมได้ทุกอย่าง
     */
    public function up(): void
    {
        Schema::table('fresh_market_settings', function (Blueprint $table) {
            // กลุ่ม 1: Bot Personality / Style
            $this->safeAddColumn($table, 'fresh_market_settings', 'bot_name', function ($table) {
                $table->string('bot_name', 50)->default('พี่ตลาด')->after('ai_system_prompt');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'bot_personality', function ($table) {
                $table->text('bot_personality')->nullable()->after('bot_name');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'bot_response_style', function ($table) {
                $table->string('bot_response_style', 50)->default('friendly')->after('bot_personality');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'bot_temperature', function ($table) {
                $table->decimal('bot_temperature', 3, 2)->default(0.70)->after('bot_response_style');
            });

            // กลุ่ม 2: Greeting & Menu
            $this->safeAddColumn($table, 'fresh_market_settings', 'greeting_message_template', function ($table) {
                $table->text('greeting_message_template')->nullable()->after('bot_temperature');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'greeting_flex_enabled', function ($table) {
                $table->boolean('greeting_flex_enabled')->default(true)->after('greeting_message_template');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'menu_button_labels', function ($table) {
                $table->json('menu_button_labels')->nullable()->after('greeting_flex_enabled');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_enabled_in_idle', function ($table) {
                $table->boolean('ai_enabled_in_idle')->default(false)->after('menu_button_labels');
            });

            // กลุ่ม 3: AI Scope / Boundaries
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_scope_description', function ($table) {
                $table->text('ai_scope_description')->nullable()->after('ai_enabled_in_idle');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_allowed_topics', function ($table) {
                $table->json('ai_allowed_topics')->nullable()->after('ai_scope_description');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_blocked_topics', function ($table) {
                $table->json('ai_blocked_topics')->nullable()->after('ai_allowed_topics');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_off_topic_message', function ($table) {
                $table->text('ai_off_topic_message')->nullable()->after('ai_blocked_topics');
            });

            // กลุ่ม 4: AI Dynamic Buttons
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_suggest_buttons', function ($table) {
                $table->boolean('ai_can_suggest_buttons')->default(true)->after('ai_off_topic_message');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_max_buttons', function ($table) {
                $table->unsignedTinyInteger('ai_max_buttons')->default(4)->after('ai_can_suggest_buttons');
            });

            // กลุ่ม 5: Data Access Controls
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_access_listings', function ($table) {
                $table->boolean('ai_can_access_listings')->default(true)->after('ai_max_buttons');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_access_orders', function ($table) {
                $table->boolean('ai_can_access_orders')->default(false)->after('ai_can_access_listings');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_access_user_profile', function ($table) {
                $table->boolean('ai_can_access_user_profile')->default(false)->after('ai_can_access_orders');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_access_sellers', function ($table) {
                $table->boolean('ai_can_access_sellers')->default(false)->after('ai_can_access_user_profile');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_can_access_pricing', function ($table) {
                $table->boolean('ai_can_access_pricing')->default(true)->after('ai_can_access_sellers');
            });
            $this->safeAddColumn($table, 'fresh_market_settings', 'ai_max_context_messages', function ($table) {
                $table->unsignedTinyInteger('ai_max_context_messages')->default(10)->after('ai_can_access_pricing');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $columns = [
            'bot_name', 'bot_personality', 'bot_response_style', 'bot_temperature',
            'greeting_message_template', 'greeting_flex_enabled', 'menu_button_labels', 'ai_enabled_in_idle',
            'ai_scope_description', 'ai_allowed_topics', 'ai_blocked_topics', 'ai_off_topic_message',
            'ai_can_suggest_buttons', 'ai_max_buttons',
            'ai_can_access_listings', 'ai_can_access_orders', 'ai_can_access_user_profile',
            'ai_can_access_sellers', 'ai_can_access_pricing', 'ai_max_context_messages',
        ];

        $this->safeDropColumn('fresh_market_settings', $columns);
    }
};
