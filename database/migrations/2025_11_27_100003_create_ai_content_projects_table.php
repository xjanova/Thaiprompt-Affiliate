<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_projects
 *
 * เก็บโปรเจกต์ Content ที่ผู้ใช้สร้าง
 * สามารถมีหลาย Generations ในโปรเจกต์เดียว
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_content_projects')) {
            return;
        }

        Schema::create('ai_content_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ความสัมพันธ์
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('ai_content_templates')->nullOnDelete();

            // ข้อมูลโปรเจกต์
            $table->string('name');
            $table->text('description')->nullable();

            // ประเภท Content
            $table->string('content_type', 50)->default('general');

            // Input Variables (JSON)
            $table->json('input_variables')->nullable();

            // Content ที่เลือกใช้ (จาก generations)
            $table->text('selected_content')->nullable();

            // การตั้งค่า AI
            $table->string('ai_provider', 30)->default('openai');
            $table->string('ai_model', 50)->nullable();

            // สถานะ
            $table->string('status', 30)->default('draft');
            // draft, generating, completed, failed, archived

            // Folder/Organization
            $table->string('folder')->nullable();
            $table->json('tags')->nullable();

            // Favorite
            $table->boolean('is_favorite')->default(false);

            // สถิติ
            $table->unsignedInteger('generations_count')->default(0);
            $table->unsignedInteger('words_count')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status'], 'acp_user_status_idx');
            $table->index('content_type', 'acp_type_idx');
            $table->index('folder', 'acp_folder_idx');
            $table->index('is_favorite', 'acp_favorite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_projects');
    }
};
