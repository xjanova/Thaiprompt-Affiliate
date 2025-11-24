<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบ Platform Fee และ Provider PV ในตาราง services
     *
     * Platform Fee = ค่าบริการของระบบ (แอดมินตั้ง)
     * Provider PV = ค่าการตลาด/โฆษณา (Provider ตั้งเอง)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // เช็คว่ามีคอลัมน์อยู่แล้วหรือยัง
            if (!Schema::hasColumn('services', 'platform_fee_percentage')) {
                $table->decimal('platform_fee_percentage', 5, 2)
                    ->default(10.00)
                    ->after('commission_rate')
                    ->comment('ค่าบริการแพลตฟอร์ม (%) - แอดมินกำหนด');
            }

            if (!Schema::hasColumn('services', 'provider_pv_percentage')) {
                $table->decimal('provider_pv_percentage', 5, 2)
                    ->default(5.00)
                    ->after('platform_fee_percentage')
                    ->comment('ค่า PV/การตลาด (%) - Provider ตั้งเอง ยิ่งสูง=โอกาสถูกแนะนำเยอะ แต่กำไรลด');
            }

            if (!Schema::hasColumn('services', 'min_pv_percentage')) {
                $table->decimal('min_pv_percentage', 5, 2)
                    ->default(0.00)
                    ->after('provider_pv_percentage')
                    ->comment('ค่า PV ต่ำสุดที่ระบบกำหนด (%)');
            }

            if (!Schema::hasColumn('services', 'max_pv_percentage')) {
                $table->decimal('max_pv_percentage', 5, 2)
                    ->default(30.00)
                    ->after('min_pv_percentage')
                    ->comment('ค่า PV สูงสุดที่ระบบกำหนด (%)');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'platform_fee_percentage')) {
                $table->dropColumn('platform_fee_percentage');
            }

            if (Schema::hasColumn('services', 'provider_pv_percentage')) {
                $table->dropColumn('provider_pv_percentage');
            }

            if (Schema::hasColumn('services', 'min_pv_percentage')) {
                $table->dropColumn('min_pv_percentage');
            }

            if (Schema::hasColumn('services', 'max_pv_percentage')) {
                $table->dropColumn('max_pv_percentage');
            }
        });
    }
};
