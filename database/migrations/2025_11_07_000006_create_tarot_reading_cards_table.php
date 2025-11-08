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
        Schema::create('tarot_reading_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_id')->constrained('tarot_readings')->onDelete('cascade');
            $table->foreignId('card_id')->constrained('tarot_cards')->onDelete('cascade');
            $table->integer('position'); // Position in the spread (1, 2, 3, etc.)
            $table->string('position_name')->nullable(); // Position name (Past, Present, Future, etc.)
            $table->boolean('is_reversed')->default(false); // Card orientation
            $table->text('interpretation')->nullable(); // Specific interpretation for this card in this position
            $table->timestamps();

            // Indexes
            $table->index(['reading_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarot_reading_cards');
    }
};
