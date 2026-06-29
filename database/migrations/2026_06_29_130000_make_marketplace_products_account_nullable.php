<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ทำให้ marketplace_products.account_id เป็น nullable
 *
 * เหตุผล: สินค้าที่นำเข้าด้วย "วางลิงก์ (scrape)" ไม่ได้ผูกกับบัญชี API (credential)
 * จึงไม่มี account_id — ต้องอนุญาตให้ว่างได้ + FK เปลี่ยนเป็น ON DELETE SET NULL
 *
 * ใช้ raw SQL สำหรับ MODIFY (โปรเจกต์ไม่มี doctrine/dbal)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_products') || ! Schema::hasColumn('marketplace_products', 'account_id')) {
            return;
        }

        // 1) ลบ FK เดิม (ชื่อ convention: marketplace_products_account_id_foreign)
        Schema::table('marketplace_products', function (Blueprint $table) {
            try {
                $table->dropForeign(['account_id']);
            } catch (\Throwable $e) {
                // FK อาจถูกลบไปแล้ว — ข้าม
            }
        });

        // 2) เปลี่ยนคอลัมน์เป็น nullable
        DB::statement('ALTER TABLE marketplace_products MODIFY account_id BIGINT UNSIGNED NULL');

        // 3) ใส่ FK กลับแบบ SET NULL ตอนลบบัญชี
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')->on('marketplace_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_products') || ! Schema::hasColumn('marketplace_products', 'account_id')) {
            return;
        }

        Schema::table('marketplace_products', function (Blueprint $table) {
            try {
                $table->dropForeign(['account_id']);
            } catch (\Throwable $e) {
            }
        });

        // กลับเป็น NOT NULL (อาจล้มถ้ามีแถว account_id = NULL — ต้องเคลียร์ก่อน manual)
        DB::statement('ALTER TABLE marketplace_products MODIFY account_id BIGINT UNSIGNED NOT NULL');

        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')->on('marketplace_accounts')
                ->cascadeOnDelete();
        });
    }
};
