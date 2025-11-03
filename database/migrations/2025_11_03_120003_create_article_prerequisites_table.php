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
        Schema::create('article_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('learning_articles')
                ->onDelete('cascade')
                ->comment('The article that requires prerequisites');
            $table->foreignId('prerequisite_article_id')
                ->constrained('learning_articles')
                ->onDelete('cascade')
                ->comment('The article that must be completed first');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['article_id', 'prerequisite_article_id'], 'unique_article_prerequisite');
            $table->index('article_id');
            $table->index('prerequisite_article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_prerequisites');
    }
};
