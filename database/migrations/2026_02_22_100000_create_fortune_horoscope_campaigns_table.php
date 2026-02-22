<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_horoscope_campaigns
 *
 * แคมเปญโพสดวงรายวันอัตโนมัติ
 * Admin ตั้งค่า AI provider, prompt, เวลาโพส, platform
 * ระบบจะสร้างเนื้อหา + โพสลง Facebook/LINE อัตโนมัติ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_horoscope_campaigns')) {
            return;
        }

        Schema::create('fortune_horoscope_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Platform
            $table->boolean('post_to_facebook')->default(false);
            $table->boolean('post_to_line')->default(false);
            $table->boolean('use_fortune_settings_tokens')->default(true);
            $table->string('facebook_page_id')->nullable();
            $table->text('facebook_page_token')->nullable();
            $table->text('line_channel_access_token')->nullable();

            // AI Text
            $table->string('ai_text_provider')->default('auto');
            $table->text('text_prompt_template')->nullable();

            // AI Image
            $table->string('ai_image_provider')->default('pollinations');
            $table->string('ai_image_model')->default('flux');
            $table->string('image_size')->default('1024x1024');
            $table->string('image_style')->nullable();
            $table->text('image_prompt_template')->nullable();

            // Schedule
            $table->string('schedule_time')->default('06:00');
            $table->string('timezone')->default('Asia/Bangkok');
            $table->json('active_days')->nullable();
            $table->json('target_birth_days')->nullable();

            // Content Options
            $table->boolean('include_image')->default(true);
            $table->boolean('include_lucky_info')->default(true);
            $table->string('post_format')->default('combined');
            $table->text('post_header_template')->nullable();
            $table->text('post_footer_template')->nullable();

            // Status
            $table->string('status')->default('draft');
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('last_posted_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_horoscope_campaigns');
    }
};
