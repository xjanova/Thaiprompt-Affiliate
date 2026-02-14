<?php

namespace Database\Seeders;

use App\Models\WalletSetting;
use Illuminate\Database\Seeder;

/**
 * WalletSettingSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่า Wallet
 * ป้องกัน settings หายหลัง deploy
 */
class WalletSettingSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้น Wallet Settings
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Wallet Settings...');

        $settings = $this->getDefaultSettings();
        $created = 0;
        $skipped = 0;

        foreach ($settings as $setting) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            if (WalletSetting::where('key', $setting['key'])->exists()) {
                $skipped++;

                continue;
            }

            WalletSetting::create($setting);
            $created++;
        }

        if ($created > 0) {
            $this->command->info("  ✓ สร้าง Wallet Settings ใหม่ {$created} รายการ");
        }
        if ($skipped > 0) {
            $this->command->info("  - ข้าม {$skipped} รายการ (มีอยู่แล้ว)");
        }

        $this->command->info('✅ Seed ข้อมูล Wallet Settings สำเร็จ!');
    }

    /**
     * ดึงการตั้งค่าเริ่มต้น
     */
    protected function getDefaultSettings(): array
    {
        return [
            // Withdrawal settings
            [
                'key' => 'withdrawal_min_amount',
                'group' => 'limits',
                'value' => '100',
                'type' => 'number',
                'label' => 'ยอดถอนขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถถอนได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'withdrawal_max_amount',
                'group' => 'limits',
                'value' => '100000',
                'type' => 'number',
                'label' => 'ยอดถอนสูงสุด',
                'description' => 'จำนวนเงินสูงสุดที่สามารถถอนได้ต่อครั้ง (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'withdrawal_fee_type',
                'group' => 'fees',
                'value' => 'percentage',
                'type' => 'string',
                'label' => 'ประเภทค่าธรรมเนียมการถอน',
                'description' => 'fixed = คงที่, percentage = เปอร์เซ็นต์',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'withdrawal_fee_amount',
                'group' => 'fees',
                'value' => '2.5',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมการถอน',
                'description' => 'หากเป็น percentage ให้ระบุเป็น % (เช่น 2.5 = 2.5%), หากเป็น fixed ให้ระบุเป็นบาท',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'withdrawal_fee_min',
                'group' => 'fees',
                'value' => '10',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมขั้นต่ำ',
                'description' => 'ค่าธรรมเนียมขั้นต่ำต่อการถอน (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'withdrawal_fee_max',
                'group' => 'fees',
                'value' => '500',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมสูงสุด',
                'description' => 'ค่าธรรมเนียมสูงสุดต่อการถอน (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 6,
            ],
            // Tax settings
            [
                'key' => 'tax_enabled',
                'group' => 'tax',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานการหักภาษี',
                'description' => 'หักภาษี ณ ที่จ่ายจากการถอนเงิน',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'tax_percentage',
                'group' => 'tax',
                'value' => '3',
                'type' => 'percentage',
                'label' => 'เปอร์เซ็นต์ภาษี',
                'description' => 'เปอร์เซ็นต์ภาษีหัก ณ ที่จ่าย (%)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 8,
            ],
            [
                'key' => 'tax_threshold',
                'group' => 'tax',
                'value' => '1000',
                'type' => 'number',
                'label' => 'ยอดเงินขั้นต่ำที่ต้องเสียภาษี',
                'description' => 'ถอนเงินมากกว่ายอดนี้จึงจะถูกหักภาษี (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 9,
            ],
            // Transfer settings
            [
                'key' => 'transfer_fee_amount',
                'group' => 'fees',
                'value' => '5',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมการโอน',
                'description' => 'ค่าธรรมเนียมการโอนเงินระหว่างกระเป๋า (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'transfer_min_amount',
                'group' => 'limits',
                'value' => '10',
                'type' => 'number',
                'label' => 'ยอดโอนขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถโอนได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 11,
            ],
            // Deposit settings
            [
                'key' => 'deposit_min_amount',
                'group' => 'limits',
                'value' => '1',
                'type' => 'number',
                'label' => 'ยอดฝากขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถฝากได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 12,
            ],
            // Payment gateway settings
            [
                'key' => 'promptpay_enabled',
                'group' => 'payment_methods',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน PromptPay',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ PromptPay',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 13,
            ],
            [
                'key' => 'stripe_enabled',
                'group' => 'payment_methods',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน Stripe',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ Stripe',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 14,
            ],
            [
                'key' => 'paypal_enabled',
                'group' => 'payment_methods',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน PayPal',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ PayPal',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 15,
            ],
            // Withdrawal approval
            [
                'key' => 'withdrawal_requires_approval',
                'group' => 'general',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'การถอนเงินต้องได้รับการอนุมัติ',
                'description' => 'ต้องรอแอดมินอนุมัติก่อนจึงจะถอนเงินได้',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 16,
            ],
            [
                'key' => 'auto_approve_threshold',
                'group' => 'general',
                'value' => '0',
                'type' => 'number',
                'label' => 'ยอดเงินที่อนุมัติอัตโนมัติ',
                'description' => 'ยอดถอนที่น้อยกว่านี้จะได้รับการอนุมัติอัตโนมัติ (0 = ปิดใช้งาน)',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 17,
            ],
        ];
    }
}
