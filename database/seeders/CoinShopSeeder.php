<?php

namespace Database\Seeders;

use App\Models\CoinShopProduct;
use Illuminate\Database\Seeder;

/**
 * CoinShopSeeder
 *
 * สร้างข้อมูลตัวอย่างสินค้าในร้านค้า Video Coins
 */
class CoinShopSeeder extends Seeder
{
    /**
     * สร้างข้อมูลสินค้าตัวอย่าง
     */
    public function run(): void
    {
        $this->command->info('🛒 กำลัง seed ข้อมูล Coin Shop Products...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (CoinShopProduct::count() > 0) {
            $this->command->info('มีข้อมูลสินค้าอยู่แล้ว ข้าม...');

            return;
        }

        $products = [
            // Vouchers
            [
                'name' => '100 THB Voucher',
                'name_th' => 'บัตรกำนัล 100 บาท',
                'description' => 'Redeemable voucher for 100 THB',
                'description_th' => 'บัตรกำนัลมูลค่า 100 บาท ใช้ได้กับร้านค้าพันธมิตร',
                'type' => 'voucher',
                'category' => 'บัตรกำนัล',
                'icon' => '🎫',
                'price_coins' => 500,
                'original_price_coins' => null,
                'price_money' => 100,
                'value_amount' => 100,
                'value_unit' => 'THB',
                'stock_quantity' => null, // ไม่จำกัด
                'is_active' => true,
                'is_featured' => true,
                'is_new' => true,
                'priority' => 100,
                'sort_order' => 1,
                'use_expire_days' => 30,
            ],
            [
                'name' => '500 THB Voucher',
                'name_th' => 'บัตรกำนัล 500 บาท',
                'description' => 'Redeemable voucher for 500 THB',
                'description_th' => 'บัตรกำนัลมูลค่า 500 บาท ใช้ได้กับร้านค้าพันธมิตร',
                'type' => 'voucher',
                'category' => 'บัตรกำนัล',
                'icon' => '🎫',
                'price_coins' => 2000,
                'original_price_coins' => 2500, // มีส่วนลด
                'price_money' => 500,
                'value_amount' => 500,
                'value_unit' => 'THB',
                'stock_quantity' => 50,
                'is_active' => true,
                'is_featured' => true,
                'priority' => 90,
                'sort_order' => 2,
                'use_expire_days' => 60,
            ],

            // Coupons
            [
                'name' => '10% Discount Coupon',
                'name_th' => 'คูปองส่วนลด 10%',
                'description' => '10% off on your next purchase',
                'description_th' => 'รับส่วนลด 10% สำหรับการซื้อสินค้าครั้งถัดไป',
                'type' => 'coupon',
                'category' => 'คูปอง',
                'icon' => '🏷️',
                'price_coins' => 200,
                'price_money' => null,
                'value_amount' => 10,
                'value_unit' => '%',
                'stock_quantity' => 100,
                'is_active' => true,
                'is_new' => true,
                'priority' => 80,
                'sort_order' => 3,
                'use_expire_days' => 7,
            ],

            // EXP Boost
            [
                'name' => '2x EXP Boost (24 Hours)',
                'name_th' => 'บูสต์ EXP 2 เท่า (24 ชม.)',
                'description' => 'Double your EXP gains for 24 hours',
                'description_th' => 'รับ EXP 2 เท่าจากทุกกิจกรรมเป็นเวลา 24 ชั่วโมง',
                'type' => 'exp_boost',
                'category' => 'บูสต์',
                'icon' => '🚀',
                'price_coins' => 300,
                'value_amount' => 2,
                'value_unit' => 'x',
                'stock_quantity' => null,
                'is_active' => true,
                'is_featured' => true,
                'priority' => 95,
                'sort_order' => 4,
            ],
            [
                'name' => '3x EXP Boost (7 Days)',
                'name_th' => 'บูสต์ EXP 3 เท่า (7 วัน)',
                'description' => 'Triple your EXP gains for 7 days',
                'description_th' => 'รับ EXP 3 เท่าจากทุกกิจกรรมเป็นเวลา 7 วัน',
                'type' => 'exp_boost',
                'category' => 'บูสต์',
                'icon' => '🚀',
                'price_coins' => 1500,
                'original_price_coins' => 2100,
                'value_amount' => 3,
                'value_unit' => 'x',
                'stock_quantity' => 20,
                'is_active' => true,
                'is_limited' => true,
                'priority' => 94,
                'sort_order' => 5,
            ],

            // Privileges
            [
                'name' => 'VIP Badge (30 Days)',
                'name_th' => 'ตรา VIP (30 วัน)',
                'description' => 'Display VIP badge on your profile',
                'description_th' => 'แสดงตรา VIP บนโปรไฟล์ของคุณเป็นเวลา 30 วัน',
                'type' => 'privilege',
                'category' => 'สิทธิพิเศษ',
                'icon' => '⭐',
                'price_coins' => 1000,
                'value_amount' => 30,
                'value_unit' => 'วัน',
                'stock_quantity' => null,
                'is_active' => true,
                'priority' => 70,
                'sort_order' => 6,
                'use_expire_days' => 30,
            ],

            // Digital Items
            [
                'name' => 'Exclusive Wallpaper Pack',
                'name_th' => 'แพ็คภาพพื้นหลังพิเศษ',
                'description' => '10 exclusive high-quality wallpapers',
                'description_th' => 'รวมภาพพื้นหลังคุณภาพสูงสุดพิเศษ 10 ภาพ',
                'type' => 'digital',
                'category' => 'ดิจิทัล',
                'icon' => '💾',
                'price_coins' => 150,
                'value_amount' => 10,
                'value_unit' => 'ภาพ',
                'stock_quantity' => null,
                'is_active' => true,
                'priority' => 50,
                'sort_order' => 7,
            ],

            // TPIX Exchange
            [
                'name' => '100 TPIX',
                'name_th' => 'แลก 100 TPIX',
                'description' => 'Exchange coins for 100 TPIX tokens',
                'description_th' => 'แลก Coins เป็นเหรียญ TPIX จำนวน 100 เหรียญ',
                'type' => 'tpix',
                'category' => 'TPIX',
                'icon' => '🪙',
                'price_coins' => 1000,
                'value_amount' => 100,
                'value_unit' => 'TPIX',
                'stock_quantity' => 500,
                'min_level' => 3,
                'is_active' => true,
                'priority' => 60,
                'sort_order' => 8,
            ],

            // Physical (Example)
            [
                'name' => 'TP-Affiliate T-Shirt',
                'name_th' => 'เสื้อยืด TP-Affiliate',
                'description' => 'Exclusive TP-Affiliate branded T-shirt',
                'description_th' => 'เสื้อยืดลิมิเต็ดอิดิชั่นจาก TP-Affiliate',
                'type' => 'physical',
                'category' => 'ของที่ระลึก',
                'icon' => '👕',
                'price_coins' => 5000,
                'price_money' => 350,
                'value_amount' => 1,
                'value_unit' => 'ตัว',
                'stock_quantity' => 10,
                'is_active' => true,
                'is_limited' => true,
                'min_level' => 5,
                'require_kyc' => true,
                'priority' => 40,
                'sort_order' => 9,
            ],

            // Money Exchange
            [
                'name' => '50 THB Cash',
                'name_th' => 'เงินสด 50 บาท',
                'description' => 'Exchange coins for 50 THB cash',
                'description_th' => 'แลก Coins เป็นเงินสด 50 บาท โอนเข้าบัญชี',
                'type' => 'money',
                'category' => 'เงินสด',
                'icon' => '💵',
                'price_coins' => 3000,
                'price_money' => 50,
                'value_amount' => 50,
                'value_unit' => 'THB',
                'stock_quantity' => null,
                'min_level' => 5,
                'max_per_user' => 2, // จำกัด 2 ครั้งต่อคน
                'require_kyc' => true,
                'is_active' => true,
                'priority' => 30,
                'sort_order' => 10,
            ],
        ];

        foreach ($products as $productData) {
            CoinShopProduct::create($productData);
        }

        $this->command->info('✅ Seed ข้อมูล Coin Shop Products สำเร็จ! ('.count($products).' รายการ)');
    }
}
