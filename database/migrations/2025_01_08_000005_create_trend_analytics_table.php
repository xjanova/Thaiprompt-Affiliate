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
        Schema::create('trend_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viral_trend_id')->constrained()->onDelete('cascade');
            $table->timestamp('snapshot_at');
            $table->integer('mention_count')->default(0);
            $table->integer('engagement_count')->default(0);
            $table->decimal('sentiment_score', 5, 2)->default(0); // -1 to 1
            $table->json('top_sources')->nullable();
            $table->json('demographics')->nullable();
            $table->json('time_distribution')->nullable(); // hourly distribution
            $table->timestamps();

            $table->index(['viral_trend_id', 'snapshot_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trend_analytics');
    }
};
