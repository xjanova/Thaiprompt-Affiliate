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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('learning_articles')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(70); // Percentage required to pass
            $table->integer('time_limit')->nullable(); // Time limit in minutes, null = unlimited
            $table->integer('max_attempts')->nullable(); // Max attempts allowed, null = unlimited
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_required')->default(false); // Required to complete course
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
