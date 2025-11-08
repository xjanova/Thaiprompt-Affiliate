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
        Schema::create('room_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('available_rooms'); // Number of rooms available for this date
            $table->decimal('price', 10, 2); // Price for this specific date
            $table->boolean('is_available')->default(true);
            $table->integer('min_stay')->default(1); // Minimum nights required
            $table->integer('max_stay')->nullable(); // Maximum nights allowed
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['room_type_id', 'date']);
            $table->index(['room_type_id', 'date', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_availability');
    }
};
