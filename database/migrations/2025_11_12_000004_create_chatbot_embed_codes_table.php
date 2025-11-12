<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Embed Code System
     * - สร้างโค้ดสำหรับฝังบอทในเว็บไซต์
     * - รองรับหลายรูปแบบ (Widget, Popup, Inline)
     */
    public function up(): void
    {
        Schema::create('chatbot_embed_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');

            // Embed Configuration
            $table->string('name');                          // ชื่อ embed code
            $table->enum('embed_type', ['widget', 'popup', 'inline', 'fullscreen'])->default('widget');
            $table->string('unique_code', 32)->unique();     // รหัสเฉพาะสำหรับ embed

            // Appearance
            $table->json('appearance_config')->nullable();   // สี, ขนาด, ตำแหน่ง
            $table->string('widget_position')->default('bottom-right'); // bottom-right, bottom-left, etc.
            $table->string('primary_color')->default('#4F46E5');
            $table->string('secondary_color')->default('#ffffff');
            $table->integer('widget_size')->default(400);    // ความกว้าง (px)

            // Behavior
            $table->boolean('auto_open')->default(false);    // เปิดอัตโนมัติ
            $table->integer('auto_open_delay')->default(0);  // หน่วงเวลาเปิด (วินาที)
            $table->string('welcome_message')->nullable();   // ข้อความต้อนรับ
            $table->boolean('show_avatar')->default(true);
            $table->boolean('show_typing_indicator')->default(true);

            // Restrictions
            $table->json('allowed_domains')->nullable();     // โดเมนที่อนุญาต
            $table->json('blocked_ips')->nullable();         // IP ที่บล็อก
            $table->integer('rate_limit_per_minute')->default(60); // จำกัดข้อความต่อนาที

            // Features
            $table->boolean('enable_file_upload')->default(false);
            $table->boolean('enable_voice_input')->default(false);
            $table->boolean('enable_emoji')->default(true);
            $table->boolean('show_powered_by')->default(true); // แสดง "Powered by..."

            // Analytics
            $table->integer('total_installations')->default(0);
            $table->integer('total_conversations')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['bot_profile_id', 'is_active']);
            $table->index('unique_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_embed_codes');
    }
};
