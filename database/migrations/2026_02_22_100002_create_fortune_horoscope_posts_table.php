<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_horoscope_posts
 *
 * ติดตามการโพสลง Facebook/LINE
 * เก็บ platform response, analytics, error tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_horoscope_posts')) {
            return;
        }

        Schema::create('fortune_horoscope_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('fortune_horoscope_campaigns')->cascadeOnDelete();
            $table->date('target_date');
            $table->string('platform');
            $table->longText('post_content')->nullable();
            $table->json('image_urls')->nullable();

            // Platform Response
            $table->string('status')->default('pending');
            $table->string('platform_post_id')->nullable();
            $table->string('platform_post_url')->nullable();
            $table->json('platform_response')->nullable();

            // Analytics
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);

            // Error
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['campaign_id', 'target_date', 'platform'], 'horoscope_post_idx');
            $table->index(['status', 'target_date'], 'horoscope_post_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_horoscope_posts');
    }
};
