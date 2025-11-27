<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับตาราง ai_content_usage_logs
 *
 * เก็บ Log การใช้งาน API ทั้งหมด
 * สำหรับติดตาม Usage, Cost, และ Rate Limiting
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
        if (Schema::hasTable('ai_content_usage_logs')) {
            return;
        }

        Schema::create('ai_content_usage_logs', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('generation_id')->nullable()->constrained('ai_content_generations')->nullOnDelete();

            // API Info
            $table->string('ai_provider', 30);
            $table->string('ai_model', 50);
            $table->string('endpoint', 100)->nullable();

            // Request Info
            $table->string('request_type', 30)->default('completion');
            // completion, chat, embedding, moderation

            // Usage
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Cost
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->decimal('cost_thb', 10, 4)->default(0);

            // Performance
            $table->unsignedInteger('response_time_ms')->nullable();

            // Status
            $table->boolean('is_success')->default(true);
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            // Request Metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes (optimized for analytics queries)
            $table->index(['user_id', 'created_at'], 'acul_user_date_idx');
            $table->index(['ai_provider', 'created_at'], 'acul_provider_date_idx');
            $table->index(['created_at', 'is_success'], 'acul_date_success_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_usage_logs');
    }
};
