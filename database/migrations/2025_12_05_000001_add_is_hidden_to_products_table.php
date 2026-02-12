<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ is_hidden สำหรับซ่อนสินค้าจากหน้าเว็บ
     *
     * สินค้าที่ถูกซ่อน:
     * - ไม่แสดงในหน้า storefront, marketplace, search results
     * - ยังสามารถซื้อได้ผ่าน direct link หรือระบบภายใน
     * - เหมาะสำหรับ: สินค้าเติมเงิน, สินค้าพิเศษ, สินค้าภายใน
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('is_featured')
                    ->comment('ซ่อนสินค้าจากหน้าเว็บ (ยังซื้อได้ผ่าน direct link)');
            }
        });

        // เพิ่ม index สำหรับ query ที่ filter hidden products
        Schema::table('products', function (Blueprint $table) {
            // สร้าง composite index สำหรับ query ที่ใช้บ่อย
            $indexNames = collect(Schema::getIndexes('products'))->pluck('name')->toArray();

            if (!in_array('products_is_active_is_hidden_index', $indexNames)) {
                $table->index(['is_active', 'is_hidden'], 'products_is_active_is_hidden_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ลบ index ก่อน
            $indexNames = collect(Schema::getIndexes('products'))->pluck('name')->toArray();

            if (in_array('products_is_active_is_hidden_index', $indexNames)) {
                $table->dropIndex('products_is_active_is_hidden_index');
            }

            // ลบ column
            if (Schema::hasColumn('products', 'is_hidden')) {
                $table->dropColumn('is_hidden');
            }
        });
    }
};
