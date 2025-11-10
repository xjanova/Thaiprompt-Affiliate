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
        Schema::create('bot_content_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('content_type', ['text', 'image', 'video', 'mixed'])->default('text');
            $table->text('content')->nullable(); // Template content with variables
            $table->json('media_urls')->nullable(); // Image/video URLs
            $table->json('hashtags')->nullable(); // Suggested hashtags
            $table->json('variables')->nullable(); // Template variables
            $table->string('category')->nullable(); // marketing, promotion, announcement, etc.
            $table->boolean('is_ai_generated')->default(false);
            $table->text('ai_prompt')->nullable(); // Prompt used for AI generation
            $table->boolean('is_public')->default(false); // Share with other users
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_content_templates');
    }
};
