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
        Schema::create('bot_social_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // TikTok, Facebook, LINE, Instagram, Twitter, etc.
            $table->string('code')->unique(); // tiktok, facebook, line, etc.
            $table->string('icon')->nullable(); // Icon class or URL
            $table->text('description')->nullable();
            $table->json('required_credentials')->nullable(); // Fields needed for auth
            $table->json('api_endpoints')->nullable(); // API endpoints configuration
            $table->boolean('is_active')->default(true);
            $table->integer('max_posts_per_day')->default(50);
            $table->integer('rate_limit_per_hour')->default(10);
            $table->json('supported_media_types')->nullable(); // image, video, text, etc.
            $table->json('post_settings')->nullable(); // Platform-specific settings
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_social_platforms');
    }
};
