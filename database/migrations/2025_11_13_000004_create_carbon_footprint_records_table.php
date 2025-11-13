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
        Schema::create('carbon_footprint_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_product_id');
            $table->unsignedBigInteger('journey_stage_id')->nullable()->comment('Which stage this emission is from');

            // Emission Source
            $table->enum('emission_category', ['farming', 'processing', 'transportation', 'storage', 'packaging', 'retail', 'waste']);
            $table->string('emission_source')->comment('Specific source description');

            // Calculation Data
            $table->json('activity_data')->nullable()->comment('Raw data used for calculation');

            $table->decimal('emission_factor', 10, 6)->comment('kg CO2 per unit');
            $table->string('emission_factor_source')->nullable()->comment('e.g., "IPCC 2023", "Thai EPA"');

            // Results
            $table->decimal('co2_emission', 10, 4)->comment('kg CO2');
            $table->decimal('ch4_emission', 10, 4)->nullable()->comment('kg CH4 (methane)');
            $table->decimal('n2o_emission', 10, 4)->nullable()->comment('kg N2O (nitrous oxide)');
            $table->decimal('total_co2_equivalent', 10, 4)->comment('Total in CO2 equivalent');

            // Credits & Offsets
            $table->decimal('carbon_offset_applied', 10, 4)->nullable()->comment('kg CO2');
            $table->string('offset_source')->nullable()->comment('e.g., "Solar power", "Reforestation"');
            $table->decimal('net_emission', 10, 4)->comment('After offsets');

            // Calculation Metadata
            $table->string('calculation_method', 100)->nullable();
            $table->timestamp('calculation_date');
            $table->unsignedBigInteger('verified_by_id')->nullable()->comment('Verifier user');
            $table->enum('verification_status', ['pending', 'verified', 'disputed'])->default('pending');

            // Documentation
            $table->json('supporting_documents')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('cascade');
            $table->foreign('journey_stage_id')->references('id')->on('product_journey')->onDelete('set null');
            $table->foreign('verified_by_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('emission_category');
            $table->index('food_product_id');
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_footprint_records');
    }
};
