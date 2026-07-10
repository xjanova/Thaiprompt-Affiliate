<?php

namespace Database\Seeders;

use App\Models\FortuneContentCampaign;
use App\Models\FortuneTellingSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * แคมเปญคอนเทนต์อัตโนมัติเริ่มต้น 5 แคมเปญ
 *
 * 1. สายมู (bridge ไปคลังหัวข้อเดิม fortune_mystic_topics) — ย้ายจากระบบเก่า:
 *    ดึง schedule + สถานะเปิดจาก settings เดิม แล้วปิด toggle เก่า (กันโพสซ้ำ 2 ระบบ)
 * 2-5. แนวใหม่ (ปิดไว้ก่อน ให้ admin เปิดเอง): กำลังใจ+ความเชื่อ / โดพามีน การใช้ชีวิต /
 *    กฎแห่งกรรม / เรื่องเล่าจิตวิทยา
 *
 * Idempotent — เช็ค slug ก่อนสร้างทุกแคมเปญ
 */
class FortuneContentCampaignSeeder extends Seeder
{
    /**
     * สร้างแคมเปญเริ่มต้น + ย้ายระบบสายมูเดิมเข้าแคมเปญ
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed แคมเปญคอนเทนต์อัตโนมัติ...');

        if (! Schema::hasTable('fortune_content_campaigns')) {
            $this->command->warn('⚠️  ยังไม่มีตาราง fortune_content_campaigns — รัน migrate ก่อน');

            return;
        }

        // ✅ First-run only: ถ้ามีแคมเปญใดๆ อยู่แล้ว = เคย seed แล้ว → ข้ามทั้งหมด
        //    (กัน flip-flop: admin ตั้งใจลบ/ปิดแคมเปญ แล้ว deploy รอบถัดไป seed สร้างคืน
        //     + flip toggle สายมูเก่าปิดซ้ำ ทำพฤติกรรมโพสเด้งไปมาทุก deploy)
        if (FortuneContentCampaign::query()->exists()) {
            $this->command->info('   มีแคมเปญอยู่แล้ว — ข้ามทั้งหมด (first-run only)');

            return;
        }

        $this->seedSaimooBridge();

        $campaigns = $this->defaultCampaigns();
        foreach ($campaigns as $data) {
            // ✅ Idempotent — มีอยู่แล้วข้าม (ไม่ทับของที่ admin แก้ไปแล้ว)
            if (FortuneContentCampaign::where('slug', $data['slug'])->exists()) {
                $this->command->info("   ข้าม \"{$data['name_th']}\" (มีอยู่แล้ว)");

                continue;
            }

            FortuneContentCampaign::create($data);
            $this->command->info("   ✅ สร้างแคมเปญ \"{$data['emoji']} {$data['name_th']}\"");
        }

        $this->command->info('✅ Seed แคมเปญคอนเทนต์สำเร็จ!');
    }

    /**
     * ย้ายระบบสายมูเดิม → แคมเปญ "สายมู" (bridge ไปคลังหัวข้อเดิม)
     *
     * - ดึง schedule + is_enabled จาก fortune_telling_settings เดิม
     * - ถ้าระบบเก่าเปิดอยู่ → เปิดแคมเปญใหม่แทน แล้วปิด toggle เก่า (กันโพสซ้ำ)
     */
    protected function seedSaimooBridge(): void
    {
        if (FortuneContentCampaign::where('slug', 'saimoo')->exists()) {
            $this->command->info('   ข้าม "สายมู" (มีอยู่แล้ว)');

            return;
        }

        $oldEnabled = false;
        $oldSchedule = ['08:00', '20:00'];

        try {
            $settings = FortuneTellingSetting::getSettings();
            $oldEnabled = (bool) ($settings->mystic_content_enabled ?? false);

            $raw = $settings->mystic_content_schedule;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : null;
            }
            if (is_array($raw) && ! empty($raw)) {
                $oldSchedule = array_values($raw);
            }
        } catch (\Throwable $e) {
            // fresh install ไม่มี settings — ใช้ default
        }

        FortuneContentCampaign::create([
            'slug' => 'saimoo',
            'name_th' => 'สายมู',
            'description_th' => 'คอนเทนต์สายมูจากคลังหัวข้อเดิม — สายมู/แก้เคล็ด/ปัญหาชีวิต/สิ่งลี้ลับ/รู้หรือไม่ทั่วโลก (หมุน 5 หมวดอัตโนมัติ)',
            'emoji' => '🌙',
            'is_enabled' => $oldEnabled,
            'schedule' => $oldSchedule,
            'topic_source' => FortuneContentCampaign::SOURCE_MYSTIC_TOPICS,
            'use_web_search' => true,
            'sort_order' => 1,
        ]);

        $this->command->info('   ✅ สร้างแคมเปญ "🌙 สายมู" (bridge คลังหัวข้อเดิม'
            .($oldEnabled ? ' — เปิดใช้งานแทนระบบเก่า' : '').')');

        // ปิด toggle ระบบเก่า — แคมเปญใหม่ทำงานแทน (กันโพสซ้ำ 2 ระบบใน slot เดียวกัน)
        if ($oldEnabled) {
            try {
                $settings = FortuneTellingSetting::getSettings();
                $settings->mystic_content_enabled = false;
                $settings->save();
                FortuneTellingSetting::clearSettingsCache();

                Log::info('FortuneContentCampaignSeeder: ย้ายสายมูเข้าแคมเปญ + ปิด mystic_content_enabled เดิม', [
                    'schedule' => $oldSchedule,
                ]);
                $this->command->warn('   ⚠️  ปิด toggle ระบบสายมูเก่าแล้ว — แคมเปญ "สายมู" ทำงานแทน (schedule เดิม)');
            } catch (\Throwable $e) {
                $this->command->error('   ❌ ปิด toggle เก่าไม่สำเร็จ: '.$e->getMessage()
                    .' — ปิดเองที่ /admin/fortune/mystic กันโพสซ้ำ!');
            }
        }
    }

    /**
     * แคมเปญแนวใหม่ 4 แนว — ปิดไว้ก่อน (admin เปิดเอง + ปรับเวลาได้)
     *
     * @return array<array<string, mixed>>
     */
    protected function defaultCampaigns(): array
    {
        return [
            // ── 💪 กำลังใจ + ความเชื่อ
            [
                'slug' => 'kamlangjai',
                'name_th' => 'กำลังใจ+ความเชื่อ',
                'description_th' => 'โพสให้กำลังใจคนเหนื่อย คนท้อ ผสมความเชื่อ/ธรรมะ/พลังบวกเบาๆ อ่านแล้วใจฟู อยากสู้ต่อ — ไม่เทศนา ไม่ยัดเยียด',
                'emoji' => '💪',
                'is_enabled' => false,
                'schedule' => ['06:30'],
                'topic_source' => FortuneContentCampaign::SOURCE_INLINE,
                'keywords' => ['กำลังใจ', 'ความหวัง', 'พลังบวก', 'ข้อคิดชีวิต', 'ความเชื่อ', 'ผ่านวันที่ยากลำบาก', 'ให้กำลังใจตัวเอง'],
                'sub_topic_pool' => [
                    'วันที่เหนื่อยที่สุด มักอยู่ใกล้จุดเปลี่ยนที่สุด',
                    'คนที่ยิ้มง่าย ไม่ได้แปลว่าไม่เคยร้องไห้',
                    'ทำดีแล้วยังไม่เห็นผล ไม่ได้แปลว่าเสียเปล่า',
                    'พักได้ แต่อย่ายอมแพ้',
                    'สิ่งศักดิ์สิทธิ์ช่วยคนที่ช่วยตัวเองก่อน',
                    'ปล่อยวางไม่ใช่ยอมแพ้ แต่คือเลือกสู้ให้ถูกสนาม',
                    'ขอบคุณตัวเองที่ผ่านมาได้ถึงวันนี้',
                    'บททดสอบวันนี้ คือภูมิคุ้มกันของวันหน้า',
                ],
                'hashtag_pool' => [
                    '#กำลังใจ', '#ข้อคิดดีๆ', '#พลังบวก', '#กำลังใจดีๆ', '#ชีวิตต้องสู้',
                    '#ความหวัง', '#ธรรมะสอนใจ', '#คิดบวก', '#ใจฟู', '#สู้ๆนะ',
                ],
                'search_queries' => ['วิธีให้กำลังใจตัวเอง', 'ข้อคิดกำลังใจชีวิต', 'จิตวิทยาพลังบวก'],
                'use_web_search' => true,
                'image_style_hint' => 'warm golden morning light, hopeful uplifting mood',
                'sort_order' => 2,
            ],

            // ── ✨ โดพามีน การใช้ชีวิต
            [
                'slug' => 'dopamine-life',
                'name_th' => 'โดพามีน การใช้ชีวิต',
                'description_th' => 'ไลฟ์สไตล์ feel-good — ความสุขเล็กๆ ในชีวิตประจำวัน เคล็ดลับใช้ชีวิตให้มีความสุขขึ้น สมดุลชีวิต-งาน อ่านแล้วรู้สึกดี อยากลองทำตาม',
                'emoji' => '✨',
                'is_enabled' => false,
                'schedule' => ['12:00'],
                'topic_source' => FortuneContentCampaign::SOURCE_INLINE,
                'keywords' => ['ความสุขเล็กๆ', 'การใช้ชีวิต', 'ไลฟ์สไตล์', 'ดูแลตัวเอง', 'สมดุลชีวิต', 'โดพามีน', 'นิสัยดีๆ'],
                'sub_topic_pool' => [
                    '5 นาทีตอนเช้าที่เปลี่ยนทั้งวัน',
                    'ความสุขราคาถูกที่คนมองข้าม',
                    'เลิกเลื่อนมือถือก่อนนอน แล้วชีวิตเปลี่ยน',
                    'ศิลปะของการอยู่คนเดียวให้เป็น',
                    'กาแฟแก้วโปรด กับความสุขง่ายๆ',
                    'จัดบ้าน = จัดใจ',
                    'เดินเล่นเงียบๆ บำบัดใจได้จริง',
                    'ของขวัญเล็กๆ ที่ควรให้ตัวเองบ้าง',
                ],
                'hashtag_pool' => [
                    '#การใช้ชีวิต', '#ความสุขเล็กๆ', '#ไลฟ์สไตล์', '#ดูแลตัวเอง', '#slowlife',
                    '#ใช้ชีวิตให้มีความสุข', '#feelgood', '#ชีวิตดีๆ', '#สุขง่ายๆ', '#มินิมอล',
                ],
                'search_queries' => ['วิธีใช้ชีวิตให้มีความสุข', 'ความสุขเล็กๆ ในชีวิตประจำวัน', 'นิสัยที่ทำให้ชีวิตดีขึ้น'],
                'use_web_search' => true,
                'image_style_hint' => 'cozy aesthetic lifestyle, soft pastel tones, calm peaceful vibe',
                'sort_order' => 3,
            ],

            // ── ☸️ กฎแห่งกรรม
            [
                'slug' => 'karma',
                'name_th' => 'กฎแห่งกรรม',
                'description_th' => 'เรื่องเล่า/แง่คิดเรื่องกฎแห่งกรรม ทำดีได้ดี เวรกรรมมีจริง — เล่าแบบอบอุ่นชวนคิด ไม่ขู่ ไม่สาปแช่ง ปิดท้ายด้วยแง่คิดให้อยากทำความดี',
                'emoji' => '☸️',
                'is_enabled' => false,
                'schedule' => ['18:00'],
                'topic_source' => FortuneContentCampaign::SOURCE_INLINE,
                'keywords' => ['กฎแห่งกรรม', 'ทำดีได้ดี', 'เวรกรรม', 'บุญกรรม', 'ผลของการกระทำ', 'อโหสิกรรม', 'ธรรมะ'],
                'sub_topic_pool' => [
                    'สิ่งที่เราทำกับคนอื่น สุดท้ายจะย้อนกลับมาเสมอ',
                    'ทำไมคนโกงบางคนดูเจริญ — กรรมทำงานยังไง',
                    'อโหสิกรรม ปลดล็อกใจเราก่อนใคร',
                    'บุญเล็กๆ ที่ทำได้ทุกวันโดยไม่ต้องใช้เงิน',
                    'ปากเป็นกรรม — คำพูดทำร้ายคนได้มากกว่าที่คิด',
                    'ความกตัญญู คือบุญใหญ่ที่คนมองข้าม',
                    'ให้อภัยไม่ใช่เพื่อเขา แต่เพื่อใจเรา',
                    'กรรมเก่าแก้ไม่ได้ แต่กรรมใหม่เลือกได้',
                ],
                'hashtag_pool' => [
                    '#กฎแห่งกรรม', '#ทำดีได้ดี', '#ธรรมะสอนใจ', '#บุญกรรม', '#เวรกรรมมีจริง',
                    '#ธรรมะ', '#สายบุญ', '#คิดดีทำดี', '#อโหสิกรรม', '#ข้อคิดธรรมะ',
                ],
                'search_queries' => ['กฎแห่งกรรม เรื่องจริง', 'ธรรมะ ผลของกรรม', 'ทำดีได้ดี ตัวอย่าง'],
                'use_web_search' => true,
                'image_style_hint' => 'serene thai buddhist atmosphere, candlelight, peaceful spiritual mood',
                'sort_order' => 4,
            ],

            // ── 🧠 เรื่องเล่าจิตวิทยา
            [
                'slug' => 'psychology-story',
                'name_th' => 'เรื่องเล่าจิตวิทยา',
                'description_th' => 'เรื่องเล่า/ความรู้จิตวิทยาใกล้ตัว — พฤติกรรมคน ความสัมพันธ์ ทำไมเราคิด/รู้สึกแบบนี้ เล่าสนุกเข้าใจง่าย มีงานวิจัย/หลักจิตวิทยาอ้างอิงเบาๆ',
                'emoji' => '🧠',
                'is_enabled' => false,
                'schedule' => ['21:00'],
                'topic_source' => FortuneContentCampaign::SOURCE_INLINE,
                'keywords' => ['จิตวิทยา', 'พฤติกรรมมนุษย์', 'ความสัมพันธ์', 'จิตวิทยาความรัก', 'นิสัยคน', 'จิตใต้สำนึก', 'ปมในใจ'],
                'sub_topic_pool' => [
                    'ทำไมเราจำคำด่าได้แม่นกว่าคำชม',
                    'คนที่ชอบขอโทษทั้งที่ไม่ผิด กำลังบอกอะไรเรา',
                    'ทำไมยิ่งห้ามยิ่งอยากทำ — จิตวิทยา Reverse',
                    'ภาษารักทั้ง 5 แบบ คุณเป็นแบบไหน',
                    'ทำไมเวลาผ่านไปเร็วขึ้นทุกปี',
                    'คนเงียบไม่ได้แปลว่าไม่มีอะไรจะพูด',
                    'ทำไมเราตัดใจจากคนที่ทำร้ายเราไม่ได้',
                    'สมองเราหลอกเราเรื่องอะไรบ้างในแต่ละวัน',
                ],
                'hashtag_pool' => [
                    '#จิตวิทยา', '#เรื่องเล่าจิตวิทยา', '#พฤติกรรมมนุษย์', '#จิตวิทยาความรัก', '#ความสัมพันธ์',
                    '#รู้หรือไม่', '#จิตวิทยาน่ารู้', '#ความรู้รอบตัว', '#พัฒนาตัวเอง', '#เข้าใจตัวเอง',
                ],
                'search_queries' => ['จิตวิทยาพฤติกรรมมนุษย์ น่ารู้', 'งานวิจัยจิตวิทยา น่าสนใจ', 'จิตวิทยาความสัมพันธ์'],
                'use_web_search' => true,
                'image_style_hint' => 'moody thoughtful atmosphere, dramatic soft lighting, conceptual minimal scene',
                'sort_order' => 5,
            ],
        ];
    }
}
