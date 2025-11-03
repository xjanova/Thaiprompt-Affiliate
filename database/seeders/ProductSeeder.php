<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user as seller (or admin)
        $seller = User::first();

        if (!$seller) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        $categories = ProductCategory::all();

        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please seed categories first.');
            return;
        }

        $products = [
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'prompt-engineering')->first()->id ?? 1,
                'name' => 'คอร์ส Prompt Engineering สำหรับมือใหม่',
                'slug' => 'prompt-engineering-beginner',
                'sku' => 'COURSE-PE-001',
                'description' => 'เรียนรู้การเขียน Prompt ที่มีประสิทธิภาพสำหรับ AI Models ต่างๆ เหมาะสำหรับผู้เริ่มต้น',
                'price' => 1990.00,
                'compare_at_price' => 2990.00,
                'cost_price' => 500.00,
                'commission_rate' => 15.00,
                'stock_quantity' => 100,
                'track_inventory' => false, // Digital product
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'คอร์ส Prompt Engineering สำหรับมือใหม่',
                'meta_description' => 'เรียนรู้การเขียน Prompt ที่มีประสิทธิภาพ',
                'tags' => json_encode(['AI', 'Prompt', 'Course']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'chatgpt-mastery')->first()->id ?? 1,
                'name' => 'ChatGPT Mastery: จากมือใหม่สู่ผู้เชี่ยวชาญ',
                'slug' => 'chatgpt-mastery-complete',
                'sku' => 'COURSE-CGM-001',
                'description' => 'คอร์สเรียน ChatGPT แบบครบวงจร ตั้งแต่พื้นฐานจนถึงการใช้งานขั้นสูง พร้อมเทคนิคลับจากมืออาชีพ',
                'price' => 2990.00,
                'compare_at_price' => 4990.00,
                'cost_price' => 800.00,
                'commission_rate' => 20.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'ChatGPT Mastery Course',
                'meta_description' => 'เรียน ChatGPT จากพื้นฐานถึงขั้นสูง',
                'tags' => json_encode(['ChatGPT', 'AI', 'Course', 'Complete']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'ebooks')->first()->id ?? 2,
                'name' => 'E-Book: AI สำหรับธุรกิจ SME',
                'slug' => 'ai-for-sme-ebook',
                'sku' => 'EBOOK-AI-001',
                'description' => 'หนังสืออิเล็กทรอนิกส์ที่สอนการนำ AI มาใช้เพิ่มผลผลิตในธุรกิจ SME',
                'price' => 590.00,
                'compare_at_price' => 990.00,
                'cost_price' => 100.00,
                'commission_rate' => 30.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'AI สำหรับธุรกิจ SME',
                'meta_description' => 'เรียนรู้การนำ AI มาใช้ในธุรกิจ',
                'tags' => json_encode(['AI', 'Business', 'E-Book', 'SME']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'business-templates')->first()->id ?? 3,
                'name' => 'ชุด Template การตลาดด้วย AI (50+ แบบ)',
                'slug' => 'ai-marketing-templates-50',
                'sku' => 'TEMP-MKT-001',
                'description' => 'รวม Template สำหรับการตลาดด้วย AI มากกว่า 50 แบบ ครอบคลุมทุกช่องทาง',
                'price' => 1490.00,
                'compare_at_price' => 2490.00,
                'cost_price' => 300.00,
                'commission_rate' => 25.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'AI Marketing Templates',
                'meta_description' => '50+ Template การตลาดด้วย AI',
                'tags' => json_encode(['Template', 'Marketing', 'AI']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'software-tools')->first()->id ?? 4,
                'name' => 'AI Content Generator Pro (รายปี)',
                'slug' => 'ai-content-generator-pro-yearly',
                'sku' => 'SOFT-ACG-001',
                'description' => 'ซอฟต์แวร์สร้างคอนเทนต์ด้วย AI รองรับภาษาไทย มากกว่า 100 เทมเพลต',
                'price' => 4990.00,
                'compare_at_price' => 8990.00,
                'cost_price' => 1000.00,
                'commission_rate' => 10.00,
                'stock_quantity' => 50,
                'track_inventory' => true,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'AI Content Generator Pro',
                'meta_description' => 'ซอฟต์แวร์สร้างคอนเทนต์ด้วย AI',
                'tags' => json_encode(['Software', 'AI', 'Content', 'Generator']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'ai-courses')->first()->id ?? 1,
                'name' => 'คอร์ส AI Image Generation ด้วย Midjourney & DALL-E',
                'slug' => 'ai-image-generation-course',
                'sku' => 'COURSE-IMG-001',
                'description' => 'เรียนรู้การสร้างภาพด้วย AI แบบมืออาชีพ ใช้ได้ทั้ง Midjourney และ DALL-E',
                'price' => 3490.00,
                'compare_at_price' => 5990.00,
                'cost_price' => 900.00,
                'commission_rate' => 18.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'AI Image Generation Course',
                'meta_description' => 'เรียน Midjourney & DALL-E',
                'tags' => json_encode(['AI', 'Image', 'Midjourney', 'DALL-E']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'ebooks')->first()->id ?? 2,
                'name' => 'คู่มือ Prompt Engineering ฉบับสมบูรณ์ (PDF)',
                'slug' => 'prompt-engineering-guide-complete',
                'sku' => 'EBOOK-PE-001',
                'description' => 'คู่มือครบวงจรสำหรับ Prompt Engineering พร้อมตัวอย่างมากกว่า 200 Prompt',
                'price' => 890.00,
                'compare_at_price' => 1490.00,
                'cost_price' => 150.00,
                'commission_rate' => 35.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'คู่มือ Prompt Engineering',
                'meta_description' => 'คู่มือครบวงจร 200+ Prompt',
                'tags' => json_encode(['E-Book', 'Prompt', 'Guide']),
            ],
            [
                'seller_id' => $seller->id,
                'category_id' => $categories->where('slug', 'business-templates')->first()->id ?? 3,
                'name' => 'Business Plan Template ด้วย AI (ภาษาไทย)',
                'slug' => 'business-plan-template-ai-thai',
                'sku' => 'TEMP-BP-001',
                'description' => 'เทมเพลตแผนธุรกิจที่ใช้ AI ช่วยสร้าง เหมาะสำหรับ Startup และ SME',
                'price' => 790.00,
                'compare_at_price' => 1290.00,
                'cost_price' => 150.00,
                'commission_rate' => 28.00,
                'stock_quantity' => 100,
                'track_inventory' => false,
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Business Plan Template AI',
                'meta_description' => 'แผนธุรกิจด้วย AI',
                'tags' => json_encode(['Template', 'Business', 'AI']),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Products seeded successfully!');
    }
}
