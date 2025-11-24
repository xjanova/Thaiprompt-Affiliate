<?php

namespace Database\Seeders;

use App\Models\AiRentalCloudProvider;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับ Cloud GPU Providers
 *
 * เพิ่มข้อมูล Cloud providers ที่แนะนำสำหรับ AI rental
 * รวมทั้ง free tier และ paid providers
 */
class AiRentalCloudProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล Cloud GPU Providers...');

        $providers = [
            // ===== FREE TIER PROVIDERS =====
            [
                'name' => 'Google Colab',
                'slug' => 'google-colab',
                'description' => 'Google Colab ให้บริการ GPU ฟรี (Tesla T4, P100) พร้อม Jupyter notebook รองรับ Hugging Face และ Python libraries ยอดนิยม',
                'website_url' => 'https://colab.research.google.com',
                'tier' => 'both',
                'has_free_tier' => true,
                'min_price_per_hour' => 0,
                'max_price_per_hour' => 9.99,
                'gpu_types' => ['Tesla T4', 'Tesla P100', 'Tesla V100'],
                'free_gpu_hours' => 12,
                'free_storage_gb' => 15,
                'supports_huggingface' => true,
                'supports_docker' => false,
                'supports_jupyter' => true,
                'supports_api' => false,
                'supports_ssh' => false,
                'popularity_score' => 95,
                'user_rating' => 4.7,
                'total_reviews' => 15000,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 1,
            ],

            [
                'name' => 'Kaggle Notebooks',
                'slug' => 'kaggle-notebooks',
                'description' => 'Kaggle ให้ GPU ฟรี 30 ชั่วโมง/สัปดาห์ (Tesla P100) เหมาะสำหรับ training models และ data science',
                'website_url' => 'https://www.kaggle.com/code',
                'tier' => 'free',
                'has_free_tier' => true,
                'gpu_types' => ['Tesla P100'],
                'free_gpu_hours' => 30,
                'free_storage_gb' => 5,
                'supports_huggingface' => true,
                'supports_docker' => false,
                'supports_jupyter' => true,
                'supports_api' => false,
                'supports_ssh' => false,
                'popularity_score' => 85,
                'user_rating' => 4.5,
                'total_reviews' => 8000,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 2,
            ],

            [
                'name' => 'Lightning AI (Free Tier)',
                'slug' => 'lightning-ai-free',
                'description' => 'Lightning AI ให้ GPU ฟรี 22 GPU hours/เดือน พร้อม collaborative workspace',
                'website_url' => 'https://lightning.ai',
                'tier' => 'both',
                'has_free_tier' => true,
                'min_price_per_hour' => 0,
                'max_price_per_hour' => 2.00,
                'gpu_types' => ['Tesla T4', 'A10G', 'A100'],
                'free_gpu_hours' => 22,
                'free_storage_gb' => 10,
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => true,
                'supports_api' => true,
                'supports_ssh' => true,
                'popularity_score' => 75,
                'user_rating' => 4.6,
                'total_reviews' => 3500,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 3,
            ],

            // ===== LOW-COST PAID PROVIDERS =====
            [
                'name' => 'RunPod',
                'slug' => 'runpod',
                'description' => 'RunPod เสนอราคา GPU ที่ถูกที่สุด เริ่มต้น $0.20/ชม. รองรับ Hugging Face และ custom containers',
                'website_url' => 'https://runpod.io',
                'tier' => 'paid',
                'has_free_tier' => false,
                'min_price_per_hour' => 0.20,
                'max_price_per_hour' => 1.89,
                'gpu_types' => ['RTX 3090', 'RTX 4090', 'A100', 'H100'],
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => true,
                'supports_api' => true,
                'supports_ssh' => true,
                'popularity_score' => 90,
                'user_rating' => 4.8,
                'total_reviews' => 5200,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 4,
            ],

            [
                'name' => 'Vast.ai',
                'slug' => 'vast-ai',
                'description' => 'Vast.ai เช่า GPU จากผู้ให้บริการรายย่อย ราคาเริ่มต้น $0.10/ชม. มีตัวเลือก GPU เยอะมาก',
                'website_url' => 'https://vast.ai',
                'tier' => 'paid',
                'has_free_tier' => false,
                'min_price_per_hour' => 0.10,
                'max_price_per_hour' => 3.00,
                'gpu_types' => ['RTX 3060', 'RTX 3090', 'RTX 4090', 'A100'],
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => true,
                'supports_api' => false,
                'supports_ssh' => true,
                'popularity_score' => 82,
                'user_rating' => 4.3,
                'total_reviews' => 4100,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 5,
            ],

            [
                'name' => 'Lambda Labs',
                'slug' => 'lambda-labs',
                'description' => 'Lambda Labs เน้น AI/ML workloads ราคาแข่งขันได้ เริ่ม $0.50/ชม. สำหรับ RTX A6000',
                'website_url' => 'https://lambdalabs.com',
                'tier' => 'paid',
                'has_free_tier' => false,
                'min_price_per_hour' => 0.50,
                'max_price_per_hour' => 1.50,
                'gpu_types' => ['RTX A6000', 'A100'],
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => true,
                'supports_api' => true,
                'supports_ssh' => true,
                'popularity_score' => 88,
                'user_rating' => 4.7,
                'total_reviews' => 3800,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 6,
            ],

            [
                'name' => 'Paperspace Gradient',
                'slug' => 'paperspace-gradient',
                'description' => 'Paperspace ให้บริการ GPU cloud พร้อม free tier จำกัด และ paid plans ราคาสมเหตุสมผล',
                'website_url' => 'https://www.paperspace.com',
                'tier' => 'both',
                'has_free_tier' => true,
                'min_price_per_hour' => 0,
                'max_price_per_hour' => 3.09,
                'gpu_types' => ['RTX 4000', 'RTX 5000', 'A100'],
                'free_gpu_hours' => 6,
                'free_storage_gb' => 5,
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => true,
                'supports_api' => true,
                'supports_ssh' => true,
                'popularity_score' => 80,
                'user_rating' => 4.4,
                'total_reviews' => 2900,
                'is_active' => true,
                'is_recommended' => true,
                'display_order' => 7,
            ],

            [
                'name' => 'Banana.dev',
                'slug' => 'banana-dev',
                'description' => 'Banana.dev เน้น inference APIs สำหรับ production ราคาต่อ request แทนต่อชั่วโมง',
                'website_url' => 'https://banana.dev',
                'tier' => 'paid',
                'has_free_tier' => false,
                'min_price_per_hour' => 0.30,
                'max_price_per_hour' => 2.00,
                'gpu_types' => ['T4', 'A100'],
                'supports_huggingface' => true,
                'supports_docker' => true,
                'supports_jupyter' => false,
                'supports_api' => true,
                'supports_ssh' => false,
                'popularity_score' => 70,
                'user_rating' => 4.5,
                'total_reviews' => 1500,
                'is_active' => true,
                'is_recommended' => false,
                'display_order' => 8,
            ],
        ];

        foreach ($providers as $providerData) {
            // เช็คว่ามีอยู่แล้วหรือไม่
            $existing = AiRentalCloudProvider::where('slug', $providerData['slug'])->first();

            if ($existing) {
                $this->command->info("   ⏭️  {$providerData['name']} มีอยู่แล้ว ข้าม...");
                continue;
            }

            AiRentalCloudProvider::create($providerData);
            $this->command->info("   ✅ เพิ่ม {$providerData['name']}");
        }

        $this->command->info('✅ Seed ข้อมูล Cloud GPU Providers สำเร็จ!');
    }
}
