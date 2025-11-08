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
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('marketplace_accounts')->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('marketplace_platforms')->onDelete('cascade');

            // Product IDs
            $table->string('external_product_id', 100);
            $table->string('external_sku', 100)->nullable();

            // Product Info
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->string('category', 200)->nullable();
            $table->string('brand', 100)->nullable();

            // Pricing
            $table->decimal('price', 15, 2);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('THB');

            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_available')->default(true);

            // Media
            $table->text('main_image_url')->nullable();
            $table->json('images')->nullable();

            // Affiliate Info
            $table->text('affiliate_url')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 15, 2')->nullable();

            // Product Details
            $table->json('attributes')->nullable();
            $table->json('variants')->nullable();

            // SEO
            $table->json('tags')->nullable();

            // Statistics
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('sales_count')->default(0);

            // Status
            $table->enum('sync_status', ['synced', 'pending', 'error'])->default('synced');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['account_id', 'external_product_id'], 'unique_external_product');
            $table->index(['platform_id', 'external_product_id']);
            $table->index('is_active');
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
