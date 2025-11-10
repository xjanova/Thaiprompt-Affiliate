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
        Schema::create('bot_automation_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');
            $table->foreignId('scheduled_post_id')->nullable()->constrained('bot_scheduled_posts')->onDelete('set null');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('execution_type', ['scheduled', 'manual', 'retry'])->default('scheduled');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable(); // Duration in milliseconds

            $table->text('input_data')->nullable(); // What was input
            $table->text('output_data')->nullable(); // What was generated/posted
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->text('stack_trace')->nullable();

            $table->integer('tokens_used')->nullable(); // For AI generations
            $table->decimal('cost', 10, 4)->nullable(); // Execution cost
            $table->integer('platforms_attempted')->default(0);
            $table->integer('platforms_succeeded')->default(0);
            $table->integer('platforms_failed')->default(0);

            $table->json('metadata')->nullable(); // Additional execution metadata

            $table->timestamps();

            $table->index(['automation_id', 'status']);
            $table->index(['started_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_automation_executions');
    }
};
