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
        Schema::create('mlm_plan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_plan_id')->constrained('mlm_plans')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1)->comment('Quantity of this product in the package');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Optional discount for this product in package');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['mlm_plan_id', 'product_id']);
            $table->index('mlm_plan_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_plan_products');
    }
};
