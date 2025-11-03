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
        if (Schema::hasTable('ai_messages')) {
            echo "Table 'ai_messages' already exists, skipping creation.\n";
            return;
        }

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->enum('role', ['system', 'user', 'assistant', 'function']);
            $table->text('content');

            // Metadata
            $table->integer('tokens_used')->nullable();
            $table->string('model_used')->nullable();
            $table->string('provider_used')->nullable();
            $table->integer('response_time_ms')->nullable();        // Response time in milliseconds

            // Function Calling
            $table->string('function_name')->nullable();
            $table->json('function_arguments')->nullable();
            $table->json('function_response')->nullable();

            // Context
            $table->json('context_used')->nullable();                // Retrieved knowledge chunks

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
