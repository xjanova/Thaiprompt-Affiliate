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
        Schema::create('carbon_credits', function (Blueprint $table) {
            $table->id();

            // Owner
            $table->unsignedBigInteger('user_id')->comment('Farmer or producer');
            $table->unsignedBigInteger('food_product_id')->nullable()->comment('Specific product that earned credits');

            // Credit Details
            $table->decimal('credit_amount', 10, 4)->comment('Amount of carbon credits (in tons CO2)');
            $table->enum('credit_type', ['reduction', 'sequestration', 'avoidance', 'removal']);

            // Earning Criteria
            $table->decimal('baseline_emission', 10, 4)->comment('Standard emission for this product type');
            $table->decimal('actual_emission', 10, 4)->comment('Actual emission achieved');
            $table->decimal('reduction_percentage', 5, 2)->comment('% reduction');

            // Valuation
            $table->decimal('credit_value_per_ton', 10, 2)->comment('THB per ton CO2');
            $table->decimal('total_value', 12, 2)->comment('Total value in THB');

            // Validity
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'traded', 'retired', 'expired'])->default('active');

            // Trading
            $table->boolean('tradeable')->default(true);
            $table->unsignedBigInteger('traded_to_user_id')->nullable();
            $table->timestamp('traded_at')->nullable();
            $table->decimal('trade_price', 12, 2)->nullable();

            // Verification
            $table->string('verified_by')->nullable()->comment('Verification organization');
            $table->string('verification_standard', 100)->nullable()->comment('e.g., "VCS", "Gold Standard"');
            $table->string('certificate_number', 100)->nullable();

            // Blockchain
            $table->string('blockchain_hash', 66)->nullable();
            $table->string('token_id', 100)->nullable()->comment('If tokenized as NFT');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('set null');
            $table->foreign('traded_to_user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('user_id');
            $table->index('issued_date');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_credits');
    }
};
