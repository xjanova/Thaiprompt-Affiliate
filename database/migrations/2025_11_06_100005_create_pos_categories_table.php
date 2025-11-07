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
        Schema::create('pos_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('vendor_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // icon class or image path
            $table->string('color')->nullable(); // hex color for UI
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_pos')->default(true);

            // Parent Category (for nested categories)
            $table->foreignId('parent_id')->nullable()->constrained('pos_categories')->cascadeOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('store_id');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('order');
        });

        // Pivot table for product-category relationship in POS
        Schema::create('pos_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_category_id')->constrained('pos_categories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['pos_category_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_category_product');
        Schema::dropIfExists('pos_categories');
    }
};
