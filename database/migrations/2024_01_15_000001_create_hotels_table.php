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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('hotel'); // hotel, resort, hostel, apartment, villa
            $table->integer('star_rating')->nullable(); // 1-5 stars

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Thailand');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Media
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->string('video_url')->nullable();

            // Policies
            $table->time('check_in_time')->default('14:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->text('cancellation_policy')->nullable();
            $table->text('house_rules')->nullable();
            $table->text('payment_methods')->nullable();

            // Amenities & Features
            $table->json('amenities')->nullable(); // Array of amenity IDs
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_wifi')->default(true);
            $table->boolean('has_pool')->default(false);
            $table->boolean('has_gym')->default(false);
            $table->boolean('has_restaurant')->default(false);
            $table->boolean('has_spa')->default(false);
            $table->boolean('pet_friendly')->default(false);

            // Status & SEO
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Statistics
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_bookings')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
