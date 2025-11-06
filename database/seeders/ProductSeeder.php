<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user as seller
        $seller = User::where('role', 'admin')->first() ?? User::first();

        if (!$seller) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        $categories = ProductCategory::all();

        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please seed categories first.');
            return;
        }

        // Products data organized by category name
        $productsData = [
            'อิเล็กทรอนิกส์' => [
                ['name' => 'โน้ตบุ๊ค Gaming ROG Strix G15', 'price' => 45990, 'compare_at_price' => 52990, 'brand' => 'ASUS', 'featured' => true],
                ['name' => 'แท็บเล็ต iPad Air M2', 'price' => 21900, 'compare_at_price' => 24900, 'brand' => 'Apple', 'featured' => true],
                ['name' => 'สมาร์ทโฟน Samsung Galaxy S24', 'price' => 28900, 'compare_at_price' => 32900, 'brand' => 'Samsung', 'featured' => false],
                ['name' => 'หูฟังบลูทูธ AirPods Pro 2', 'price' => 8990, 'compare_at_price' => 10490, 'brand' => 'Apple', 'featured' => false],
                ['name' => 'เม้าส์เกมมิ่ง Logitech G Pro X', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Logitech', 'featured' => false],
            ],
            'แฟชั่นและเครื่องแต่งกาย' => [
                ['name' => 'เสื้อโปโล Premium Cotton', 'price' => 890, 'compare_at_price' => 1290, 'brand' => 'Uniqlo', 'featured' => false],
                ['name' => 'กางเกงยีนส์ Slim Fit', 'price' => 1290, 'compare_at_price' => 1790, 'brand' => 'Levi\'s', 'featured' => true],
                ['name' => 'กระเป๋าสะพายหนังแท้', 'price' => 2990, 'compare_at_price' => 3990, 'brand' => 'Coach', 'featured' => false],
                ['name' => 'รองเท้าผ้าใบ Air Max', 'price' => 4290, 'compare_at_price' => 5290, 'brand' => 'Nike', 'featured' => true],
                ['name' => 'นาฬิกาข้อมือสมาร์ทวอทช์', 'price' => 8990, 'compare_at_price' => 10990, 'brand' => 'Apple Watch', 'featured' => false],
            ],
            'ความงามและของใช้ส่วนตัว' => [
                ['name' => 'เซรั่มวิตามินซี 20%', 'price' => 1290, 'compare_at_price' => 1790, 'brand' => 'Klairs', 'featured' => true],
                ['name' => 'ครีมกันแดด SPF 50+', 'price' => 690, 'compare_at_price' => 990, 'brand' => 'Biore', 'featured' => false],
                ['name' => 'น้ำหอม Chanel No.5', 'price' => 5490, 'compare_at_price' => 6990, 'brand' => 'Chanel', 'featured' => true],
                ['name' => 'ลิปสติกเนื้อแมท', 'price' => 590, 'compare_at_price' => 890, 'brand' => 'MAC', 'featured' => false],
                ['name' => 'แชมพูบำรุงเส้นผม', 'price' => 450, 'compare_at_price' => 650, 'brand' => 'Dove', 'featured' => false],
            ],
            'บ้านและสวน' => [
                ['name' => 'โซฟา 3 ที่นั่ง Modern Style', 'price' => 12900, 'compare_at_price' => 15900, 'brand' => 'IKEA', 'featured' => true],
                ['name' => 'โต๊ะทำงานไม้โอ๊ค', 'price' => 5490, 'compare_at_price' => 6990, 'brand' => 'Index Living', 'featured' => false],
                ['name' => 'ชุดเครื่องนอน 6 ชิ้น', 'price' => 2290, 'compare_at_price' => 2990, 'brand' => 'Lotus', 'featured' => false],
                ['name' => 'ต้นไม้ประดับในร่ม 5 ต้น', 'price' => 890, 'compare_at_price' => 1290, 'brand' => 'Local', 'featured' => false],
                ['name' => 'หม้อต้นไม้เซรามิค Set 3', 'price' => 590, 'compare_at_price' => 890, 'brand' => 'Homepro', 'featured' => false],
            ],
            'กีฬาและกิจกรรมกลางแจ้ง' => [
                ['name' => 'ดัมเบลปรับน้ำหนักได้ 20kg', 'price' => 3490, 'compare_at_price' => 4490, 'brand' => 'Reebok', 'featured' => true],
                ['name' => 'เสื่อโยคะ Premium 6mm', 'price' => 890, 'compare_at_price' => 1290, 'brand' => 'Manduka', 'featured' => false],
                ['name' => 'รองเท้าวิ่ง Ultraboost', 'price' => 5490, 'compare_at_price' => 6490, 'brand' => 'Adidas', 'featured' => true],
                ['name' => 'เต็นท์ตั้งแคมป์ 4 คน', 'price' => 4990, 'compare_at_price' => 6490, 'brand' => 'Coleman', 'featured' => false],
                ['name' => 'ถุงนอนกันหนาว', 'price' => 1790, 'compare_at_price' => 2290, 'brand' => 'Quechua', 'featured' => false],
            ],
            'หนังสือและเครื่องเขียน' => [
                ['name' => 'หนังสือ Rich Dad Poor Dad', 'price' => 350, 'compare_at_price' => 450, 'brand' => 'SE-ED', 'featured' => true],
                ['name' => 'ปากกาหมึกซึม Pilot', 'price' => 450, 'compare_at_price' => 650, 'brand' => 'Pilot', 'featured' => false],
                ['name' => 'สมุดบันทึกปกแข็ง A5', 'price' => 290, 'compare_at_price' => 390, 'brand' => 'Moleskine', 'featured' => false],
                ['name' => 'ชุดสีไม้ระบายสี 48 สี', 'price' => 890, 'compare_at_price' => 1190, 'brand' => 'Faber-Castell', 'featured' => false],
                ['name' => 'ไฟล์เก็บเอกสาร Set 5', 'price' => 390, 'compare_at_price' => 590, 'brand' => 'Kokuyo', 'featured' => false],
            ],
            'ของเล่นและงานอดิเรก' => [
                ['name' => 'โมเดล Gundam MG 1/100', 'price' => 1490, 'compare_at_price' => 1890, 'brand' => 'Bandai', 'featured' => true],
                ['name' => 'บอร์ดเกม Catan', 'price' => 1290, 'compare_at_price' => 1690, 'brand' => 'Catan Studio', 'featured' => false],
                ['name' => 'ตัวต่อ LEGO Creator', 'price' => 2490, 'compare_at_price' => 2990, 'brand' => 'LEGO', 'featured' => true],
                ['name' => 'ปริศนาจิ๊กซอว์ 1000 ชิ้น', 'price' => 590, 'compare_at_price' => 790, 'brand' => 'Ravensburger', 'featured' => false],
                ['name' => 'รถบังคับวิทยุ RC Car', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Traxxas', 'featured' => false],
            ],
            'อาหารและเครื่องดื่ม' => [
                ['name' => 'กาแฟคั่วบด Arabica 250g', 'price' => 290, 'compare_at_price' => 390, 'brand' => 'Doi Chaang', 'featured' => true],
                ['name' => 'ชาเขียวญี่ปุ่น Matcha', 'price' => 450, 'compare_at_price' => 650, 'brand' => 'Ito En', 'featured' => false],
                ['name' => 'น้ำผึ้งแท้ 100% ขนาด 500ml', 'price' => 350, 'compare_at_price' => 490, 'brand' => 'ผึ้งหลวง', 'featured' => false],
                ['name' => 'ขนมขบเคี้ยวมิกซ์ 10 ชนิด', 'price' => 590, 'compare_at_price' => 790, 'brand' => 'Tao Kae Noi', 'featured' => false],
                ['name' => 'ข้าวกล่องอินทรีย์ 5kg', 'price' => 450, 'compare_at_price' => 590, 'brand' => 'ข้าวหอมมะลิ', 'featured' => false],
            ],
            'สุขภาพและอาหารเสริม' => [
                ['name' => 'วิตามินซี 1000mg 100 เม็ด', 'price' => 590, 'compare_at_price' => 790, 'brand' => 'Blackmores', 'featured' => true],
                ['name' => 'โปรตีนเชค Whey Protein', 'price' => 1890, 'compare_at_price' => 2290, 'brand' => 'Optimum Nutrition', 'featured' => true],
                ['name' => 'น้ำมันปลา Omega-3', 'price' => 890, 'compare_at_price' => 1190, 'brand' => 'Nordic Naturals', 'featured' => false],
                ['name' => 'คอลลาเจนผง 200g', 'price' => 1290, 'compare_at_price' => 1690, 'brand' => 'Shiseido', 'featured' => false],
                ['name' => 'วิตามินบีรวม B-Complex', 'price' => 490, 'compare_at_price' => 690, 'brand' => 'Nature Made', 'featured' => false],
            ],
            'สัตว์เลี้ยง' => [
                ['name' => 'อาหารสุนัข Royal Canin 3kg', 'price' => 1290, 'compare_at_price' => 1590, 'brand' => 'Royal Canin', 'featured' => true],
                ['name' => 'ทรายแมวกลิ่นลาเวนเดอร์', 'price' => 290, 'compare_at_price' => 390, 'brand' => 'Ever Clean', 'featured' => false],
                ['name' => 'ของเล่นสุนัข Kong Classic', 'price' => 390, 'compare_at_price' => 540, 'brand' => 'Kong', 'featured' => false],
                ['name' => 'กรงแมว 3 ชั้น', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Smartcat', 'featured' => false],
                ['name' => 'ปลอกคอสุนัขมีไฟ LED', 'price' => 490, 'compare_at_price' => 690, 'brand' => 'Nite Ize', 'featured' => false],
            ],
            'แม่และเด็ก' => [
                ['name' => 'รถเข็นเด็ก 3 in 1', 'price' => 8990, 'compare_at_price' => 10990, 'brand' => 'Chicco', 'featured' => true],
                ['name' => 'ขวดนม Anti-Colic Set 3', 'price' => 890, 'compare_at_price' => 1190, 'brand' => 'Dr. Brown\'s', 'featured' => false],
                ['name' => 'ผ้าอ้อมเด็ก Size M 72 ชิ้น', 'price' => 590, 'compare_at_price' => 750, 'brand' => 'Pampers', 'featured' => false],
                ['name' => 'เครื่องปั่นอาหารเด็ก', 'price' => 2290, 'compare_at_price' => 2990, 'brand' => 'Philips Avent', 'featured' => true],
                ['name' => 'เปลไกวไฟฟ้า', 'price' => 5490, 'compare_at_price' => 6990, 'brand' => 'Graco', 'featured' => false],
            ],
            'เครื่องใช้ไฟฟ้าในบ้าน' => [
                ['name' => 'เครื่องซักผ้าฝาบน 12kg', 'price' => 8990, 'compare_at_price' => 10990, 'brand' => 'Samsung', 'featured' => true],
                ['name' => 'ไมโครเวฟดิจิตอล 28L', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Sharp', 'featured' => false],
                ['name' => 'หม้อหุงข้าวดิจิตอล 1.8L', 'price' => 2990, 'compare_at_price' => 3490, 'brand' => 'Zojirushi', 'featured' => true],
                ['name' => 'เครื่องดูดฝุ่นไร้สาย', 'price' => 8490, 'compare_at_price' => 9990, 'brand' => 'Dyson', 'featured' => true],
                ['name' => 'พัดลมไอเย็น 50L', 'price' => 4990, 'compare_at_price' => 5990, 'brand' => 'Honeywell', 'featured' => false],
            ],
            'รถยนต์และมอเตอร์ไซค์' => [
                ['name' => 'กล้องติดรถยนต์ 4K', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'BlackVue', 'featured' => true],
                ['name' => 'เบาะหนังหุ้มพวงมาลัย', 'price' => 890, 'compare_at_price' => 1190, 'brand' => 'Sparco', 'featured' => false],
                ['name' => 'ชุดไฟ LED แต่งรถ', 'price' => 1290, 'compare_at_price' => 1690, 'brand' => 'Philips', 'featured' => false],
                ['name' => 'ยางรถยนต์ 205/55R16', 'price' => 2490, 'compare_at_price' => 2990, 'brand' => 'Michelin', 'featured' => false],
                ['name' => 'น้ำมันเครื่อง Fully Synthetic', 'price' => 890, 'compare_at_price' => 1190, 'brand' => 'Mobil 1', 'featured' => false],
            ],
            'กล้องและอุปกรณ์ถ่ายภาพ' => [
                ['name' => 'กล้อง Mirrorless Sony A7 IV', 'price' => 89900, 'compare_at_price' => 95900, 'brand' => 'Sony', 'featured' => true],
                ['name' => 'เลนส์ 50mm f/1.8', 'price' => 8900, 'compare_at_price' => 9900, 'brand' => 'Canon', 'featured' => true],
                ['name' => 'ขาตั้งกล้องคาร์บอนไฟเบอร์', 'price' => 5490, 'compare_at_price' => 6490, 'brand' => 'Manfrotto', 'featured' => false],
                ['name' => 'กระเป๋ากล้อง Backpack', 'price' => 2990, 'compare_at_price' => 3490, 'brand' => 'Lowepro', 'featured' => false],
                ['name' => 'แฟลช Speedlite', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Godox', 'featured' => false],
            ],
            'นาฬิกาและแว่นตา' => [
                ['name' => 'นาฬิกา Automatic Swiss', 'price' => 45900, 'compare_at_price' => 52900, 'brand' => 'Tissot', 'featured' => true],
                ['name' => 'แว่นกันแดด Polarized', 'price' => 3490, 'compare_at_price' => 4290, 'brand' => 'Ray-Ban', 'featured' => true],
                ['name' => 'นาฬิกาดิจิตอล G-Shock', 'price' => 5490, 'compare_at_price' => 6490, 'brand' => 'Casio', 'featured' => false],
                ['name' => 'แว่นสายตา Blue Light Filter', 'price' => 1990, 'compare_at_price' => 2490, 'brand' => 'Zeiss', 'featured' => false],
                ['name' => 'สายนาฬิกาหนังแท้', 'price' => 890, 'compare_at_price' => 1290, 'brand' => 'Hirsch', 'featured' => false],
            ],
        ];

        $totalProducts = 0;

        foreach ($categories as $category) {
            if (isset($productsData[$category->name])) {
                foreach ($productsData[$category->name] as $productData) {
                    // Check if product exists to keep existing SKU and slug
                    $existingProduct = Product::where('name', $productData['name'])
                        ->where('category_id', $category->id)
                        ->first();

                    $sku = $existingProduct ? $existingProduct->sku : 'SKU-' . strtoupper(Str::random(8));
                    $slug = $existingProduct ? $existingProduct->slug : Str::slug($sku);

                    Product::updateOrCreate(
                        [
                            'name' => $productData['name'],
                            'category_id' => $category->id,
                        ],
                        [
                            'seller_id' => $seller->id,
                            'slug' => $slug,
                            'sku' => $sku,
                            'description' => $this->generateDescription($productData['name'], $category->name),
                            'short_description' => $this->generateShortDescription($productData['name']),
                            'price' => $productData['price'],
                            'compare_at_price' => $productData['compare_at_price'],
                            'cost_price' => $productData['price'] * 0.6,
                            'commission_rate' => rand(10, 30),
                            'brand' => $productData['brand'] ?? null,
                            'stock_quantity' => rand(10, 100),
                            'track_inventory' => true,
                            'low_stock_threshold' => 5,
                            'stock_status' => 'in_stock',
                            'is_active' => true,
                            'is_featured' => $productData['featured'],
                            'weight' => rand(100, 5000) / 100,
                            'dimensions' => rand(10, 50) . 'x' . rand(10, 50) . 'x' . rand(10, 50),
                            'rating_average' => rand(35, 50) / 10,
                            'rating_count' => rand(5, 200),
                            'sales_count' => rand(10, 500),
                            'view_count' => rand(50, 1000),
                            'meta_title' => $productData['name'],
                            'meta_description' => $this->generateShortDescription($productData['name']),
                            'tags' => json_encode([$category->name, $productData['brand'] ?? 'ทั่วไป']),
                        ]
                    );
                    $totalProducts++;
                }
            }
        }

        $this->command->info('✓ Created ' . $totalProducts . ' products across ' . $categories->count() . ' categories!');
    }

    /**
     * Generate product description
     */
    private function generateDescription(string $name, string $category): string
    {
        return "🌟 {$name}\n\n" .
               "✅ สินค้าคุณภาพพรีเมี่ยมจากหมวดหมู่{$category}\n" .
               "✅ ผลิตจากวัสดุคุณภาพดี ทนทาน\n" .
               "✅ การันตีความพึงพอใจ\n" .
               "✅ จัดส่งรวดเร็วทั่วประเทศ\n" .
               "✅ มีบริการหลังการขายที่เป็นเลิศ\n\n" .
               "🎁 โปรโมชั่นพิเศษ! สั่งซื้อวันนี้รับส่วนลดทันที\n" .
               "📦 ส่งฟรีเมื่อซื้อครบ 500 บาท\n" .
               "🔄 สามารถเปลี่ยนหรือคืนสินค้าได้ภายใน 7 วัน";
    }

    /**
     * Generate short description
     */
    private function generateShortDescription(string $name): string
    {
        return "{$name} คุณภาพพรีเมี่ยม ราคาพิเศษ จัดส่งฟรีทั่วประเทศ มีบริการหลังการขายที่เป็นเลิศ";
    }
}
