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
        $settings = [
            // VAT Settings
            [
                'key' => 'vat_rate',
                'value' => '7.00',
                'type' => 'decimal',
                'group' => 'tax',
                'label' => 'อัตราภาษี VAT (%)',
                'description' => 'ภาษีมูลค่าเพิ่มที่หักจากผู้ขาย/ผู้ให้บริการ และผู้รับคอม MLM',
                'allowed_values' => json_encode(['min' => 0, 'max' => 100, 'step' => 0.01]),
                'is_editable' => true,
                'display_order' => 100,
            ],
            [
                'key' => 'vat_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'tax',
                'label' => 'เปิดใช้งาน VAT',
                'description' => 'เปิด/ปิดการคำนวณภาษี VAT',
                'is_editable' => true,
                'display_order' => 101,
            ],

            // Withdrawal Fee Settings
            [
                'key' => 'withdrawal_fee_percentage',
                'value' => '2.00',
                'type' => 'decimal',
                'group' => 'fees',
                'label' => 'ค่าธรรมเนียมถอนเงิน (%)',
                'description' => 'ค่าธรรมเนียมเมื่อถอนเงินจาก Wallet เป็นเงินสด',
                'allowed_values' => json_encode(['min' => 0, 'max' => 10, 'step' => 0.01]),
                'is_editable' => true,
                'display_order' => 110,
            ],
            [
                'key' => 'withdrawal_min_amount',
                'value' => '100.00',
                'type' => 'decimal',
                'group' => 'fees',
                'label' => 'ยอดถอนขั้นต่ำ (บาท)',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถถอนได้',
                'allowed_values' => json_encode(['min' => 0, 'max' => 10000]),
                'is_editable' => true,
                'display_order' => 111,
            ],

            // PV to Money Conversion
            [
                'key' => 'pv_to_money_rate',
                'value' => '1.00',
                'type' => 'decimal',
                'group' => 'mlm',
                'label' => 'อัตราแปลง PV เป็นเงิน',
                'description' => '1 PV = X บาท (สำหรับคำนวณคอมมิชชัน MLM)',
                'allowed_values' => json_encode(['min' => 0.01, 'max' => 100, 'step' => 0.01]),
                'is_editable' => true,
                'display_order' => 120,
            ],
            [
                'key' => 'pv_calculation_method',
                'value' => 'price_based',
                'type' => 'select',
                'group' => 'mlm',
                'label' => 'วิธีคำนวณ PV',
                'description' => 'price_based = PV ตามราคา, custom = ตั้ง PV เอง',
                'allowed_values' => json_encode(['price_based', 'custom', 'hybrid']),
                'is_editable' => true,
                'display_order' => 121,
            ],

            // MLM Commission Settings
            [
                'key' => 'mlm_commission_deduct_from_seller',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mlm',
                'label' => 'หักค่าคอม MLM จากผู้ขาย',
                'description' => 'true = หักจากผู้ขาย, false = Platform จ่าย',
                'is_editable' => true,
                'display_order' => 122,
            ],
            [
                'key' => 'mlm_commission_instant_transfer',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mlm',
                'label' => 'โอนค่าคอม MLM ทันที',
                'description' => 'โอนเข้า wallet ทันทีหลังชำระเงิน (หักภาษีแล้ว)',
                'is_editable' => true,
                'display_order' => 123,
            ],

            // Cashback Settings
            [
                'key' => 'cashback_deduct_from_seller',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'marketing',
                'label' => 'Cashback หักจากผู้ขาย',
                'description' => 'true = ผู้ขายจ่ายเอง (ค่าการตลาด), false = Platform จ่าย',
                'is_editable' => true,
                'display_order' => 130,
            ],

            // Promotion Settings
            [
                'key' => 'platform_promotion_max_percentage',
                'value' => '50',
                'type' => 'integer',
                'group' => 'marketing',
                'label' => 'ส่วนลดจากระบบสูงสุด (%)',
                'description' => 'ส่วนลดจาก Platform ไม่เกิน % ของ Platform Fee',
                'allowed_values' => json_encode(['min' => 0, 'max' => 100]),
                'is_editable' => true,
                'display_order' => 131,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('mlm_global_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
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
