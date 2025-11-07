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
        Schema::create('tarot_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // Card name in English
            $table->string('name_th'); // Card name in Thai
            $table->enum('type', ['major_arcana', 'minor_arcana']); // Card type
            $table->string('suit')->nullable(); // Suit for minor arcana: wands, cups, swords, pentacles
            $table->integer('number')->nullable(); // Card number (0-21 for major, 1-14 for minor)
            $table->text('description_en')->nullable(); // English description
            $table->text('description_th')->nullable(); // Thai description
            $table->text('upright_meaning_en')->nullable(); // Upright meaning in English
            $table->text('upright_meaning_th')->nullable(); // Upright meaning in Thai
            $table->text('reversed_meaning_en')->nullable(); // Reversed meaning in English
            $table->text('reversed_meaning_th')->nullable(); // Reversed meaning in Thai
            $table->string('image_url')->nullable(); // Card front image
            $table->json('keywords_en')->nullable(); // Keywords in English
            $table->json('keywords_th')->nullable(); // Keywords in Thai
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();

            // Indexes
            $table->index(['type', 'suit']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_cards');
    }
};
