<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เตรียมระบบร้านค้า (e-commerce) สำหรับเปิดตัวแอป — Workstream D
 *
 * 1) orders.payment_method: ขยาย enum ให้รับค่าที่ checkout ใช้จริง (wallet, paysolutions, coins)
 *    เดิมมีแค่ promptpay/bank_transfer/credit_card/cod ทำให้ checkout เว็บ/แอปที่ส่ง 'wallet' พังใน strict mode
 *    (ค่าเก่าเก็บไว้ครบ · ค่า alias เช่น cash_on_delivery/bank/card ถูก normalize ในโค้ดก่อนเขียน)
 * 2) orders: stock_deducted_at (ตัดสต็อกไปแล้วหรือยัง → ยกเลิกแล้วคืนสต็อกเฉพาะที่ตัดจริง),
 *    delivery_method (parcel|rider), checkout_group (ออเดอร์หลายร้านจากการกดสั่งครั้งเดียว)
 *    + backfill: ออเดอร์ที่จ่ายแล้วเดิมถูกตัดสต็อกตอนจ่าย → ใส่ stock_deducted_at ให้
 *    + backfill store_id ของออเดอร์ที่สินค้าทั้งหมดมาจากผู้ขายคนเดียวที่มีร้านเดียว
 * 3) shipping_addresses: latitude/longitude (ส่งด้วยไรเดอร์ต้องมีพิกัด)
 * 4) vendor_stores: ตั้งค่าส่งด้วยไรเดอร์ (rider_delivery_enabled + จุดรับของ) และ vat_registered
 * 5) coupons.deleted_at: model Coupon ใช้ SoftDeletes แต่ตารางไม่มีคอลัมน์ → query คูปองทุกตัวพัง
 * 6) products.store_id: backfill จากร้านของผู้ขาย (ผู้ขายที่มีร้านเดียว) — หน้าร้านในแอป/POS ค้นด้วย store_id
 */
return new class extends Migration
{
    use SafeMigration;

    /** ค่า enum ใหม่ของ orders.payment_method (รวมค่าเก่าทั้งหมด) */
    private const PAYMENT_METHODS = [
        'promptpay',
        'bank_transfer',
        'credit_card',
        'cod',
        'wallet',
        'paysolutions',
        'coins',
    ];

    /** ค่า enum เดิม (ใช้ตอน down เฉพาะเมื่อไม่มีแถวที่ใช้ค่าใหม่) */
    private const OLD_PAYMENT_METHODS = ['promptpay', 'bank_transfer', 'credit_card', 'cod'];

    public function up(): void
    {
        $this->upOrders();
        $this->upShippingAddresses();
        $this->upVendorStores();
        $this->upCoupons();
        $this->backfillProductStoreIds();
    }

    public function down(): void
    {
        $this->safeDropIndex('orders', 'orders_checkout_group_index');
        $this->safeDropColumn('orders', ['stock_deducted_at', 'delivery_method', 'checkout_group']);
        $this->safeDropColumn('shipping_addresses', ['latitude', 'longitude']);
        $this->safeDropColumn('vendor_stores', [
            'rider_delivery_enabled',
            'pickup_latitude',
            'pickup_longitude',
            'pickup_address',
            'vat_registered',
        ]);
        $this->safeDropColumn('coupons', 'deleted_at');

        // คืน enum เดิมได้เฉพาะเมื่อไม่มีแถวใช้ค่าใหม่ (กันข้อมูลหาย)
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('orders')) {
            $inUse = DB::table('orders')
                ->whereNotNull('payment_method')
                ->whereNotIn('payment_method', self::OLD_PAYMENT_METHODS)
                ->exists();

            if (! $inUse) {
                DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('".implode("','", self::OLD_PAYMENT_METHODS)."') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
            }
        }
    }

    // =====================================================
    // orders
    // =====================================================

    private function upOrders(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        // ขยาย enum วิธีชำระเงิน (MySQL เท่านั้น — driver อื่นไม่มี enum จริง)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('".implode("','", self::PAYMENT_METHODS)."') COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
        }

        Schema::table('orders', function (Blueprint $table) {
            $this->safeAddColumn($table, 'orders', 'stock_deducted_at', function (Blueprint $table) {
                $table->timestamp('stock_deducted_at')
                    ->nullable()
                    ->after('paid_at')
                    ->comment('เวลาที่ตัดสต็อกของออเดอร์นี้ (null = ยังไม่ตัด → ยกเลิกแล้วไม่ต้องคืนสต็อก)');
            });

            $this->safeAddColumn($table, 'orders', 'delivery_method', function (Blueprint $table) {
                $table->string('delivery_method', 20)
                    ->default('parcel')
                    ->after('shipping_fee')
                    ->comment('parcel = ส่งพัสดุ, rider = ส่งด้วยไรเดอร์ของแพลตฟอร์ม');
            });

            $this->safeAddColumn($table, 'orders', 'checkout_group', function (Blueprint $table) {
                $table->string('checkout_group', 40)
                    ->nullable()
                    ->after('order_number')
                    ->comment('รหัสการสั่งซื้อครั้งเดียวที่แยกเป็นหลายออเดอร์ตามร้าน');
            });
        });

        $this->safeAddIndex('orders', 'checkout_group', 'orders_checkout_group_index');

        // ออเดอร์ที่จ่ายแล้ว (ยังไม่ยกเลิก) ถูกตัดสต็อกตอนยืนยันการจ่าย (PaymentService) → บันทึกไว้
        // ให้การยกเลิก/คืนเงินภายหลังคืนสต็อกได้ถูกต้องเหมือนเดิม
        if (Schema::hasColumn('orders', 'stock_deducted_at')) {
            DB::table('orders')
                ->whereNull('stock_deducted_at')
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->update(['stock_deducted_at' => DB::raw('COALESCE(paid_at, updated_at, created_at)')]);
        }

        // store_id ของออเดอร์เดิม: สินค้าทุกชิ้นมาจากผู้ขายคนเดียว และผู้ขายมีร้านเดียว
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('order_items') && Schema::hasTable('vendor_stores')) {
            DB::statement(<<<'SQL'
                UPDATE `orders` o
                JOIN (
                    SELECT oi.order_id, MIN(oi.seller_id) AS seller_id
                    FROM `order_items` oi
                    GROUP BY oi.order_id
                    HAVING COUNT(DISTINCT oi.seller_id) = 1
                ) one_seller ON one_seller.order_id = o.id
                JOIN (
                    SELECT vs.user_id, MIN(vs.id) AS store_id
                    FROM `vendor_stores` vs
                    WHERE vs.deleted_at IS NULL
                    GROUP BY vs.user_id
                    HAVING COUNT(*) = 1
                ) one_store ON one_store.user_id = one_seller.seller_id
                SET o.store_id = one_store.store_id
                WHERE o.store_id IS NULL
            SQL);
        }
    }

    // =====================================================
    // shipping_addresses
    // =====================================================

    private function upShippingAddresses(): void
    {
        if (! Schema::hasTable('shipping_addresses')) {
            return;
        }

        Schema::table('shipping_addresses', function (Blueprint $table) {
            $this->safeAddColumn($table, 'shipping_addresses', 'latitude', function (Blueprint $table) {
                $table->decimal('latitude', 10, 7)->nullable()->after('postal_code')
                    ->comment('ละติจูดของที่อยู่ (จำเป็นเมื่อส่งด้วยไรเดอร์)');
            });

            $this->safeAddColumn($table, 'shipping_addresses', 'longitude', function (Blueprint $table) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                    ->comment('ลองจิจูดของที่อยู่ (จำเป็นเมื่อส่งด้วยไรเดอร์)');
            });
        });
    }

    // =====================================================
    // vendor_stores
    // =====================================================

    private function upVendorStores(): void
    {
        if (! Schema::hasTable('vendor_stores')) {
            return;
        }

        Schema::table('vendor_stores', function (Blueprint $table) {
            $this->safeAddColumn($table, 'vendor_stores', 'rider_delivery_enabled', function (Blueprint $table) {
                $table->boolean('rider_delivery_enabled')->default(false)->after('enable_cod')
                    ->comment('เปิดให้ลูกค้าเลือกส่งด้วยไรเดอร์ของแพลตฟอร์ม');
            });

            $this->safeAddColumn($table, 'vendor_stores', 'pickup_latitude', function (Blueprint $table) {
                $table->decimal('pickup_latitude', 10, 7)->nullable()->after('rider_delivery_enabled')
                    ->comment('ละติจูดจุดรับของของไรเดอร์');
            });

            $this->safeAddColumn($table, 'vendor_stores', 'pickup_longitude', function (Blueprint $table) {
                $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude')
                    ->comment('ลองจิจูดจุดรับของของไรเดอร์');
            });

            $this->safeAddColumn($table, 'vendor_stores', 'pickup_address', function (Blueprint $table) {
                $table->string('pickup_address', 500)->nullable()->after('pickup_longitude')
                    ->comment('ที่อยู่จุดรับของ (ว่าง = ใช้ store_address)');
            });

            $this->safeAddColumn($table, 'vendor_stores', 'vat_registered', function (Blueprint $table) {
                $table->boolean('vat_registered')->default(false)->after('tax_id')
                    ->comment('ร้านจดทะเบียนภาษีมูลค่าเพิ่มหรือไม่ (ใช้คำนวณ VAT ใน PricingEngine)');
            });
        });
    }

    // =====================================================
    // coupons
    // =====================================================

    private function upCoupons(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            $this->safeAddColumn($table, 'coupons', 'deleted_at', function (Blueprint $table) {
                $table->softDeletes();
            });
        });
    }

    // =====================================================
    // products.store_id
    // =====================================================

    private function backfillProductStoreIds(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('products') || ! Schema::hasTable('vendor_stores')) {
            return;
        }

        // เฉพาะผู้ขายที่มีร้าน (ที่ยังไม่ถูกลบ) ร้านเดียว — ผู้ขายหลายร้านต้องให้แอดมินเลือกเอง
        DB::statement(<<<'SQL'
            UPDATE `products` p
            JOIN (
                SELECT vs.user_id, MIN(vs.id) AS store_id
                FROM `vendor_stores` vs
                WHERE vs.deleted_at IS NULL
                GROUP BY vs.user_id
                HAVING COUNT(*) = 1
            ) one_store ON one_store.user_id = p.seller_id
            SET p.store_id = one_store.store_id
            WHERE p.store_id IS NULL
        SQL);
    }
};
