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
        Schema::create('trend_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->integer('frequency')->default(1);
            $table->decimal('trend_score', 10, 2)->default(0);
            $table->decimal('growth_rate', 10, 2)->default(0); // percentage
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->string('category')->nullable();
            $table->json('related_keywords')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('keyword');
            $table->index('trend_score');
            $table->index(['last_seen_at', 'trend_score']);
        });

        // Pivot table for trend_data and keywords
        Schema::create('trend_data_keyword', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_data_id')->constrained()->onDelete('cascade');
            $table->foreignId('trend_keyword_id')->constrained()->onDelete('cascade');
            $table->integer('weight')->default(1); // TF-IDF weight
            $table->timestamps();

            $table->unique(['trend_data_id', 'trend_keyword_id']);
            $table->index('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trend_data_keyword');
        Schema::dropIfExists('trend_keywords');
    }
};
