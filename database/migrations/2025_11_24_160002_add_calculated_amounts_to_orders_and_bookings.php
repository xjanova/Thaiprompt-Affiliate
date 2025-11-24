<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์สำหรับเก็บค่าที่คำนวณแล้วใน Orders และ Service Bookings
     *
     * บันทึก snapshot ของค่าต่างๆ ณ เวลาที่สั่งซื้อ/จอง
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่มใน orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'vat_percentage')) {
                    $table->decimal('vat_percentage', 5, 2)
                        ->default(7.00)
                        ->after('total')
                        ->comment('% VAT (snapshot)');
                }

                if (!Schema::hasColumn('orders', 'vat_amount')) {
                    $table->decimal('vat_amount', 10, 2)
                        ->default(0)
                        ->after('vat_percentage')
                        ->comment('จำนวน VAT (บาท)');
                }

                if (!Schema::hasColumn('orders', 'cashback_percentage')) {
                    $table->decimal('cashback_percentage', 5, 2)
                        ->default(0)
                        ->after('vat_amount')
                        ->comment('% Cashback (snapshot)');
                }

                if (!Schema::hasColumn('orders', 'cashback_amount')) {
                    $table->decimal('cashback_amount', 10, 2)
                        ->default(0)
                        ->after('cashback_percentage')
                        ->comment('จำนวน Cashback (บาท)');
                }

                if (!Schema::hasColumn('orders', 'mlm_pv_total')) {
                    $table->decimal('mlm_pv_total', 10, 2)
                        ->default(0)
                        ->after('cashback_amount')
                        ->comment('PV รวม (สำหรับ MLM Commission)');
                }

                if (!Schema::hasColumn('orders', 'mlm_commission_total')) {
                    $table->decimal('mlm_commission_total', 10, 2)
                        ->default(0)
                        ->after('mlm_pv_total')
                        ->comment('ค่าคอม MLM รวม (หักจากผู้ขาย)');
                }

                if (!Schema::hasColumn('orders', 'seller_net_earnings')) {
                    $table->decimal('seller_net_earnings', 10, 2)
                        ->default(0)
                        ->after('mlm_commission_total')
                        ->comment('รายได้สุทธิผู้ขาย (หลังหักทุกอย่าง)');
                }
            });
        }

        // เพิ่มใน service_bookings table
        if (Schema::hasTable('service_bookings')) {
            Schema::table('service_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('service_bookings', 'vat_percentage')) {
                    $table->decimal('vat_percentage', 5, 2)
                        ->default(7.00)
                        ->after('system_revenue')
                        ->comment('% VAT (snapshot)');
                }

                if (!Schema::hasColumn('service_bookings', 'vat_amount')) {
                    $table->decimal('vat_amount', 10, 2)
                        ->default(0)
                        ->after('vat_percentage')
                        ->comment('จำนวน VAT (บาท)');
                }

                if (!Schema::hasColumn('service_bookings', 'cashback_percentage')) {
                    $table->decimal('cashback_percentage', 5, 2)
                        ->default(0)
                        ->after('vat_amount')
                        ->comment('% Cashback (snapshot)');
                }

                if (!Schema::hasColumn('service_bookings', 'cashback_amount')) {
                    $table->decimal('cashback_amount', 10, 2)
                        ->default(0)
                        ->after('cashback_percentage')
                        ->comment('จำนวน Cashback (บาท)');
                }

                if (!Schema::hasColumn('service_bookings', 'mlm_pv_total')) {
                    $table->decimal('mlm_pv_total', 10, 2)
                        ->default(0)
                        ->after('cashback_amount')
                        ->comment('PV รวม (สำหรับ MLM Commission)');
                }

                if (!Schema::hasColumn('service_bookings', 'mlm_commission_total')) {
                    $table->decimal('mlm_commission_total', 10, 2)
                        ->default(0)
                        ->after('mlm_pv_total')
                        ->comment('ค่าคอม MLM รวม');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $columns = [
            'vat_percentage',
            'vat_amount',
            'cashback_percentage',
            'cashback_amount',
            'mlm_pv_total',
            'mlm_commission_total',
            'seller_net_earnings',
        ];

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('service_bookings')) {
            Schema::table('service_bookings', function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if ($column === 'seller_net_earnings') continue; // ไม่มีใน service_bookings
                    if (Schema::hasColumn('service_bookings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
