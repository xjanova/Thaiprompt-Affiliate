<?php

namespace Database\Seeders;

use App\Models\FortuneHoroscopeCampaign;
use Illuminate\Database\Seeder;

/**
 * FortuneHoroscopeCampaignSeeder
 *
 * สร้างแคมเปญตัวอย่างสำหรับระบบโพสดวงรายวันอัตโนมัติ
 */
class FortuneHoroscopeCampaignSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับ Fortune Horoscope Campaigns
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🔮 กำลัง seed ข้อมูล Fortune Horoscope Campaigns...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือยัง
        if (FortuneHoroscopeCampaign::count() > 0) {
            $this->command->info('ข้อมูลมีอยู่แล้ว ข้าม...');

            return;
        }

        FortuneHoroscopeCampaign::create([
            'name' => 'ดวงรายวัน 7 วันเกิด',
            'description' => 'โพสดวงรายวันอัตโนมัติ ครอบคลุมทุกวันเกิด (อาทิตย์-เสาร์) โดยใช้ AI สร้างคำทำนาย + รูปภาพ',
            'post_to_facebook' => false,
            'post_to_line' => false,
            'use_fortune_settings_tokens' => true,
            'ai_text_provider' => 'auto',
            'ai_image_provider' => 'pollinations',
            'ai_image_model' => 'flux',
            'image_size' => '1024x1024',
            'image_style' => 'artistic',
            'schedule_time' => '06:00',
            'timezone' => 'Asia/Bangkok',
            'include_image' => true,
            'include_lucky_info' => true,
            'post_format' => 'combined',
            'text_prompt_template' => 'คุณเป็นหมอดูชื่อดังระดับประเทศ เชี่ยวชาญโหราศาสตร์ไทย (หลักเจ้าชนะ)

วันนี้วันที่: {target_date}
คนเกิดวัน: {birth_day_name}
ดาวเจ้าชนะ: {main_planet}
ธาตุ: {element}
สีมงคล: {lucky_color}
ดาวมิตร: {friend_planets}
ดาวศัตรู: {enemy_planets}
ตำแหน่งดาว: {planet_positions}

จงทำนายดวงวันนี้สำหรับคนเกิดวัน{birth_day_name} โดย:
1. ภาพรวมดวงวันนี้ (2-3 ประโยค ฟันธง)
2. การเงิน (1-2 ประโยค)
3. ความรัก (1-2 ประโยค)
4. การงาน (1-2 ประโยค)
5. สุขภาพ (1 ประโยค)
6. สีมงคล + เลขมงคล + ทิศมงคล

ใช้ภาษาไทยเข้าใจง่าย ใส่ emoji ให้น่าอ่าน ฟันธงชัดเจน อ้างอิงดาวเคราะห์ ห้ามคลุมเครือ
ความยาวไม่เกิน 200 คำ',
            'image_prompt_template' => 'Thai astrology zodiac card, {birth_day_name} born, {element} element theme, mystical {lucky_color} color scheme, celestial background with stars and {main_planet} planet, ornate Thai art style, golden decorations, sacred geometry, high quality digital art, beautiful and mystical',
            'post_header_template' => '🔮✨ ดวงรายวัน {target_date} ✨🔮
━━━━━━━━━━━━━━━━━━━━━━━',
            'post_footer_template' => '━━━━━━━━━━━━━━━━━━━━━━━
💬 อยากดูดวงเชิงลึกเฉพาะตัว?
👉 ทักหมอจันทราได้เลยค่ะ
#ดวงรายวัน #โหราศาสตร์ไทย #หมอจันทรา #ดูดวง',
            'status' => 'draft',
        ]);

        $this->command->info('✅ Seed ข้อมูล Fortune Horoscope Campaigns สำเร็จ! (1 แคมเปญตัวอย่าง)');
    }
}
