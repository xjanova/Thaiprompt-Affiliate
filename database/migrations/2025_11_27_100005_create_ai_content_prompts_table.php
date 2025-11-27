<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_prompts
 *
 * เก็บ Custom Prompts ที่ผู้ใช้สร้างขึ้น
 * สามารถแชร์และนำกลับมาใช้ซ้ำได้
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_content_prompts')) {
            return;
        }

        Schema::create('ai_content_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ผู้สร้าง
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // ข้อมูล Prompt
            $table->string('name');
            $table->text('description')->nullable();

            // Prompt Content
            $table->text('system_prompt')->nullable();
            $table->text('user_prompt');

            // ประเภทและหมวดหมู่
            $table->string('content_type', 50)->default('general');
            $table->string('category', 50)->nullable();
            $table->json('tags')->nullable();

            // ตัวแปร
            $table->json('variables')->nullable();

            // การตั้งค่า AI แนะนำ
            $table->string('recommended_provider', 30)->nullable();
            $table->string('recommended_model', 50)->nullable();
            $table->decimal('recommended_temperature', 3, 2)->nullable();

            // การแชร์
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);

            // สถิติ
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();

            // สถานะ
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_active'], 'acpr_user_active_idx');
            $table->index(['is_public', 'is_approved'], 'acpr_public_idx');
            $table->index('content_type', 'acpr_type_idx');
            $table->index('category', 'acpr_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_prompts');
    }
};
