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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');

            // Product Details (snapshot at order time)
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('product_image')->nullable();
            $table->json('product_attributes')->nullable();  // e.g., {"color": "red", "size": "M"}

            // Pricing
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);              // unit_price * quantity
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);                 // subtotal - discount

            // Commission
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('seller_earning', 10, 2);

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'completed',
                'cancelled',
                'refunded'
            ])->default('pending');

            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['product_id']);
            $table->index(['seller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
