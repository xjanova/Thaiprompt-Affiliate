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
        Schema::create('generated_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->text('prompt');
            $table->longText('html_content');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->integer('views')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('slug');
            $table->index('created_at');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_pages');
    }
};
