<?php

namespace Database\Seeders;

use App\Models\PlatformWallet;
use App\Models\PayoutSetting;
use Illuminate\Database\Seeder;

/**
 * PlatformRevenueSeeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ Platform Revenue
 * - Platform Wallets (ค่า Fee, VAT, MLM Pool, Reserve)
 * - Payout Settings (การตั้งค่าการจ่ายเงิน)
 */
class PlatformRevenueSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้น Platform Revenue
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Platform Revenue...');

        $this->seedPlatformWallets();
        $this->seedPayoutSettings();

        $this->command->info('✅ Seed ข้อมูล Platform Revenue สำเร็จ!');
    }

    /**
     * สร้าง Platform Wallets
     *
     * @return void
     */
    protected function seedPlatformWallets(): void
    {
        $wallets = [
            [
                'name' => 'ค่าธรรมเนียม Platform',
                'slug' => 'fee',
                'type' => 'platform_fee',
                'description' => 'กระเป๋าเก็บค่าธรรมเนียมจากการขายสินค้าและบริการ',
            ],
            [
                'name' => 'ภาษี VAT',
                'slug' => 'vat',
                'type' => 'vat',
                'description' => 'กระเป๋าเก็บภาษีมูลค่าเพิ่ม (VAT 7%)',
            ],
            [
                'name' => 'กองทุน MLM',
                'slug' => 'mlm_pool',
                'type' => 'mlm_pool',
                'description' => 'กระเป๋าเก็บเงินสำหรับจ่าย MLM Commission',
            ],
            [
                'name' => 'เงินสำรอง',
                'slug' => 'reserve',
                'type' => 'reserve',
                'description' => 'กระเป๋าเงินสำรองสำหรับกรณีฉุกเฉิน',
            ],
            [
                'name' => 'กองทุนคืนเงิน',
                'slug' => 'refund_pool',
                'type' => 'refund_pool',
                'description' => 'กระเป๋าสำหรับคืนเงินลูกค้า',
            ],
            [
                'name' => 'รายได้ร้านแอดมิน',
                'slug' => 'admin_shop',
                'type' => 'admin_shop',
                'description' => 'กระเป๋ารายได้จากการขายสินค้า Official Shop (ร้านแอดมิน)',
            ],
            [
                'name' => 'รายได้บริการแอดมิน',
                'slug' => 'admin_services',
                'type' => 'admin_services',
                'description' => 'กระเป๋ารายได้จากบริการต่างๆ ของแอดมิน (ค่าสมัคร, ค่าบริการ, etc.)',
            ],
        ];

        foreach ($wallets as $wallet) {
            // ตรวจสอบก่อนสร้าง (idempotent)
            if (PlatformWallet::where('slug', $wallet['slug'])->exists()) {
                $this->command->info("  - Platform Wallet '{$wallet['slug']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            PlatformWallet::create([
                'name' => $wallet['name'],
                'slug' => $wallet['slug'],
                'type' => $wallet['type'],
                'description' => $wallet['description'],
                'balance' => 0,
                'total_income' => 0,
                'total_expense' => 0,
                'total_transferred' => 0,
                'is_active' => true,
            ]);

            $this->command->info("  - สร้าง Platform Wallet '{$wallet['name']}' สำเร็จ");
        }
    }

    /**
     * สร้าง Payout Settings
     *
     * @return void
     */
    protected function seedPayoutSettings(): void
    {
        $settings = [
            [
                'name' => 'รายได้จากการขาย',
                'slug' => 'seller_sale',
                'earning_type' => 'seller_sale',
                'payout_mode' => 'manual',
                'min_payout' => 100,
                'max_payout' => null,
                'fee_fixed' => 0,
                'fee_percentage' => 0,
                'holding_days' => 7,
                'requires_approval' => true,
                'is_active' => true,
                'schedule' => [
                    'type' => 'biweekly',
                    'hour' => 9,
                ],
            ],
            [
                'name' => 'MLM Commission',
                'slug' => 'mlm_commission',
                'earning_type' => 'mlm_commission',
                'payout_mode' => 'auto_schedule',
                'min_payout' => 50,
                'max_payout' => null,
                'fee_fixed' => 0,
                'fee_percentage' => 0,
                'holding_days' => 3,
                'requires_approval' => false,
                'is_active' => true,
                'schedule' => [
                    'type' => 'weekly',
                    'day_of_week' => 1, // Monday
                    'hour' => 9,
                ],
            ],
            [
                'name' => 'Affiliate Commission',
                'slug' => 'affiliate_commission',
                'earning_type' => 'affiliate_commission',
                'payout_mode' => 'manual',
                'min_payout' => 100,
                'max_payout' => null,
                'fee_fixed' => 0,
                'fee_percentage' => 0,
                'holding_days' => 14,
                'requires_approval' => true,
                'is_active' => true,
                'schedule' => [
                    'type' => 'monthly',
                    'day_of_month' => 1,
                    'hour' => 9,
                ],
            ],
        ];

        foreach ($settings as $setting) {
            // ตรวจสอบก่อนสร้าง (idempotent)
            if (PayoutSetting::where('slug', $setting['slug'])->exists()) {
                $this->command->info("  - Payout Setting '{$setting['slug']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            PayoutSetting::create($setting);

            $this->command->info("  - สร้าง Payout Setting '{$setting['name']}' สำเร็จ");
        }
    }
}
