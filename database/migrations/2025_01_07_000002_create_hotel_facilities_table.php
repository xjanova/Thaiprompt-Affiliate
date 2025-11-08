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
        Schema::create('hotel_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->enum('category', ['general', 'room', 'recreation', 'food', 'business', 'safety'])->default('general');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot table for hotel and facilities
        Schema::create('hotel_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotel_facility_id')->constrained('hotel_facilities')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'hotel_facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_facility');
        Schema::dropIfExists('hotel_facilities');
    }
};
