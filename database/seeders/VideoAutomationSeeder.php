<?php

namespace Database\Seeders;

use App\Models\VideoAutoSetting;
use App\Models\VideoAutoTemplate;
use Illuminate\Database\Seeder;

/**
 * Video Automation Seeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ Video Automation
 */
class VideoAutomationSeeder extends Seeder
{
    /**
     * รัน seeder
     */
    public function run(): void
    {
        $this->command->info('🎬 กำลัง seed ข้อมูล Video Automation...');

        $this->seedSettings();
        $this->seedDefaultTemplates();

        $this->command->info('✅ Seed ข้อมูล Video Automation สำเร็จ!');
    }

    /**
     * สร้างการตั้งค่าเริ่มต้น
     */
    protected function seedSettings(): void
    {
        $this->command->info('  📝 กำลังสร้างการตั้งค่าเริ่มต้น...');

        // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
        if (VideoAutoSetting::count() > 0) {
            $this->command->info('  ⏭️ มีการตั้งค่าอยู่แล้ว ข้าม...');

            return;
        }

        $settings = [
            // Suno AI Settings
            [
                'key' => 'suno_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'suno',
                'label' => 'Suno API Key',
                'description' => 'API Key สำหรับเชื่อมต่อ Suno AI (สร้างเพลง)',
            ],
            [
                'key' => 'suno_api_url',
                'value' => 'https://api.suno.ai/v1',
                'type' => 'string',
                'group' => 'suno',
                'label' => 'Suno API URL',
                'description' => 'Base URL ของ Suno API',
            ],
            [
                'key' => 'suno_webhook_url',
                'value' => '',
                'type' => 'string',
                'group' => 'suno',
                'label' => 'Suno Webhook URL',
                'description' => 'URL สำหรับรับแจ้งเตือนจาก Suno',
            ],

            // Freepik AI Settings
            [
                'key' => 'freepik_api_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'freepik',
                'label' => 'Freepik API Key',
                'description' => 'API Key สำหรับเชื่อมต่อ Freepik AI (สร้างภาพ)',
            ],
            [
                'key' => 'freepik_api_url',
                'value' => 'https://api.freepik.com/v1',
                'type' => 'string',
                'group' => 'freepik',
                'label' => 'Freepik API URL',
                'description' => 'Base URL ของ Freepik API',
            ],
            [
                'key' => 'freepik_default_style',
                'value' => 'realistic',
                'type' => 'string',
                'group' => 'freepik',
                'label' => 'สไตล์ภาพเริ่มต้น',
                'description' => 'สไตล์ภาพที่ใช้เป็นค่าเริ่มต้น',
            ],

            // YouTube Settings
            [
                'key' => 'youtube_client_id',
                'value' => '',
                'type' => 'string',
                'group' => 'youtube',
                'label' => 'YouTube Client ID',
                'description' => 'OAuth 2.0 Client ID จาก Google Cloud Console',
            ],
            [
                'key' => 'youtube_client_secret',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'youtube',
                'label' => 'YouTube Client Secret',
                'description' => 'OAuth 2.0 Client Secret จาก Google Cloud Console',
            ],

            // Video Settings
            [
                'key' => 'video_default_resolution',
                'value' => '1080p',
                'type' => 'string',
                'group' => 'video',
                'label' => 'ความละเอียดเริ่มต้น',
                'description' => 'ความละเอียดวิดีโอที่ใช้เป็นค่าเริ่มต้น',
            ],
            [
                'key' => 'video_default_fps',
                'value' => '30',
                'type' => 'integer',
                'group' => 'video',
                'label' => 'Frame Rate เริ่มต้น',
                'description' => 'จำนวน frame ต่อวินาที',
            ],
            [
                'key' => 'video_image_duration',
                'value' => '5',
                'type' => 'integer',
                'group' => 'video',
                'label' => 'ระยะเวลาแต่ละภาพ (วินาที)',
                'description' => 'ระยะเวลาที่แสดงแต่ละภาพในวิดีโอ',
            ],
            [
                'key' => 'video_default_transition',
                'value' => 'crossfade',
                'type' => 'string',
                'group' => 'video',
                'label' => 'Transition เริ่มต้น',
                'description' => 'รูปแบบการเปลี่ยนภาพที่ใช้เป็นค่าเริ่มต้น',
            ],
            [
                'key' => 'video_transition_duration',
                'value' => '1',
                'type' => 'float',
                'group' => 'video',
                'label' => 'ระยะเวลา Transition (วินาที)',
                'description' => 'ระยะเวลาการเปลี่ยนภาพ',
            ],
            [
                'key' => 'video_ken_burns_effect',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'video',
                'label' => 'Ken Burns Effect',
                'description' => 'เปิด/ปิด Ken Burns Effect (zoom เคลื่อนไหว)',
            ],

            // Storage Settings
            [
                'key' => 'storage_path',
                'value' => 'video-automation',
                'type' => 'string',
                'group' => 'storage',
                'label' => 'โฟลเดอร์เก็บไฟล์',
                'description' => 'โฟลเดอร์สำหรับเก็บไฟล์วิดีโอและเพลง',
            ],
            [
                'key' => 'auto_delete_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'ลบไฟล์อัตโนมัติหลัง (วัน)',
                'description' => 'จำนวนวันก่อนลบไฟล์อัตโนมัติ (0 = ไม่ลบ)',
            ],
            [
                'key' => 'max_storage_gb',
                'value' => '50',
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'ขนาดที่เก็บสูงสุด (GB)',
                'description' => 'ขนาดพื้นที่เก็บข้อมูลสูงสุด',
            ],
            [
                'key' => 'keep_original_files',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'storage',
                'label' => 'เก็บไฟล์ต้นฉบับ',
                'description' => 'เก็บไฟล์เพลงและภาพต้นฉบับหลังสร้างวิดีโอ',
            ],
        ];

        foreach ($settings as $setting) {
            VideoAutoSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('  ✅ สร้างการตั้งค่า '.count($settings).' รายการ');
    }

    /**
     * สร้าง Template เริ่มต้น
     */
    protected function seedDefaultTemplates(): void
    {
        $this->command->info('  🎨 กำลังสร้าง Template เริ่มต้น...');

        // ตรวจสอบว่ามี template อยู่แล้วหรือไม่
        if (VideoAutoTemplate::count() > 0) {
            $this->command->info('  ⏭️ มี template อยู่แล้ว ข้าม...');

            return;
        }

        $templates = [
            [
                'name' => 'เพลงป๊อป สไตล์สดใส',
                'slug' => 'pop-happy-digital-art',
                'description' => 'สร้างเพลงป๊อปจังหวะสนุก พร้อมภาพสีสันสดใส สไตล์ Digital Art',
                'music_genre' => 'pop',
                'music_mood' => 'happy',
                'music_prompt' => 'สร้างเพลงป๊อปจังหวะสนุก มีเสียงซินธ์และกลองหนักแน่น เหมาะกับการฟังยามพักผ่อน',
                'image_style' => 'digital_art',
                'image_prompt_template' => 'ภาพสีสันสดใส สไตล์ Digital Art มีความเคลื่อนไหวและพลังงาน',
                'music_duration' => 120,
                'images_count' => 12,
                'video_resolution' => '1080p',
                'slide_duration' => 5,
                'transition_type' => 'fade',
                'enable_ken_burns' => true,
                'default_category' => '10',
                'default_privacy' => 'public',
                'default_title_template' => 'Pop Music - {date}',
                'default_description_template' => 'เพลงป๊อปสร้างด้วย AI\n\nCreated with Video Automation',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'เพลง Lo-Fi ผ่อนคลาย',
                'slug' => 'lofi-chill-anime',
                'description' => 'สร้างเพลง Lo-Fi เบาๆ ฟังสบาย พร้อมภาพ Anime น่ารัก',
                'music_genre' => 'lofi',
                'music_mood' => 'chill',
                'music_prompt' => 'สร้างเพลง Lo-Fi จังหวะช้าๆ ผ่อนคลาย มีเสียง vinyl crackle และเปียโนเบาๆ',
                'image_style' => 'anime',
                'image_prompt_template' => 'ภาพ Anime สไตล์ Lo-Fi สบายตา มีตัวละครกำลังอ่านหนังสือหรือดื่มกาแฟ',
                'music_duration' => 180,
                'images_count' => 15,
                'video_resolution' => '1080p',
                'slide_duration' => 8,
                'transition_type' => 'fade',
                'enable_ken_burns' => true,
                'default_category' => '10',
                'default_privacy' => 'public',
                'default_title_template' => 'Lo-Fi Beats to Study/Relax - {date}',
                'default_description_template' => 'เพลง Lo-Fi ฟังสบาย เหมาะกับการอ่านหนังสือ ทำงาน หรือพักผ่อน\n\nCreated with Video Automation',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'เพลงไทย อารมณ์ดี',
                'slug' => 'thai-happy-realistic',
                'description' => 'สร้างเพลงไทยสมัยใหม่ พร้อมภาพทิวทัศน์ไทยสวยงาม',
                'music_genre' => 'thai',
                'music_mood' => 'happy',
                'music_prompt' => 'สร้างเพลงไทยสมัยใหม่ ผสมผสานดนตรีไทยกับป๊อป มีเสียงขลุ่ยและระนาดเบาๆ',
                'image_style' => 'realistic',
                'image_prompt_template' => 'ภาพทิวทัศน์ประเทศไทย วัด น้ำตก ทะเล สถาปัตยกรรมไทย สีสันสดใส',
                'music_duration' => 150,
                'images_count' => 12,
                'video_resolution' => '1080p',
                'slide_duration' => 6,
                'transition_type' => 'dissolve',
                'enable_ken_burns' => true,
                'default_category' => '10',
                'default_privacy' => 'public',
                'default_title_template' => 'เพลงไทย AI - {date}',
                'default_description_template' => 'เพลงไทยสมัยใหม่สร้างด้วย AI\nพร้อมภาพทิวทัศน์ประเทศไทยสวยงาม\n\nCreated with Video Automation',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'เพลง Electronic สุดมันส์',
                'slug' => 'electronic-energetic-3d',
                'description' => 'สร้างเพลง Electronic จังหวะเร้าใจ พร้อมภาพ 3D สุดล้ำ',
                'music_genre' => 'electronic',
                'music_mood' => 'energetic',
                'music_prompt' => 'สร้างเพลง Electronic จังหวะเร้าใจ มี drop หนักๆ เหมาะกับการออกกำลังกาย',
                'image_style' => '3d',
                'image_prompt_template' => 'ภาพ 3D สไตล์ Futuristic แสงนีออน สีสันจัดจ้าน มีพลังงานและความเคลื่อนไหว',
                'music_duration' => 120,
                'images_count' => 10,
                'video_resolution' => '1080p',
                'slide_duration' => 4,
                'transition_type' => 'zoom',
                'enable_ken_burns' => true,
                'default_category' => '10',
                'default_privacy' => 'public',
                'default_title_template' => 'EDM Energy - {date}',
                'default_description_template' => 'เพลง Electronic สุดมันส์ เหมาะกับการออกกำลังกาย\n\nCreated with Video Automation',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'เพลง Classical สงบผ่อนคลาย',
                'slug' => 'classical-peaceful-nature',
                'description' => 'สร้างเพลง Classical เปียโน พร้อมภาพธรรมชาติสวยงาม',
                'music_genre' => 'classical',
                'music_mood' => 'peaceful',
                'music_prompt' => 'สร้างเพลง Classical เปียโนเดี่ยว ท่วงทำนองสงบ เหมาะกับการนั่งสมาธิ',
                'image_style' => 'realistic',
                'image_prompt_template' => 'ภาพธรรมชาติสงบเงียบ ป่าไม้ ทะเลสาบ ภูเขา ท้องฟ้ายามรุ่งอรุณ',
                'music_duration' => 240,
                'images_count' => 20,
                'video_resolution' => '1080p',
                'slide_duration' => 10,
                'transition_type' => 'fade',
                'enable_ken_burns' => true,
                'default_category' => '10',
                'default_privacy' => 'public',
                'default_title_template' => 'Peaceful Piano - {date}',
                'default_description_template' => 'เพลงเปียโนสงบผ่อนคลาย เหมาะกับการนอนหลับ นั่งสมาธิ หรือพักผ่อน\n\nCreated with Video Automation',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            VideoAutoTemplate::create($templateData);
        }

        $this->command->info('  ✅ สร้าง template '.count($templates).' รายการ');
    }
}
