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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->string('model_identifier');                     // 'gpt-4', 'claude-3-opus', 'deepseek-chat'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('context_window')->nullable();          // 8192, 32768, 128000
            $table->integer('max_output_tokens')->nullable();
            $table->boolean('supports_functions')->default(false);
            $table->boolean('supports_vision')->default(false);
            $table->boolean('supports_streaming')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('pricing')->nullable();                    // Override provider pricing
            $table->timestamps();

            $table->index(['provider_id', 'is_active']);
            $table->unique(['provider_id', 'model_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
