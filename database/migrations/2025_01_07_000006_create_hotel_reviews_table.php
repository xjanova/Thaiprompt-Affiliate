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
        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('hotel_bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Ratings (1-5 scale)
            $table->integer('overall_rating'); // Main rating
            $table->integer('cleanliness_rating')->nullable();
            $table->integer('staff_rating')->nullable();
            $table->integer('facilities_rating')->nullable();
            $table->integer('location_rating')->nullable();
            $table->integer('value_rating')->nullable(); // Value for money

            // Review Content
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('pros')->nullable(); // What they liked
            $table->text('cons')->nullable(); // What they didn't like

            // Guest Info
            $table->string('guest_name');
            $table->string('guest_type')->nullable(); // Solo, Couple, Family, Business
            $table->date('stayed_date')->nullable();

            // Images
            $table->json('images')->nullable();

            // Hotel Response
            $table->text('hotel_response')->nullable();
            $table->timestamp('hotel_response_date')->nullable();

            // Moderation
            $table->boolean('is_verified')->default(false); // Verified booking
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);

            // Helpfulness
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['hotel_id', 'is_approved']);
            $table->index(['user_id', 'booking_id']);
            $table->index('overall_rating');
        });

        // Helpfulness votes table
        Schema::create('hotel_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('hotel_reviews')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_helpful'); // true = helpful, false = not helpful
            $table->timestamps();

            $table->unique(['review_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_review_votes');
        Schema::dropIfExists('hotel_reviews');
    }
};
