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
        Schema::create('hotel_special_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique()->nullable(); // Promo code
            $table->text('description')->nullable();

            // Offer Type
            $table->enum('discount_type', ['percentage', 'fixed', 'free_night'])->default('percentage');
            $table->decimal('discount_value', 10, 2); // Percentage or fixed amount
            $table->integer('free_nights')->default(0); // For "Stay 3 Pay 2" offers

            // Validity
            $table->date('valid_from');
            $table->date('valid_until');
            $table->json('applicable_days')->nullable(); // e.g., ["monday", "tuesday"]

            // Conditions
            $table->integer('min_nights')->default(1);
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->integer('max_usage')->nullable(); // Maximum number of times this offer can be used
            $table->integer('used_count')->default(0);

            // Room Type Restrictions
            $table->json('room_type_ids')->nullable(); // Applicable room types

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Banner
            $table->string('banner_image')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['hotel_id', 'is_active']);
            $table->index('code');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_special_offers');
    }
};
