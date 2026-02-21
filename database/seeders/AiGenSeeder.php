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
     *
     * สร้าง AI Providers ทุกตัวในตลาดพร้อมลิ้งค์ API docs
     * admin แค่ใส่ API key ก็พร้อมใช้งานได้ทันที
     */
    public function run(): void
    {
        $this->command->info('🤖 กำลัง seed AI Gen Providers ทั้งหมด...');

        // =====================================================================
        // 🖼️ IMAGE GENERATION PROVIDERS
        // =====================================================================

        // 1. Together AI (FLUX.1 schnell - ฟรีไม่อั้น) ⭐ แนะนำ
        $togetherAi = AiGenProvider::updateOrCreate(
            ['slug' => 'together-ai'],
            [
                'name' => 'Together AI (FLUX)',
                'type' => 'image',
                'description' => 'เจนภาพฟรีไม่อั้นด้วย FLUX.1 schnell - คุณภาพดี ความเร็วสูง',
                'api_docs_url' => 'https://api.together.ai/settings/api-keys',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 1,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 0,
            ]
        );
        $togetherAi->setConfig('api_key', '', true);
        $togetherAi->setConfig('api_endpoint', 'https://api.together.xyz/v1', false);

        // 2. Cloudflare Workers AI (FLUX - ฟรี ~40 ภาพ/วัน)
        $cloudflareAi = AiGenProvider::updateOrCreate(
            ['slug' => 'cloudflare-ai'],
            [
                'name' => 'Cloudflare AI (FLUX)',
                'type' => 'image',
                'description' => 'เจนภาพราคาถูกด้วย Cloudflare Workers AI - ฟรี ~40 ภาพ/วัน จากนั้น $0.003/ภาพ',
                'api_docs_url' => 'https://dash.cloudflare.com/profile/api-tokens',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 2,
                'wallet_cost_per_image' => 1,
                'wallet_cost_per_video' => 0,
            ]
        );
        $cloudflareAi->setConfig('api_key', '', true);
        $cloudflareAi->setConfig('account_id', '', false);

        // 3. Grok / xAI Aurora (คุณภาพสูง)
        $grok = AiGenProvider::updateOrCreate(
            ['slug' => 'grok'],
            [
                'name' => 'Grok (xAI Aurora)',
                'type' => 'image',
                'description' => 'เจนภาพคุณภาพสูงด้วย Grok Aurora จาก xAI ($0.07/ภาพ)',
                'api_docs_url' => 'https://console.x.ai/',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 3,
                'wallet_cost_per_image' => 3,
                'wallet_cost_per_video' => 0,
            ]
        );
        $grok->setConfig('api_key', '', true);
        $grok->setConfig('api_endpoint', 'https://api.x.ai/v1', false);

        // 4. OpenAI (DALL-E 3 / gpt-image-1) ⭐ ยอดนิยม
        $openai = AiGenProvider::updateOrCreate(
            ['slug' => 'openai'],
            [
                'name' => 'OpenAI (DALL-E 3)',
                'type' => 'image',
                'description' => 'เจนภาพด้วย DALL-E 3 และ gpt-image-1 จาก OpenAI - คุณภาพสูง เข้าใจ prompt ได้ดี',
                'api_docs_url' => 'https://platform.openai.com/api-keys',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 4,
                'wallet_cost_per_image' => 5,
                'wallet_cost_per_video' => 0,
            ]
        );
        $openai->setConfig('api_key', '', true);
        $openai->setConfig('api_endpoint', 'https://api.openai.com/v1', false);

        // 5. Stability AI (Stable Diffusion 3.5)
        $stabilityAi = AiGenProvider::updateOrCreate(
            ['slug' => 'stability-ai'],
            [
                'name' => 'Stability AI (SD 3.5)',
                'type' => 'image',
                'description' => 'เจนภาพด้วย Stable Diffusion 3.5 - โมเดล open-source ระดับโลก รองรับ negative prompt',
                'api_docs_url' => 'https://platform.stability.ai/account/keys',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 5,
                'wallet_cost_per_image' => 3,
                'wallet_cost_per_video' => 0,
            ]
        );
        $stabilityAi->setConfig('api_key', '', true);

        // 6. FAL AI (FLUX Pro - เร็วมาก)
        $falAi = AiGenProvider::updateOrCreate(
            ['slug' => 'fal-ai'],
            [
                'name' => 'FAL AI (FLUX Pro)',
                'type' => 'image',
                'description' => 'เจนภาพเร็วมาก (~2 วินาที) ด้วย FLUX Pro, FLUX Dev, FLUX Realism',
                'api_docs_url' => 'https://fal.ai/dashboard/keys',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 6,
                'wallet_cost_per_image' => 2,
                'wallet_cost_per_video' => 0,
            ]
        );
        $falAi->setConfig('api_key', '', true);

        // 7. Black Forest Labs (FLUX Pro - คุณภาพสูงสุด)
        $bfl = AiGenProvider::updateOrCreate(
            ['slug' => 'bfl'],
            [
                'name' => 'BFL (FLUX Pro 1.1)',
                'type' => 'image',
                'description' => 'เจนภาพคุณภาพสูงสุดจากผู้สร้าง FLUX โดยตรง - FLUX Pro 1.1',
                'api_docs_url' => 'https://api.bfl.ml/',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 7,
                'wallet_cost_per_image' => 4,
                'wallet_cost_per_video' => 0,
            ]
        );
        $bfl->setConfig('api_key', '', true);

        // 8. Replicate (หลายโมเดล)
        $replicate = AiGenProvider::updateOrCreate(
            ['slug' => 'replicate'],
            [
                'name' => 'Replicate',
                'type' => 'image',
                'description' => 'เข้าถึง AI model หลายร้อยตัว - FLUX, SDXL, Kandinsky และอื่นๆ',
                'api_docs_url' => 'https://replicate.com/account/api-tokens',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 8,
                'wallet_cost_per_image' => 2,
                'wallet_cost_per_video' => 0,
            ]
        );
        $replicate->setConfig('api_key', '', true);

        // 9. Ideogram (เน้นข้อความในภาพ)
        $ideogram = AiGenProvider::updateOrCreate(
            ['slug' => 'ideogram'],
            [
                'name' => 'Ideogram',
                'type' => 'image',
                'description' => 'เจนภาพที่มีข้อความถูกต้อง 100% - เหมาะสำหรับโปสเตอร์ โลโก้ โฆษณา',
                'api_docs_url' => 'https://ideogram.ai/manage-api',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 9,
                'wallet_cost_per_image' => 3,
                'wallet_cost_per_video' => 0,
            ]
        );
        $ideogram->setConfig('api_key', '', true);

        // 10. Leonardo AI (game art, concept art)
        $leonardoAi = AiGenProvider::updateOrCreate(
            ['slug' => 'leonardo-ai'],
            [
                'name' => 'Leonardo AI',
                'type' => 'image',
                'description' => 'เจนภาพเน้น game art, character design, concept art - มีโมเดลเฉพาะทาง',
                'api_docs_url' => 'https://app.leonardo.ai/api-access',
                'supported_features' => ['text-to-image'],
                'is_active' => false,
                'priority' => 10,
                'wallet_cost_per_image' => 3,
                'wallet_cost_per_video' => 0,
            ]
        );
        $leonardoAi->setConfig('api_key', '', true);

        // 11. Freepik (ภาพ + วิดีโอ)
        $freepik = AiGenProvider::updateOrCreate(
            ['slug' => 'freepik'],
            [
                'name' => 'Freepik AI',
                'type' => 'both',
                'description' => 'เจนภาพและวิดีโอคุณภาพสูงด้วย Freepik AI - เหมาะสำหรับงาน commercial',
                'api_docs_url' => 'https://www.freepik.com/api',
                'supported_features' => ['text-to-image', 'text-to-video', 'image-editing'],
                'is_active' => false,
                'priority' => 11,
                'wallet_cost_per_image' => 2,
                'wallet_cost_per_video' => 10,
            ]
        );
        $freepik->setConfig('api_key', '', true);
        $freepik->setConfig('api_endpoint', 'https://api.freepik.com/v1', false);

        // =====================================================================
        // 🎬 VIDEO GENERATION PROVIDERS
        // =====================================================================

        // 12. Kling AI (ภาพ + วิดีโอ คุณภาพสูง) ⭐
        $klingAi = AiGenProvider::updateOrCreate(
            ['slug' => 'kling-ai'],
            [
                'name' => 'Kling AI',
                'type' => 'both',
                'description' => 'สร้างภาพและวิดีโอ AI คุณภาพสูง - text-to-video, image-to-video ราคาเหมาะสม',
                'api_docs_url' => 'https://platform.klingai.com/',
                'supported_features' => ['text-to-image', 'text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 12,
                'wallet_cost_per_image' => 2,
                'wallet_cost_per_video' => 15,
            ]
        );
        $klingAi->setConfig('api_key', '', true);

        // 13. Runway ML (Gen-3 Alpha - top tier video)
        $runwayMl = AiGenProvider::updateOrCreate(
            ['slug' => 'runway-ml'],
            [
                'name' => 'Runway ML (Gen-3)',
                'type' => 'video',
                'description' => 'สร้างวิดีโอคุณภาพสูงสุดด้วย Gen-3 Alpha Turbo - ผู้นำตลาด AI video',
                'api_docs_url' => 'https://dev.runwayml.com/',
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 13,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 25,
            ]
        );
        $runwayMl->setConfig('api_key', '', true);

        // 14. Luma AI (Dream Machine)
        $lumaAi = AiGenProvider::updateOrCreate(
            ['slug' => 'luma-ai'],
            [
                'name' => 'Luma AI (Dream Machine)',
                'type' => 'video',
                'description' => 'สร้างวิดีโอ cinematic ด้วย Dream Machine - คุณภาพระดับ Hollywood',
                'api_docs_url' => 'https://lumalabs.ai/dream-machine/api',
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 14,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 20,
            ]
        );
        $lumaAi->setConfig('api_key', '', true);

        // 15. Minimax / Hailuo (วิดีโอ realistic)
        $minimax = AiGenProvider::updateOrCreate(
            ['slug' => 'minimax'],
            [
                'name' => 'Minimax (Hailuo)',
                'type' => 'video',
                'description' => 'สร้างวิดีโอ realistic ด้วย Hailuo AI - คุณภาพสูง ราคาย่อมเยา',
                'api_docs_url' => 'https://platform.minimaxi.com/',
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 15,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 15,
            ]
        );
        $minimax->setConfig('api_key', '', true);

        // 16. Vidu (placeholder)
        AiGenProvider::updateOrCreate(
            ['slug' => 'vidu'],
            [
                'name' => 'Vidu',
                'type' => 'video',
                'description' => 'AI-powered video generation platform - รอ API เปิดให้บริการ',
                'api_docs_url' => 'https://www.vidu.com/',
                'supported_features' => ['text-to-video', 'video-editing'],
                'is_active' => false,
                'priority' => 16,
            ]
        );

        // 17. Pixverse (placeholder)
        AiGenProvider::updateOrCreate(
            ['slug' => 'pixverse'],
            [
                'name' => 'Pixverse',
                'type' => 'video',
                'description' => 'สร้างวิดีโอ AI สไตล์สวยงาม - รอ API เปิดให้บริการ',
                'api_docs_url' => 'https://pixverse.ai/',
                'supported_features' => ['text-to-video', 'image-to-video'],
                'is_active' => false,
                'priority' => 17,
            ]
        );

        // 18. Pollinations.ai (ฟรี 100% ไม่ต้อง API key) ⭐ แนะนำ
        AiGenProvider::updateOrCreate(
            ['slug' => 'pollinations'],
            [
                'name' => 'Pollinations.ai (FLUX)',
                'type' => 'image',
                'description' => 'เจนภาพฟรี 100% ไม่ต้อง API key ไม่ต้องสมัคร - ใช้โมเดล FLUX, FLUX Realism, FLUX Anime',
                'api_docs_url' => 'https://pollinations.ai',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 0,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 0,
            ]
        );

        $this->command->info('✅ Seed AI Providers ทั้งหมด 18 ตัว สำเร็จ!');

        // =====================================================================
        // 📦 PACKAGES
        // =====================================================================

        AiGenPackage::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Perfect for trying out AI generation',
                'price' => 299.00,
                'currency' => 'THB',
                'image_credits' => 50,
                'video_credits' => 10,
                'duration_days' => 30,
                'is_recurring' => false,
                'recurring_period' => null,
                'features' => ['50 Image Credits', '10 Video Credits', '30 Days Access', 'Standard Quality'],
                'provider_access' => null,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ]
        );

        AiGenPackage::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
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

        // =====================================================================
        // 🎯 QUOTAS
        // =====================================================================

        AiGenQuota::updateOrCreate(
            ['name' => 'Default Free Quota'],
            [
                'description' => 'Free quota for all registered users',
                'free_image_daily' => 3,
                'free_image_monthly' => 20,
                'free_video_daily' => 1,
                'free_video_monthly' => 5,
                'role' => null,
                'is_active' => true,
                'is_default' => true,
            ]
        );

        AiGenQuota::updateOrCreate(
            ['name' => 'Admin Unlimited'],
            [
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

        // =====================================================================
        // ⚙️ SETTINGS
        // =====================================================================

        $this->command->info('🔧 กำลังตั้งค่า AI Gen settings...');

        Setting::set('ai_gen_wallet_enabled', false, 'boolean', 'ai_gen');
        Setting::set('ai_gen_wallet_cost_image', 5, 'float', 'ai_gen');
        Setting::set('ai_gen_wallet_cost_video', 20, 'float', 'ai_gen');
        Setting::set('ai_gen_system_enabled', true, 'boolean', 'ai_gen');
        Setting::set('ai_gen_max_daily_generations', 100, 'integer', 'ai_gen');
        Setting::set('ai_gen_max_prompt_length', 1000, 'integer', 'ai_gen');
        Setting::set('ai_gen_allow_nsfw', false, 'boolean', 'ai_gen');
        Setting::set('ai_gen_default_provider', '', 'string', 'ai_gen');
        Setting::set('ai_gen_max_storage_mb', 500, 'integer', 'ai_gen');
        Setting::set('ai_gen_auto_cleanup_days', 30, 'integer', 'ai_gen');

        // =====================================================================
        // 🎉 PROMOTIONS
        // =====================================================================

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
