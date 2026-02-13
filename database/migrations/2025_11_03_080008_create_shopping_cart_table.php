<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง shopping_cart สำหรับระบบตะกร้าสินค้า
     */
    public function up(): void
    {
        if (Schema::hasTable('shopping_cart')) {
            return;
        }

        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->integer('quantity')->default(1);
            $table->json('selected_attributes')->nullable(); // e.g., {"color": "red", "size": "M"}

            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            // ⚠️ ไม่สามารถสร้าง UNIQUE index บน JSON column ได้ใน MySQL 8.0
            // ใช้ unique index เฉพาะ user_id + product_id แทน
            $table->unique(['user_id', 'product_id'], 'cart_user_product_unique');
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
