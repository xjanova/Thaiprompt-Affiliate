<?php

namespace Database\Seeders;

use App\Models\AiContentSetting;
use App\Models\AiContentTemplate;
use Illuminate\Database\Seeder;

/**
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ AI Content Writer
 *
 * รวมถึง:
 * - การตั้งค่า API Keys เริ่มต้น (ว่าง)
 * - เทมเพลตตัวอย่าง 10 แบบ
 */
class AiContentWriterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🖊️ กำลัง seed ข้อมูล AI Content Writer...');

        // สร้างการตั้งค่าเริ่มต้น
        $this->seedSettings();

        // สร้างเทมเพลตตัวอย่าง
        $this->seedTemplates();

        $this->command->info('✅ Seed ข้อมูล AI Content Writer สำเร็จ!');
    }

    /**
     * สร้างการตั้งค่าเริ่มต้น
     *
     * @return void
     */
    protected function seedSettings(): void
    {
        $settings = [
            // OpenAI Settings
            [
                'key' => 'openai_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'openai',
                'label' => 'OpenAI API Key',
                'description' => 'API Key สำหรับ OpenAI (GPT-4, GPT-4o)',
            ],
            [
                'key' => 'openai_default_model',
                'value' => 'gpt-4o-mini',
                'type' => 'string',
                'group' => 'openai',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ OpenAI',
            ],

            // Claude Settings
            [
                'key' => 'claude_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'claude',
                'label' => 'Claude API Key',
                'description' => 'API Key สำหรับ Anthropic Claude',
            ],
            [
                'key' => 'claude_default_model',
                'value' => 'claude-3-haiku-20240307',
                'type' => 'string',
                'group' => 'claude',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ Claude',
            ],

            // Gemini Settings
            [
                'key' => 'gemini_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'gemini',
                'label' => 'Gemini API Key',
                'description' => 'API Key สำหรับ Google Gemini',
            ],
            [
                'key' => 'gemini_default_model',
                'value' => 'gemini-1.5-flash',
                'type' => 'string',
                'group' => 'gemini',
                'label' => 'Model เริ่มต้น',
                'description' => 'Model ที่ใช้เป็นค่าเริ่มต้นสำหรับ Gemini',
            ],

            // General Settings
            [
                'key' => 'default_provider',
                'value' => 'openai',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Provider เริ่มต้น',
                'description' => 'AI Provider ที่ใช้เป็นค่าเริ่มต้น',
            ],
            [
                'key' => 'default_temperature',
                'value' => '0.7',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Temperature เริ่มต้น',
                'description' => 'ระดับความสร้างสรรค์ (0-2)',
            ],
            [
                'key' => 'default_max_tokens',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'general',
                'label' => 'Max Tokens เริ่มต้น',
                'description' => 'จำนวน tokens สูงสุดต่อการสร้าง',
            ],
            [
                'key' => 'enable_usage_tracking',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'บันทึกการใช้งาน',
                'description' => 'เปิด/ปิดการบันทึก Usage Logs',
            ],
        ];

        foreach ($settings as $setting) {
            AiContentSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('   📝 สร้างการตั้งค่า AI Content Writer แล้ว');
    }

    /**
     * สร้างเทมเพลตตัวอย่าง
     *
     * @return void
     */
    protected function seedTemplates(): void
    {
        $templates = [
            // Blog Templates
            [
                'name' => 'บทความ SEO Blog',
                'description' => 'สร้างบทความ SEO ที่เหมาะกับการทำอันดับใน Google',
                'content_type' => 'blog',
                'category' => 'marketing',
                'system_prompt' => 'คุณเป็นนักเขียน Content SEO ผู้เชี่ยวชาญ มีประสบการณ์มากกว่า 10 ปีในการเขียนบทความที่ติดอันดับบน Google คุณเข้าใจหลักการ On-Page SEO เป็นอย่างดี',
                'user_prompt_template' => "เขียนบทความ SEO เกี่ยวกับหัวข้อ: {{topic}}\n\nความยาว: {{length}} คำ\nคีย์เวิร์ดหลัก: {{keyword}}\nกลุ่มเป้าหมาย: {{target_audience}}\n\nโครงสร้างที่ต้องการ:\n1. หัวข้อที่ดึงดูด (มีคีย์เวิร์ด)\n2. บทนำที่น่าสนใจ\n3. หัวข้อย่อย (H2, H3) อย่างน้อย 3 หัวข้อ\n4. สรุปและ Call-to-Action",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o',
                'temperature' => 0.7,
                'max_tokens' => 3000,
                'tone' => 'professional',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'บทความ How-To',
                'description' => 'สร้างบทความแนะนำวิธีการทำสิ่งต่างๆ แบบ Step-by-Step',
                'content_type' => 'blog',
                'category' => 'education',
                'system_prompt' => 'คุณเป็นผู้เชี่ยวชาญในการเขียนบทความ How-To ที่อธิบายขั้นตอนได้ชัดเจน เข้าใจง่าย เหมาะสำหรับผู้เริ่มต้น',
                'user_prompt_template' => "เขียนบทความ How-To: {{topic}}\n\nรายละเอียด:\n- จำนวนขั้นตอน: {{steps}} ขั้นตอน\n- ระดับความยาก: {{difficulty}}\n- อุปกรณ์ที่ต้องใช้: {{tools}}\n\nรวมถึง:\n- บทนำอธิบายประโยชน์\n- ขั้นตอนละเอียดพร้อมเคล็ดลับ\n- ข้อควรระวัง\n- สรุปผลลัพธ์ที่คาดหวัง",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
                'temperature' => 0.5,
                'max_tokens' => 2500,
                'tone' => 'friendly',
                'is_active' => true,
                'is_featured' => false,
            ],

            // Video Script Templates
            [
                'name' => 'Script YouTube แบบสั้น',
                'description' => 'Script สำหรับวิดีโอ YouTube Shorts หรือ TikTok',
                'content_type' => 'video_script',
                'category' => 'social',
                'system_prompt' => 'คุณเป็น Content Creator ผู้เชี่ยวชาญในการสร้าง Short-form Video ที่มียอดวิวล้านๆ เข้าใจอัลกอริทึมของ TikTok และ YouTube Shorts',
                'user_prompt_template' => "สร้าง Script วิดีโอสั้น (60 วินาที) เรื่อง: {{topic}}\n\nสไตล์: {{style}}\nอารมณ์: {{mood}}\n\nโครงสร้าง:\n1. Hook (3 วินาทีแรก) - ดึงดูดความสนใจ\n2. Problem - ปัญหาที่คนดูเจอ\n3. Solution - วิธีแก้\n4. Call-to-Action - กดติดตาม/แชร์",
                'default_provider' => 'claude',
                'default_model' => 'claude-3-haiku-20240307',
                'temperature' => 0.8,
                'max_tokens' => 800,
                'tone' => 'entertaining',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Script YouTube แบบยาว',
                'description' => 'Script สำหรับวิดีโอ YouTube แบบเต็ม (8-15 นาที)',
                'content_type' => 'video_script',
                'category' => 'education',
                'system_prompt' => 'คุณเป็น YouTuber ผู้เชี่ยวชาญในการสร้างวิดีโอที่ให้ความรู้ มี Watch Time สูง และทำให้คนดูจนจบ',
                'user_prompt_template' => "สร้าง Script วิดีโอ YouTube (10 นาที) เรื่อง: {{topic}}\n\nกลุ่มเป้าหมาย: {{target_audience}}\nวัตถุประสงค์: {{objective}}\n\nโครงสร้าง:\n1. Intro Hook (30 วินาที)\n2. Problem Statement\n3. เนื้อหาหลัก 3-5 ประเด็น\n4. Case Study/ตัวอย่าง\n5. สรุปและ Call-to-Action\n\nรวม: Timestamp สำหรับแต่ละส่วน",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o',
                'temperature' => 0.6,
                'max_tokens' => 4000,
                'tone' => 'educational',
                'is_active' => true,
                'is_featured' => false,
            ],

            // Social Media Templates
            [
                'name' => 'Caption Instagram',
                'description' => 'สร้าง Caption Instagram ที่มี Engagement สูง',
                'content_type' => 'social_caption',
                'category' => 'social',
                'system_prompt' => 'คุณเป็น Social Media Manager มืออาชีพ เข้าใจ Algorithm ของ Instagram และรู้วิธีสร้าง Caption ที่ได้ Engagement สูง',
                'user_prompt_template' => "สร้าง Caption Instagram สำหรับ: {{topic}}\n\nประเภทโพสต์: {{post_type}}\nโทน: {{tone}}\nมี Hashtag: {{include_hashtags}}\n\nต้องมี:\n- Hook ประโยคแรก\n- เนื้อหา 2-3 ย่อหน้า\n- Call-to-Action\n- Emoji ที่เหมาะสม\n- Hashtag 10-15 อัน",
                'default_provider' => 'gemini',
                'default_model' => 'gemini-1.5-flash',
                'temperature' => 0.8,
                'max_tokens' => 600,
                'tone' => 'casual',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Thread Twitter/X',
                'description' => 'สร้าง Thread Twitter ที่น่าสนใจและ Viral',
                'content_type' => 'social_caption',
                'category' => 'social',
                'system_prompt' => 'คุณเป็นผู้เชี่ยวชาญในการเขียน Twitter Thread ที่ Viral คุณรู้จักการเล่าเรื่องที่ทำให้คนอ่านจนจบและ Retweet',
                'user_prompt_template' => "สร้าง Twitter Thread (10 ทวีต) เรื่อง: {{topic}}\n\nสไตล์: {{style}}\nกลุ่มเป้าหมาย: {{audience}}\n\nโครงสร้าง:\n1. ทวีตแรก - Hook ดึงดูดความสนใจ\n2. ทวีต 2-8 - เนื้อหาหลัก\n3. ทวีต 9 - สรุป\n4. ทวีตสุดท้าย - CTA (Follow, RT)\n\nแต่ละทวีตไม่เกิน 280 ตัวอักษร",
                'default_provider' => 'claude',
                'default_model' => 'claude-3-sonnet-20240229',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'tone' => 'conversational',
                'is_active' => true,
                'is_featured' => false,
            ],

            // Marketing Templates
            [
                'name' => 'Email Marketing',
                'description' => 'สร้างอีเมลการตลาดที่มี Open Rate สูง',
                'content_type' => 'email',
                'category' => 'marketing',
                'system_prompt' => 'คุณเป็นผู้เชี่ยวชาญ Email Marketing ที่เขียนอีเมลได้ Open Rate 40%+ และ Click Rate 15%+ คุณเข้าใจจิตวิทยาของผู้อ่าน',
                'user_prompt_template' => "สร้างอีเมลการตลาดสำหรับ: {{product_service}}\n\nวัตถุประสงค์: {{objective}}\nกลุ่มเป้าหมาย: {{audience}}\nข้อเสนอ: {{offer}}\n\nต้องมี:\n1. Subject Line 3 แบบ\n2. Preview Text\n3. เนื้อหาอีเมล (Greeting, Problem, Solution, Benefits, CTA)\n4. P.S. Line",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
                'temperature' => 0.6,
                'max_tokens' => 1500,
                'tone' => 'persuasive',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Copy โฆษณา Facebook',
                'description' => 'สร้าง Ad Copy สำหรับ Facebook/Instagram Ads',
                'content_type' => 'ad_copy',
                'category' => 'marketing',
                'system_prompt' => 'คุณเป็น Copywriter มืออาชีพที่เชี่ยวชาญ Facebook Ads คุณเขียน Copy ที่ได้ CTR สูงและ Cost Per Result ต่ำ',
                'user_prompt_template' => "สร้าง Ad Copy สำหรับ Facebook Ads:\n\nสินค้า/บริการ: {{product}}\nราคา: {{price}}\nโปรโมชั่น: {{promotion}}\nกลุ่มเป้าหมาย: {{target}}\nวัตถุประสงค์: {{objective}}\n\nสร้าง 3 เวอร์ชัน:\n1. Primary Text (125 ตัวอักษร)\n2. Headline (40 ตัวอักษร)\n3. Description (30 ตัวอักษร)\n\nรวมเวอร์ชันสำหรับ: Awareness, Consideration, Conversion",
                'default_provider' => 'claude',
                'default_model' => 'claude-3-haiku-20240307',
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'tone' => 'persuasive',
                'is_active' => true,
                'is_featured' => false,
            ],

            // Product Description
            [
                'name' => 'รายละเอียดสินค้า E-commerce',
                'description' => 'สร้างรายละเอียดสินค้าที่น่าสนใจและขายได้',
                'content_type' => 'product_description',
                'category' => 'ecommerce',
                'system_prompt' => 'คุณเป็น E-commerce Copywriter ผู้เชี่ยวชาญ เขียนรายละเอียดสินค้าที่ทำให้คนอยากซื้อ มี Conversion Rate สูง',
                'user_prompt_template' => "สร้างรายละเอียดสินค้าสำหรับ:\n\nชื่อสินค้า: {{product_name}}\nหมวดหมู่: {{category}}\nราคา: {{price}}\nคุณสมบัติ: {{features}}\nกลุ่มเป้าหมาย: {{target}}\n\nต้องมี:\n1. หัวข้อที่ดึงดูด\n2. รายละเอียดสั้นๆ (50 คำ)\n3. รายละเอียดยาว (150 คำ)\n4. จุดเด่น 5 ข้อ (Bullet Points)\n5. ข้อมูลจำเพาะ\n6. FAQ 3 ข้อ",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
                'temperature' => 0.5,
                'max_tokens' => 1500,
                'tone' => 'professional',
                'is_active' => true,
                'is_featured' => false,
            ],

            // General Template
            [
                'name' => 'สร้าง Content ทั่วไป',
                'description' => 'เทมเพลตทั่วไปสำหรับสร้าง Content ทุกประเภท',
                'content_type' => 'general',
                'category' => 'general',
                'system_prompt' => 'คุณเป็นนักเขียน Content มืออาชีพ สามารถเขียนได้ทุกประเภท ทุกโทน และทุกรูปแบบ',
                'user_prompt_template' => "{{prompt}}",
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'tone' => null,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($templates as $template) {
            // เช็คว่ามีอยู่แล้วหรือไม่
            if (AiContentTemplate::where('name', $template['name'])->exists()) {
                continue;
            }

            AiContentTemplate::create($template);
        }

        $this->command->info('   📄 สร้างเทมเพลต AI Content Writer ' . count($templates) . ' รายการแล้ว');
    }
}
