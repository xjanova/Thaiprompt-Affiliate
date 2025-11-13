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
        Schema::create('consumer_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_product_id');

            // Scanner Information
            $table->unsignedBigInteger('user_id')->nullable()->comment('If logged in');
            $table->string('session_id', 100)->nullable()->comment('Anonymous session');

            // Scan Details
            $table->timestamp('scanned_at');
            $table->json('scan_location')->nullable()->comment('{"lat": 0, "lng": 0} from device');
            $table->json('device_info')->nullable()->comment('{"type": "", "os": "", "browser": ""}');

            // Engagement
            $table->boolean('viewed_journey')->default(false);
            $table->boolean('viewed_quality')->default(false);
            $table->boolean('viewed_carbon')->default(false);
            $table->integer('time_spent_seconds')->nullable();

            // Feedback
            $table->tinyInteger('consumer_rating')->nullable()->comment('1-5 stars');
            $table->text('feedback')->nullable();

            // Verification
            $table->boolean('verified_purchase')->default(false);
            $table->string('purchase_receipt_url', 500)->nullable();

            // Foreign Keys
            $table->foreign('food_product_id')->references('id')->on('food_products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('scanned_at');
            $table->index('food_product_id');
            $table->index('user_id');
            $table->index(['food_product_id', 'scanned_at']);

            // No timestamps - only scanned_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_scans');
    }
};
