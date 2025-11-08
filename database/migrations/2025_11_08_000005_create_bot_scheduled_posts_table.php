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
        Schema::create('bot_scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('bot_automations')->onDelete('cascade');
            $table->foreignId('platform_connection_id')->constrained('bot_platform_connections')->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'published', 'failed', 'cancelled'])->default('pending');

            $table->text('content')->nullable();
            $table->json('media_urls')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->string('location')->nullable();

            $table->timestamp('scheduled_for');
            $table->timestamp('published_at')->nullable();
            $table->string('platform_post_id')->nullable(); // ID from platform
            $table->string('platform_post_url')->nullable(); // Direct link to post

            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);

            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            $table->json('platform_response')->nullable(); // Full response from platform
            $table->json('analytics_data')->nullable(); // Engagement analytics

            $table->timestamps();
            $table->softDeletes();

            $table->index(['automation_id', 'status']);
            $table->index(['scheduled_for', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_scheduled_posts');
    }
};
