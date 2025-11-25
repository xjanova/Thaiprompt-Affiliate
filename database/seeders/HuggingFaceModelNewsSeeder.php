<?php

namespace Database\Seeders;

use App\Models\HuggingFaceModelNews;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับข่าวสาร Models จาก Hugging Face
 *
 * สร้างข่าวสารเกี่ยวกับ models ใหม่, updates, trends, และ best practices
 */
class HuggingFaceModelNewsSeeder extends Seeder
{
    /**
     * สร้างข้อมูลข่าวสาร
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข่าวสาร Hugging Face Model News...');

        // ✅ ตรวจสอบก่อนสร้าง (idempotent)
        if (HuggingFaceModelNews::count() > 0) {
            $this->command->info('ข้อมูลข่าวสารมีอยู่แล้ว ข้าม...');
            return;
        }

        $news = [
            // 🔥 Breaking News - FLUX.1
            [
                'title' => 'FLUX.1 กลายเป็น Image Generation Model ยอดนิยม #1 ของ Hugging Face!',
                'slug' => null, // Will be auto-generated
                'summary' => 'FLUX.1 จาก Black Forest Labs ทำลายสถิติด้วย 850K downloads ในเดือนแรก เหนือกว่า Stable Diffusion และ Midjourney ในหลายด้าน',
                'content' => "# FLUX.1 ครองอันดับ 1 Image Generation Model\n\nBlack Forest Labs เปิดตัว **FLUX.1** โมเดลสร้างภาพขนาด 12B parameters ที่ใช้เทคโนโลยี Rectified Flow Transformer ซึ่งให้ผลลัพธ์ที่น่าทึ่ง:\n\n## ไฮไลท์:\n- 🏆 คะแนนคุณภาพสูงกว่า Midjourney v6 และ DALL-E 3\n- 🎨 Prompt adherence ที่แม่นยำสุดๆ (9.8/10)\n- 🚀 850,000 downloads ในเดือนแรก\n- 💾 ขนาด 23.8 GB (FP16)\n\n## Hardware Requirements:\n- **Minimum**: 24 GB VRAM (RTX 4090, A100 40GB)\n- **Recommended**: 40 GB VRAM (A100 80GB)\n- **Cost**: ประมาณ $1.50/hr บน RunPod\n\n## วิธีใช้งาน:\n\n```python\nfrom diffusers import FluxPipeline\nimport torch\n\npipe = FluxPipeline.from_pretrained(\n    \"black-forest-labs/FLUX.1-dev\",\n    torch_dtype=torch.bfloat16\n)\npipe.to(\"cuda\")\n\nimage = pipe(\n    \"A cat holding a sign that says 'FLUX is amazing!'\",\n    guidance_scale=3.5,\n    num_inference_steps=50,\n    height=1024,\n    width=1024,\n).images[0]\n\nimage.save(\"flux_output.png\")\n```\n\n## Best Practices:\n1. ใช้ `bfloat16` สำหรับ memory efficiency\n2. Guidance scale 3-4 ให้ผลลัพธ์ดีที่สุด\n3. 50 steps เพียงพอสำหรับคุณภาพสูง\n\n[ทดลองใช้งานได้ที่ Hugging Face Spaces](https://huggingface.co/spaces/black-forest-labs/FLUX.1-dev)",
                'model_id' => 'black-forest-labs/FLUX.1-dev',
                'model_name' => 'FLUX.1 [dev]',
                'news_type' => 'trending',
                'importance' => 'critical',
                'model_tags' => ['flux', 'image-generation', 'breaking-news', 'trending', 'black-forest-labs'],
                'model_type' => 'Image Generation',
                'is_published' => true,
                'is_featured' => true,
                'is_pinned' => true,
                'image_url' => 'https://huggingface.co/datasets/huggingface/brand-assets/resolve/main/hf-logo.png',
                'author_name' => 'Hugging Face News Team',
                'published_at' => now()->subDays(2),
                'view_count' => 15420,
                'share_count' => 234,
                'metadata' => [
                    'reading_time' => '5 min',
                    'difficulty' => 'intermediate',
                    'related_models' => ['stabilityai/stable-diffusion-3.5-large', 'runwayml/stable-diffusion-v1-5'],
                ],
            ],

            // 🆕 New Model Release - Llama 3.1
            [
                'title' => 'Meta เปิดตัว Llama 3.1 405B - LLM ที่ใหญ่ที่สุดแบบ Open Source!',
                'summary' => 'Llama 3.1 405B คือโมเดลภาษาแบบ open-source ที่ใหญ่ที่สุด รองรับ context length 128K tokens เทียบเท่า GPT-4',
                'content' => "# Llama 3.1 405B: การปฏิวัติ Open Source LLM\n\nMeta AI ปล่อย **Llama 3.1** ครอบครัวใหม่ โดยเฉพาะรุ่น 405B ที่ทรงพลังที่สุด:\n\n## Key Features:\n- 🧠 405 billion parameters\n- 📏 Context length 128K tokens\n- 🌐 Multilingual support (รวมภาษาไทย)\n- 🎯 MMLU Score: 88.6% (ใกล้เคียง GPT-4)\n- 📊 GSM8K: 95.1% (Math reasoning)\n\n## Model Variants:\n- **8B**: สำหรับ edge devices\n- **70B**: Balance ระหว่าง performance และ cost\n- **405B**: State-of-the-art performance\n\n## Use Cases:\n- 💬 Chatbots และ virtual assistants\n- 📝 Content generation ภาษาไทย\n- 🔍 RAG systems\n- 💻 Code generation\n- 📊 Data analysis\n\n## Deployment:\n\n**405B Requirements**:\n- 8x A100 80GB หรือ 4x H100 80GB\n- ~$25/hr บน Lambda Labs\n\n**70B Requirements** (แนะนำสำหรับส่วนใหญ่):\n- 2x A100 40GB\n- ~$3/hr บน RunPod\n\n```python\nfrom transformers import AutoTokenizer, AutoModelForCausalLM\nimport torch\n\nmodel_id = \"meta-llama/Llama-3.1-405B-Instruct\"\n\ntokenizer = AutoTokenizer.from_pretrained(model_id)\nmodel = AutoModelForCausalLM.from_pretrained(\n    model_id,\n    torch_dtype=torch.bfloat16,\n    device_map=\"auto\",\n)\n\nmessages = [\n    {\"role\": \"user\", \"content\": \"แปลประโยคนี้เป็นภาษาอังกฤษ: 'ฉันรักประเทศไทย'\"}\n]\n\ninput_ids = tokenizer.apply_chat_template(\n    messages,\n    add_generation_prompt=True,\n    return_tensors=\"pt\"\n).to(model.device)\n\noutputs = model.generate(\n    input_ids,\n    max_new_tokens=256,\n    do_sample=True,\n    temperature=0.7,\n)\n\nresponse = tokenizer.decode(outputs[0][input_ids.shape[-1]:], skip_special_tokens=True)\nprint(response)\n```\n\n## License:\nLlama 3.1 Community License - **ใช้ในเชิงพาณิชย์ได้!**",
                'model_id' => 'meta-llama/Llama-3.1-405B-Instruct',
                'model_name' => 'Llama 3.1 405B Instruct',
                'news_type' => 'new_model',
                'importance' => 'critical',
                'model_tags' => ['llama', 'meta', 'llm', 'open-source', 'multilingual'],
                'model_type' => 'Language Model',
                'is_published' => true,
                'is_featured' => true,
                'is_pinned' => false,
                'author_name' => 'Meta AI Team',
                'published_at' => now()->subDays(15),
                'view_count' => 28500,
                'share_count' => 680,
                'metadata' => [
                    'reading_time' => '8 min',
                    'difficulty' => 'advanced',
                    'related_models' => ['mistralai/Mixtral-8x7B-Instruct-v0.1', 'HuggingFaceH4/zephyr-7b-beta'],
                ],
            ],

            // 🎯 Best Practices - Whisper
            [
                'title' => 'วิธีใช้ Whisper Large V3 สำหรับ Speech-to-Text ภาษาไทยให้ได้ผลดีที่สุด',
                'summary' => 'เทคนิค และ best practices ในการใช้ Whisper V3 สำหรับ transcribe เสียงภาษาไทย พร้อมตัวอย่างโค้ด',
                'content' => "# Whisper Large V3: Best Practices สำหรับภาษาไทย\n\nWhisper V3 จาก OpenAI เป็นโมเดล Speech-to-Text ที่แม่นยำที่สุดสำหรับภาษาไทย\n\n## Performance:\n- 🎯 WER (Word Error Rate): ~7.8% สำหรับภาษาไทย\n- 🌐 รองรับ 99 ภาษา\n- ⚡ Inference time: ~800ms ต่อนาที (บน A100)\n\n## Hardware Requirements:\n- **Minimum**: 10 GB VRAM (RTX 3090, T4)\n- **Recommended**: 16 GB VRAM (RTX 4090)\n- **Cost**: ~$0.30/hr บน vast.ai\n\n## Installation & Setup:\n\n```bash\npip install -U openai-whisper\n# หรือ\npip install transformers accelerate\n```\n\n## Basic Usage:\n\n```python\nimport whisper\n\n# โหลด model\nmodel = whisper.load_model(\"large-v3\")\n\n# Transcribe ไฟล์เสียง\nresult = model.transcribe(\n    \"thai_audio.mp3\",\n    language=\"th\",  # ระบุภาษาไทยช่วยเพิ่มความแม่นยำ\n    task=\"transcribe\",\n    fp16=True,  # ใช้ FP16 เพื่อประหยัด memory\n)\n\nprint(result[\"text\"])\n```\n\n## Advanced Tips:\n\n### 1. ใช้ VAD (Voice Activity Detection):\n```python\nresult = model.transcribe(\n    \"audio.mp3\",\n    language=\"th\",\n    vad_filter=True,  # กรองเสียงรบกวน\n    vad_parameters={\n        \"threshold\": 0.5,\n        \"min_speech_duration_ms\": 250,\n    }\n)\n```\n\n### 2. Batch Processing:\n```python\nimport torch\nfrom transformers import AutoModelForSpeechSeq2Seq, AutoProcessor\n\ndevice = \"cuda:0\" if torch.cuda.is_available() else \"cpu\"\ntorch_dtype = torch.float16 if torch.cuda.is_available() else torch.float32\n\nmodel_id = \"openai/whisper-large-v3\"\n\nmodel = AutoModelForSpeechSeq2Seq.from_pretrained(\n    model_id,\n    torch_dtype=torch_dtype,\n    low_cpu_mem_usage=True,\n)\nmodel.to(device)\n\nprocessor = AutoProcessor.from_pretrained(model_id)\n\n# Process multiple files\nfor audio_file in audio_files:\n    inputs = processor(audio, sampling_rate=16000, return_tensors=\"pt\")\n    generated_ids = model.generate(inputs[\"input_features\"].to(device))\n    transcription = processor.batch_decode(generated_ids, skip_special_tokens=True)[0]\n```\n\n### 3. แก้ปัญหาเสียงคุณภาพต่ำ:\n```python\nimport librosa\nimport noisereduce as nr\n\n# Load audio\naudio, sr = librosa.load(\"noisy_audio.mp3\", sr=16000)\n\n# ลดเสียงรบกวน\nreduced_noise = nr.reduce_noise(y=audio, sr=sr)\n\n# Transcribe\nresult = model.transcribe(reduced_noise, language=\"th\")\n```\n\n## Pro Tips:\n1. **ระบุภาษา**: ใส่ `language=\"th\"` เสมอเพื่อเพิ่มความแม่นยำ\n2. **Audio Quality**: ใช้ไฟล์เสียง 16kHz, mono\n3. **Chunk Long Audio**: แบ่งไฟล์ยาวๆ เป็น chunks\n4. **Use FP16**: ประหยัด memory และเร็วขึ้น 2x\n5. **Warm-up**: รัน inference 1-2 ครั้งก่อนจับเวลา\n\n## Real-world Example:\n\n```python\nfrom pydub import AudioSegment\nimport whisper\n\nmodel = whisper.load_model(\"large-v3\")\n\n# แปลง audio format\naudio = AudioSegment.from_file(\"input.m4a\")\naudio = audio.set_channels(1).set_frame_rate(16000)\naudio.export(\"processed.wav\", format=\"wav\")\n\n# Transcribe พร้อม timestamps\nresult = model.transcribe(\n    \"processed.wav\",\n    language=\"th\",\n    word_timestamps=True,  # ได้ timestamp แต่ละคำ\n)\n\n# Export to SRT (subtitle)\nfrom whisper.utils import WriteSRT\nwith open(\"output.srt\", \"w\", encoding=\"utf-8\") as f:\n    WriteSRT(f).write_result(result)\n```\n\n## Benchmarks:\n| Cloud Provider | GPU | Cost/hr | Speed |\n|---------------|-----|---------|-------|\n| Google Colab | T4 (Free) | $0 | 1x |\n| vast.ai | RTX 3090 | $0.30 | 2.5x |\n| RunPod | RTX 4090 | $0.50 | 3.5x |\n| Lambda Labs | A100 | $1.10 | 5x |\n\nWhisper V3 เป็นตัวเลือกที่ดีที่สุดสำหรับ Speech-to-Text ภาษาไทย!",
                'model_id' => 'openai/whisper-large-v3',
                'model_name' => 'Whisper Large V3',
                'news_type' => 'tutorial',
                'importance' => 'high',
                'model_tags' => ['whisper', 'speech-to-text', 'thai-language', 'tutorial', 'best-practices'],
                'model_type' => 'Audio Processing',
                'is_published' => true,
                'is_featured' => true,
                'is_pinned' => false,
                'author_name' => 'Thai AI Community',
                'published_at' => now()->subDays(7),
                'view_count' => 8940,
                'share_count' => 189,
                'metadata' => [
                    'reading_time' => '12 min',
                    'difficulty' => 'beginner',
                    'code_examples' => 6,
                ],
            ],

            // 💡 Tutorial - Mixtral
            [
                'title' => 'Deploy Mixtral 8x7B บน Cloud GPU ในงบ $2/ชม. - คู่มือฉบับสมบูรณ์',
                'summary' => 'วิธี deploy Mixtral 8x7B โมเดล MoE ที่ประหยัดและทรงพลัง บน RunPod, vast.ai และ Google Colab',
                'content' => "# Mixtral 8x7B Deployment Guide\n\nMixtral เป็น Sparse Mixture of Experts model ที่ให้ประสิทธิภาพใกล้เคียง GPT-3.5 แต่ใช้ทรัพยากรน้อยกว่ามาก\n\n## Why Mixtral?\n- ⚡ เร็วกว่า Llama 2 70B ถึง 6x\n- 💰 ถูกกว่า (ใช้แค่ 2 experts ต่อ token)\n- 🎯 Context length 32K tokens\n- 📊 MMLU: 70.6%\n\n## Deployment Options:\n\n### Option 1: RunPod (แนะนำ)\n**Cost**: ~$1.20/hr (2x RTX 4090)\n\n1. ไปที่ [RunPod.io](https://runpod.io)\n2. เลือก template \"vLLM\"\n3. GPU: 2x RTX 4090 48GB\n4. เพิ่ม Environment Variables:\n```\nMODEL_NAME=mistralai/Mixtral-8x7B-Instruct-v0.1\nGPU_MEMORY_UTILIZATION=0.95\nMAX_MODEL_LEN=32768\n```\n\n### Option 2: vast.ai (ถูกที่สุด)\n**Cost**: ~$0.80/hr\n\n```bash\nvastai search offers 'gpu_ram >= 48 num_gpus>=1 rentable=True'\nvastai create instance <INSTANCE_ID> --image runpod/pytorch:latest\nvastai ssh-url <INSTANCE_ID>\n\n# SSH เข้าไป\nssh root@<HOST>\n\n# ติดตั้ง dependencies\npip install vllm accelerate\n\n# Start server\nvllm serve mistralai/Mixtral-8x7B-Instruct-v0.1 \\\n    --gpu-memory-utilization 0.95 \\\n    --max-model-len 32768\n```\n\n### Option 3: Google Colab (ฟรี!)\n**Cost**: $0 (แต่มีข้อจำกัดเวลา)\n\n```python\n# ใน Colab notebook\n!pip install -q accelerate bitsandbytes transformers\n\nfrom transformers import AutoModelForCausalLM, AutoTokenizer\nimport torch\n\n# Load model ด้วย 4-bit quantization\nmodel_id = \"mistralai/Mixtral-8x7B-Instruct-v0.1\"\n\nmodel = AutoModelForCausalLM.from_pretrained(\n    model_id,\n    load_in_4bit=True,  # ใช้ 4-bit quantization\n    device_map=\"auto\",\n    torch_dtype=torch.float16,\n)\n\ntokenizer = AutoTokenizer.from_pretrained(model_id)\n\n# Chat!\nmessages = [\n    {\"role\": \"user\", \"content\": \"อธิบาย Mixture of Experts เป็นภาษาไทย\"}\n]\n\ninputs = tokenizer.apply_chat_template(messages, return_tensors=\"pt\").to(\"cuda\")\noutputs = model.generate(inputs, max_new_tokens=512)\nprint(tokenizer.decode(outputs[0], skip_special_tokens=True))\n```\n\n## Performance Optimization:\n\n### 1. ใช้ vLLM (เร็วที่สุด)\n```python\nfrom vllm import LLM, SamplingParams\n\nllm = LLM(\n    model=\"mistralai/Mixtral-8x7B-Instruct-v0.1\",\n    tensor_parallel_size=2,  # 2 GPUs\n    gpu_memory_utilization=0.95,\n)\n\nsampling_params = SamplingParams(\n    temperature=0.7,\n    top_p=0.95,\n    max_tokens=512,\n)\n\noutputs = llm.generate([\n    \"สวัสดี Mixtral!\",\n    \"อธิบาย AI\",\n], sampling_params)\n```\n\n### 2. ใช้ AWQ Quantization\n```python\n# AWQ 4-bit quantization - เร็วกว่า GPTQ\nmodel = AutoModelForCausalLM.from_pretrained(\n    \"TheBloke/Mixtral-8x7B-Instruct-v0.1-AWQ\",\n    device_map=\"auto\",\n)\n# ใช้ memory แค่ 24GB!\n```\n\n### 3. Flash Attention 2\n```python\npip install flash-attn --no-build-isolation\n\nmodel = AutoModelForCausalLM.from_pretrained(\n    model_id,\n    use_flash_attention_2=True,  # เร็วขึ้น 2x!\n)\n```\n\n## Cost Comparison:\n| Provider | GPU | VRAM | Cost/hr | Speed |\n|----------|-----|------|---------|-------|\n| Google Colab | T4 | 16GB | $0 | 0.5x (4-bit) |\n| vast.ai | RTX 3090 | 24GB | $0.30 | 0.8x (4-bit) |\n| RunPod | RTX 4090 | 24GB | $0.60 | 1x (4-bit) |\n| RunPod | 2x 4090 | 48GB | $1.20 | 2.5x (FP16) |\n| Lambda | A100 80GB | 80GB | $1.80 | 3x (FP16) |\n\n## Production Tips:\n1. ใช้ vLLM สำหรับ production\n2. ตั้ง `max_model_len` ให้เหมาะสมกับ use case\n3. Monitor GPU memory usage\n4. ใช้ load balancer สำหรับ high traffic\n5. Setup autoscaling\n\nMixtral คือโมเดลที่ balance ระหว่าง performance และ cost ดีที่สุด!",
                'model_id' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
                'model_name' => 'Mixtral 8x7B Instruct',
                'news_type' => 'tutorial',
                'importance' => 'high',
                'model_tags' => ['mixtral', 'deployment', 'cloud-gpu', 'tutorial', 'cost-effective'],
                'model_type' => 'Language Model',
                'is_published' => true,
                'is_featured' => false,
                'is_pinned' => false,
                'author_name' => 'Cloud GPU Expert',
                'published_at' => now()->subDays(10),
                'view_count' => 6780,
                'share_count' => 145,
                'metadata' => [
                    'reading_time' => '15 min',
                    'difficulty' => 'intermediate',
                    'code_examples' => 8,
                ],
            ],

            // 🔥 Trending - Stable Diffusion 3.5
            [
                'title' => 'Stable Diffusion 3.5 Large เปิดตัวแล้ว! คุณภาพดีขึ้นมาก',
                'summary' => 'SD 3.5 Large รุ่นใหม่ล่าสุด ปรับปรุงคุณภาพภาพ, prompt understanding, และเพิ่ม resolution สูงสุด 1 megapixel',
                'content' => "# Stable Diffusion 3.5 Large Launch\n\nStability AI เปิดตัว **Stable Diffusion 3.5 Large** โมเดล text-to-image รุ่นใหม่ล่าสุด\n\n## What's New:\n- 📐 Resolution สูงถึง 1 megapixel (1024x1024 ขึ้นไป)\n- 🎨 คุณภาพภาพดีขึ้น 30% จาก SD 3.0\n- 📝 เข้าใจ prompt ซับซ้อนได้ดีขึ้น\n- ⚡ Inference เร็วขึ้น 15%\n\n## Key Improvements:\n\n### 1. Better Prompt Following:\nSDXL:\n```\n\"A red cube on top of a blue sphere\"\n→ ได้ blue cube บน red sphere (ผิด!)\n```\n\nSD 3.5:\n```\n\"A red cube on top of a blue sphere\"\n→ ได้ red cube บน blue sphere ถูกต้อง! ✅\n```\n\n### 2. Higher Quality:\n- สีสันสดใสและสมจริงขึ้น\n- Texture รายละเอียดมากขึ้น\n- Composition ดีขึ้น\n\n## Quick Start:\n\n```python\nimport torch\nfrom diffusers import StableDiffusion3Pipeline\n\npipe = StableDiffusion3Pipeline.from_pretrained(\n    \"stabilityai/stable-diffusion-3.5-large\",\n    torch_dtype=torch.bfloat16\n)\npipe.to(\"cuda\")\n\nimage = pipe(\n    prompt=\"A serene Thai temple at sunset, photorealistic, 8k\",\n    negative_prompt=\"blurry, low quality, distorted\",\n    num_inference_steps=40,\n    guidance_scale=7.0,\n    height=1024,\n    width=1024,\n).images[0]\n\nimage.save(\"temple.png\")\n```\n\n## Hardware Requirements:\n- **Min VRAM**: 16 GB (RTX 4090, RTX 3090)\n- **Recommended**: 24 GB (A100, RTX 6000)\n- **Cost**: ~$0.80/hr บน vast.ai\n\n## Optimization Tips:\n\n### 1. Use SDPA (Scaled Dot Product Attention):\n```python\npipe.enable_xformers_memory_efficient_attention()\n# หรือ\npipe.unet.set_attn_processor(AttnProcessor2_0())\n```\n\n### 2. VAE Tiling:\n```python\npipe.enable_vae_tiling()\n# สร้างภาพใหญ่ได้โดยไม่ out of memory!\n```\n\n### 3. CPU Offload:\n```python\npipe.enable_sequential_cpu_offload()\n# ใช้ memory น้อยลง 50%\n```\n\n## Best Settings:\n- **Steps**: 40-50 (quality), 25-30 (speed)\n- **CFG Scale**: 7.0 (standard), 3-5 (creative)\n- **Sampler**: DPM++ 2M Karras (แนะนำ)\n\n## Comparison:\n| Model | Params | VRAM | Quality | Speed |\n|-------|--------|------|---------|-------|\n| SDXL | 6.6B | 12GB | 8/10 | 1x |\n| SD 3.0 | 8.0B | 16GB | 8.5/10 | 0.8x |\n| **SD 3.5** | 8.0B | 16GB | **9/10** | **0.95x** |\n| FLUX.1 | 12B | 24GB | 9.5/10 | 0.5x |\n\n## License:\nStability AI Community License - ใช้ในเชิงพาณิชย์ได้!\n\nSD 3.5 เป็นตัวเลือกที่ balance ระหว่าง quality และ cost ได้ดีมาก!",
                'model_id' => 'stabilityai/stable-diffusion-3.5-large',
                'model_name' => 'Stable Diffusion 3.5 Large',
                'news_type' => 'model_update',
                'importance' => 'high',
                'model_tags' => ['stable-diffusion', 'image-generation', 'stability-ai', 'new-release'],
                'model_type' => 'Image Generation',
                'is_published' => true,
                'is_featured' => true,
                'is_pinned' => false,
                'author_name' => 'Stability AI',
                'published_at' => now()->subDays(5),
                'view_count' => 12300,
                'share_count' => 267,
                'metadata' => [
                    'reading_time' => '10 min',
                    'difficulty' => 'intermediate',
                ],
            ],
        ];

        // สร้างข่าวสาร
        foreach ($news as $newsData) {
            HuggingFaceModelNews::create($newsData);
            $this->command->info("  ✅ {$newsData['title']}");
        }

        $this->command->info('✅ Seed ข่าวสาร Hugging Face สำเร็จ! สร้างทั้งหมด ' . count($news) . ' ข่าว');
    }
}
