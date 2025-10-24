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
        Schema::create('hotel_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable(); // FontAwesome or custom icon
            $table->string('category')->default('general'); // general, room, bathroom, kitchen, entertainment
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('hotel_bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Ratings (1-5 scale)
            $table->integer('overall_rating');
            $table->integer('cleanliness_rating')->nullable();
            $table->integer('service_rating')->nullable();
            $table->integer('location_rating')->nullable();
            $table->integer('facilities_rating')->nullable();
            $table->integer('value_rating')->nullable();

            // Review Content
            $table->string('title')->nullable();
            $table->text('comment');
            $table->json('pros')->nullable(); // Array of positive points
            $table->json('cons')->nullable(); // Array of negative points

            // Trip Details
            $table->string('travel_type')->nullable(); // solo, couple, family, business, friends
            $table->date('stay_date');

            // Moderation
            $table->boolean('is_verified')->default(false); // Verified booking
            $table->boolean('is_approved')->default(false);
            $table->text('admin_notes')->nullable();

            // Helpfulness
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);

            // Vendor Response
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_reviews');
        Schema::dropIfExists('hotel_amenities');
    }
};
