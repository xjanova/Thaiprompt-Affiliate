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
        if (Schema::hasTable('ai_installation_logs')) {
            echo "Table 'ai_installation_logs' already exists, skipping creation.\n";
            return;
        }

        Schema::create('ai_installation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('installation_type', ['deepseek', 'ollama', 'custom']);

            // Progress
            $table->enum('status', ['pending', 'downloading', 'installing', 'configuring', 'completed', 'failed'])->default('pending');
            $table->integer('progress_percentage')->default(0);
            $table->string('current_step')->nullable();
            $table->integer('total_steps')->nullable();

            // Installation Details
            $table->string('server_ip', 45)->nullable();
            $table->integer('server_port')->nullable();
            $table->string('install_path', 500)->nullable();
            $table->string('model_size', 50)->nullable();            // '7B', '13B', '70B'
            $table->boolean('gpu_enabled')->default(false);

            // Configuration
            $table->json('config')->nullable();

            // Logs
            $table->longText('log_output')->nullable();
            $table->text('error_log')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_installation_logs');
    }
};
