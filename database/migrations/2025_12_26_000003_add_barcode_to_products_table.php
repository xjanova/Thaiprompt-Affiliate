<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration เพิ่ม barcode field ในตาราง products
 *
 * เพิ่มฟิลด์ barcode สำหรับระบบ POS และการพิมพ์ฉลาก
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * เพิ่ม barcode field
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // เช็คว่าคอลัมน์ barcode มีอยู่แล้วหรือยัง
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
                $table->index('barcode', 'products_barcode_idx');
            }
        });
    }

    /**
     * ลบ barcode field
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropIndex('products_barcode_idx');
                $table->dropColumn('barcode');
            }
        });
    }
};
