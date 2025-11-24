<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ VAT และ PV สำหรับการคำนวณรายได้สุทธิ
     *
     * VAT หักจากผู้ขาย/ผู้ให้บริการ และผู้รับค่าคอม MLM
     * PV ใช้คำนวณค่าคอม MLM
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่มใน products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'vat_percentage')) {
                $table->decimal('vat_percentage', 5, 2)
                    ->default(7.00)
                    ->after('cashback_percentage')
                    ->comment('ภาษี VAT (%) หักจากผู้ขาย');
            }

            if (!Schema::hasColumn('products', 'pv_value')) {
                $table->decimal('pv_value', 10, 2)
                    ->default(0)
                    ->after('vat_percentage')
                    ->comment('PV สำหรับคำนวณ MLM Commission (0 = ไม่มีคอม)');
            }
        });

        // เพิ่มใน services table
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (!Schema::hasColumn('services', 'vat_percentage')) {
                    $table->decimal('vat_percentage', 5, 2)
                        ->default(7.00)
                        ->after('max_pv_percentage')
                        ->comment('ภาษี VAT (%) หักจากผู้ให้บริการ');
                }

                if (!Schema::hasColumn('services', 'cashback_percentage')) {
                    $table->decimal('cashback_percentage', 5, 2)
                        ->default(0)
                        ->after('vat_percentage')
                        ->comment('Cashback (%) ผู้ให้บริการจ่ายเอง');
                }

                if (!Schema::hasColumn('services', 'pv_value')) {
                    $table->decimal('pv_value', 10, 2)
                        ->default(0)
                        ->after('cashback_percentage')
                        ->comment('PV สำหรับคำนวณ MLM Commission');
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
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'vat_percentage')) {
                $table->dropColumn('vat_percentage');
            }
            if (Schema::hasColumn('products', 'pv_value')) {
                $table->dropColumn('pv_value');
            }
        });

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $columns = ['vat_percentage', 'cashback_percentage', 'pv_value'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('services', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
