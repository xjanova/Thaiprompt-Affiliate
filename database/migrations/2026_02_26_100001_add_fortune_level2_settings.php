<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ Level 1/Level 2 commission settings ในตาราง fortune_telling_settings
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // Level 1: ประเภทคอมมิชชั่น (fixed = จำนวนเงินคงที่, percent = เปอร์เซ็นต์)
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_level1_commission_type')) {
                $table->enum('fortune_level1_commission_type', ['fixed', 'percent'])
                    ->default('fixed')
                    ->after('fortune_static_commission_amount');
            }

            // Level 1: จำนวนเงิน/เปอร์เซ็นต์
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_level1_commission_amount')) {
                $table->decimal('fortune_level1_commission_amount', 10, 2)
                    ->default(10)
                    ->after('fortune_level1_commission_type');
            }

            // Level 2: เปิด/ปิดชั้นหลาน
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_level2_enabled')) {
                $table->boolean('fortune_level2_enabled')
                    ->default(true)
                    ->after('fortune_level1_commission_amount');
            }

            // Level 2: ประเภทคอมมิชชั่น
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_level2_commission_type')) {
                $table->enum('fortune_level2_commission_type', ['fixed', 'percent'])
                    ->default('fixed')
                    ->after('fortune_level2_enabled');
            }

            // Level 2: จำนวนเงิน/เปอร์เซ็นต์
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_level2_commission_amount')) {
                $table->decimal('fortune_level2_commission_amount', 10, 2)
                    ->default(5)
                    ->after('fortune_level2_commission_type');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'fortune_level1_commission_type',
                'fortune_level1_commission_amount',
                'fortune_level2_enabled',
                'fortune_level2_commission_type',
                'fortune_level2_commission_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
