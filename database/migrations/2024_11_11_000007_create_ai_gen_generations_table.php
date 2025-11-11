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
        Schema::create('ai_gen_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('ai_gen_providers')->onDelete('cascade');
            $table->foreignId('usage_log_id')->nullable()->constrained('ai_gen_usage_logs')->onDelete('set null');

            // Generation info
            $table->string('type'); // image, video
            $table->text('prompt');
            $table->json('settings')->nullable();

            // File info
            $table->string('file_path')->nullable();
            $table->string('file_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // for video in seconds

            // External references
            $table->string('external_id')->nullable(); // ID from provider
            $table->json('external_data')->nullable();

            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // User actions
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['provider_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_generations');
    }
};
