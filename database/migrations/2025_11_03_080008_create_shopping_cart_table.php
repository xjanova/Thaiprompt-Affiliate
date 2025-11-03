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
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->integer('quantity')->default(1);
            $table->json('selected_attributes')->nullable(); // e.g., {"color": "red", "size": "M"}

            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->unique(['user_id', 'product_id', 'selected_attributes']); // Prevent duplicate items
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_cart');
    }
};
