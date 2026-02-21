<?php

namespace Database\Seeders;

use App\Models\AiGenPackage;
use App\Models\AiGenPromotion;
use App\Models\AiGenProvider;
use App\Models\AiGenQuota;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class AiGenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Freepik Provider (เดิม)
        $freepik = AiGenProvider::updateOrCreate(
            ['slug' => 'freepik'],
            [
                'name' => 'Freepik',
                'slug' => 'freepik',
                'type' => 'both',
                'description' => 'เจนภาพและวิดีโอคุณภาพสูงด้วย Freepik AI',
                'logo_url' => null,
                'supported_features' => ['text-to-image', 'text-to-video', 'image-editing'],
                'is_active' => true,
                'priority' => 5,
            ]
        );

        // Add Freepik configs (empty by default - admin needs to configure)
        $freepik->setConfig('api_key', '', true);
        $freepik->setConfig('api_endpoint', 'https://api.freepik.com/v1', false);

        // Together AI Provider (FLUX.1 schnell - ฟรีไม่อั้น)
        $togetherAi = AiGenProvider::updateOrCreate(
            ['slug' => 'together-ai'],
            [
                'name' => 'Together AI (FLUX)',
                'slug' => 'together-ai',
                'type' => 'image',
                'description' => 'เจนภาพฟรีไม่อั้นด้วย FLUX.1 schnell - คุณภาพดี ความเร็วสูง',
                'logo_url' => null,
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 1,
            ]
        );

        // ตั้งค่า Together AI (admin ต้องใส่ API key ในหลังบ้าน)
        $togetherAi->setConfig('api_key', '', true);
        $togetherAi->setConfig('api_endpoint', 'https://api.together.xyz/v1', false);

        // Grok Provider (xAI Aurora - คุณภาพสูง $0.07/ภาพ)
        $grok = AiGenProvider::updateOrCreate(
            ['slug' => 'grok'],
            [
                'name' => 'Grok (xAI Aurora)',
                'slug' => 'grok',
                'type' => 'image',
                'description' => 'เจนภาพคุณภาพสูงด้วย Grok Aurora จาก xAI ($0.07/ภาพ)',
                'logo_url' => null,
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 3,
            ]
        );

        // ตั้งค่า Grok (admin ต้องใส่ API key ในหลังบ้าน)
        $grok->setConfig('api_key', '', true);
        $grok->setConfig('api_endpoint', 'https://api.x.ai/v1', false);

        // Cloudflare Workers AI Provider (ฟรี ~40 ภาพ/วัน แล้ว ~$0.003/ภาพ)
        $cloudflareAi = AiGenProvider::updateOrCreate(
            ['slug' => 'cloudflare-ai'],
            [
                'name' => 'Cloudflare AI (FLUX)',
                'slug' => 'cloudflare-ai',
                'type' => 'image',
                'description' => 'เจนภาพราคาถูกด้วย Cloudflare Workers AI - ฟรี ~40 ภาพ/วัน',
                'logo_url' => null,
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 2,
            ]
        );

        // ตั้งค่า Cloudflare AI (admin ต้องใส่ API token และ Account ID ในหลังบ้าน)
        $cloudflareAi->setConfig('api_key', '', true);
        $cloudflareAi->setConfig('account_id', '', false);

        // Placeholder providers สำหรับอนาคต
        AiGenProvider::updateOrCreate(
            ['slug' => 'vidu'],
            [
                'name' => 'Vidu',
                'slug' => 'vidu',
                'type' => 'video',
                'description' => 'AI-powered video generation platform',
                'logo_url' => null,
                'supported_features' => ['text-to-video', 'video-editing'],
                'is_active' => false,
                'priority' => 10,
            ]
        );

        AiGenProvider::updateOrCreate(
            ['slug' => 'pixverse'],
            [
                'name' => 'Pixverse',
                'slug' => 'pixverse',
                'type' => 'video',
                'description' => 'Create stunning videos with AI',
                'logo_url' => null,
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 11,
            ]
        );

        // Create Packages
        AiGenPackage::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for trying out AI generation',
                'price' => 299.00,
                'currency' => 'THB',
                'image_credits' => 50,
                'video_credits' => 10,
                'duration_days' => 30,
                'is_recurring' => false,
                'recurring_period' => null,
                'features' => ['50 Image Credits', '10 Video Credits', '30 Days Access', 'Standard Quality'],
                'provider_access' => null, // null = all providers
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ]
        );

        AiGenPackage::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For content creators and professionals',
                'price' => 799.00,
                'currency' => 'THB',
                'image_credits' => 200,
                'video_credits' => 50,
                'duration_days' => 30,
                'is_recurring' => true,
                'recurring_period' => 'monthly',
                'features' => ['200 Image Credits', '50 Video Credits', 'Monthly Recurring', 'High Quality', 'Priority Support'],
                'provider_access' => null,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ]
        );

        AiGenPackage::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited power for teams and businesses',
                'price' => 1999.00,
                'currency' => 'THB',
                'image_credits' => 1000,
                'video_credits' => 200,
                'duration_days' => 30,
                'is_recurring' => true,
                'recurring_period' => 'monthly',
                'features' => ['1000 Image Credits', '200 Video Credits', 'Monthly Recurring', 'Ultra Quality', '24/7 Priority Support', 'API Access'],
                'provider_access' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ]
        );

        // Create Quotas
        AiGenQuota::updateOrCreate(
            ['name' => 'Default Free Quota'],
            [
                'name' => 'Default Free Quota',
                'description' => 'Free quota for all registered users',
                'free_image_daily' => 3,
                'free_image_monthly' => 20,
                'free_video_daily' => 1,
                'free_video_monthly' => 5,
                'role' => null, // applies to all
                'is_active' => true,
                'is_default' => true,
            ]
        );

        AiGenQuota::updateOrCreate(
            ['name' => 'Admin Unlimited'],
            [
                'name' => 'Admin Unlimited',
                'description' => 'Unlimited access for administrators',
                'free_image_daily' => 999999,
                'free_image_monthly' => 999999,
                'free_video_daily' => 999999,
                'free_video_monthly' => 999999,
                'role' => 'admin',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // ===== ตั้งค่า Wallet & System =====
        $this->command->info('🔧 กำลังตั้งค่า AI Gen settings...');

        // ตั้งค่า wallet (ปิดเป็นค่าเริ่มต้น - admin เปิดเองได้)
        Setting::set('ai_gen_wallet_enabled', false, 'boolean', 'ai_gen');
        Setting::set('ai_gen_wallet_cost_image', 5, 'float', 'ai_gen');
        Setting::set('ai_gen_wallet_cost_video', 20, 'float', 'ai_gen');
        Setting::set('ai_gen_system_enabled', true, 'boolean', 'ai_gen');
        Setting::set('ai_gen_max_daily_generations', 100, 'integer', 'ai_gen');
        Setting::set('ai_gen_max_prompt_length', 1000, 'integer', 'ai_gen');
        Setting::set('ai_gen_allow_nsfw', false, 'boolean', 'ai_gen');
        Setting::set('ai_gen_default_provider', '', 'string', 'ai_gen');

        // ===== โปรโมชั่นตัวอย่าง =====
        AiGenPromotion::updateOrCreate(
            ['code' => 'WELCOME50'],
            [
                'name' => 'ยินดีต้อนรับ - ส่วนลด 50%',
                'description' => 'ส่วนลด 50% สำหรับสมาชิกใหม่',
                'type' => 'discount_percent',
                'value' => 50,
                'code' => 'WELCOME50',
                'applies_to' => 'all',
                'max_uses_per_user' => 3,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ AI Gen system seeded successfully!');
    }
}
