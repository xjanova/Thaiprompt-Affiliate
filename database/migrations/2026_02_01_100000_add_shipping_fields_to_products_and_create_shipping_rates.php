<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟิลด์การจัดส่งในตารางสินค้า และสร้างตาราง shipping_rates
     *
     * shipping_method:
     * - free = จัดส่งฟรี
     * - flat_rate = อัตราเหมา (กำหนดค่าส่งคงที่)
     * - weight_based = คำนวณตามน้ำหนัก
     * - store_default = ใช้ค่าเริ่มต้นของร้าน
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่มฟิลด์ shipping ในตาราง products
        Schema::table('products', function (Blueprint $table) {
            $this->safeAddColumn($table, 'products', 'shipping_method', function ($table) {
                $table->enum('shipping_method', ['free', 'flat_rate', 'weight_based', 'store_default'])
                    ->default('store_default')
                    ->after('is_virtual');
            });

            $this->safeAddColumn($table, 'products', 'shipping_fee', function ($table) {
                $table->decimal('shipping_fee', 10, 2)->default(0)
                    ->after('shipping_method');
            });

            $this->safeAddColumn($table, 'products', 'shipping_weight_kg', function ($table) {
                $table->decimal('shipping_weight_kg', 8, 3)->nullable()
                    ->after('shipping_fee');
            });

            $this->safeAddColumn($table, 'products', 'free_shipping_min_amount', function ($table) {
                $table->decimal('free_shipping_min_amount', 10, 2)->nullable()
                    ->after('shipping_weight_kg');
            });
        });

        // สร้างตาราง shipping_rates สำหรับคำนวณตามน้ำหนัก/โซน
        if (!Schema::hasTable('shipping_rates')) {
            Schema::create('shipping_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('zone')->default('domestic');
                $table->decimal('min_weight_kg', 8, 3)->default(0);
                $table->decimal('max_weight_kg', 8, 3)->default(0);
                $table->decimal('base_fee', 10, 2)->default(0);
                $table->decimal('per_kg_fee', 10, 2)->default(0);
                $table->decimal('min_fee', 10, 2)->default(0);
                $table->decimal('max_fee', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['zone', 'is_active', 'sort_order'], 'ship_rate_zone_idx');
            });
        }
    }

    /**
     * ลบฟิลด์และตารางที่สร้าง
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropColumn('products', [
            'shipping_method',
            'shipping_fee',
            'shipping_weight_kg',
            'free_shipping_min_amount',
        ]);

        Schema::dropIfExists('shipping_rates');
    }
};
