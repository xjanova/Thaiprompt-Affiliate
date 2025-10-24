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
        Schema::create('hotel_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->onDelete('cascade');

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Discount
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_nights']);
            $table->decimal('discount_value', 10, 2);
            $table->integer('free_nights')->default(0); // For "Stay 3, Pay 2" promotions

            // Conditions
            $table->integer('min_nights')->default(1);
            $table->integer('max_nights')->nullable();
            $table->integer('min_rooms')->default(1);
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->integer('max_uses')->nullable(); // Total usage limit
            $table->integer('max_uses_per_user')->default(1);
            $table->integer('current_uses')->default(0);

            // Validity
            $table->date('valid_from');
            $table->date('valid_to');
            $table->json('blackout_dates')->nullable(); // Dates when promo is not valid
            $table->json('applicable_days')->nullable(); // [0,1,2,3,4,5,6] Sunday=0

            // Advanced Settings
            $table->boolean('is_public')->default(true); // Public or private code
            $table->boolean('combinable')->default(false); // Can be combined with other promos
            $table->integer('advance_booking_days')->nullable(); // Must book X days in advance
            $table->string('target_market')->nullable(); // domestic, international, all

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // For multiple applicable promotions

            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index(['valid_from', 'valid_to']);
        });

        Schema::create('hotel_promotion_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('hotel_promotions')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('hotel_bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_promotion_usage');
        Schema::dropIfExists('hotel_promotions');
    }
};
