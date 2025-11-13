<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Auto Content Posting System
     * - บอทคิดและโพสต์คอนเทนต์อัตโนมัติ
     * - รองรับหลายแพลตฟอร์ม
     */
    public function up(): void
    {
        Schema::create('chatbot_auto_content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('ai_bot_profiles')->onDelete('cascade');

            // Content Generation Settings
            $table->string('topic')->nullable();             // หัวข้อที่ต้องการให้สร้าง
            $table->text('content_prompt');                  // Prompt สำหรับสร้างคอนเทนต์
            $table->json('content_guidelines')->nullable();  // แนวทางในการสร้าง (tone, style, length)

            // Generated Content
            $table->text('generated_content')->nullable();   // คอนเทนต์ที่ AI สร้างขึ้น
            $table->json('generated_media')->nullable();     // รูปภาพ/วิดีโอ ที่สร้างมา
            $table->json('hashtags')->nullable();            // #hashtags

            // Posting Schedule
            $table->timestamp('scheduled_at')->nullable();   // เวลาที่ต้องการโพสต์
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly'])->default('once');
            $table->json('post_days')->nullable();           // วันที่ต้องการโพสต์ [1,2,3,4,5]
            $table->time('post_time')->nullable();           // เวลาที่โพสต์

            // Target Platforms
            $table->json('target_platforms');                // ['line', 'facebook', 'instagram', ...]
            $table->json('platform_specific_config')->nullable(); // การตั้งค่าเฉพาะแต่ละแพลตฟอร์ม

            // Status & Results
            $table->enum('status', ['draft', 'scheduled', 'processing', 'posted', 'failed'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->json('post_results')->nullable();        // ผลลัพธ์จากแต่ละแพลตฟอร์ม
            $table->text('error_message')->nullable();

            // Engagement Tracking
            $table->integer('total_views')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_shares')->default(0);

            // Auto-regenerate
            $table->boolean('auto_regenerate')->default(false); // สร้างคอนเทนต์ใหม่อัตโนมัติ

            $table->timestamps();

            // Indexes
            $table->index(['bot_profile_id', 'status']);
            $table->index('scheduled_at');
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_auto_content_posts');
    }
};
