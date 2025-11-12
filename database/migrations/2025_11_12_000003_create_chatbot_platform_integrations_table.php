<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Multi-Platform Integration System
     * - เชื่อมต่อบอทกับหลายแพลตฟอร์ม
     * - รองรับ LINE, Facebook, Instagram, Telegram, Discord, etc.
     */
    public function up(): void
    {
        Schema::create('chatbot_platform_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');

            // Platform Info
            $table->enum('platform_type', [
                'line',
                'facebook',
                'instagram',
                'telegram',
                'discord',
                'whatsapp',
                'twitter',
                'messenger',
                'slack',
                'web_widget',
                'custom_api'
            ]);
            $table->string('platform_name')->nullable();     // ชื่อที่ตั้งเอง

            // Authentication & Configuration
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('platform_credentials')->nullable(); // ข้อมูลการเชื่อมต่อเฉพาะแต่ละแพลตฟอร์ม

            // LINE Specific
            $table->string('line_channel_id')->nullable();
            $table->text('line_channel_secret')->nullable();
            $table->text('line_channel_access_token')->nullable();

            // Facebook/Instagram Specific
            $table->string('fb_page_id')->nullable();
            $table->string('fb_app_id')->nullable();
            $table->text('fb_app_secret')->nullable();

            // Telegram Specific
            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_username')->nullable();

            // Discord Specific
            $table->string('discord_bot_token')->nullable();
            $table->string('discord_client_id')->nullable();

            // Features & Settings
            $table->json('enabled_features')->nullable();    // ['auto_reply', 'keyword_match', 'ai_fallback']
            $table->json('platform_settings')->nullable();   // การตั้งค่าเฉพาะแพลตฟอร์ม

            // Message Handling
            $table->boolean('auto_reply_enabled')->default(true);
            $table->boolean('keyword_matching_enabled')->default(true);
            $table->boolean('ai_fallback_enabled')->default(true);
            $table->integer('response_delay_ms')->default(0); // หน่วงเวลาตอบ (มิลลิวินาที)

            // Room/Channel Management
            $table->json('allowed_rooms')->nullable();       // ห้องที่อนุญาตให้ทำงาน
            $table->json('blocked_rooms')->nullable();       // ห้องที่ไม่อนุญาต
            $table->boolean('work_in_all_rooms')->default(true);

            // Admin Features
            $table->boolean('can_post_content')->default(false); // สามารถโพสต์คอนเทนต์ได้
            $table->json('admin_rooms')->nullable();         // ห้องที่เป็นแอดมิน

            // Status & Monitoring
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);  // ยืนยันการเชื่อมต่อแล้ว
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->text('connection_error')->nullable();

            // Usage Statistics
            $table->integer('total_messages_received')->default(0);
            $table->integer('total_messages_sent')->default(0);
            $table->integer('total_users')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['bot_profile_id', 'platform_type']);
            $table->index(['bot_profile_id', 'is_active']);
            $table->index('platform_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_platform_integrations');
    }
};
