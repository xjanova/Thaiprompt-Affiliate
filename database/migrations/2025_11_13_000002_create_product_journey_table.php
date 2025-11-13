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
        Schema::create('product_journey', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_product_id');

            // Stage Information
            $table->enum('stage', ['farm', 'processing', 'storage', 'distribution', 'retail', 'transport']);
            $table->string('stage_name', 100)->comment('Human-readable name');
            $table->integer('sequence_order')->comment('Order in journey');

            // Location & Time
            $table->string('location_name');
            $table->json('location_coordinates')->nullable()->comment('{"lat": 0, "lng": 0}');
            $table->timestamp('arrived_at');
            $table->timestamp('departed_at')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable()->comment('Auto-calculated');

            // Handler Information
            $table->unsignedBigInteger('handler_id')->nullable();
            $table->string('handler_name');
            $table->enum('handler_type', ['farmer', 'processor', 'distributor', 'retailer', 'transporter']);
            $table->string('organization')->nullable();

            // Environmental Data
            $table->json('temperature_range')->nullable()->comment('{"min": 0, "max": 0, "avg": 0}');
            $table->json('humidity_range')->nullable()->comment('{"min": 0, "max": 0, "avg": 0}');
            $table->text('storage_conditions')->nullable();

            // Transportation
            $table->string('transport_method', 50)->nullable()->comment('truck, ship, plane, train');
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->string('fuel_type', 50)->nullable();

            // Carbon Impact
            $table->decimal('stage_carbon_emission', 10, 4)->nullable()->comment('kg CO2 for this stage');

            // Quality Status
            $table->enum('quality_status', ['excellent', 'good', 'acceptable', 'poor'])->default('good');

            // Documentation
            $table->text('notes')->nullable();
            $table->json('photos')->nullable()->comment('Array of photo URLs');
            $table->json('documents')->nullable()->comment('Array of document URLs');

            // Blockchain
            $table->string('blockchain_hash', 66)->nullable();
            $table->string('ipfs_hash', 100)->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('cascade');
            $table->foreign('handler_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('stage');
            $table->index(['food_product_id', 'sequence_order']);
            $table->index('handler_id');
            $table->index('arrived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_journey');
    }
};
