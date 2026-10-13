<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🪙 ชำระเงินด้วยเหรียญ TPIX (เชน 4289)
 *
 * 1) tpix_price_snapshots — ราคาย้อนหลังจากพูล DEX (คิดค่าเฉลี่ยกันปั่นราคา + กราฟ/ตรวจสอบย้อนหลัง)
 * 2) tpix_payments — ใบเสนอยอดเหรียญ 1 ใบต่อการจ่าย 1 ครั้ง (ยอด wei แบบตรงตัว + tx hash ที่ใช้จ่าย)
 *    open_key = "กระเป๋า:ยอด wei" ขณะใบยังเปิด → unique index กันยอดซ้ำแบบ atomic (ปิดใบแล้วตั้งเป็น NULL)
 *    tx_hash unique → ธุรกรรมเดียวจ่ายได้ใบเดียว
 *    สถานะเป็น string ไม่ใช่ enum (เพิ่มสถานะทีหลังไม่ต้อง ALTER บน MariaDB)
 * 3) products.price_tpix — ราคา TPIX คงที่ (ใช้จริงเฉพาะสินค้า Official Shop ที่แพลตฟอร์มขายเอง)
 * 4) orders.payment_method — เพิ่มค่า 'tpix' ใน enum
 */
return new class extends Migration
{
    use SafeMigration;

    /** ค่า enum ของ orders.payment_method หลังเพิ่ม tpix (รวมค่าเดิมทั้งหมดจาก 2026_09_25_170000) */
    private const PAYMENT_METHODS = [
        'promptpay',
        'bank_transfer',
        'credit_card',
        'cod',
        'wallet',
        'paysolutions',
        'coins',
        'tpix',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tpix_price_snapshots')) {
            Schema::create('tpix_price_snapshots', function (Blueprint $table) {
                $table->id();
                $table->decimal('price_usd', 24, 12)->comment('ราคา 1 TPIX เป็น USD จากพูล DEX');
                $table->decimal('usd_thb', 14, 6)->comment('อัตรา 1 USD เป็นบาท');
                $table->decimal('price_thb', 24, 12)->comment('ราคา 1 TPIX เป็นบาท');
                $table->decimal('reserve_tpix', 36, 18)->nullable();
                $table->decimal('reserve_usdt', 36, 18)->nullable();
                $table->string('source', 20)->default('dex');
                // ⚠️ nullable — MariaDB เติม ON UPDATE CURRENT_TIMESTAMP ให้ timestamp ตัวแรกที่ไม่ nullable
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (! Schema::hasTable('tpix_payments')) {
            Schema::create('tpix_payments', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 40)->unique()->comment('รหัสอ้างอิงสาธารณะ (ใช้ใน URL หน้าจ่าย)');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
                $table->string('purpose', 20)->comment('order | wallet_topup');
                $table->string('payable_type', 50)->nullable();
                $table->unsignedBigInteger('payable_id')->nullable();
                $table->decimal('amount_thb', 14, 2)->comment('มูลค่าเป็นบาทที่ต้องได้รับ');
                $table->decimal('rate_thb', 24, 12)->comment('ราคา 1 TPIX เป็นบาทที่ใช้คิดใบนี้');
                $table->decimal('price_usd', 24, 12)->nullable();
                $table->decimal('usd_thb', 14, 6)->nullable();
                $table->string('amount_wei', 80)->comment('ยอดที่ต้องโอน หน่วย wei (ต้องตรงทุกหลัก)');
                $table->decimal('amount_tpix', 38, 18);
                $table->string('receive_address', 42);
                $table->string('status', 20)->default('pending')
                    ->comment('pending|confirming|paid|credited_wallet|late|expired|cancelled|failed');
                $table->string('open_key', 140)->nullable()->unique()->comment('กันยอดซ้ำขณะใบยังเปิด');
                $table->string('tx_hash', 66)->nullable()->unique();
                $table->string('from_address', 42)->nullable();
                $table->unsignedBigInteger('block_number')->nullable();
                $table->unsignedInteger('confirmations')->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('paid_at')->nullable();
                $table->string('failure_reason', 255)->nullable();
                $table->json('meta')->nullable();
                // nullable ทั้งคู่ — กันกับดัก ON UPDATE ของ MariaDB
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['status', 'expires_at'], 'tpix_pay_status_exp_idx');
                $table->index(['payable_type', 'payable_id'], 'tpix_pay_payable_idx');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->safeAddColumn($table, 'products', 'price_tpix', function (Blueprint $table) {
                    $table->decimal('price_tpix', 30, 8)->nullable()
                        ->comment('ราคา TPIX คงที่ (Official Shop) — ว่าง = คิดจากราคาบาทตามราคาตลาด');
                });
            });
        }

        // ขยาย enum วิธีชำระของออเดอร์ (MySQL/MariaDB เท่านั้น — driver อื่นไม่มี enum จริง)
        if (Schema::hasTable('orders') && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('".implode("','", self::PAYMENT_METHODS)."') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        // คืน enum เดิมได้เฉพาะเมื่อไม่มีออเดอร์ที่จ่ายด้วย tpix (กันข้อมูลหาย)
        if (Schema::hasTable('orders') && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            && ! DB::table('orders')->where('payment_method', 'tpix')->exists()) {
            $old = array_values(array_diff(self::PAYMENT_METHODS, ['tpix']));
            DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('".implode("','", $old)."') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
        }

        $this->safeDropColumn('products', 'price_tpix');
        Schema::dropIfExists('tpix_payments');
        Schema::dropIfExists('tpix_price_snapshots');
    }
};
