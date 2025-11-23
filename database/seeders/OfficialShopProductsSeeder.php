<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * Official Shop Products Seeder
 *
 * สร้างข้อมูลสินค้าสำหรับร้านของระบบ (Official Shop)
 * - สร้าง official seller account สำหรับร้านของระบบ
 * - คุณภาพสูง พร้อมรูปภาพและรายละเอียดครบถ้วน
 * - คอมมิชชั่นสูงสุด 40%
 */
class OfficialShopProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed สินค้าของระบบ (Official Shop)...');

        // ตรวจสอบว่ามี category หรือยัง
        $categories = ProductCategory::active()->get();

        if ($categories->isEmpty()) {
            $this->command->warn('⚠️  ไม่พบ Product Category กรุณา seed ProductCategorySeeder ก่อน');
            return;
        }

        // สร้าง/หา Official Shop Seller Account
        $officialSeller = $this->getOrCreateOfficialSeller();

        // สินค้าตัวอย่างสำหรับร้านของระบบ
        $officialProducts = [
            // หมวด: เทคโนโลยี / อุปกรณ์อิเล็กทรอนิกส์
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'slug' => 'iphone-15-pro-max-256gb',
                'sku' => 'OFFICIAL-IP15PM-256',
                'description' => 'iPhone 15 Pro Max รุ่นล่าสุดพร้อมชิป A17 Pro ที่ทรงพลังที่สุด กล้อง 48MP และดีไซน์ไทเทเนียมระดับพรีเมียม',
                'short_description' => 'iPhone รุ่นท็อปสุดล่าสุด ประสิทธิภาพสูงสุด',
                'price' => 49900.00,
                'compare_at_price' => 54900.00,
                'stock_quantity' => 50,
                'brand' => 'Apple',
                'commission_rate' => 40.00,
                'is_featured' => true,
                'category_keyword' => 'electronics',
            ],
            [
                'name' => 'MacBook Air M3 15" 16GB/512GB',
                'slug' => 'macbook-air-m3-15-16gb-512gb',
                'sku' => 'OFFICIAL-MBA-M3-15',
                'description' => 'MacBook Air ขนาด 15 นิ้ว พร้อมชิป M3 ประสิทธิภาพสูง RAM 16GB และ SSD 512GB เหมาะสำหรับงานสร้างสรรค์และมัลติทาสก์',
                'short_description' => 'MacBook Air M3 ขนาดใหญ่ ประสิทธิภาพสูง',
                'price' => 54900.00,
                'compare_at_price' => 59900.00,
                'stock_quantity' => 30,
                'brand' => 'Apple',
                'commission_rate' => 35.00,
                'is_featured' => true,
                'category_keyword' => 'electronics',
            ],

            // หมวด: ไลฟ์สไตล์ / แฟชั่น
            [
                'name' => 'Apple Watch Series 9 GPS 45mm',
                'slug' => 'apple-watch-series-9-gps-45mm',
                'sku' => 'OFFICIAL-AW9-45',
                'description' => 'Apple Watch Series 9 พร้อมชิป S9 ที่เร็วขึ้น จอสว่างขึ้น และฟีเจอร์ด้านสุขภาพครบครัน',
                'short_description' => 'นาฬิกาอัจฉริยะรุ่นล่าสุดจาก Apple',
                'price' => 15900.00,
                'compare_at_price' => 17900.00,
                'stock_quantity' => 100,
                'brand' => 'Apple',
                'commission_rate' => 30.00,
                'is_featured' => true,
                'category_keyword' => 'fashion',
            ],
            [
                'name' => 'AirPods Pro (2nd generation)',
                'slug' => 'airpods-pro-2nd-generation',
                'sku' => 'OFFICIAL-APP-2GEN',
                'description' => 'AirPods Pro รุ่นที่ 2 พร้อม Active Noise Cancellation ที่ดีขึ้น เสียงคุณภาพสูง และแบตเตอรี่ทนนานขึ้น',
                'short_description' => 'หูฟังไร้สายระดับ Pro พร้อมตัดเสียงรบกวน',
                'price' => 8990.00,
                'compare_at_price' => 9990.00,
                'stock_quantity' => 150,
                'brand' => 'Apple',
                'commission_rate' => 25.00,
                'is_featured' => false,
                'category_keyword' => 'electronics',
            ],

            // หมวด: คอร์สเรียน / การศึกษา
            [
                'name' => 'คอร์สเรียน Affiliate Marketing Pro',
                'slug' => 'course-affiliate-marketing-pro',
                'sku' => 'OFFICIAL-COURSE-AFF',
                'description' => 'คอร์สเรียนออนไลน์ทำ Affiliate Marketing ตั้งแต่เริ่มต้นจนถึงระดับมืออาชีพ พร้อมกลยุทธ์การทำเงินจริง',
                'short_description' => 'เรียนรู้การทำ Affiliate Marketing แบบครบวงจร',
                'price' => 4990.00,
                'compare_at_price' => 7990.00,
                'stock_quantity' => 999,
                'brand' => 'TP Affiliate',
                'commission_rate' => 40.00,
                'is_featured' => true,
                'is_virtual' => true,
                'category_keyword' => 'courses',
            ],
            [
                'name' => 'คอร์ส Digital Marketing 2024',
                'slug' => 'course-digital-marketing-2024',
                'sku' => 'OFFICIAL-COURSE-DM',
                'description' => 'คอร์สเรียน Digital Marketing ครบวงจร เทคนิคการตลาดออนไลน์ยุค 2024 พร้อม Case Study จริง',
                'short_description' => 'Digital Marketing สำหรับยุค 2024',
                'price' => 5990.00,
                'compare_at_price' => 9990.00,
                'stock_quantity' => 999,
                'brand' => 'TP Affiliate',
                'commission_rate' => 40.00,
                'is_featured' => true,
                'is_virtual' => true,
                'category_keyword' => 'courses',
            ],

            // หมวด: บริการ / Software
            [
                'name' => 'TP Affiliate Pro License (1 Year)',
                'slug' => 'tp-affiliate-pro-license-1-year',
                'sku' => 'OFFICIAL-LIC-PRO-1Y',
                'description' => 'License TP Affiliate Pro แบบ 1 ปี ระบบ MLM ครบครัน พร้อมฟีเจอร์ทั้งหมด และ Support ตลอดอายุการใช้งาน',
                'short_description' => 'ระบบ Affiliate Marketing & MLM ระดับ Pro',
                'price' => 29900.00,
                'compare_at_price' => 39900.00,
                'stock_quantity' => 999,
                'brand' => 'TP Affiliate',
                'commission_rate' => 35.00,
                'is_featured' => true,
                'is_virtual' => true,
                'category_keyword' => 'software',
            ],
            [
                'name' => 'AI Bot Premium Package (Monthly)',
                'slug' => 'ai-bot-premium-package-monthly',
                'sku' => 'OFFICIAL-AIBOT-PREMIUM',
                'description' => 'แพ็กเกจ AI Bot Premium รายเดือน พร้อม AI ตอบกลับอัตโนมัติ รองรับหลายภาษา และฟีเจอร์พิเศษ',
                'short_description' => 'AI Bot ระดับ Premium รายเดือน',
                'price' => 999.00,
                'compare_at_price' => 1499.00,
                'stock_quantity' => 999,
                'brand' => 'TP Affiliate',
                'commission_rate' => 30.00,
                'is_featured' => false,
                'is_virtual' => true,
                'category_keyword' => 'software',
            ],

            // หมวด: สุขภาพ
            [
                'name' => 'ชุดวิตามินรวม Premium Set',
                'slug' => 'premium-vitamin-set',
                'sku' => 'OFFICIAL-HEALTH-VIT',
                'description' => 'ชุดวิตามินรวมครบครัน ได้รับการรับรองจาก FDA พร้อมสารอาหารครบถ้วนสำหรับคนทุกเพศทุกวัย',
                'short_description' => 'วิตามินรวมคุณภาพพรีเมียม',
                'price' => 1990.00,
                'compare_at_price' => 2990.00,
                'stock_quantity' => 200,
                'brand' => 'Health Pro',
                'commission_rate' => 25.00,
                'is_featured' => false,
                'category_keyword' => 'health',
            ],
            [
                'name' => 'เครื่องวัดความดันโลหิตดิจิตอล',
                'slug' => 'digital-blood-pressure-monitor',
                'sku' => 'OFFICIAL-HEALTH-BP',
                'description' => 'เครื่องวัดความดันโลหิตแบบดิจิตอล ความแม่นยำสูง ใช้งานง่าย พร้อมหน่วยความจำ 90 ครั้ง',
                'short_description' => 'วัดความดันได้เองที่บ้าน แม่นยำ',
                'price' => 2990.00,
                'compare_at_price' => 3990.00,
                'stock_quantity' => 80,
                'brand' => 'MedTech',
                'commission_rate' => 28.00,
                'is_featured' => false,
                'category_keyword' => 'health',
            ],
        ];

        $createdCount = 0;

        foreach ($officialProducts as $productData) {
            // หา category ที่ตรงกับ keyword
            $category = $categories->first(function ($cat) use ($productData) {
                return Str::contains(Str::lower($cat->slug), $productData['category_keyword']);
            });

            // ถ้าไม่เจอ ใช้ category แรก
            if (!$category) {
                $category = $categories->first();
            }

            // ตรวจสอบว่ามีสินค้านี้อยู่แล้วหรือไม่
            if (Product::where('sku', $productData['sku'])->exists()) {
                $this->command->info("   ⏭️  ข้าม: {$productData['name']} (มีอยู่แล้ว)");
                continue;
            }

            // สร้างสินค้า
            Product::create([
                'seller_id' => $officialSeller->id, // ✅ ใช้ Official Shop Seller ID
                'category_id' => $category->id,
                'name' => $productData['name'],
                'slug' => $productData['slug'],
                'sku' => $productData['sku'],
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'price' => $productData['price'],
                'compare_at_price' => $productData['compare_at_price'],
                'stock_quantity' => $productData['stock_quantity'],
                'stock_status' => 'in_stock',
                'brand' => $productData['brand'],
                'commission_rate' => $productData['commission_rate'],
                'is_active' => true,
                'is_featured' => $productData['is_featured'],
                'is_virtual' => $productData['is_virtual'] ?? false,
                'is_public_approved' => true,
                'public_approved_at' => now(),
                'published_at' => now(),
                'rating_average' => rand(40, 50) / 10, // 4.0 - 5.0
                'rating_count' => rand(10, 100),
                'view_count' => rand(100, 1000),
                'sales_count' => rand(10, 500),
                'main_image_url' => 'https://via.placeholder.com/800x800.png?text=' . urlencode($productData['name']),
            ]);

            $createdCount++;
            $this->command->info("   ✅ สร้าง: {$productData['name']}");
        }

        $this->command->info("✨ สร้างสินค้าของระบบสำเร็จ: {$createdCount} สินค้า");
    }

    /**
     * สร้างหรือหา Official Shop Seller Account
     *
     * @return User
     */
    protected function getOrCreateOfficialSeller(): User
    {
        // ตรวจสอบว่ามี official seller อยู่แล้วหรือไม่
        $seller = User::where('email', 'official-shop@thaiprompt.com')
            ->orWhere('username', 'official-shop')
            ->first();

        if ($seller) {
            $this->command->info('   ✅ พบ Official Shop Seller: ' . $seller->name);
            return $seller;
        }

        // สร้าง Official Shop Seller Account ใหม่
        $seller = User::create([
            'name' => 'Official Shop',
            'username' => 'official-shop',
            'email' => 'official-shop@thaiprompt.com',
            'password' => Hash::make('OfficialShop@2024'), // Password แรงๆ
            'role' => 'seller', // หรือ admin ก็ได้
            'is_active' => true,
            'email_verified_at' => now(),
            'profile_complete' => true,
        ]);

        $this->command->info('   ✅ สร้าง Official Shop Seller: ' . $seller->name);
        $this->command->warn('   ⚠️  Username: official-shop');
        $this->command->warn('   ⚠️  Password: OfficialShop@2024');

        return $seller;
    }
}
