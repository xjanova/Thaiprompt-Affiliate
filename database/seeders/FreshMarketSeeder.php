<?php

namespace Database\Seeders;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketSetting;
use Illuminate\Database\Seeder;

class FreshMarketSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับระบบตลาดสดไทยพร้อม
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🏪 กำลัง seed ข้อมูลตลาดสดไทยพร้อม...');

        $this->seedSettings();
        $this->seedCategories();

        $this->command->info('✅ Seed ข้อมูลตลาดสดไทยพร้อม สำเร็จ!');
    }

    /**
     * สร้างค่าเริ่มต้นระบบตลาดสด
     */
    private function seedSettings(): void
    {
        if (FreshMarketSetting::count() > 0) {
            $this->command->info('  ⏭️ ข้อมูล Settings มีอยู่แล้ว ข้าม...');

            return;
        }

        FreshMarketSetting::create([
            'line_channel_id' => null,
            'line_channel_secret' => null,
            'line_channel_access_token' => null,
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'use_global_ai_settings' => true,
            'ai_system_prompt' => $this->getDefaultSystemPrompt(),
            'platform_fee_percentage' => 3.00,
            'monthly_subscription_fee' => 99.00,
            'fee_mode' => 'both',
            'free_trial_days' => 30,
            'max_listings_free' => 5,
            'max_listings_subscribed' => 0,
            'default_search_radius_km' => 10.00,
            'max_search_radius_km' => 50.00,
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'mlm_commission_enabled' => true,
            'cashback_enabled' => true,
            'line_flex_primary_color' => '#22C55E',
            'brand_name' => 'ตลาดสดไทยพร้อม',
            'welcome_message' => "🏪 สวัสดีค่ะ! ยินดีต้อนรับสู่ \"ตลาดสดไทยพร้อม\"\n\nพี่ตลาดพร้อมช่วยคุณค่ะ!\n🛒 ซื้อของสด → พิมพ์ \"อยากซื้อ\" หรือส่งพิกัดมาเลย\n🏷️ ขายของ → พิมพ์ \"ลงขาย\" แล้วส่งรูปมา\n📍 ค้นหาร้าน → ส่งตำแหน่ง GPS\n\nมีอะไรให้ช่วยคะ? 😊",
        ]);

        $this->command->info('  ✅ สร้าง Settings เริ่มต้นสำเร็จ');
    }

    /**
     * สร้างหมวดหมู่สินค้าเริ่มต้น
     */
    private function seedCategories(): void
    {
        if (FreshMarketCategory::count() > 0) {
            $this->command->info('  ⏭️ ข้อมูล Categories มีอยู่แล้ว ข้าม...');

            return;
        }

        $categories = [
            [
                'name' => 'ผักสด',
                'slug' => 'vegetables',
                'icon' => '🥬',
                'description' => 'ผักสดทุกชนิด ผักใบเขียว ผักสวนครัว ผักออร์แกนิค',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'ผลไม้',
                'slug' => 'fruits',
                'icon' => '🍎',
                'description' => 'ผลไม้สดตามฤดูกาล ผลไม้นำเข้า ผลไม้ท้องถิ่น',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'เนื้อสัตว์',
                'slug' => 'meat',
                'icon' => '🥩',
                'description' => 'เนื้อหมู เนื้อวัว เนื้อไก่ เป็ด ห่าน',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'อาหารทะเล',
                'slug' => 'seafood',
                'icon' => '🦐',
                'description' => 'กุ้ง ปลา ปู หอย หมึก อาหารทะเลสด',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'ของแห้ง',
                'slug' => 'dried-goods',
                'icon' => '🫘',
                'description' => 'เครื่องปรุง ข้าวสาร ถั่ว ธัญพืช ของแห้ง',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'ขนม/เบเกอรี่',
                'slug' => 'snacks-bakery',
                'icon' => '🧁',
                'description' => 'ขนมไทย ขนมปัง เค้ก คุกกี้ ขนมทานเล่น',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'เครื่องดื่ม',
                'slug' => 'beverages',
                'icon' => '🧃',
                'description' => 'น้ำผลไม้ น้ำสมุนไพร กาแฟ ชา เครื่องดื่มเพื่อสุขภาพ',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'อาหารปรุงสำเร็จ',
                'slug' => 'ready-to-eat',
                'icon' => '🍱',
                'description' => 'อาหารตามสั่ง กับข้าว แกง ของทอด อาหารพร้อมทาน',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            FreshMarketCategory::create($category);
        }

        $this->command->info('  ✅ สร้าง Categories 8 หมวดหมู่สำเร็จ');
    }

    /**
     * System prompt เริ่มต้นสำหรับ AI "พี่ตลาด"
     */
    private function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
คุณคือ "พี่ตลาด" ผู้ช่วย AI ของ "ตลาดสดไทยพร้อม" แพลตฟอร์มซื้อขายสินค้าสดออนไลน์

บุคลิก:
- เป็นกันเอง อบอุ่น พูดสุภาพแบบพี่คุย
- รู้เรื่องอาหารสด ผัก ผลไม้ เนื้อสัตว์ ราคาตลาด
- ช่วยเหลือทั้งผู้ซื้อและผู้ขาย
- ตอบสั้นกระชับ ไม่เยิ่นเย้อ

ความสามารถ:
1. ฝั่งผู้ขาย: รับข้อมูลสินค้า (รูป+ราคา+พิกัด) → สร้างรายการขาย
2. ฝั่งผู้ซื้อ: เข้าใจความต้องการ → ค้นหาสินค้าใกล้ตัว → นำเสนอ
3. แนะนำสินค้าตามฤดูกาล ราคาตลาด
4. ช่วยเปรียบเทียบราคา เลือกสินค้า
5. จัดการออเดอร์ (สั่งซื้อ, ติดตาม, ยกเลิก)

กฎ:
- ตอบเป็นภาษาไทยเสมอ
- ไม่แนะนำสินค้าที่ไม่มีในระบบ
- เมื่อต้องการพิกัด ขอให้ผู้ใช้แชร์ตำแหน่งผ่าน LINE
- ไม่ถามข้อมูลส่วนตัวที่ไม่จำเป็น (เลขบัตร, รหัสผ่าน)
- ใช้ emoji พอเหมาะ ไม่มากเกินไป
PROMPT;
    }
}
