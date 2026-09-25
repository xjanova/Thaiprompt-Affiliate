<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แยกส่วนลดของออเดอร์ร้านค้าตามชนิด + ผู้ออกเงิน (รีวิว Wave-1: คูปองร้านถูกนับเป็นคูปองแพลตฟอร์ม)
 *
 * เดิม orders.discount_amount เก็บรวมส่วนลดสินค้า + ส่วนลดค่าส่ง ทำให้ระบบแบ่งเงิน:
 *  - แบ่งค่าส่งเต็มจำนวนให้ร้าน ทั้งที่ผู้ซื้อได้ส่งฟรี (ร้านได้เงินที่ไม่มีใครจ่าย)
 *  - ลงส่วนลดทั้งก้อนเป็นรายจ่ายของแพลตฟอร์ม ทั้งที่ร้านรับภาระส่วนลดสินค้าไปแล้วใน order_items.total
 *
 * คอลัมน์ใหม่ (nullable = ออเดอร์เก่า → ระบบคำนวณย้อนจาก order_items + coupon_usages แทน):
 *  - product_discount   ส่วนลดค่าสินค้า (บาท)
 *  - shipping_discount  ส่วนลดค่าส่ง (บาท)
 *  - discount_funded_by ผู้ออกเงินส่วนลด: store (คูปองของร้าน) | platform (คูปองของแพลตฟอร์ม)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'orders', 'product_discount', function (Blueprint $table) {
                $table->decimal('product_discount', 12, 2)
                    ->nullable()
                    ->after('discount_amount')
                    ->comment('ส่วนลดค่าสินค้า (บาท) — null = ออเดอร์เก่า');
            });

            $this->safeAddColumn($table, 'orders', 'shipping_discount', function (Blueprint $table) {
                $table->decimal('shipping_discount', 12, 2)
                    ->nullable()
                    ->after('product_discount')
                    ->comment('ส่วนลดค่าส่ง (บาท) — null = ออเดอร์เก่า');
            });

            $this->safeAddColumn($table, 'orders', 'discount_funded_by', function (Blueprint $table) {
                $table->string('discount_funded_by', 20)
                    ->nullable()
                    ->after('shipping_discount')
                    ->comment('ผู้ออกเงินส่วนลด: store | platform');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('orders', ['product_discount', 'shipping_discount', 'discount_funded_by']);
    }
};
