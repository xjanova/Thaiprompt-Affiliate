<?php

namespace Database\Seeders;

use App\Models\AiGenProvider;
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
        $this->setConfigIfMissing($togetherAi, 'api_key', '', true);
        $this->setConfigIfMissing($togetherAi, 'api_endpoint', 'https://api.together.xyz/v1', false);

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
        $this->setConfigIfMissing($cloudflareAi, 'api_key', '', true);
        $this->setConfigIfMissing($cloudflareAi, 'account_id', '', false);

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
        $this->setConfigIfMissing($grok, 'api_key', '', true);
        $this->setConfigIfMissing($grok, 'api_endpoint', 'https://api.x.ai/v1', false);

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
        $this->setConfigIfMissing($openai, 'api_key', '', true);
        $this->setConfigIfMissing($openai, 'api_endpoint', 'https://api.openai.com/v1', false);

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
        $this->setConfigIfMissing($stabilityAi, 'api_key', '', true);

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
        $this->setConfigIfMissing($falAi, 'api_key', '', true);

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
        $this->setConfigIfMissing($bfl, 'api_key', '', true);

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
        $this->setConfigIfMissing($replicate, 'api_key', '', true);

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
        $this->setConfigIfMissing($ideogram, 'api_key', '', true);

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
        $this->setConfigIfMissing($leonardoAi, 'api_key', '', true);

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
        $this->setConfigIfMissing($freepik, 'api_key', '', true);
        $this->setConfigIfMissing($freepik, 'api_endpoint', 'https://api.freepik.com/v1', false);

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
        $this->setConfigIfMissing($klingAi, 'api_key', '', true);

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
        $this->setConfigIfMissing($runwayMl, 'api_key', '', true);

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
        $this->setConfigIfMissing($lumaAi, 'api_key', '', true);

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
        $this->setConfigIfMissing($minimax, 'api_key', '', true);

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

        // 18. Pollinations.ai (ฟรีสมัคร API key ได้ที่ enter.pollinations.ai) ⭐ แนะนำ
        AiGenProvider::updateOrCreate(
            ['slug' => 'pollinations'],
            [
                'name' => 'Pollinations.ai (FLUX)',
                'type' => 'image',
                'description' => 'เจนภาพด้วยโมเดล FLUX - สมัคร API key ฟรีที่ enter.pollinations.ai',
                'api_docs_url' => 'https://enter.pollinations.ai',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 0,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 0,
            ]
        );

        // 19. Hugging Face (ฟรี สมัคร token ได้ที่ huggingface.co) ⭐ แนะนำ - รองรับภาษาไทย
        AiGenProvider::updateOrCreate(
            ['slug' => 'huggingface'],
            [
                'name' => 'Hugging Face (FLUX)',
                'type' => 'image',
                'description' => 'เจนภาพด้วย FLUX.1-schnell ฟรี! รองรับ prompt ภาษาไทย (แปลอัตโนมัติ) - สมัคร token ฟรีที่ huggingface.co',
                'api_docs_url' => 'https://huggingface.co/settings/tokens',
                'supported_features' => ['text-to-image'],
                'is_active' => true,
                'priority' => 0,
                'wallet_cost_per_image' => 0,
                'wallet_cost_per_video' => 0,
            ]
        );

        $this->command->info('✅ Seed AI Providers ทั้งหมด 19 ตัว สำเร็จ!');
    }

    /**
     * ตั้งค่า provider config เฉพาะเมื่อยังไม่มีค่า — กัน admin token ถูก wipe ทุกครั้งที่ deploy รัน seeder
     *
     * @param  \App\Models\AiGenProvider  $provider
     * @param  string  $key
     * @param  string  $defaultValue  ค่าเริ่มต้น (มักเป็น '' ให้ admin กรอกเอง)
     * @param  bool  $isEncrypted  เข้ารหัสก่อนเก็บไหม (สำหรับ api_key)
     */
    protected function setConfigIfMissing($provider, string $key, string $defaultValue, bool $isEncrypted = false): void
    {
        if (! $provider) {
            return;
        }

        $existing = $provider->configs()->where('config_key', $key)->first();

        // ถ้ามี config + มีค่าจริง (ไม่ใช่ empty) → preserve ทิ้งไว้ ไม่ overwrite
        if ($existing && ! empty(trim((string) $existing->config_value))) {
            return;
        }

        // ไม่มี หรือว่างเปล่า → set default value (เฉพาะกรณี first install)
        $provider->setConfig($key, $defaultValue, $isEncrypted);
    }
}
