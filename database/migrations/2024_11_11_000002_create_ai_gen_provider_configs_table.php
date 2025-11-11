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
        Schema::create('ai_gen_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_gen_providers')->onDelete('cascade');
            $table->string('config_key'); // api_key, api_endpoint, etc.
            $table->text('config_value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'config_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_provider_configs');
    }
};
