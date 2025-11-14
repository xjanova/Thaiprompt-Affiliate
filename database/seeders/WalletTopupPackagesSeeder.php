<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

class WalletTopupPackagesSeeder extends Seeder
{
    /**
     * สร้างแพ็คเกจเติมเงิน Wallet
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed แพ็คเกจเติมเงิน Wallet...');

        // ✅ สร้างหมวดหมู่สำหรับ Wallet Topup (ถ้ายังไม่มี)
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'wallet-topup'],
            [
                'name' => 'เติมเงิน Wallet',
                'description' => 'แพ็คเกจเติมเงินเข้า Wallet',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        // ✅ หา admin user สำหรับเป็น seller
        $adminUser = User::where('is_admin', true)->first();
        if (!$adminUser) {
            $this->command->warn('ไม่พบ admin user สำหรับเป็น seller ของ topup packages');
            return;
        }

        // ✅ แพ็คเกจเติมเงิน
        $packages = [
            [
                'name' => '🪙 เติมเงิน 100 บาท',
                'slug' => 'topup-100',
                'price' => 100,
                'description' => 'เติมเงิน 100 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 100 บาท',
            ],
            [
                'name' => '🪙 เติมเงิน 300 บาท',
                'slug' => 'topup-300',
                'price' => 300,
                'description' => 'เติมเงิน 300 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 300 บาท',
            ],
            [
                'name' => '🪙 เติมเงิน 500 บาท',
                'slug' => 'topup-500',
                'price' => 500,
                'description' => 'เติมเงิน 500 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 500 บาท',
            ],
            [
                'name' => '💎 เติมเงิน 1,000 บาท',
                'slug' => 'topup-1000',
                'price' => 1000,
                'description' => 'เติมเงิน 1,000 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 1,000 บาท',
            ],
            [
                'name' => '💎 เติมเงิน 2,000 บาท',
                'slug' => 'topup-2000',
                'price' => 2000,
                'description' => 'เติมเงิน 2,000 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 2,000 บาท',
            ],
            [
                'name' => '💎 เติมเงิน 5,000 บาท',
                'slug' => 'topup-5000',
                'price' => 5000,
                'description' => 'เติมเงิน 5,000 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 5,000 บาท',
            ],
            [
                'name' => '👑 เติมเงิน 10,000 บาท',
                'slug' => 'topup-10000',
                'price' => 10000,
                'description' => 'เติมเงิน 10,000 บาท เข้า Wallet ของคุณ',
                'short_description' => 'เติม 10,000 บาท',
            ],
        ];

        foreach ($packages as $packageData) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $existing = Product::where('slug', $packageData['slug'])->first();
            if ($existing) {
                $this->command->info("  ✓ {$packageData['name']} มีอยู่แล้ว");
                continue;
            }

            // สร้างแพ็คเกจ
            Product::create([
                'seller_id' => $adminUser->id,
                'category_id' => $category->id,
                'name' => $packageData['name'],
                'slug' => $packageData['slug'],
                'sku' => 'TOPUP-' . strtoupper($packageData['slug']),
                'description' => $packageData['description'],
                'short_description' => $packageData['short_description'],
                'price' => $packageData['price'],
                'compare_at_price' => null,
                'cost_price' => $packageData['price'], // ไม่มี cost
                'stock_quantity' => 999999, // ไม่จำกัดจำนวน
                'track_inventory' => false, // ไม่ track stock
                'stock_status' => 'in_stock',
                'is_virtual' => true, // ✅ สินค้าดิจิทัล
                'is_active' => true,
                'is_featured' => false,
                'is_public_approved' => true,
                'public_approved_at' => now(),
                'public_approved_by' => $adminUser->id,
                'published_at' => now(),
                'commission_rate' => 0, // ไม่มี commission
                'customer_cashback' => 0, // ไม่มี cashback
                'cashback_percentage' => 0,
            ]);

            $this->command->info("  ✓ สร้าง {$packageData['name']}");
        }

        $this->command->info('✅ Seed แพ็คเกจเติมเงิน Wallet สำเร็จ!');
    }
}
