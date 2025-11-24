<?php

namespace Database\Seeders;

use App\Models\AiRentalModel;
use Illuminate\Database\Seeder;

/**
 * AI Rental Model Seeder
 *
 * Seeder สำหรับสร้างข้อมูล AI Models ตัวอย่าง
 */
class AiRentalModelSeeder extends Seeder
{
    /**
     * สร้างข้อมูล AI Models
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล AI Rental Models...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (AiRentalModel::count() > 0) {
            $this->command->info('ข้อมูล AI Models มีอยู่แล้ว ข้าม...');
            return;
        }

        $models = $this->getModelsData();

        foreach ($models as $modelData) {
            AiRentalModel::create($modelData);
        }

        $this->command->info('✅ Seed ข้อมูล AI Models สำเร็จ! (' . count($models) . ' models)');
    }

    /**
     * ดึงข้อมูล AI Models ทั้งหมด
     *
     * @return array
     */
    protected function getModelsData(): array
    {
        return [
            // ========================================
            // Image Generation Models
            // ========================================
            [
                'name' => 'Stable Diffusion XL',
                'slug' => 'stable-diffusion-xl',
                'description' => 'AI สร้างภาพคุณภาพสูงจาก text prompts รองรับภาพขนาดใหญ่ถึง 1024x1024 pixels',
                'version' => '1.0',
                'category' => 'image_generation',
                'framework' => 'PyTorch',
                'license' => 'CreativeML Open RAIL-M',
                'docker_image' => 'stabilityai/sdxl:latest',
                'docker_env_vars' => [
                    'MODEL_NAME' => 'stabilityai/stable-diffusion-xl-base-1.0',
                ],
                'docker_ports' => ['7860:7860'],
                'docker_command' => 'python webui.py --listen --port 7860',
                'min_gpu_type' => 'RTX 3060',
                'min_vram_gb' => 12,
                'recommended_vram_gb' => 16,
                'supported_gpu_types' => ['RTX 3060', 'RTX 3080', 'RTX 3090', 'RTX 4080', 'RTX 4090', 'A100'],
                'min_cpu_cores' => 4,
                'min_ram_gb' => 16,
                'min_storage_gb' => 30,
                'avg_inference_time_ms' => 3500,
                'max_batch_size' => 4,
                'base_cost_per_hour' => 0.50,
                'official_url' => 'https://stability.ai/',
                'documentation_url' => 'https://github.com/Stability-AI/generative-models',
                'github_url' => 'https://github.com/Stability-AI/generative-models',
                'huggingface_url' => 'https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0',
                'tags' => ['image', 'art', 'stable-diffusion', 'text-to-image'],
                'use_cases' => ['Art Generation', 'Product Design', 'Marketing Materials'],
                'is_active' => true,
                'is_featured' => true,
                'is_new' => false,
                'availability' => 'public',
                'display_order' => 1,
                'badge_text' => 'POPULAR',
                'badge_color' => '#3B82F6',
            ],

            [
                'name' => 'Midjourney Alternative',
                'slug' => 'midjourney-alternative',
                'description' => 'AI สร้างภาพคุณภาพสูงแบบ Midjourney โดยใช้ Stable Diffusion เป็นฐาน',
                'version' => '6.0',
                'category' => 'image_generation',
                'framework' => 'PyTorch',
                'docker_image' => 'invokeai/invokeai:latest',
                'min_gpu_type' => 'RTX 3060',
                'min_vram_gb' => 10,
                'recommended_vram_gb' => 12,
                'min_cpu_cores' => 4,
                'min_ram_gb' => 12,
                'min_storage_gb' => 25,
                'base_cost_per_hour' => 0.45,
                'tags' => ['image', 'art', 'photorealistic'],
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 2,
            ],

            // ========================================
            // Text Generation Models
            // ========================================
            [
                'name' => 'LLaMA 3.1 70B',
                'slug' => 'llama-3-1-70b',
                'description' => 'Large Language Model จาก Meta ขนาด 70 billion parameters รองรับ Thai/English',
                'version' => '3.1',
                'category' => 'text_generation',
                'framework' => 'PyTorch',
                'license' => 'LLaMA 3 Community License',
                'docker_image' => 'meta/llama3.1:70b',
                'docker_ports' => ['8080:8080'],
                'docker_command' => 'python -m vllm.entrypoints.api_server --model meta-llama/Meta-Llama-3.1-70B',
                'min_gpu_type' => 'A100',
                'min_vram_gb' => 80,
                'recommended_vram_gb' => 160,
                'supported_gpu_types' => ['A100', 'H100'],
                'min_cpu_cores' => 16,
                'min_ram_gb' => 128,
                'min_storage_gb' => 200,
                'avg_inference_time_ms' => 500,
                'max_batch_size' => 32,
                'base_cost_per_hour' => 2.50,
                'official_url' => 'https://ai.meta.com/llama/',
                'github_url' => 'https://github.com/meta-llama/llama3',
                'huggingface_url' => 'https://huggingface.co/meta-llama/Meta-Llama-3.1-70B',
                'tags' => ['llm', 'text', 'chatbot', 'thai', 'multilingual'],
                'use_cases' => ['Chatbot', 'Content Generation', 'Code Review', 'Translation'],
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 10,
                'badge_text' => 'RECOMMENDED',
                'badge_color' => '#10B981',
            ],

            [
                'name' => 'GPT-NeoX 20B',
                'slug' => 'gpt-neox-20b',
                'description' => 'Open-source LLM ขนาด 20B parameters เทียบเท่า GPT-3',
                'version' => '1.0',
                'category' => 'text_generation',
                'framework' => 'PyTorch',
                'license' => 'Apache 2.0',
                'docker_image' => 'eleutherai/gpt-neox:20b',
                'min_gpu_type' => 'RTX 3090',
                'min_vram_gb' => 40,
                'recommended_vram_gb' => 48,
                'min_cpu_cores' => 8,
                'min_ram_gb' => 64,
                'min_storage_gb' => 80,
                'base_cost_per_hour' => 1.20,
                'tags' => ['llm', 'text', 'open-source'],
                'is_active' => true,
                'display_order' => 11,
            ],

            // ========================================
            // Code Generation Models
            // ========================================
            [
                'name' => 'CodeLlama 34B',
                'slug' => 'codellama-34b',
                'description' => 'AI สำหรับสร้างและอธิบายโค้ด รองรับภาษา Python, JavaScript, TypeScript, C++',
                'version' => '1.0',
                'category' => 'code_generation',
                'framework' => 'PyTorch',
                'license' => 'LLaMA 2 Community License',
                'docker_image' => 'meta/codellama:34b',
                'min_gpu_type' => 'RTX 4090',
                'min_vram_gb' => 48,
                'recommended_vram_gb' => 64,
                'min_cpu_cores' => 12,
                'min_ram_gb' => 64,
                'min_storage_gb' => 100,
                'base_cost_per_hour' => 1.50,
                'official_url' => 'https://ai.meta.com/blog/code-llama-large-language-model-coding/',
                'huggingface_url' => 'https://huggingface.co/codellama/CodeLlama-34b-hf',
                'tags' => ['code', 'programming', 'developer', 'autocomplete'],
                'use_cases' => ['Code Generation', 'Code Review', 'Bug Fixing', 'Documentation'],
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 20,
            ],

            // ========================================
            // Audio/Speech Models
            // ========================================
            [
                'name' => 'Whisper Large V3',
                'slug' => 'whisper-large-v3',
                'description' => 'Speech-to-Text model จาก OpenAI รองรับ 99 ภาษา ความแม่นยำสูง',
                'version' => '3.0',
                'category' => 'audio_generation',
                'framework' => 'PyTorch',
                'license' => 'MIT',
                'docker_image' => 'openai/whisper:large-v3',
                'docker_ports' => ['9000:9000'],
                'min_gpu_type' => 'RTX 3060',
                'min_vram_gb' => 8,
                'recommended_vram_gb' => 12,
                'min_cpu_cores' => 4,
                'min_ram_gb' => 16,
                'min_storage_gb' => 10,
                'avg_inference_time_ms' => 1000,
                'base_cost_per_hour' => 0.30,
                'official_url' => 'https://openai.com/research/whisper',
                'github_url' => 'https://github.com/openai/whisper',
                'huggingface_url' => 'https://huggingface.co/openai/whisper-large-v3',
                'tags' => ['audio', 'speech-to-text', 'transcription', 'multilingual', 'thai'],
                'use_cases' => ['Transcription', 'Subtitles', 'Voice Commands', 'Meeting Notes'],
                'is_active' => true,
                'is_featured' => true,
                'display_order' => 30,
            ],

            [
                'name' => 'Bark TTS',
                'slug' => 'bark-tts',
                'description' => 'Text-to-Speech AI สร้างเสียงพูดที่เสมือนจริง รองรับหลายภาษา',
                'version' => '1.0',
                'category' => 'audio_generation',
                'framework' => 'PyTorch',
                'license' => 'MIT',
                'docker_image' => 'suno/bark:latest',
                'min_gpu_type' => 'RTX 3060',
                'min_vram_gb' => 6,
                'recommended_vram_gb' => 8,
                'min_cpu_cores' => 4,
                'min_ram_gb' => 8,
                'min_storage_gb' => 15,
                'base_cost_per_hour' => 0.25,
                'tags' => ['audio', 'text-to-speech', 'tts', 'voice'],
                'is_active' => true,
                'display_order' => 31,
            ],

            // ========================================
            // Chatbot Models
            // ========================================
            [
                'name' => 'Vicuna 13B',
                'slug' => 'vicuna-13b',
                'description' => 'Open-source chatbot model trained by fine-tuning LLaMA ใกล้เคียง ChatGPT',
                'version' => '1.5',
                'category' => 'chatbot',
                'framework' => 'PyTorch',
                'license' => 'Non-commercial',
                'docker_image' => 'lmsys/vicuna:13b-v1.5',
                'min_gpu_type' => 'RTX 3080',
                'min_vram_gb' => 24,
                'recommended_vram_gb' => 32,
                'min_cpu_cores' => 8,
                'min_ram_gb' => 32,
                'min_storage_gb' => 50,
                'base_cost_per_hour' => 0.80,
                'github_url' => 'https://github.com/lm-sys/FastChat',
                'huggingface_url' => 'https://huggingface.co/lmsys/vicuna-13b-v1.5',
                'tags' => ['chatbot', 'conversation', 'assistant'],
                'is_active' => true,
                'display_order' => 40,
            ],

            // ========================================
            // Translation Models
            // ========================================
            [
                'name' => 'NLLB-200 3.3B',
                'slug' => 'nllb-200-3.3b',
                'description' => 'แปลภาษา 200 ภาษา รวมภาษาไทย โดย Meta AI',
                'version' => '1.0',
                'category' => 'translation',
                'framework' => 'PyTorch',
                'license' => 'CC-BY-NC',
                'docker_image' => 'facebook/nllb:3.3b',
                'min_gpu_type' => 'RTX 3060',
                'min_vram_gb' => 8,
                'recommended_vram_gb' => 12,
                'min_cpu_cores' => 4,
                'min_ram_gb' => 16,
                'min_storage_gb' => 20,
                'base_cost_per_hour' => 0.35,
                'official_url' => 'https://ai.meta.com/research/no-language-left-behind/',
                'huggingface_url' => 'https://huggingface.co/facebook/nllb-200-3.3B',
                'tags' => ['translation', 'multilingual', 'thai', 'nllb'],
                'use_cases' => ['Document Translation', 'Website Localization', 'Subtitle Translation'],
                'is_active' => true,
                'display_order' => 50,
            ],

            // ========================================
            // Video Generation (Beta)
            // ========================================
            [
                'name' => 'AnimateDiff',
                'slug' => 'animate-diff',
                'description' => 'สร้างวิดีโอแอนิเมชันจาก text prompts',
                'version' => '1.0',
                'category' => 'video_generation',
                'framework' => 'PyTorch',
                'docker_image' => 'guoyww/animatediff:latest',
                'min_gpu_type' => 'RTX 4090',
                'min_vram_gb' => 24,
                'recommended_vram_gb' => 48,
                'min_cpu_cores' => 8,
                'min_ram_gb' => 32,
                'min_storage_gb' => 50,
                'base_cost_per_hour' => 1.80,
                'github_url' => 'https://github.com/guoyww/AnimateDiff',
                'tags' => ['video', 'animation', 'motion'],
                'is_active' => true,
                'is_beta' => true,
                'availability' => 'beta',
                'display_order' => 60,
                'badge_text' => 'BETA',
                'badge_color' => '#F59E0B',
            ],
        ];
    }
}
