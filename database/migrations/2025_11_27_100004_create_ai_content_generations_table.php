<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_generations
 *
 * เก็บประวัติการสร้าง Content แต่ละครั้ง
 * รวมถึง Input, Output, และข้อมูล API Usage
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
        if (Schema::hasTable('ai_content_generations')) {
            return;
        }

        Schema::create('ai_content_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ความสัมพันธ์
            $table->foreignId('project_id')->constrained('ai_content_projects')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('ai_content_templates')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Input
            $table->text('system_prompt')->nullable();
            $table->text('user_prompt');
            $table->json('input_variables')->nullable();

            // Output
            $table->longText('generated_content')->nullable();
            $table->string('output_format', 20)->default('text');

            // AI Provider Info
            $table->string('ai_provider', 30);
            $table->string('ai_model', 50);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_tokens')->nullable();

            // Usage Stats
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);

            // Performance
            $table->unsignedInteger('generation_time_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // สถานะ
            $table->string('status', 20)->default('pending');
            // pending, processing, completed, failed, cancelled

            // Error Info
            $table->text('error_message')->nullable();
            $table->string('error_code', 50)->nullable();

            // User Feedback
            $table->tinyInteger('rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->boolean('is_selected')->default(false);

            // Version/Iteration
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'version'], 'acg_project_version_idx');
            $table->index(['user_id', 'created_at'], 'acg_user_date_idx');
            $table->index('status', 'acg_status_idx');
            $table->index('ai_provider', 'acg_provider_idx');
            $table->index('is_selected', 'acg_selected_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_generations');
    }
};
