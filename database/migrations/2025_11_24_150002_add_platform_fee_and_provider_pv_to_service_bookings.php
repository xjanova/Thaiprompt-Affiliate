<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบ Platform Fee และ Provider PV ในตาราง service_bookings
     *
     * บันทึกค่าธรรมเนียม ณ เวลาจอง เพื่อป้องกันการเปลี่ยนแปลงย้อนหลัง
     *
     * สูตรการคำนวณ:
     * platform_fee_amount = total_amount * platform_fee_percentage / 100
     * provider_pv_amount = total_amount * provider_pv_percentage / 100
     * provider_earnings = total_amount - platform_fee_amount - provider_pv_amount
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            // Platform Fee (ค่าบริการแพลตฟอร์ม)
            if (!Schema::hasColumn('service_bookings', 'platform_fee_percentage')) {
                $table->decimal('platform_fee_percentage', 5, 2)
                    ->default(10.00)
                    ->after('commission_amount')
                    ->comment('เปอร์เซ็นต์ค่าแพลตฟอร์ม (snapshot ณ เวลาจอง)');
            }

            if (!Schema::hasColumn('service_bookings', 'platform_fee_amount')) {
                $table->decimal('platform_fee_amount', 10, 2)
                    ->default(0.00)
                    ->after('platform_fee_percentage')
                    ->comment('จำนวนเงินค่าแพลตฟอร์ม (บาท)');
            }

            // Provider PV (ค่าการตลาด)
            if (!Schema::hasColumn('service_bookings', 'provider_pv_percentage')) {
                $table->decimal('provider_pv_percentage', 5, 2)
                    ->default(5.00)
                    ->after('platform_fee_amount')
                    ->comment('เปอร์เซ็นต์ PV/การตลาด (snapshot ณ เวลาจอง)');
            }

            if (!Schema::hasColumn('service_bookings', 'provider_pv_amount')) {
                $table->decimal('provider_pv_amount', 10, 2)
                    ->default(0.00)
                    ->after('provider_pv_percentage')
                    ->comment('จำนวนเงิน PV (บาท)');
            }

            // Provider Earnings (รายได้สุทธิของ Provider)
            if (!Schema::hasColumn('service_bookings', 'provider_earnings')) {
                $table->decimal('provider_earnings', 10, 2)
                    ->default(0.00)
                    ->after('provider_pv_amount')
                    ->comment('รายได้สุทธิของ Provider = total - platform_fee - pv');
            }

            // System Revenue (รายได้รวมของระบบ: platform_fee + pv)
            if (!Schema::hasColumn('service_bookings', 'system_revenue')) {
                $table->decimal('system_revenue', 10, 2)
                    ->default(0.00)
                    ->after('provider_earnings')
                    ->comment('รายได้รวมของระบบ = platform_fee + pv');
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
        Schema::table('service_bookings', function (Blueprint $table) {
            $columns = [
                'platform_fee_percentage',
                'platform_fee_amount',
                'provider_pv_percentage',
                'provider_pv_amount',
                'provider_earnings',
                'system_revenue',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('service_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
