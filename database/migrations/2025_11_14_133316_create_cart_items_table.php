<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง cart_items
     *
     * รายการสินค้าในตะกร้า
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('cart_items')) {
            return;
        }

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // Foreign key สำหรับตะกร้า
            $table->foreignId('cart_id')
                ->constrained('carts')
                ->onDelete('cascade')
                ->comment('ตะกร้าที่รายการนี้อยู่');

            // Foreign key สำหรับสินค้า
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('สินค้าที่เพิ่มในตะกร้า');

            // ข้อมูลรายการ
            $table->integer('quantity')
                ->unsigned()
                ->default(1)
                ->comment('จำนวนสินค้า');

            $table->decimal('price', 10, 2)
                ->default(0)
                ->comment('ราคาต่อหน่วยที่บันทึกไว้ (snapshot)');

            // คุณสมบัติเพิ่มเติม (เช่น สี ขนาด)
            $table->json('attributes')
                ->nullable()
                ->comment('คุณสมบัติเพิ่มเติมของสินค้า');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cart_id');
            $table->index('product_id');
            $table->index('created_at');

            // Unique constraint: ป้องกันการเพิ่มสินค้าซ้ำในตะกร้าเดียวกัน
            $table->unique(['cart_id', 'product_id', 'deleted_at'], 'cart_product_unique');
        });
    }

    /**
     * ลบตาราง cart_items
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
