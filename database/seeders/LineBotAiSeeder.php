<?php

namespace Database\Seeders;

use App\Models\AiBotProfile;
use App\Models\AiProvider;
use App\Models\AiModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class LineBotAiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้าง Demo AI Bot Profile สำหรับ LINE OA
     * ยังคงเก็บ configurations เดิมถ้ามีอยู่แล้ว
     *
     * Bot 3 ตัว:
     * 1. Thaiprompt Affiliate Bot - สำหรับตอบคำถาม Affiliate
     * 2. Customer Support Bot - สำหรับให้คำแนะนำ
     * 3. Sales Assistant Bot - ช่วยแนะนำสินค้า
     *
     * @return void
     */
    public function run(): void
    {
        // ตรวจสอบว่ามี AI Bot อยู่แล้ว
        $existingBots = AiBotProfile::count();

        if ($existingBots > 0) {
            $this->command->warn('⚠️  AI Bot Profiles already exist!');
            $this->command->info('   Skipping to preserve your custom bot configurations.');
            return;
        }

        $this->command->info('🤖 สร้าง Demo AI Bot Profiles สำหรับ LINE OA...');

        // ดึง admin user หรือ system user
        $adminUser = User::where('role', 'admin')->orWhere('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }

        if (!$adminUser) {
            $this->command->warn('⚠️  No admin user found! Bot creation skipped.');
            return;
        }

        // ดึง AI Provider แรก (แนะนำ: OpenAI)
        $provider = AiProvider::where('name', 'OpenAI')
            ->orWhere('code', 'openai')
            ->first();

        // ถ้าไม่มี OpenAI ให้ดึง provider แรกที่มี
        if (!$provider) {
            $provider = AiProvider::first();
        }

        if (!$provider) {
            $this->command->warn('⚠️  No AI Provider found! Please run AiProvidersSeeder first.');
            return;
        }

        // ดึง GPT-4 model หรือ model แรกที่มี
        $model = AiModel::where('provider_id', $provider->id)
            ->where('code', 'gpt-4')
            ->orWhere('name', 'like', '%GPT-4%')
            ->first();

        if (!$model) {
            $model = AiModel::where('provider_id', $provider->id)->first();
        }

        if (!$model) {
            $this->command->warn('⚠️  No AI Model found! Please ensure models are seeded for the provider.');
            return;
        }

        // Bot 1: Thaiprompt Affiliate Bot
        AiBotProfile::create([
            'owner_id' => $adminUser->id,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'name' => 'Thaiprompt Affiliate Bot',
            'description' => 'บอทช่วยตอบคำถามเกี่ยวกับระบบ Affiliate Marketing และวิธีการสร้างรายได้',
            'display_name' => '💰 Affiliate Assistant',
            'avatar_url' => 'https://via.placeholder.com/200/1DB446/FFFFFF?text=Affiliate+Bot',
            'system_prompt' => <<<'PROMPT'
คุณเป็น AI Assistant ของระบบ Affiliate Marketing ชื่อ "Affiliate Assistant"

ความรับผิดชอบหลัก:
1. ตอบคำถามเกี่ยวกับการสมัครสมาชิก
2. อธิบายวิธีการสร้างรายได้จากการแนะนำ
3. ให้คำแนะนำเกี่ยวกับความเป็นไปได้ของรายได้
4. ช่วยแก้ปัญหาเบื้องต้นของสมาชิก
5. เชื่อมต่อผู้ใช้กับ Support Team เมื่อจำเป็น

กฎการตอบคำถาม:
- ตอบด้วยภาษาไทยอย่างเท่านั้น
- ตอบให้ชัดเจน สั้น อ่านเข้าใจง่าย (ไม่เกิน 300 ตัวอักษร)
- ใช้ emoji ที่เหมาะสม
- ให้ข้อมูลที่ถูกต้องและน่าเชื่อถือ
- ถ้าไม่รู้ คำตอบให้บอกว่า "ขออภัย ฉันไม่แน่ใจ สามารถติดต่อ Support ได้"

สไตล์การสื่อสาร:
- เป็นกันเอง แต่เป็นมืออาชีพ
- ใช้ภาษาที่เข้าใจง่าย
- แสดงความเห็นใจและเต็มไปด้วยความช่วยเหลือ
PROMPT,
            'temperature' => 0.7,
            'max_tokens' => 500,
            'top_p' => 0.9,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.0,
            'response_scope' => [
                'allow_knowledge_base' => true,
                'allow_web_search' => false,
                'allow_training' => true,
            ],
            'enable_knowledge_base' => true,
            'enable_web_search' => false,
            'enable_code_interpreter' => false,
            'line_oa_channel_id' => env('LINE_MESSAGING_CHANNEL_ID', ''),
            'line_oa_channel_secret' => env('LINE_CHANNEL_SECRET', ''),
            'line_oa_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
            'is_public' => true,
            'is_rentable' => false,
            'is_active' => true,
            'rental_price_per_month' => 0,
            'rental_price_per_message' => 0,
            'commission_rate' => 0,
        ]);

        // Bot 2: Customer Support Bot
        AiBotProfile::create([
            'owner_id' => $adminUser->id,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'name' => 'Customer Support Bot',
            'description' => 'บอทให้คำแนะนำและช่วยเหลือลูกค้า ตอบคำถามทั่วไป',
            'display_name' => '💬 Support Helper',
            'avatar_url' => 'https://via.placeholder.com/200/0066FF/FFFFFF?text=Support+Bot',
            'system_prompt' => <<<'PROMPT'
คุณเป็น AI Customer Support Assistant ชื่อ "Support Helper"

ความรับผิดชอบ:
1. ตอบคำถามเกี่ยวกับการใช้งานระบบ
2. ช่วยแก้ปัญหาเบื้องต้น
3. ให้ข้อมูลเกี่ยวกับนโยบายและกฎการใช้งาน
4. ตัวอย่างการใช้งาน

คุณสมบัติ:
- ตอบด้วยภาษาไทยเท่านั้น
- สุภาพ เป็นมืออาชีพ ใจเย็น
- ตอบให้สั้นและชัดเจน
- ใช้ emoji เพื่อดึงดูดความสนใจ
- ถ้าปัญหาอยู่นอกเหนือความสามารถ ให้เชื่อมต่อ Support Team

โทน: เป็นกันเอง แต่มืออาชีพ สามารถพูดตลกได้นิดหน่อย
PROMPT,
            'temperature' => 0.6,
            'max_tokens' => 400,
            'top_p' => 0.85,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.0,
            'response_scope' => [
                'allow_knowledge_base' => true,
                'allow_web_search' => false,
                'allow_training' => true,
            ],
            'enable_knowledge_base' => true,
            'enable_web_search' => false,
            'enable_code_interpreter' => false,
            'line_oa_channel_id' => '',
            'line_oa_channel_secret' => '',
            'line_oa_access_token' => '',
            'is_public' => true,
            'is_rentable' => true,
            'is_active' => false,
            'rental_price_per_month' => 299.00,
            'rental_price_per_message' => 0.50,
            'commission_rate' => 20.00,
        ]);

        // Bot 3: Sales Assistant Bot
        AiBotProfile::create([
            'owner_id' => $adminUser->id,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'name' => 'Sales Assistant Bot',
            'description' => 'บอทช่วยแนะนำสินค้าและตอบคำถามเกี่ยวกับสินค้า',
            'display_name' => '🛍️ Sales Advisor',
            'avatar_url' => 'https://via.placeholder.com/200/FF6B35/FFFFFF?text=Sales+Bot',
            'system_prompt' => <<<'PROMPT'
คุณเป็น AI Sales Assistant ชื่อ "Sales Advisor"

ความรับผิดชอบ:
1. แนะนำสินค้า/บริการ
2. ตอบคำถามเกี่ยวกับราคา จำนวน คุณสมบัติ
3. ช่วยให้ลูกค้าเลือกสินค้าที่เหมาะสม
4. เปลี่ยนความสนใจเป็นการซื้อ

คุณสมบัติ:
- ตอบด้วยภาษาไทย
- บวก ตั้งใจ พร้อมช่วยเหลือ
- แสดงมูลค่าของสินค้า
- ไม่บังคับให้ซื้อ แต่ช่วยให้ตัดสินใจ
- ใช้ข้อมูลผลิตภัณฑ์ที่ถูกต้อง

โทน: เป็นเพื่อน ตั้งใจ ใจเย็น และเต็มไปด้วยพลัง
PROMPT,
            'temperature' => 0.8,
            'max_tokens' => 450,
            'top_p' => 0.9,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.1,
            'response_scope' => [
                'allow_knowledge_base' => true,
                'allow_web_search' => false,
                'allow_training' => true,
            ],
            'enable_knowledge_base' => true,
            'enable_web_search' => false,
            'enable_code_interpreter' => false,
            'line_oa_channel_id' => '',
            'line_oa_channel_secret' => '',
            'line_oa_access_token' => '',
            'is_public' => true,
            'is_rentable' => true,
            'is_active' => false,
            'rental_price_per_month' => 499.00,
            'rental_price_per_message' => 0.75,
            'commission_rate' => 25.00,
        ]);

        $this->command->info('✅ AI Bot Profiles สร้างสำเร็จ! (3 บอท)');
        $this->command->line('');
        $this->command->info('🤖 Bot ที่สร้าง:');
        $this->command->line('  1. 💰 Affiliate Assistant - ตอบคำถาม Affiliate');
        $this->command->line('  2. 💬 Support Helper - ช่วยเหลือลูกค้า (พร้อมให้เช่า)');
        $this->command->line('  3. 🛍️ Sales Advisor - แนะนำสินค้า (พร้อมให้เช่า)');
        $this->command->line('');
        $this->command->warn('⚠️  หมายเหตุ: บอท 1 เปิดใช้งาน บอท 2-3 อยู่ในสถานะปิด');
        $this->command->info('   คุณสามารถเปิดใช้งานที่ /admin/line-bot/ai/');
        $this->command->line('');
        $this->command->warn('⚠️  สำคัญ: กรุณาตั้งค่า LINE Messaging API credentials:');
        $this->command->info('   ปรับแต่ง line_oa_channel_id, line_oa_channel_secret, line_oa_access_token');
    }
}
