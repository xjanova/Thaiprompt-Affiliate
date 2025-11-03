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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('set null');

            // Review Content
            $table->integer('rating');                       // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('images')->nullable();              // Array of review images

            // Status
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);

            // Helpful votes
            $table->integer('helpful_count')->default(0);

            // Seller Response
            $table->text('seller_response')->nullable();
            $table->timestamp('seller_responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['product_id', 'is_approved']);
            $table->index(['user_id']);
            $table->unique(['user_id', 'order_item_id']); // One review per order item
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
