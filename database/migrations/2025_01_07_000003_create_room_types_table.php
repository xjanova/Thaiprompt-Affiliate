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
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Room Details
            $table->decimal('size_sqm', 8, 2)->nullable(); // Size in square meters
            $table->integer('max_adults')->default(2);
            $table->integer('max_children')->default(0);
            $table->integer('max_occupancy')->default(2);
            $table->integer('total_rooms')->default(1); // Number of this room type

            // Bed Configuration
            $table->json('bed_types')->nullable(); // e.g., [{"type": "King", "count": 1}, {"type": "Single", "count": 2}]

            // Pricing
            $table->decimal('base_price', 10, 2); // Base price per night
            $table->decimal('weekend_price', 10, 2)->nullable();
            $table->decimal('extra_adult_price', 10, 2)->default(0);
            $table->decimal('extra_child_price', 10, 2)->default(0);

            // Media
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();

            // Room Features & Amenities
            $table->json('amenities')->nullable(); // e.g., ["WiFi", "TV", "Mini Bar", "Safe"]
            $table->text('special_features')->nullable();

            // View Type
            $table->enum('view_type', ['city', 'sea', 'mountain', 'garden', 'pool', 'none'])->nullable();

            // Smoking Policy
            $table->enum('smoking_policy', ['non_smoking', 'smoking', 'both'])->default('non_smoking');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['hotel_id', 'is_active']);
            $table->index('slug');
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
