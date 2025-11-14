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
        Schema::create('food_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->comment('Link to existing products table');
            $table->string('food_passport_id', 100)->unique()->comment('Unique passport ID (e.g., FP-2025-001234)');

            // Origin Information
            $table->string('farm_name')->nullable();
            $table->json('farm_location')->nullable()->comment('{"lat": 0, "lng": 0, "address": ""}');
            $table->unsignedBigInteger('farmer_id')->comment('Link to users table');

            // Product Details
            $table->string('product_type', 100)->comment('vegetables, fruits, meat, seafood, etc.');
            $table->string('variety', 100)->comment('e.g., "Organic Rice", "Free-Range Chicken"');
            $table->date('harvest_date');
            $table->string('batch_number', 50);
            $table->decimal('estimated_quantity', 10, 2);
            $table->string('unit', 20)->comment('kg, pieces, liters');

            // Certifications
            $table->json('certifications')->nullable()->comment('["organic", "gmp", "haccp", "fairtrade"]');

            // Status
            $table->enum('current_stage', ['farm', 'processing', 'distribution', 'retail', 'consumed'])->default('farm');
            $table->string('blockchain_hash', 66)->nullable()->comment('Transaction hash on blockchain');
            $table->string('ipfs_hash', 100)->nullable()->comment('IPFS hash for documents');

            // Carbon Data (summary)
            $table->decimal('total_carbon_footprint', 10, 4)->nullable()->comment('kg CO2');
            $table->string('carbon_score', 5)->nullable()->comment('A+, A, B, C, D, E');

            // QR Code
            $table->string('qr_code_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('farmer_id')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('food_passport_id');
            $table->index('farmer_id');
            $table->index('product_type');
            $table->index('current_stage');
            $table->index('carbon_score');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_products');
    }
};
