<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_templates
 *
 * เก็บเทมเพลต Content ที่สร้างไว้ล่วงหน้า
 * เช่น Blog Post, Video Script, Social Caption
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_content_templates')) {
            return;
        }

        Schema::create('ai_content_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ข้อมูลพื้นฐาน
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // ประเภท Content
            $table->string('content_type', 50)->default('general');
            // general, blog_post, video_script, youtube_title, youtube_description,
            // social_caption, email, ad_copy, product_description, seo_meta

            // หมวดหมู่
            $table->string('category', 50)->nullable();

            // System Prompt และ User Prompt Template
            $table->text('system_prompt')->nullable();
            $table->text('user_prompt_template');

            // ตัวแปรที่ใช้ใน Template (JSON array)
            $table->json('variables')->nullable();
            // [{"name": "topic", "label": "หัวข้อ", "type": "text", "required": true}]

            // การตั้งค่า AI
            $table->string('default_provider', 30)->default('openai');
            $table->string('default_model', 50)->nullable();
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_tokens')->default(2000);

            // Output Settings
            $table->string('output_format', 20)->default('text');
            // text, markdown, html, json
            $table->string('language', 10)->default('th');
            $table->string('tone', 30)->nullable();
            // professional, casual, friendly, formal, humorous

            // ไอคอนและสี
            $table->string('icon', 50)->nullable();
            $table->string('color', 30)->nullable();

            // ผู้สร้าง
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_premium')->default(false);

            // สถิติการใช้งาน
            $table->unsignedInteger('usage_count')->default(0);

            // การเรียงลำดับ
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('content_type', 'act_type_idx');
            $table->index('category', 'act_category_idx');
            $table->index(['is_active', 'sort_order'], 'act_active_sort_idx');
            $table->index('is_featured', 'act_featured_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_templates');
    }
};
