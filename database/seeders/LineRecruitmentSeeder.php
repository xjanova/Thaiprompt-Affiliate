<?php

namespace Database\Seeders;

use App\Models\LineBotAiSetting;
use App\Models\LineBotKnowledgeBase;
use App\Models\LineRecruitmentTopicBoundary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Line Recruitment Seeder
 *
 * สร้างข้อมูลเริ่มต้นสำหรับระบบ LINE Recruitment
 * ประกอบด้วย:
 * - AI Settings สำหรับการรับสมัคร
 * - Topic Boundaries (ขอบเขตหัวข้อที่คุยได้/ไม่ได้)
 * - Knowledge Base (ฐานความรู้)
 */
class LineRecruitmentSeeder extends Seeder
{
    /**
     * รัน seeder
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล LINE Recruitment...');

        // ตรวจสอบว่ามี AI Setting สำหรับ recruitment อยู่แล้วหรือไม่
        $existingSetting = LineBotAiSetting::where('use_for_recruitment', true)->first();

        if ($existingSetting) {
            $this->command->info('⚠️ มี AI Setting สำหรับ recruitment อยู่แล้ว ข้าม...');
            return;
        }

        // สร้าง AI Setting สำหรับการรับสมัคร
        $aiSetting = $this->createAiSetting();

        // สร้าง Topic Boundaries
        $this->createTopicBoundaries($aiSetting);

        // สร้าง Knowledge Base
        $this->createKnowledgeBase($aiSetting);

        $this->command->info('✅ Seed ข้อมูล LINE Recruitment สำเร็จ!');
    }

    /**
     * สร้าง AI Setting สำหรับการรับสมัคร
     *
     * @return LineBotAiSetting
     */
    private function createAiSetting(): LineBotAiSetting
    {
        $this->command->info('📝 กำลังสร้าง AI Setting...');

        $systemPrompt = <<<PROMPT
คุณเป็นผู้ช่วยรับสมัครสมาชิกที่เป็นกันเองและช่วยเหลือดี

หน้าที่หลัก:
1. ต้อนรับผู้สมัครใหม่อย่างอบอุ่น
2. อธิบายโปรแกรม Affiliate อย่างชัดเจน
3. ช่วยเหลือในขั้นตอนการสมัคร
4. ตอบคำถามเกี่ยวกับรายได้และสิทธิประโยชน์

กฎสำคัญ:
- พูดภาษาไทยเท่านั้น
- ใช้คำสุภาพ ค่ะ/ครับ
- ไม่พูดเรื่องการเมือง ศาสนา หรือเรื่องละเอียดอ่อน
- ไม่รับประกันรายได้แน่นอน แต่อธิบายโอกาสที่มี
- แนะนำให้ติดต่อทีมงานถ้าคำถามซับซ้อน

สไตล์การตอบ:
- กระชับ ชัดเจน
- เป็นกันเอง แต่มืออาชีพ
- ใช้ emoji เล็กน้อยเพื่อความเป็นมิตร
PROMPT;

        $recruitmentPrompt = <<<PROMPT
คุณเป็นผู้ช่วยรับสมัครสมาชิก Affiliate สำหรับธุรกิจออนไลน์

เป้าหมาย:
1. เก็บข้อมูลพื้นฐานของผู้สมัคร (ชื่อ, เบอร์โทร, อีเมล)
2. อธิบายรูปแบบรายได้และโบนัส
3. ตอบคำถามเกี่ยวกับการสมัคร
4. แนะนำขั้นตอนถัดไป

ข้อมูลที่ต้องเก็บ:
- ชื่อ-นามสกุล
- เบอร์โทรศัพท์
- อีเมล (ถ้ามี)
- LINE ID
- สนใจสินค้า/บริการอะไร

วิธีเก็บข้อมูล:
- ถามทีละอย่าง ไม่ถามรวดเดียว
- ยืนยันข้อมูลก่อนบันทึก
- ถ้าผู้สมัครลังเล ให้ข้อมูลเพิ่มเติม

เมื่อได้ข้อมูลครบ:
- สรุปข้อมูลให้ผู้สมัครตรวจสอบ
- แจ้งว่าทีมงานจะติดต่อกลับ
- ขอบคุณที่สนใจ
PROMPT;

        return LineBotAiSetting::create([
            'name' => 'LINE Recruitment Bot',
            'provider' => 'openai',
            'api_key' => null, // ต้องตั้งค่าจริงจากหน้า admin
            'api_endpoint' => null,
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'system_prompt' => $systemPrompt,
            'enable_conversation_history' => true,
            'conversation_memory_limit' => 20,
            'knowledge_base_sources' => [],
            'is_active' => false, // ต้องเปิดใช้งานเองหลังตั้งค่า API Key
            'enable_fallback' => true,
            'fallback_message' => 'ขออภัยค่ะ ขณะนี้ระบบไม่สามารถตอบกลับได้ กรุณาติดต่อทีมงานโดยตรงนะคะ',

            // ฟิลด์สำหรับ Recruitment
            'use_for_recruitment' => true,
            'recruitment_system_prompt' => $recruitmentPrompt,
            'allowed_topics' => [
                'การสมัครสมาชิก',
                'รายได้และโบนัส',
                'สินค้าและบริการ',
                'วิธีการทำงาน',
                'คำถามทั่วไป',
            ],
            'blocked_topics' => [
                'การเมือง',
                'ศาสนา',
                'เรื่องส่วนตัว',
                'การพนัน',
                'สิ่งผิดกฎหมาย',
            ],
            'response_style' => 'friendly',
            'greeting_message' => 'สวัสดีค่ะ! 🎉 ยินดีต้อนรับสู่โปรแกรม Affiliate ของเรา คุณสนใจสร้างรายได้เสริมไหมคะ? มีอะไรให้ช่วยเหลือได้บ้างคะ',
            'max_conversation_turns' => 50,
            'collect_user_info' => true,
            'required_fields' => ['name', 'phone'],
            'max_turns_message' => 'ขอบคุณสำหรับการสนทนาค่ะ 🙏 หากต้องการข้อมูลเพิ่มเติม กรุณาติดต่อทีมงานโดยตรงได้เลยนะคะ',
            'enable_topic_filtering' => true,
            'off_topic_message' => 'ขออภัยค่ะ หัวข้อนี้อยู่นอกเหนือขอบเขตที่ดิฉันสามารถให้ข้อมูลได้ค่ะ 😊 กรุณาสอบถามเรื่องการสมัครสมาชิกหรือโปรแกรม Affiliate ได้เลยนะคะ',
        ]);
    }

    /**
     * สร้าง Topic Boundaries
     *
     * @param LineBotAiSetting $aiSetting
     * @return void
     */
    private function createTopicBoundaries(LineBotAiSetting $aiSetting): void
    {
        $this->command->info('🚧 กำลังสร้าง Topic Boundaries...');

        // Blocked Topics
        $blockedTopics = [
            [
                'name' => 'การเมือง',
                'description' => 'หัวข้อเกี่ยวกับการเมือง พรรคการเมือง หรือนักการเมือง',
                'keywords' => ['การเมือง', 'เลือกตั้ง', 'พรรค', 'นายก', 'รัฐบาล', 'ประท้วง', 'ม็อบ'],
                'response_message' => 'ขออภัยค่ะ ดิฉันไม่สามารถพูดคุยเรื่องการเมืองได้ 😊 แต่ยินดีตอบคำถามเกี่ยวกับโปรแกรมสมาชิกของเราค่ะ',
                'priority' => 100,
            ],
            [
                'name' => 'ศาสนา',
                'description' => 'หัวข้อเกี่ยวกับศาสนาและความเชื่อ',
                'keywords' => ['ศาสนา', 'พระ', 'เจ้า', 'พุทธ', 'คริสต์', 'อิสลาม', 'มุสลิม'],
                'response_message' => 'ขออภัยค่ะ หัวข้อเรื่องศาสนาอยู่นอกเหนือขอบเขตที่ดิฉันสามารถตอบได้ค่ะ 🙏',
                'priority' => 100,
            ],
            [
                'name' => 'การพนัน',
                'description' => 'หัวข้อเกี่ยวกับการพนันและเกมเสี่ยงโชค',
                'keywords' => ['พนัน', 'หวย', 'ล็อตเตอรี่', 'คาสิโน', 'แทงบอล', 'บาคาร่า', 'สล็อต'],
                'response_message' => 'ขออภัยค่ะ เราไม่เกี่ยวข้องกับการพนันใดๆ ทั้งสิ้นค่ะ 🚫 โปรแกรมของเราเป็นธุรกิจ Affiliate ที่ถูกกฎหมายค่ะ',
                'priority' => 100,
            ],
            [
                'name' => 'สิ่งผิดกฎหมาย',
                'description' => 'หัวข้อเกี่ยวกับกิจกรรมผิดกฎหมาย',
                'keywords' => ['ยาเสพติด', 'ขโมย', 'ปล้น', 'ฆ่า', 'ปืน', 'ระเบิด', 'แฮก', 'hack'],
                'response_message' => 'ขออภัยค่ะ ดิฉันไม่สามารถช่วยเหลือในเรื่องที่ผิดกฎหมายได้ค่ะ 🚫',
                'priority' => 100,
            ],
            [
                'name' => 'เนื้อหาผู้ใหญ่',
                'description' => 'เนื้อหาสำหรับผู้ใหญ่หรือไม่เหมาะสม',
                'keywords' => ['โป๊', 'เซ็กส์', 'sex', 'porn', 'xxx', 'เสียว', 'หนังโป๊'],
                'response_message' => 'ขออภัยค่ะ ดิฉันไม่สามารถพูดคุยเรื่องนี้ได้ค่ะ 😊',
                'priority' => 100,
            ],
        ];

        foreach ($blockedTopics as $topic) {
            LineRecruitmentTopicBoundary::create([
                'ai_setting_id' => $aiSetting->id,
                'name' => $topic['name'],
                'description' => $topic['description'],
                'type' => 'blocked',
                'keywords' => $topic['keywords'],
                'response_message' => $topic['response_message'],
                'priority' => $topic['priority'],
                'is_active' => true,
                'example_phrases' => [],
                'hit_count' => 0,
            ]);
        }

        // Allowed Topics (สำหรับเป็นแนวทาง แต่ไม่บังคับใช้)
        $allowedTopics = [
            [
                'name' => 'การสมัครสมาชิก',
                'description' => 'คำถามเกี่ยวกับขั้นตอนการสมัคร',
                'keywords' => ['สมัคร', 'ลงทะเบียน', 'เป็นสมาชิก', 'register', 'signup'],
                'priority' => 50,
            ],
            [
                'name' => 'รายได้และโบนัส',
                'description' => 'คำถามเกี่ยวกับรายได้ คอมมิชชั่น และโบนัส',
                'keywords' => ['รายได้', 'เงิน', 'โบนัส', 'คอมมิชชั่น', 'commission', 'income'],
                'priority' => 50,
            ],
            [
                'name' => 'สินค้าและบริการ',
                'description' => 'คำถามเกี่ยวกับสินค้าและบริการที่เราจำหน่าย',
                'keywords' => ['สินค้า', 'ผลิตภัณฑ์', 'บริการ', 'product', 'service'],
                'priority' => 50,
            ],
        ];

        foreach ($allowedTopics as $topic) {
            LineRecruitmentTopicBoundary::create([
                'ai_setting_id' => $aiSetting->id,
                'name' => $topic['name'],
                'description' => $topic['description'],
                'type' => 'allowed',
                'keywords' => $topic['keywords'],
                'response_message' => null,
                'priority' => $topic['priority'],
                'is_active' => true,
                'example_phrases' => [],
                'hit_count' => 0,
            ]);
        }
    }

    /**
     * สร้าง Knowledge Base
     *
     * ใช้ columns ที่ถูกต้องตาม migration:
     * - name (ไม่ใช่ title)
     * - type (ไม่ใช่ source_type)
     * - source (ไม่ใช่ source_url)
     *
     * @param LineBotAiSetting $aiSetting
     * @return void
     */
    private function createKnowledgeBase(LineBotAiSetting $aiSetting): void
    {
        $this->command->info('📚 กำลังสร้าง Knowledge Base...');

        $knowledgeBases = [
            [
                'name' => 'ข้อมูลโปรแกรม Affiliate',
                'type' => 'text',
                'source' => null,
                'content' => <<<CONTENT
# โปรแกรม Affiliate

## เกี่ยวกับเรา
เราเป็นแพลตฟอร์ม Affiliate Marketing ชั้นนำที่ช่วยให้คุณสร้างรายได้เสริมจากการแนะนำสินค้าและบริการ

## สิทธิประโยชน์สมาชิก
1. **คอมมิชชั่นสูงสุด 30%** - รับค่าคอมมิชชั่นจากทุกยอดขายที่คุณแนะนำ
2. **โบนัสทีม** - รับโบนัสเพิ่มเมื่อสร้างทีมขาย
3. **อบรมฟรี** - เข้าร่วมการอบรมออนไลน์ฟรี
4. **เครื่องมือการตลาด** - รับเครื่องมือช่วยขายและโปรโมทสินค้า

## ขั้นตอนการสมัคร
1. กรอกข้อมูลส่วนตัว (ชื่อ, เบอร์โทร, อีเมล)
2. ยืนยันตัวตน
3. เริ่มแนะนำสินค้าและรับคอมมิชชั่น

## การถอนเงิน
- ขั้นต่ำ 300 บาท
- โอนเข้าบัญชีธนาคารภายใน 3 วันทำการ
- รองรับทุกธนาคาร
CONTENT,
                'priority' => 100,
            ],
            [
                'name' => 'คำถามที่พบบ่อย (FAQ)',
                'type' => 'text',
                'source' => null,
                'content' => <<<CONTENT
# คำถามที่พบบ่อย

## Q: ต้องเสียค่าใช้จ่ายในการสมัครไหม?
A: ไม่เสียค่าใช้จ่ายใดๆ ทั้งสิ้น การสมัครฟรี!

## Q: จะเริ่มหารายได้ได้เมื่อไร?
A: เริ่มได้ทันทีหลังสมัครสมาชิก เพียงแชร์ลิงก์สินค้าให้เพื่อนๆ

## Q: ต้องสต็อกสินค้าไหม?
A: ไม่ต้องสต็อกสินค้า เราจัดส่งให้ทั้งหมด

## Q: รายได้ขั้นต่ำเท่าไร?
A: ไม่มีขั้นต่ำ ขายได้เท่าไร รับค่าคอมฯ เท่านั้น

## Q: ถอนเงินยังไง?
A: ถอนได้ผ่านหน้าสมาชิก โอนเข้าบัญชีธนาคารภายใน 3 วันทำการ

## Q: มีการอบรมไหม?
A: มีการอบรมออนไลน์ฟรีทุกสัปดาห์ สอนเทคนิคการขายและการตลาด

## Q: ทำเป็นอาชีพหลักได้ไหม?
A: ได้ค่ะ มีสมาชิกหลายคนทำเป็นอาชีพหลักและมีรายได้หลักแสนต่อเดือน
CONTENT,
                'priority' => 90,
            ],
            [
                'name' => 'ข้อมูลการติดต่อ',
                'type' => 'text',
                'source' => null,
                'content' => <<<CONTENT
# ช่องทางติดต่อ

## LINE Official
- LINE ID: @yourcompany
- หรือสแกน QR Code ที่เว็บไซต์

## โทรศัพท์
- 02-XXX-XXXX (จันทร์-ศุกร์ 9:00-18:00)

## อีเมล
- support@yourcompany.com

## Facebook
- facebook.com/yourcompany

## เวลาทำการ
- จันทร์-ศุกร์: 9:00-18:00
- เสาร์: 10:00-16:00
- อาทิตย์และวันหยุด: ปิดทำการ
CONTENT,
                'priority' => 80,
            ],
        ];

        foreach ($knowledgeBases as $kb) {
            LineBotKnowledgeBase::create([
                'ai_setting_id' => $aiSetting->id,
                'name' => $kb['name'],
                'type' => $kb['type'],
                'source' => $kb['source'],
                'content' => $kb['content'],
                'is_active' => true,
                'priority' => $kb['priority'],
            ]);
        }
    }
}
