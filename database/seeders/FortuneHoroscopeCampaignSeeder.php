<?php

namespace Database\Seeders;

use App\Models\FortuneHoroscopeCampaign;
use Illuminate\Database\Seeder;

/**
 * FortuneHoroscopeCampaignSeeder
 *
 * สร้างแคมเปญ Default สำหรับระบบโพสดวงรายวันอัตโนมัติ
 * พร้อม Smart Marketing (auto hashtags, CTA, engagement hooks)
 *
 * ฟีเจอร์:
 * - AI สร้างคำทำนายดวง 7 วันเกิด (อาทิตย์-เสาร์) ทุกวัน
 * - AI สร้างรูปภาพ zodiac card (Pollinations ฟรี)
 * - Smart Hashtags อัตโนมัติ (แท็กตามวัน/เดือน/เทรนด์)
 * - Engagement Hooks (กระตุ้นคอมเมนต์/แชร์)
 * - Call-to-Action (ชวนดูดวงเชิงลึก)
 * - โพสลง Facebook อัตโนมัติ เวลา 06:00
 * - รองรับ LINE (ปิดเป็น default เพราะมีโควต้า)
 */
class FortuneHoroscopeCampaignSeeder extends Seeder
{
    /**
     * สร้างแคมเปญ Default พร้อมใช้งานทันที
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🔮 กำลัง seed ข้อมูล Fortune Horoscope Campaigns...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือยัง (idempotent)
        if (FortuneHoroscopeCampaign::count() > 0) {
            $this->command->info('ข้อมูลมีอยู่แล้ว ข้าม...');

            return;
        }

        FortuneHoroscopeCampaign::create([
            // === ข้อมูลพื้นฐาน ===
            'name' => 'ดวงรายวัน 7 วันเกิด — Smart Marketing',
            'description' => 'แคมเปญดูดวงรายวันอัตโนมัติ ครอบคลุมทุกวันเกิด (อาทิตย์-เสาร์) '
                . 'AI สร้างคำทำนาย + รูปภาพ + Smart Hashtags + Engagement Hooks + CTA '
                . 'โพสลง Facebook ทุกวันเวลา 06:00 น.',

            // === ช่องทางโพส ===
            'post_to_facebook' => true,
            'post_to_line' => false,       // ปิด LINE เป็น default (ต้องมี token + มีโควต้า)
            'use_fortune_settings_tokens' => true, // ใช้ token จากตั้งค่าระบบดูดวง

            // === AI Text Generation ===
            'ai_text_provider' => 'auto',  // ใช้ provider ที่ดีที่สุดอัตโนมัติ
            'text_prompt_template' => $this->getTextPrompt(),

            // === AI Image Generation ===
            'ai_image_provider' => 'pollinations', // ฟรี ไม่มี API key
            'ai_image_model' => 'flux',
            'image_size' => '1024x1024',
            'image_style' => 'artistic',
            'image_prompt_template' => $this->getImagePrompt(),

            // === กำหนดเวลาโพส ===
            'schedule_time' => '06:00',    // โพสตี 6 ทุกวัน
            'timezone' => 'Asia/Bangkok',
            'active_days' => [0, 1, 2, 3, 4, 5, 6],        // โพสทุกวัน
            'target_birth_days' => [0, 1, 2, 3, 4, 5, 6],   // ครอบคลุมทุกวันเกิด

            // === รูปแบบโพส ===
            'include_image' => true,
            'include_lucky_info' => true,
            'post_format' => 'combined',   // รวมทุกวันเกิดในโพสเดียว
            'post_header_template' => $this->getHeaderTemplate(),
            'post_footer_template' => $this->getFooterTemplate(),

            // === Smart Marketing ===
            'enable_auto_hashtags' => true,
            'custom_hashtags' => '#ดวงรายวัน #โหราศาสตร์ไทย #ดูดวงฟรี #ดวงวันนี้ #ดวง7วันเกิด',
            'enable_cta' => true,
            'cta_text' => '🔮 อยากรู้ดวงเชิงลึกเฉพาะตัวคุณ? ทักแชทมาเลยค่ะ!',
            'enable_engagement_hooks' => true,
            'page_name' => 'Thaiprompt',
            'page_mention' => '@thaiprompt',

            // === LINE Quota (สำรองไว้เมื่อเปิด LINE) ===
            'line_monthly_quota' => 500,
            'line_used_this_month' => 0,
            'line_quota_warning_threshold' => 50,

            // === สถานะ ===
            'status' => 'active',          // พร้อมใช้งานทันที
            'created_by' => 1,             // Super Admin
        ]);

        $this->command->info('✅ Seed แคมเปญดวงรายวัน Smart Marketing สำเร็จ!');
        $this->command->info('   📌 ชื่อ: ดวงรายวัน 7 วันเกิด — Smart Marketing');
        $this->command->info('   📌 สถานะ: Active (พร้อมใช้งาน)');
        $this->command->info('   📌 Facebook: เปิด | LINE: ปิด (ต้องตั้งค่า token)');
        $this->command->info('   📌 เวลาโพส: 06:00 น. ทุกวัน');
        $this->command->info('   📌 Smart Marketing: Hashtags + CTA + Engagement Hooks');
    }

    /**
     * AI Text Prompt Template — สร้างคำทำนายแบบ Marketing-Focused
     *
     * Placeholders:
     * - {target_date} วันที่ (แบบไทย)
     * - {birth_day_name} ชื่อวันเกิด (อาทิตย์-เสาร์)
     * - {main_planet} ดาวเจ้าชนะ
     * - {element} ธาตุ
     * - {lucky_color} สีมงคล
     * - {friend_planets} ดาวมิตร
     * - {enemy_planets} ดาวศัตรู
     * - {planet_positions} ตำแหน่งดาว
     */
    protected function getTextPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็น "แม่หมอจันทรา" หมอดูชื่อดังเชี่ยวชาญโหราศาสตร์ไทย (หลักเจ้าชนะ) ที่เก่งเรื่องการเขียนคอนเทนต์โซเชียลมีเดีย

📅 วันนี้วันที่: {target_date}
👤 คนเกิดวัน: {birth_day_name}
⭐ ดาวเจ้าชนะ: {main_planet}
🔥 ธาตุ: {element}
🎨 สีมงคล: {lucky_color}
🤝 ดาวมิตร: {friend_planets}
⚔️ ดาวศัตรู: {enemy_planets}
🪐 ตำแหน่งดาว: {planet_positions}

✍️ ทำนายดวงวันนี้สำหรับคนเกิดวัน{birth_day_name} ในสไตล์ที่:
- ฟันธง ชัดเจน ไม่กั๊ก อ้างอิงตำแหน่งดาวจริง
- ใช้ภาษาโดนใจ ให้คนอ่านอยากแชร์/คอมเมนต์/แท็กเพื่อน
- เน้นเรื่องที่คนสนใจ (เงิน, ความรัก, โชคลาภ)
- มีจุดเด่นที่น่าจดจำ (คำคมประจำวัน/คำเตือนสำคัญ)

📋 รูปแบบ:
1. 🔥 ภาพรวมวันนี้ (2-3 ประโยค เปิดด้วยสิ่งน่าสนใจที่สุด เช่น "ดาว{main_planet}ส่งพลัง...")
2. 💰 การเงิน/โชคลาภ (1-2 ประโยค ระบุช่วงเวลาที่โชคดี)
3. 💕 ความรัก (1-2 ประโยค แยกคนโสด/คนมีคู่)
4. 💼 การงาน/การเรียน (1-2 ประโยค)
5. 🏥 สุขภาพ (1 ประโยค)

📌 กฎเหล็ก:
- ภาษาไทย 100% ใส่ emoji ให้น่าอ่าน
- ฟันธงชัดเจน อ้างอิงดาวเจ้าชนะ/มิตร/ศัตรู
- เขียนให้คนอ่านอยากแท็กเพื่อนที่เกิดวัน{birth_day_name}
- ห้ามคลุมเครือ ห้ามใช้คำว่า "อาจจะ" "น่าจะ" มากเกินไป
- ไม่เกิน 200 คำ
PROMPT;
    }

    /**
     * AI Image Prompt Template — สร้างรูป zodiac card
     */
    protected function getImagePrompt(): string
    {
        return 'Thai astrology zodiac card for {birth_day_name} born person, '
            . '{element} element theme with mystical {lucky_color} color scheme, '
            . 'celestial background with stars and {main_planet} planet, '
            . 'ornate Thai art style with golden decorations, '
            . 'sacred geometry patterns, cosmic energy visualization, '
            . 'high quality digital art, beautiful and mystical atmosphere, '
            . 'vertical card format 3:4 ratio';
    }

    /**
     * Post Header Template — ส่วนหัวโพส
     */
    protected function getHeaderTemplate(): string
    {
        return <<<'HEADER'
🔮✨ ดวงรายวัน {target_date} ✨🔮
━━━━━━━━━━━━━━━━━━━━━━━
📌 โดย แม่หมอจันทรา | โหราศาสตร์ไทย หลักเจ้าชนะ
HEADER;
    }

    /**
     * Post Footer Template — ส่วนท้ายโพส
     */
    protected function getFooterTemplate(): string
    {
        return <<<'FOOTER'
━━━━━━━━━━━━━━━━━━━━━━━
📌 ดวงนี้ใช้หลักเจ้าชนะ คำนวณจากตำแหน่งดาวจริง
💬 แม่นไหม? คอมเมนต์บอกกันค่ะ!
FOOTER;
    }
}
