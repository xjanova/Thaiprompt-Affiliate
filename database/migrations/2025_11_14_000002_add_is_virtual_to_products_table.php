<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ is_virtual สำหรับสินค้าดิจิทัล/เสมือน (เช่น wallet topup)
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ ตรวจสอบว่ามีฟิลด์อยู่แล้วหรือไม่
        if (!Schema::hasColumn('products', 'is_virtual')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_virtual')->default(false)->after('is_featured');
            });
        }
    }

    /**
     * ลบฟิลด์ is_virtual
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'is_virtual')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_virtual');
            });
        }
    }
};
