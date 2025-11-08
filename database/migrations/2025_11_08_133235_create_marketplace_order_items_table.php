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
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('marketplace_orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('marketplace_products')->onDelete('set null');

            // Product Info
            $table->string('external_product_id', 100)->nullable();
            $table->string('external_sku', 100)->nullable();
            $table->string('product_name', 500);
            $table->text('product_image_url')->nullable();

            // Variant Info
            $table->string('variant_name', 200)->nullable();
            $table->string('variant_sku', 100)->nullable();

            // Pricing
            $table->decimal('unit_price', 15, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Commission
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->default(0);

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
    }
};
