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
        Schema::create('ai_bot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->foreignId('model_id')->constrained('ai_models')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // System Prompt Configuration
            $table->text('system_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.7);     // 0.0 - 2.0
            $table->decimal('top_p', 3, 2)->default(1.0);
            $table->integer('max_tokens')->default(2000);
            $table->decimal('presence_penalty', 3, 2)->default(0);
            $table->decimal('frequency_penalty', 3, 2)->default(0);

            // Response Boundaries
            $table->json('response_scope')->nullable();              // {topics: [], forbidden_topics: [], language: 'th'}
            $table->json('allowed_domains')->nullable();             // URLs/domains allowed
            $table->integer('max_conversation_length')->default(10);

            // Features
            $table->boolean('enable_knowledge_base')->default(false);
            $table->boolean('enable_web_search')->default(false);
            $table->boolean('enable_function_calling')->default(false);
            $table->boolean('enable_memory')->default(true);

            // Rental Settings
            $table->boolean('is_rentable')->default(false);
            $table->decimal('rental_price_per_month', 10, 2)->nullable();
            $table->decimal('rental_price_per_message', 10, 4)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();    // % commission

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);            // Show in marketplace

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'is_active']);
            $table->index(['is_public', 'is_rentable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_bot_profiles');
    }
};
