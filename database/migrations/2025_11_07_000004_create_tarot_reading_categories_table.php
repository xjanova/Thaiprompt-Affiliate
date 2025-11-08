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
        Schema::create('tarot_reading_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // Category name in English
            $table->string('name_th'); // Category name in Thai
            $table->string('slug')->unique(); // URL-friendly slug
            $table->text('description_en')->nullable(); // Description in English
            $table->text('description_th')->nullable(); // Description in Thai
            $table->string('icon')->nullable(); // Icon class or URL
            $table->string('color')->nullable(); // Color hex code
            $table->decimal('price', 10, 2)->default(0); // Price for this category (0 = free)
            $table->boolean('is_free_first')->default(true); // Free first time per day
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('sort_order')->default(0); // Display order
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_reading_categories');
    }
};
