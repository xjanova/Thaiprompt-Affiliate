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
        Schema::create('trend_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_source_id')->constrained()->onDelete('cascade');
            $table->text('title')->nullable();
            $table->text('content')->nullable();
            $table->text('url')->nullable();
            $table->string('author')->nullable();
            $table->integer('engagement_count')->default(0); // likes, shares, views
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->decimal('viral_score', 10, 2)->default(0); // calculated score
            $table->json('raw_data')->nullable(); // original scraped data
            $table->json('extracted_keywords')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scraped_at')->useCurrent();
            $table->timestamps();

            $table->index(['trend_source_id', 'scraped_at']);
            $table->index('viral_score');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trend_data');
    }
};
