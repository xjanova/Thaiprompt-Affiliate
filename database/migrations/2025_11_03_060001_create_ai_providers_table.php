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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                       // 'openai', 'claude', 'deepseek', 'gemini'
            $table->string('display_name');                         // 'OpenAI GPT-4', 'Claude 3 Opus'
            $table->enum('provider_type', ['cloud', 'self-hosted'])->default('cloud');
            $table->string('api_endpoint', 500)->nullable();        // API endpoint URL
            $table->string('api_version', 50)->nullable();          // v1, v2, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);         // Available for use
            $table->json('config')->nullable();                     // Provider-specific config
            $table->json('pricing')->nullable();                    // {input_tokens: 0.01, output_tokens: 0.03}
            $table->timestamps();

            $table->index(['is_active', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
