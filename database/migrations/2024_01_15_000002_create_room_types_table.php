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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->string('name'); // Standard Room, Deluxe Room, Suite, etc.
            $table->string('slug');
            $table->text('description')->nullable();

            // Specifications
            $table->decimal('size_sqm', 8, 2)->nullable(); // Room size in square meters
            $table->string('bed_type')->nullable(); // Single, Double, Queen, King, Twin
            $table->integer('max_adults')->default(2);
            $table->integer('max_children')->default(1);
            $table->integer('max_occupancy')->default(3);

            // Pricing
            $table->decimal('base_price', 10, 2); // Base price per night
            $table->decimal('weekend_price', 10, 2)->nullable(); // Weekend surcharge
            $table->decimal('extra_bed_price', 10, 2)->default(0);
            $table->decimal('extra_person_price', 10, 2)->default(0);

            // Amenities
            $table->json('amenities')->nullable(); // TV, AC, Minibar, etc.
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_sea_view')->default(false);
            $table->boolean('has_city_view')->default(false);
            $table->boolean('has_bathtub')->default(false);
            $table->boolean('has_kitchen')->default(false);

            // Media
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hotel_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
