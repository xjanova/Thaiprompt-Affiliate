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
        Schema::dropIfExists('vendor_public_products');
        Schema::create('vendor_public_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Request Details
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->text('request_note')->nullable();

            // Review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'removed'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Display Settings (if approved)
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamp('featured_until')->nullable();

            // Stats
            $table->integer('views_count')->default(0);
            $table->integer('clicks_count')->default(0);

            $table->timestamps();

            $table->unique(['product_id']);
            $table->index(['status', 'created_at']);
            $table->index(['store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_public_products');
    }
};
