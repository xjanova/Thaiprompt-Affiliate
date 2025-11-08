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
        Schema::create('tarot_card_back_images', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Image name
            $table->string('image_url'); // Image path
            $table->boolean('is_default')->default(false); // Default image
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('sort_order')->default(0); // Display order
            $table->timestamps();

            // Indexes
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_card_back_images');
    }
};
