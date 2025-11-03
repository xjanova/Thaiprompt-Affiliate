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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');

            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();  // Original price for discount display
            $table->decimal('cost_price', 10, 2)->nullable();         // Cost for profit calculation

            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('track_inventory')->default(true);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock');

            // Product Details
            $table->string('brand')->nullable();
            $table->decimal('weight', 8, 2)->nullable();            // in grams
            $table->string('dimensions')->nullable();                // e.g., "10x20x30 cm"

            // Images
            $table->string('main_image_url')->nullable();
            $table->json('image_urls')->nullable();                 // Array of additional images

            // Attributes & Variants
            $table->json('attributes')->nullable();                  // e.g., {"color": "red", "size": "M"}
            $table->boolean('has_variants')->default(false);
            $table->foreignId('parent_product_id')->nullable()->constrained('products')->onDelete('cascade');

            // Commission & Rental Integration
            $table->decimal('commission_rate', 5, 2)->default(10.00); // % commission to platform
            $table->boolean('requires_rental')->default(false);        // Requires rented bot to sell
            $table->foreignId('linked_bot_rental_id')->nullable()->constrained('ai_bot_rentals')->onDelete('set null');

            // SEO & Marketing
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();

            // Status & Visibility
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            // Stats
            $table->integer('view_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['seller_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['slug']);
            $table->index(['sku']);
            $table->index(['is_featured', 'is_active']);
            $table->index('parent_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
