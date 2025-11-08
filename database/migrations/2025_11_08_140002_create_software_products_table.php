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
        Schema::create('software_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('software_product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('features')->nullable(); // JSON array of features
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('price_label')->nullable(); // เช่น "เริ่มต้นที่"
            $table->string('currency', 10)->default('THB');
            $table->string('main_image_url')->nullable();
            $table->json('gallery_images')->nullable(); // Array of image URLs
            $table->json('technical_specs')->nullable(); // Technical specifications
            $table->text('demo_url')->nullable();
            $table->text('documentation_url')->nullable();
            $table->boolean('is_customizable')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_consultation')->default(false);
            $table->integer('estimated_delivery_days')->nullable();
            $table->json('meta_data')->nullable(); // Additional metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('quotation_count')->default(0);
            $table->integer('order_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('slug');
            $table->index(['is_active', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_products');
    }
};
