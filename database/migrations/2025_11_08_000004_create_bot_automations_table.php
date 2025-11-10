<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_bot_profile_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('automation_type', [
                'scheduled_post',      // โพสตามเวลา
                'customer_support',    // ตอบคำถามซัพพอร์ต
                'sales_assistant',     // ปิดการขาย/แนะนำสินค้า
                'engagement',          // ตอบกลับคอมเมนต์/ข้อความ
                'analytics'            // รายงานผล
            ])->default('scheduled_post');

            $table->enum('trigger_type', ['schedule', 'event', 'webhook', 'manual'])->default('schedule');
            $table->enum('schedule_type', ['once', 'hourly', 'daily', 'weekly', 'monthly', 'cron'])->nullable();
            $table->string('cron_expression')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // For 'once' type
            $table->json('schedule_config')->nullable(); // Days of week, time, etc.

            $table->enum('content_source', ['custom', 'template', 'ai_generated'])->default('custom');
            $table->foreignId('template_id')->nullable()->constrained('bot_content_templates')->onDelete('set null');
            $table->text('custom_content')->nullable();
            $table->text('ai_generation_prompt')->nullable();

            $table->json('target_platforms')->nullable(); // Array of platform IDs
            $table->json('settings')->nullable(); // Additional settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_rental')->default(false); // Is this a rental bot?
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            $table->integer('total_executions')->default(0);
            $table->integer('successful_executions')->default(0);
            $table->integer('failed_executions')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'automation_type']);
            $table->index(['is_active', 'next_execution_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_automations');
    }
};
