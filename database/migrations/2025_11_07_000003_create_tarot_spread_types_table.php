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
        Schema::create('tarot_spread_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // Spread name in English
            $table->string('name_th'); // Spread name in Thai
            $table->text('description_en')->nullable(); // Description in English
            $table->text('description_th')->nullable(); // Description in Thai
            $table->integer('card_count'); // Number of cards (3, 5, 10, etc.)
            $table->json('positions'); // Position definitions and meanings
            $table->string('image_url')->nullable(); // Spread layout image
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('sort_order')->default(0); // Display order
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('card_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_spread_types');
    }
};
