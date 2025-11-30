<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * เพิ่มการตั้งค่า VAT, Withdrawal Fee, และ PV to Money Rate
     *
     * ใช้ mlm_global_settings table (key-value store)
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง mlm_global_settings มีอยู่หรือไม่
        if (!\Illuminate\Support\Facades\Schema::hasTable('mlm_global_settings')) {
            return;
        }

        // ใช้เฉพาะคอลัมน์ที่มีอยู่ในตาราง (key, value)
        $settings = [
            // VAT Settings
            'vat_rate' => '7.00',
            'vat_enabled' => '1',

            // Withdrawal Fee Settings
            'withdrawal_fee_percentage' => '2.00',
            'withdrawal_min_amount' => '100.00',

            // PV to Money Conversion
            'pv_to_money_rate' => '1.00',
            'pv_calculation_method' => 'price_based',

            // MLM Commission Settings
            'mlm_commission_deduct_from_seller' => '1',
            'mlm_commission_instant_transfer' => '1',

            // Cashback Settings
            'cashback_deduct_from_seller' => '1',

            // Promotion Settings
            'platform_promotion_max_percentage' => '50',
        ];

        foreach ($settings as $key => $value) {
            DB::table('mlm_global_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'key' => $key,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $keys = [
            'vat_rate',
            'vat_enabled',
            'withdrawal_fee_percentage',
            'withdrawal_min_amount',
            'pv_to_money_rate',
            'pv_calculation_method',
            'mlm_commission_deduct_from_seller',
            'mlm_commission_instant_transfer',
            'cashback_deduct_from_seller',
            'platform_promotion_max_percentage',
        ];

        DB::table('mlm_global_settings')->whereIn('key', $keys)->delete();
    }
};
