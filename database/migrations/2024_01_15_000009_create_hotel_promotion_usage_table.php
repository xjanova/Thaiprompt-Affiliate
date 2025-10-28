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
        // This table is created after both hotel_bookings and hotel_promotions exist
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
    }
};
