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
        Schema::create('viral_trends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_keyword_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('viral_score', 10, 2);
            $table->integer('total_mentions')->default(0);
            $table->integer('total_engagement')->default(0);
            $table->decimal('velocity', 10, 2)->default(0); // mentions per hour
            $table->string('status')->default('rising'); // rising, peaked, declining, stable
            $table->timestamp('peak_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->json('sources')->nullable(); // array of source IDs
            $table->json('analytics')->nullable(); // detailed analytics data
            $table->json('generated_content')->nullable(); // AI generated content
            $table->timestamps();

            $table->index('viral_score');
            $table->index(['status', 'viral_score']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viral_trends');
    }
};
