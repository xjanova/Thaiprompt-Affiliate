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
        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Product Snapshot (at time of sale)
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->string('product_barcode')->nullable();
            $table->text('product_description')->nullable();
            $table->string('product_image')->nullable();

            // Pricing
            $table->decimal('unit_price', 15, 2);
            $table->decimal('original_price', 15, 2); // before discount
            $table->integer('quantity');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2); // unit_price * quantity
            $table->decimal('total', 15, 2); // after discount

            // Tax
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(7);
            $table->boolean('is_tax_inclusive')->default(true);

            // Variant Info
            $table->json('variant_attributes')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('pos_transaction_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
    }
};
